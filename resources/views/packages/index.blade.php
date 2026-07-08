@extends('layouts.app')

@section('title', 'Paquetes')

@push('styles')
@include('partials.packages-module-styles')
@endpush

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
                    <div class="packages-field-dates">
                        <div class="packages-field">
                            <label class="packages-label">Desde</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="packages-input">
                        </div>
                        <div class="packages-field">
                            <label class="packages-label">Hasta</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="packages-input">
                        </div>
                    </div>
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
                        <th>Descripción</th>
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
                            <span class="packages-description" title="{{ $package->description ?? '' }}">{{ $package->description ? Str::limit($package->description, 45) : '—' }}</span>
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
                        <td colspan="10" class="packages-empty">
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
