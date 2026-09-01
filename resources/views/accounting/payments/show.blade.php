@extends('layouts.app')

@section('title', 'Detalle de cobro')

@section('content')
<div class="cb-page">
    <x-module-banner
        section="Contabilidad"
        current="Detalle de cobro"
        title="Cobro #{{ $payment->id }}"
        subtitle="{{ $payment->agency?->name ?? 'Cliente' }} · {{ $payment->paid_at->format('d/m/Y') }}{{ $payment->isVoid() ? ' · Cancelado' : '' }}. Asignación del pago a facturas y caja."
        back-href="{{ route('accounting.payments.index') }}"
        back-label="Volver a cobros"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @if($payment->agency)
            <a href="{{ route('accounting.receivables.show', $payment->agency) }}" class="mb-btn mb-btn-primary">Estado de cuenta</a>
            @endif
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cb-alert cb-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cb-alert cb-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cb-stats">
        <div class="cb-stat">
            <span class="cb-stat-label">Monto</span>
            <span class="cb-stat-value cb-amount">${{ number_format((float) $payment->amount_usd, 2) }}</span>
        </div>
        <div class="cb-stat">
            <span class="cb-stat-label">Moneda</span>
            <span class="cb-stat-value">USD</span>
        </div>
        <div class="cb-stat">
            <span class="cb-stat-label">Método</span>
            <span class="cb-stat-value">{{ $payment->methodLabel() }}</span>
        </div>
        <div class="cb-stat">
            <span class="cb-stat-label">Cuenta</span>
            <span class="cb-stat-value">{{ $payment->accountLabel() }}</span>
        </div>
    </div>

    <div class="cb-card">
        <div class="cb-section-head">
            <h2 class="cb-section-title">Aplicado a facturas</h2>
            <span class="cb-muted">{{ $payment->allocations->count() }} {{ $payment->allocations->count() === 1 ? 'factura' : 'facturas' }}</span>
        </div>
        <div class="cb-table-scroll">
            <table class="cb-table">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th class="cb-num">Aplicado</th>
                        <th>Estado factura</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payment->allocations as $allocation)
                    <tr>
                        <td>
                            @if($allocation->invoice)
                            <a href="{{ route('accounting.invoices.show', $allocation->invoice) }}" class="cb-folio" title="{{ $allocation->invoice->folio }}">#{{ $allocation->invoice->id }}</a>
                            <span class="cb-muted">{{ $allocation->invoice->folio }}</span>
                            @else
                            —
                            @endif
                        </td>
                        <td class="cb-num cb-amount">${{ number_format((float) $allocation->amount_usd, 2) }}</td>
                        <td>{{ $allocation->invoice?->statusLabel() ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="cb-empty">Sin facturas asociadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($payment->reference || $payment->notes || $payment->createdBy)
    <div class="cb-meta">
        @if($payment->reference)<p><strong>Referencia:</strong> {{ $payment->reference }}</p>@endif
        @if($payment->notes)<p><strong>Notas:</strong> {{ $payment->notes }}</p>@endif
        @if($payment->createdBy)<p class="cb-muted">Registrado por {{ $payment->createdBy->name }}</p>@endif
        @if($payment->isVoid())
        <p class="cb-void-note">Cancelado{{ $payment->voidedBy ? ' por '.$payment->voidedBy->name : '' }}{{ $payment->voided_at ? ' el '.$payment->voided_at->format('d/m/Y H:i') : '' }}. Motivo: {{ $payment->void_reason }}</p>
        @endif
    </div>
    @endif

    @if(! $payment->isVoid())
    <div class="cb-card cb-void-card">
        <div class="cb-section-head">
            <h2 class="cb-section-title">Cancelar cobro</h2>
        </div>
        <form action="{{ route('accounting.payments.void', $payment) }}" method="POST" class="cb-void-form" onsubmit="return confirm('¿Cancelar este cobro? Los saldos de las facturas se recalcularán.');">
            @csrf
            <label class="cb-label" for="void_reason">Motivo (mínimo 5 caracteres)</label>
            <textarea name="void_reason" id="void_reason" class="cb-textarea" rows="2" required minlength="5" placeholder="Ej. cobro duplicado">{{ old('void_reason') }}</textarea>
            @error('void_reason')<p class="cb-error">{{ $message }}</p>@enderror
            <button type="submit" class="cb-btn cb-btn-danger">Cancelar cobro</button>
        </form>
    </div>
    @endif
</div>

<style>
.cb-page {
    --cb-navy: #0A2D6F; --cb-blue: #1E4FA8; --cb-green: #16794C; --cb-line: #E8EEF8; --cb-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 72rem; margin: 0 auto; width: 100%;
}
.cb-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
.cb-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; }
.cb-title { margin: 0; font-size: 1.7rem; font-weight: 800; color: #0f172a; letter-spacing: -0.03em; }
.cb-subtitle { margin: 0.35rem 0 0; font-size: 0.9rem; color: var(--cb-muted); }
.cb-void-flag { color: #B03030; font-weight: 800; }
.cb-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.58rem 1.05rem;
    font-size: 0.875rem; font-weight: 700; border-radius: 0.55rem; border: 1px solid transparent; text-decoration: none; cursor: pointer;
}
.cb-btn-primary { background: var(--cb-navy); color: #fff; border-color: var(--cb-navy); }
.cb-btn-primary:hover { background: var(--cb-blue); color: #fff; }
.cb-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cb-btn-secondary:hover { background: #F4F8FD; color: var(--cb-navy); }
.cb-btn-danger { background: #fff; color: #B03030; border-color: #F6C9C9; margin-top: 0.7rem; }
.cb-btn-danger:hover { background: #FDECEC; }
.cb-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cb-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.cb-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cb-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; margin-bottom: 1.1rem; }
.cb-stat { background: #fff; border: 1px solid var(--cb-line); border-radius: 0.75rem; padding: 0.85rem 1rem; }
.cb-stat-label { display: block; font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; margin-bottom: 0.25rem; }
.cb-stat-value { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
.cb-amount { color: var(--cb-green); }
.cb-card { background: #fff; border: 1px solid var(--cb-line); border-radius: 0.85rem; overflow: hidden; margin-bottom: 1.1rem; }
.cb-section-head { display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.1rem; border-bottom: 1px solid var(--cb-line); }
.cb-section-title { margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
.cb-table-scroll { overflow-x: auto; }
.cb-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.cb-table thead th {
    background: var(--cb-navy); color: #fff; text-align: left; padding: 0.62rem 1rem;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;
}
.cb-table thead th.cb-num { text-align: right; }
.cb-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
.cb-num { text-align: right; font-variant-numeric: tabular-nums; }
.cb-folio { font-weight: 800; color: var(--cb-blue); text-decoration: none; margin-right: 0.4rem; }
.cb-folio:hover { color: var(--cb-navy); text-decoration: underline; }
.cb-muted { color: #94a3b8; font-size: 0.8rem; }
.cb-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; }
.cb-meta { margin-bottom: 1.1rem; font-size: 0.875rem; color: #334155; }
.cb-meta p { margin: 0 0 0.35rem; }
.cb-void-note { color: #B03030; font-weight: 600; }
.cb-void-card { padding-bottom: 0.4rem; }
.cb-void-form { padding: 1rem 1.1rem 1.15rem; }
.cb-label { display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem; }
.cb-textarea {
    width: 100%; box-sizing: border-box; padding: 0.55rem 0.7rem; font-size: 0.85rem;
    border: 1px solid #D8DCE2; border-radius: 0.5rem; font-family: inherit;
}
.cb-error { color: #B03030; font-size: 0.8rem; margin: 0.35rem 0 0; }
@media (max-width: 800px) { .cb-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endsection
