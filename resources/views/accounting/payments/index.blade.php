@extends('layouts.app')

@section('title', 'Cobros')

@section('content')
<div class="cx-page">
    <x-module-banner section="Contabilidad" current="Cobros" title="Cobros PrimeTrack" subtitle="Pagos recibidos con impacto automático en CxC y contabilidad.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.payments.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Registrar cobro
            </a>
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
            <span class="cx-kpi-label">Total cobrado</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($kpis->collected, 2) }}</span>
            <span class="cx-kpi-note">{{ number_format($kpis->count) }} cobro(s) activos</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Este mes</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($kpis->month, 2) }}</span>
            <span class="cx-kpi-note">Pagos activos del mes en curso</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--red">
            <span class="cx-kpi-label">Cancelados</span>
            <span class="cx-kpi-value cx-text-red">${{ number_format($kpis->voided, 2) }}</span>
            <span class="cx-kpi-note">{{ number_format($kpis->voided_count) }} cobro(s) anulados</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('accounting.payments.index') }}" class="cx-filters-form">
            <div class="cx-field">
                <label class="cx-label" for="client">Cliente</label>
                <input type="text" name="client" id="client" class="cx-input" value="{{ request('client') }}" placeholder="Nombre…">
            </div>
            <div class="cx-field">
                <label class="cx-label" for="status">Estado</label>
                <select name="status" id="status" class="cx-input">
                    <option value="all" @selected($status === 'all')>Todos los estados</option>
                    <option value="active" @selected($status === 'active')>Activo</option>
                    <option value="void" @selected($status === 'void')>Cancelado</option>
                </select>
            </div>
            <div class="cx-filters-actions">
                <button class="cx-btn cx-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('accounting.payments.index') }}" class="cx-btn cx-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($payments->total()) }}</strong> {{ $payments->total() === 1 ? 'registro' : 'registros' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th class="cx-num">Monto</th>
                        <th>Moneda</th>
                        <th>Método</th>
                        <th>Cuenta</th>
                        <th>Estado</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="cx-nowrap">{{ $payment->paid_at->format('d/m/Y') }}</td>
                        <td>
                            @forelse($payment->allocations as $allocation)
                                @if($allocation->invoice)
                                <a href="{{ route('accounting.invoices.show', $allocation->invoice) }}" class="cx-folio" title="{{ $allocation->invoice->folio }}">#{{ $allocation->invoice->id }}</a>{{ $loop->last ? '' : ', ' }}
                                @endif
                            @empty
                                <span class="cx-muted">—</span>
                            @endforelse
                        </td>
                        <td>
                            <div class="cx-client">{{ $payment->agency?->name ?? '—' }}</div>
                            @if($payment->agency)
                            <span class="cx-type-badge">{{ $payment->agency->typeLabel() }}</span>
                            @endif
                        </td>
                        <td class="cx-num cx-text-green"><strong>${{ number_format((float) $payment->amount_usd, 2) }}</strong></td>
                        <td>USD</td>
                        <td>{{ $payment->methodLabel() }}</td>
                        <td>{{ $payment->accountLabel() }}</td>
                        <td>
                            <span class="cx-status {{ $payment->isVoid() ? 'cx-status--overdue' : 'cx-status--paid' }}">{{ $payment->isVoid() ? 'Cancelado' : 'Activo' }}</span>
                        </td>
                        <td class="cx-actions">
                            <a href="{{ route('accounting.payments.show', $payment) }}" class="cx-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Ver cobro
                            </a>
                            @if($payment->agency)
                            <a href="{{ route('accounting.receivables.show', $payment->agency) }}" class="cx-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                                Detalle CxC
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="cx-empty">No hay cobros en este filtro.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="cx-card-footer">
            <div class="cx-pager-wrap">{{ $payments->links('vendor.pagination.primetrack') }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.cx-page {
    --cx-navy: #0A2D6F;
    --cx-blue: #1E4FA8;
    --cx-green: #16794C;
    --cx-red: #D64545;
    --cx-line: #E8EEF8;
    --cx-border: #C5D4EB;
    --cx-soft: #F4F8FD;
    --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}
.cx-header { margin: 0 0 1.15rem; padding: 0.15rem 0.1rem 0; }
.cx-title { margin: 0; font-size: 1.85rem; font-weight: 800; color: var(--cx-navy); letter-spacing: -0.03em; line-height: 1.15; }
.cx-subtitle { margin: 0.35rem 0 0; font-size: 0.9rem; color: var(--cx-muted); }

.cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.cx-btn-primary:hover { background: var(--cx-blue); border-color: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); border-color: var(--cx-border); }
.cx-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }

