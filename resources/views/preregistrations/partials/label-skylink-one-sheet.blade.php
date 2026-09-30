@php
    $account = $preregistration->agency;
    $agency = $account?->labelBrandAgency() ?? $account;
    $logoUrl = $agency?->logo_url;
    $providerLogo = asset('images/primetrack-group-logo.png').'?v=2';

    $displayTz = config('app.display_timezone') ?: 'America/New_York';
    $dt = $preregistration->created_at ? $preregistration->created_at->timezone($displayTz) : null;

    $tracking = trim((string) ($preregistration->tracking_external ?? ''));
    // Drop Off: si este bulto no trae tracking, usar tracking del grupo (mismo warehouse) si existe.
    if ($tracking === '' && ! empty($preregistration->warehouse_code) && ! empty($preregistration->bultos_total) && $preregistration->bultos_total > 1) {
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
        ? (($preregistration->bulto_index ?? 1).' de '.$preregistration->bultos_total)
        : null;
    $serviceLabel = match ($preregistration->service_type) {
        'SEA' => 'SEA',
        'CFT' => 'CFT',
        default => 'AIR',
    };
    $serviceClass = \App\Support\ServiceType::route($preregistration->service_type) === 'SEA' ? 'service-sea' : 'service-air';
    $serviceMark = \App\Support\ServiceType::routeMark($preregistration->service_type);
    $weight = number_format((float) ($preregistration->verified_weight_lbs ?? $preregistration->intake_weight_lbs ?? 0), 2);
    $cubicFeetValue = $preregistration->cubic_feet !== null ? number_format((float) $preregistration->cubic_feet, 2) : null;
    $descriptionValue = ! empty($preregistration->description) ? mb_strtoupper(trim($preregistration->description)) : '—';
@endphp

<div class="label-sheet">
    <div class="sl-header">
        <div class="sl-header-provider">
            <img src="{{ $providerLogo }}" alt="PrimeTrack Group">
            <div class="sl-header-address">8307 NW 68th St<br>Miami, FL 33166</div>
        </div>
        <div class="sl-header-rule" aria-hidden="true"></div>
        <div class="sl-header-agency">
            <div class="sl-header-agency-frame">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo {{ $agency?->name ?? 'Agencia' }}">
                @else
                    <div class="sl-header-agency-name">{{ $agency?->name ?? 'AGENCIA' }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="sl-warehouse-title">
        CÓDIGO DE ALMACÉN
        @if($bultoBadge)
            <span class="sl-bulto-badge">{{ $bultoBadge }}</span>
        @endif
    </div>
    <div class="sl-warehouse-code">{{ $preregistration->warehouse_code }}</div>

    <div class="sl-barcode-wrap">
        @if($preregistration->warehouse_code)
            <div class="sl-barcode-row">
                <canvas id="barcode-{{ $preregistration->id }}-skylink" class="barcode-canvas" data-barcode="{{ $preregistration->warehouse_code }}"></canvas>
                <span class="sl-service-mark-large" aria-label="Tipo de servicio">{{ $serviceMark }}</span>
            </div>
        @endif
    </div>

    <div class="sl-meta-2">
        <div class="sl-meta-col">
            <div class="sl-tracking-global-label">TRACKING GLOBAL</div>
            <div class="sl-tracking-global-value">{{ $tracking }}</div>
            @if($cubicFeetValue !== null)
                <div class="sl-tracking-cubic">{{ $cubicFeetValue }} pie³</div>
            @endif
        </div>
        <div class="sl-meta-col">
            <div class="sl-destination-title">DESTINATARIO</div>
            <div class="sl-destination-name">{{ $destination }}</div>
        </div>
    </div>

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

    <div class="sl-care-row" aria-hidden="true">
        <div class="sl-care">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0-10 3.5 3.5M12 4 8.5 7.5M5 16h14v4H5z"/></svg>
            <span>Este lado arriba</span>
        </div>
        <div class="sl-care">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M9 17h6M8 3h8l1 9c0 2.2-2 5-5 5s-5-2.8-5-5L8 3z"/></svg>
            <span>Frágil</span>
        </div>
        <div class="sl-care">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2M5.6 6.2l1.4 1.4M3 13h2m14 0h2M17 7.6l1.4-1.4M8 15a4 4 0 1 1 8 0c0 2.2-1.6 3.4-4 5.8C9.6 18.4 8 17.2 8 15z"/></svg>
            <span>Mantener seco</span>
        </div>
        <div class="sl-care">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V8a2 2 0 1 1 4 0v3m5 0V9a2 2 0 1 1 4 0v6a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2a2 2 0 1 1 4 0"/></svg>
            <span>Manejar con cuidado</span>
        </div>
    </div>
</div>
