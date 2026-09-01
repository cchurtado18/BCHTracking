@extends('layouts.app')

@section('title', 'Tarifas por cliente')

@section('content')
<div class="pt-page">
    <x-module-banner section="Contabilidad" current="Tarifas" title="Tarifas por cliente" subtitle="Seleccione un cliente para definir el precio de aéreo, marítimo y pie cúbico. El costo de operación se carga en rentabilidad.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.rates.history') }}" class="mb-btn mb-btn-secondary">Histórico</a>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="pt-alert pt-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="pt-alert pt-alert-danger">{{ session('error') }}</div>
    @endif

    <div class="pt-stats">
        <div class="pt-stat-card pt-stat-total">
            <span class="pt-stat-label">Clientes</span>
            <span class="pt-stat-value">{{ number_format($agencies->total()) }}</span>
        </div>
        <div class="pt-stat-card pt-stat-paid">
            <span class="pt-stat-label">Con los 3 servicios</span>
            <span class="pt-stat-value">{{ number_format($complete) }}</span>
        </div>
        <div class="pt-stat-card pt-stat-pending">
            <span class="pt-stat-label">Falta algún precio</span>
            <span class="pt-stat-value">{{ number_format($pending) }}</span>
        </div>
    </div>

    <div class="pt-card pt-filters-card">
        <div class="pt-card-body">
            <form method="GET" action="{{ route('accounting.rates.index') }}" class="pt-filters-form">
                <div class="pt-filters-grid">
                    <div class="pt-field pt-field-search">
                        <label class="pt-label" for="search">Cliente</label>
                        <input type="text" name="search" id="search" class="pt-input" value="{{ request('search') }}" placeholder="Nombre o código…">
                    </div>
                </div>
                <div class="pt-filters-actions">
                    <button class="pt-btn pt-btn-primary" type="submit">Buscar</button>
                    <a href="{{ route('accounting.rates.index') }}" class="pt-btn pt-btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="pt-card pt-table-card">
        <div class="pt-card-header pt-table-header">
            <h2 class="pt-card-title">Clientes</h2>
            <span class="pt-card-badge">{{ $agencies->total() }} {{ $agencies->total() === 1 ? 'cuenta' : 'cuentas' }}</span>
        </div>
        <div class="pt-table-wrap">
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Aéreo</th>
                        <th>Marítimo</th>
                        <th>Pie cúbico</th>
                        <th class="pt-th-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agencies as $agency)
                    @php
                        $rates = $currentByAgency->get($agency->id, collect())->keyBy('service_type');
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('accounting.rates.show', $agency) }}" class="pt-code">{{ $agency->code }}</a>
                            <div>{{ $agency->name }}</div>
                            @if($agency->parent)
                            <div class="pt-muted">{{ $agency->parent->name }}</div>
                            @endif
                        </td>
                        <td><span class="pt-badge pt-badge-muted">{{ $agency->typeLabel() }}</span></td>
                        @foreach(\App\Support\ServiceType::ALL as $service)
                        <td>
                            @if($rate = $rates->get($service))
                            <span class="pt-num">${{ number_format((float) $rate->price_per_lb, 2) }} / {{ \App\Support\ServiceType::unit($service) }}</span>
                            @else
                            <span class="pt-muted">Sin precio</span>
                            @endif
                        </td>
                        @endforeach
                        <td class="pt-actions">
                            <a href="{{ route('accounting.rates.show', $agency) }}" class="pt-btn pt-btn-primary pt-btn-sm">Definir precios</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="pt-empty">
                            <p class="pt-empty-text">No hay clientes para mostrar.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agencies->hasPages())
        <div class="pt-card-footer">
            <div class="pt-pagination-links">{{ $agencies->links('vendor.pagination.primetrack') }}</div>
        </div>
        @endif
    </div>
</div>

@include('partials.primetrack-module-styles')
@endsection
