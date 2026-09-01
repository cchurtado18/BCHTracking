@extends('layouts.tracking')

@section('title', 'Consultar paquete')

@section('content')
<div class="tracking-page">
    <p class="pt-kicker">Rastreo</p>
    <h1 class="pt-heading">Consultar su paquete</h1>
    <p class="pt-lead">Ingrese el código de almacén (6 dígitos) o el número de tracking.</p>

    <form action="{{ route('tracking.index') }}" method="GET" class="tracking-search-form">
        <label for="code" class="guest-label">Código o tracking</label>
        <div class="tracking-search-row">
            <input id="code" type="text" name="code" value="{{ old('code', $code) }}" placeholder="Ej: 000123 o 1Z999AA10123456784" class="guest-input" autofocus>
            <button type="submit" class="guest-submit">Buscar</button>
        </div>
    </form>

    @if($notFound)
    <div class="tracking-alert">
        <p class="tracking-alert-title">No se encontró ningún paquete</p>
        <p class="tracking-alert-text">Verifique el código o tracking e intente de nuevo.</p>
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
        <article class="tracking-result-card">
            <div class="tracking-result-head">
                <span class="tracking-result-code">{{ $p->warehouse_code ?? $p->tracking_external ?? '—' }}</span>
                @if(($p->status ?? '') === 'CANCELLED')
                <span class="tracking-badge-cancelled">{{ \App\Http\Controllers\Web\TrackingController::statusLabel($p->status) }}</span>
                @endif
            </div>
            <div class="tracking-overview">
                @if($receivedAt)
                <p class="tracking-overview-date">Recibido el {{ $receivedAt->format('d/m/y, h:i a') }}</p>
                @endif
                <div class="tracking-overview-meta">
                    <span><strong>Tracking:</strong> {{ $p->tracking_external ?? '—' }}</span>
                    <span><strong>Guía:</strong> {{ $p->warehouse_code ?? '—' }}</span>
                    <span><strong>{{ $weightStr }}</strong></span>
                    <span>{{ \App\Http\Controllers\Web\TrackingController::serviceLabel($p->service_type ?? '') }}</span>
                </div>
                @if($p->bultos_total && $p->bultos_total > 1)
                <p class="tracking-overview-bulto">Bulto {{ $p->bulto_index }} de {{ $p->bultos_total }}</p>
                @endif
            </div>
            <div class="tracking-timeline">
                @foreach($steps as $index => $step)
                <div class="tracking-timeline-item {{ $step['is_current'] ? 'is-current' : '' }} {{ !$step['is_completed'] ? 'is-pending' : '' }}">
                    <div class="tracking-timeline-indicator">
                        @if($step['key'] === 'DELIVERED' && $step['is_completed'])
                        <span>✓</span>
                        @else
                        <span>{{ $index + 1 }}</span>
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
        </article>
        @endforeach
    </div>
    @endif
</div>

<style>
.pt-kicker { margin: 0 0 0.35rem; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: #1E4FA8; }
.pt-heading { margin: 0 0 0.35rem; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.03em; color: #0A2D6F; }
.pt-lead { margin: 0 0 1.35rem; font-size: 0.9rem; color: #5E6168; line-height: 1.45; }
.guest-label { display: block; font-size: 0.78rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem; }
.guest-input {
    width: 100%; padding: 0.78rem 0.95rem; font-size: 0.95rem;
    border: 1px solid #D8DCE2; border-radius: 0.7rem; background: #F8FAFC;
}
.guest-input:focus { outline: none; background: #fff; border-color: #1E4FA8; box-shadow: 0 0 0 4px rgba(30, 79, 168, 0.14); }
.guest-input::placeholder { color: #94a3b8; }
.guest-submit {
    padding: 0.78rem 1.2rem; font-size: 0.95rem; font-weight: 800; color: #fff; white-space: nowrap;
    background: linear-gradient(180deg, #1E4FA8 0%, #0A2D6F 100%);
    border: none; border-radius: 0.8rem; cursor: pointer;
    box-shadow: 0 10px 22px rgba(10, 45, 111, 0.22);
}
.guest-submit:hover { filter: brightness(1.06); }
.tracking-search-row { display: flex; gap: 0.65rem; align-items: stretch; }
.tracking-search-row .guest-input { flex: 1; min-width: 0; }
.tracking-alert {
    margin-top: 1.15rem; padding: 0.95rem 1.05rem; border-radius: 0.85rem;
    background: #FFF6E8; border: 1px solid #F3D19C; color: #9A6700;
}
.tracking-alert-title { margin: 0 0 0.2rem; font-weight: 800; }
.tracking-alert-text { margin: 0; font-size: 0.85rem; }
.tracking-results { margin-top: 1.35rem; display: grid; gap: 1rem; }
.tracking-results-hint { margin: 0; font-size: 0.85rem; color: #5E6168; }
.tracking-result-card {
    border: 1px solid #E8EBEF; border-radius: 1rem; padding: 1.15rem 1.2rem 1.2rem; background: #fff;
    box-shadow: 0 8px 22px rgba(10, 45, 111, 0.05);
}
.tracking-result-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.85rem; }
.tracking-result-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 800; font-size: 1.05rem; color: #0A2D6F; }
.tracking-badge-cancelled { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 800; background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }
.tracking-overview { padding-bottom: 1rem; margin-bottom: 0.35rem; border-bottom: 1px dashed #E8EBEF; }
.tracking-overview-date { margin: 0 0 0.55rem; font-size: 0.98rem; font-weight: 800; color: #0f172a; }
.tracking-overview-meta { display: flex; flex-wrap: wrap; gap: 0.45rem 1rem; font-size: 0.82rem; color: #5E6168; }
.tracking-overview-meta strong { color: #0f172a; }
.tracking-overview-bulto { margin: 0.5rem 0 0; font-size: 0.8rem; color: #94a3b8; }
.tracking-timeline { display: flex; flex-direction: column; }
.tracking-timeline-item { display: flex; align-items: flex-start; gap: 0.85rem; position: relative; padding: 0.55rem 0; }
.tracking-timeline-item:not(:last-child)::after {
    content: ''; position: absolute; left: 0.87rem; top: 2.15rem; bottom: -0.55rem; width: 2px; background: #E8EBEF;
}
.tracking-timeline-item.is-current:not(:last-child)::after { background: linear-gradient(180deg, #0A2D6F 0%, #E8EBEF 100%); }
.tracking-timeline-indicator {
    flex-shrink: 0; width: 1.85rem; height: 1.85rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 800; background: #F4F8FD; color: #94a3b8; z-index: 1;
}
.tracking-timeline-item.is-current .tracking-timeline-indicator { background: #0A2D6F; color: #fff; }
.tracking-timeline-item.is-pending .tracking-timeline-indicator { background: #F1F5F9; color: #cbd5e1; }
.tracking-timeline-label { font-size: 0.78rem; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
.tracking-timeline-item.is-current .tracking-timeline-label { color: #0A2D6F; }
.tracking-timeline-content { display: flex; flex-direction: column; gap: 0.12rem; }
.tracking-timeline-time { font-size: 0.8rem; color: #5E6168; }
@media (max-width: 520px) {
    .tracking-search-row { flex-direction: column; }
    .guest-submit { width: 100%; }
    .pt-heading { font-size: 1.35rem; }
}
</style>
@endsection
