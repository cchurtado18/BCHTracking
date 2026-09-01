@extends('layouts.app')

@section('title', 'Paquetes')

@push('styles')
<style>
.packages-page { padding: 1.25rem 0 2rem; max-width: 96rem; margin: 0 auto; width: 100%; }

.packages-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 0.875rem;
    padding: 1rem 1.2rem;
    margin-bottom: 1.35rem;
    box-shadow: 0 6px 16px rgba(10, 45, 111, 0.18);
}
.packages-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.packages-hero-title { margin: 0; font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: -0.02em; }
.packages-hero-subtitle { margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 400; color: rgba(236, 253, 245, 0.94); max-width: 54ch; }
.packages-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.packages-hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.55rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.55rem;
    border: 1px solid transparent;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}
.packages-hero-btn:hover { transform: translateY(-1px); }
.packages-hero-btn-primary {
    background: #ffffff;
    color: #0A2D6F;
    border-color: #ffffff;
}
.packages-hero-btn-primary:hover {
    background: #E8EEF8;
    color: #062048;
    border-color: #E8EEF8;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.18);
}

.packages-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1.15rem;
}
.packages-stat-card {
    background: #ffffff;
    border-radius: 0.75rem;
    padding: 1rem 1.1rem;
    border: 1px solid #e8ecf1;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 0.85rem;
}
.packages-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.65rem;
    height: 2.65rem;
    flex-shrink: 0;
    border-radius: 0.65rem;
    background: #eef2ff;
}
.packages-stat-body { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.packages-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #9ca3af;
}
.packages-stat-value {
    font-size: 1.55rem;
    line-height: 1.1;
    font-weight: 800;
    color: #1f2937;
}
.packages-stat-total .packages-stat-icon { background: #e8eef8; color: #0A2D6F; }
.packages-stat-air .packages-stat-icon { background: #dbeafe; color: #1d4ed8; }
.packages-stat-sea .packages-stat-icon { background: #e0f2fe; color: #0369a1; }
.packages-stat-ready .packages-stat-icon { background: #dcfce7; color: #16a34a; }
.packages-stat-delivered .packages-stat-icon { background: #ffedd5; color: #ea580c; }

.packages-filters {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.05rem 1.2rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.packages-filters-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.7rem 0.75rem;
}
.packages-field { display: flex; flex-direction: column; gap: 0.32rem; min-width: 0; }
.packages-field-search { flex: 1 1 200px; min-width: 170px; }
.packages-field-select { flex: 0 1 135px; min-width: 115px; }
.packages-field-date { flex: 0 1 135px; min-width: 125px; }
.packages-label { font-size: 0.75rem; font-weight: 500; color: #6b7280; }
.packages-input, .packages-select {
    display: block;
    width: 100%;
    padding: 0.52rem 0.7rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.45rem;
    background: #fff;
    color: #111827;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.packages-input::placeholder { color: #9ca3af; }
.packages-input:focus, .packages-select:focus {
    outline: none;
    border-color: #0A2D6F;
    box-shadow: 0 0 0 3px rgba(10, 45, 111, 0.12);
}
.packages-filters-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-left: auto;
    padding-bottom: 1px;
}
.packages-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}
.packages-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
.packages-btn-primary:hover { background: #143A8C; border-color: #143A8C; color: #fff; }
.packages-btn-secondary { background: #fff; color: #4b5563; border-color: #d1d5db; }
.packages-btn-secondary:hover { background: #f9fafb; color: #111827; border-color: #9ca3af; }

.packages-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.packages-count { font-size: 0.875rem; color: #6b7280; font-weight: 500; }

.packages-table-shell { background: transparent; margin-bottom: 1.5rem; }
.packages-table-wrap {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.packages-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}
.packages-table thead th {
    text-align: left;
    padding: 0.7rem 0.875rem;
    font-weight: 700;
    font-size: 0.78rem;
    color: #fff;
    background: #0A2D6F;
    white-space: nowrap;
    border: none;
}
.packages-table thead th:first-child { border-radius: 0.75rem 0 0 0; }
.packages-table thead th:last-child { border-radius: 0 0.75rem 0 0; }
.packages-table tbody td {
    padding: 0.62rem 0.875rem;
    vertical-align: middle;
    color: #111827;
    background: #fff;
    border-bottom: 1px solid #eef2f7;
}
.packages-table tbody tr:last-child td { border-bottom: none; }
.packages-table tbody tr:nth-child(even) td { background: #f8fafc; }
.packages-table tbody tr:hover td { background: #F4F8FD; }
.packages-clickable-row { cursor: pointer; }

.packages-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: #0f172a; }
.packages-name { display: block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; color: #111827; }
.packages-description { display: block; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; font-size: 0.8125rem; }
.packages-agency { display: block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; font-size: 0.8125rem; }
.packages-tracking {
    display: inline-block;
    max-width: 12rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #1E4FA8;
    text-decoration: underline;
    text-underline-offset: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.packages-tracking--empty { color: #9ca3af; text-decoration: none; }
.packages-date { font-size: 0.8125rem; color: #4b5563; white-space: nowrap; font-variant-numeric: tabular-nums; }
.packages-weight { color: #374151; font-variant-numeric: tabular-nums; }
.packages-uom { font-size: 0.75rem; color: #9ca3af; }

.packages-badge {
    display: inline-block;
    padding: 0.17rem 0.58rem;
    font-size: 0.735rem;
    font-weight: 600;
    border-radius: 9999px;
    border: 1px solid transparent;
    white-space: nowrap;
}
.packages-badge-air,
.packages-badge-sea { background: #e5e7eb; color: #374151; }
.packages-badge-cft { background: #E8F6EE; color: #16794C; }
.packages-status.status-info { background: #fef3c7; color: #92400e; }
.packages-status.status-warning { background: #ffedd5; color: #9a3412; }
.packages-status.status-primary { background: #dbeafe; color: #1e40af; }
.packages-status.status-success { background: #dcfce7; color: #166534; }
.packages-status.status-delivered { background: #e5e7eb; color: #374151; }
.packages-status.status-default { background: #f3f4f6; color: #4b5563; }

.packages-th-actions { text-align: right; width: 1%; }
.packages-actions { text-align: right; white-space: nowrap; }
.packages-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.15rem;
}
.packages-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.875rem;
    height: 1.875rem;
    padding: 0;
    border: none;
    border-radius: 0.375rem;
    background: transparent;
    color: #4b5563;
    text-decoration: none;
    cursor: pointer;
    transition: color 0.15s ease, background 0.15s ease;
}
.packages-icon-btn:hover { background: #f3f4f6; color: #111827; }
.packages-icon-btn--view:hover { color: #1E4FA8; background: #eff6ff; }
.packages-icon-btn--accent:hover { color: #0A2D6F; background: #E8EEF8; }
.packages-icon-btn--success:hover { color: #166534; background: #dcfce7; }
.packages-sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}

.packages-empty { text-align: center; padding: 3rem 1rem !important; background: #fff !important; }
.packages-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.packages-empty .packages-btn { margin: 0 0.25rem; }

.packages-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 0.15rem 0;
    font-size: 0.875rem;
    color: #6b7280;
}
.packages-pagination-info { font-weight: 600; color: #4b5563; }
.packages-pagination-links { display: flex; align-items: center; }

.pt-pager {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.pt-pager-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.15rem;
    height: 2.15rem;
    padding: 0 0.5rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0A2D6F;
    background: #fff;
    border: 1px solid #C5D4EB;
    border-radius: 0.5rem;
    text-decoration: none;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}
.pt-pager-btn:hover {
    background: #E8EEF8;
    color: #0A2D6F;
    border-color: #1E4FA8;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.14);
}
.pt-pager-btn-active {
    background: #0A2D6F;
    color: #fff;
    border-color: #0A2D6F;
    box-shadow: 0 3px 8px rgba(10, 45, 111, 0.28);
    pointer-events: none;
}
.pt-pager-btn-disabled {
    color: #9ca3af;
    background: #f3f4f6;
    border-color: #e5e7eb;
    box-shadow: none;
    cursor: default;
    pointer-events: none;
}
.pt-pager-ellipsis {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.4rem;
    color: #9ca3af;
    font-weight: 700;
}

@media (max-width: 1100px) {
    .packages-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .packages-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .packages-field-search,
    .packages-field-select,
    .packages-field-date { flex: 1 1 100%; }
    .packages-filters-actions { width: 100%; margin-left: 0; }
}
@media (max-width: 480px) {
    .packages-stats { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $packagesDisplayTz = config('app.display_timezone') ?: 'America/New_York';
    $isClientView = (bool) auth()->user()?->isAgencyUser();
    $packagesColspan = $isClientView ? 8 : 10;
@endphp
<div class="packages-page">
    <x-module-banner section="General" current="Paquetes" title="{{ $isClientView ? 'Mis paquetes' : 'Paquetes PrimeTrack' }}" subtitle="{{ $isClientView ? 'Consulte el estado de sus envíos.' : 'Listado unificado de preregistros y paquetes. Filtra por estado, servicio o agencia.' }}" :hide-back="$isClientView">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
        </x-slot:icon>
        @unless($isClientView)
        <x-slot:actions>
            <a href="{{ route('reporte.solicitar') }}" class="mb-btn mb-btn-primary">Reporte PDF</a>
        </x-slot:actions>
        @endunless
    </x-module-banner>

    <div class="packages-stats">
        <div class="packages-stat-card packages-stat-total">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Total</span>
                <span class="packages-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
            </div>
        </div>
        <div class="packages-stat-card packages-stat-air">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 14 8-3 3-8 2 2-2 7 7 2 2 2-8 1-2 4-2-2 1-4-7-1Z"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Aéreo</span>
                <span class="packages-stat-value">{{ number_format($statsAir ?? 0) }}</span>
            </div>
        </div>
        <div class="packages-stat-card packages-stat-sea">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 18s2.5 3 5 3 4-2 5-2 2.5 2 5 2 5-3 5-3M4 16V9h14v7M8 9l1.5-3h3L14 9"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Marítimo</span>
                <span class="packages-stat-value">{{ number_format($statsSea ?? 0) }}</span>
            </div>
        </div>
        <div class="packages-stat-card packages-stat-cft">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5 12 3 3 7.5m18 0-9 4.5m9-4.5v9l-9 4.5m0-13.5L3 7.5m9 4.5v9m0-9L3 7.5m0 0v9l9 4.5"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Pie cúbico</span>
                <span class="packages-stat-value">{{ number_format($statsCft ?? 0) }}</span>
            </div>
        </div>
        <div class="packages-stat-card packages-stat-ready">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4 10-10"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Listos</span>
                <span class="packages-stat-value">{{ number_format($statsReady ?? 0) }}</span>
            </div>
        </div>
        <div class="packages-stat-card packages-stat-delivered">
            <span class="packages-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="packages-stat-body">
                <span class="packages-stat-label">Entregados</span>
                <span class="packages-stat-value">{{ number_format($statsDelivered ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="packages-filters">
        <form method="GET" action="{{ route('packages.index') }}" class="packages-filters-form">
            <div class="packages-field packages-field-search">
                <label class="packages-label">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tracking, código, nombre…" class="packages-input">
            </div>
            <div class="packages-field packages-field-select">
                <label class="packages-label">Servicio</label>
                <select name="service_type" class="packages-select">
                    <option value="">Todos</option>
                    <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                    <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                    <option value="CFT" {{ request('service_type') == 'CFT' ? 'selected' : '' }}>Pie cúbico</option>
                </select>
            </div>
            @unless($isClientView)
            <div class="packages-field packages-field-select">
                <label class="packages-label">Ingreso</label>
                <select name="intake_type" class="packages-select">
                    <option value="">Todos</option>
                    <option value="COURIER" {{ request('intake_type') == 'COURIER' ? 'selected' : '' }}>Courier</option>
                    <option value="DROP_OFF" {{ request('intake_type') == 'DROP_OFF' ? 'selected' : '' }}>Drop Off</option>
                </select>
            </div>
            @endunless
            <div class="packages-field packages-field-select">
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
            @unless($isClientView)
            <div class="packages-field packages-field-select">
                <label class="packages-label">Agencia</label>
                <select name="agency_id" class="packages-select">
                    <option value="">Todas</option>
                    @foreach($agenciesForFilter ?? [] as $agency)
                    <option value="{{ $agency->id }}" {{ (int) request('agency_id') === (int) $agency->id ? 'selected' : '' }}>{{ $agency->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="packages-field packages-field-date">
                <label class="packages-label">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="packages-input">
            </div>
            <div class="packages-field packages-field-date">
                <label class="packages-label">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="packages-input">
            </div>
            <div class="packages-filters-actions">
                <button type="submit" class="packages-btn packages-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('packages.index', ['clear_filters' => 1]) }}" class="packages-btn packages-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="packages-toolbar">
        <span class="packages-count">{{ $packages->total() }} {{ $packages->total() === 1 ? 'registro' : 'registros' }}</span>
    </div>

    <div class="packages-table-shell">
        <div class="packages-table-wrap">
            <table class="packages-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tracking</th>
                        <th>Fecha ingreso</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        @unless($isClientView)
                        <th>Agencia</th>
                        @endunless
                        <th>Servicio</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        @unless($isClientView)
                        <th class="packages-th-actions">Acciones</th>
                        @endunless
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
                            <span class="packages-tracking {{ $pkgTrk === '' ? 'packages-tracking--empty' : '' }}" title="{{ $pkgTrk !== '' ? $pkgTrk : 'Sin tracking' }}">{{ $pkgTrk !== '' ? Str::limit($pkgTrk, 28) : '—' }}</span>
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
                        @unless($isClientView)
                        <td>
                            <span class="packages-agency" title="{{ $package->agency?->name ?? '' }}">{{ $package->agency?->name ?? '—' }}</span>
                        </td>
                        @endunless
                        <td>
                            <span class="packages-badge packages-badge-{{ strtolower($package->service_type ?? '') }}">
                                {{ \App\Support\ServiceType::label($package->service_type) }}
                            </span>
                        </td>
                        <td class="packages-weight">{{ number_format($package->verified_weight_lbs ?? $package->intake_weight_lbs ?? 0, 2) }} <span class="packages-uom">lb</span></td>
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
                        @unless($isClientView)
                        <td class="packages-actions">
                            <div class="packages-action-group" role="group" aria-label="Acciones">
                                <a href="{{ route('packages.show', $package->id) }}" class="packages-icon-btn packages-icon-btn--view" title="Ver detalle" aria-label="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if($package->warehouse_code)
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
                        @endunless
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $packagesColspan }}" class="packages-empty">
                            <p class="packages-empty-text">No hay paquetes con los filtros actuales.</p>
                            <a href="{{ route('packages.index', ['clear_filters' => 1]) }}" class="packages-btn packages-btn-secondary">Ver todos</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
        <div class="packages-footer">
            <span class="packages-pagination-info">
                {{ $packages->firstItem() }} – {{ $packages->lastItem() }} de {{ $packages->total() }}
            </span>
            <div class="packages-pagination-links">{{ $packages->links('vendor.pagination.primetrack') }}</div>
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
