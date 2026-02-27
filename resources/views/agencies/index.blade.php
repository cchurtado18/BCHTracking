@extends('layouts.app')

@section('title', 'Agencias')

@section('content')
<div class="agency-page">
    {{-- Hero --}}
    <header class="agency-hero">
        <div class="agency-hero-inner">
            <div class="agency-hero-text">
                <h1 class="agency-hero-title">Agencias</h1>
                <p class="agency-hero-subtitle">Gestión de agencias B2B. Subagencias de SkyLink One y CH LOGISTICS.</p>
            </div>
            <a href="{{ route('agencies.create') }}" class="agency-hero-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                Nueva Agencia
            </a>
        </div>
    </header>

    @if(session('error'))
    <div class="agency-alert agency-alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="agency-alert agency-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Tarjetas de resumen --}}
    <div class="agency-stats">
        <div class="agency-stat-card agency-stat-total">
            <span class="agency-stat-label">Total</span>
            <span class="agency-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="agency-stat-card agency-stat-active">
            <span class="agency-stat-label">Activas</span>
            <span class="agency-stat-value">{{ number_format($statsActive ?? 0) }}</span>
        </div>
        <div class="agency-stat-card agency-stat-inactive">
            <span class="agency-stat-label">Inactivas</span>
            <span class="agency-stat-value">{{ number_format($statsInactive ?? 0) }}</span>
        </div>
        <div class="agency-stat-card agency-stat-sub">
            <span class="agency-stat-label">Subagencias</span>
            <span class="agency-stat-value">{{ number_format($statsSubagencies ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="agency-card agency-filters-card">
        <div class="agency-card-header">
            <h2 class="agency-card-title">Filtros</h2>
        </div>
        <div class="agency-card-body">
            <form method="GET" action="{{ route('agencies.index') }}" class="agency-filters-form">
                <div class="agency-filters-grid">
                    <div class="agency-field">
                        <label class="agency-label">Estado</label>
                        <select name="is_active" class="agency-select">
                            <option value="">Todos</option>
                            <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                    <div class="agency-field agency-field-search">
                        <label class="agency-label">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre o código" class="agency-input">
                    </div>
                </div>
                <div class="agency-filters-actions">
                    <button type="submit" class="agency-btn agency-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('agencies.index') }}" class="agency-btn agency-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="agency-card agency-table-card">
        <div class="agency-card-header agency-table-header">
            <h2 class="agency-card-title">Listado de agencias</h2>
            <span class="agency-card-badge">{{ $agencies->total() }} {{ $agencies->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="agency-table-wrap">
            <table class="agency-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Agencia principal</th>
                        <th>Teléfono</th>
                        <th>Clientes</th>
                        <th>Estado</th>
                        <th class="agency-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agencies as $agency)
                    <tr>
                        <td><span class="agency-code">{{ $agency->code }}</span></td>
                        <td class="agency-name-cell" title="{{ $agency->name }}">{{ $agency->name }}</td>
                        <td class="agency-muted">{{ $agency->is_main ? '—' : ($agency->parent->name ?? '—') }}</td>
                        <td class="agency-muted">{{ $agency->phone ?? '—' }}</td>
                        <td class="agency-num">{{ $agency->clients_count }}</td>
                        <td>
                            @if($agency->is_active)
                            <span class="agency-badge agency-badge-success">Activa</span>
                            @else
                            <span class="agency-badge agency-badge-danger">Inactiva</span>
                            @endif
                        </td>
                        <td class="agency-actions">
                            <a href="{{ route('agencies.show', $agency->id) }}" class="agency-btn agency-btn-sm agency-btn-outline-primary">Ver</a>
                            <a href="{{ route('agencies.edit', $agency->id) }}" class="agency-btn agency-btn-sm agency-btn-outline-secondary">Editar</a>
                            @if(!$agency->is_main)
                                @if(($agency->preregistrations_count ?? 0) > 0)
                                <span class="agency-btn agency-btn-sm agency-btn-disabled" title="No se puede eliminar: tiene {{ $agency->preregistrations_count }} paquete(s) asignado(s).">Eliminar</span>
                                @else
                                <form action="{{ route('agencies.destroy', $agency->id) }}" method="POST" class="agency-form-inline" onsubmit="return confirm('¿Eliminar la subagencia «{{ addslashes($agency->name) }}»? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="agency-btn agency-btn-sm agency-btn-outline-danger">Eliminar</button>
                                </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="agency-empty">
                            <p class="agency-empty-text">No hay agencias.</p>
                            <a href="{{ route('agencies.create') }}" class="agency-btn agency-btn-primary">Crear una</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agencies->total() > 0)
        <div class="agency-card-footer">
            <span class="agency-pagination-info">
                {{ $agencies->firstItem() }} – {{ $agencies->lastItem() }} de {{ $agencies->total() }}
            </span>
            @if($agencies->hasPages())
            <div class="agency-pagination-links">{{ $agencies->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.agency-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.agency-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.agency-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.agency-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.agency-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }
.agency-hero-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    background: #fff; color: #0f766e; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem;
    text-decoration: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.agency-hero-btn:hover { background: #f0fdfa; color: #0d9488; border-color: #fff; }

/* Alerts */
.agency-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.agency-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.agency-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

/* Stats */
.agency-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.agency-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.agency-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.agency-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.agency-stat-total { border-left: 4px solid #0d9488; }
.agency-stat-active { border-left: 4px solid #059669; }
.agency-stat-inactive { border-left: 4px solid #6b7280; }
.agency-stat-sub { border-left: 4px solid #0d9488; }

/* Card */
.agency-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.agency-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.agency-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.agency-card-body { padding: 1.25rem; }
.agency-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

/* Filters */
.agency-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.agency-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.agency-field-search { min-width: 180px; }
.agency-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.agency-input, .agency-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.agency-input:focus, .agency-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.agency-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Buttons */
.agency-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.agency-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.agency-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.agency-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.agency-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.agency-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.agency-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.agency-btn-outline-secondary { background: #fff; color: #6b7280; border-color: #d1d5db; }
.agency-btn-outline-secondary:hover { background: #f3f4f6; color: #374151; }
.agency-btn-outline-danger { background: #fff; color: #dc2626; border-color: #dc2626; }
.agency-btn-outline-danger:hover { background: #fef2f2; color: #b91c1c; }
.agency-btn-disabled { background: #f9fafb; color: #9ca3af; border-color: #e5e7eb; cursor: not-allowed; pointer-events: none; }
.agency-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
.agency-form-inline { display: inline; }

/* Table */
.agency-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.agency-table-header .agency-card-title { color: #fff; }
.agency-table-header .agency-card-badge { color: rgba(255,255,255,0.9); }
.agency-table-wrap { overflow-x: auto; }
.agency-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.agency-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.agency-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.agency-table tbody tr:hover { background: #f9fafb; }
.agency-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.agency-name-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.agency-muted { color: #6b7280; }
.agency-num { font-weight: 500; color: #374151; }
.agency-th-actions { text-align: right; }
.agency-actions { text-align: right; white-space: nowrap; }
.agency-actions .agency-btn, .agency-actions form { margin-left: 0.25rem; }
.agency-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.agency-badge-success { background: #d1fae5; color: #047857; }
.agency-badge-danger { background: #fee2e2; color: #b91c1c; }
.agency-empty { text-align: center; padding: 3rem 1rem !important; }
.agency-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.agency-pagination-info { font-weight: 500; }
.agency-pagination-links { display: flex; align-items: center; }
.agency-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.agency-pagination-links a, .agency-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.agency-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.agency-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.agency-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
