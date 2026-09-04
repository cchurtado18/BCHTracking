@extends('layouts.app')

@section('title', 'Escaneo NIC')

@section('content')
<div class="cx-page">
    <x-module-banner section="Operaciones" current="Escaneo NIC" title="Escaneo NIC PrimeTrack" subtitle="Sacos y contenedores enviados listos para recibir en Nicaragua. Escanee el código y luego los paquetes.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75h15v10.5h-15V6.75Zm3 3h4.5m-4.5 3h9"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('consolidations.index') }}" class="mb-btn mb-btn-secondary">Ver consolidaciones</a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="cx-alert cx-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="cx-alert cx-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="cx-card cx-scan-card">
        <form method="GET" action="{{ route('nic-consolidations.index') }}" id="saco-scan-form" class="cx-scan-form">
            <div class="cx-scan-field">
                <label class="cx-label" for="saco_code">Código del saco o contenedor</label>
                <input type="text" name="saco_code" id="saco_code" class="cx-input cx-input-lg" placeholder="Escanear con pistola…" autofocus value="{{ old('saco_code') }}" autocomplete="off">
            </div>
            <div class="cx-filters-actions">
                <button type="submit" class="cx-btn cx-btn-primary">Abrir consolidación</button>
            </div>
        </form>
        <p class="cx-scan-hint">Use la pistola sobre el código (SAC- o CNT-); al confirmar se abre el escaneo de paquetes.</p>
    </div>

    <div class="cx-kpis">
        <div class="cx-kpi-card">
            <span class="cx-kpi-label">Enviados</span>
            <span class="cx-kpi-value">{{ number_format($statsTotal ?? 0) }}</span>
            <span class="cx-kpi-note">Listos para escanear</span>
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
        <div class="cx-kpi-card cx-kpi-card--green">
            <span class="cx-kpi-label">Total items</span>
            <span class="cx-kpi-value cx-text-green">{{ number_format($statsTotalItems ?? 0) }}</span>
            <span class="cx-kpi-note">Paquetes en esas consolidaciones</span>
        </div>
    </div>

    <div class="cx-card cx-filters-card">
        <form method="GET" action="{{ route('nic-consolidations.index') }}" class="cx-filters-form">
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
                <a href="{{ route('nic-consolidations.index') }}" class="cx-btn cx-btn-secondary">Limpiar</a>
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
                        <th>Items</th>
                        <th>Escaneados</th>
                        <th>Faltantes</th>
                        <th>Fecha envío</th>
                        <th class="cx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidations as $consolidation)
                    @php
                        $scanned_count = $consolidation->items()->whereNotNull('scanned_at')->count();
                        $missing_count = $consolidation->items_count - $scanned_count;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('nic-consolidations.show', $consolidation->id) }}" class="cx-folio">{{ $consolidation->code }}</a>
                        </td>
                        <td>
                            <span class="cx-type-badge {{ ($consolidation->service_type ?? '') === 'SEA' ? 'cx-type-badge--sea' : '' }}">
                                {{ \App\Support\ServiceType::label($consolidation->service_type) }}
                            </span>
                        </td>
                        <td>{{ $consolidation->items_count }}</td>
                        <td><span class="cx-text-green">{{ $scanned_count }}</span></td>
                        <td>
                            @if($missing_count > 0)
                            <span class="cx-text-red">{{ $missing_count }}</span>
                            @else
                            <span class="cx-text-green">0</span>
                            @endif
                        </td>
                        <td class="cx-nowrap">{{ $consolidation->sent_at ? $consolidation->sent_at->format('d/m/Y H:i') : '—' }}</td>
                        <td class="cx-actions">
                            <a href="{{ route('nic-consolidations.show', $consolidation->id) }}" class="cx-action-btn">Escanear</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="cx-empty">
                            No hay sacos ni contenedores enviados para escanear.
                            <a href="{{ route('consolidations.index') }}" class="cx-folio">Ver consolidaciones</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consolidations->total() > 0)
        <div class="cx-card-footer">
            <span class="cx-count">{{ $consolidations->firstItem() }} – {{ $consolidations->lastItem() }} de {{ $consolidations->total() }}</span>
            @if($consolidations->hasPages())
            <div class="cx-pager-wrap">{{ $consolidations->links('vendor.pagination.primetrack') }}</div>
            @endif
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
.cx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 600; }
.cx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; }
.cx-alert-danger { background: #FDECEC; border: 1px solid #F6C9C9; color: #B03030; }
.cx-scan-card {
    padding: 1rem 1.15rem 0.95rem; margin-bottom: 1.15rem;
    background: linear-gradient(180deg, #fff 45%, #F4F8FD 160%);
    border-color: var(--cx-border);
}
.cx-scan-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.7rem; }
.cx-scan-field { display: flex; flex-direction: column; gap: 0.28rem; flex: 1; min-width: 16rem; max-width: 36rem; }
.cx-scan-hint { margin: 0.65rem 0 0; font-size: 0.78rem; color: #94a3b8; }
.cx-input-lg { font-size: 1rem; padding: 0.68rem 0.85rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; letter-spacing: 0.02em; }
.cx-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.15rem; }
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
.cx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; }
.cx-folio { font-weight: 800; color: var(--cx-blue); text-decoration: none; }
.cx-folio:hover { color: var(--cx-navy); text-decoration: underline; }
.cx-type-badge {
    display: inline-flex; padding: 0.12rem 0.5rem; border-radius: 999px;
    background: #EAF1FC; color: var(--cx-blue); font-size: 0.66rem; font-weight: 700; border: 1px solid #C9DAF3;
}
.cx-type-badge--sea { background: #EFFAF4; color: #116039; border-color: #A7DFC3; }
.cx-action-btn {
    display: inline-flex; align-items: center; gap: 0.28rem; padding: 0.32rem 0.6rem;
    font-size: 0.72rem; font-weight: 700; border-radius: 0.45rem; border: 1px solid #C5D4EB;
    background: #fff; color: var(--cx-blue); text-decoration: none; margin-left: 0.25rem; cursor: pointer;
}
.cx-action-btn:hover { background: var(--cx-soft); color: var(--cx-navy); }
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
.cx-text-red { color: var(--cx-red); font-weight: 700; }
@media (max-width: 900px) { .cx-kpis { grid-template-columns: 1fr 1fr; } .cx-field, .cx-scan-field { max-width: none; } }
@media (max-width: 560px) { .cx-kpis { grid-template-columns: 1fr; } }
</style>
@endsection
