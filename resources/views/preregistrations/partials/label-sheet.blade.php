<div class="label-sheet">
    <div class="label-header">
        <div class="company-block">
            <div class="company">BCH Tracking</div>
            <div class="company-address">8307 NW 68TH ST 33166</div>
            <div class="company-city">Miami, Florida</div>
        </div>
        @if($preregistration->agency)
            <div class="agency-right">
                <div class="agency-logo-wrap" style="background: transparent;">
                    @php
                        $logoUrl = $preregistration->agency->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($preregistration->agency->logo_path)
                            ? asset('storage/' . $preregistration->agency->logo_path)
                            : null;
                    @endphp
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo {{ $preregistration->agency->name }}" class="agency-logo" style="background: transparent;" onerror="this.style.display='none'; this.nextElementSibling && (this.nextElementSibling.style.display = 'block');">
                        <span class="agency-name-fallback" style="display: none;">{{ $preregistration->agency->name }}</span>
                    @else
                        <span class="agency-name-fallback">{{ $preregistration->agency->name }}</span>
                    @endif
                </div>
                <div class="agency-name-below">{{ $preregistration->agency->name }}</div>
            </div>
        @endif
    </div>

    {{-- Información arriba (solo para llenar la etiqueta sin alargarla de más). --}}

    @if($preregistration->bultos_total && $preregistration->bultos_total > 1)
    <div class="kv">
        <div class="field">Bulto</div>
        <div class="value value-sm">{{ $preregistration->bulto_index }} de {{ $preregistration->bultos_total }}</div>
    </div>
    @endif
    @if($preregistration->intake_type === 'COURIER' || !empty($preregistration->tracking_external))
    <div class="kv">
        <div class="field">Tracking</div>
        <div class="value label-tracking">{{ $preregistration->tracking_external ?? '—' }}</div>
    </div>
    @endif
    @if($preregistration->agency)
    <div class="kv kv-destination">
        <div class="field">Agencia</div>
        <div class="value value-destination">{{ $preregistration->agency->code }} - {{ $preregistration->agency->name }}</div>
    </div>
    @endif

    @php
        // Para que la etiqueta no crezca, mostramos dimensión/pie³ como sublínea dentro de la celda de "PESO".
        $dimension = !empty($preregistration->dimension) ? mb_strtoupper(trim($preregistration->dimension)) : null;
        $cubicFeet = $preregistration->cubic_feet !== null ? number_format((float) $preregistration->cubic_feet, 2) : null;
        $serviceMark = strtoupper((string) ($preregistration->service_type ?? 'AIR')) === 'SEA' ? 'M' : 'A';
        $boxSizeLine = null;
        if ($dimension) {
            $boxSizeLine = $dimension;
            if ($cubicFeet !== null) {
                $boxSizeLine .= ' · ' . $cubicFeet . ' pie³';
            }
        } elseif ($cubicFeet !== null) {
            $boxSizeLine = $cubicFeet . ' pie³';
        }
    @endphp

    <div class="kv">
        <div class="field">Nombre en etiqueta</div>
        <div class="value">{{ $preregistration->label_name }}</div>
    </div>
    <div class="kv kv-3col">
        <div class="kv-col">
            <div class="field">Servicio</div>
            <div class="value label-service label-service-{{ strtolower($preregistration->service_type ?? 'air') }}">
                {{ $preregistration->service_type === 'AIR' ? 'AIR' : 'SEA' }}
            </div>
        </div>
        <div class="kv-col">
            <div class="field">Peso (lbs)</div>
            <div class="value">{{ number_format($preregistration->verified_weight_lbs ?? $preregistration->intake_weight_lbs, 2) }}</div>
            @if(!empty($boxSizeLine))
                <div class="value value-sm" style="margin-top: 2px;">{{ $boxSizeLine }}</div>
            @endif
        </div>
        <div class="kv-col">
            <div class="field">Código</div>
            <div class="value value-code">{{ $preregistration->warehouse_code }}</div>
        </div>
    </div>
    @if(!empty($preregistration->description))
    <div class="kv">
        <div class="field">Descripción</div>
        <div class="value value-sm">{{ Str::limit($preregistration->description, 60) }}</div>
    </div>
    @endif

    {{-- Código de almacén y código de barras más abajo / al centro --}}
    <div class="barcode-section">
    <div class="field">Código de almacén</div>
    <div class="warehouse-code">{{ $preregistration->warehouse_code }}</div>
    @if($preregistration->warehouse_code)
    <div class="barcode-wrap">
        <div class="barcode-row">
            <canvas id="barcode-{{ $preregistration->id }}" class="barcode-canvas" data-barcode="{{ $preregistration->warehouse_code }}"></canvas>
            <span class="service-mark-large" aria-label="Tipo de servicio">{{ $serviceMark }}</span>
        </div>
    </div>
    @endif
    </div>

    {{-- Bloque inferior: RECIBIDO EN ALMACÉN (fecha + hora) --}}
    @php
        $displayTz = config('app.display_timezone') ?: 'America/New_York';
        $dt = $preregistration->created_at ? $preregistration->created_at->timezone($displayTz) : null;
    @endphp
    <div class="reception-note">
        <div class="title">RECIBIDO EN ALMACÉN</div>
        <div class="divider"></div>
        <div class="datetime">
            {{ $dt ? $dt->format('d/m/Y') : '—' }} - {{ $dt ? $dt->format('H.i') : '' }}
        </div>
    </div>

    <div class="label-review-text">Revise su paquete antes de retirarlo</div>

</div>
