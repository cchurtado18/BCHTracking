@extends('layouts.app')

@section('title', 'Parámetros de contabilidad')

@section('content')
@php
    $serviceMeta = [
        \App\Support\ServiceType::AIR => ['label' => 'Aéreo', 'unit' => 'USD / lb', 'field' => 'cost_air'],
        \App\Support\ServiceType::SEA => ['label' => 'Marítimo', 'unit' => 'USD / lb', 'field' => 'cost_sea'],
        \App\Support\ServiceType::CFT => ['label' => 'Pie cúbico', 'unit' => 'USD / pie³', 'field' => 'cost_cft'],
    ];
@endphp
<div class="pt-page">
    <x-module-banner section="Contabilidad" current="Parámetros" title="Parámetros" subtitle="Tipo de cambio y costo de operación por servicio.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
        </x-slot:icon>
    </x-module-banner>

    @if(session('success'))
    <div class="pt-alert pt-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="pt-alert pt-alert-danger">
        <ul class="pt-alert-list">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="pt-card">
            <div class="pt-card-header">
                <h2 class="pt-card-title">Tipo de cambio</h2>
            </div>
            <div class="pt-card-body">
                <div class="pt-field" style="max-width:18rem;margin:0;">
                    <label class="pt-label" for="exchange_rate">Córdobas por dólar (C$ / US$)</label>
                    <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="exchange_rate" class="pt-input" value="{{ old('exchange_rate', $settings->exchange_rate) }}" required>
                </div>
            </div>
        </div>

        <p class="pt-section-title">Costo de operación</p>
        <div class="rt-services">
            @foreach($serviceMeta as $service => $meta)
            @php $cost = $currentCosts[$service] ?? null; @endphp
            <div class="rt-service">
                <div class="rt-service-head">
                    <span class="rt-service-name">{{ $meta['label'] }}</span>
                    <span class="rt-service-unit">{{ $meta['unit'] }}</span>
                </div>
                <label class="pt-label" for="{{ $meta['field'] }}">Costo</label>
                <input type="number" step="0.0001" min="0" name="{{ $meta['field'] }}" id="{{ $meta['field'] }}" class="pt-input" value="{{ old($meta['field'], $cost ? number_format((float) $cost->cost_per_unit, 4, '.', '') : '') }}" placeholder="0.00">
            </div>
            @endforeach
        </div>

        <div class="pt-filters-actions">
            <button type="submit" class="pt-btn pt-btn-primary">Guardar</button>
        </div>
    </form>

    <div class="pt-card" style="margin-top:1.75rem;">
        <div class="pt-card-header">
            <h2 class="pt-card-title">Cambios</h2>
        </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cambio</th>
                        <th>Valor</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($changes as $change)
                    <tr>
                        <td class="pt-muted">{{ $change->at->format('d/m/Y') }}</td>
                        <td>{{ $change->label }}</td>
                        <td class="pt-num">{{ $change->value }}</td>
                        <td class="pt-muted">{{ $change->by ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="pt-empty"><p class="pt-empty-text">Todavía no hay cambios.</p></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.primetrack-module-styles')
<style>
.rt-services { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1rem; }
.pt-section-title { margin: 0 0 0.75rem; font-size: 0.9375rem; font-weight: 700; color: #0A2D6F; }
.rt-service {
    background: #fff; border: 1px solid #E8EEF8; border-radius: 0.85rem;
    padding: 1.05rem 1.1rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.rt-service-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.85rem; }
.rt-service-name { font-size: 1.05rem; font-weight: 800; color: #0A2D6F; letter-spacing: -0.02em; }
.rt-service-unit { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
@media (max-width: 800px) {
    .rt-services { grid-template-columns: 1fr; }
}
</style>
@endsection
