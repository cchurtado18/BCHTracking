@extends('layouts.app')

@section('title', 'Paquetes')

@section('content')
<div class="packages-page">
    {{-- Hero --}}
    <header class="packages-hero">
        <div class="packages-hero-inner">
            <div class="packages-hero-text">
                <h1 class="packages-hero-title">Paquetes</h1>
                <p class="packages-hero-subtitle">Listado unificado de preregistros y paquetes. Filtra por estado, servicio o agencia.</p>
            </div>
            <a href="{{ route('reporte.solicitar') }}" class="packages-hero-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                Reporte PDF
            </a>
        </div>
    </header>

    {{-- Tarjetas de resumen --}}
    <div class="packages-stats">
        <div class="packages-stat-card packages-stat-total">
            <span class="packages-stat-label">Total</span>
            <span class="packages-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-air">
            <span class="packages-stat-label">Aéreo</span>
            <span class="packages-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-sea">
            <span class="packages-stat-label">Marítimo</span>
            <span class="packages-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-ready">
            <span class="packages-stat-label">Listos</span>
            <span class="packages-stat-value">{{ number_format($statsReady ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-delivered">
            <span class="packages-stat-label">Entregados</span>
            <span class="packages-stat-value">{{ number_format($statsDelivered ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="packages-card packages-filters-card">
        <div class="packages-card-header">
            <h2 class="packages-card-title">Filtros</h2>
        </div>
        <div class="packages-card-body">
            <form method="GET" action="{{ route('packages.index') }}" class="packages-filters-form">
                <div class="packages-filters-grid">
                    <div class="packages-field packages-field-search">
                        <label class="packages-label">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tracking, código, nombre" class="packages-input">
                    </div>
                    <div class="packages-field">
                        <label class="packages-label">Servicio</label>
                        <select name="service_type" class="packages-select">
                            <option value="">Todos</option>
                            <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                    </div>
                    <div class="packages-field">
                        <label class="packages-label">Ingreso</label>
                        <select name="intake_type" class="packages-select">
                            <option value="">Todos</option>
                            <option value="COURIER" {{ request('intake_type') == 'COURIER' ? 'selected' : '' }}>Courier</option>
                            <option value="DROP_OFF" {{ request('intake_type') == 'DROP_OFF' ? 'selected' : '' }}>Drop Off</option>
                        </select>
                    </div>
                    <div class="packages-field">
                        <label class="packages-label">Estado</label>
                        <select name="status" class="packages-select">
                            <option value="">Todos</option>
                            <option value="RECEIVED_MIAMI" {{ request('status') == 'RECEIVED_MIAMI' ? 'selected' : '' }}>Recibido Miami</option>
                            <option value="IN_TRANSIT" {{ request('status') == 'IN_TRANSIT' ? 'selected' : '' }}>En tránsito</option>
                            <option value="IN_WAREHOUSE_NIC" {{ request('status') == 'IN_WAREHOUSE_NIC' ? 'selected' : '' }}>En almacén NIC</option>
                            <option value="READY" {{ request('status') == 'READY' ? 'selected' : '' }}>Listo para retiro</option>
                            <option value="DELIVERED" {{ request('status') == 'DELIVERED' ? 'selected' : '' }}>Entregado</option>
                        </select>
                    </div>
                    @if(!auth()->user() || !auth()->user()->isAgencyUser())
                    <div class="packages-field">
                        <label class="packages-label">Agencia</label>
                        <select name="agency_id" class="packages-select">
                            <option value="">Todas</option>
                            @foreach($agenciesForFilter ?? [] as $agency)
                                <option value="{{ $agency->id }}" {{ (int) request('agency_id') === (int) $agency->id ? 'selected' : '' }}>{{ $agency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                <div class="packages-filters-actions">
                    <button type="submit" class="packages-btn packages-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('packages.index', ['clear_filters' => 1]) }}" class="packages-btn packages-btn-secondary">Limpiar</a>
                    <a href="{{ route('reporte.solicitar') }}" class="packages-btn packages-btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/></svg>
                        Reporte PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de paquetes --}}
    <div class="packages-card packages-table-card">
        <div class="packages-card-header packages-table-header">
            <h2 class="packages-card-title">Listado de paquetes</h2>
            <span class="packages-card-badge">{{ $packages->total() }} {{ $packages->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="packages-table-wrap">
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Código / Tracking</th>
                        <th>Nombre (etiqueta)</th>
                        <th>Agencia</th>
                        <th>Servicio</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th class="packages-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td>
                            <span class="packages-code" title="{{ $package->warehouse_code ?? $package->tracking_external }}">
                                {{ $package->warehouse_code ?? $package->tracking_external ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="packages-name" title="{{ $package->label_name }}">{{ $package->label_name ? Str::limit($package->label_name, 35) : '—' }}</span>
                        </td>
                        <td>
                            <span class="packages-agency" title="{{ $package->agency->name ?? '' }}">{{ $package->agency->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="packages-badge packages-badge-{{ strtolower($package->service_type ?? '') }}">
                                {{ $package->service_type == 'AIR' ? 'Aéreo' : ($package->service_type == 'SEA' ? 'Marítimo' : ($package->service_type ?? '—')) }}
                            </span>
                        </td>
                        <td class="packages-weight">{{ number_format($package->verified_weight_lbs ?? $package->intake_weight_lbs ?? 0, 2) }} <span class="packages-uom">lbs</span></td>
                        <td>
                            @php
                                $statusLabels = [
                                    'RECEIVED_MIAMI' => ['Recibido Miami', 'status-info'],
                                    'IN_TRANSIT' => ['En tránsito', 'status-warning'],
                                    'IN_WAREHOUSE_NIC' => ['En almacén NIC', 'status-primary'],
                                    'READY' => ['Listo retiro', 'status-success'],
                                    'DELIVERED' => ['Entregado', 'status-delivered'],
                                ];
                                $sl = $statusLabels[$package->status ?? ''] ?? [$package->status ?? '—', 'status-default'];
                            @endphp
                            <span class="packages-badge packages-status {{ $sl[1] }}">{{ $sl[0] }}</span>
                        </td>
                        <td class="packages-actions">
                            <a href="{{ route('packages.show', $package->id) }}" class="packages-btn packages-btn-sm packages-btn-outline-primary">Ver</a>
                            @if($package->status == 'IN_WAREHOUSE_NIC')
                            <a href="{{ route('packages.process', $package->id) }}" class="packages-btn packages-btn-sm packages-btn-success">Procesar</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="packages-empty">
                            <p class="packages-empty-text">No hay paquetes con los filtros actuales.</p>
                            <a href="{{ route('packages.index', ['clear_filters' => 1]) }}" class="packages-btn packages-btn-secondary">Ver todos</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
        <div class="packages-card-footer">
            <span class="packages-pagination-info">
                {{ $packages->firstItem() }} – {{ $packages->lastItem() }} de {{ $packages->total() }}
            </span>
            <div class="packages-pagination-links">{{ $packages->links() }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.packages-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.packages-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.packages-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.packages-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.packages-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 42ch; }
.packages-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    color: #0f766e; background: #fff; border: none; border-radius: 0.5rem; text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.15s, box-shadow 0.15s;
}
.packages-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #0d9488; }

/* Stats */
.packages-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.packages-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.packages-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.packages-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.packages-stat-total { border-left: 4px solid #0d9488; }
.packages-stat-air { border-left: 4px solid #3b82f6; }
.packages-stat-sea { border-left: 4px solid #059669; }
.packages-stat-ready { border-left: 4px solid #22c55e; }
.packages-stat-delivered { border-left: 4px solid #6b7280; }

/* Card */
.packages-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.packages-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.packages-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.packages-card-badge { font-size: 0.8125rem; color: #6b7280; font-weight: 500; }
.packages-card-body { padding: 1.25rem; }
.packages-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

/* Filters */
.packages-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.packages-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.packages-field-search { grid-column: 1 / -1; }
@media (min-width: 640px) { .packages-field-search { grid-column: span 2; max-width: 280px; } }
.packages-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.packages-input, .packages-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.packages-input:focus, .packages-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.packages-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Buttons */
.packages-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.packages-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.packages-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.packages-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.packages-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.packages-btn-outline { background: #fff; color: #6b7280; border-color: #d1d5db; }
.packages-btn-outline:hover { background: #f9fafb; color: #374151; }
.packages-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.packages-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.packages-btn-success { background: #059669; color: #fff; border-color: #059669; }
.packages-btn-success:hover { background: #047857; color: #fff; }
.packages-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

/* Table */
.packages-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.packages-table-header .packages-card-title { color: #fff; }
.packages-table-header .packages-card-badge { color: rgba(255,255,255,0.9); }
.packages-table-wrap { overflow-x: auto; }
.packages-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.packages-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.packages-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.packages-table tbody tr:hover { background: #f9fafb; }
.packages-code { font-family: ui-monospace, monospace; font-weight: 500; color: #111827; }
.packages-name { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.packages-agency { display: block; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; }
.packages-weight { color: #374151; }
.packages-uom { font-size: 0.75rem; color: #9ca3af; }
.packages-th-actions { text-align: right; }
.packages-actions { text-align: right; white-space: nowrap; }
.packages-actions .packages-btn { margin-left: 0.25rem; }
.packages-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.packages-badge-air { background: #dbeafe; color: #1d4ed8; }
.packages-badge-sea { background: #d1fae5; color: #047857; }
.packages-status { }
.packages-status.status-info { background: #e0f2fe; color: #0369a1; }
.packages-status.status-warning { background: #fef3c7; color: #b45309; }
.packages-status.status-primary { background: #e0e7ff; color: #4338ca; }
.packages-status.status-success { background: #d1fae5; color: #047857; }
.packages-status.status-delivered { background: #e5e7eb; color: #4b5563; }
.packages-status.status-default { background: #f3f4f6; color: #6b7280; }
.packages-empty { text-align: center; padding: 3rem 1rem !important; }
.packages-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.packages-pagination-info { font-weight: 500; }
.packages-pagination-links { display: flex; align-items: center; }
.packages-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.packages-pagination-links a, .packages-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.packages-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.packages-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.packages-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
