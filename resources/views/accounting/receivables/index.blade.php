@extends('layouts.app')

@section('title', 'Cuentas por cobrar')

@section('content')
<div class="cx-page">
    <x-module-banner section="Contabilidad" current="Cuentas por cobrar" title="Cuentas por cobrar" subtitle="Control de saldos pendientes por factura.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.payments.create') }}" class="mb-btn mb-btn-primary">Registrar cobro</a>
            <a href="{{ route('accounting.credit-notes.create') }}" class="mb-btn mb-btn-secondary">Nota de crédito</a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Saldo total</span>
            <span class="cx-kpi-value cx-text-red">${{ number_format($kpis->total, 2) }}</span>
            <span class="cx-kpi-note">{{ number_format($kpis->open_count) }} factura(s) abiertas</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Al día</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($kpis->current, 2) }}</span>
            <span class="cx-kpi-note">Dentro del plazo de crédito</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--red">
            <span class="cx-kpi-label">En mora</span>
            <span class="cx-kpi-value cx-text-red">${{ number_format($kpis->overdue, 2) }}</span>
            <span class="cx-kpi-note">Vencidas sin saldar</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Saldo a favor</span>
            <span class="cx-kpi-value cx-text-green">${{ number_format($kpis->credit ?? 0, 2) }}</span>
            <span class="cx-kpi-note">Crédito disponible de clientes</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('accounting.receivables.index') }}" class="cx-filters-form">
            <div class="cx-field">
                <label class="cx-label" for="client">Cliente</label>
                <input type="text" name="client" id="client" class="cx-input" value="{{ request('client') }}" placeholder="Nombre…">
            </div>
            <div class="cx-field">
                <label class="cx-label" for="status">Estado</label>
                <select name="status" id="status" class="cx-input">
                    <option value="all" @selected($status === 'all')>Todos los estados</option>
                    <option value="current" @selected($status === 'current')>Al Día</option>
                    <option value="overdue" @selected($status === 'overdue')>En mora</option>
                    <option value="partial" @selected($status === 'partial')>Parcial</option>
                    <option value="paid" @selected($status === 'paid')>Pagada</option>
                </select>
            </div>
            <div class="cx-filters-actions">
                <button class="cx-btn cx-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('accounting.receivables.index') }}" class="cx-btn cx-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($invoices->total()) }}</strong> {{ $invoices->total() === 1 ? 'registro' : 'registros' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                        <th class="cx-num">Monto original</th>
                        <th class="cx-num">Cobrado</th>
                        <th class="cx-num">Faltante</th>
                        <th class="cx-num">Saldo</th>
                        <th>Estado</th>
                        <th class="cx-num">Mora (días)</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    @php
                        $balance = $invoice->balanceUsd();
                        $ar = $invoice->arStatus();
                        $due = $invoice->dueAt();
                        $mora = $invoice->daysOverdue();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('accounting.invoices.show', $invoice) }}" class="cx-folio" title="{{ $invoice->folio }}">#{{ $invoice->id }}</a>
                        </td>
                        <td>
                            <div class="cx-client">{{ $invoice->agency?->name ?? '—' }}</div>
                            @if($invoice->agency)
                            <span class="cx-type-badge">{{ $invoice->agency->typeLabel() }}</span>
                            @endif
                        </td>
                        <td class="cx-nowrap">{{ optional($invoice->issued_at)->format('d/m/Y') ?? '—' }}</td>
                        <td class="cx-nowrap">{{ $due ? $due->format('d/m/Y') : '—' }}</td>
                        <td class="cx-num"><strong>${{ number_format((float) $invoice->total_usd, 2) }}</strong></td>
                        <td class="cx-num cx-text-green">${{ number_format((float) $invoice->amount_paid, 2) }}</td>
                        <td class="cx-num cx-text-red">${{ number_format($balance, 2) }}</td>
                        <td class="cx-num cx-text-red"><strong>${{ number_format($balance, 2) }}</strong></td>
                        <td>
                            <span class="cx-status cx-status--{{ $ar }}">{{ $invoice->arStatusLabel() }}</span>
                        </td>
                        <td class="cx-num {{ $mora > 0 ? 'cx-text-red' : 'cx-muted' }}">{{ $mora }}</td>
                        <td class="cx-actions">
                            <a href="{{ route('accounting.invoices.show', $invoice) }}" class="cx-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                                Ver factura
                            </a>
                            @if($invoice->agency)
                            <a href="{{ route('accounting.receivables.show', $invoice->agency) }}" class="cx-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                                Detalle CxC
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="cx-empty">No hay facturas en este filtro.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="cx-card-footer">
            <div class="cx-pager-wrap">{{ $invoices->links('vendor.pagination.primetrack') }}</div>
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
.cx-header {
    margin: 0 0 1.15rem;
    padding: 0.15rem 0.1rem 0;
    background: transparent;
    border: 0;
    box-shadow: none;
}
.cx-title {
    margin: 0;
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--cx-navy);
    letter-spacing: -0.03em;
    line-height: 1.15;
}
.cx-subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.9rem;
    color: var(--cx-muted);
}

