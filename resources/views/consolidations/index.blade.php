@extends('layouts.app')

@section('title', 'Consolidaciones')

@section('content')
<div class="conso-page">
    {{-- Hero --}}
    <header class="conso-hero">
        <div class="conso-hero-inner">
            <div class="conso-hero-text">
                <h1 class="conso-hero-title">Consolidaciones (Sacos)</h1>
                <p class="conso-hero-subtitle">Lista de sacos consolidados. Crea nuevos sacos, filtra por estado o servicio.</p>
            </div>
            <a href="{{ route('consolidations.create') }}" class="conso-hero-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                Nuevo saco
            </a>
        </div>
    </header>

    {{-- Tarjetas de resumen --}}
    <div class="conso-stats">
        <div class="conso-stat-card conso-stat-total">
            <span class="conso-stat-label">Total</span>
            <span class="conso-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="conso-stat-card conso-stat-open">
            <span class="conso-stat-label">Abiertos</span>
            <span class="conso-stat-value">{{ number_format($statsOpen ?? 0) }}</span>
        </div>
        <div class="conso-stat-card conso-stat-sent">
            <span class="conso-stat-label">Enviados</span>
            <span class="conso-stat-value">{{ number_format($statsSent ?? 0) }}</span>
        </div>
        <div class="conso-stat-card conso-stat-received">
            <span class="conso-stat-label">Recibidos</span>
            <span class="conso-stat-value">{{ number_format($statsReceived ?? 0) }}</span>
        </div>
        <div class="conso-stat-card conso-stat-air">
            <span class="conso-stat-label">Aéreo</span>
            <span class="conso-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="conso-stat-card conso-stat-sea">
            <span class="conso-stat-label">Marítimo</span>
            <span class="conso-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="conso-card conso-filters-card">
        <div class="conso-card-header">
            <h2 class="conso-card-title">Filtros</h2>
        </div>
        <div class="conso-card-body">
            <form method="GET" action="{{ route('consolidations.index') }}" class="conso-filters-form">
                <div class="conso-filters-grid">
                    <div class="conso-field">
                        <label class="conso-label">Estado</label>
                        <select name="status" class="conso-select">
                            <option value="">Todos</option>
                            <option value="OPEN" {{ request('status') == 'OPEN' ? 'selected' : '' }}>Abierto</option>
                            <option value="SENT" {{ request('status') == 'SENT' ? 'selected' : '' }}>Enviado</option>
                            <option value="RECEIVED" {{ request('status') == 'RECEIVED' ? 'selected' : '' }}>Recibido</option>
                            <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="conso-field">
                        <label class="conso-label">Servicio</label>
                        <select name="service_type" class="conso-select">
                            <option value="">Todos</option>
                            <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                    </div>
                </div>
                <div class="conso-filters-actions">
                    <button type="submit" class="conso-btn conso-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('consolidations.index', ['clear_filters' => 1]) }}" class="conso-btn conso-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de consolidaciones --}}
    <div class="conso-card conso-table-card">
        <div class="conso-card-header conso-table-header">
            <h2 class="conso-card-title">Listado de consolidaciones</h2>
            <span class="conso-card-badge">{{ $consolidations->total() }} {{ $consolidations->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="conso-table-wrap">
            <table class="conso-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Items</th>
                        <th>Fecha</th>
                        <th class="conso-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidations as $consolidation)
                    <tr>
                        <td>
                            <span class="conso-code">{{ $consolidation->code }}</span>
                        </td>
                        <td>
                            <span class="conso-badge conso-badge-{{ strtolower($consolidation->service_type ?? '') }}">
                                {{ $consolidation->service_type == 'AIR' ? 'Aéreo' : ($consolidation->service_type == 'SEA' ? 'Marítimo' : ($consolidation->service_type ?? '—')) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusLabels = [
                                    'OPEN' => ['Abierto', 'status-open'],
                                    'SENT' => ['Enviado', 'status-sent'],
                                    'RECEIVED' => ['Recibido', 'status-success'],
                                    'CANCELLED' => ['Cancelado', 'status-danger'],
                                ];
                                $sl = $statusLabels[$consolidation->status ?? ''] ?? [$consolidation->status ?? '—', 'status-default'];
                            @endphp
                            <span class="conso-badge conso-status {{ $sl[1] }}">{{ $sl[0] }}</span>
                        </td>
                        <td class="conso-items">{{ $consolidation->items_count }} <span class="conso-uom">items</span>@if($consolidation->items_count == 1)<span class="conso-badge conso-badge-unit">1 caja</span>@endif</td>
                        <td class="conso-date">{{ $consolidation->created_at->format('d/m/Y') }}</td>
                        <td class="conso-actions">
                            <a href="{{ route('consolidations.show', $consolidation->id) }}" class="conso-btn conso-btn-sm conso-btn-outline-primary">Ver</a>
                            <a href="{{ route('consolidations.edit', $consolidation->id) }}" class="conso-btn conso-btn-sm conso-btn-secondary">Editar</a>
                            @if($consolidation->status === 'OPEN')
                            <form action="{{ route('consolidations.destroy', $consolidation->id) }}" method="POST" class="conso-form-inline" onsubmit="return confirm('¿Eliminar este saco? Se quitarán los items y los preregistros quedarán disponibles de nuevo.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="conso-btn conso-btn-sm conso-btn-danger">Eliminar</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="conso-empty">
                            <p class="conso-empty-text">No hay consolidaciones con los filtros actuales.</p>
                            <a href="{{ route('consolidations.create') }}" class="conso-btn conso-btn-primary">Crear saco</a>
                            <a href="{{ route('consolidations.index', ['clear_filters' => 1]) }}" class="conso-btn conso-btn-secondary">Ver todos</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consolidations->hasPages())
        <div class="conso-card-footer">
            <span class="conso-pagination-info">
                {{ $consolidations->firstItem() }} – {{ $consolidations->lastItem() }} de {{ $consolidations->total() }}
            </span>
            <div class="conso-pagination-links">{{ $consolidations->links() }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.conso-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.conso-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.conso-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.conso-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.conso-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 42ch; }
.conso-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    color: #0f766e; background: #fff; border: none; border-radius: 0.5rem; text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.15s, box-shadow 0.15s;
}
.conso-hero-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #0d9488; }

/* Stats */
.conso-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.conso-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.conso-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.conso-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.conso-stat-total { border-left: 4px solid #0d9488; }
.conso-stat-open { border-left: 4px solid #22c55e; }
.conso-stat-sent { border-left: 4px solid #f59e0b; }
.conso-stat-received { border-left: 4px solid #0ea5e9; }
.conso-stat-air { border-left: 4px solid #3b82f6; }
.conso-stat-sea { border-left: 4px solid #059669; }

/* Card */
.conso-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.conso-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.conso-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.conso-card-badge { font-size: 0.8125rem; color: #6b7280; font-weight: 500; }
.conso-card-body { padding: 1.25rem; }
.conso-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

/* Filters */
.conso-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.conso-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.conso-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.conso-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.conso-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.conso-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Buttons */
.conso-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.conso-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.conso-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.conso-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.conso-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.conso-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.conso-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.conso-btn-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.conso-btn-danger:hover { background: #fef2f2; color: #b91c1c; }
.conso-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.conso-form-inline { display: inline; }
.conso-form-inline button { margin-left: 0.25rem; }

/* Table */
.conso-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.conso-table-header .conso-card-title { color: #fff; }
.conso-table-header .conso-card-badge { color: rgba(255,255,255,0.9); }
.conso-table-wrap { overflow-x: auto; }
.conso-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.conso-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.conso-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.conso-table tbody tr:hover { background: #f9fafb; }
.conso-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.conso-items, .conso-date { color: #374151; }
.conso-uom { font-size: 0.75rem; color: #9ca3af; }
.conso-th-actions { text-align: right; }
.conso-actions { text-align: right; white-space: nowrap; }
.conso-actions .conso-btn { margin-left: 0.25rem; }
.conso-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.conso-badge-air { background: #dbeafe; color: #1d4ed8; }
.conso-badge-sea { background: #d1fae5; color: #047857; }
.conso-badge-unit { background: #e0f2fe; color: #0369a1; margin-left: 0.35rem; }
.conso-status { }
.conso-status.status-open { background: #d1fae5; color: #047857; }
.conso-status.status-sent { background: #fef3c7; color: #b45309; }
.conso-status.status-success { background: #dbeafe; color: #0369a1; }
.conso-status.status-danger { background: #fee2e2; color: #b91c1c; }
.conso-status.status-default { background: #f3f4f6; color: #6b7280; }
.conso-empty { text-align: center; padding: 3rem 1rem !important; }
.conso-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.conso-empty .conso-btn { margin: 0 0.25rem; }
.conso-pagination-info { font-weight: 500; }
.conso-pagination-links { display: flex; align-items: center; }
.conso-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.conso-pagination-links a, .conso-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.conso-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.conso-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.conso-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
