@extends('layouts.app')

@section('title', 'Preregistros')

@section('content')
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
            <span class="preregs-stat-label">Total</span>
            <span class="preregs-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-air">
            <span class="preregs-stat-label">Aéreo</span>
            <span class="preregs-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-sea">
            <span class="preregs-stat-label">Marítimo</span>
            <span class="preregs-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-received">
            <span class="preregs-stat-label">Recibido Miami</span>
            <span class="preregs-stat-value">{{ number_format($statsReceived ?? 0) }}</span>
        </div>
        <div class="preregs-stat-card preregs-stat-ready">
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
                    <a href="{{ route('preregistrations.index', ['clear_filters' => 1]) }}" class="preregs-btn preregs-btn-secondary">Limpiar</a>
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
                        <th>Código / Tracking</th>
                        <th>Nombre (etiqueta)</th>
                        <th>Agencia</th>
                        <th>Servicio</th>
                        <th>Peso</th>
                        <th>Estado</th>
                        <th>Foto</th>
                        <th class="preregs-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preregistrations as $preregistration)
                    <tr>
                        <td>
                            <span class="preregs-code" title="{{ $preregistration->warehouse_code ?? $preregistration->tracking_external }}">
                                {{ $preregistration->warehouse_code ?? $preregistration->tracking_external ?? '—' }}
                            </span>
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
                            <a href="{{ route('preregistrations.show', $preregistration->id) }}" class="preregs-btn preregs-btn-sm preregs-btn-outline-primary">Ver</a>
                            @if($preregistration->warehouse_code)
                            <a href="{{ route('preregistrations.label', $preregistration->id) }}" target="_blank" class="preregs-btn preregs-btn-sm preregs-btn-success">Etiqueta</a>
                            @endif
                            <a href="{{ route('preregistrations.edit', $preregistration->id) }}" class="preregs-btn preregs-btn-sm preregs-btn-secondary">Editar</a>
                            @if(in_array($preregistration->status, ['RECEIVED_MIAMI', 'CANCELLED']))
                            <form action="{{ route('preregistrations.destroy', $preregistration->id) }}" method="POST" class="preregs-form-inline" onsubmit="return confirm('¿Eliminar este preregistro?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="preregs-btn preregs-btn-sm preregs-btn-danger">Eliminar</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="preregs-empty">
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
.preregs-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.preregs-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.preregs-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.preregs-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.preregs-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 42ch; }
.preregs-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
.preregs-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f766e;
    background: #fff;
    border-radius: 0.5rem;
    border: 1px solid rgba(255,255,255,0.7);
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.15s, box-shadow 0.15s, background 0.15s, color 0.15s;
    white-space: nowrap;
}
.preregs-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #0d9488; background: #f9fafb; }
.preregs-hero-btn.preregs-hero-btn-secondary {
    background: #fff;
    color: #0f766e;
    border-color: rgba(255,255,255,0.9);
}
.preregs-hero-btn.preregs-hero-btn-secondary:hover {
    background: #f9fafb;
    color: #0d9488;
}

/* Stats */
.preregs-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.preregs-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.preregs-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.preregs-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.preregs-stat-total { border-left: 4px solid #0d9488; }
.preregs-stat-air { border-left: 4px solid #3b82f6; }
.preregs-stat-sea { border-left: 4px solid #059669; }
.preregs-stat-received { border-left: 4px solid #0ea5e9; }
.preregs-stat-ready { border-left: 4px solid #22c55e; }

/* Card */
.preregs-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.preregs-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.preregs-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.preregs-card-badge { font-size: 0.8125rem; color: #6b7280; font-weight: 500; }
.preregs-card-body { padding: 1.25rem; }
.preregs-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

/* Filters */
.preregs-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.preregs-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.preregs-field-search { grid-column: 1 / -1; }
@media (min-width: 640px) { .preregs-field-search { grid-column: span 2; max-width: 280px; } }
.preregs-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.preregs-input, .preregs-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.preregs-input:focus, .preregs-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.preregs-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Buttons */
.preregs-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.preregs-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.preregs-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.preregs-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.preregs-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.preregs-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.preregs-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.preregs-btn-success { background: #059669; color: #fff; border-color: #059669; }
.preregs-btn-success:hover { background: #047857; color: #fff; }
.preregs-btn-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.preregs-btn-danger:hover { background: #fef2f2; color: #b91c1c; }
.preregs-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.preregs-form-inline { display: inline; }
.preregs-form-inline button { margin-left: 0.25rem; }

/* Table */
.preregs-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.preregs-table-header .preregs-card-title { color: #fff; }
.preregs-table-header .preregs-card-badge { color: rgba(255,255,255,0.9); }
.preregs-table-wrap { overflow-x: auto; }
.preregs-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.preregs-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.preregs-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.preregs-table tbody tr:hover { background: #f9fafb; }
.preregs-code { font-family: ui-monospace, monospace; font-weight: 500; color: #111827; }
.preregs-name { display: block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preregs-agency { display: block; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6b7280; }
.preregs-weight { color: #374151; }
.preregs-uom { font-size: 0.75rem; color: #9ca3af; }
.preregs-photo-yes { color: #059669; font-weight: 600; }
.preregs-photo-no { color: #9ca3af; }
.preregs-th-actions { text-align: right; }
.preregs-actions { text-align: right; white-space: nowrap; }
.preregs-actions .preregs-btn { margin-left: 0.25rem; }
.preregs-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.preregs-badge-air { background: #dbeafe; color: #1d4ed8; }
.preregs-badge-sea { background: #d1fae5; color: #047857; }
.preregs-status { }
.preregs-status.status-pending { background: #f3f4ff; color: #4338ca; }
.preregs-status.status-info { background: #e0f2fe; color: #0369a1; }
.preregs-status.status-warning { background: #fef3c7; color: #b45309; }
.preregs-status.status-primary { background: #e0e7ff; color: #4338ca; }
.preregs-status.status-success { background: #d1fae5; color: #047857; }
.preregs-status.status-delivered { background: #e5e7eb; color: #4b5563; }
.preregs-status.status-danger { background: #fee2e2; color: #b91c1c; }
.preregs-status.status-default { background: #f3f4f6; color: #6b7280; }
.preregs-empty { text-align: center; padding: 3rem 1rem !important; }
.preregs-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.preregs-empty .preregs-btn { margin: 0 0.25rem; }
.preregs-pagination-info { font-weight: 500; }
.preregs-pagination-links { display: flex; align-items: center; }
.preregs-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.preregs-pagination-links a, .preregs-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.preregs-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.preregs-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.preregs-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
