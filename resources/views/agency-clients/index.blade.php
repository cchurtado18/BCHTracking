@extends('layouts.app')

@section('title', 'Clientes - ' . $agency->name)

@section('content')
<div class="acli-page">
    {{-- Hero --}}
    <header class="acli-hero">
        <div class="acli-hero-inner">
            <div class="acli-hero-text">
                <h1 class="acli-hero-title">Clientes – {{ $agency->name }}</h1>
                <p class="acli-hero-subtitle">Código: {{ $agency->code }}@if($agency->parent) · Subagencia de {{ $agency->parent->name }}@endif</p>
            </div>
            <div class="acli-hero-actions">
                <a href="{{ route('agency-clients.create', $agency->id) }}" class="acli-btn acli-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    Nuevo Cliente
                </a>
                <a href="{{ route('agencies.show', $agency->id) }}" class="acli-btn acli-btn-hero-outline">← Volver a Agencia</a>
            </div>
        </div>
    </header>

    @if(session('success'))
    <div class="acli-alert acli-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Stats --}}
    <div class="acli-stats">
        <div class="acli-stat-card acli-stat-total">
            <span class="acli-stat-label">Total</span>
            <span class="acli-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="acli-stat-card acli-stat-active">
            <span class="acli-stat-label">Activos</span>
            <span class="acli-stat-value">{{ number_format($statsActive ?? 0) }}</span>
        </div>
        <div class="acli-stat-card acli-stat-inactive">
            <span class="acli-stat-label">Inactivos</span>
            <span class="acli-stat-value">{{ number_format($statsInactive ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="acli-card acli-filters-card">
        <div class="acli-card-header">
            <h2 class="acli-card-title">Filtros</h2>
        </div>
        <div class="acli-card-body">
            <form method="GET" action="{{ route('agency-clients.index', $agency->id) }}" class="acli-filters-form">
                <div class="acli-filters-grid">
                    <div class="acli-field">
                        <label class="acli-label">Estado</label>
                        <select name="is_active" class="acli-select">
                            <option value="">Todos</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                    <div class="acli-field acli-field-search">
                        <label class="acli-label">Búsqueda</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre" class="acli-input">
                    </div>
                </div>
                <div class="acli-filters-actions">
                    <button type="submit" class="acli-btn acli-btn-primary">Filtrar</button>
                    <a href="{{ route('agency-clients.index', $agency->id) }}" class="acli-btn acli-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="acli-card acli-table-card">
        <div class="acli-card-header acli-table-header">
            <h2 class="acli-card-title">Listado de clientes</h2>
            <span class="acli-card-badge">{{ $clients->total() }} {{ $clients->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="acli-table-wrap">
            <table class="acli-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th class="acli-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td class="acli-name-cell">{{ $client->full_name }}</td>
                        <td class="acli-muted">{{ $client->phone ?? '—' }}</td>
                        <td>
                            @if($client->is_active)
                            <span class="acli-badge acli-badge-success">Activo</span>
                            @else
                            <span class="acli-badge acli-badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="acli-actions">
                            <a href="{{ route('agency-clients.show', $client->id) }}" class="acli-btn acli-btn-sm acli-btn-outline-primary">Ver</a>
                            <a href="{{ route('agency-clients.edit', $client->id) }}" class="acli-btn acli-btn-sm acli-btn-outline-secondary">Editar</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="acli-empty">
                            <p class="acli-empty-text">No hay clientes</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clients->hasPages())
        <div class="acli-card-footer">
            <span class="acli-pagination-info">{{ $clients->firstItem() }} – {{ $clients->lastItem() }} de {{ $clients->total() }}</span>
            <div class="acli-pagination-links">{{ $clients->links() }}</div>
        </div>
        @endif
    </div>
</div>

<style>
.acli-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.acli-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.acli-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.acli-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.acli-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); }
.acli-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.acli-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
    padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
}
.acli-btn-primary { background: #fff; color: #0f766e; border-color: rgba(255,255,255,0.5); font-weight: 600; }
.acli-btn-primary:hover { background: #f0fdfa; color: #0d9488; }
.acli-btn-hero-outline { background: transparent; color: rgba(255,255,255,0.95); border-color: rgba(255,255,255,0.6); }
.acli-btn-hero-outline:hover { background: rgba(255,255,255,0.15); color: #fff; }
.acli-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.acli-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.acli-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.acli-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.acli-btn-outline-secondary { background: #fff; color: #6b7280; border-color: #d1d5db; }
.acli-btn-outline-secondary:hover { background: #f9fafb; color: #374151; }
.acli-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.acli-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.acli-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

.acli-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.acli-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.acli-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.acli-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.acli-stat-total { border-left: 4px solid #0d9488; }
.acli-stat-active { border-left: 4px solid #059669; }
.acli-stat-inactive { border-left: 4px solid #6b7280; }

.acli-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.acli-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.acli-card-header.acli-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.acli-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.acli-table-header .acli-card-title { color: #fff; }
.acli-card-badge { font-size: 0.8125rem; color: #6b7280; font-weight: 500; }
.acli-table-header .acli-card-badge { color: rgba(255,255,255,0.9); }
.acli-card-body { padding: 1.25rem; }
.acli-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

.acli-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.acli-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.acli-field-search { min-width: 200px; }
.acli-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.acli-input, .acli-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem;
    border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827;
}
.acli-input:focus, .acli-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.acli-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

.acli-table-wrap { overflow-x: auto; }
.acli-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.acli-table thead tr { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.acli-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); white-space: nowrap; }
.acli-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.acli-table tbody tr:hover { background: #f9fafb; }
.acli-name-cell { font-weight: 500; color: #111827; }
.acli-muted { color: #6b7280; }
.acli-th-actions { text-align: right; }
.acli-actions { text-align: right; white-space: nowrap; }
.acli-actions .acli-btn { margin-left: 0.25rem; }
.acli-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.acli-badge-success { background: #d1fae5; color: #047857; }
.acli-badge-danger { background: #fee2e2; color: #b91c1c; }
.acli-empty { text-align: center; padding: 3rem 1rem !important; }
.acli-empty-text { margin: 0; color: #6b7280; }
.acli-pagination-info { font-weight: 500; }
.acli-pagination-links { display: flex; align-items: center; }
.acli-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.acli-pagination-links a, .acli-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.acli-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.acli-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.acli-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
