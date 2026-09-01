@extends('layouts.app')

@section('title', 'Preregistros')

@push('styles')
<style>
.preregs-page { padding: 1.25rem 0 2rem; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.preregs-hero {
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 0.875rem;
    padding: 1rem 1.2rem;
    margin-bottom: 1.35rem;
    box-shadow: 0 6px 16px rgba(10, 45, 111, 0.18);
}
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-title { margin: 0; font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: -0.02em; }
.preregs-hero-subtitle { margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 400; color: rgba(236, 253, 245, 0.94); max-width: 54ch; }
.preregs-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.preregs-hero-btn {
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
.preregs-hero-btn:hover { transform: translateY(-1px); }
.preregs-hero-btn-primary {
    background: #ffffff;
    color: #0A2D6F;
    border-color: #ffffff;
}
.preregs-hero-btn-primary:hover {
    background: #E8EEF8;
    color: #062048;
    border-color: #E8EEF8;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.18);
}
.preregs-hero-btn-secondary {
    background: #1E4FA8;
    color: #ffffff;
    border-color: #7BA3E0;
}
.preregs-hero-btn-secondary:hover {
    background: #2A63C7;
    color: #ffffff;
    border-color: #ffffff;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.22);
}

/* Stats — cards horizontales con icono a la izquierda */
.preregs-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1.15rem;
}
.preregs-stat-card {
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
.preregs-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.65rem;
    height: 2.65rem;
    flex-shrink: 0;
    border-radius: 0.65rem;
    background: #eef2ff;
}
.preregs-stat-body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}
.preregs-stat-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #9ca3af;
}
.preregs-stat-value {
    font-size: 1.55rem;
    line-height: 1.1;
    font-weight: 800;
    color: #1f2937;
}
.preregs-stat-total .preregs-stat-icon { background: #e8eef8; color: #0A2D6F; }
.preregs-stat-air .preregs-stat-icon { background: #dbeafe; color: #1d4ed8; }
.preregs-stat-sea .preregs-stat-icon { background: #e0f2fe; color: #0369a1; }
.preregs-stat-received .preregs-stat-icon { background: #ffedd5; color: #ea580c; }
.preregs-stat-ready .preregs-stat-icon { background: #dcfce7; color: #16a34a; }

/* Filters — barra limpia tipo referencia */
.preregs-filters {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.05rem 1.2rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.preregs-filters-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.7rem 0.75rem;
}
.preregs-field { display: flex; flex-direction: column; gap: 0.32rem; min-width: 0; }
.preregs-field-search { flex: 1 1 200px; min-width: 170px; }
.preregs-field-select { flex: 0 1 135px; min-width: 115px; }
.preregs-field-date { flex: 0 1 135px; min-width: 125px; }
.preregs-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
}
.preregs-input, .preregs-select {
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
.preregs-input::placeholder { color: #9ca3af; }
.preregs-input:focus, .preregs-select:focus {
    outline: none;
    border-color: #0A2D6F;
    box-shadow: 0 0 0 3px rgba(10, 45, 111, 0.12);
}
.preregs-filters-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-left: auto;
    padding-bottom: 1px;
}
.preregs-btn {
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
.preregs-btn-primary {
    background: #0A2D6F;
    color: #fff;
    border-color: #0A2D6F;
}
.preregs-btn-primary:hover { background: #143A8C; border-color: #143A8C; color: #fff; }
.preregs-btn-secondary {
    background: #fff;
    color: #4b5563;
    border-color: #d1d5db;
}
.preregs-btn-secondary:hover { background: #f9fafb; color: #111827; border-color: #9ca3af; }
.preregs-btn-outline-accent {
    background: #fff;
    color: #0A2D6F;
    border-color: #0A2D6F;
}
.preregs-btn-outline-accent:hover { background: #F4F8FD; color: #0A2D6F; }

/* Toolbar acciones */
.preregs-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.preregs-toolbar-left { display: flex; align-items: center; gap: 0.5rem; }
.preregs-toolbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.preregs-count {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

/* Tabla — encabezado azul, filas blancas redondeadas */
.preregs-table-shell {
    background: transparent;
    margin-bottom: 1.5rem;
}
.preregs-table-wrap {
    overflow-x: auto;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.preregs-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}
.preregs-table thead th {
    text-align: left;
    padding: 0.7rem 0.875rem;
    font-weight: 700;
    font-size: 0.78rem;
    color: #fff;
    background: #0A2D6F;
    white-space: nowrap;
    border: none;
}
.preregs-table thead th:first-child { border-radius: 0.75rem 0 0 0; }
.preregs-table thead th:last-child { border-radius: 0 0.75rem 0 0; }
.preregs-table tbody td {
    padding: 0.62rem 0.875rem;
    vertical-align: middle;
    color: #111827;
    background: #fff;
    border-bottom: 1px solid #eef2f7;
}
.preregs-table tbody tr:last-child td { border-bottom: none; }
.preregs-table tbody tr:nth-child(even) td { background: #f8fafc; }
.preregs-table tbody tr:hover td { background: #F4F8FD; }
.preregs-clickable-row { cursor: pointer; }

.preregs-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600; color: #0f172a; }
.preregs-name { display: block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; color: #111827; }
.preregs-agency { display: block; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; font-size: 0.8125rem; }
.preregs-tracking {
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
.preregs-tracking--empty { color: #9ca3af; text-decoration: none; }
.preregs-date { font-size: 0.8125rem; color: #4b5563; white-space: nowrap; font-variant-numeric: tabular-nums; }
.preregs-weight { color: #374151; font-variant-numeric: tabular-nums; }
.preregs-uom { font-size: 0.75rem; color: #9ca3af; }
.preregs-photo-yes { color: #0A2D6F; font-weight: 700; }
.preregs-photo-no { color: #9ca3af; }

.preregs-badge {
    display: inline-block;
    padding: 0.17rem 0.58rem;
    font-size: 0.735rem;
    font-weight: 600;
    border-radius: 9999px;
    border: 1px solid transparent;
    white-space: nowrap;
}
.preregs-badge-air { background: #e5e7eb; color: #374151; }
.preregs-badge-sea { background: #e5e7eb; color: #374151; }
.preregs-badge-cft { background: #E8F6EE; color: #16794C; }
.preregs-status.status-pending { background: #eef2ff; color: #3730a3; }
.preregs-status.status-info { background: #fef3c7; color: #92400e; }
.preregs-status.status-warning { background: #ffedd5; color: #9a3412; }
.preregs-status.status-primary { background: #dbeafe; color: #1e40af; }
.preregs-status.status-success { background: #dcfce7; color: #166534; }
.preregs-status.status-delivered { background: #e5e7eb; color: #374151; }
.preregs-status.status-danger { background: #fee2e2; color: #991b1b; }
.preregs-status.status-default { background: #f3f4f6; color: #4b5563; }

.preregs-th-actions { text-align: right; width: 1%; }
.preregs-actions { text-align: right; white-space: nowrap; }
.preregs-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.15rem;
}
.preregs-icon-btn {
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
.preregs-icon-btn:hover { background: #f3f4f6; color: #111827; }
.preregs-icon-btn--view:hover { color: #1E4FA8; background: #eff6ff; }
.preregs-icon-btn--edit:hover { color: #a16207; background: #fef9c3; }
.preregs-icon-btn--accent:hover { color: #0A2D6F; background: #E8EEF8; }
.preregs-icon-btn--danger:hover { color: #be123c; background: #fff1f2; }
.preregs-form-inline { display: inline-flex; margin: 0; padding: 0; }
.preregs-sr-only {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}

.preregs-empty { text-align: center; padding: 3rem 1rem !important; background: #fff !important; }
.preregs-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.preregs-empty .preregs-btn { margin: 0 0.25rem; }

.preregs-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 0.15rem 0;
    font-size: 0.875rem;
    color: #6b7280;
}
.preregs-pagination-info {
    font-weight: 600;
    color: #4b5563;
}
.preregs-pagination-links { display: flex; align-items: center; }

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
    .preregs-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .preregs-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .preregs-field-search,
    .preregs-field-select,
    .preregs-field-date { flex: 1 1 100%; }
    .preregs-filters-actions { width: 100%; margin-left: 0; }
}
@media (max-width: 480px) {
    .preregs-stats { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
@php
    $preregsDisplayTz = config('app.display_timezone') ?: 'America/New_York';
@endphp
<div class="preregs-page">
    <x-module-banner section="General" current="Preregistros" title="Preregistros PrimeTrack" subtitle="Lista de preregistros en Miami. Crea nuevos, filtra por servicio, ingreso o estado.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6h9m-9 4.5h9m-9 4.5h5.25M5.25 3.75h13.5A1.5 1.5 0 0 1 20.25 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('preregistrations.quick-courier') }}" class="mb-btn mb-btn-secondary">Captura rápida Courier</a>
            <a href="{{ route('preregistrations.create') }}" class="mb-btn mb-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nuevo preregistro
            </a>
        </x-slot:actions>
    </x-module-banner>

    <div class="preregs-stats">
        <div class="preregs-stat-card preregs-stat-total">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
            </span>
            <div class="preregs-stat-body">
                <span class="preregs-stat-label">Total</span>
                <span class="preregs-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
            </div>
        </div>
        <div class="preregs-stat-card preregs-stat-air">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 14 8-3 3-8 2 2-2 7 7 2 2 2-8 1-2 4-2-2 1-4-7-1Z"/></svg>
            </span>
            <div class="preregs-stat-body">
                <span class="preregs-stat-label">Aéreo</span>
                <span class="preregs-stat-value">{{ number_format($statsAir ?? 0) }}</span>
            </div>
        </div>
        <div class="preregs-stat-card preregs-stat-sea">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 18s2.5 3 5 3 4-2 5-2 2.5 2 5 2 5-3 5-3M4 16V9h14v7M8 9l1.5-3h3L14 9"/></svg>
            </span>
            <div class="preregs-stat-body">
                <span class="preregs-stat-label">Marítimo</span>
                <span class="preregs-stat-value">{{ number_format($statsSea ?? 0) }}</span>
            </div>
        </div>
        <div class="preregs-stat-card preregs-stat-received">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <div class="preregs-stat-body">
                <span class="preregs-stat-label">Recibido Miami</span>
                <span class="preregs-stat-value">{{ number_format($statsReceived ?? 0) }}</span>
            </div>
        </div>
        <div class="preregs-stat-card preregs-stat-ready">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4 10-10"/></svg>
            </span>
            <div class="preregs-stat-body">
                <span class="preregs-stat-label">Listos</span>
                <span class="preregs-stat-value">{{ number_format($statsReady ?? 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Filtros horizontales --}}
    <div class="preregs-filters">
        <form method="GET" action="{{ route('preregistrations.index') }}" class="preregs-filters-form">
            <div class="preregs-field preregs-field-search">
                <label class="preregs-label">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tracking, código, nombre…" class="preregs-input">
            </div>
            <div class="preregs-field preregs-field-select">
                <label class="preregs-label">Servicio</label>
                <select name="service_type" class="preregs-select">
                    <option value="">Todos</option>
                    <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                    <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                    <option value="CFT" {{ request('service_type') == 'CFT' ? 'selected' : '' }}>Pie cúbico</option>
                </select>
            </div>
            <div class="preregs-field preregs-field-select">
                <label class="preregs-label">Ingreso</label>
                <select name="intake_type" class="preregs-select">
                    <option value="">Todos</option>
                    <option value="COURIER" {{ request('intake_type') == 'COURIER' ? 'selected' : '' }}>Courier</option>
                    <option value="DROP_OFF" {{ request('intake_type') == 'DROP_OFF' ? 'selected' : '' }}>Drop Off</option>
                </select>
            </div>
            <div class="preregs-field preregs-field-select">
                <label class="preregs-label">Estado</label>
                <select name="status" class="preregs-select">
                    <option value="">Todos</option>
                    <option value="PHOTO_PENDING" {{ request('status') == 'PHOTO_PENDING' ? 'selected' : '' }}>Pendiente por completar</option>
                    <option value="RECEIVED_MIAMI" {{ request('status') == 'RECEIVED_MIAMI' ? 'selected' : '' }}>Recibido Miami</option>
                    <option value="IN_TRANSIT" {{ request('status') == 'IN_TRANSIT' ? 'selected' : '' }}>En tránsito</option>
                    <option value="IN_WAREHOUSE_NIC" {{ request('status') == 'IN_WAREHOUSE_NIC' ? 'selected' : '' }}>En almacén NIC</option>
                    <option value="READY" {{ request('status') == 'READY' ? 'selected' : '' }}>Listo para retiro</option>
                    <option value="DELIVERED" {{ request('status') == 'DELIVERED' ? 'selected' : '' }}>Entregado</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="preregs-field preregs-field-select">
                <label class="preregs-label">Agencia</label>
                <select name="agency_id" class="preregs-select">
                    <option value="">Todas</option>
                    @foreach($agenciesForFilter ?? [] as $agencyOption)
                    <option value="{{ $agencyOption->id }}" {{ (int) request('agency_id') === (int) $agencyOption->id ? 'selected' : '' }}>{{ $agencyOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="preregs-field preregs-field-date">
                <label class="preregs-label">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="preregs-input">
            </div>
            <div class="preregs-field preregs-field-date">
                <label class="preregs-label">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="preregs-input">
            </div>
            <div class="preregs-filters-actions">
                <button type="submit" class="preregs-btn preregs-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3-3"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('preregistrations.index', ['clear_filters' => 1]) }}" class="preregs-btn preregs-btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Acciones --}}
    <div class="preregs-toolbar">
        <div class="preregs-toolbar-left">
            <span class="preregs-count">{{ $preregistrations->total() }} {{ $preregistrations->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="preregs-table-shell">
        <div class="preregs-table-wrap">
            <table class="preregs-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tracking</th>
                        <th>Fecha ingreso</th>
                        <th>Nombre</th>
                        <th>Agencia</th>
                        <th>Servicio</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th>Foto</th>
                        <th class="preregs-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preregistrations as $preregistration)
                    <tr class="preregs-clickable-row" data-href="{{ route('preregistrations.show', $preregistration->id) }}">
                        <td>
                            <span class="preregs-code" title="{{ $preregistration->warehouse_code ?? 'Sin código' }}">{{ $preregistration->warehouse_code ?? '—' }}</span>
                        </td>
                        <td>
                            @php $trk = trim((string) ($preregistration->tracking_external ?? '')); @endphp
                            <span class="preregs-tracking {{ $trk === '' ? 'preregs-tracking--empty' : '' }}" title="{{ $trk !== '' ? $trk : 'Sin tracking' }}">{{ $trk !== '' ? Str::limit($trk, 28) : '—' }}</span>
                        </td>
                        <td>
                            <span class="preregs-date">{{ $preregistration->created_at ? $preregistration->created_at->timezone($preregsDisplayTz)->format('d/m/Y H:i') : '—' }}</span>
                        </td>
                        <td>
                            <span class="preregs-name" title="{{ $preregistration->label_name }}">{{ $preregistration->label_name ? Str::limit($preregistration->label_name, 35) : '—' }}</span>
                        </td>
                        <td>
                            @if($preregistration->agency)
                            <span class="preregs-agency" title="{{ $preregistration->agency->name }}">{{ $preregistration->agency->code ? $preregistration->agency->code . ' - ' : '' }}{{ Str::limit($preregistration->agency->name, 22) }}</span>
                            @else
                            <span class="preregs-agency">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="preregs-badge preregs-badge-{{ strtolower($preregistration->service_type ?? '') }}">
                                {{ \App\Support\ServiceType::label($preregistration->service_type) }}
                            </span>
                        </td>
                        <td class="preregs-weight">{{ number_format($preregistration->intake_weight_lbs ?? 0, 2) }} <span class="preregs-uom">lb</span></td>
                        <td>
                            @php
                                $statusLabels = [
                                    'PHOTO_PENDING' => ['Pendiente datos', 'status-pending'],
                                    'RECEIVED_MIAMI' => ['Recibido Miami', 'status-info'],
                                    'IN_TRANSIT' => ['En tránsito', 'status-warning'],
                                    'IN_WAREHOUSE_NIC' => ['En almacén NIC', 'status-primary'],
                                    'READY' => ['Listo retiro', 'status-success'],
                                    'DELIVERED' => ['Entregado', 'status-delivered'],
                                    'CANCELLED' => ['Inactivo', 'status-danger'],
                                ];
                                $sl = $statusLabels[$preregistration->status ?? ''] ?? [$preregistration->status ?? '—', 'status-default'];
                            @endphp
                            <span class="preregs-badge preregs-status {{ $sl[1] }}">{{ $sl[0] }}</span>
                        </td>
                        <td>
                            @if($preregistration->photos->count() > 0)
                            <span class="preregs-photo-yes" title="Tiene foto">✓</span>
                            @else
                            <span class="preregs-photo-no">—</span>
                            @endif
                        </td>
                        <td class="preregs-actions">
                            <div class="preregs-action-group" role="group" aria-label="Acciones">
                                <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="preregs-icon-btn preregs-icon-btn--edit" title="Editar" aria-label="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-icon-btn preregs-icon-btn--view" title="Ver detalle" aria-label="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if($preregistration->warehouse_code)
                                <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="preregs-icon-btn preregs-icon-btn--accent" title="Etiqueta" aria-label="Abrir etiqueta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </a>
                                @endif
                                @if(in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED']))
                                <form action="{{ route('preregistrations.destroy', $preregistration->id) }}" method="POST" class="preregs-form-inline" onsubmit="return confirm('¿Eliminar este preregistro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="preregs-icon-btn preregs-icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="preregs-empty">
                            <p class="preregs-empty-text">No hay preregistros con los filtros actuales.</p>
                            <a href="{{ route('preregistrations.create') }}" class="preregs-btn preregs-btn-primary">Crear preregistro</a>
                            <a href="{{ route('preregistrations.index', ['clear_filters' => 1]) }}" class="preregs-btn preregs-btn-secondary">Ver todos</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($preregistrations->hasPages())
        <div class="preregs-footer">
            <span class="preregs-pagination-info">
                {{ $preregistrations->firstItem() }} – {{ $preregistrations->lastItem() }} de {{ $preregistrations->total() }}
            </span>
            <div class="preregs-pagination-links">{{ $preregistrations->links('vendor.pagination.primetrack') }}</div>
        </div>
        @endif
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.preregs-clickable-row').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, form, label')) return;
            const href = row.getAttribute('data-href');
            if (href) window.location.href = href;
        });
    });
});
</script>
@endsection
