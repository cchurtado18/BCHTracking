@extends('layouts.app')

@section('title', 'Histórico de tarifas')

@section('content')
<div class="pt-page">
    <x-module-banner section="Contabilidad" current="Histórico de tarifas" title="Histórico de tarifas" subtitle="Cambios de precio de venta por cliente y servicio." back-href="{{ route('accounting.rates.index') }}" back-label="Volver a clientes">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </x-slot:icon>
    </x-module-banner>

    <div class="pt-card pt-filters-card">
        <div class="pt-card-header">
            <h2 class="pt-card-title">Filtros</h2>
        </div>
        <div class="pt-card-body">
            <form method="GET" action="{{ route('accounting.rates.history') }}" class="pt-filters-form">
                <div class="pt-filters-grid">
                    <div class="pt-field">
                        <label class="pt-label" for="agency_id">Cliente</label>
                        <select name="agency_id" id="agency_id" class="pt-select">
                            <option value="">Todas</option>
                            @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected(request('agency_id') == $agency->id)>{{ $agency->code }} — {{ $agency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-field">
                        <label class="pt-label" for="service_type">Servicio</label>
                        <select name="service_type" id="service_type" class="pt-select">
                            <option value="">Todos</option>
                            <option value="AIR" @selected(request('service_type') === 'AIR')>Aéreo</option>
                            <option value="SEA" @selected(request('service_type') === 'SEA')>Marítimo</option>
                            <option value="CFT" @selected(request('service_type') === 'CFT')>Pie cúbico</option>
                        </select>
                    </div>
                </div>
                <div class="pt-filters-actions">
                    <button class="pt-btn pt-btn-primary" type="submit">Aplicar filtros</button>
                    <a href="{{ route('accounting.rates.history') }}" class="pt-btn pt-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="pt-card pt-table-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Cambios registrados</h2>
            <span class="pt-card-badge">{{ $rates->total() }} {{ $rates->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                    <tr>
                        <td>
                            @if($rate->agency)
                            <a href="{{ route('accounting.rates.show', $rate->agency) }}" class="pt-code">{{ $rate->agency->code }}</a>
                            — {{ $rate->agency->name }}
                            @else
                            —
                            @endif
                        </td>
                        <td class="pt-muted">{{ \App\Support\ServiceType::label($rate->service_type) }}</td>
                        <td class="pt-num">${{ number_format((float) $rate->price_per_lb, 2) }} / {{ \App\Support\ServiceType::unit($rate->service_type) }}</td>
                        <td class="pt-muted">
                            {{ $rate->effective_from->format('d/m/Y') }} — {{ $rate->effective_to ? $rate->effective_to->format('d/m/Y') : 'vigente' }}
                        </td>
                        <td>
                            <span class="pt-badge {{ $rate->effective_to ? 'pt-badge-muted' : 'pt-badge-success' }}">{{ $rate->effective_to ? 'Histórica' : 'Vigente' }}</span>
                        </td>
                        <td class="pt-muted">{{ $rate->createdBy?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="pt-empty">
                            <p class="pt-empty-text">Sin cambios de tarifa con estos filtros.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rates->hasPages())
        <div class="pt-card-footer">
            <div class="pt-pagination-links">{{ $rates->links() }}</div>
        </div>
        @endif
    </div>
</div>

@include('partials.primetrack-module-styles')
@endsection
