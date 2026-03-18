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

    {{-- Información arriba para dar espacio y que el código de barras quede más al centro --}}
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
    <div class="kv">
        <div class="field">Nombre en etiqueta</div>
        <div class="value">{{ $preregistration->label_name }}</div>
    </div>
    <div class="kv kv-3col">
        <div class="kv-col">
            <div class="field">Servicio</div>
            <div class="value label-service label-service-{{ strtolower($preregistration->service_type ?? 'air') }}">{{ $preregistration->service_type === 'AIR' ? 'Aéreo' : 'Marítimo' }}</div>
        </div>
        <div class="kv-col">
            <div class="field">Peso (lbs)</div>
            <div class="value">{{ number_format($preregistration->verified_weight_lbs ?? $preregistration->intake_weight_lbs, 2) }}</div>
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
    @if(!empty($preregistration->dimension))
    <div class="kv">
        <div class="field">Dimensión</div>
        <div class="value">{{ $preregistration->dimension }}@if($preregistration->cubic_feet !== null)&nbsp;·&nbsp;{{ number_format($preregistration->cubic_feet, 2) }} pie³@endif</div>
    </div>
    @endif

    {{-- Código de almacén y código de barras más abajo / al centro --}}
    <div class="barcode-section">
    <div class="field">Código de almacén</div>
    <div class="warehouse-code">{{ $preregistration->warehouse_code }}</div>
    @if($preregistration->warehouse_code)
    <div class="barcode-wrap">
        <canvas id="barcode-{{ $preregistration->id }}" class="barcode-canvas" data-barcode="{{ $preregistration->warehouse_code }}"></canvas>
    </div>
    @endif
    </div>

    <div class="reception-note">
        <div class="title">Nota de recepción en almacén</div>
        <div class="datetime">{{ $preregistration->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</div>
    </div>
</div>
