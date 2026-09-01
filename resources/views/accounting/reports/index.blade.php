@extends('layouts.app')

@section('title', 'Reporte ejecutivo')

@php
    $pctDelta = function (float $current, float $previous): ?float {
        if (abs($previous) < 0.005) {
            return null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    };

    $kpis = [
        [
            'key' => 'invoiced',
            'label' => 'Facturado',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>',
            'tone' => 'navy',
        ],
        [
            'key' => 'collected',
            'label' => 'Cobrado',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            'tone' => 'green',
        ],
        [
            'key' => 'receivables',
            'label' => 'Saldo CxC',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>',
            'tone' => 'red',
        ],
        [
            'key' => 'avg_ticket',
            'label' => 'Ticket promedio',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-12-.75h14.25A2.25 2.25 0 0 0 21 15V9a2.25 2.25 0 0 0-2.25-2.25H4.5A2.25 2.25 0 0 0 2.25 9v6a2.25 2.25 0 0 0 2.25 2.25Zm0 0v.375c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125v-.375"/></svg>',
            'tone' => 'purple',
        ],
    ];

    $chartMax = max(1, (float) $monthlySeries->max(fn ($m) => max($m->invoiced, $m->collected)));
    $chartStep = $chartMax / 4;

    $donutColors = ['#0A2D6F', '#2BB673', '#F6A623', '#3498DB', '#5E6168'];
@endphp

@section('content')
<div class="rex-page">
    <x-module-banner class="no-print" section="Contabilidad" current="Reporte ejecutivo" title="Reporte ejecutivo" subtitle="Comparativo mes vs mes: facturado, cobrado, CxC, evolución y ranking de clientes.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <button type="button" class="mb-btn mb-btn-secondary" onclick="window.print()">Imprimir</button>
        </x-slot:actions>
    </x-module-banner>

    {{-- ══ Mes actual vs mes anterior ══ --}}
    <section class="rex-section">
        <div class="rex-section-head">
            <span class="rex-section-icon rex-section-icon--blue" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
            </span>
            <h2 class="rex-section-title">Mes actual vs mes anterior</h2>
            <span class="rex-section-badge">{{ $comparisonLabel }}</span>
        </div>

        <div class="rex-kpis">
            @foreach($kpis as $kpi)
            @php
                $cur = (float) $currentMonth[$kpi['key']];
                $prev = (float) $previousMonth[$kpi['key']];
                $delta = $pctDelta($cur, $prev);
            @endphp
            <div class="rex-kpi-card">
                <div class="rex-kpi-top">
                    <span class="rex-kpi-icon rex-kpi-icon--{{ $kpi['tone'] }}" aria-hidden="true">{!! $kpi['icon'] !!}</span>
                    <span class="rex-kpi-label">{{ $kpi['label'] }}</span>
                    @if($delta !== null)
                    <span class="rex-kpi-delta {{ $delta >= 0 ? 'rex-kpi-delta--up' : 'rex-kpi-delta--down' }}">
                        {{ $delta >= 0 ? '↑' : '↓' }} {{ number_format(abs($delta), 1) }}%
                    </span>
                    @else
                    <span class="rex-kpi-delta rex-kpi-delta--neutral">—</span>
                    @endif
                </div>
                <p class="rex-kpi-value">${{ number_format($cur, 2) }}</p>
                <div class="rex-kpi-foot">
                    <span>Mes anterior</span>
                    <span class="rex-kpi-prev">${{ number_format($prev, 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ══ Evolución últimos 6 meses ══ --}}
    <section class="rex-card">
        <div class="rex-card-head">
            <span class="rex-section-icon rex-section-icon--blue" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
            </span>
            <h2 class="rex-card-title">Evolución últimos 6 meses (Facturado vs Cobrado)</h2>
        </div>
        <div class="rex-card-body">
            <div class="rex-chart">
                <div class="rex-chart-grid" aria-hidden="true">
                    @for($i = 4; $i >= 0; $i--)
                    <div class="rex-chart-gridline">
                        <span class="rex-chart-gridlabel">${{ number_format($chartStep * $i, 0) }}</span>
                    </div>
                    @endfor
                </div>
                <div class="rex-chart-bars">
                    @foreach($monthlySeries as $month)
                    <div class="rex-chart-group">
                        <div class="rex-chart-cols">
                            <div class="rex-chart-bar rex-chart-bar--invoiced" style="height: {{ $chartMax > 0 ? max(1, round($month->invoiced / $chartMax * 100, 1)) : 1 }}%" title="Facturado {{ $month->label }}: ${{ number_format($month->invoiced, 2) }}"></div>
                            <div class="rex-chart-bar rex-chart-bar--collected" style="height: {{ $chartMax > 0 ? max(1, round($month->collected / $chartMax * 100, 1)) : 1 }}%" title="Cobrado {{ $month->label }}: ${{ number_format($month->collected, 2) }}"></div>
                        </div>
                        <span class="rex-chart-month">{{ $month->label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="rex-chart-legend">
                <span class="rex-legend-item"><span class="rex-legend-dot rex-legend-dot--navy"></span> Facturado</span>
                <span class="rex-legend-item"><span class="rex-legend-dot rex-legend-dot--green"></span> Cobrado</span>
            </div>
        </div>
    </section>

    {{-- ══ Análisis por período ══ --}}
    <section class="rex-card">
        <div class="rex-card-head">
            <span class="rex-section-icon rex-section-icon--blue" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
            </span>
            <h2 class="rex-card-title">Análisis por período</h2>
        </div>
        <div class="rex-card-body">
            <div class="rex-quick-filters">
                @php
                    $quickRanges = [
                        'this_month' => 'Este mes',
                        'last_month' => 'Mes anterior',
                        'last_30' => 'Últimos 30 días',
                        'quarter' => 'Trimestre',
                        'year' => 'Año actual',
                    ];
                @endphp
                @foreach($quickRanges as $key => $label)
                <a href="{{ route('accounting.reports.index', $key === 'this_month' ? [] : ['range' => $key]) }}"
                   class="rex-quick-btn {{ $rangeKey === $key ? 'is-active' : '' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('accounting.reports.index') }}" class="rex-range-form">
                <div class="rex-range-field">
                    <label class="rex-range-label" for="from">Desde</label>
                    <input type="date" name="from" id="from" class="rex-input" value="{{ $rangeKey === 'custom' ? $from->toDateString() : '' }}">
                </div>
                <div class="rex-range-field">
                    <label class="rex-range-label" for="to">Hasta</label>
                    <input type="date" name="to" id="to" class="rex-input" value="{{ $rangeKey === 'custom' ? $to->toDateString() : '' }}">
                </div>
                <button class="rex-btn rex-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Aplicar rango
                </button>
            </form>

            <p class="rex-period-summary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                Período analizado: <strong>{{ $periodLabel }}</strong>
                · Facturado: <strong class="rex-text-navy">${{ number_format($invoicedUsd, 2) }}</strong>
                · Cobrado: <strong class="rex-text-green">${{ number_format($collected, 2) }}</strong>
                · Gastos: <strong class="rex-text-red">${{ number_format($totalExpenses, 2) }}</strong>
            </p>
        </div>
    </section>

    {{-- ══ Cobros por método ══ --}}
    <section class="rex-section">
        <div class="rex-section-head">
            <span class="rex-section-icon rex-section-icon--green" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </span>
            <h2 class="rex-section-title">¿Cómo entra el dinero? — Cobros por método de pago</h2>
        </div>

        <div class="rex-money-grid">
            <div class="rex-card rex-money-donut-card">
                <p class="rex-mini-title">Distribución</p>
                @if($paymentsTotal > 0)
                @php
                    $acc = 0;
                    $segments = [];
                    foreach ($paymentsByMethod as $i => $pm) {
                        $pct = $pm->total / $paymentsTotal * 100;
                        $segments[] = $donutColors[$i % count($donutColors)].' '.round($acc, 2).'% '.round($acc + $pct, 2).'%';
                        $acc += $pct;
                    }
                @endphp
                <div class="rex-donut" style="background: conic-gradient({{ implode(', ', $segments) }});" role="img" aria-label="Distribución de cobros por método">
                    <span class="rex-donut-hole"></span>
                </div>
                <div class="rex-donut-legend">
                    @foreach($paymentsByMethod as $i => $pm)
                    <span class="rex-legend-item"><span class="rex-legend-dot" style="background: {{ $donutColors[$i % count($donutColors)] }}"></span> {{ $pm->label }}</span>
                    @endforeach
                </div>
                <p class="rex-donut-total">Total cobrado: <strong class="rex-text-green">${{ number_format($paymentsTotal, 2) }}</strong></p>
                @else
                <p class="rex-empty-note">Sin cobros en el período.</p>
                @endif
            </div>

            <div class="rex-card rex-money-rank-card">
                <p class="rex-mini-title">Ranking de métodos</p>
                <div class="rex-table-scroll">
                    <table class="rex-rank-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Método</th>
                                <th class="rex-num">Movs.</th>
                                <th class="rex-num">Cobrado</th>
                                <th class="rex-num">% del total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentsByMethod as $i => $pm)
                            @php $pct = $paymentsTotal > 0 ? round($pm->total / $paymentsTotal * 100, 1) : 0; @endphp
                            <tr>
                                <td class="rex-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="rex-strong">{{ $pm->label }}</span>
                                </td>
                                <td class="rex-num">{{ $pm->movs }}</td>
                                <td class="rex-num rex-text-green rex-strong">${{ number_format($pm->total, 2) }}</td>
                                <td class="rex-num">
                                    <span class="rex-pct-cell">
                                        {{ number_format($pct, 1) }}%
                                        <span class="rex-progress"><span class="rex-progress-fill rex-progress-fill--navy" style="width: {{ min(100, $pct) }}%"></span></span>
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="rex-empty-note">Sin cobros en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    {{-- ══ Top 10 clientes facturados ══ --}}
    <section class="rex-section">
        <div class="rex-section-head">
            <span class="rex-section-icon rex-section-icon--purple" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"/></svg>
            </span>
            <h2 class="rex-section-title">Top 10 clientes facturados</h2>
        </div>

        <div class="rex-card">
            <div class="rex-table-scroll">
                <table class="rex-top-table">
                    <thead>
                        <tr>
                            <th class="rex-top-rank-col">#</th>
                            <th>Cliente / Cuenta</th>
                            <th class="rex-num">Facturas</th>
                            <th class="rex-num">Facturado</th>
                            <th class="rex-num">Cobrado</th>
                            <th class="rex-num">Saldo</th>
                            <th class="rex-num">% facturado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topClients as $i => $row)
                        @php $pct = $topClientsInvoicedTotal > 0 ? round($row->invoiced / $topClientsInvoicedTotal * 100, 1) : 0; @endphp
                        <tr>
                            <td class="rex-top-rank-col">
                                <span class="rex-rank-circle rex-rank-circle--{{ min($i + 1, 4) }}">{{ $i + 1 }}</span>
                            </td>
                            <td>
                                <span class="rex-strong">{{ $row->agency?->name ?? '—' }}</span>
                                <span class="rex-client-tag">{{ $row->agency?->typeLabel() ?? '' }}{{ $row->agency?->code ? ' · '.$row->agency->code : '' }}</span>
                            </td>
                            <td class="rex-num">{{ $row->invoices }}</td>
                            <td class="rex-num rex-text-navy rex-strong">${{ number_format($row->invoiced, 2) }}</td>
                            <td class="rex-num rex-text-green">${{ number_format($row->paid, 2) }}</td>
                            <td class="rex-num {{ $row->balance > 0 ? 'rex-text-red rex-strong' : 'rex-muted' }}">${{ number_format($row->balance, 2) }}</td>
                            <td class="rex-num">
                                <span class="rex-pct-cell">
                                    {{ number_format($pct, 1) }}%
                                    <span class="rex-progress"><span class="rex-progress-fill rex-progress-fill--purple" style="width: {{ min(100, $pct) }}%"></span></span>
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="rex-empty-note">Sin facturas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ══ Estado de resultados ══ --}}
    <section class="rex-section">
        <div class="rex-section-head">
            <span class="rex-section-icon rex-section-icon--blue" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
            </span>
            <h2 class="rex-section-title">Estado de resultados</h2>
            <span class="rex-section-badge">{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</span>
        </div>

        <div class="rex-results-grid">
            <div class="rex-card rex-results-card">
                <div class="rex-table-scroll">
                    <table class="rex-rank-table rex-pl-table">
                        <tbody>
                            <tr>
                                <td>Ingresos facturados ({{ $invoicedCount }} {{ $invoicedCount === 1 ? 'factura' : 'facturas' }}, {{ number_format($invoicedLbs, 2) }} lbs)</td>
                                <td class="rex-num rex-strong">${{ number_format($invoicedUsd, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="rex-muted">(−) Costo de operación (cantidad × costo vigente en Parámetros)</td>
                                <td class="rex-num rex-text-red">−${{ number_format($estimatedCost, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="rex-muted">(−) Gastos extras (planilla, renta, etc.; el flete de vía no se resta otra vez)</td>
                                <td class="rex-num rex-text-red">−${{ number_format($totalExpenses, 2) }}</td>
                            </tr>
                            <tr class="rex-pl-total">
                                <td><strong>Resultado del período</strong></td>
                                <td class="rex-num"><strong class="{{ $netResult < 0 ? 'rex-text-red' : 'rex-text-green' }}">${{ number_format($netResult, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($operations->withoutRateLbs > 0)
                <p class="rex-warn-note">
                    {{ number_format($operations->withoutRateLbs, 2) }} lbs entregadas sin tarifa vigente no suman al costo estimado.
                    <a href="{{ route('accounting.rates.index') }}">Registrar tarifas</a>.
                </p>
                @endif
            </div>

            <div class="rex-card rex-results-card">
                <div class="rex-card-head rex-card-head--inner">
                    <h3 class="rex-mini-title" style="margin:0">Gastos por categoría</h3>
                    <a href="{{ route('accounting.expenses.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rex-link">Ver detalle</a>
                </div>
                <div class="rex-table-scroll">
                    <table class="rex-rank-table">
                        <tbody>
                            @forelse($expensesByCategory as $row)
                            <tr>
                                <td>{{ $row->category?->name }}</td>
                                <td class="rex-num rex-strong">${{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="rex-empty-note">Sin gastos en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="rex-donut-total" style="margin-top:0.6rem">Saldo CxC pendiente (hoy): <strong class="rex-text-red">${{ number_format($receivables, 2) }}</strong></p>
            </div>
        </div>
    </section>

    {{-- ══ Operación por agencia ══ --}}
    <section class="rex-section">
        <div class="rex-section-head">
            <span class="rex-section-icon rex-section-icon--green" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h1.5c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>
            </span>
            <h2 class="rex-section-title">Operación por agencia y servicio (estimado)</h2>
            <a href="{{ route('accounting.profitability.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rex-link rex-section-link">Ver rentabilidad</a>
        </div>

        <div class="rex-card">
            <div class="rex-table-scroll">
                <table class="rex-top-table">
                    <thead>
                        <tr>
                            <th>Agencia</th>
                            <th>Servicio</th>
                            <th class="rex-num">Lbs</th>
                            <th class="rex-num">Ingreso est.</th>
                            <th class="rex-num">Costo est.</th>
                            <th class="rex-num">Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operations->rows as $row)
                        <tr>
                            <td><span class="rex-strong">{{ $row->agency?->code }}</span> — {{ $row->agency?->name }}</td>
                            <td class="rex-muted">{{ \App\Support\ServiceType::label($row->service) }}</td>
                            <td class="rex-num">{{ number_format($row->lbs, 2) }} {{ \App\Support\ServiceType::unit($row->service) }}</td>
                            <td class="rex-num">${{ number_format($row->revenue, 2) }}</td>
                            <td class="rex-num">${{ number_format($row->cost, 2) }}</td>
                            <td class="rex-num rex-strong {{ $row->margin < 0 ? 'rex-text-red' : 'rex-text-green' }}">${{ number_format($row->margin, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="rex-empty-note">Sin salidas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<style>
.rex-page {
    --rex-navy: #0A2D6F;
    --rex-blue: #1E4FA8;
    --rex-green: #2BB673;
    --rex-red: #D64545;
    --rex-purple: #7C3AED;
    --rex-line: #E8EEF8;
    --rex-border: #C5D4EB;
    --rex-soft: #F4F8FD;
    --rex-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

/* Header */
.rex-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--rex-line);
    border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.rex-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.45rem;
}
.rex-breadcrumb strong { color: #334155; font-weight: 700; }
.rex-title-row { display: flex; align-items: center; gap: 0.6rem; }
.rex-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    background: linear-gradient(135deg, var(--rex-navy), var(--rex-blue));
    color: #fff;
    box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28);
    flex-shrink: 0;
}
.rex-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.rex-subtitle {
    margin: 0.4rem 0 0;
    font-size: 0.875rem;
    color: var(--rex-muted);
    line-height: 1.45;
}
.rex-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }

.rex-btn {
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
.rex-btn-primary {
    background: var(--rex-navy);
    color: #fff;
    border-color: var(--rex-navy);
    box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25);
}
.rex-btn-primary:hover { background: var(--rex-blue); border-color: var(--rex-blue); color: #fff; transform: translateY(-1px); }
.rex-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.rex-btn-secondary:hover { background: var(--rex-soft); color: var(--rex-navy); border-color: var(--rex-border); }

/* Secciones */
.rex-section { margin-bottom: 1.15rem; }
.rex-section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 0.75rem;
}
.rex-section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    flex-shrink: 0;
}
.rex-section-icon--blue { background: #E8EEF8; color: var(--rex-navy); }
.rex-section-icon--green { background: #D9F3E6; color: #16794C; }
.rex-section-icon--purple { background: #EDE9FE; color: var(--rex-purple); }
.rex-section-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.rex-section-badge {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #64748b;
    background: #f1f5f9;
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
}
.rex-section-link { margin-left: auto; }

/* KPI comparativo */
.rex-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
}
.rex-kpi-card {
    background: #fff;
    border: 1px solid var(--rex-line);
    border-radius: 0.85rem;
    padding: 0.95rem 1.05rem 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}
.rex-kpi-top { display: flex; align-items: center; gap: 0.5rem; }
.rex-kpi-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 0.5rem;
    flex-shrink: 0;
}
.rex-kpi-icon--navy { background: #E8EEF8; color: var(--rex-navy); }
.rex-kpi-icon--green { background: #D9F3E6; color: #16794C; }
.rex-kpi-icon--red { background: #FDE8E8; color: var(--rex-red); }
.rex-kpi-icon--purple { background: #EDE9FE; color: var(--rex-purple); }
.rex-kpi-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    min-width: 0;
}
.rex-kpi-delta {
    margin-left: auto;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
    white-space: nowrap;
}
.rex-kpi-delta--up { background: #D9F3E6; color: #16794C; }
.rex-kpi-delta--down { background: #FDE8E8; color: var(--rex-red); }
.rex-kpi-delta--neutral { background: #f1f5f9; color: #94a3b8; }
.rex-kpi-value {
    margin: 0.6rem 0 0.55rem;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}
.rex-kpi-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 0.55rem;
    border-top: 1px solid #f1f5f9;
    font-size: 0.72rem;
    color: #94a3b8;
}
.rex-kpi-prev { font-weight: 700; color: #475569; font-variant-numeric: tabular-nums; }

/* Cards genéricas */
.rex-card {
    background: #fff;
    border: 1px solid var(--rex-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.rex-section .rex-card { margin-bottom: 0; }
.rex-card-head {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--rex-line);
}
.rex-card-head--inner { padding: 0 0 0.65rem; border-bottom: 1px solid var(--rex-line); margin-bottom: 0.5rem; }
.rex-card-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.rex-card-body { padding: 1rem 1.1rem 1.1rem; }
.rex-mini-title {
    margin: 0 0 0.75rem;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
}

/* Gráfico de barras */
.rex-chart { position: relative; height: 15rem; }
.rex-chart-grid {
    position: absolute;
    inset: 0 0 1.6rem 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.rex-chart-gridline {
    border-top: 1px dashed #eef2f7;
    position: relative;
    height: 0;
}
.rex-chart-gridline:last-child { border-top-color: #dbe3ee; }
.rex-chart-gridlabel {
    position: absolute;
    left: 0;
    top: -0.6rem;
    font-size: 0.65rem;
    color: #94a3b8;
    background: #fff;
    padding-right: 0.3rem;
    font-variant-numeric: tabular-nums;
}
.rex-chart-bars {
    position: absolute;
    inset: 0 0.5rem 0 3.2rem;
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    gap: 0.5rem;
}
.rex-chart-group {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    flex: 1;
    min-width: 0;
}
.rex-chart-cols {
    display: flex;
    align-items: flex-end;
    gap: 0.3rem;
    height: calc(100% - 1.6rem);
    width: 100%;
    justify-content: center;
}
.rex-chart-bar {
    width: clamp(0.9rem, 2.4vw, 2.1rem);
    border-radius: 0.25rem 0.25rem 0 0;
    transition: opacity .15s ease;
}
.rex-chart-bar:hover { opacity: 0.85; }
.rex-chart-bar--invoiced { background: var(--rex-navy); }
.rex-chart-bar--collected { background: var(--rex-green); }
.rex-chart-month {
    height: 1.6rem;
    display: inline-flex;
    align-items: flex-end;
    font-size: 0.7rem;
    color: #94a3b8;
    padding-top: 0.3rem;
}
.rex-chart-legend {
    display: flex;
    justify-content: center;
    gap: 1.25rem;
    margin-top: 0.75rem;
}
.rex-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}
.rex-legend-dot { width: 0.7rem; height: 0.7rem; border-radius: 0.2rem; display: inline-block; }
.rex-legend-dot--navy { background: var(--rex-navy); }
.rex-legend-dot--green { background: var(--rex-green); }

/* Análisis por período */
.rex-quick-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.9rem; }
.rex-quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.48rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 700;
    border-radius: 0.55rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #475569;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.rex-quick-btn:hover { background: var(--rex-soft); color: var(--rex-navy); border-color: var(--rex-border); }
.rex-quick-btn.is-active {
    background: var(--rex-navy);
    color: #fff;
    border-color: var(--rex-navy);
    box-shadow: 0 4px 10px rgba(10, 45, 111, 0.22);
}
.rex-range-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}
.rex-range-field { display: flex; flex-direction: column; gap: 0.25rem; }
.rex-range-label {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
}
.rex-input {
    padding: 0.52rem 0.7rem;
    font-size: 0.875rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
}
.rex-input:focus { outline: none; border-color: var(--rex-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.rex-period-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    margin: 0;
    padding: 0.7rem 0.9rem;
    background: var(--rex-soft);
    border-radius: 0.6rem;
    font-size: 0.8rem;
    color: #475569;
}
.rex-period-summary svg { color: var(--rex-blue); flex-shrink: 0; }

/* Cobros por método */
.rex-money-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
    gap: 1rem;
    align-items: stretch;
}
.rex-money-donut-card,
.rex-money-rank-card { padding: 1rem 1.1rem 1.1rem; margin-bottom: 0; }
.rex-donut {
    width: 11rem;
    height: 11rem;
    border-radius: 50%;
    margin: 0.35rem auto 0.85rem;
    position: relative;
}
.rex-donut-hole {
    position: absolute;
    inset: 22%;
    background: #fff;
    border-radius: 50%;
}
.rex-donut-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.4rem 0.9rem;
    margin-bottom: 0.6rem;
}
.rex-donut-total {
    margin: 0;
    text-align: center;
    font-size: 0.8rem;
    color: #475569;
}
.rex-empty-note { padding: 0.9rem 0.5rem; font-size: 0.8125rem; color: #94a3b8; text-align: center; margin: 0; }

/* Tablas */
.rex-table-scroll { overflow-x: auto; }
.rex-rank-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.rex-rank-table th {
    text-align: left;
    padding: 0.5rem 0.65rem;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #94a3b8;
    border-bottom: 1px solid var(--rex-line);
}
.rex-rank-table td {
    padding: 0.62rem 0.65rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.rex-rank-table tbody tr:last-child td { border-bottom: none; }
.rex-rank-table tbody tr:hover td { background: var(--rex-soft); }

.rex-top-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.rex-top-table thead th {
    background: linear-gradient(135deg, var(--rex-navy), var(--rex-blue));
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.8rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.rex-top-table td {
    padding: 0.66rem 0.8rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.rex-top-table tbody tr:last-child td { border-bottom: none; }
.rex-top-table tbody tr:hover td { background: var(--rex-soft); }
.rex-top-rank-col { width: 3rem; text-align: center; }
.rex-rank-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #fff;
}
.rex-rank-circle--1 { background: #F6A623; box-shadow: 0 3px 8px rgba(246, 166, 35, 0.4); }
.rex-rank-circle--2 { background: #9AA5B1; }
.rex-rank-circle--3 { background: #C08A5A; }
.rex-rank-circle--4 { background: #CBD5E1; color: #475569; }
.rex-client-tag {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.7rem;
    color: #94a3b8;
}

.rex-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.rex-strong { font-weight: 700; color: #0f172a; }
.rex-muted { color: #94a3b8; }
.rex-text-navy { color: var(--rex-navy); }
.rex-text-green { color: #16794C; }
.rex-text-red { color: var(--rex-red); }
.rex-link { font-size: 0.8rem; font-weight: 700; color: var(--rex-blue); text-decoration: none; }
.rex-link:hover { text-decoration: underline; }

.rex-pct-cell {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: flex-end;
}
.rex-progress {
    width: 4.5rem;
    height: 0.4rem;
    border-radius: 999px;
    background: #eef2f7;
    overflow: hidden;
    display: inline-block;
}
.rex-progress-fill { display: block; height: 100%; border-radius: 999px; }
.rex-progress-fill--navy { background: var(--rex-navy); }
.rex-progress-fill--purple { background: var(--rex-purple); }

/* Estado de resultados */
.rex-results-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    gap: 1rem;
    align-items: stretch;
}
.rex-results-card { padding: 1rem 1.1rem 1.1rem; margin-bottom: 0; }
.rex-pl-table td { padding: 0.7rem 0.65rem; }
.rex-pl-total td {
    border-top: 2px solid var(--rex-line);
    background: var(--rex-soft);
    font-size: 0.9rem;
}
.rex-warn-note {
    margin: 0.75rem 0 0;
    padding: 0.65rem 0.8rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.55rem;
    font-size: 0.78rem;
    color: #92400e;
    line-height: 1.45;
}
.rex-warn-note a { color: #b45309; font-weight: 700; }

@media (max-width: 1100px) {
    .rex-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rex-money-grid,
    .rex-results-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .rex-kpis { grid-template-columns: 1fr; }
    .rex-header { padding: 0.9rem 1rem; }
    .rex-chart { height: 12rem; }
    .rex-section-link { margin-left: 0; }
}
@media print {
    .rex-header-actions, .rex-quick-filters, .rex-range-form { display: none !important; }
}
</style>
@endsection
