@extends('layouts.app')

@section('title', 'Nota de crédito '.$creditNote->folio)

@section('content')
@php
    $applications = $creditNote->applicationRows();
@endphp
<div class="cx-page">
    <x-module-banner
        section="Contabilidad"
        current="Detalle"
        title="{{ $creditNote->folio }}"
        subtitle="{{ $creditNote->agency?->name ?? 'Cliente' }} · {{ $creditNote->usageStatusLabel() }}"
        back-href="{{ route('accounting.credit-notes.index') }}"
        back-label="Volver a notas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6.75m.75-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            @if($creditNote->agency)
            <a href="{{ route('accounting.receivables.show', $creditNote->agency) }}" class="mb-btn mb-btn-secondary">Estado de cuenta</a>
            @endif
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cx-alert cx-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Monto de la nota</span>
            <span class="cx-kpi-value">${{ number_format((float) $creditNote->amount_usd, 2) }}</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Aplicado a facturas</span>
            <span class="cx-kpi-value">${{ number_format($creditNote->appliedUsd(), 2) }}</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Restante</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($creditNote->remainingUsd(), 2) }}</span>
            <span class="cx-kpi-note">Disponible para la próxima factura o cobro</span>
        </div>
    </div>

    <div class="cx-card">
        <div class="cx-section-head">
            <h2 class="cx-section-title">Datos</h2>
        </div>
        <dl class="cx-dl">
            <div><dt>Cliente</dt><dd>{{ $creditNote->agency?->name ?? '—' }} <span class="cx-muted">{{ $creditNote->agency?->code }}</span></dd></div>
            <div><dt>Motivo</dt><dd>{{ $creditNote->reason }}</dd></div>
            <div><dt>Estado</dt><dd>{{ $creditNote->usageStatusLabel() }}</dd></div>
            @if($creditNote->createdBy)
            <div><dt>Emitida por</dt><dd>{{ $creditNote->createdBy->name }} · {{ $creditNote->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</dd></div>
            @endif
            @if($creditNote->isVoid())
            <div><dt>Anulación</dt><dd class="cx-text-red">{{ $creditNote->void_reason }}{{ $creditNote->voidedBy ? ' · '.$creditNote->voidedBy->name : '' }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="cx-card">
        <div class="cx-section-head">
            <h2 class="cx-section-title">Dónde se aplicó</h2>
            <span class="cx-muted">{{ $applications->count() }} {{ $applications->count() === 1 ? 'factura' : 'facturas' }}</span>
        </div>
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Factura</th>
                        <th class="cx-num">Aplicado</th>
                        <th class="cx-num">Vigente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $row)
                    <tr>
                        <td class="cx-nowrap">{{ optional($row->at)->timezone(config('app.display_timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if($row->invoice)
                            <a href="{{ route('accounting.invoices.show', $row->invoice) }}" class="cx-folio">{{ $row->invoice->folio }}</a>
                            @else
                            —
                            @endif
                        </td>
                        <td class="cx-num">${{ number_format($row->applied_usd, 2) }}</td>
                        <td class="cx-num {{ $row->net_usd > 0 ? 'cx-text-green' : '' }}">${{ number_format($row->net_usd, 2) }}</td>
                        <td>{{ $row->is_reversed ? 'Reversado' : 'Aplicado' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="cx-empty">Esta nota aún no se ha aplicado a ninguna factura. Restante: ${{ number_format($creditNote->remainingUsd(), 2) }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(! $creditNote->isVoid() && $creditNote->appliedUsd() <= 0.005)
    <div class="cx-card">
        <div class="cx-section-head">
            <h2 class="cx-section-title">Anular nota</h2>
        </div>
        <form method="POST" action="{{ route('accounting.credit-notes.void', $creditNote) }}" class="cx-void-form" onsubmit="return confirm('¿Anular esta nota de crédito? El saldo a favor se revertirá.');">
            @csrf
            <label class="cx-label" for="void_reason">Motivo (mínimo 5 caracteres)</label>
            <textarea name="void_reason" id="void_reason" class="cx-textarea" rows="2" required minlength="5" placeholder="Ej. se emitió por error">{{ old('void_reason') }}</textarea>
            @error('void_reason')<p class="cx-error">{{ $message }}</p>@enderror
            <button type="submit" class="cx-btn-danger">Anular nota de crédito</button>
        </form>
    </div>
    @elseif(! $creditNote->isVoid())
    <p class="cx-hint">No se puede anular: ya hay ${{ number_format($creditNote->appliedUsd(), 2) }} aplicados a facturas.</p>
    @endif
</div>
<style>
.cx-page { --cx-navy:#0A2D6F; --cx-blue:#1E4FA8; --cx-green:#16794C; --cx-red:#D64545; --cx-line:#E8EEF8; padding:1.15rem 0 2.25rem; max-width:72rem; margin:0 auto; width:100%; }
.cx-kpis { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:0.75rem; margin-bottom:1.15rem; }
.cx-kpi-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; padding:0.9rem 1.05rem; display:flex; flex-direction:column; gap:0.28rem; }
.cx-kpi-card--green { border-color:#A7DFC3; background:linear-gradient(180deg,#fff 40%,#F2FBF6 140%); }
.cx-kpi-label { font-size:0.66rem; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#94a3b8; }
.cx-kpi-value { font-size:1.35rem; font-weight:800; color:#0f172a; }
.cx-kpi-note { font-size:0.7rem; color:#94a3b8; }
.cx-alert { padding:0.85rem 1.05rem; border-radius:0.7rem; margin-bottom:1rem; font-size:0.875rem; font-weight:600; }
.cx-alert-success { background:#EFFAF4; border:1px solid #A7DFC3; color:#116039; }
.cx-alert-danger { background:#FDECEC; border:1px solid #F6C9C9; color:#B03030; }
.cx-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; overflow:hidden; box-shadow:0 2px 8px rgba(15,23,42,0.04); margin-bottom:1.1rem; }
.cx-section-head { display:flex; justify-content:space-between; align-items:center; padding:0.85rem 1.1rem; border-bottom:1px solid var(--cx-line); }
.cx-section-title { margin:0; font-size:1rem; font-weight:800; color:#0f172a; }
.cx-dl { margin:0; padding:0.85rem 1.15rem 1.1rem; display:grid; gap:0.65rem; }
.cx-dl div { display:grid; grid-template-columns:9rem 1fr; gap:0.75rem; font-size:0.875rem; }
.cx-dl dt { color:#94a3b8; font-weight:700; }
.cx-dl dd { margin:0; font-weight:600; color:#0f172a; }
.cx-table-scroll { overflow-x:auto; }
.cx-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.cx-table thead th { background:#fff; color:#64748b; text-align:left; padding:0.62rem 0.8rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; border-bottom:1px solid var(--cx-line); }
.cx-table thead th.cx-num { text-align:right; }
.cx-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f4f7fb; color:#334155; }
.cx-num { text-align:right; font-variant-numeric:tabular-nums; }
.cx-nowrap { white-space:nowrap; }
.cx-folio { font-weight:800; color:var(--cx-blue); text-decoration:none; }
.cx-muted { color:#94a3b8; font-size:0.75rem; }
.cx-empty { padding:1.4rem 1rem; text-align:center; color:#94a3b8; }
.cx-text-green { color:var(--cx-green); }
.cx-text-red { color:var(--cx-red); }
.cx-void-form { padding:1rem 1.15rem 1.2rem; }
.cx-label { display:block; font-size:0.8rem; font-weight:700; color:#334155; margin-bottom:0.35rem; }
.cx-textarea { width:100%; box-sizing:border-box; padding:0.55rem 0.7rem; font-size:0.85rem; border:1px solid #D8DCE2; border-radius:0.5rem; font-family:inherit; }
.cx-error { color:#B03030; font-size:0.8rem; margin:0.35rem 0 0; }
.cx-btn-danger { margin-top:0.7rem; display:inline-flex; padding:0.55rem 1rem; font-size:0.875rem; font-weight:700; border-radius:0.55rem; border:1px solid #F6C9C9; background:#fff; color:#B03030; cursor:pointer; }
.cx-hint { font-size:0.85rem; color:#94a3b8; margin:0 0 1rem; }
@media (max-width:720px) {
    .cx-kpis { grid-template-columns:1fr; }
    .cx-dl div { grid-template-columns:1fr; gap:0.15rem; }
}
</style>
@endsection
