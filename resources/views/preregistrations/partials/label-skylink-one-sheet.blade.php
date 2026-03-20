@php
    $agency = $preregistration->agency;
    $logoUrl = $agency?->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo_path)
        ? asset('storage/' . $agency->logo_path)
        : null;

    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $dt = $preregistration->created_at ? $preregistration->created_at->timezone($displayTz) : null;

    $tracking = trim((string) ($preregistration->tracking_external ?? ''));
    // Drop Off: si este bulto no trae tracking, usar tracking del grupo (mismo warehouse) si existe.
    if ($tracking === '' && !empty($preregistration->warehouse_code) && !empty($preregistration->bultos_total) && $preregistration->bultos_total > 1) {
        $groupTracking = \App\Models\Preregistration::where('warehouse_code', $preregistration->warehouse_code)
            ->whereNotNull('tracking_external')
            ->where('tracking_external', '!=', '')
            ->orderBy('bulto_index')
            ->value('tracking_external');
        $tracking = trim((string) ($groupTracking ?? ''));
    }
    if ($tracking === '') {
        $tracking = '—';
    }
    $destination = $preregistration->label_name ?? '—';
    $bultoBadge = ($preregistration->bultos_total && $preregistration->bultos_total > 1)
        ? (($preregistration->bulto_index ?? 1) . ' de ' . $preregistration->bultos_total)
        : null;
    $serviceLabel = $preregistration->service_type === 'SEA' ? 'SEA' : 'AIR';
    $serviceClass = $preregistration->service_type === 'SEA' ? 'service-sea' : 'service-air';
    $serviceMark = $preregistration->service_type === 'SEA' ? 'M' : 'A';
    $weight = number_format((float) ($preregistration->verified_weight_lbs ?? $preregistration->intake_weight_lbs ?? 0), 2);
    $cubicFeetValue = $preregistration->cubic_feet !== null ? number_format((float) $preregistration->cubic_feet, 2) : null;
    $descriptionValue = !empty($preregistration->description) ? mb_strtoupper(trim($preregistration->description)) : '—';
@endphp

<div class="label-sheet">
    <div class="sl-top-logo">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo {{ $agency?->name ?? 'Agencia' }}">
        @else
            <div style="font-size:16px;font-weight:800;color:#111;">{{ $agency?->name ?? 'AGENCIA' }}</div>
        @endif
    </div>

    <div class="sl-divider"></div>

    <div class="sl-agency-box">
        <div class="sl-agency-title">AGENCIA</div>
        <div class="sl-agency-value">
            @if(!empty($agency?->code))
                {{ $agency->code }} - {{ $agency?->name ?? '—' }}
            @else
                {{ $agency?->name ?? '—' }}
            @endif
        </div>
        @if($bultoBadge)
            <div class="sl-bulto-badge">{{ $bultoBadge }}</div>
        @endif
    </div>

    <div class="sl-warehouse-title">CÓDIGO DE ALMACÉN</div>
    <div class="sl-warehouse-code">{{ $preregistration->warehouse_code }}</div>

    <div class="sl-barcode-wrap">
        @if($preregistration->warehouse_code)
            <div class="sl-barcode-row">
                <canvas id="barcode-{{ $preregistration->id }}-skylink" class="barcode-canvas" data-barcode="{{ $preregistration->warehouse_code }}"></canvas>
                <span class="sl-service-mark-large" aria-label="Tipo de servicio">{{ $serviceMark }}</span>
            </div>
        @endif
    </div>

    <div class="sl-tracking-global-label sl-tracking-global-label-after">TRACKING GLOBAL</div>
    <div class="sl-tracking-row">
        <div class="sl-tracking-global-value sl-tracking-global-value-after">{{ $tracking }}</div>
        @if($cubicFeetValue !== null)
            <div class="sl-tracking-cubic">{{ $cubicFeetValue }} pie³</div>
        @endif
    </div>

    <div class="sl-destination-title">DESTINATARIO</div>
    <div class="sl-destination-name">{{ $destination }}</div>

    <div class="sl-grid-3">
        <div class="sl-grid-cell">
            <div class="sl-grid-title">SERVICIO</div>
            <div class="sl-grid-value sl-service-badge {{ $serviceClass }}">{{ $serviceLabel }}</div>
        </div>
        <div class="sl-grid-cell">
            <div class="sl-grid-title">PESO</div>
            <div class="sl-grid-value">{{ $weight }} lbs</div>
        </div>
        <div class="sl-grid-cell">
            <div class="sl-grid-title">RECEPCIÓN</div>
            <div class="sl-code-mini-date">{{ $dt ? $dt->format('d/m/Y') : '—' }}</div>
        </div>
    </div>

    <div class="sl-description-title">DESCRIPCIÓN</div>
    <div class="sl-description-value">{{ $descriptionValue }}</div>

    <div class="sl-review-text">Revise su paquete antes de retirarlo</div>
</div>

