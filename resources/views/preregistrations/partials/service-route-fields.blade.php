@once
<style>
.preregs-choice-row { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.4rem; }
.preregs-choice {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
    padding: 0.45rem 0.7rem;
    border: 1px solid #C5D4EB;
    border-radius: 0.55rem;
    background: #fff;
    font-size: 0.85rem;
    font-weight: 600;
    color: #0A2D6F;
    cursor: pointer;
}
.preregs-choice:has(input:checked) {
    border-color: #1E4FA8;
    background: #E8EEF8;
}
.prd-service-form .preregs-choice-row { margin-top: 0.35rem; }
.prd-service-form .js-sea-billing-wrap { margin-top: 0.55rem; }
</style>
@endonce
@php
    $selectId = $selectId ?? 'service_type';
    $named = $named ?? false;
    $currentService = $currentService ?? '';
    $oldService = old('service_type', $currentService);
    $routeValue = old('service_route', in_array($oldService, ['SEA', 'CFT'], true) ? 'SEA' : ($oldService === 'AIR' ? 'AIR' : ''));
    $billingValue = old('sea_billing', \App\Support\ServiceType::seaBillingValue($oldService));
    $wrapId = ($selectId === 'service_type_multi') ? 'wrap_sea_billing_multi' : 'wrap_sea_billing';
    $radioName = $named ? 'sea_billing' : ($selectId === 'service_type_multi' ? 'sea_billing_multi' : 'sea_billing_ui');
@endphp
<div class="preregs-field">
    <label for="{{ $selectId }}" class="preregs-field-label">Tipo de servicio <span class="preregs-req">*</span></label>
    <select id="{{ $selectId }}" class="preregs-input preregs-select js-service-route" @if($named) name="service_route" required @endif>
        <option value="" disabled {{ $routeValue ? '' : 'selected' }}>Seleccione un servicio</option>
        <option value="AIR" {{ $routeValue === 'AIR' ? 'selected' : '' }}>Aéreo</option>
        <option value="SEA" {{ $routeValue === 'SEA' ? 'selected' : '' }}>Marítimo</option>
    </select>
    @error('service_type')
    <p class="preregs-field-error">{{ $message }}</p>
    @enderror
    @error('service_route')
    <p class="preregs-field-error">{{ $message }}</p>
    @enderror
</div>
<div class="preregs-field js-sea-billing-wrap" id="{{ $wrapId }}" @if($routeValue !== 'SEA') hidden @endif>
    <span class="preregs-field-label">Marítimo se cobra <span class="preregs-req">*</span></span>
    <div class="preregs-choice-row">
        <label class="preregs-choice">
            <input type="radio" name="{{ $radioName }}" value="LBS" class="js-sea-billing" {{ $billingValue === 'LBS' ? 'checked' : '' }}>
            <span>Por libra (lbs)</span>
        </label>
        <label class="preregs-choice">
            <input type="radio" name="{{ $radioName }}" value="CFT" class="js-sea-billing" {{ $billingValue === 'CFT' ? 'checked' : '' }}>
            <span>Por pie cúbico</span>
        </label>
    </div>
    <p class="preregs-hint">Obligatorio si eligió marítimo. Así la factura usa tarifa por lb o por pie³.</p>
    @error('sea_billing')
    <p class="preregs-field-error">{{ $message }}</p>
    @enderror
</div>
