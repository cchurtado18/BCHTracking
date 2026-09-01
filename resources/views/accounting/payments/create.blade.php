@extends('layouts.app')

@section('title', 'Registrar cobro')

@section('content')
@php
    $invoicesJson = $openInvoices->mapWithKeys(function ($invoice) {
        return [$invoice->id => [
            'id' => $invoice->id,
            'folio' => $invoice->folio,
            'client' => $invoice->agency?->name ?? '—',
            'code' => $invoice->agency?->code ?? '',
            'type' => $invoice->agency?->typeLabel() ?? '',
            'issued' => optional($invoice->issued_at)->format('d/m/Y') ?? '—',
            'total' => (float) $invoice->total_usd,
            'paid' => (float) $invoice->amount_paid,
            'balance' => $invoice->balanceUsd(),
            'rate' => (float) $invoice->exchange_rate,
            'status' => $invoice->statusLabel(),
            'credit' => (float) ($invoice->agency?->credit_balance_usd ?? 0),
        ]];
    });
@endphp
<div class="cb-page">
    <x-module-banner
        section="Contabilidad"
        current="Registrar cobro"
        title="Registrar cobro"
        subtitle="El monto es lo que entra a caja. Si cobra de más, el excedente se suma al saldo a favor que el cliente ya tenga."
        back-href="{{ route('accounting.payments.index') }}"
        back-label="Volver a cobros"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if($errors->any())
    <div class="cb-alert cb-alert-danger">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($openInvoices->isEmpty())
    <div class="cb-card">
        <div class="cb-empty">
            <strong>No hay facturas con saldo pendiente</strong>
            <p>Emita una factura o revise CxC para registrar un cobro.</p>
            <a href="{{ route('accounting.receivables.index') }}" class="cb-btn cb-btn-primary">Ir a CxC</a>
        </div>
    </div>
    @else
    <form method="POST" action="{{ route('accounting.payments.store') }}" id="registerPaymentForm" class="cb-layout">
        @csrf

        <div class="cb-main">
            <ol class="cb-steps" aria-label="Pasos del cobro">
                <li class="is-current"><span>1</span> Factura</li>
                <li><span>2</span> Importe</li>
                <li><span>3</span> Depósito</li>
            </ol>

            <div class="cb-card">
                <div class="cb-card-head">
                    <div>
                        <h2 class="cb-card-title">Factura a cobrar</h2>
                        <p class="cb-card-note">Solo aparecen documentos emitidos o parcialmente pagados.</p>
                    </div>
                </div>
                <div class="cb-card-body">
                    <div class="cb-field">
                        <label class="cb-label" for="invoice_id">Factura</label>
                        <select name="invoice_id" id="invoice_id" class="cb-input" required>
                            <option value="">Seleccione una factura…</option>
                            @foreach($openInvoices as $invoice)
                            <option value="{{ $invoice->id }}" @selected((int) old('invoice_id', $selectedInvoiceId) === (int) $invoice->id)>
                                #{{ $invoice->id }} · {{ $invoice->folio }} · {{ $invoice->agency?->name }} · saldo ${{ number_format($invoice->balanceUsd(), 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="cb-card">
                <div class="cb-card-head">
                    <div>
                        <h2 class="cb-card-title">Importe y fecha</h2>
                        <p class="cb-card-note">El cobro se registra en USD. Si elige NIO se convierte con la tasa.</p>
                    </div>
                </div>
                <div class="cb-card-body">
                    <div class="cb-grid">
                        <div class="cb-field">
                            <label class="cb-label" for="paid_at">Fecha pago</label>
                            <input type="date" name="paid_at" id="paid_at" class="cb-input" value="{{ old('paid_at', now()->toDateString()) }}" required>
                        </div>
                        <div class="cb-field">
                            <div class="cb-label-row">
                                <label class="cb-label" for="amount">Monto recibido en caja</label>
                                <button type="button" class="cb-link-btn" id="fillBalance" hidden>Solo el saldo de la factura</button>
                            </div>
                            <div class="cb-amount">
                                <span class="cb-amount-prefix">$</span>
                                <input type="number" name="amount" id="amount" class="cb-input cb-amount-input" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0.00">
                            </div>
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="currency">Moneda</label>
                            <select name="currency" id="currency" class="cb-input">
                                <option value="USD" @selected(old('currency', 'USD') === 'USD')>USD</option>
                                <option value="NIO" @selected(old('currency') === 'NIO')>NIO</option>
                            </select>
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="exchange_rate">Tasa cambio</label>
                            <input type="number" name="exchange_rate" id="exchange_rate" class="cb-input" step="0.0001" min="0.0001" value="{{ old('exchange_rate', number_format($exchangeRate, 4, '.', '')) }}">
                        </div>
                    </div>
                    <div class="cb-math" id="applyPreview" hidden>
                        <p class="cb-math-line" id="cashMath">—</p>
                        <div class="cb-apply">
                            <div>
                                <span class="cb-apply-label">Cubre la factura</span>
                                <strong class="cb-apply-value cb-text-green" id="applyAmount">$0.00</strong>
                            </div>
                            <div>
                                <span class="cb-apply-label">Queda debiendo</span>
                                <strong class="cb-apply-value" id="applyRemain">$0.00</strong>
                            </div>
                            <div id="overpayBox" hidden>
                                <span class="cb-apply-label">De más (va a crédito)</span>
                                <strong class="cb-apply-value cb-text-green" id="overpayAmount">$0.00</strong>
                            </div>
                        </div>
                        <p class="cb-warn" id="overpayWarn" hidden></p>
                    </div>
                    <div class="cb-credit-box" id="creditBox">
                        <p class="cb-credit-title">Saldo a favor del cliente</p>
                        <p class="cb-card-note" id="creditExplain">No es un cobro nuevo. Es dinero que el cliente ya tiene (un cobro de más anterior o una nota de crédito) y que se puede descontar de esta factura sin pasar por caja.</p>
                        <div class="cb-field" id="creditField" hidden>
                            <div class="cb-label-row">
                                <label class="cb-label" for="apply_credit">Cuánto descontar ahora</label>
                                <button type="button" class="cb-link-btn" id="fillCredit">Usar todo lo disponible</button>
                            </div>
                            <input type="number" name="apply_credit" id="apply_credit" class="cb-input" step="0.01" min="0" value="{{ old('apply_credit', '0') }}" placeholder="0.00">
                            <p class="cb-card-note" id="creditHint">Disponible: $0.00</p>
                        </div>
                        <p class="cb-card-note" id="creditIdle"></p>
                    </div>
                </div>
            </div>

            <div class="cb-card">
                <div class="cb-card-head">
                    <div>
                        <h2 class="cb-card-title">Método y depósito</h2>
                        <p class="cb-card-note">Indique cómo llegó el dinero y a qué cuenta entra.</p>
                    </div>
                </div>
                <div class="cb-card-body">
                    <div class="cb-grid">
                        <div class="cb-field">
                            <label class="cb-label" for="method">Método</label>
                            <select name="method" id="method" class="cb-input">
                                <option value="">Seleccione…</option>
                                @foreach(\App\Models\AccountingPayment::METHODS as $value => $label)
                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="deposit_account">Cuenta banco / caja</label>
                            <select name="deposit_account" id="deposit_account" class="cb-input">
                                <option value="">Seleccione…</option>
                                @foreach(\App\Models\AccountingPayment::ACCOUNTS as $value => $label)
                                <option value="{{ $value }}" @selected(old('deposit_account') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="reference">Referencia</label>
                            <input type="text" name="reference" id="reference" class="cb-input" value="{{ old('reference') }}" maxlength="120" placeholder="Nº transferencia, recibo…">
                        </div>
                        <div class="cb-field">
                            <label class="cb-label" for="commission">Comisión</label>
                            <input type="number" name="commission" id="commission" class="cb-input" step="0.01" min="0" value="{{ old('commission') }}" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="cb-card-foot">
                    <a href="{{ route('accounting.payments.index') }}" class="cb-btn cb-btn-secondary">Cancelar</a>
                    <button type="submit" class="cb-btn cb-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 3v4.5H6.75V3M6.75 21h10.5A2.25 2.25 0 0 0 19.5 18.75V8.25L15.75 3H6.75A2.25 2.25 0 0 0 4.5 5.25v13.5A2.25 2.25 0 0 0 6.75 21Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 15.75h6"/></svg>
                        Guardar cobro
                    </button>
                </div>
            </div>
        </div>

        <aside class="cb-side">
            <div class="cb-summary" id="invoiceSummary">
                <div class="cb-summary-head">Resumen CxC</div>
                <div class="cb-summary-empty" id="summaryEmpty">
                    Seleccione una factura para ver cliente, saldo y lo que quedará pendiente.
                </div>
                <div class="cb-summary-body" id="summaryBody" hidden>
                    <div class="cb-summary-folio" id="sumFolio">—</div>
                    <div class="cb-summary-client" id="sumClient">—</div>
                    <div class="cb-summary-meta" id="sumMeta">—</div>
                    <span class="cb-badge" id="sumStatus">—</span>
                    <dl class="cb-dl">
                        <div><dt>Total</dt><dd id="sumTotal">$0.00</dd></div>
                        <div><dt>Cobrado</dt><dd class="cb-text-green" id="sumPaid">$0.00</dd></div>
                        <div><dt>Saldo deudor</dt><dd class="cb-text-red" id="sumBalance">$0.00</dd></div>
                        <div><dt>Saldo a favor</dt><dd class="cb-text-green" id="sumCredit">$0.00</dd></div>
                    </dl>
                    <div class="cb-outcome" id="sumOutcome">
                        <div class="cb-outcome-title">De dónde sale el dinero</div>
                        <dl class="cb-dl">
                            <div><dt>Recibido en caja</dt><dd id="outCash">$0.00</dd></div>
                            <div><dt>Cubre la factura</dt><dd class="cb-text-green" id="outApplied">$0.00</dd></div>
                            <div id="outOverpayRow" hidden><dt>De más (crédito nuevo)</dt><dd class="cb-text-green" id="outOverpay">$0.00</dd></div>
                            <div><dt>Ya tenía a favor</dt><dd class="cb-text-green" id="outHadCredit">$0.00</dd></div>
                            <div id="outCreditRow" hidden><dt>Se usa ahora</dt><dd id="outCreditUse">$0.00</dd></div>
                            <div><dt>Quedará debiendo</dt><dd id="outRemain">$0.00</dd></div>
                            <div><dt>Quedará a favor</dt><dd class="cb-text-green" id="outFavor">$0.00</dd></div>
                        </dl>
                        <p class="cb-formula" id="outFormula"></p>
                    </div>
                </div>
            </div>
        </aside>
    </form>
    @endif
</div>

<style>
.cb-page {
    --cb-navy: #0A2D6F; --cb-blue: #1E4FA8; --cb-green: #16794C; --cb-red: #D64545;
    --cb-line: #E8EEF8; --cb-soft: #F4F8FD; --cb-border: #C5D4EB;
    padding: 1.15rem 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.cb-layout { display: grid; grid-template-columns: minmax(0, 1fr) 20rem; gap: 1.1rem; align-items: start; }
.cb-main { min-width: 0; display: flex; flex-direction: column; gap: 1.1rem; }
.cb-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cb-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cb-alert ul { margin: 0; padding-left: 1.1rem; }
.cb-steps {
    list-style: none; margin: 0 0 0.15rem; padding: 0.55rem 0.2rem 0.15rem;
    display: flex; align-items: center; gap: 0;
}
.cb-steps li {
    display: inline-flex; align-items: center; gap: 0.45rem;
    font-size: 0.78rem; font-weight: 700; color: #94a3b8; white-space: nowrap;
}
.cb-steps li span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.45rem; height: 1.45rem; border-radius: 999px;
    border: 1.5px solid #D5DEEA; background: #fff; color: #64748b;
    font-size: 0.7rem; font-weight: 800;
}
.cb-steps li.is-current { color: #0f172a; }
.cb-steps li.is-current span { border-color: var(--cb-navy); color: var(--cb-navy); background: #fff; }
.cb-steps li + li { margin-left: 0.7rem; padding-left: 0.7rem; position: relative; }
.cb-steps li + li::before {
    content: ""; position: absolute; left: 0; top: 50%; width: 0.7rem; height: 1px;
    background: #D5DEEA; transform: translate(-100%, -50%);
}
.cb-card { background: #fff; border: 1px solid var(--cb-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; }
.cb-card-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;
    padding: 0.95rem 1.15rem 0.85rem; background: #fff; border-bottom: 1px solid var(--cb-line);
}
.cb-card-title { margin: 0; font-size: 0.98rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.cb-card-note { margin: 0.22rem 0 0; font-size: 0.78rem; color: #94a3b8; }
.cb-card-body { padding: 1.15rem 1.2rem 1.2rem; }
.cb-card-foot {
    display: flex; justify-content: flex-end; gap: 0.65rem; flex-wrap: wrap;
    padding: 0.9rem 1.15rem; border-top: 1px solid var(--cb-line); background: #FAFCFF;
}
.cb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.95rem 1rem; }
.cb-field { display: flex; flex-direction: column; gap: 0.32rem; min-width: 0; }
.cb-label { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
.cb-label-row { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.cb-link-btn { appearance: none; border: 0; background: none; padding: 0; font-size: 0.75rem; font-weight: 700; color: var(--cb-blue); cursor: pointer; }
.cb-link-btn:hover { color: var(--cb-navy); text-decoration: underline; }
.cb-input {
    width: 100%; box-sizing: border-box; padding: 0.58rem 0.75rem; font-size: 0.9rem;
    color: #0f172a; background: #fff; border: 1px solid #D8DCE2; border-radius: 0.55rem;
}
.cb-input:focus { outline: none; border-color: var(--cb-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cb-amount { position: relative; }
.cb-amount-prefix {
    position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
    font-weight: 800; color: var(--cb-green); pointer-events: none;
}
.cb-amount-input { padding-left: 1.55rem; font-weight: 700; }
.cb-apply {
    display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.65rem;
    padding: 0.8rem 0.95rem; border-radius: 0.7rem; background: var(--cb-soft); border: 1px solid var(--cb-line);
}
.cb-apply:has(#overpayBox:not([hidden])) { grid-template-columns: 1fr 1fr 1fr; }
.cb-apply-label { display: block; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
.cb-apply-value { font-size: 1.15rem; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
.cb-credit-box {
    margin-top: 1rem; padding: 0.85rem 0.95rem; border-radius: 0.7rem;
    background: #FAFCFF; border: 1px dashed var(--cb-border);
}
.cb-credit-title { margin: 0 0 0.3rem; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; color: #94a3b8; }
.cb-credit-box .cb-field { margin-top: 0.7rem; }
.cb-math { margin-top: 1rem; }
.cb-math-line { margin: 0; font-size: 0.82rem; color: #334155; font-weight: 600; }
.cb-warn {
    display: none; margin: 0.65rem 0 0; padding: 0.7rem 0.85rem; border-radius: 0.55rem;
    background: #FFF6E8; border: 1px solid #F3D19C; color: #9A6700; font-size: 0.8rem; font-weight: 600; line-height: 1.4;
}
.cb-warn:not([hidden]) { display: block; }
.cb-formula { margin: 0.7rem 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.45; font-weight: 600; }
.cb-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700;
    border-radius: 0.6rem; border: 1px solid transparent; text-decoration: none; cursor: pointer;
}
.cb-btn-primary { background: var(--cb-navy); color: #fff; border-color: var(--cb-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.22); }
.cb-btn-primary:hover { background: var(--cb-blue); border-color: var(--cb-blue); color: #fff; }
.cb-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cb-btn-secondary:hover { background: var(--cb-soft); color: var(--cb-navy); }
.cb-side { position: sticky; top: 1rem; }
.cb-summary { background: #fff; border: 1px solid var(--cb-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; }
.cb-summary-head {
    padding: 0.85rem 1.05rem 0.7rem; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase;
    color: #94a3b8; background: #fff; border-bottom: 1px solid var(--cb-line);
}
.cb-summary-empty { padding: 1.2rem 1rem 1.3rem; color: #94a3b8; font-size: 0.85rem; line-height: 1.45; }
.cb-summary-body { padding: 1rem 1.05rem 1.15rem; }
.cb-summary-folio { font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.cb-summary-client { margin-top: 0.2rem; font-weight: 700; color: #334155; }
.cb-summary-meta { margin: 0.2rem 0 0.65rem; font-size: 0.75rem; color: #94a3b8; }
.cb-badge { display: inline-flex; padding: 0.18rem 0.5rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; background: #EAF1FC; color: var(--cb-blue); border: 1px solid #C9DAF3; }
.cb-dl { margin: 0.9rem 0 0; display: grid; gap: 0.45rem; }
.cb-dl div { display: flex; justify-content: space-between; gap: 0.75rem; font-size: 0.85rem; }
.cb-dl dt { color: #94a3b8; font-weight: 700; }
.cb-dl dd { margin: 0; font-weight: 800; font-variant-numeric: tabular-nums; }
.cb-outcome { margin-top: 0.95rem; padding-top: 0.85rem; border-top: 1px dashed var(--cb-line); }
.cb-outcome-title { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; }
.cb-outcome .cb-dl { margin-top: 0.55rem; }
.cb-text-green { color: var(--cb-green); }
.cb-text-red { color: var(--cb-red); }
.cb-empty { padding: 2rem 1.25rem; text-align: center; color: #64748b; }
.cb-empty strong { display: block; margin-bottom: 0.35rem; color: #0f172a; font-size: 1.05rem; }
.cb-empty p { margin: 0 0 1rem; }
@media (max-width: 960px) {
    .cb-layout { grid-template-columns: 1fr; }
    .cb-side { position: static; }
}
@media (max-width: 640px) {
    .cb-grid, .cb-apply { grid-template-columns: 1fr; }
    .cb-card-foot { flex-direction: column-reverse; align-items: stretch; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var invoices = @json($invoicesJson);
    var invoiceSelect = document.getElementById('invoice_id');
    var amountInput = document.getElementById('amount');
    var rateInput = document.getElementById('exchange_rate');
    var currencySelect = document.getElementById('currency');
    var methodSelect = document.getElementById('method');
    var accountSelect = document.getElementById('deposit_account');
    var fillBtn = document.getElementById('fillBalance');
    var creditInput = document.getElementById('apply_credit');
    var fillCredit = document.getElementById('fillCredit');
    var creditField = document.getElementById('creditField');
    if (!invoiceSelect) return;

    function money(n) {
        return '$' + Number(n || 0).toFixed(2);
    }

    function currentUsd() {
        var raw = parseFloat(amountInput.value || '0') || 0;
        var rate = parseFloat(rateInput && rateInput.value ? rateInput.value : '0') || 0;
        if (currencySelect && currencySelect.value === 'NIO' && rate > 0) {
            return raw / rate;
        }
        return raw;
    }

    function selected() {
        return invoices[invoiceSelect.value] || null;
    }

    function refresh() {
        var data = selected();
        var empty = document.getElementById('summaryEmpty');
        var body = document.getElementById('summaryBody');
        var preview = document.getElementById('applyPreview');
        var overpayBox = document.getElementById('overpayBox');
        if (!data) {
            empty.hidden = false;
            body.hidden = true;
            preview.hidden = true;
            fillBtn.hidden = true;
            if (creditField) creditField.hidden = true;
            return;
        }
        empty.hidden = true;
        body.hidden = false;
        fillBtn.hidden = false;
        document.getElementById('sumFolio').textContent = data.folio + ' · #' + data.id;
        document.getElementById('sumClient').textContent = data.client;
        document.getElementById('sumMeta').textContent = (data.code ? data.code + ' · ' : '') + (data.type ? data.type + ' · ' : '') + 'Emisión ' + data.issued;
        document.getElementById('sumStatus').textContent = data.status;
        document.getElementById('sumTotal').textContent = money(data.total);
        document.getElementById('sumPaid').textContent = money(data.paid);
        document.getElementById('sumBalance').textContent = money(data.balance);
        document.getElementById('sumCredit').textContent = money(data.credit || 0);

        var cash = currentUsd();
        var cashToInvoice = Math.min(cash, data.balance);
        var overpay = Math.max(0, cash - data.balance);
        var remainAfterCash = Math.max(0, data.balance - cashToInvoice);
        var creditWanted = creditInput ? (parseFloat(creditInput.value || '0') || 0) : 0;
        var creditUse = Math.min(creditWanted, data.credit || 0, remainAfterCash);
        var remain = Math.max(0, remainAfterCash - creditUse);
        var favor = (data.credit || 0) - creditUse + overpay;

        preview.hidden = false;
        document.getElementById('applyAmount').textContent = money(cashToInvoice + creditUse);
        var remainEl = document.getElementById('applyRemain');
        remainEl.textContent = money(remain);
        remainEl.className = 'cb-apply-value ' + (remain > 0.004 ? 'cb-text-red' : 'cb-text-green');
        if (overpayBox) {
            overpayBox.hidden = overpay <= 0.004;
            document.getElementById('overpayAmount').textContent = money(overpay);
        }
        var cashMath = document.getElementById('cashMath');
        if (cashMath) {
            cashMath.textContent = money(cash) + ' recibido − ' + money(cashToInvoice) + ' a la factura = ' + money(overpay) + ' de más';
        }
        var overpayWarn = document.getElementById('overpayWarn');
        if (overpayWarn) {
            if (overpay > 0.004) {
                overpayWarn.hidden = false;
                overpayWarn.textContent = 'El cobro es mayor que el saldo de la factura. Los ' + money(overpay) + ' de más no se cobran otra vez: quedan como saldo a favor, sumados a los ' + money(data.credit || 0) + ' que el cliente ya tiene.';
            } else {
                overpayWarn.hidden = true;
                overpayWarn.textContent = '';
            }
        }
        if (creditField) {
            var hasCredit = (data.credit || 0) > 0.004;
            var canApply = remainAfterCash > 0.004;
            var idle = document.getElementById('creditIdle');
            creditField.hidden = !hasCredit || !canApply;
            if (idle) {
                if (!hasCredit) {
                    idle.hidden = false;
                    idle.textContent = 'Este cliente no tiene saldo a favor. El recuadro de descontar solo se activa cuando ya le quedó dinero de un cobro de más o de una nota de crédito.';
                } else if (!canApply) {
                    idle.hidden = false;
                    idle.textContent = 'Tiene ' + money(data.credit || 0) + ' a favor, pero no se usa porque el cobro en caja ya cubre los ' + money(data.balance) + ' de la factura. Si quiere descontar crédito, baje el monto recibido (ejemplo: cobre $30 y descuente $10).';
                } else {
                    idle.hidden = true;
                    idle.textContent = '';
                }
            }
            var hint = document.getElementById('creditHint');
            if (hint && hasCredit && canApply) {
                hint.textContent = 'Puede descontar hasta ' + money(Math.min(data.credit || 0, remainAfterCash)) + '. El resto de la factura se cobra en caja.';
            }
        }
        var outApplied = document.getElementById('outApplied');
        if (outApplied) {
            document.getElementById('outCash').textContent = money(cash);
            outApplied.textContent = money(cashToInvoice + creditUse);
            document.getElementById('outOverpay').textContent = money(overpay);
            document.getElementById('outOverpayRow').hidden = overpay <= 0.004;
            document.getElementById('outHadCredit').textContent = money(data.credit || 0);
            document.getElementById('outCreditUse').textContent = money(creditUse);
            document.getElementById('outCreditRow').hidden = creditUse <= 0.004;
            var outRemain = document.getElementById('outRemain');
            outRemain.textContent = money(remain);
            outRemain.className = remain > 0.004 ? 'cb-text-red' : 'cb-text-green';
            document.getElementById('outFavor').textContent = money(favor);
            var formula = document.getElementById('outFormula');
            if (formula) {
                formula.textContent = money(data.credit || 0) + ' que ya tenía'
                    + (creditUse > 0.004 ? ' − ' + money(creditUse) + ' que se usa' : '')
                    + (overpay > 0.004 ? ' + ' + money(overpay) + ' de más de este cobro' : '')
                    + ' = ' + money(favor) + ' a favor';
            }
        }
    }

    invoiceSelect.addEventListener('change', function () {
        var data = selected();
        if (!data) { refresh(); return; }
        if (data.rate > 0 && rateInput) rateInput.value = Number(data.rate).toFixed(4);
        if (creditInput && (data.credit || 0) > 0.004) {
            creditInput.value = Math.min(data.credit || 0, data.balance).toFixed(2);
        } else if (creditInput && !creditInput.value) {
            creditInput.value = '0';
        }
        if (!amountInput.value) {
            var creditNow = creditInput ? (parseFloat(creditInput.value || '0') || 0) : 0;
            amountInput.value = Math.max(0, data.balance - Math.min(creditNow, data.credit || 0, data.balance)).toFixed(2);
        }
        refresh();
    });

    fillBtn.addEventListener('click', function () {
        var data = selected();
        if (!data) return;
        var creditNow = creditInput ? (parseFloat(creditInput.value || '0') || 0) : 0;
        var needed = Math.max(0, data.balance - Math.min(creditNow, data.credit || 0, data.balance));
        amountInput.value = needed.toFixed(2);
        if (currencySelect) currencySelect.value = 'USD';
        refresh();
    });

    if (fillCredit) {
        fillCredit.addEventListener('click', function () {
            var data = selected();
            if (!data || !creditInput) return;
            var cash = currentUsd();
            var remain = Math.max(0, data.balance - Math.min(cash, data.balance));
            creditInput.value = Math.min(data.credit || 0, remain).toFixed(2);
            refresh();
        });
    }

    ['input', 'change'].forEach(function (evt) {
        amountInput.addEventListener(evt, refresh);
        if (rateInput) rateInput.addEventListener(evt, refresh);
        if (currencySelect) currencySelect.addEventListener(evt, refresh);
        if (creditInput) creditInput.addEventListener(evt, refresh);
    });

    if (methodSelect && accountSelect) {
        methodSelect.addEventListener('change', function () {
            if (accountSelect.value) return;
            var defaults = { cash: 'cash_general', other: 'cash_general', card: 'bank_lafise', transfer: 'bank_bac', check: 'bank_bac' };
            accountSelect.value = defaults[methodSelect.value] || '';
        });
    }

    var initial = selected();
    if (initial && !amountInput.value) {
        if (initial.rate > 0 && rateInput) rateInput.value = Number(initial.rate).toFixed(4);
        if (creditInput && (initial.credit || 0) > 0.004) {
            creditInput.value = Math.min(initial.credit || 0, initial.balance).toFixed(2);
        }
        var creditNow = creditInput ? (parseFloat(creditInput.value || '0') || 0) : 0;
        amountInput.value = Math.max(0, initial.balance - Math.min(creditNow, initial.credit || 0, initial.balance)).toFixed(2);
    }
    refresh();

    var form = document.getElementById('registerPaymentForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var data = selected();
            if (!data) return;
            var cash = currentUsd();
            var overpay = Math.max(0, cash - data.balance);
            if (overpay <= 0.004) return;
            var favor = (data.credit || 0) + overpay;
            var ok = window.confirm(
                'El cobro (' + money(cash) + ') es mayor que el saldo de la factura (' + money(data.balance) + ').\n\n'
                + money(overpay) + ' quedarán de más como saldo a favor.\n'
                + 'El cliente ya tiene ' + money(data.credit || 0) + ' a favor, así que el total a favor sería ' + money(favor) + '.\n\n'
                + '¿Registrar este cobro?'
            );
            if (!ok) e.preventDefault();
        });
    }
});
</script>
@endsection
