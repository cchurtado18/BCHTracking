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
        if (! $user || (! $user->is_admin && ! $user->isAgencyUser()) || $user->isPackagesOnlyPortal()) {
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
            'deliveryNotes:id,code',
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

        $invoice->load(['lines', 'agency.users', 'deliveryNote', 'deliveryNotes', 'createdBy', 'voidedBy']);

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

        $invoice->load(['lines', 'agency', 'deliveryNote', 'deliveryNotes', 'createdBy']);

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

        $invoice->load(['lines', 'agency', 'deliveryNote', 'deliveryNotes', 'createdBy']);

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

        $invoice->load(['lines', 'agency', 'deliveryNote', 'deliveryNotes', 'createdBy']);

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

        $invoice->load(['agency.parent.parent.parent', 'agency.users', 'deliveryNote', 'deliveryNotes', 'lines']);
        $email = $invoice->agency?->invoiceEmail();
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
            ->with(['agency.parent.parent.parent', 'deliveries.preregistration:id,agency_id'])
            ->withCount('deliveries')
            ->whereHas('deliveries')
            ->withoutActiveInvoice()
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return view('accounting.invoices.create', compact('notes'));
    }

    public function startCreate(Request $request)
    {
        if ($request->filled('delivery_note_id') && ! $request->filled('delivery_note_ids')) {
            $request->merge(['delivery_note_ids' => [(int) $request->input('delivery_note_id')]]);
        }

        $data = $request->validate([
            'delivery_note_ids' => 'required|array|min:1',
            'delivery_note_ids.*' => 'integer|exists:delivery_notes,id',
        ], [
            'delivery_note_ids.required' => 'Seleccione al menos una hoja de salida.',
            'delivery_note_ids.min' => 'Seleccione al menos una hoja de salida.',
        ]);

        $ids = collect($data['delivery_note_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $primary = $ids->first();
        $extra = $ids->slice(1)->values()->all();

        return redirect()->route('accounting.invoices.create-from-note', array_filter([
            'deliveryNote' => $primary,
            'notes' => $extra ?: null,
        ]));
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

    public function createFromNote(Request $request, DeliveryNote $deliveryNote, InvoiceFromDeliveryNoteService $service)
    {
        $deliveryNote->load(['deliveries.preregistration', 'agency.parent.parent.parent']);

        $active = AccountingInvoice::query()
            ->coveringNote((int) $deliveryNote->id)
            ->where('status', '!=', 'void')
            ->first();

        if ($active) {
            return redirect()
                ->route('accounting.invoices.show', $active)
                ->with('success', 'Ya existe la factura '.$active->folio.' para esta hoja de salida.');
        }

        $selectedNotes = $this->selectedNotesForInvoice($request, $deliveryNote);

        try {
            $preview = $service->previewNotes($selectedNotes);
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

        $agency = Agency::with('parent')->find($agencyId) ?? $deliveryNote->agency;
        $creditBalance = round((float) ($agency?->credit_balance_usd ?? 0), 2);
        $compatibleNotes = $this->compatibleUninvoicedNotes($deliveryNote, $selectedNotes);

        return view('accounting.invoices.create-from-note', [
            'deliveryNote' => $deliveryNote,
            'selectedNotes' => $selectedNotes,
            'compatibleNotes' => $compatibleNotes,
            'preview' => $preview,
            'suggestedRates' => $suggestedRates,
            'exchangeRate' => (float) \App\Models\AccountingSetting::current()->exchange_rate,
            'creditBalance' => $creditBalance,
            'deliveryFee' => old('delivery_fee', 0),
        ]);
    }

    public function storeFromNote(Request $request, DeliveryNote $deliveryNote, InvoiceFromDeliveryNoteService $service)
    {
        $data = $request->validate([
            'rate_air' => 'nullable|numeric|min:0',
            'rate_sea' => 'nullable|numeric|min:0',
            'rate_cft' => 'nullable|numeric|min:0',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_note_ids' => 'nullable|array',
            'delivery_note_ids.*' => 'integer|exists:delivery_notes,id',
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

        $extraNotes = $this->selectedNotesForInvoice($request, $deliveryNote)
            ->reject(fn (DeliveryNote $n) => (int) $n->id === (int) $deliveryNote->id)
            ->values();
        $deliveryFee = (float) ($data['delivery_fee'] ?? 0);

        try {
            $invoice = DB::transaction(function () use ($service, $deliveryNote, $request, $overrides, $data, $deliveryFee, $extraNotes) {
                $created = $service->create(
                    $deliveryNote,
                    $request->user(),
                    $overrides,
                    (float) $data['exchange_rate'],
                    $deliveryFee,
                    $extraNotes
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
                    ->orWhereHas('deliveryNotes', fn ($dq) => $dq->where('code', 'like', "%{$s}%"))
                    ->orWhereHas('agency', fn ($aq) => $aq->where('name', 'like', "%{$s}%"));
            });
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, DeliveryNote>
     */
    private function selectedNotesForInvoice(Request $request, DeliveryNote $primary): \Illuminate\Support\Collection
    {
        $ids = collect($request->input('delivery_note_ids', $request->query('notes', [])))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $ids->prepend((int) $primary->id);
        $ids = $ids->unique()->values();

        $notes = DeliveryNote::query()
            ->whereIn('id', $ids->all())
            ->with(['deliveries.preregistration', 'agency.parent.parent.parent'])
            ->get()
            ->sortBy(fn (DeliveryNote $n) => $ids->search((int) $n->id))
            ->values();

        if (! $notes->contains(fn (DeliveryNote $n) => (int) $n->id === (int) $primary->id)) {
            $notes->prepend($primary);
        }

        return $notes;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DeliveryNote>  $alreadySelected
     * @return \Illuminate\Support\Collection<int, DeliveryNote>
     */
    private function compatibleUninvoicedNotes(DeliveryNote $primary, $alreadySelected)
    {
        $family = $primary->invoiceFamilyIds();
        if ($family === []) {
            $family = array_filter([(int) $primary->agency_id]);
        }
        $selectedIds = $alreadySelected->pluck('id')->map(fn ($id) => (int) $id)->all();

        return DeliveryNote::query()
            ->with(['agency', 'deliveries.preregistration:id,agency_id'])
            ->withCount('deliveries')
            ->whereHas('deliveries')
            ->withoutActiveInvoice()
            ->where(function ($q) use ($family) {
                $q->whereIn('agency_id', $family)
                    ->orWhereHas('deliveries.preregistration', fn ($p) => $p->whereIn('agency_id', $family));
            })
            ->whereNotIn('id', $selectedIds)
            ->orderByDesc('id')
            ->limit(80)
            ->get();
    }
}
