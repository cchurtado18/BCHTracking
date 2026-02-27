@extends('layouts.app')

@section('title', 'Escaneo NIC')

@section('content')
<div class="nic-page">
    {{-- Hero --}}
    <header class="nic-hero">
        <div class="nic-hero-inner">
            <div class="nic-hero-text">
                <h1 class="nic-hero-title">Escaneo NIC</h1>
                <p class="nic-hero-subtitle">Sacos enviados listos para escanear en Nicaragua. Escanee el código del saco con la pistola y luego los paquetes.</p>
            </div>
        </div>
    </header>

    {{-- Escanear código del saco (pistola) --}}
    <div class="nic-card nic-scan-card">
        <div class="nic-card-header nic-scan-header">
            <h2 class="nic-card-title">Escanear código del saco</h2>
        </div>
        <div class="nic-card-body">
            <p class="nic-scan-hint">Use la pistola para escanear el código o barra del saco; luego escanee los paquetes dentro.</p>
            <form method="GET" action="{{ route('nic-consolidations.index') }}" id="saco-scan-form" class="nic-scan-form">
                <input type="text" name="saco_code" id="saco_code" class="nic-input nic-input-lg" placeholder="Código del saco (escanear con pistola)" autofocus value="{{ old('saco_code') }}">
                <button type="submit" class="nic-btn nic-btn-primary">Ir al saco</button>
            </form>
            @if(session('error'))
            <p class="nic-scan-error">{{ session('error') }}</p>
            @endif
        </div>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="nic-stats">
        <div class="nic-stat-card nic-stat-total">
            <span class="nic-stat-label">Sacos enviados</span>
            <span class="nic-stat-value">{{ number_format($statsTotal ?? 0) }}</span>
        </div>
        <div class="nic-stat-card nic-stat-air">
            <span class="nic-stat-label">Aéreo</span>
            <span class="nic-stat-value">{{ number_format($statsAir ?? 0) }}</span>
        </div>
        <div class="nic-stat-card nic-stat-sea">
            <span class="nic-stat-label">Marítimo</span>
            <span class="nic-stat-value">{{ number_format($statsSea ?? 0) }}</span>
        </div>
        <div class="nic-stat-card nic-stat-items">
            <span class="nic-stat-label">Total items</span>
            <span class="nic-stat-value">{{ number_format($statsTotalItems ?? 0) }}</span>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="nic-card nic-filters-card">
        <div class="nic-card-header">
            <h2 class="nic-card-title">Filtros</h2>
        </div>
        <div class="nic-card-body">
            <form method="GET" action="{{ route('nic-consolidations.index') }}" class="nic-filters-form">
                <div class="nic-filters-grid">
                    <div class="nic-field">
                        <label class="nic-label">Servicio</label>
                        <select name="service_type" class="nic-select">
                            <option value="">Todos</option>
                            <option value="AIR" {{ request('service_type') == 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ request('service_type') == 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                    </div>
                </div>
                <div class="nic-filters-actions">
                    <button type="submit" class="nic-btn nic-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('nic-consolidations.index') }}" class="nic-btn nic-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de sacos --}}
    <div class="nic-card nic-table-card">
        <div class="nic-card-header nic-table-header">
            <h2 class="nic-card-title">Listado de sacos enviados</h2>
            <span class="nic-card-badge">{{ $consolidations->total() }} {{ $consolidations->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="nic-table-wrap">
            <table class="nic-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Servicio</th>
                        <th>Items</th>
                        <th>Escaneados</th>
                        <th>Faltantes</th>
                        <th>Fecha envío</th>
                        <th class="nic-th-actions">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidations as $consolidation)
                    @php
                        $scanned_count = $consolidation->items()->whereNotNull('scanned_at')->count();
                        $missing_count = $consolidation->items_count - $scanned_count;
                    @endphp
                    <tr>
                        <td><span class="nic-code">{{ $consolidation->code }}</span></td>
                        <td>
                            <span class="nic-badge nic-badge-{{ strtolower($consolidation->service_type ?? '') }}">
                                {{ $consolidation->service_type == 'AIR' ? 'Aéreo' : ($consolidation->service_type == 'SEA' ? 'Marítimo' : ($consolidation->service_type ?? '—')) }}
                            </span>
                        </td>
                        <td class="nic-num">{{ $consolidation->items_count }}</td>
                        <td class="nic-num nic-num-success">{{ $scanned_count }}</td>
                        <td class="nic-num nic-num-danger">{{ $missing_count }}</td>
                        <td class="nic-date">{{ $consolidation->sent_at ? $consolidation->sent_at->format('d/m/Y H:i') : '—' }}</td>
                        <td class="nic-actions">
                            <a href="{{ route('nic-consolidations.show', $consolidation->id) }}" class="nic-btn nic-btn-sm nic-btn-outline-primary">Escanear</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="nic-empty">
                            <p class="nic-empty-text">No hay sacos enviados para escanear.</p>
                            <a href="{{ route('consolidations.index') }}" class="nic-btn nic-btn-secondary">Ver consolidaciones</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($consolidations->total() > 0)
        <div class="nic-card-footer">
            <span class="nic-pagination-info">
                {{ $consolidations->firstItem() }} – {{ $consolidations->lastItem() }} de {{ $consolidations->total() }}
            </span>
            @if($consolidations->hasPages())
            <div class="nic-pagination-links">{{ $consolidations->links() }}</div>
            @endif
        </div>
        @endif
    </div>
</div>

<style>
.nic-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.nic-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.nic-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.nic-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.nic-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }

/* Scan saco card */
.nic-scan-card { border: 2px solid #0d9488; background: #f0fdfa; }
.nic-scan-header { background: #ccfbf1; border-bottom-color: #99f6e4; }
.nic-scan-header .nic-card-title { color: #0f766e; }
.nic-scan-hint { font-size: 0.875rem; color: #6b7280; margin: 0 0 1rem; }
.nic-scan-form { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
.nic-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #fff; color: #111827; max-width: 280px; width: 100%; }
.nic-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.nic-input-lg { padding: 0.65rem 1rem; font-size: 1rem; font-family: ui-monospace, monospace; }
.nic-scan-error { font-size: 0.875rem; color: #dc2626; margin: 0.75rem 0 0; }

/* Card */
.nic-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.nic-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.nic-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.nic-card-body { padding: 1.25rem; }
.nic-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; color: #6b7280; }

/* Stats */
.nic-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.nic-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.nic-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.nic-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.nic-stat-total { border-left: 4px solid #0d9488; }
.nic-stat-air { border-left: 4px solid #3b82f6; }
.nic-stat-sea { border-left: 4px solid #059669; }
.nic-stat-items { border-left: 4px solid #0d9488; }

/* Filters */
.nic-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.nic-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.nic-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.nic-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.nic-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.nic-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Buttons */
.nic-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.nic-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.nic-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.nic-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.nic-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.nic-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.nic-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.nic-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

/* Table */
.nic-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.nic-table-header .nic-card-title { color: #fff; }
.nic-table-header .nic-card-badge { color: rgba(255,255,255,0.9); }
.nic-table-wrap { overflow-x: auto; }
.nic-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.nic-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.nic-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.nic-table tbody tr:hover { background: #f9fafb; }
.nic-code { font-family: ui-monospace, monospace; font-weight: 600; color: #111827; }
.nic-num { color: #374151; font-weight: 500; }
.nic-num-success { color: #059669; font-weight: 600; }
.nic-num-danger { color: #dc2626; font-weight: 600; }
.nic-date { color: #6b7280; }
.nic-th-actions { text-align: right; }
.nic-actions { text-align: right; white-space: nowrap; }
.nic-badge { display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; }
.nic-badge-air { background: #dbeafe; color: #1d4ed8; }
.nic-badge-sea { background: #d1fae5; color: #047857; }
.nic-empty { text-align: center; padding: 3rem 1rem !important; }
.nic-empty-text { margin: 0 0 0.75rem; color: #6b7280; }
.nic-pagination-info { font-weight: 500; }
.nic-pagination-links { display: flex; align-items: center; }
.nic-pagination-links nav { display: flex; gap: 0.25rem; flex-wrap: wrap; }
.nic-pagination-links a, .nic-pagination-links span { display: inline-block; padding: 0.35rem 0.65rem; font-size: 0.8125rem; border-radius: 0.375rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; text-decoration: none; }
.nic-pagination-links a:hover { background: #f3f4f6; color: #0d9488; }
.nic-pagination-links .disabled span { background: #f9fafb; color: #9ca3af; }
.nic-pagination-links .active span { background: #0d9488; color: #fff; border-color: #0d9488; }
</style>
@endsection
