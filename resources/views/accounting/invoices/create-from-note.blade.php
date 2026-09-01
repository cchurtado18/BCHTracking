@extends('layouts.app')

@section('title', 'Generar factura · '.$deliveryNote->code)

@section('content')
@php
    $agency = $deliveryNote->agency ?? \App\Models\Agency::find($preview['agency_id']);
    $hasAir = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'AIR');
    $hasSea = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'SEA');
    $hasCft = collect($preview['lines'])->contains(fn ($l) => $l['service_type'] === 'CFT');
@endphp
<div class="pt-page">
    <x-module-banner
        section="Contabilidad"
        current="Generar factura"
        title="Generar factura PrimeTrack"
        subtitle="Desde la hoja {{ $deliveryNote->code }}. Confirme tarifas y tipo de cambio; no bloquea entregas futuras."
        back-href="{{ route('accounting.invoices.index') }}"
        back-label="Volver a facturas"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if(session('error'))
    <div class="pt-alert pt-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Cliente (bill-to)</h2>
        </div>
        <div class="pt-card-body">
            <p class="pt-summary-line"><strong>{{ $agency?->name ?? '—' }}</strong> · Código <span class="pt-code">{{ $agency?->code ?? '—' }}</span></p>
            <p class="pt-muted">Paquetes en la hoja de salida: {{ $deliveryNote->deliveries->count() }}</p>
        </div>
    </div>

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Vista previa por servicio</h2>
            <span class="pt-card-badge">{{ count($preview['lines']) }} {{ count($preview['lines']) === 1 ? 'servicio' : 'servicios' }}</span>
        </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Paquetes</th>
                        <th>Cantidad</th>
                        <th>Tarifa sugerida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview['lines'] as $line)
                    <tr>
                        <td>{{ $line['description'] }}</td>
                        <td class="pt-num">{{ $line['package_count'] }}</td>
                        <td class="pt-num">{{ number_format($line['quantity_lbs'], 2) }} {{ $line['unit'] ?? \App\Support\ServiceType::unit($line['service_type']) }}</td>
                        <td class="pt-num">${{ number_format($line['rate_per_lb'], 4) }} / {{ $line['unit'] ?? \App\Support\ServiceType::unit($line['service_type']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="pt-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Tarifas y tipo de cambio</h2>
        </div>
        <div class="pt-card-body">
            <form method="POST" action="{{ route('accounting.invoices.store-from-note', $deliveryNote) }}">
                @csrf
                <div class="pt-fields-grid">
                    @if($hasAir)
                    <div class="pt-field">
                        <label class="pt-label" for="rate_air">Tarifa AIR (USD/lb) *</label>
                        <input type="number" step="0.0001" min="0" name="rate_air" id="rate_air" required
                               value="{{ old('rate_air', $suggestedRates['AIR'] ?? 0) }}" class="pt-input">
                    </div>
                    @endif
                    @if($hasSea)
                    <div class="pt-field">
                        <label class="pt-label" for="rate_sea">Tarifa SEA (USD/lb) *</label>
                        <input type="number" step="0.0001" min="0" name="rate_sea" id="rate_sea" required
                               value="{{ old('rate_sea', $suggestedRates['SEA'] ?? 0) }}" class="pt-input">
                    </div>
                    @endif
                    @if($hasCft)
                    <div class="pt-field">
                        <label class="pt-label" for="rate_cft">Tarifa pie cúbico (USD/pie³) *</label>
                        <input type="number" step="0.0001" min="0" name="rate_cft" id="rate_cft" required
                               value="{{ old('rate_cft', $suggestedRates['CFT'] ?? 0) }}" class="pt-input">
                    </div>
                    @endif
                    <div class="pt-field">
                        <label class="pt-label" for="exchange_rate">T.C. COR por 1 USD *</label>
                        <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="exchange_rate" required
                               value="{{ old('exchange_rate', $exchangeRate) }}" class="pt-input">
                    </div>
                </div>
                <label class="pt-checkbox-row">
                    <input type="checkbox" name="persist_rates" value="1" @checked(old('persist_rates'))>
                    Guardar estas tarifas como vigentes para la agencia
                </label>
                @if(($creditBalance ?? 0) > 0)
                <label class="pt-checkbox-row">
                    <input type="checkbox" name="apply_credit" value="1" @checked(old('apply_credit', true))>
                    Aplicar saldo a favor (${{ number_format($creditBalance, 2) }} USD)
                </label>
                <div class="pt-field" style="max-width:14rem;margin-top:0.5rem">
                    <label class="pt-label" for="apply_credit_amount">Monto de crédito a aplicar</label>
                    <input type="number" step="0.01" min="0" max="{{ $creditBalance }}" name="apply_credit_amount" id="apply_credit_amount" class="pt-input"
                           value="{{ old('apply_credit_amount', number_format(min($creditBalance, (float) $preview['total_usd']), 2, '.', '')) }}">
                </div>
                @endif
                <div class="pt-form-actions">
                    <a href="{{ route('accounting.invoices.create') }}" class="pt-btn pt-btn-secondary">Cancelar</a>
                    <button type="submit" class="pt-btn pt-btn-primary">Emitir factura y abrir voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('partials.primetrack-module-styles')
@endsection