.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }

.cx-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.cx-kpi-card {
    background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem;
    padding: 0.9rem 1.05rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex; flex-direction: column; gap: 0.28rem;
}
.cx-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.cx-kpi-card--red { border-color: #F6C9C9; background: linear-gradient(180deg, #fff 40%, #FDECEC 140%); }
.cx-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.cx-kpi-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.cx-kpi-note { font-size: 0.7rem; color: #94a3b8; }

.cx-card {
    background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem;
}
.cx-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.cx-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 10rem; flex: 1; max-width: 18rem; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-input {
    padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem;
    background: #fff; color: #0f172a; width: 100%; box-sizing: border-box;
}
.cx-input:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-filters-actions { display: flex; align-items: center; gap: 0.55rem; }

.cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.6rem; margin: 0 0.1rem 0.75rem; }
.cx-back-link { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.85rem; font-weight: 700; color: var(--cx-blue); text-decoration: none; }
.cx-back-link:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-toolbar-right { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
.cx-count { font-size: 0.85rem; color: #64748b; }
.cx-count strong { color: #0f172a; }

.cx-table-scroll { overflow-x: auto; }
.cx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.cx-table thead th {
    background: var(--cx-navy); color: #fff; text-align: left; padding: 0.62rem 0.8rem;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
}
.cx-table thead th.cx-num { text-align: right; }
.cx-table td { padding: 0.7rem 0.8rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.cx-table tbody tr:nth-child(even) td { background: #FAFCFF; }
.cx-table tbody tr:hover td { background: var(--cx-soft); }
.cx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cx-th-actions, .cx-actions { text-align: right; }
.cx-actions { white-space: nowrap; }
.cx-nowrap { white-space: nowrap; }
.cx-muted { color: #94a3b8; }
.cx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.cx-folio { font-weight: 800; color: var(--cx-blue); text-decoration: none; }
.cx-folio:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-client { font-weight: 700; color: #0f172a; }
.cx-type-badge {
    display: inline-flex; margin-top: 0.22rem; padding: 0.12rem 0.5rem; border-radius: 999px;
    background: #EFFAF4; color: #116039; font-size: 0.66rem; font-weight: 700; border: 1px solid #A7DFC3;
}
.cx-status { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; white-space: nowrap; }
.cx-status--paid { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.cx-status--overdue { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.cx-action-btn {
    display: inline-flex; align-items: center; gap: 0.28rem; padding: 0.32rem 0.6rem;
    font-size: 0.72rem; font-weight: 700; border-radius: 0.45rem; border: 1px solid #C5D4EB;
    background: #fff; color: var(--cx-blue); text-decoration: none; margin-left: 0.25rem;
}
.cx-action-btn:hover { background: var(--cx-soft); color: var(--cx-navy); }
.cx-card-footer { padding: 0.75rem 1.1rem; border-top: 1px solid var(--cx-line); }
.cx-pager-wrap .pt-pager { display: flex; align-items: center; gap: 0.3rem; }
.cx-pager-wrap .pt-pager-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0 0.45rem;
    border-radius: 0.45rem; border: 1px solid #d1d9e6;
    background: #fff; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 700;
}
.cx-pager-wrap .pt-pager-btn-active { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); }
.cx-pager-wrap .pt-pager-btn-disabled { opacity: 0.4; }
.cx-text-green { color: var(--cx-green); }
.cx-text-red { color: var(--cx-red); }

@media (max-width: 900px) {
    .cx-kpis { grid-template-columns: 1fr; }
    .cx-field { max-width: none; }
}
</style>
@endsection