.cx-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.cx-btn-primary { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.cx-btn-primary:hover { background: var(--cx-blue); border-color: var(--cx-blue); color: #fff; }
.cx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.cx-btn-secondary:hover { background: var(--cx-soft); color: var(--cx-navy); border-color: var(--cx-border); }
.cx-btn-sm { padding: 0.42rem 0.85rem; font-size: 0.8rem; }

.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.cx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }

.cx-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
.cx-kpi-card {
    background: #fff;
    border: 1px solid var(--cx-line);
    border-radius: 0.85rem;
    padding: 0.9rem 1.05rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
}
.cx-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 40%, #F2FBF6 140%); }
.cx-kpi-card--red { border-color: #F6C9C9; background: linear-gradient(180deg, #fff 40%, #FDECEC 140%); }
.cx-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.cx-kpi-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.cx-kpi-note { font-size: 0.7rem; color: #94a3b8; }

.cx-card {
    background: #fff;
    border: 1px solid var(--cx-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.cx-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.cx-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 10rem; flex: 1; max-width: 18rem; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; letter-spacing: 0; text-transform: none; }
.cx-input {
    padding: 0.52rem 0.7rem;
    font-size: 0.85rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
    width: 100%;
    box-sizing: border-box;
}
.cx-input:focus { outline: none; border-color: var(--cx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.cx-filters-actions { display: flex; align-items: center; gap: 0.55rem; }

.cx-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.6rem;
    margin: 0 0.1rem 0.75rem;
}
.cx-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--cx-blue);
    text-decoration: none;
}
.cx-back-link:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-toolbar-right { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
.cx-count { font-size: 0.85rem; color: #64748b; }
.cx-count strong { color: #0f172a; }

.cx-table-scroll { overflow-x: auto; }
.cx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.cx-table thead th {
    background: var(--cx-navy);
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.cx-table thead th.cx-num { text-align: right; }
.cx-table td {
    padding: 0.7rem 0.8rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.cx-table tbody tr:nth-child(even) td { background: #FAFCFF; }
.cx-table tbody tr:hover td { background: var(--cx-soft); }
.cx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cx-th-actions { text-align: right; }
.cx-actions { text-align: right; white-space: nowrap; }
.cx-nowrap { white-space: nowrap; }
.cx-muted { color: #94a3b8; }
.cx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.cx-folio { font-weight: 800; color: var(--cx-blue); text-decoration: none; }
.cx-folio:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-client { font-weight: 700; color: #0f172a; }
.cx-type-badge {
    display: inline-flex;
    margin-top: 0.22rem;
    padding: 0.12rem 0.5rem;
    border-radius: 999px;
    background: #EFFAF4;
    color: #116039;
    font-size: 0.66rem;
    font-weight: 700;
    border: 1px solid #A7DFC3;
}
.cx-status {
    display: inline-flex;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    white-space: nowrap;
}
.cx-status--paid { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.cx-status--current { background: #EAF1FC; color: var(--cx-blue); border: 1px solid #C9DAF3; }
.cx-status--overdue { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.cx-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    padding: 0.32rem 0.6rem;
    font-size: 0.72rem;
    font-weight: 700;
    border-radius: 0.45rem;
    border: 1px solid #C5D4EB;
    background: #fff;
    color: var(--cx-blue);
    text-decoration: none;
    margin-left: 0.25rem;
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

@media (max-width: 1100px) {
    .cx-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .cx-kpis { grid-template-columns: 1fr; }
    .cx-field { max-width: none; }
}
</style>
@endsection
