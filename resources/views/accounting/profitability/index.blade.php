@extends('layouts.app')

@section('title', 'Reporte de Rentabilidad')

@section('content')
@php
    $serviceLabels = \App\Support\ServiceType::options();
    $qs = fn (array $extra = []) => http_build_query(array_filter(['agency_id' => $agencyId] + $extra, fn ($v) => $v !== null && $v !== ''));
@endphp
<div class="rp-page">
    <x-module-banner section="Contabilidad" current="Rentabilidad" title="Reporte de Rentabilidad" subtitle="Ganancia por cliente según la tarifa vigente en cada salida, gastos extras y resultado neto del período.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.rates.index') }}" class="mb-btn mb-btn-secondary">Tarifas por cliente</a>
            <a href="{{ route('accounting.settings.edit') }}" class="mb-btn mb-btn-secondary">Parámetros</a>
            <a href="{{ route('accounting.expenses.index') }}" class="mb-btn mb-btn-secondary">Gastos</a>
        </x-slot:actions>
        <x-slot:strip>
            @if($activeCosts->isNotEmpty())
            <span class="mb-strip-label">Costo de operación vigente</span>
            @if($activeCosts->has('AIR'))
            <span class="mb-pill">Aéreo <strong>${{ number_format($activeCosts['AIR'], 2) }}</strong></span>
            @endif
            @if($activeCosts->has('SEA'))
            <span class="mb-pill">Marítimo <strong>${{ number_format($activeCosts['SEA'], 2) }}</strong></span>
            @endif
            @if($activeCosts->has('CFT'))
            <span class="mb-pill">Pie cúbico <strong>${{ number_format($activeCosts['CFT'], 2) }}</strong></span>
            @endif
            @endif
        </x-slot:strip>
    </x-module-banner>

    @if(session('success'))
    <div class="rp-alert rp-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Período --}}
    <div class="rp-card rp-period-card">
        <div class="rp-period-head">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5a1 1 0 0 1 1-1h16a1 1 0 0 1 .78 1.625L15 12.36V19a1 1 0 0 1-1.447.894l-4-2A1 1 0 0 1 9 17v-4.64L3.22 5.625A1 1 0 0 1 3 5Z"/></svg>
            Período
        </div>
        <div class="rp-period-body">
            <div class="rp-period-presets">
                <a href="{{ route('accounting.profitability.index') }}{{ $qs() ? '?'.$qs() : '' }}" class="rp-preset {{ $preset === 'this_month' ? 'is-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    Este mes
                </a>
                <a href="{{ route('accounting.profitability.index') }}?{{ $qs(['period' => 'last_month']) }}" class="rp-preset {{ $preset === 'last_month' ? 'is-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    Mes anterior
                </a>
                <a href="{{ route('accounting.profitability.index') }}?{{ $qs(['period' => 'last_30']) }}" class="rp-preset {{ $preset === 'last_30' ? 'is-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    Últimos 30 días
                </a>
                <a href="{{ route('accounting.profitability.index') }}?{{ $qs(['period' => 'quarter']) }}" class="rp-preset {{ $preset === 'quarter' ? 'is-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    Trimestre
                </a>
                <a href="{{ route('accounting.profitability.index') }}?{{ $qs(['period' => 'year']) }}" class="rp-preset {{ $preset === 'year' ? 'is-active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    Año
                </a>
            </div>
            <form method="GET" action="{{ route('accounting.profitability.index') }}" class="rp-period-form">
                <div class="rp-field">
                    <label class="rp-label" for="from">Desde</label>
                    <input type="date" name="from" id="from" class="rp-input" value="{{ $preset === 'custom' ? $from->toDateString() : '' }}">
                </div>
                <div class="rp-field">
                    <label class="rp-label" for="to">Hasta</label>
                    <input type="date" name="to" id="to" class="rp-input" value="{{ $preset === 'custom' ? $to->toDateString() : '' }}">
                </div>
                <div class="rp-field">
                    <label class="rp-label" for="agency_id">Cliente</label>
                    <select name="agency_id" id="agency_id" class="rp-input">
                        <option value="">Todos</option>
                        @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected($agencyId === $agency->id)>{{ $agency->code }} — {{ $agency->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($preset !== 'custom' && $preset !== 'this_month')
                <input type="hidden" name="period" value="{{ $preset }}">
                @endif
                <button type="submit" class="rp-btn rp-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Aplicar
                </button>
            </form>
        </div>
        <p class="rp-period-note">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
            {{ $periodLabel }}
        </p>
    </div>

    {{-- Comparativo --}}
    <div class="rp-section-head">
        <span class="rp-section-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
        </span>
        <h2 class="rp-section-title">Comparativo del período</h2>
        <span class="rp-section-badge">{{ $compareLabel }}</span>
    </div>

    <div class="rp-kpis">
        @foreach($kpis as $kpi)
        @php
            $isCost = in_array($kpi['label'], ['Costo operativo', 'Gastos extras'], true);
            $deltaGood = $kpi['delta'] !== null ? ($isCost ? $kpi['delta'] <= 0 : $kpi['delta'] >= 0) : null;
        @endphp
        <div class="rp-kpi-card">
            <div class="rp-kpi-top">
                <span class="rp-kpi-icon rp-kpi-icon--{{ $kpi['icon'] }}" aria-hidden="true">
                    @switch($kpi['icon'])
                        @case('scale')
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                            @break
                        @case('dollar')
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/></svg>
                            @break
                        @case('minus')
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            @break
                        @case('trend')
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                            @break
                        @case('receipt')
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                            @break
                        @default
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3"/></svg>
                    @endswitch
                </span>
                <span class="rp-kpi-label">{{ $kpi['label'] }}</span>
                @if($kpi['delta'] !== null)
                <span class="rp-kpi-delta {{ $deltaGood ? 'is-good' : 'is-bad' }}">
                    {{ $kpi['delta'] >= 0 ? '↑' : '↓' }} {{ number_format($kpi['delta'], 1) }}%
                </span>
                @endif
            </div>
            <div class="rp-kpi-value {{ $kpi['label'] === 'Resultado neto' ? ($kpi['current'] >= 0 ? 'rp-text-green' : 'rp-text-red') : '' }}">
                @if($kpi['type'] === 'lb')
                    {{ number_format($kpi['current'], $kpi['current'] == (int) $kpi['current'] ? 0 : 1) }} <small>lb</small>
                @else
                    ${{ number_format($kpi['current'], 2) }}
                @endif
            </div>
            <div class="rp-kpi-prev">
                <span>Período ant.</span>
                <span>
                    @if($kpi['type'] === 'lb')
                        {{ number_format($kpi['previous'], $kpi['previous'] == (int) $kpi['previous'] ? 0 : 1) }} lb
                    @else
                        ${{ number_format($kpi['previous'], 2) }}
                    @endif
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Punto de equilibrio + proyección --}}
    <div class="rp-highlights {{ $projection ? '' : 'rp-highlights--single' }}">
        <div class="rp-highlight rp-highlight--green">
            <div class="rp-highlight-head">
                <span class="rp-highlight-icon rp-highlight-icon--green" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25a5.25 5.25 0 1 0 0-10.5 5.25 5.25 0 0 0 0 10.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 13.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                </span>
                <h3 class="rp-highlight-title">Punto de equilibrio del período</h3>
            </div>
            @if($breakeven->reached)
            <p class="rp-highlight-lead rp-text-green">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <strong>¡Ya superaste el equilibrio!</strong> Cada libra adicional es ganancia neta directa.
            </p>
            <p class="rp-highlight-text">Llevás <strong>{{ number_format($totals->lbs, $totals->lbs == (int) $totals->lbs ? 0 : 1) }} libras</strong> y tu ganancia bruta ya cubrió los <strong>${{ number_format($expensesTotal, 2) }}</strong> de gastos extras.</p>
            @else
            <p class="rp-highlight-lead rp-text-amber">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                <strong>Aún no cubrís los gastos.</strong> Faltan ${{ number_format($breakeven->gap, 2) }} de ganancia bruta.
            </p>
            <p class="rp-highlight-text">
                Ganancia bruta actual: <strong>${{ number_format($totals->margin, 2) }}</strong> vs gastos extras de <strong>${{ number_format($expensesTotal, 2) }}</strong>.
                @if($breakeven->lbsNeeded)
                Al margen promedio actual necesitás <strong>~{{ number_format($breakeven->lbsNeeded) }} libras más</strong> para alcanzar el equilibrio.
                @endif
            </p>
            @endif
        </div>
        @if($projection)
        <div class="rp-highlight rp-highlight--blue">
            <div class="rp-highlight-head">
                <span class="rp-highlight-icon rp-highlight-icon--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                </span>
                <h3 class="rp-highlight-title">Proyección al cierre del mes</h3>
            </div>
            <p class="rp-highlight-text rp-mb-sm">Día <strong>{{ $projection->day }}</strong> de {{ $projection->daysInMonth }} ({{ number_format($projection->pct, 1) }}% del mes)</p>
            <div class="rp-proj-rows">
                <div class="rp-proj-row"><span>Libras proyectadas:</span><strong>{{ number_format($projection->lbs, $projection->lbs == (int) $projection->lbs ? 0 : 1) }} lb</strong></div>
                <div class="rp-proj-row"><span>Ingreso proyectado:</span><strong class="rp-text-blue">${{ number_format($projection->revenue, 2) }}</strong></div>
                <div class="rp-proj-row"><span>Ganancia bruta:</span><strong class="rp-text-green">${{ number_format($projection->margin, 2) }}</strong></div>
            </div>
            <div class="rp-proj-net">
                <span class="rp-proj-net-label">Resultado neto estimado al cierre</span>
                <span class="rp-proj-net-value {{ $projection->net >= 0 ? 'rp-text-green' : 'rp-text-red' }}">${{ number_format($projection->net, 2) }}</span>
            </div>
        </div>
        @endif
    </div>

    @if($withoutRateLbs > 0)
    <div class="rp-alert rp-alert-warning">
        Hay <strong>{{ number_format($withoutRateLbs, 2) }} lbs</strong> entregadas sin tarifa vigente en la fecha de salida; no suman a ingreso ni costo.
        <a href="{{ route('accounting.rates.index') }}">Registrar tarifas</a>.
    </div>
    @endif

    {{-- Rentabilidad por cliente --}}
    <div class="rp-card">
        <div class="rp-table-head">
            <div class="rp-table-head-left">
                <span class="rp-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                </span>
                <h2 class="rp-section-title">Rentabilidad por cliente</h2>
            </div>
            <span class="rp-table-head-note">{{ $clients->count() }} cliente(s) con actividad en el período</span>
        </div>
        <div class="rp-table-scroll">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th class="rp-th-rank">#</th>
                        <th>Cliente</th>
                        <th class="rp-num">Paquetes</th>
                        <th class="rp-num">Libras</th>
                        <th class="rp-num">Tarifa prom.</th>
                        <th class="rp-num">Ingreso</th>
                        <th class="rp-num">Costo op.</th>
                        <th class="rp-num">Ganancia</th>
                        <th class="rp-num">Margen</th>
                        <th>Estado</th>
                        <th class="rp-th-actions">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $i => $client)
                    <tr>
                        <td>
                            <span class="rp-rank {{ $i === 0 ? 'rp-rank--gold' : ($i === 1 ? 'rp-rank--silver' : ($i === 2 ? 'rp-rank--bronze' : '')) }}">{{ $i + 1 }}</span>
                        </td>
                        <td>
                            <div class="rp-client-name">{{ $client->agency->name }}</div>
                            <div class="rp-client-services">
                                @foreach($client->services as $service => $lbs)
                                <span class="rp-service-badge rp-service-badge--{{ strtolower($service) }}">
                                    {{ \App\Support\ServiceType::icon($service) }} {{ $serviceLabels[$service] ?? $service }}: {{ number_format($lbs, $lbs == (int) $lbs ? 0 : 1) }} {{ \App\Support\ServiceType::unit($service) }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="rp-num rp-muted">{{ number_format($client->packages) }}</td>
                        <td class="rp-num">{{ number_format($client->lbs, $client->lbs == (int) $client->lbs ? 0 : 1) }}</td>
                        <td class="rp-num rp-muted">{{ $client->avg_rate !== null ? '$'.number_format($client->avg_rate, 2).'/lb' : '—' }}</td>
                        <td class="rp-num"><strong class="rp-text-blue">${{ number_format($client->revenue, 2) }}</strong></td>
                        <td class="rp-num rp-muted">${{ number_format($client->cost, 2) }}</td>
                        <td class="rp-num"><strong class="{{ $client->margin >= 0 ? 'rp-text-green' : 'rp-text-red' }}">${{ number_format($client->margin, 2) }}</strong></td>
                        <td class="rp-num">
                            @if($client->margin_pct !== null)
                            <strong class="{{ $client->margin_pct >= 15 ? 'rp-text-green' : ($client->margin_pct > 0 ? 'rp-text-amber' : 'rp-text-red') }}">{{ number_format($client->margin_pct, 1) }}%</strong>
                            @else
                            <span class="rp-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($client->missing_rate)
                            <span class="rp-status rp-status--red">✕ Sin tarifa</span>
                            @elseif($client->margin_pct === null)
                            <span class="rp-status">—</span>
                            @elseif($client->margin_pct >= 15)
                            <span class="rp-status rp-status--green">● Saludable</span>
                            @elseif($client->margin_pct > 0)
                            <span class="rp-status rp-status--amber">▲ Margen bajo</span>
                            @else
                            <span class="rp-status rp-status--red">▼ Pérdida</span>
                            @endif
                        </td>
                        <td class="rp-actions">
                            <a href="{{ route('accounting.profitability.show', array_merge(['agency' => $client->agency->id], $preset === 'custom' ? ['from' => $from->toDateString(), 'to' => $to->toDateString()] : ($preset !== 'this_month' ? ['period' => $preset] : []))) }}" class="rp-action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="rp-empty">No hay salidas entregadas en el período seleccionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.rp-page {
    --rp-navy: #0A2D6F;
    --rp-blue: #1E4FA8;
    --rp-green: #16794C;
    --rp-green-bright: #2BB673;
    --rp-red: #D64545;
    --rp-amber: #B27A0E;
    --rp-line: #E8EEF8;
    --rp-border: #C5D4EB;
    --rp-soft: #F4F8FD;
    --rp-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

/* Header */
.rp-header {
    background: #fff;
    border: 1px solid var(--rp-line);
    border-radius: 1rem;
    padding: 1.05rem 1.25rem 1rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.rp-header-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.rp-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.45rem;
}
.rp-breadcrumb strong { color: #334155; font-weight: 700; }
.rp-title-row { display: flex; align-items: center; gap: 0.6rem; }
.rp-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    background: linear-gradient(135deg, #16794C, #2BB673);
    color: #fff;
    box-shadow: 0 6px 14px rgba(43, 182, 115, 0.32);
    flex-shrink: 0;
}
.rp-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.rp-subtitle {
    margin: 0.4rem 0 0;
    font-size: 0.875rem;
    color: var(--rp-muted);
    line-height: 1.45;
    max-width: 44rem;
}
.rp-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; justify-content: flex-end; }

.rp-costs-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.95rem;
    padding-top: 0.85rem;
    border-top: 1px solid var(--rp-line);
}
.rp-costs-label {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
}
.rp-cost-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.26rem 0.7rem;
    border-radius: 999px;
    background: #EFFAF4;
    border: 1px solid #A7DFC3;
    color: #116039;
    font-size: 0.75rem;
    font-weight: 600;
}
.rp-cost-pill strong { font-weight: 800; }

