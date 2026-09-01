@extends('layouts.app')

@section('title', 'Clientes - ' . $agency->name)

@section('content')
<div class="acli-page">
    <x-module-banner section="Administración" current="Destinatarios" title="Destinatarios – {{ $agency->name }}" subtitle="Código: {{ $agency->code }}{{ $agency->parent ? ' · Subagencia de '.$agency->parent->name : '' }}" back-href="{{ route('agencies.show', $agency->id) }}" back-label="Volver a la cuenta">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('agency-clients.create', $agency->id) }}" class="mb-btn mb-btn-primary">Nuevo destinatario</a>
        </x-slot:actions>
    </x-module-banner>

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
    background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
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
.acli-btn-primary { background: #fff; color: #0A2D6F; border-color: rgba(255,255,255,0.5); font-weight: 600; }
.acli-btn-primary:hover { background: #F4F8FD; color: #0A2D6F; }
.acli-btn-hero-outline { background: transparent; color: rgba(255,255,255,0.95); border-color: rgba(255,255,255,0.6); }
.acli-btn-hero-outline:hover { background: rgba(255,255,255,0.15); color: #fff; }
.acli-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.acli-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.acli-btn-outline-primary { background: #fff; color: #0A2D6F; border-color: #0A2D6F; }
.acli-btn-outline-primary:hover { background: #E8EEF8; color: #0A2D6F; }
.acli-btn-outline-secondary { background: #fff; color: #6b7280; border-color: #d1d5db; }
.acli-btn-outline-secondary:hover { background: #f9fafb; color: #374151; }
.acli-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.acli-alert { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
.acli-alert-success { background: #F4F8FD; border: 1px solid #C5D4EB; color: #0A2D6F; }

.acli-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.acli-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.acli-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.acli-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.acli-stat-total { border-left: 4px solid #0A2D6F; }
.acli-stat-active { border-left: 4px solid #0A2D6F; }
.acli-stat-inactive { border-left: 4px solid #6b7280; }

.acli-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.acli-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.acli-card-header.acli-table-header { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
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
.acli-input:focus, .acli-select:focus { outline: none; border-color: #0A2D6F; box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.acli-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

.acli-table-wrap { overflow-x: auto; }
.acli-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.acli-table thead tr { background: linear-gradient(135deg, #0A2D6F 0%, #143A8C 50%, #1E4FA8 100%); }
.acli-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.2); white-space: nowrap; }
.acli-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.acli-table tbody tr:hover { background: #f9fafb; }
.acli-name-cell { font-weight: 500; color: #111827; }
.acli-muted { color: #6b7280; }
.acli-th-actions { text-align: right; }
.acli-actions { text-align: right; white-space: nowrap; }
.acli-actions .acli-btn { margin-left: 0.25rem; }
.acli-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.acli-badge-success { background: #E8EEF8; color: #0A2D6F; }
.acli-badge-danger { background: #fee2e2; color: #b91c1c; }
.acli-empty { text-align: center; padding: 3rem 1rem !important; }
.acli-empty-text { margin: 0; color: #6b7280; }
.acli-pagination-info { font-weight: 500; }
.acli-pagination-links { display: flex; align-items: center; }
.acli-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.acli-pagination-links a, .acli-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.acli-pagination-links a:hover { background: #f3f4f6; color: #0A2D6F; }
.acli-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.acli-pagination-links .active span { background: #0A2D6F; color: #fff; border-color: #0A2D6F; }
</style>
@endsection
