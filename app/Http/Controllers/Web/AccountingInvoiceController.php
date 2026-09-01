<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AuthorizesAgencyAccess;
use App\Mail\InvoiceSentToClient;
use App\Models\AccountingInvoice;
use App\Models\AccountingRateCard;
use App\Models\AccountingSetting;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\DeliveryNote;
use App\Services\Accounting\ClientCreditService;
use App\Services\Accounting\InvoiceFromDeliveryNoteService;
use App\Support\QueryDate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class AccountingInvoiceController extends Controller
{
    use AuthorizesAgencyAccess;

    private function ensureCanBrowseInvoices(): ?\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (! $user || (! $user->is_admin && ! $user->isAgencyUser())) {
            return redirect()->route('packages.index');
        }

        return null;
    }

    private function ensureCanViewInvoice(AccountingInvoice $invoice): ?\Illuminate\Http\RedirectResponse
    {
        if ($redirect = $this->ensureCanBrowseInvoices()) {
            return $redirect;
        }
        if (auth()->user()->isAgencyUser()) {
            $this->ensureUserCanAccessAgency($invoice->agency);
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCanBrowseInvoices()) {
            return $redirect;
        }

        $query = AccountingInvoice::with([
            'agency:id,name,code,billing_email',
            'agency.users:id,agency_id,email',
            'deliveryNote:id,code',
            'lines:id,accounting_invoice_id,service_type',
        ])
            ->withCount('lines')
            ->orderByDesc('id');

        $this->applyInvoiceFilters($query, $request);

        $statsQuery = AccountingInvoice::query();
        $this->applyInvoiceFilters($statsQuery, $request);

        $statsTotal = (clone $statsQuery)->count();
        $statsPending = (clone $statsQuery)->where('status', 'issued')->count();
        $statsPartial = (clone $statsQuery)->where('status', 'partially_paid')->count();
        $statsPaid = (clone $statsQuery)->where('status', 'paid')->count();
        $statsTotalUsd = (clone $statsQuery)->where('status', '!=', 'void')->sum('total_usd');

        $invoices = $query->paginate(20)->withQueryString();

        return view('accounting.invoices.index', compact(
            'invoices',
            'statsTotal',
            'statsPending',
            'statsPartial',
            'statsPaid',
            'statsTotalUsd'
        ));
    }

    public function show(AccountingInvoice $invoice)
    {
        if ($redirect = $this->ensureCanViewInvoice($invoice)) {
            return $redirect;
        }

        $invoice->load(['lines', 'agency.users', 'deliveryNote', 'createdBy', 'voidedBy']);

        return view('accounting.invoices.show', [
            'invoice' => $invoice,
            'company' => AccountingSetting::current()->toCompanyArray(),
        ]);
    }

    public function voucher(AccountingInvoice $invoice)
    {
        if ($redirect = $this->ensureCanViewInvoice($invoice)) {
            return $redirect;
        }

        $invoice->load(['lines', 'agency', 'deliveryNote', 'createdBy']);

        return view('accounting.invoices.voucher', [
            'invoice' => $invoice,
            'company' => AccountingSetting::current()->toCompanyArray(),
        ]);
    }

    public function pdf(AccountingInvoice $invoice)
    {
        if ($redirect = $this->ensureCanViewInvoice($invoice)) {
            return $redirect;
        }

        if ($invoice->isVoid()) {
            return redirect()
                ->back()
                ->with('error', 'No se puede descargar el voucher de una factura anulada.');
        }

        $invoice->load(['lines', 'agency', 'deliveryNote', 'createdBy']);

        $pdf = Pdf::loadView('accounting.invoices.voucher-pdf', [
            'invoice' => $invoice,
            'company' => AccountingSetting::current()->toCompanyArray(),
        ])->setPaper([0, 0, 226.77, 1200], 'portrait');

        $filename = 'voucher-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', $invoice->folio).'.pdf';

        return $pdf->download($filename);
    }

    public function publicVoucher(AccountingInvoice $invoice)
    {
        if ($invoice->isVoid()) {
            abort(410, 'Esta factura fue anulada.');
        }

        $invoice->load(['lines', 'agency', 'deliveryNote', 'createdBy']);

        return view('accounting.invoices.voucher', [
            'invoice' => $invoice,
            'company' => AccountingSetting::current()->toCompanyArray(),
        ]);
    }

    public function sendEmail(Request $request, AccountingInvoice $invoice)
    {
        if ($invoice->isVoid()) {
            return redirect()->back()->with('error', 'No se puede enviar una factura anulada.');
        }

        $invoice->load(['agency.users', 'deliveryNote', 'lines']);
        $email = $invoice->agency?->billingEmail();
        if (! $email) {
            return redirect()->back()->with(
                'error',
                'Este cliente no tiene correo registrado. Agréguelo en la ficha del cliente (correo de facturación).'
            );
        }

        try {
            Mail::to($email)->send(new InvoiceSentToClient($invoice));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'No se pudo enviar el correo: '.$e->getMessage());
        }

        $invoice->update(['emailed_at' => now()]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_invoice',
            'auditable_id' => $invoice->id,
            'action' => 'invoice_emailed',
            'summary' => 'Envió factura '.$invoice->folio.' a '.$email,
            'old_values' => null,
            'new_values' => ['email' => $email],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Factura '.$invoice->folio.' enviada a '.$email.'.');
    }

    public function create()
    {
        $notes = DeliveryNote::query()
            ->with(['agency'])
            ->withCount('deliveries')
            ->whereHas('deliveries')
            ->whereDoesntHave('accountingInvoices', fn ($q) => $q->where('status', '!=', 'void'))
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('accounting.invoices.create', compact('notes'));
    }

    public function startCreate(Request $request)
    {
        $data = $request->validate([
            'delivery_note_id' => 'required|exists:delivery_notes,id',
        ], [
            'delivery_note_id.required' => 'Seleccione una hoja de salida.',
            'delivery_note_id.exists' => 'La hoja de salida no es válida.',
        ]);

        return redirect()->route('accounting.invoices.create-from-note', $data['delivery_note_id']);
    }

    public function void(Request $request, AccountingInvoice $invoice)
    {
        $data = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ], [
            'void_reason.required' => 'Indique el motivo de anulación.',
            'void_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if (! $invoice->canVoid()) {
            $message = $invoice->isVoid()
                ? 'Esta factura ya está anulada.'
                : 'No se puede anular: tiene cobros en caja aplicados. Cancele los cobros primero.';

            return redirect()->back()->with('error', $message);
        }

        $previous = $invoice->status;
        try {
            DB::transaction(function () use ($invoice, $data, $request) {
                $locked = AccountingInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if (! $locked->canVoid()) {
                    throw new InvalidArgumentException(
                        $locked->isVoid()
                            ? 'Esta factura ya está anulada.'
                            : 'No se puede anular: tiene cobros en caja aplicados. Cancele los cobros primero.'
                    );
                }

                app(ClientCreditService::class)->reverseAppliedToInvoice($locked);
                $locked->update([
                    'status' => 'void',
                    'void_reason' => trim($data['void_reason']),
                    'voided_at' => now(),
                    'voided_by' => $request->user()->id,
                    'amount_paid' => 0,
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $invoice->refresh();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_invoice',
            'auditable_id' => $invoice->id,
            'action' => 'invoice_voided',
            'summary' => 'Anuló factura '.$invoice->folio,
            'old_values' => ['status' => $previous],
            'new_values' => ['status' => 'void', 'void_reason' => $invoice->void_reason],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('accounting.invoices.show', $invoice)
            ->with('success', 'Factura '.$invoice->folio.' anulada. Puede emitir otra para la misma hoja de salida.');
    }

    public function destroy(Request $request, AccountingInvoice $invoice)
    {
        if (! $invoice->canDelete()) {
            $message = $invoice->paymentAllocations()->exists()
                ? 'No se puede eliminar: tiene cobros asociados. Conserve la factura anulada para auditoría.'
                : 'Solo se pueden eliminar facturas anuladas o en borrador. Anúlela primero para conservar el historial del folio.';

            return redirect()
                ->back()
                ->with('error', $message);
        }

        $folio = $invoice->folio;
        $id = $invoice->id;

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_invoice',
            'auditable_id' => $id,
            'action' => 'invoice_deleted',
            'summary' => 'Eliminó factura '.$folio,
            'old_values' => ['folio' => $folio, 'status' => $invoice->status],
            'new_values' => null,
            'ip_address' => $request->ip(),
        ]);

        $invoice->delete();

        return redirect()
            ->route('accounting.invoices.index')
            ->with('success', 'Factura '.$folio.' eliminada.');
    }

    public function createFromNote(DeliveryNote $deliveryNote, InvoiceFromDeliveryNoteService $service)
    {
        $deliveryNote->load(['deliveries.preregistration', 'agency']);

        $active = AccountingInvoice::query()
            ->where('delivery_note_id', $deliveryNote->id)
            ->where('status', '!=', 'void')
            ->first();

        if ($active) {
            return redirect()
                ->route('accounting.invoices.show', $active)
                ->with('success', 'Ya existe la factura '.$active->folio.' para esta hoja de salida.');
        }

        try {
            $preview = $service->preview($deliveryNote);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('accounting.invoices.create')
                ->with('error', $e->getMessage());
        }

        $agencyId = $preview['agency_id'];
        $suggestedRates = [
            'AIR' => AccountingRateCard::currentFor($agencyId, 'AIR')?->price_per_lb,
            'SEA' => AccountingRateCard::currentFor($agencyId, 'SEA')?->price_per_lb,
            'CFT' => AccountingRateCard::currentFor($agencyId, 'CFT')?->price_per_lb,
        ];

        $agency = $deliveryNote->agency ?? Agency::find($agencyId);
        $creditBalance = round((float) ($agency?->credit_balance_usd ?? 0), 2);

        return view('accounting.invoices.create-from-note', [
            'deliveryNote' => $deliveryNote,
            'preview' => $preview,
            'suggestedRates' => $suggestedRates,
            'exchangeRate' => (float) \App\Models\AccountingSetting::current()->exchange_rate,
            'creditBalance' => $creditBalance,
        ]);
    }

    public function storeFromNote(Request $request, DeliveryNote $deliveryNote, InvoiceFromDeliveryNoteService $service)
    {
        $data = $request->validate([
            'rate_air' => 'nullable|numeric|min:0',
            'rate_sea' => 'nullable|numeric|min:0',
            'rate_cft' => 'nullable|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'persist_rates' => 'sometimes|boolean',
            'apply_credit' => 'sometimes|boolean',
            'apply_credit_amount' => 'nullable|numeric|min:0',
        ]);

        $overrides = [];
        if ($request->filled('rate_air')) {
            $overrides['AIR'] = (float) $data['rate_air'];
        }
        if ($request->filled('rate_sea')) {
            $overrides['SEA'] = (float) $data['rate_sea'];
        }
        if ($request->filled('rate_cft')) {
            $overrides['CFT'] = (float) $data['rate_cft'];
        }

        try {
            $invoice = DB::transaction(function () use ($service, $deliveryNote, $request, $overrides, $data) {
                $created = $service->create(
                    $deliveryNote,
                    $request->user(),
                    $overrides,
                    (float) $data['exchange_rate']
                );

                if ($request->boolean('apply_credit')) {
                    $agency = $created->agency ?? Agency::find($created->agency_id);
                    if ($agency) {
                        $credits = app(ClientCreditService::class);
                        $available = $credits->balance($agency);
                        $requested = $request->filled('apply_credit_amount')
                            ? (float) $request->input('apply_credit_amount')
                            : $available;
                        $credits->applyToInvoice($agency, $created, $requested, $request->user()->id);
                        $created->refresh();
                    }
                }

                return $created;
            });
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        if ($request->boolean('persist_rates')) {
            $agencyId = (int) $invoice->agency_id;
            foreach ($overrides as $service => $price) {
                if ($price === null) {
                    continue;
                }
                $current = AccountingRateCard::currentFor($agencyId, $service);
                if ($current && (float) $current->price_per_lb === (float) $price) {
                    continue;
                }
                if ($current && $current->effective_to === null) {
                    $current->update(['effective_to' => now()->subDay()->toDateString()]);
                }
                AccountingRateCard::create([
                    'agency_id' => $agencyId,
                    'service_type' => $service,
                    'price_per_lb' => $price,
                    'cost_per_lb' => $current?->cost_per_lb ?? 0,
                    'currency' => 'USD',
                    'effective_from' => now()->toDateString(),
                    'effective_to' => null,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return redirect()
            ->route('accounting.invoices.voucher', $invoice)
            ->with('success', 'Factura PrimeTrack '.$invoice->folio.' emitida.');
    }

    private function applyInvoiceFilters($query, Request $request): void
    {
        $isClient = auth()->user()?->isAgencyUser();

        if ($isClient || ($request->input('status') !== 'void' && ! $request->boolean('include_void'))) {
            $query->where('status', '!=', 'void');
        }

        if ($request->filled('status') && ! ($isClient && $request->input('status') === 'void')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('client')) {
            $name = trim((string) $request->client);
            $query->whereHas('agency', fn ($q) => $q->where('name', 'like', "%{$name}%"));
        }

        if ($issuedAt = QueryDate::parse($request, 'issued_at')) {
            $query->whereDate('issued_at', $issuedAt->toDateString());
        }

        $allowed = auth()->user()?->allowedAgencyIds();
        if ($allowed !== null) {
            $query->whereIn('agency_id', $allowed);
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('folio', 'like', "%{$s}%")
                    ->orWhereHas('deliveryNote', fn ($dq) => $dq->where('code', 'like', "%{$s}%"))
                    ->orWhereHas('agency', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
            });
        }
    }
}