/* Botones */
.rp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.rp-btn-primary {
    background: var(--rp-navy);
    color: #fff;
    border-color: var(--rp-navy);
    box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25);
}
.rp-btn-primary:hover { background: var(--rp-blue); border-color: var(--rp-blue); color: #fff; transform: translateY(-1px); }
.rp-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.rp-btn-secondary:hover { background: var(--rp-soft); color: var(--rp-navy); border-color: var(--rp-border); }
.rp-btn-outline-amber { background: #fff; color: #92610B; border-color: #F0D48A; }
.rp-btn-outline-amber:hover { background: #FDF7E8; border-color: #DFAF33; color: #7A5109; }
.rp-btn-outline-red { background: #fff; color: #B03030; border-color: #F6C9C9; }
.rp-btn-outline-red:hover { background: #FDECEC; border-color: #E89A9A; color: #8F2424; }

/* Alertas */
.rp-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1.15rem; font-size: 0.875rem; }
.rp-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.rp-alert-warning { background: #FDF7E8; border: 1px solid #F0D48A; color: #7A5109; }
.rp-alert-warning a { color: #7A5109; font-weight: 700; }

/* Cards */
.rp-card {
    background: #fff;
    border: 1px solid var(--rp-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}

/* Período */
.rp-period-card { padding: 0.95rem 1.15rem 0.85rem; overflow: visible; }
.rp-period-head {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 0.7rem;
}
.rp-period-body {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.9rem;
}
.rp-period-presets { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.rp-preset {
    display: inline-flex;
    align-items: center;
    gap: 0.38rem;
    padding: 0.5rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: 0.55rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #475569;
    text-decoration: none;
    white-space: nowrap;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.rp-preset:hover { background: var(--rp-soft); color: var(--rp-navy); border-color: var(--rp-border); }
.rp-preset.is-active {
    background: var(--rp-navy);
    color: #fff;
    border-color: var(--rp-navy);
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.25);
}
.rp-period-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.6rem; }
.rp-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 0; }
.rp-label {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
}
.rp-input {
    padding: 0.52rem 0.7rem;
    font-size: 0.85rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
    box-sizing: border-box;
}
.rp-input:focus { outline: none; border-color: var(--rp-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.rp-period-note {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0.7rem 0 0;
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Sección */
.rp-section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
    margin: 0 0.15rem 0.75rem;
}
.rp-section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: var(--rp-soft);
    border: 1px solid var(--rp-line);
    color: var(--rp-navy);
    flex-shrink: 0;
}
.rp-section-title { margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a; }
.rp-section-badge {
    font-size: 0.66rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    color: #64748b;
    background: var(--rp-soft);
    border: 1px solid var(--rp-line);
    border-radius: 999px;
    padding: 0.24rem 0.65rem;
}

/* KPIs */
.rp-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.7rem;
    margin-bottom: 1.15rem;
}
.rp-kpi-card {
    background: #fff;
    border: 1px solid var(--rp-line);
    border-radius: 0.8rem;
    padding: 0.8rem 0.9rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    min-width: 0;
}
.rp-kpi-top { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.rp-kpi-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 0.45rem;
    flex-shrink: 0;
}
.rp-kpi-icon--scale { background: #EAF1FC; color: var(--rp-blue); }
.rp-kpi-icon--dollar { background: #EAF1FC; color: var(--rp-navy); }
.rp-kpi-icon--minus { background: #F1F5F9; color: #475569; }
.rp-kpi-icon--trend { background: #EFFAF4; color: var(--rp-green); }
.rp-kpi-icon--receipt { background: #FDECEC; color: var(--rp-red); }
.rp-kpi-icon--wallet { background: #EFFAF4; color: var(--rp-green); }
.rp-kpi-label {
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    line-height: 1.2;
    flex: 1;
    min-width: 0;
}
.rp-kpi-delta {
    font-size: 0.64rem;
    font-weight: 800;
    padding: 0.14rem 0.42rem;
    border-radius: 999px;
    white-space: nowrap;
}
.rp-kpi-delta.is-good { background: #EFFAF4; color: #116039; }
.rp-kpi-delta.is-bad { background: #FDECEC; color: #B03030; }
.rp-kpi-value {
    font-size: 1.28rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.rp-kpi-value small { font-size: 0.72rem; font-weight: 700; color: #94a3b8; }
.rp-kpi-prev {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.68rem;
    color: #94a3b8;
    border-top: 1px dashed var(--rp-line);
    padding-top: 0.42rem;
    font-variant-numeric: tabular-nums;
}

/* Highlights */
.rp-highlights {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
    margin-bottom: 1.15rem;
}
.rp-highlights--single { grid-template-columns: minmax(0, 1fr); }
.rp-highlight {
    border-radius: 0.85rem;
    padding: 1rem 1.15rem 1.1rem;
    background: #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.rp-highlight--green { border: 1px solid #A7DFC3; background: linear-gradient(180deg, #fff 55%, #F2FBF6 130%); }
.rp-highlight--blue { border: 1px solid #BBD3F5; background: linear-gradient(180deg, #fff 55%, #F1F6FD 130%); }
.rp-highlight-head { display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.7rem; }
.rp-highlight-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.55rem;
    color: #fff;
    flex-shrink: 0;
}
.rp-highlight-icon--green { background: linear-gradient(135deg, #16794C, #2BB673); box-shadow: 0 5px 12px rgba(43, 182, 115, 0.3); }
.rp-highlight-icon--blue { background: linear-gradient(135deg, #1E4FA8, #4D82D8); box-shadow: 0 5px 12px rgba(30, 79, 168, 0.3); }
.rp-highlight-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.rp-highlight-lead {
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    line-height: 1.45;
}
.rp-highlight-lead svg { flex-shrink: 0; margin-top: 0.12rem; }
.rp-highlight-text { margin: 0; font-size: 0.82rem; color: var(--rp-muted); line-height: 1.5; }
.rp-mb-sm { margin-bottom: 0.6rem; }
.rp-proj-rows { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.75rem; }
.rp-proj-row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    font-size: 0.82rem;
    color: var(--rp-muted);
    font-variant-numeric: tabular-nums;
}
.rp-proj-row strong { color: #0f172a; font-weight: 800; }
.rp-proj-net { border-top: 1px solid var(--rp-line); padding-top: 0.6rem; }
.rp-proj-net-label {
    display: block;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
    margin-bottom: 0.15rem;
}
.rp-proj-net-value { font-size: 1.35rem; font-weight: 800; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }

/* Tabla */
.rp-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--rp-line);
}
.rp-table-head-left { display: flex; align-items: center; gap: 0.55rem; }
.rp-table-head-note { font-size: 0.75rem; color: #94a3b8; }
.rp-table-scroll { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.rp-table thead th {
    background: linear-gradient(135deg, var(--rp-navy), var(--rp-blue));
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.85rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.rp-table thead th.rp-num { text-align: right; }
.rp-table td {
    padding: 0.7rem 0.85rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.rp-table tbody tr:last-child td { border-bottom: none; }
.rp-table tbody tr:hover td { background: var(--rp-soft); }
.rp-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.rp-th-rank { width: 3rem; }
.rp-th-actions { text-align: right; }
.rp-actions { text-align: right; }
.rp-muted { color: #94a3b8; }
.rp-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }

.rp-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 999px;
    background: #F1F5F9;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 800;
}
.rp-rank--gold { background: linear-gradient(135deg, #F6C445, #EEA820); color: #fff; box-shadow: 0 3px 8px rgba(238, 168, 32, 0.4); }
.rp-rank--silver { background: linear-gradient(135deg, #C6CDD6, #9AA5B1); color: #fff; box-shadow: 0 3px 8px rgba(154, 165, 177, 0.4); }
.rp-rank--bronze { background: linear-gradient(135deg, #D89B66, #B9712F); color: #fff; box-shadow: 0 3px 8px rgba(185, 113, 47, 0.4); }

.rp-client-name { font-weight: 700; color: #0f172a; }
.rp-client-services { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.3rem; }
.rp-service-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.14rem 0.5rem;
    border-radius: 999px;
    font-size: 0.66rem;
    font-weight: 700;
    white-space: nowrap;
}
.rp-service-badge--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.rp-service-badge--sea { background: #EAF1FC; color: var(--rp-blue); border: 1px solid #C9DAF3; }

.rp-status {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    padding: 0.22rem 0.6rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
    background: #F1F5F9;
    color: #64748b;
}
.rp-status--green { background: #EFFAF4; color: #116039; border: 1px solid #A7DFC3; }
.rp-status--amber { background: #FDF7E8; color: #92610B; border: 1px solid #F0D48A; }
.rp-status--red { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }

.rp-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.34rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 0.5rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #475569;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.rp-action-btn:hover { background: var(--rp-soft); color: var(--rp-navy); border-color: var(--rp-border); }

.rp-text-green { color: var(--rp-green); }
.rp-text-red { color: var(--rp-red); }
.rp-text-blue { color: var(--rp-blue); }
.rp-text-amber { color: var(--rp-amber); }

@media (max-width: 1280px) {
    .rp-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
    .rp-highlights { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .rp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rp-header { padding: 0.9rem 1rem; }
    .rp-period-body { flex-direction: column; align-items: stretch; }
}
@media (max-width: 520px) {
    .rp-kpis { grid-template-columns: 1fr; }
}
</style>
@endsection
