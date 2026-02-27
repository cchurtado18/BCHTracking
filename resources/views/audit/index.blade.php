@extends('layouts.app')

@section('title', 'Auditoría')

@section('content')
<div class="audit-page">
    {{-- Hero --}}
    <header class="audit-hero">
        <div class="audit-hero-inner">
            <div class="audit-hero-text">
                <h1 class="audit-hero-title">Auditoría</h1>
                <p class="audit-hero-subtitle">Registro de paquetes creados, modificados y eliminados por usuario.</p>
            </div>
        </div>
    </header>

    {{-- Tarjetas de resumen --}}
    <div class="audit-stats">
        <div class="audit-stat-card audit-stat-total">
            <span class="audit-stat-label">Total</span>
            <span class="audit-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="audit-stat-card audit-stat-created">
            <span class="audit-stat-label">Creados</span>
            <span class="audit-stat-value">{{ number_format($statsCreated ?? 0) }}</span>
        </div>
        <div class="audit-stat-card audit-stat-updated">
            <span class="audit-stat-label">Modificados</span>
            <span class="audit-stat-value">{{ number_format($statsUpdated ?? 0) }}</span>
        </div>
        <div class="audit-stat-card audit-stat-deleted">
            <span class="audit-stat-label">Eliminados</span>
            <span class="audit-stat-value">{{ number_format($statsDeleted ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="audit-card audit-filters-card">
        <div class="audit-card-header">
            <h2 class="audit-card-title">Filtros</h2>
        </div>
        <div class="audit-card-body">
            <form method="GET" action="{{ route('audit.index') }}" class="audit-filters-form">
                <div class="audit-filters-grid">
                    <div class="audit-field audit-field-search">
                        <label class="audit-label">Buscar en resumen</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Código, peso, estado..." class="audit-input">
                    </div>
                    <div class="audit-field">
                        <label class="audit-label">Acción</label>
                        <select name="action" class="audit-select">
                            <option value="">Todas</option>
                            <option value="created" {{ request('action') == 'created' ? 'selected' : '' }}>Creado</option>
                            <option value="updated" {{ request('action') == 'updated' ? 'selected' : '' }}>Modificado</option>
                            <option value="deleted" {{ request('action') == 'deleted' ? 'selected' : '' }}>Eliminado</option>
                        </select>
                    </div>
                    <div class="audit-field">
                        <label class="audit-label">Usuario</label>
                        <select name="user_id" class="audit-select">
                            <option value="">Todos</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (int) request('user_id') === (int) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="audit-field">
                        <label class="audit-label">Desde</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="audit-input">
                    </div>
                    <div class="audit-field">
                        <label class="audit-label">Hasta</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="audit-input">
                    </div>
                </div>
                <div class="audit-filters-actions">
                    <button type="submit" class="audit-btn audit-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('audit.index') }}" class="audit-btn audit-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="audit-card audit-table-card">
        <div class="audit-card-header audit-table-header">
            <h2 class="audit-card-title">Registro de auditoría</h2>
            <span class="audit-card-badge">{{ $logs->total() }} {{ $logs->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="audit-table-wrap">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Acción</th>
                        <th>Resumen</th>
                        <th>Usuario</th>
                        <th class="audit-th-actions">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="audit-muted">{{ $log->created_at->timezone(config('app.timezone', 'America/Managua'))->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($log->action === 'created')
                            <span class="audit-badge audit-badge-created">Creado</span>
                            @elseif($log->action === 'updated')
                            <span class="audit-badge audit-badge-updated">Modificado</span>
                            @else
                            <span class="audit-badge audit-badge-deleted">Eliminado</span>
                            @endif
                        </td>
                        <td class="audit-summary-cell">{{ Str::limit($log->summary, 80) }}</td>
                        <td class="audit-muted">{{ $log->user?->name ?? '—' }}</td>
                        <td class="audit-actions">
                            <a href="{{ route('audit.show', $log->id) }}" class="audit-btn audit-btn-sm audit-btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="audit-empty">
                            <p class="audit-empty-text">No hay registros de auditoría con los filtros aplicados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->total() > 0)
        <div class="audit-card-footer">
            <span class="audit-pagination-info">{{ $logs->firstItem() }} – {{ $logs->lastItem() }} de {{ $logs->total() }}</span>
            @if($logs->hasPages())
            <div class="audit-pagination-links">{{ $logs->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.audit-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.audit-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.audit-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.audit-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.audit-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }

.audit-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.audit-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.audit-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.audit-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.audit-stat-total { border-left: 4px solid #0d9488; }
.audit-stat-created { border-left: 4px solid #059669; }
.audit-stat-updated { border-left: 4px solid #3b82f6; }
.audit-stat-deleted { border-left: 4px solid #dc2626; }

.audit-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.audit-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.audit-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.audit-card-body { padding: 1.25rem; }
.audit-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }
.audit-card-badge { font-size: 0.8125rem; color: #6b7280; }

.audit-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.audit-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
.audit-field-search { min-width: 200px; }
.audit-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.audit-input, .audit-select { display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; }
.audit-input:focus, .audit-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.audit-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

.audit-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.audit-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.audit-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.audit-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.audit-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.audit-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.audit-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.audit-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.audit-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.audit-table-header .audit-card-title { color: #fff; }
.audit-table-header .audit-card-badge { color: rgba(255,255,255,0.9); }
.audit-table-wrap { overflow-x: auto; }
.audit-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.audit-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.audit-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.audit-table tbody tr:hover { background: #f9fafb; }
.audit-muted { color: #6b7280; font-size: 0.875rem; }
.audit-summary-cell { max-width: 360px; }
.audit-th-actions { text-align: right; }
.audit-actions { text-align: right; white-space: nowrap; }
.audit-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.audit-badge-created { background: #d1fae5; color: #047857; }
.audit-badge-updated { background: #dbeafe; color: #1d4ed8; }
.audit-badge-deleted { background: #fee2e2; color: #b91c1c; }
.audit-empty { text-align: center; padding: 3rem 1rem !important; }
.audit-empty-text { margin: 0; color: #6b7280; }
.audit-pagination-info { font-weight: 500; }
.audit-pagination-links { display: flex; align-items: center; }
.audit-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.audit-pagination-links a, .audit-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.audit-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.audit-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.audit-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
