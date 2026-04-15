@extends('layouts.app')

@section('title', 'Paquetes')

@section('content')
@php
    $packagesDisplayTz = config('app.display_timezone') ?: 'America/New_York';
@endphp
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
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
            </span>
            <span class="packages-stat-label">Total</span>
            <span class="packages-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-air">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 14 8-3 3-8 2 2-2 7 7 2 2 2-8 1-2 4-2-2 1-4-7-1Z"/></svg>
            </span>
            <span class="packages-stat-label">Aéreo</span>
            <span class="packages-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-sea">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 18s2.5 3 5 3 4-2 5-2 2.5 2 5 2 5-3 5-3M4 16V9h14v7M8 9l1.5-3h3L14 9"/></svg>
            </span>
            <span class="packages-stat-label">Marítimo</span>
            <span class="packages-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-ready">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4 10-10"/></svg>
            </span>
            <span class="packages-stat-label">Listos</span>
            <span class="packages-stat-value">{{ number_format($statsReady ?? 0) }}</span>
        </div>
        <div class="packages-stat-card packages-stat-delivered">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M7.5 16.5 3.75 12 7.5 7.5m9 9 3.75-4.5-3.75-4.5"/></svg>
            </span>
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
                    <a href="{{ route('packages.index', ['clear_filters' => 1]) }}" class="packages-btn packages-btn-ghost">Limpiar</a>
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
                        <th>Código almacén</th>
                        <th>Tracking</th>
                        <th>Fecha ingreso</th>
                        <th>Nombre (etiqueta)</th>
                        <th>Agencia</th>
                        <th>Servicio</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th class="packages-th-actions"><span class="packages-sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr class="packages-clickable-row" data-href="{{ route('packages.show', $package->id) }}">
                        <td>
                            <span class="packages-code" title="{{ $package->warehouse_code ?? 'Sin código' }}">{{ $package->warehouse_code ?? '—' }}</span>
                        </td>
                        <td>
                            @php $pkgTrk = trim((string) ($package->tracking_external ?? '')); @endphp
                            <span class="packages-tracking" title="{{ $pkgTrk !== '' ? $pkgTrk : 'Sin tracking' }}">{{ $pkgTrk !== '' ? Str::limit($pkgTrk, 28) : '—' }}</span>
                        </td>
                        <td>
                            <span class="packages-date">{{ $package->created_at ? $package->created_at->timezone($packagesDisplayTz)->format('d/m/Y H:i') : '—' }}</span>
                        </td>
                        <td>
                            <span class="packages-name" title="{{ $package->label_name }}">{{ $package->label_name ? Str::limit($package->label_name, 35) : '—' }}</span>
                        </td>
                        <td>
                            <span class="packages-agency" title="{{ $package->agency?->name ?? '' }}">{{ $package->agency?->name ?? '—' }}</span>
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
                            <div class="packages-action-group" role="group" aria-label="Acciones">
                                <a href="{{ route('packages.show', $package->id) }}" class="packages-icon-btn packages-icon-btn--view" title="Ver detalle" aria-label="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if($package->warehouse_code && (!auth()->user() || !auth()->user()->isAgencyUser()))
                                <a href="{{ route('preregistrations.label', $package->id) }}" target="_blank" class="packages-icon-btn packages-icon-btn--accent" title="Etiqueta" aria-label="Abrir etiqueta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </a>
                                @endif
                                @if($package->status == 'IN_WAREHOUSE_NIC')
                                <a href="{{ route('packages.process', $package->id) }}" class="packages-icon-btn packages-icon-btn--success" title="Procesar en almacén NIC" aria-label="Procesar paquete">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 15l3-3m0 0l-3-3m3 3h-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="packages-empty">
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
.packages-page { padding: 1.25rem 0 2rem; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.packages-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0f766e 45%, #128176 100%);
    border-radius: 0.875rem;
    padding: 1.125rem 1.25rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 8px 24px rgba(15, 118, 110, 0.16);
}
.packages-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.packages-hero-title { margin: 0; font-size: 1.85rem; font-weight: 600; color: #fff; letter-spacing: -0.02em; text-shadow: 0 1px 0 rgba(0, 0, 0, 0.08); }
.packages-hero-subtitle { margin: 0.28rem 0 0; font-size: 0.925rem; color: rgba(236, 253, 245, 0.96); max-width: 54ch; }
.packages-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.52rem 0.96rem; font-size: 0.875rem; font-weight: 600;
    color: #fff; background: #059669; border: 1px solid rgba(15, 118, 110, 0.45); border-radius: 0.625rem; text-decoration: none;
    box-shadow: 0 2px 8px rgba(2, 44, 34, 0.2); transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.packages-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(2, 44, 34, 0.24); color: #fff; background: #047857; border-color: #047857; }

/* Stats */
.packages-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.95rem; margin-bottom: 1.75rem; }
.packages-stat-card {
    border-radius: 0.75rem; padding: 1rem 1rem 1.1rem; border: 1px solid #e6ecf3;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06); display: flex; flex-direction: column; gap: 0.28rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.packages-stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1); }
.packages-stat-icon { display: inline-flex; align-items: center; justify-content: center; width: 1.85rem; height: 1.85rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.62); }
.packages-stat-label { font-size: 0.73rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.packages-stat-value { font-size: 1.72rem; line-height: 1.1; font-weight: 700; color: #0f172a; }
.packages-stat-total { background: linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%); }
.packages-stat-air { background: linear-gradient(180deg, #eff6ff 0%, #f8fbff 100%); }
.packages-stat-sea { background: linear-gradient(180deg, #ecfeff 0%, #f0fdfa 100%); }
.packages-stat-ready { background: linear-gradient(180deg, #f0fdf4 0%, #f7fee7 100%); }
.packages-stat-delivered { background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); }

/* Card */
.packages-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e7edf4; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05); margin-bottom: 1.75rem; overflow: hidden; }
.packages-card-header { padding: 1rem 1.2rem; border-bottom: 1px solid #edf2f7; background: #fbfcfe; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.packages-card-title { margin: 0; font-size: 0.94rem; font-weight: 600; color: #334155; }
.packages-card-badge { font-size: 0.8125rem; color: #64748b; font-weight: 500; }
.packages-card-body { padding: 1.3rem 1.2rem; }
.packages-card-footer { padding: 0.85rem 1.2rem; border-top: 1px solid #edf2f7; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #64748b; }

/* Filters */
.packages-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.packages-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.packages-field-search { grid-column: 1 / -1; }
@media (min-width: 640px) { .packages-field-search { grid-column: span 2; max-width: 340px; } }
.packages-label { display: block; font-size: 0.74rem; font-weight: 500; color: #64748b; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
.packages-input, .packages-select {
    display: block; width: 100%; padding: 0.58rem 0.82rem; font-size: 0.875rem; border: 1px solid #dbe3ec; border-radius: 0.625rem;
    background: #fff; color: #0f172a; transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.packages-input:hover, .packages-select:hover { border-color: #c7d2e0; background: #fcfdff; }
.packages-input:focus, .packages-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14); }
.packages-filters-actions { display: flex; flex-wrap: wrap; gap: 0.72rem; align-items: center; margin-top: 0.15rem; }

/* Buttons */
.packages-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.56rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.625rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
.packages-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25); }
.packages-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; box-shadow: 0 8px 16px rgba(15, 118, 110, 0.25); }
.packages-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.packages-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.packages-btn-ghost { background: transparent; color: #64748b; border-color: transparent; padding-left: 0.55rem; padding-right: 0.55rem; }
.packages-btn-ghost:hover { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }
.packages-btn-outline { background: #fff; color: #6b7280; border-color: #d1d5db; }
.packages-btn-outline:hover { background: #f9fafb; color: #374151; }
.packages-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.packages-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.packages-btn-success { background: #059669; color: #fff; border-color: #059669; }
.packages-btn-success:hover { background: #047857; color: #fff; }
.packages-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.packages-tracking {
    display: block;
    max-width: 11.5rem;
    font-size: 0.8125rem;
    color: #334155;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.packages-date {
    font-size: 0.8125rem;
    color: #4b5563;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.packages-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.34rem;
    flex-wrap: nowrap;
}
.packages-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.05rem;
    height: 2.05rem;
    padding: 0;
    border: 1px solid #dbe3ec;
    border-radius: 9999px;
    background: #fff;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.packages-icon-btn:hover {
    transform: translateY(-1px);
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
}
.packages-icon-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.22); border-color: #0d9488; }
.packages-icon-btn--view:hover { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.packages-icon-btn--accent {
    border-color: rgba(13, 148, 136, 0.35);
    color: #0f766e;
    background: #f0fdfa;
}
.packages-icon-btn--accent:hover {
    background: #ccfbf1;
    border-color: #0d9488;
    color: #0f766e;
}
.packages-icon-btn--success {
    border-color: rgba(5, 150, 105, 0.28);
    color: #047857;
    background: #ecfdf5;
}
.packages-icon-btn--success:hover {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}
.packages-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Table */
.packages-table-header { background: #fbfcfe; }
.packages-table-header .packages-card-title { color: #0f172a; }
.packages-table-header .packages-card-badge { color: #64748b; }
.packages-table-wrap { overflow-x: auto; }
.packages-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.packages-table th { text-align: left; padding: 0.82rem 1rem; font-weight: 500; font-size: 0.7rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.packages-table td { padding: 0.96rem 1rem; border-bottom: 1px solid #e9eef5; vertical-align: middle; color: #334155; }
.packages-table tbody tr:nth-child(even) { background: #fbfdff; }
.packages-table tbody tr:hover { background: #f0f9ff; }
.packages-clickable-row { cursor: pointer; transition: background 0.2s ease; }
.packages-code { font-family: ui-monospace, monospace; font-weight: 500; color: #0f172a; }
.packages-name { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.packages-agency { display: block; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #64748b; }
.packages-weight { color: #334155; }
.packages-uom { font-size: 0.75rem; color: #94a3b8; }
.packages-th-actions { text-align: right; width: 1%; }
.packages-actions { text-align: right; white-space: nowrap; vertical-align: middle; }
.packages-actions .packages-btn { margin-left: 0.25rem; }
.packages-badge { display: inline-block; padding: 0.26rem 0.62rem; font-size: 0.74rem; font-weight: 600; border-radius: 9999px; }
.packages-badge-air { background: #dbeafe; color: #1e40af; }
.packages-badge-sea { background: #dcfce7; color: #166534; }
.packages-status { }
.packages-status.status-info { background: #e0f2fe; color: #075985; }
.packages-status.status-warning { background: #fef3c7; color: #92400e; }
.packages-status.status-primary { background: #e0f2fe; color: #0369a1; }
.packages-status.status-success { background: #dcfce7; color: #166534; }
.packages-status.status-delivered { background: #e2e8f0; color: #334155; }
.packages-status.status-default { background: #f1f5f9; color: #475569; }
.packages-empty { text-align: center; padding: 3rem 1rem !important; }
.packages-empty-text { margin: 0 0 0.75rem; color: #64748b; }
.packages-pagination-info { font-weight: 500; }
.packages-pagination-links { display: flex; align-items: center; }
.packages-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.packages-pagination-links a, .packages-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.45rem; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; }
.packages-pagination-links a:hover { background: #f8fafc; color: #0d9488; }
.packages-pagination-links .disabled span { background: #f8fafc; color: #94a3b8; }
.packages-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.packages-clickable-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, form, label')) return;
            const href = row.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
});
</script>
@endsection
