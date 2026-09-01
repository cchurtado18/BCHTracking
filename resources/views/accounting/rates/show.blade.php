@extends('layouts.app')

@section('title', 'Tarifas · '.$agency->name)

@section('content')
@php
    $serviceMeta = [
        \App\Support\ServiceType::AIR => ['label' => 'Aéreo', 'unit' => 'USD / lb', 'field' => 'price_air'],
        \App\Support\ServiceType::SEA => ['label' => 'Marítimo', 'unit' => 'USD / lb', 'field' => 'price_sea'],
        \App\Support\ServiceType::CFT => ['label' => 'Pie cúbico', 'unit' => 'USD / pie³', 'field' => 'price_cft'],
    ];
@endphp
<div class="pt-page">
    <x-module-banner section="Contabilidad" current="Tarifas" title="{{ $agency->code }} — {{ $agency->name }}" subtitle="Precio que se le cobra a este cliente en cada servicio. Deje vacío el que no vaya a cambiar." back-href="{{ route('accounting.rates.index') }}" back-label="Volver a clientes">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.rates.history', ['agency_id' => $agency->id]) }}" class="mb-btn mb-btn-secondary">Histórico de este cliente</a>
        </x-slot:actions>
        <x-slot:strip>
            <span class="mb-strip-label">Cuenta</span>
            <span class="mb-pill">{{ $agency->typeLabel() }}</span>
            @if($agency->parent)
            <span class="mb-pill">{{ $agency->parent->name }}</span>
            @endif
        </x-slot:strip>
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

    <form method="POST" action="{{ route('accounting.rates.store') }}" class="rt-form">
        @csrf
        <input type="hidden" name="agency_id" value="{{ $agency->id }}">

        <div class="rt-services">
            @foreach($serviceMeta as $service => $meta)
            @php $rate = $current[$service] ?? null; @endphp
            <div class="rt-service">
                <div class="rt-service-head">
                    <span class="rt-service-name">{{ $meta['label'] }}</span>
                    <span class="rt-service-unit">{{ $meta['unit'] }}</span>
                </div>
                <p class="rt-current">
                    @if($rate)
                    Vigente: <strong>${{ number_format((float) $rate->price_per_lb, 2) }}</strong>
                    <span class="pt-muted">desde {{ $rate->effective_from->format('d/m/Y') }}</span>
                    @else
                    <span class="pt-muted">Sin precio definido</span>
                    @endif
                </p>
                <label class="pt-label" for="{{ $meta['field'] }}">Nuevo precio</label>
                <input type="number" step="0.0001" min="0" name="{{ $meta['field'] }}" id="{{ $meta['field'] }}" class="pt-input" value="{{ old($meta['field'], $rate ? number_format((float) $rate->price_per_lb, 4, '.', '') : '') }}" placeholder="0.00">
            </div>
            @endforeach
        </div>

        <div class="pt-card">
            <div class="pt-card-body rt-save-row">
                <div class="pt-field">
                    <label class="pt-label" for="effective_from">Vigente desde</label>
                    <input type="date" name="effective_from" id="effective_from" class="pt-input" value="{{ old('effective_from', now()->toDateString()) }}" required>
                    <p class="pt-hint">Si ya había un precio, el anterior pasa al histórico.</p>
                </div>
                <button type="submit" class="pt-btn pt-btn-primary">Guardar precios</button>
            </div>
        </div>
    </form>
</div>

@include('partials.primetrack-module-styles')
<style>
.rt-services { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1rem; }
.rt-service {
    background: #fff; border: 1px solid #E8EEF8; border-radius: 0.85rem;
    padding: 1.05rem 1.1rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.rt-service-head { display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.45rem; }
.rt-service-name { font-size: 1.05rem; font-weight: 800; color: #0A2D6F; letter-spacing: -0.02em; }
.rt-service-unit { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
.rt-current { margin: 0 0 0.85rem; font-size: 0.875rem; color: #334155; }
.rt-save-row { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; }
.rt-save-row .pt-field { min-width: 12rem; margin: 0; }
@media (max-width: 800px) {
    .rt-services { grid-template-columns: 1fr; }
}
</style>
@endsection
