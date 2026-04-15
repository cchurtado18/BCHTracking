@extends('layouts.app')

@section('title', 'Preregistros')

@section('content')
@php
    $preregsDisplayTz = config('app.display_timezone') ?: 'America/New_York';
@endphp
<div class="preregs-page">
    {{-- Hero --}}
    <header class="preregs-hero">
        <div class="preregs-hero-inner">
            <div class="preregs-hero-text">
                <h1 class="preregs-hero-title">Preregistros</h1>
                <p class="preregs-hero-subtitle">Lista de preregistros en Miami. Crea nuevos, filtra por servicio, ingreso o estado.</p>
            </div>
            <div class="preregs-hero-actions">
                <a href="{{ route('preregistrations.quick-courier') }}" class="preregs-hero-btn preregs-hero-btn-secondary">
                    Captura rápida Courier
                </a>
                <a href="{{ route('preregistrations.create') }}" class="preregs-hero-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    Nuevo preregistro
                </a>
            </div>
        </div>
    </header>

    {{-- Tarjetas de resumen --}}
    <div class="preregs-stats">
        <div class="preregs-stat-card preregs-stat-total">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.5 12 13 3 8.5M12 13v8M4.2 7.8 12 3l7.8 4.8A2 2 0 0 1 21 9.5v8.9a2 2 0 0 1-1 1.73l-7 4.02a2 2 0 0 1-2 0l-7-4.02a2 2 0 0 1-1-1.73V9.5a2 2 0 0 1 1.2-1.7Z"/></svg>
            </span>
            <span class="preregs-stat-label">Total</span>
            <span class="preregs-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-air">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3 14 8-3 3-8 2 2-2 7 7 2 2 2-8 1-2 4-2-2 1-4-7-1Z"/></svg>
            </span>
            <span class="preregs-stat-label">Aéreo</span>
            <span class="preregs-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-sea">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 18s2.5 3 5 3 4-2 5-2 2.5 2 5 2 5-3 5-3M4 16V9h14v7M8 9l1.5-3h3L14 9"/></svg>
            </span>
            <span class="preregs-stat-label">Marítimo</span>
            <span class="preregs-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-received">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s7-5.8 7-12a7 7 0 1 0-14 0c0 6.2 7 12 7 12Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </span>
            <span class="preregs-stat-label">Recibido Miami</span>
            <span class="preregs-stat-value">{{ number_format($statsReceived ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-ready">
            <span class="preregs-stat-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4 10-10"/></svg>
            </span>
            <span class="preregs-stat-label">Listos</span>
            <span class="preregs-stat-value">{{ number_format($statsReady ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="preregs-card preregs-filters-card">
        <div class="preregs-card-header">
            <h2 class="preregs-card-title">Filtros</h2>
        </div>
        <div class="preregs-card-body">
            <form method="GET" action="{{ route('preregistrations.index') }}" class="preregs-filters-form">
                <div class="preregs-filters-grid">
                    <div class="preregs-field preregs-field-search">
                        <label class="preregs-label">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tracking, código, nombre" class="preregs-input">
                    </div>
                    <div class="preregs-field">
                        <label class="preregs-label">Servicio</label>
                        <select name="service_type" class="preregs-select">
                            <option value="">Todos</option>
                            <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                    </div>
                    <div class="preregs-field">
                        <label class="preregs-label">Ingreso</label>
                        <select name="intake_type" class="preregs-select">
                            <option value="">Todos</option>
                            <option value="COURIER" {{ request('intake_type') == 'COURIER' ? 'selected' : '' }}>Courier</option>
                            <option value="DROP_OFF" {{ request('intake_type') == 'DROP_OFF' ? 'selected' : '' }}>Drop Off</option>
                        </select>
                    </div>
                    <div class="preregs-field">
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
                    <div class="preregs-field">
                        <label class="preregs-label">Desde</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="preregs-input">
                    </div>
                    <div class="preregs-field">
                        <label class="preregs-label">Hasta</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="preregs-input">
                    </div>
                </div>
                <div class="preregs-filters-actions">
                    <button type="submit" class="preregs-btn preregs-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('preregistrations.index', ['clear_filters' => 1]) }}" class="preregs-btn preregs-btn-ghost">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de preregistros --}}
    <div class="preregs-card preregs-table-card">
        <div class="preregs-card-header preregs-table-header">
            <h2 class="preregs-card-title">Listado de preregistros</h2>
            <span class="preregs-card-badge">{{ $preregistrations->total() }} {{ $preregistrations->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="preregs-table-wrap">
            <table class="preregs-table">
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
                        <th>Foto</th>
                        <th class="preregs-th-actions"><span class="preregs-sr-only">Acciones</span></th>
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
                            <span class="preregs-tracking" title="{{ $trk !== '' ? $trk : 'Sin tracking' }}">{{ $trk !== '' ? Str::limit($trk, 28) : '—' }}</span>
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
                                {{ $preregistration->service_type == 'AIR' ? 'Aéreo' : ($preregistration->service_type == 'SEA' ? 'Marítimo' : ($preregistration->service_type ?? '—')) }}
                            </span>
                        </td>
                        <td class="preregs-weight">{{ number_format($preregistration->intake_weight_lbs ?? 0, 2) }} <span class="preregs-uom">lbs</span></td>
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
                                <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-icon-btn preregs-icon-btn--view" title="Ver detalle" aria-label="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                @if($preregistration->warehouse_code)
                                <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="preregs-icon-btn preregs-icon-btn--accent" title="Etiqueta" aria-label="Abrir etiqueta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </a>
                                @endif
                                <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="preregs-icon-btn preregs-icon-btn--edit" title="Editar" aria-label="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
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
        <div class="preregs-card-footer">
            <span class="preregs-pagination-info">
                {{ $preregistrations->firstItem() }} – {{ $preregistrations->lastItem() }} de {{ $preregistrations->total() }}
            </span>
            <div class="preregs-pagination-links">{{ $preregistrations->links() }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.preregs-page { padding: 1.25rem 0 2rem; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #11695f 100%);
    border-radius: 0.875rem;
    padding: 1rem 1.2rem;
    margin-bottom: 1.35rem;
    box-shadow: 0 6px 16px rgba(15, 118, 110, 0.14);
}
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-title { margin: 0; font-size: 1.8rem; font-weight: 700; color: #ffffff; letter-spacing: -0.02em; }
.preregs-hero-subtitle { margin: 0.25rem 0 0; font-size: 0.9rem; font-weight: 400; color: rgba(236, 253, 245, 0.94); max-width: 54ch; }
.preregs-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
.preregs-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.52rem 0.96rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #ffffff;
    background: #059669;
    border-radius: 0.625rem;
    border: 1px solid rgba(15, 118, 110, 0.45);
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(2, 44, 34, 0.2);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    white-space: nowrap;
}
.preregs-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 16px rgba(2, 44, 34, 0.24); color: #ffffff; background: #047857; border-color: #047857; }
.preregs-hero-btn.preregs-hero-btn-secondary {
    background: rgba(255, 255, 255, 0.06);
    color: #ecfdf5;
    border-color: rgba(236, 253, 245, 0.8);
    box-shadow: none;
}
.preregs-hero-btn.preregs-hero-btn-secondary:hover {
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    border-color: #ffffff;
}

/* Stats */
.preregs-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.4rem; }
.preregs-stat-card {
    position: relative;
    background: #ffffff;
    border-radius: 0.75rem;
    padding: 1.02rem 1rem 1.12rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    display: flex;
    flex-direction: column;
    gap: 0.28rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.preregs-stat-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    border-radius: 0.75rem 0 0 0.75rem;
    background: #cbd5e1;
}
.preregs-stat-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}
.preregs-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 9999px;
    font-size: 0.95rem;
    background: #f8fafc;
}
.preregs-stat-label { font-size: 0.73rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
.preregs-stat-value { font-size: 1.78rem; line-height: 1.05; font-weight: 800; color: #0f172a; }
.preregs-stat-total::before { background: #0d9488; }
.preregs-stat-air::before { background: #2563eb; }
.preregs-stat-sea::before { background: #059669; }
.preregs-stat-received::before { background: #0284c7; }
.preregs-stat-ready::before { background: #16a34a; }
.preregs-stat-total .preregs-stat-icon { color: #0f766e; }
.preregs-stat-air .preregs-stat-icon { color: #1d4ed8; }
.preregs-stat-sea .preregs-stat-icon { color: #047857; }
.preregs-stat-received .preregs-stat-icon { color: #0369a1; }
.preregs-stat-ready .preregs-stat-icon { color: #15803d; }

/* Card */
.preregs-card { background: #ffffff; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); margin-bottom: 1.5rem; overflow: hidden; }
.preregs-card-header { padding: 1rem 1.2rem; border-bottom: 1px solid #edf2f7; background: #fbfcfe; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.preregs-card-title { margin: 0; font-size: 0.94rem; font-weight: 600; color: #334155; }
.preregs-card-badge { font-size: 0.8125rem; color: #64748b; font-weight: 500; }
.preregs-card-body { padding: 1.2rem; }
.preregs-card-footer { padding: 0.85rem 1.2rem; border-top: 1px solid #edf2f7; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #64748b; }

/* Filters */
.preregs-filters-form { display: flex; flex-direction: column; gap: 0.9rem; }
.preregs-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.88rem; }
.preregs-field-search { grid-column: 1 / -1; }
@media (min-width: 640px) { .preregs-field-search { grid-column: span 2; max-width: 340px; } }
.preregs-label { display: block; font-size: 0.74rem; font-weight: 500; color: #64748b; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
.preregs-input, .preregs-select {
    display: block; width: 100%; padding: 0.58rem 0.82rem; font-size: 0.875rem; border: 1px solid #dbe3ec; border-radius: 0.625rem;
    background: #ffffff; color: #0f172a;
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
.preregs-input:hover, .preregs-select:hover { border-color: #c7d2e0; background: #fcfdff; }
.preregs-input:focus, .preregs-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14); }
.preregs-filters-actions { display: flex; flex-wrap: wrap; gap: 0.72rem; align-items: center; margin-top: 0.15rem; }
.preregs-filters-card { border-color: #e5eaf1; }

/* Buttons */
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.56rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.625rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease; }
.preregs-btn-primary { background: #0f766e; color: #fff; border-color: #0f766e; box-shadow: 0 3px 10px rgba(15, 118, 110, 0.24); font-weight: 600; }
.preregs-btn-primary:hover { background: #115e59; border-color: #115e59; color: #fff; box-shadow: 0 8px 18px rgba(15, 118, 110, 0.26); }
.preregs-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.preregs-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.preregs-btn-success { background: #059669; color: #fff; border-color: #059669; }
.preregs-btn-success:hover { background: #047857; color: #fff; }
.preregs-btn-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.preregs-btn-danger:hover { background: #fef2f2; color: #b91c1c; }
.preregs-btn-ghost { background: transparent; color: #64748b; border-color: transparent; padding-left: 0.55rem; padding-right: 0.55rem; }
.preregs-btn-ghost:hover { background: #f1f5f9; color: #334155; border-color: #e2e8f0; }
.preregs-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.preregs-form-inline {
    display: inline-flex;
    margin: 0;
    padding: 0;
    vertical-align: middle;
    align-items: center;
}
.preregs-form-inline button { margin: 0; }

.preregs-tracking {
    display: block;
    max-width: 11.5rem;
    font-size: 0.8125rem;
    color: #334155;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.preregs-date {
    font-size: 0.8125rem;
    color: #4b5563;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.preregs-action-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.34rem;
    flex-wrap: nowrap;
}
.preregs-icon-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.05rem;
    height: 2.05rem;
    padding: 0;
    border: 1px solid #dbe3ec;
    border-radius: 9999px;
    background: #ffffff;
    color: #475569;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}
.preregs-icon-btn:hover {
    transform: translateY(-1px);
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.1);
}
.preregs-icon-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.22);
    border-color: #0d9488;
}
.preregs-icon-btn--view:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #1d4ed8;
}
.preregs-icon-btn--edit:hover {
    background: #fef9c3;
    border-color: #fcd34d;
    color: #a16207;
}
.preregs-icon-btn--accent {
    border-color: rgba(13, 148, 136, 0.28);
    color: #0f766e;
    background: #f0fdfa;
}
.preregs-icon-btn--accent:hover {
    background: #ccfbf1;
    border-color: #0d9488;
    color: #0f766e;
}
.preregs-icon-btn--danger {
    border-color: #fecdd3;
    color: #e11d48;
}
.preregs-icon-btn--danger:hover {
    background: #fff1f2;
    border-color: #fda4af;
    color: #be123c;
}
.preregs-sr-only {
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
.preregs-table-card {
    border: 1px solid #dbe3ee;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}
.preregs-table-header { background: #ffffff; }
.preregs-table-header .preregs-card-title { color: #0f172a; font-size: 1rem; }
.preregs-table-header .preregs-card-badge { color: #475569; font-weight: 600; }
.preregs-table-wrap { overflow-x: auto; }
.preregs-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.preregs-table th { text-align: left; padding: 0.88rem 1rem; font-weight: 600; font-size: 0.69rem; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid #dbe4ef; white-space: nowrap; }
.preregs-table td { padding: 1rem 1rem; border-bottom: 1px solid #e4ebf3; vertical-align: middle; color: #1e293b; }
.preregs-table tbody tr:nth-child(even) { background: #f8fbff; }
.preregs-table tbody tr:hover { background: #eef6ff; }
.preregs-clickable-row { cursor: pointer; transition: background 0.2s ease; }
.preregs-code { font-family: ui-monospace, monospace; font-weight: 600; color: #0f172a; }
.preregs-name { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preregs-agency { display: block; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #64748b; }
.preregs-weight { color: #334155; }
.preregs-uom { font-size: 0.75rem; color: #94a3b8; }
.preregs-photo-yes { color: #059669; font-weight: 600; }
.preregs-photo-no { color: #94a3b8; }
.preregs-th-actions { text-align: right; width: 1%; }
.preregs-actions { text-align: right; white-space: nowrap; vertical-align: middle; }
.preregs-actions .preregs-btn { margin-left: 0.25rem; }
.preregs-badge { display: inline-block; padding: 0.28rem 0.65rem; font-size: 0.74rem; font-weight: 700; border-radius: 9999px; border: 1px solid transparent; }
.preregs-badge-air { background: #dbeafe; color: #1e40af; }
.preregs-badge-sea { background: #dcfce7; color: #166534; }
.preregs-status { }
.preregs-status.status-pending { background: #eef2ff; color: #3730a3; border-color: #c7d2fe; }
.preregs-status.status-info { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
.preregs-status.status-warning { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.preregs-status.status-primary { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
.preregs-status.status-success { background: #dcfce7; color: #166534; border-color: #86efac; }
.preregs-status.status-delivered { background: #e2e8f0; color: #334155; border-color: #cbd5e1; }
.preregs-status.status-danger { background: #ffe4e6; color: #be123c; border-color: #fda4af; }
.preregs-status.status-default { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
.preregs-empty { text-align: center; padding: 3rem 1rem !important; }
.preregs-empty-text { margin: 0 0 0.75rem; color: #64748b; }
.preregs-empty .preregs-btn { margin: 0 0.25rem; }
.preregs-pagination-info { font-weight: 500; }
.preregs-pagination-links { display: flex; align-items: center; }
.preregs-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.preregs-pagination-links a, .preregs-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.45rem; border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none; }
.preregs-pagination-links a:hover { background: #f8fafc; color: #0d9488; }
.preregs-pagination-links .disabled span { background: #f8fafc; color: #94a3b8; }
.preregs-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
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
