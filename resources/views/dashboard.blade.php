@extends('layouts.app')

@section('title', 'Panel')

@section('content')
<div class="panel-page">
    {{-- Hero --}}
    <header class="panel-hero">
        <div class="panel-hero-inner">
            <div class="panel-hero-text">
                <h1 class="panel-hero-title">{{ auth()->user() && auth()->user()->isAgencyUser() ? 'Resumen de tu agencia' : 'Panel de control' }}</h1>
                <p class="panel-hero-subtitle">{{ $periodLabel }}{{ auth()->user() && auth()->user()->isAgencyUser() && isset($selectedAgency) && $selectedAgency ? ' · ' . $selectedAgency->name : '' }}</p>
            </div>
        </div>
    </header>

    {{-- Filtros --}}
    <div class="panel-card panel-filters-card">
        <div class="panel-card-header">
            <h2 class="panel-card-title">Filtros</h2>
        </div>
        <div class="panel-card-body">
            <form method="GET" action="{{ route('dashboard') }}" class="panel-filters-form">
                <div class="panel-filters-grid">
                    <div class="panel-field">
                        <label class="panel-label">Desde</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="panel-input">
                    </div>
                    <div class="panel-field">
                        <label class="panel-label">Hasta</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="panel-input">
                    </div>
                    @if(auth()->user() && auth()->user()->isAgencyUser())
                    <input type="hidden" name="agency_id" value="{{ auth()->user()->agency_id }}">
                    @else
                    <div class="panel-field">
                        <label class="panel-label">Agencia</label>
                        <select name="agency_id" class="panel-select">
                            <option value="">Todas</option>
                            @foreach($agenciesForFilter ?? [] as $a)
                                <option value="{{ $a->id }}" {{ (int)($agencyId ?? 0) === (int)$a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="panel-field">
                        <label class="panel-label">Servicio</label>
                        <select name="service_type" class="panel-select">
                            <option value="">Todos</option>
                            <option value="AIR" {{ ($serviceType ?? '') === 'AIR' ? 'selected' : '' }}>Aéreo</option>
                            <option value="SEA" {{ ($serviceType ?? '') === 'SEA' ? 'selected' : '' }}>Marítimo</option>
                        </select>
                    </div>
                </div>
                <div class="panel-filters-actions">
                    <button type="submit" class="panel-btn panel-btn-primary">Aplicar filtros</button>
                    <a href="{{ route('dashboard') }}" class="panel-btn panel-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tarjetas de resumen (período + estado) --}}
    <div class="panel-stats">
        <div class="panel-stat-card panel-stat-total">
            <span class="panel-stat-label">Paquetes (período)</span>
            <span class="panel-stat-value">{{ number_format($packagesInPeriod ?? 0) }}</span>
            @if(auth()->user() && auth()->user()->isAgencyUser())
            <a href="{{ route('packages.index') }}" class="panel-stat-link">Ver mis paquetes →</a>
            @else
            <a href="{{ route('preregistrations.index') }}" class="panel-stat-link">Ver preregistros →</a>
            @endif
        </div>
        <div class="panel-stat-card panel-stat-air">
            <span class="panel-stat-label">Lbs Aéreo (período)</span>
            <span class="panel-stat-value">{{ number_format($lbsAirPeriod ?? 0, 1) }} <span class="panel-uom">lbs</span></span>
        </div>
        <div class="panel-stat-card panel-stat-sea">
            <span class="panel-stat-label">Lbs Marítimo (período)</span>
            <span class="panel-stat-value">{{ number_format($lbsSeaPeriod ?? 0, 1) }} <span class="panel-uom">lbs</span></span>
        </div>
        @if(!auth()->user() || !auth()->user()->isAgencyUser())
        <div class="panel-stat-card panel-stat-open">
            <span class="panel-stat-label">Sacos abiertos</span>
            <span class="panel-stat-value">{{ $consolidationsOpen ?? 0 }}</span>
            <a href="{{ route('consolidations.index') }}" class="panel-stat-link">Ver consolidaciones →</a>
        </div>
        @endif
    </div>

    {{-- Estado operativo --}}
    <div class="panel-card">
        <div class="panel-card-header panel-table-header">
            <h2 class="panel-card-title">Estado operativo</h2>
        </div>
        <div class="panel-card-body">
            <div class="panel-metrics-grid">
                <div class="panel-metric-box">
                    <span class="panel-metric-value">{{ number_format($preregistrationsCount ?? 0) }}</span>
                    <span class="panel-metric-label">Total preregistros</span>
                </div>
                <div class="panel-metric-box panel-metric-miami">
                    <span class="panel-metric-value">{{ number_format($preregistrationsReceived ?? 0) }}</span>
                    <span class="panel-metric-label">Recibidos Miami</span>
                </div>
                <div class="panel-metric-box panel-metric-transit">
                    <span class="panel-metric-value">{{ number_format($preregistrationsInTransit ?? 0) }}</span>
                    <span class="panel-metric-label">En tránsito</span>
                </div>
                <div class="panel-metric-box panel-metric-ready">
                    <span class="panel-metric-value">{{ number_format($preregistrationsReady ?? 0) }}</span>
                    <span class="panel-metric-label">Listos retiro</span>
                </div>
                @if(!auth()->user() || !auth()->user()->isAgencyUser())
                <div class="panel-metric-box">
                    <span class="panel-metric-value">{{ $consolidationsOpen ?? 0 }}</span>
                    <span class="panel-metric-label">Sacos abiertos</span>
                </div>
                <div class="panel-metric-box">
                    <span class="panel-metric-value">{{ $consolidationsSent ?? 0 }}</span>
                    <span class="panel-metric-label">Sacos enviados</span>
                </div>
                @endif
                <div class="panel-metric-box">
                    <span class="panel-metric-value">{{ number_format($lbsAir ?? 0, 0) }}</span>
                    <span class="panel-metric-label">Lbs aéreo (total)</span>
                </div>
                <div class="panel-metric-box">
                    <span class="panel-metric-value">{{ number_format($lbsSea ?? 0, 0) }}</span>
                    <span class="panel-metric-label">Lbs marítimo (total)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Atención / Alertas --}}
    <div class="panel-card panel-alerts-card">
        <div class="panel-card-header panel-alerts-header">
            <h2 class="panel-card-title">Atención</h2>
            @if(!empty($alerts) && count($alerts) > 0)
            <span class="panel-alerts-badge">{{ count($alerts) }} {{ count($alerts) === 1 ? 'alerta' : 'alertas' }}</span>
            @endif
        </div>
        <div class="panel-card-body">
            @if(!empty($alerts) && count($alerts) > 0)
            <div class="panel-alerts-list">
                @foreach($alerts as $alert)
                <a href="{{ $alert['url'] ?? '#' }}" class="panel-alert-item">
                    <span class="panel-alert-indicator"></span>
                    <span class="panel-alert-icon">
                        @if(str_contains(strtolower($alert['title'] ?? ''), 'miami')) 📍
                        @elseif(str_contains(strtolower($alert['title'] ?? ''), 'sacos')) 📦
                        @else ✅
                        @endif
                    </span>
                    <div class="panel-alert-body">
                        <span class="panel-alert-title">{{ $alert['title'] }}</span>
                        <span class="panel-alert-meta">Requiere acción · Ver detalle</span>
                    </div>
                    <span class="panel-alert-count">{{ $alert['count'] }}</span>
                    <span class="panel-alert-arrow">→</span>
                </a>
                @endforeach
            </div>
            @else
            <div class="panel-alert-empty">
                <span class="panel-alert-empty-icon">✓</span>
                <p class="panel-alert-empty-text">No hay alertas pendientes. Todo en orden.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Agencias en el período --}}
    @if(!empty($agenciesRanking) && $agenciesRanking->isNotEmpty() && (!auth()->user() || !auth()->user()->isAgencyUser()))
    <div class="panel-card">
        <div class="panel-card-header panel-table-header">
            <h2 class="panel-card-title">Agencias en el período</h2>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Agencia</th>
                        <th class="panel-th-end">Paquetes</th>
                        <th class="panel-th-end">Lbs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agenciesRanking as $row)
                    <tr>
                        <td>{{ $row['agency']->name ?? '—' }}</td>
                        <td class="panel-td-end">{{ number_format($row['packages_count']) }}</td>
                        <td class="panel-td-end">{{ number_format($row['total_lbs'], 1) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="panel-card">
        <div class="panel-card-header">
            <h2 class="panel-card-title">Accesos rápidos</h2>
        </div>
        <div class="panel-card-body">
            <div class="panel-quicklinks">
                @if(auth()->user() && auth()->user()->isAgencyUser())
                <a href="{{ route('packages.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Mis paquetes</a>
                <a href="{{ route('reporte.solicitar') }}" class="panel-btn panel-btn-sm panel-btn-secondary">Reporte PDF</a>
                <a href="{{ route('tracking.index') }}" class="panel-btn panel-btn-sm panel-btn-secondary">Consultar tracking</a>
                @else
                <a href="{{ route('preregistrations.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Preregistros</a>
                <a href="{{ route('consolidations.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Consolidaciones</a>
                <a href="{{ route('nic-consolidations.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Escaneo NIC</a>
                <a href="{{ route('packages.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Paquetes</a>
                <a href="{{ route('agencies.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Agencias</a>
                <a href="{{ route('deliveries.index') }}" class="panel-btn panel-btn-sm panel-btn-outline-primary">Entregas</a>
                <a href="{{ route('reporte.solicitar') }}" class="panel-btn panel-btn-sm panel-btn-secondary">Reporte PDF</a>
                <a href="{{ route('tracking.index') }}" class="panel-btn panel-btn-sm panel-btn-secondary">Consultar tracking</a>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.panel-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

/* Hero */
.panel-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.panel-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.panel-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.panel-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }

/* Card */
.panel-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.panel-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.panel-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.panel-card-body { padding: 1.25rem; }

/* Filters */
.panel-filters-form { display: flex; flex-direction: column; gap: 1rem; }
.panel-filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
.panel-label { display: block; font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.35rem; }
.panel-input, .panel-select {
    display: block; width: 100%; padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.panel-input:focus, .panel-select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.panel-filters-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }

/* Stats */
.panel-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.panel-stat-card {
    background: #fff; border-radius: 0.75rem; padding: 1rem 1.25rem; border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 0.25rem;
}
.panel-stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
.panel-stat-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
.panel-uom { font-size: 0.75rem; font-weight: 500; color: #9ca3af; }
.panel-stat-link { font-size: 0.8125rem; color: #0d9488; font-weight: 500; margin-top: 0.35rem; text-decoration: none; }
.panel-stat-link:hover { color: #0f766e; text-decoration: underline; }
.panel-stat-total { border-left: 4px solid #0d9488; }
.panel-stat-air { border-left: 4px solid #3b82f6; }
.panel-stat-sea { border-left: 4px solid #059669; }
.panel-stat-open { border-left: 4px solid #22c55e; }

/* Metrics grid */
.panel-metrics-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
.panel-metric-box {
    background: #f8fafc; border-radius: 0.5rem; padding: 1rem; text-align: center; border: 1px solid #e5e7eb;
    border-left: 4px solid #94a3b8;
}
.panel-metric-value { display: block; font-size: 1.25rem; font-weight: 700; color: #111827; }
.panel-metric-label { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; display: block; }
.panel-metric-miami { border-left-color: #f59e0b; }
.panel-metric-transit { border-left-color: #3b82f6; }
.panel-metric-ready { border-left-color: #22c55e; }

/* Table header (teal) */
.panel-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.panel-table-header .panel-card-title { color: #fff; }
.panel-table-wrap { overflow-x: auto; }
.panel-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.panel-table th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: #374151; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.panel-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; color: #334155; }
.panel-table tbody tr:hover { background: #f9fafb; }
.panel-th-end, .panel-td-end { text-align: right; }

/* Buttons */
.panel-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; transition: background 0.15s, color 0.15s; }
.panel-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.panel-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.panel-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.panel-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.panel-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.panel-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.panel-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

/* Alerts */
.panel-alerts-card { border-color: #fde68a; background: #fffbeb; }
.panel-alerts-header { background: #fef3c7; border-bottom-color: #fde68a; }
.panel-alerts-header .panel-card-title { color: #92400e; }
.panel-alerts-badge { font-size: 0.75rem; font-weight: 700; background: #d97706; color: #fff; padding: 0.2rem 0.5rem; border-radius: 9999px; }
.panel-alerts-list { display: flex; flex-direction: column; gap: 0.5rem; }
.panel-alert-item {
    display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; background: #fff; border-radius: 0.5rem;
    border: 1px solid #fde68a; text-decoration: none; color: inherit; transition: background 0.15s, box-shadow 0.15s;
    position: relative; padding-left: 1.25rem;
}
.panel-alert-item:hover { background: #fef3c7; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.12); }
.panel-alert-indicator { position: absolute; left: 0; top: 0; bottom: 0; width: 4px; min-height: 2.5rem; background: #d97706; border-radius: 0 4px 4px 0; }
.panel-alert-icon { font-size: 1.25rem; flex-shrink: 0; }
.panel-alert-body { flex: 1; min-width: 0; }
.panel-alert-title { font-weight: 600; color: #92400e; font-size: 0.9375rem; display: block; }
.panel-alert-meta { font-size: 0.75rem; color: #b45309; margin-top: 0.15rem; display: block; }
.panel-alert-count { font-weight: 700; font-size: 1.125rem; color: #b45309; min-width: 2rem; text-align: center; background: #fef3c7; padding: 0.25rem 0.5rem; border-radius: 0.375rem; }
.panel-alert-arrow { color: #d97706; font-size: 1rem; flex-shrink: 0; }
.panel-alert-empty { text-align: center; padding: 2rem; color: #059669; }
.panel-alert-empty-icon { display: inline-block; width: 2.5rem; height: 2.5rem; line-height: 2.5rem; background: #d1fae5; border-radius: 50%; font-weight: 700; font-size: 1.25rem; margin-bottom: 0.75rem; }
.panel-alert-empty-text { margin: 0; font-size: 0.9375rem; font-weight: 500; }

/* Quicklinks */
.panel-quicklinks { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
</style>
@endsection
