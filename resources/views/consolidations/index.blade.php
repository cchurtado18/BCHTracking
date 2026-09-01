@extends('layouts.app')

@section('title', 'Consolidaciones')

@section('content')
<div class="cx-page">
    <x-module-banner section="Operaciones" current="Consolidaciones" title="Consolidaciones PrimeTrack" subtitle="Sacos Miami → Nicaragua. Cree un saco, filtre por estado o servicio y abra el detalle o el reporte.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 8.25h16.5M3.75 15.75h16.5M7.5 3.75v16.5m9-16.5v16.5"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('consolidations.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo saco
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
            <span class="cx-kpi-label">Total</span>
            <span class="cx-kpi-value">{{ number_format($statsTotal ?? 0) }}</span>
            <span class="cx-kpi-note">Sacos en el listado</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Abiertos</span>
            <span class="cx-kpi-value">{{ number_format($statsOpen ?? 0) }}</span>
            <span class="cx-kpi-note">Aún se pueden cargar items</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Enviados</span>
            <span class="cx-kpi-value">{{ number_format($statsSent ?? 0) }}</span>
            <span class="cx-kpi-note">En tránsito a Nicaragua</span>
        </div>
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Recibidos</span>
            <span class="cx-kpi-value cx-text-green">{{ number_format($statsReceived ?? 0) }}</span>
            <span class="cx-kpi-note">Ya en bodega NIC</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Aéreo</span>
            <span class="cx-kpi-value">{{ number_format($statsAir ?? 0) }}</span>
            <span class="cx-kpi-note">Servicio AIR</span>
        </div>
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Marítimo</span>
            <span class="cx-kpi-value">{{ number_format($statsSea ?? 0) }}</span>
            <span class="cx-kpi-note">Servicio SEA</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('consolidations.index') }}" class="cx-filters-form">
            <div class="cx-field">
                <label class="cx-label" for="status">Estado</label>
                <select name="status" id="status" class="cx-input">
                    <option value="">Todos los estados</option>
                    <option value="OPEN" @selected(request('status') === 'OPEN')>Abierto</option>
                    <option value="SENT" @selected(request('status') === 'SENT')>Enviado</option>
                    <option value="RECEIVED" @selected(request('status') === 'RECEIVED')>Recibido</option>
                    <option value="CANCELLED" @selected(request('status') === 'CANCELLED')>Cancelado</option>
                </select>
            </div>
            <div class="cx-field">
                <label class="cx-label" for="service_type">Servicio</label>
                <select name="service_type" id="service_type" class="cx-input">
                    <option value="">Todos los servicios</option>
                    <option value="AIR" @selected(request('service_type') === 'AIR')>Aéreo</option>
                    <option value="SEA" @selected(request('service_type') === 'SEA')>Marítimo</option>
                </select>
            </div>
            <div class="cx-filters-actions">
                <button class="cx-btn cx-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('consolidations.index', ['clear_filters' => 1]) }}" class="cx-btn cx-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="cx-toolbar">
        <span class="cx-count">Total: <strong>{{ number_format($consolidations->total()) }}</strong> {{ $consolidations->total() === 1 ? 'registro' : 'registros' }}.</span>
    </div>

    <div class="cx-card">
        <div class="cx-table-scroll">
            <table class="cx-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Items</th>
                        <th>Fecha</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidations as $consolidation)
                    @php
                        $statusLabels = [
                            'OPEN' => ['Abierto', 'cx-status--open'],
                            'SENT' => ['Enviado', 'cx-status--sent'],
                            'RECEIVED' => ['Recibido', 'cx-status--paid'],
                            'CANCELLED' => ['Cancelado', 'cx-status--overdue'],
                        ];
                        $sl = $statusLabels[$consolidation->status ?? ''] ?? [$consolidation->status ?? '—', 'cx-status--open'];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('consolidations.show', $consolidation->id) }}" class="cx-folio">{{ $consolidation->code }}</a>
                        </td>
                        <td>
                            <span class="cx-type-badge {{ ($consolidation->service_type ?? '') === 'SEA' ? 'cx-type-badge--sea' : '' }}">
                                {{ \App\Support\ServiceType::label($consolidation->service_type) }}
                            </span>
                        </td>
                        <td><span class="cx-status {{ $sl[1] }}">{{ $sl[0] }}</span></td>
                        <td>
                            {{ $consolidation->items_count }} <span class="cx-muted">{{ $consolidation->items_count === 1 ? 'item' : 'items' }}</span>
                            @if($consolidation->items_count == 1)
                            <span class="cx-type-badge">1 caja</span>
                            @endif
                        </td>
                        <td class="cx-nowrap">{{ $consolidation->created_at->format('d/m/Y') }}</td>
                        <td class="cx-actions">
                            <a href="{{ route('consolidations.show', $consolidation->id) }}" class="cx-action-btn">Ver</a>
                            <a href="{{ route('consolidations.report', $consolidation->id) }}" target="_blank" class="cx-action-btn">Reporte</a>
                            <a href="{{ route('consolidations.edit', $consolidation->id) }}" class="cx-action-btn">Editar</a>
                            @if($consolidation->status === 'OPEN')
                            <form action="{{ route('consolidations.destroy', $consolidation->id) }}" method="POST" class="cx-inline-form" onsubmit="return confirm('¿Eliminar este saco? Se quitarán los items y los preregistros quedarán disponibles de nuevo.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cx-action-btn cx-action-btn--danger">Eliminar</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="cx-empty">
                            No hay consolidaciones con los filtros actuales.
                            <a href="{{ route('consolidations.create') }}" class="cx-folio">Crear saco</a>
                            ·
                            <a href="{{ route('consolidations.index', ['clear_filters' => 1]) }}" class="cx-folio">Ver todos</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consolidations->hasPages())
        <div class="cx-card-footer">
            <span class="cx-count">{{ $consolidations->firstItem() }} – {{ $consolidations->lastItem() }} de {{ $consolidations->total() }}</span>
            <div class="cx-pager-wrap">{{ $consolidations->links('vendor.pagination.primetrack') }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.cx-page {
    --cx-navy: #0A2D6F; --cx-blue: #1E4FA8; --cx-green: #16794C; --cx-red: #D64545;
    --cx-line: #E8EEF8; --cx-border: #C5D4EB; --cx-soft: #F4F8FD; --cx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem; max-width: 96rem; margin: 0 auto; width: 100%;
}
.cx-header {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem;
    background: #fff; border: 1px solid var(--cx-line); border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem; margin-bottom: 1.15rem; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.cx-breadcrumb { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.45rem; }
.cx-breadcrumb strong { color: #334155; font-weight: 700; }
.cx-title-row { display: flex; align-items: center; gap: 0.6rem; }
.cx-title-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.35rem; height: 2.35rem; border-radius: 0.65rem;
    background: linear-gradient(135deg, var(--cx-navy), var(--cx-blue));
    color: #fff; box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28); flex-shrink: 0;
}
.cx-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.cx-subtitle { margin: 0.4rem 0 0; font-size: 0.875rem; color: var(--cx-muted); line-height: 1.45; max-width: 44rem; }
.cx-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }
.cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
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
.cx-kpi-label { font-size: 0.66rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
.cx-kpi-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; color: #0f172a; }
.cx-kpi-note { font-size: 0.7rem; color: #94a3b8; }
.cx-card { background: #fff; border: 1px solid var(--cx-line); border-radius: 0.85rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); overflow: hidden; margin-bottom: 1.15rem; }
.cx-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.cx-filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 10rem; flex: 1; max-width: 16rem; }
.cx-label { font-size: 0.8rem; font-weight: 700; color: #334155; }
.cx-input { padding: 0.52rem 0.7rem; font-size: 0.85rem; border: 1px solid #D8DCE2; border-radius: 0.55rem; background: #fff; color: #0f172a; width: 100%; box-sizing: border-box; }
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
.cx-table td { padding: 0.7rem 0.8rem; border-bottom: 1px solid #f4f7fb; color: #334155; vertical-align: middle; }
.cx-table tbody tr:nth-child(even) td { background: #FAFCFF; }
.cx-table tbody tr:hover td { background: var(--cx-soft); }
.cx-th-actions, .cx-actions { text-align: right; }
.cx-actions { white-space: nowrap; }
.cx-nowrap { white-space: nowrap; }
.cx-muted { color: #94a3b8; font-size: 0.78rem; }
.cx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; }
.cx-folio { font-weight: 800; color: var(--cx-blue); text-decoration: none; }
.cx-folio:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-type-badge {
    display: inline-flex; padding: 0.12rem 0.5rem; border-radius: 999px;
    background: #EAF1FC; color: var(--cx-blue); font-size: 0.66rem; font-weight: 700; border: 1px solid #C9DAF3;
}
.cx-type-badge--sea { background: #EFFAF4; color: #116039; border-color: #A7DFC3; }
.cx-status { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 800; white-space: nowrap; }
.cx-status--open { background: #EAF1FC; color: var(--cx-blue); border: 1px solid #C9DAF3; }
.cx-status--sent { background: #FFF6E8; color: #9A6700; border: 1px solid #F3D19C; }
.cx-status--paid { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.cx-status--overdue { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.cx-action-btn {
    display: inline-flex; align-items: center; gap: 0.28rem; padding: 0.32rem 0.6rem;
    font-size: 0.72rem; font-weight: 700; border-radius: 0.45rem; border: 1px solid #C5D4EB;
    background: #fff; color: var(--cx-blue); text-decoration: none; margin-left: 0.25rem; cursor: pointer;
}
.cx-action-btn:hover { background: var(--cx-soft); color: var(--cx-navy); }
.cx-action-btn--danger { color: var(--cx-red); border-color: #F6C9C9; }
.cx-action-btn--danger:hover { background: #FDECEC; color: #B03030; }
.cx-inline-form { display: inline; }
.cx-card-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.75rem 1.1rem; border-top: 1px solid var(--cx-line); }
.cx-pager-wrap .pt-pager { display: flex; align-items: center; gap: 0.3rem; }
.cx-pager-wrap .pt-pager-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 2rem; height: 2rem; padding: 0 0.45rem;
    border-radius: 0.45rem; border: 1px solid #d1d9e6;
    background: #fff; color: #334155; text-decoration: none; font-size: 0.8rem; font-weight: 700;
}
.cx-pager-wrap .pt-pager-btn-active { background: var(--cx-navy); color: #fff; border-color: var(--cx-navy); }
.cx-text-green { color: var(--cx-green); }
@media (max-width: 900px) { .cx-kpis { grid-template-columns: 1fr; } .cx-field { max-width: none; } }
</style>
@endsection
