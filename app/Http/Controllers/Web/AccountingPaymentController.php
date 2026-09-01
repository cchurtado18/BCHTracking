<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingInvoice;
use App\Models\AccountingPayment;
use App\Models\AccountingPaymentAllocation;
use App\Models\AccountingSetting;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Services\Accounting\ClientCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountingPayment::query()
            ->with(['agency:id,code,name,account_type,is_main', 'allocations.invoice:id,folio'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }

        if ($request->filled('client')) {
            $name = trim((string) $request->client);
            $query->whereHas('agency', function ($q) use ($name) {
                $q->where(function ($inner) use ($name) {
                    $inner->where('name', 'like', "%{$name}%")
                        ->orWhere('code', 'like', "%{$name}%");
                });
            });
        }

        $status = (string) $request->input('status', 'all');
        if ($status === '') {
            $status = 'all';
        }
        if (in_array($status, ['active', 'void'], true)) {
            $query->where('status', $status);
        }

        $kpis = (object) [
            'collected' => round((float) AccountingPayment::query()->where('status', 'active')->sum('amount_usd'), 2),
            'count' => AccountingPayment::query()->where('status', 'active')->count(),
            'month' => round((float) AccountingPayment::query()
                ->where('status', 'active')
                ->whereYear('paid_at', now()->year)
                ->whereMonth('paid_at', now()->month)
                ->sum('amount_usd'), 2),
            'voided' => round((float) AccountingPayment::query()->where('status', 'void')->sum('amount_usd'), 2),
            'voided_count' => AccountingPayment::query()->where('status', 'void')->count(),
        ];

        $payments = $query->paginate(25)->withQueryString();

        return view('accounting.payments.index', compact('payments', 'kpis', 'status'));
    }

    public function create(Request $request)
    {
        $openInvoices = AccountingInvoice::query()
            ->with('agency:id,code,name,account_type,is_main,credit_balance_usd')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();

        $selectedInvoiceId = $request->integer('invoice_id') ?: null;
        if (! $selectedInvoiceId && $request->filled('agency_id')) {
            $selectedInvoiceId = $openInvoices->firstWhere('agency_id', (int) $request->agency_id)?->id;
        }

        $exchangeRate = (float) AccountingSetting::current()->exchange_rate;

        return view('accounting.payments.create', compact('openInvoices', 'selectedInvoiceId', 'exchangeRate'));
    }

    public function show(AccountingPayment $payment)
    {
        $payment->load(['agency:id,code,name', 'createdBy:id,name', 'voidedBy:id,name', 'allocations.invoice:id,folio,total_usd,amount_paid,status']);

        return view('accounting.payments.show', compact('payment'));
    }

    public function store(Request $request, ClientCreditService $credits)
    {
        $this->mergeSingleInvoiceAllocation($request);

        if (strtoupper((string) $request->input('currency', 'USD')) === 'NIO') {
            $request->validate([
                'exchange_rate' => 'required|numeric|min:0.0001',
            ], [
                'exchange_rate.required' => 'Indique el tipo de cambio para cobros en córdobas.',
                'exchange_rate.min' => 'El tipo de cambio debe ser mayor que cero.',
            ]);
        }

        $data = $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'paid_at' => 'required|date',
            'method' => 'nullable|in:'.implode(',', array_keys(AccountingPayment::METHODS)),
            'deposit_account' => 'nullable|in:'.implode(',', array_keys(AccountingPayment::ACCOUNTS)),
            'reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
            'apply_credit' => 'nullable|numeric|min:0',
        ], [
            'agency_id.required' => 'Seleccione la factura.',
        ]);

        $cashByInvoice = collect($data['allocations'] ?? [])
            ->mapWithKeys(fn ($v, $k) => [(int) $k => round((float) $v, 2)])
            ->filter(fn ($v) => $v > 0);

        $applyCreditRequested = round((float) ($data['apply_credit'] ?? 0), 2);

        if ($cashByInvoice->isEmpty() && $applyCreditRequested <= 0) {
            return back()->withInput()->withErrors(['allocations' => 'Indique un monto de cobro o aplique saldo a favor.']);
        }

        if ($cashByInvoice->isNotEmpty() && empty($data['method'])) {
            return back()->withInput()->withErrors(['method' => 'Seleccione el método de cobro.']);
        }

        $invoiceIds = $cashByInvoice->keys();
        if ($request->filled('invoice_id')) {
            $invoiceIds = $invoiceIds->push((int) $request->invoice_id)->unique();
        }

        if ($invoiceIds->isEmpty()) {
            return back()->withInput()->withErrors(['allocations' => 'No hay una factura válida para este cobro.']);
        }

        $agency = Agency::findOrFail((int) $data['agency_id']);

        try {
            $result = DB::transaction(function () use ($data, $cashByInvoice, $invoiceIds, $applyCreditRequested, $agency, $request, $credits) {
                $lockedAgency = Agency::query()->whereKey($agency->id)->lockForUpdate()->firstOrFail();
                $invoices = AccountingInvoice::query()
                    ->whereIn('id', $invoiceIds->all())
                    ->where('agency_id', $lockedAgency->id)
                    ->whereIn('status', ['issued', 'partially_paid'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($invoices->isEmpty()) {
                    throw new InvalidArgumentException('No hay una factura válida para este cobro.');
                }

                $splits = [];
                $totalCash = 0.0;
                $totalOverpay = 0.0;

                foreach ($invoices as $invoice) {
                    $cash = (float) ($cashByInvoice[$invoice->id] ?? 0);
                    $balance = $invoice->balanceUsd();
                    $appliedCash = min($cash, $balance);
                    $overpay = round($cash - $appliedCash, 2);
                    $splits[] = compact('invoice', 'cash', 'appliedCash', 'overpay', 'balance');
                    $totalCash = round($totalCash + $cash, 2);
                    $totalOverpay = round($totalOverpay + $overpay, 2);
                }

                $payment = null;

                if ($totalCash > 0) {
                    $payment = AccountingPayment::create([
                        'agency_id' => (int) $data['agency_id'],
                        'amount_usd' => $totalCash,
                        'paid_at' => $data['paid_at'],
                        'method' => $data['method'] ?? 'other',
                        'deposit_account' => $data['deposit_account'] ?? AccountingPayment::defaultAccountForMethod($data['method'] ?? 'other'),
                        'reference' => $data['reference'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'status' => 'active',
                        'created_by' => $request->user()->id,
                    ]);

                    foreach ($splits as $split) {
                        if ($split['appliedCash'] > 0) {
                            AccountingPaymentAllocation::create([
                                'payment_id' => $payment->id,
                                'accounting_invoice_id' => $split['invoice']->id,
                                'amount_usd' => $split['appliedCash'],
                            ]);
                            $split['invoice']->refreshPaymentStatus();
                        }
                    }

                    if ($totalOverpay > 0) {
                        $credits->add($lockedAgency, $totalOverpay, \App\Models\AccountingCreditMovement::TYPE_OVERPAYMENT, [
                            'payment_id' => $payment->id,
                            'notes' => 'Excedente de cobro',
                            'created_by' => $request->user()->id,
                        ]);
                    }
                }

                $creditLeft = min($applyCreditRequested, $credits->balance($lockedAgency->fresh() ?? $lockedAgency));
                $creditApplied = 0.0;
                if ($creditLeft > 0) {
                    foreach ($splits as $split) {
                        if ($creditLeft <= 0.005) {
                            break;
                        }
                        $invoice = $split['invoice']->fresh();
                        $remaining = $invoice ? $invoice->balanceUsd() : 0;
                        if ($remaining <= 0.005) {
                            continue;
                        }
                        $take = min($creditLeft, $remaining);
                        $credits->applyToInvoice($lockedAgency->fresh() ?? $lockedAgency, $invoice, $take, $request->user()->id);
                        $creditLeft = round($creditLeft - $take, 2);
                        $creditApplied = round($creditApplied + $take, 2);
                    }
                }

                return [
                    'payment' => $payment,
                    'primary' => $splits[0]['invoice'],
                    'totalCash' => $totalCash,
                    'totalOverpay' => $totalOverpay,
                    'creditToApply' => $creditApplied,
                ];
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['apply_credit' => $e->getMessage()]);
        }

        $payment = $result['payment'];
        $primary = $result['primary'];
        $totalCash = $result['totalCash'];
        $totalOverpay = $result['totalOverpay'];
        $creditToApply = $result['creditToApply'];

        $summaryAmount = $totalCash > 0 ? $totalCash : $creditToApply;
        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_payment',
            'auditable_id' => $payment?->id ?? $primary->id,
            'action' => 'payment_registered',
            'summary' => 'Registró cobro de $'.number_format($summaryAmount, 2).' a '.$agency->name,
            'old_values' => null,
            'new_values' => [
                'amount_usd' => $totalCash,
                'overpay' => $totalOverpay,
                'apply_credit' => $creditToApply,
            ],
            'ip_address' => $request->ip(),
        ]);

        $msg = 'Cobro registrado.';
        if ($totalCash > 0) {
            $msg = 'Cobro de $'.number_format($totalCash, 2).' registrado.';
        }
        if ($totalOverpay > 0) {
            $msg .= ' Excedente de $'.number_format($totalOverpay, 2).' quedó como saldo a favor.';
        }
        if ($creditToApply > 0) {
            $msg .= ' Se aplicaron $'.number_format($creditToApply, 2).' de crédito.';
        }

        return redirect()->route('accounting.payments.index')->with('success', $msg);
    }

    public function void(Request $request, AccountingPayment $payment, ClientCreditService $credits)
    {
        $data = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ], [
            'void_reason.required' => 'Indique el motivo de la cancelación.',
            'void_reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if ($payment->isVoid()) {
            return back()->with('error', 'Este cobro ya está cancelado.');
        }

        try {
            DB::transaction(function () use ($payment, $data, $request, $credits) {
                $credits->reverseOverpaymentForPayment($payment);

                $payment->update([
                    'status' => 'void',
                    'void_reason' => trim($data['void_reason']),
                    'voided_at' => now(),
                    'voided_by' => $request->user()->id,
                ]);

                $payment->load('allocations.invoice');
                foreach ($payment->allocations as $allocation) {
                    $allocation->invoice?->refreshPaymentStatus();
                }
            });
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => 'accounting_payment',
            'auditable_id' => $payment->id,
            'action' => 'payment_voided',
            'summary' => 'Canceló cobro de $'.number_format((float) $payment->amount_usd, 2).' ('.$payment->agency?->name.')',
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'void', 'void_reason' => $payment->void_reason],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('accounting.payments.index')
            ->with('success', 'Cobro cancelado. Los saldos de las facturas fueron recalculados.');
    }

    /**
     * El formulario de alta envía factura + monto; se traduce a la asignación interna.
     */
    private function mergeSingleInvoiceAllocation(Request $request): void
    {
        if ($request->filled('allocations') || ! $request->filled('invoice_id')) {
            return;
        }

        $invoice = AccountingInvoice::query()->find($request->integer('invoice_id'));
        if (! $invoice) {
            return;
        }

        $amount = round((float) $request->input('amount', 0), 2);
        $currency = strtoupper((string) $request->input('currency', 'USD'));
        $rate = (float) $request->input('exchange_rate', 0);
        if ($currency === 'NIO') {
            if ($rate < 0.0001) {
                return;
            }
            $amount = round($amount / $rate, 2);
        }

        $notes = trim((string) $request->input('notes', ''));
        if ($request->filled('commission')) {
            $commission = number_format((float) $request->input('commission'), 2, '.', '');
            $line = 'Comisión: '.$commission;
            $notes = $notes !== '' ? $notes.' | '.$line : $line;
        }

        $request->merge([
            'agency_id' => $invoice->agency_id,
            'allocations' => [$invoice->id => $amount],
            'notes' => $notes !== '' ? $notes : null,
        ]);
    }
}
