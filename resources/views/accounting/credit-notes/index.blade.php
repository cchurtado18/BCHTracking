@extends('layouts.app')

@section('title', 'Notas de crédito')

@section('content')
<div class="cx-page">
    <x-module-banner section="Contabilidad" current="Notas de crédito" title="Notas de crédito" subtitle="Acreditan saldo a favor del cliente para aplicarlo en la próxima factura o cobro.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6.75m.75-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.credit-notes.create') }}" class="mb-btn mb-btn-primary">Nueva nota de crédito</a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cx-alert cx-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cx-kpis">
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Saldo a favor total</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($creditTotal, 2) }}</span>
            <span class="cx-kpi-note">Disponible en clientes</span>
        </div>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th class="cx-num">Monto</th>
                        <th class="cx-num">Aplicado</th>
                        <th class="cx-num">Restante</th>
                        <th>Motivo</th>
                        <th>Estado</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $note)
                    <tr>
                        <td><a href="{{ route('accounting.credit-notes.show', $note) }}" class="cx-folio">{{ $note->folio }}</a></td>
                        <td>
                            <div class="cx-client">{{ $note->agency?->name ?? '—' }}</div>
                            <div class="cx-muted">{{ $note->agency?->code }}</div>
                        </td>
                        <td class="cx-num">${{ number_format((float) $note->amount_usd, 2) }}</td>
                        <td class="cx-num">${{ number_format($note->appliedUsd(), 2) }}</td>
                        <td class="cx-num {{ $note->remainingUsd() > 0 ? 'cx-text-green' : '' }}">${{ number_format($note->remainingUsd(), 2) }}</td>
                        <td>{{ $note->reason }}</td>
                        <td>
                            <span class="cx-status {{ $note->isVoid() ? 'cx-status--overdue' : ($note->usageStatus() === 'applied' ? 'cx-status--paid' : ($note->usageStatus() === 'partial' ? 'cx-status--partial' : 'cx-status--paid')) }}">{{ $note->usageStatusLabel() }}</span>
                        </td>
                        <td class="cx-actions">
                            <a href="{{ route('accounting.credit-notes.show', $note) }}" class="cx-action-btn">Ver detalle</a>
                            @if(! $note->isVoid() && $note->appliedUsd() <= 0.005)
                            <form method="POST" action="{{ route('accounting.credit-notes.void', $note) }}" onsubmit="var r=prompt('Motivo de anulación (mín. 5 caracteres)'); if(!r||r.trim().length<5){ if(r!==null) alert('Indique un motivo de al menos 5 caracteres.'); return false;} this.void_reason.value=r.trim();">
                                @csrf
                                <input type="hidden" name="void_reason" value="">
                                <button type="submit" class="cx-action-btn">Anular</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="cx-empty">Aún no hay notas de crédito. <a href="{{ route('accounting.credit-notes.create') }}">Crear una</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notes->total() > 0)
        <div class="cx-card-footer">
            <span class="cx-muted">{{ $notes->firstItem() }} – {{ $notes->lastItem() }} de {{ $notes->total() }}</span>
            @if($notes->hasPages())
            <div class="cx-pager-wrap">{{ $notes->links('vendor.pagination.primetrack') }}</div>
            @endif
        </div>
        @endif
    </div>
</div>
<style>
.cx-page { --cx-navy:#0A2D6F; --cx-blue:#1E4FA8; --cx-green:#16794C; --cx-red:#D64545; --cx-line:#E8EEF8; --cx-soft:#F4F8FD; padding:1.15rem 0 2.25rem; max-width:96rem; margin:0 auto; width:100%; }
.cx-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem; margin-bottom:1.15rem; }
.cx-kpi-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; padding:0.9rem 1.05rem; display:flex; flex-direction:column; gap:0.28rem; }
.cx-kpi-card--green { border-color:#A7DFC3; background:linear-gradient(180deg,#fff 40%,#F2FBF6 140%); }
.cx-kpi-label { font-size:0.66rem; font-weight:800; text-transform:uppercase; letter-spacing:0.07em; color:#94a3b8; }
.cx-kpi-value { font-size:1.35rem; font-weight:800; color:#0f172a; }
.cx-kpi-note { font-size:0.7rem; color:#94a3b8; }
.cx-alert { padding:0.85rem 1.05rem; border-radius:0.7rem; margin-bottom:1rem; font-size:0.875rem; font-weight:600; }
.cx-alert-success { background:#EFFAF4; border:1px solid #A7DFC3; color:#116039; }
.cx-alert-danger { background:#FDECEC; border:1px solid #F6C9C9; color:#B03030; }
.cx-card { background:#fff; border:1px solid var(--cx-line); border-radius:0.85rem; overflow:hidden; box-shadow:0 2px 8px rgba(15,23,42,0.04); }
.cx-table-scroll { overflow-x:auto; }
.cx-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.cx-table thead th { background:var(--cx-navy); color:#fff; text-align:left; padding:0.62rem 0.8rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; }
.cx-table thead th.cx-num { text-align:right; }
.cx-table td { padding:0.7rem 0.8rem; border-bottom:1px solid #f4f7fb; color:#334155; }
.cx-num { text-align:right; font-variant-numeric:tabular-nums; }
.cx-th-actions, .cx-actions { text-align:right; }
.cx-folio { font-weight:800; color:var(--cx-blue); }
.cx-client { font-weight:700; color:#0f172a; }
.cx-muted { color:#94a3b8; font-size:0.75rem; }
.cx-empty { padding:1.4rem 1rem; text-align:center; color:#94a3b8; }
.cx-empty a { color:var(--cx-navy); font-weight:700; }
.cx-status { display:inline-flex; padding:0.2rem 0.55rem; border-radius:999px; font-size:0.68rem; font-weight:800; }
.cx-status--paid { background:#EFFAF4; color:#116039; border:1px solid #A7DFC3; }
.cx-status--partial { background:#FFF6E8; color:#9A6700; border:1px solid #F3D19C; }
.cx-status--overdue { background:#FDECEC; color:#B03030; border:1px solid #F6C9C9; }
.cx-actions { display:flex; justify-content:flex-end; gap:0.4rem; align-items:center; }
.cx-action-btn { display:inline-flex; padding:0.32rem 0.6rem; font-size:0.72rem; font-weight:700; border-radius:0.45rem; border:1px solid #C5D4EB; background:#fff; color:var(--cx-blue); cursor:pointer; }
.cx-text-green { color:var(--cx-green); }
.cx-card-footer { display:flex; justify-content:space-between; padding:0.75rem 1.1rem; border-top:1px solid var(--cx-line); }
</style>
@endsection
