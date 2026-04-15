@extends('layouts.tracking')

@section('title', 'Consultar paquete')

@section('content')
<div class="tracking-page">
    <header class="tracking-hero">
        <div class="tracking-hero-inner">
            <div class="tracking-hero-text">
                <h1 class="tracking-hero-title">Consultar su paquete</h1>
                <p class="tracking-hero-subtitle">Ingrese el código de almacén (6 dígitos) o el número de tracking para ver el estado.</p>
            </div>
        </div>
    </header>

    <div class="tracking-card tracking-search-card">
        <div class="tracking-card-header">
            <h2 class="tracking-card-title">Buscar por código o tracking</h2>
        </div>
        <div class="tracking-card-body">
            <form action="{{ route('tracking.index') }}" method="GET" class="tracking-search-form">
                <div class="tracking-search-row">
                    <input type="text" name="code" value="{{ old('code', $code) }}" placeholder="Ej: 000123 o 1Z999AA10123456784" class="tracking-input" autofocus>
                    <button type="submit" class="tracking-btn tracking-btn-primary">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    @if($notFound)
    <div class="tracking-alert tracking-alert-warning">
        <p class="tracking-alert-title">No se encontró ningún paquete con ese código o tracking.</p>
        <p class="tracking-alert-text">Verifique el número e intente de nuevo.</p>
    </div>
    @endif

    @if($preregistrations->isNotEmpty())
    <div class="tracking-results">
        @if($preregistrations->count() > 1)
        <p class="tracking-results-hint">Este código corresponde a <strong>{{ $preregistrations->count() }} bultos</strong>.</p>
        @endif

        @foreach($preregistrations as $p)
        @php
            $displayTz = config('app.display_timezone', 'America/New_York');
            $steps = \App\Http\Controllers\Web\TrackingController::timelineSteps($p, $displayTz);
            $receivedAt = $p->created_at ? $p->created_at->timezone($displayTz) : null;
            $weight = $p->verified_weight_lbs ?? $p->intake_weight_lbs;
            $weightStr = $weight !== null && $weight !== '' ? number_format((float) $weight, 1) . ' lb(s)' : '—';
        @endphp
        <div class="tracking-card tracking-result-card">
            <div class="tracking-card-header tracking-table-header">
                <span class="tracking-result-code">{{ $p->warehouse_code ?? $p->tracking_external ?? '—' }}</span>
                @if(($p->status ?? '') === 'CANCELLED')
                <span class="tracking-badge tracking-badge-cancelled">{{ \App\Http\Controllers\Web\TrackingController::statusLabel($p->status) }}</span>
                @endif
            </div>
            <div class="tracking-card-body tracking-result-body">
                {{-- Resumen del paquete --}}
                <div class="tracking-overview">
                    @if($receivedAt)
                    <p class="tracking-overview-date">Recibido el {{ $receivedAt->format('d/m/y, h:i a') }}</p>
                    @endif
                    <div class="tracking-overview-meta">
                        <span class="tracking-overview-item"><strong>Tracking:</strong> {{ $p->tracking_external ?? '—' }}</span>
                        <span class="tracking-overview-item"><strong>Guía:</strong> {{ $p->warehouse_code ?? '—' }}</span>
                        <span class="tracking-overview-item"><strong>{{ $weightStr }}</strong></span>
                        <span class="tracking-overview-item">{{ \App\Http\Controllers\Web\TrackingController::serviceLabel($p->service_type ?? '') }}</span>
                    </div>
                    @if($p->bultos_total && $p->bultos_total > 1)
                    <p class="tracking-overview-bulto">Bulto {{ $p->bulto_index }} de {{ $p->bultos_total }}</p>
                    @endif
                </div>

                {{-- Línea de tiempo vertical --}}
                <div class="tracking-timeline">
                    @foreach($steps as $index => $step)
                    <div class="tracking-timeline-item {{ $step['is_current'] ? 'tracking-timeline-item-current' : '' }} {{ !$step['is_completed'] ? 'tracking-timeline-item-pending' : '' }}">
                        <div class="tracking-timeline-indicator">
                            @if($step['key'] === 'DELIVERED')
                                @if($step['is_completed'])
                                <span class="tracking-timeline-icon tracking-timeline-icon-check">✓</span>
                                @else
                                <span class="tracking-timeline-num">{{ $index + 1 }}</span>
                                @endif
                            @else
                            <span class="tracking-timeline-num">{{ $index + 1 }}</span>
                            @endif
                        </div>
                        <div class="tracking-timeline-content">
                            <span class="tracking-timeline-label">{{ $step['label'] }}</span>
                            @if($step['timestamp'])
                            <span class="tracking-timeline-time">{{ $step['timestamp']->format('d/m/y, h:i a') }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<style>
.tracking-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }
.tracking-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem; padding: 1.75rem 1.5rem; margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.tracking-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.tracking-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.tracking-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 52ch; }
.tracking-card { background: #fff; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 1.5rem; overflow: hidden; }
.tracking-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; background: #fafafa; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }
.tracking-card-header.tracking-table-header { background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%); }
.tracking-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.tracking-card-body { padding: 1.25rem 1.5rem; }
.tracking-search-form { margin: 0; }
.tracking-search-row { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
.tracking-input {
    flex: 1; min-width: 200px; padding: 0.65rem 1rem; font-size: 1rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
    background: #fff; color: #111827;
}
.tracking-input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15); }
.tracking-input::placeholder { color: #9ca3af; }
.tracking-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 1.25rem; font-size: 0.9375rem; font-weight: 600; border-radius: 0.5rem; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.tracking-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; }
.tracking-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.tracking-alert { padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; border: 1px solid; }
.tracking-alert-warning { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.tracking-alert-title { margin: 0 0 0.25rem; font-weight: 600; }
.tracking-alert-text { margin: 0; font-size: 0.875rem; opacity: 0.95; }
.tracking-results { margin-top: 0; }
.tracking-results-hint { font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem; }
.tracking-result-code { font-family: ui-monospace, monospace; font-weight: 700; font-size: 1.125rem; color: #fff; }
.tracking-badge { display: inline-block; padding: 0.25rem 0.65rem; font-size: 0.8125rem; font-weight: 600; border-radius: 9999px; }
.tracking-badge-status-received_miami { background: rgba(255,255,255,0.25); color: #fff; }
.tracking-badge-status-in_transit { background: rgba(255,255,255,0.25); color: #fff; }
.tracking-badge-status-in_warehouse_nic { background: rgba(255,255,255,0.25); color: #fff; }
.tracking-badge-status-ready { background: rgba(255,255,255,0.25); color: #fff; }
.tracking-badge-status-delivered { background: rgba(255,255,255,0.25); color: #fff; }
.tracking-badge-status-cancelled { background: rgba(255,255,255,0.2); color: #fff; }
.tracking-dl { margin: 0; }
.tracking-dl-row { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: 0.5rem; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; }
.tracking-dl-row:last-child { border-bottom: 0; }
.tracking-dt { font-size: 0.875rem; color: #6b7280; margin: 0; }
.tracking-dd { font-size: 0.875rem; font-weight: 500; color: #111827; margin: 0; text-align: right; }
.tracking-code { font-family: ui-monospace, monospace; }

/* Result card: overview + timeline */
.tracking-result-body { padding: 1.5rem 1.75rem; }
.tracking-overview { margin-bottom: 1.75rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; }
.tracking-overview-date { font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0 0 0.75rem; }
.tracking-overview-meta { display: flex; flex-wrap: wrap; gap: 1rem 1.25rem; font-size: 0.875rem; color: #4b5563; }
.tracking-overview-item { white-space: nowrap; }
.tracking-overview-item strong { color: #111827; font-weight: 600; }
.tracking-overview-bulto { font-size: 0.8125rem; color: #6b7280; margin: 0.5rem 0 0; }

.tracking-timeline { display: flex; flex-direction: column; gap: 0; }
.tracking-timeline-item { display: flex; align-items: flex-start; gap: 1rem; position: relative; padding: 0.6rem 0; }
.tracking-timeline-item:not(:last-child)::after { content: ''; position: absolute; left: 0.9375rem; top: 2.25rem; bottom: -0.6rem; width: 2px; background: #e5e7eb; }
.tracking-timeline-item-current:not(:last-child)::after { background: linear-gradient(180deg, #0d9488 0%, #e5e7eb 100%); }
.tracking-timeline-indicator {
    flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.8125rem; font-weight: 700; background: #e5e7eb; color: #9ca3af; z-index: 1;
}
.tracking-timeline-item-current .tracking-timeline-indicator { background: #0d9488; color: #fff; }
.tracking-timeline-item-pending .tracking-timeline-indicator { background: #f3f4f6; color: #9ca3af; }
.tracking-timeline-item-current.tracking-timeline-item-completed .tracking-timeline-indicator { background: #0d9488; color: #fff; }
.tracking-timeline-icon-check { font-size: 1rem; line-height: 1; }
.tracking-timeline-item .tracking-timeline-indicator .tracking-timeline-icon-check { color: #fff; }
.tracking-timeline-item-pending .tracking-timeline-indicator .tracking-timeline-icon-check { color: #9ca3af; }
.tracking-timeline-num { line-height: 1; }
.tracking-timeline-content { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.tracking-timeline-label { font-size: 0.875rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.02em; }
.tracking-timeline-item-current .tracking-timeline-label { color: #111827; }
.tracking-timeline-time { font-size: 0.8125rem; color: #6b7280; }
.tracking-timeline-item-current .tracking-timeline-time { color: #374151; font-weight: 500; }
.tracking-badge-cancelled { background: rgba(255,255,255,0.2); color: #fff; padding: 0.25rem 0.65rem; font-size: 0.8125rem; font-weight: 600; border-radius: 9999px; }
</style>
@endsection
