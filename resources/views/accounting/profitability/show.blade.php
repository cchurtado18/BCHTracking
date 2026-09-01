@extends('layouts.app')

@section('title', 'Rentabilidad — '.$agency->name)

@section('content')
@php
    $serviceLabels = \App\Support\ServiceType::options();
    if ($totals->missing_rate) {
        $statusInfo = ['label' => 'Sin tarifa', 'class' => 'red', 'icon' => 'x'];
    } elseif ($totals->margin_pct === null) {
        $statusInfo = ['label' => 'Sin datos', 'class' => 'gray', 'icon' => 'dash'];
    } elseif ($totals->margin_pct >= 15) {
        $statusInfo = ['label' => 'Saludable', 'class' => 'green', 'icon' => 'check'];
    } elseif ($totals->margin_pct > 0) {
        $statusInfo = ['label' => 'Margen bajo', 'class' => 'amber', 'icon' => 'warn'];
    } else {
        $statusInfo = ['label' => 'Pérdida', 'class' => 'red', 'icon' => 'down'];
    }

    $fmtLb = fn ($v) => number_format((float) $v, abs($v - (int) $v) < 0.05 ? 0 : 1);
    $niceMax = function (float $v): float {
        $v = max(1.0, $v);
        $mag = 10 ** (int) floor(log10($v));
        $n = $v / $mag;
        $nice = $n <= 1 ? 1 : ($n <= 2 ? 2 : ($n <= 5 ? 5 : 10));

        return $nice * $mag;
    };

    $chartW = 980; $chartH = 290;
    $padL = 52; $padR = 62; $padT = 22; $padB = 36;
    $plotW = $chartW - $padL - $padR;
    $plotH = $chartH - $padT - $padB;
    $n = max(1, $history->count());
    $slotW = $plotW / $n;
    $barW = min(56, $slotW * 0.48);

    $maxLbs = $niceMax((float) $history->max('lbs') * 1.12);
    $maxMoney = $niceMax((float) max(abs((float) $history->max('margin')), abs((float) $history->min('margin'))) * 1.18);
    $maxPct = $niceMax(max(20, (float) $history->max('margin_pct') * 1.15));

    $xCenter = fn ($i) => $padL + $slotW * $i + $slotW / 2;
    $yLbs = fn ($v) => $padT + $plotH - (max(0, $v) / $maxLbs) * $plotH;
    $yMoney = fn ($v) => $padT + $plotH - (max(0, $v) / $maxMoney) * $plotH;
    $yPct = fn ($v) => $padT + $plotH - (max(0, $v) / $maxPct) * $plotH;

    $marginPts = $history->values()->map(fn ($m, $i) => round($xCenter($i), 1).','.round($yMoney($m->margin), 1))->implode(' ');
    $pctPts = $history->values()->map(fn ($m, $i) => round($xCenter($i), 1).','.round($yPct($m->margin_pct), 1))->implode(' ');
@endphp
<div class="rpd-page">
    <x-module-banner
        section="Contabilidad"
        current="Rentabilidad"
        title="{{ $agency->name }}"
        subtitle="{{ $agency->typeLabel() }} · {{ $periodLabel }}. Detalle de libras, ingresos, costos y margen de este cliente."
        back-href="{{ route('accounting.profitability.index', $backQuery) }}"
        back-label="Volver al reporte"
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
        </x-slot:icon>
        <x-slot:strip>
            <span class="mb-strip-label">Estado</span>
            <span class="mb-pill {{ $statusInfo['class'] === 'green' ? 'mb-pill--ok' : ($statusInfo['class'] === 'red' ? 'mb-pill--warn' : '') }}">{{ $statusInfo['label'] }}</span>
            <span class="mb-pill">{{ $agency->code }}</span>
        </x-slot:strip>
    </x-module-banner>

    <div class="rpd-kpis">
        <div class="rpd-kpi-card">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </span>
                Libras
            </span>
            <span class="rpd-kpi-value">{{ $fmtLb($totals->lbs) }}</span>
        </div>
        <div class="rpd-kpi-card">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                </span>
                Tarifa prom.
            </span>
            <span class="rpd-kpi-value">{{ $totals->avg_rate !== null ? '$'.number_format($totals->avg_rate, 2) : '—' }}<small>{{ $totals->avg_rate !== null ? '/lb' : '' }}</small></span>
        </div>
        <div class="rpd-kpi-card">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--blue" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/></svg>
                </span>
                $ Ingreso
            </span>
            <span class="rpd-kpi-value rpd-text-blue">${{ number_format($totals->revenue, 2) }}</span>
        </div>
        <div class="rpd-kpi-card">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--slate" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </span>
                Costo op.
            </span>
            <span class="rpd-kpi-value">${{ number_format($totals->cost, 2) }}</span>
        </div>
        <div class="rpd-kpi-card">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--green" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
                </span>
                Ganancia
            </span>
            <span class="rpd-kpi-value {{ $totals->margin >= 0 ? 'rpd-text-green' : 'rpd-text-red' }}">${{ number_format($totals->margin, 2) }}</span>
            @if($totals->margin_pct !== null)
            <span class="rpd-kpi-note {{ $totals->margin_pct >= 15 ? 'rpd-text-green' : ($totals->margin_pct > 0 ? 'rpd-text-amber' : 'rpd-text-red') }}">Margen {{ number_format($totals->margin_pct, 1) }}%</span>
            @endif
        </div>
        <div class="rpd-kpi-card rpd-kpi-card--{{ $statusInfo['class'] }}">
            <span class="rpd-kpi-label">
                <span class="rpd-kpi-ico rpd-kpi-ico--{{ $statusInfo['class'] }}" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5"/></svg>
                </span>
                Estado
            </span>
            <span class="rpd-kpi-status rpd-status-{{ $statusInfo['class'] }}">
                @if($statusInfo['icon'] === 'check')
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                @elseif($statusInfo['icon'] === 'warn')
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                @elseif($statusInfo['icon'] === 'x')
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                @endif
                {{ $statusInfo['label'] }}
            </span>
        </div>
    </div>

    <div class="rpd-card">
        <div class="rpd-table-head">
            <div class="rpd-table-head-left">
                <span class="rpd-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                </span>
                <h2 class="rpd-section-title">Paquetes del cliente en el período</h2>
            </div>
            <span class="rpd-table-head-note">{{ number_format($totals->packages) }} paquete(s)</span>
        </div>
        <div class="rpd-table-scroll">
            <table class="rpd-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Guía / Tracking</th>
                        <th>Servicio</th>
                        <th class="rpd-num">Peso</th>
                        <th class="rpd-num">Tarifa</th>
                        <th class="rpd-num">Ingreso</th>
                        <th class="rpd-num">Costo</th>
                        <th class="rpd-num">Ganancia</th>
                        <th class="rpd-num">Margen</th>
                        <th class="rpd-th-actions">Factura</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td class="rpd-nowrap">{{ $row->delivered_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('preregistrations.show', $row->preregistration_id) }}" class="rpd-guide-link">{{ $row->tracking }}</a>
                        </td>
                        <td>
                            <span class="rpd-service rpd-service--{{ strtolower($row->service) }}">
                                {{ \App\Support\ServiceType::icon($row->service) }} {{ $serviceLabels[$row->service] ?? $row->service }}
                            </span>
                        </td>
                        <td class="rpd-num">{{ number_format($row->lbs, 1) }} {{ \App\Support\ServiceType::unit($row->service) }}</td>
                        <td class="rpd-num rpd-muted">
                            @if($row->price_per_lb !== null)
                            ${{ number_format($row->price_per_lb, 2) }}/{{ \App\Support\ServiceType::unit($row->service) }}
                            @else
                            <span class="rpd-pill rpd-pill--red">Sin tarifa</span>
                            @endif
                        </td>
                        <td class="rpd-num"><strong class="rpd-text-blue">${{ number_format($row->revenue, 2) }}</strong></td>
                        <td class="rpd-num">
                            <div class="rpd-cost-main">${{ number_format($row->cost, 2) }}</div>
                            @if($row->cost_per_lb !== null)
                            <div class="rpd-cost-rate">${{ number_format($row->cost_per_lb, 4) }}/lb</div>
                            @endif
                        </td>
                        <td class="rpd-num"><strong class="{{ $row->margin >= 0 ? 'rpd-text-green' : 'rpd-text-red' }}">${{ number_format($row->margin, 2) }}</strong></td>
                        <td class="rpd-num">
                            @if($row->margin_pct !== null)
                            <strong class="{{ $row->margin_pct >= 15 ? 'rpd-text-green' : ($row->margin_pct > 0 ? 'rpd-text-amber' : 'rpd-text-red') }}">{{ number_format($row->margin_pct, 1) }}%</strong>
                            @else
                            <span class="rpd-muted">—</span>
                            @endif
                        </td>
                        <td class="rpd-actions">
                            @php $invoice = $row->delivery_note_id ? $invoicesByNote->get($row->delivery_note_id) : null; @endphp
                            @if($invoice)
                            <a href="{{ route('accounting.invoices.show', $invoice->id) }}" class="rpd-invoice-link">
                                {{ $invoice->folio }}
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                            </a>
                            @else
                            <span class="rpd-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="rpd-empty">Este cliente no tiene salidas entregadas en el período seleccionado.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3" class="rpd-tfoot-label">Totales</td>
                        <td class="rpd-num">{{ number_format($totals->lbs, 1) }} lb</td>
                        <td class="rpd-num">{{ $totals->avg_rate !== null ? '$'.number_format($totals->avg_rate, 2).'/lb' : '—' }}</td>
                        <td class="rpd-num"><strong class="rpd-text-blue">${{ number_format($totals->revenue, 2) }}</strong></td>
                        <td class="rpd-num"><strong>${{ number_format($totals->cost, 2) }}</strong></td>
                        <td class="rpd-num"><strong class="{{ $totals->margin >= 0 ? 'rpd-text-green' : 'rpd-text-red' }}">${{ number_format($totals->margin, 2) }}</strong></td>
                        <td class="rpd-num"><strong class="{{ ($totals->margin_pct ?? 0) >= 15 ? 'rpd-text-green' : (($totals->margin_pct ?? 0) > 0 ? 'rpd-text-amber' : '') }}">{{ $totals->margin_pct !== null ? number_format($totals->margin_pct, 1).'%' : '—' }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="rpd-section-head">
        <span class="rpd-section-icon rpd-section-icon--purple" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
        </span>
        <h2 class="rpd-section-title">Histórico últimos 6 meses</h2>
    </div>

    <div class="rpd-card rpd-chart-card">
        <div class="rpd-chart-wrap">
            <svg viewBox="0 0 {{ $chartW }} {{ $chartH }}" class="rpd-chart" role="img" aria-label="Libras, ganancia y margen por mes">
                @for($g = 0; $g <= 4; $g++)
                @php $gy = $padT + $plotH - ($plotH / 4) * $g; @endphp
                <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $chartW - $padR }}" y2="{{ $gy }}" stroke="#EDF2F9" stroke-width="1"/>
                <text x="{{ $padL - 8 }}" y="{{ $gy + 3.5 }}" text-anchor="end" class="rpd-chart-tick">{{ number_format($maxLbs / 4 * $g, 0) }}</text>
                <text x="{{ $chartW - $padR + 8 }}" y="{{ $gy + 3.5 }}" text-anchor="start" class="rpd-chart-tick">{{ number_format($maxMoney / 4 * $g, 0) }}</text>
                @endfor
                <text x="14" y="{{ $padT + $plotH / 2 }}" text-anchor="middle" class="rpd-chart-axis" transform="rotate(-90 14 {{ $padT + $plotH / 2 }})">Libras</text>
                <text x="{{ $chartW - 12 }}" y="{{ $padT + $plotH / 2 }}" text-anchor="middle" class="rpd-chart-axis" transform="rotate(90 {{ $chartW - 12 }} {{ $padT + $plotH / 2 }})">Ganancia ($)</text>

                @foreach($history as $i => $m)
                @php
                    $bx = $xCenter($i) - $barW / 2;
                    $by = $yLbs($m->lbs);
                    $bh = max(2, $padT + $plotH - $by);
                @endphp
                <rect x="{{ round($bx, 1) }}" y="{{ round($by, 1) }}" width="{{ round($barW, 1) }}" height="{{ round($bh, 1) }}" rx="5" fill="#5BB8E4"/>
                <text x="{{ round($xCenter($i), 1) }}" y="{{ $padT + $plotH + 20 }}" text-anchor="middle" class="rpd-chart-tick">{{ $m->label }}</text>
                @endforeach

                <polyline points="{{ $pctPts }}" fill="none" stroke="#7C3AED" stroke-width="2.4" stroke-dasharray="5 6" stroke-linecap="round"/>
                @foreach($history as $i => $m)
                <circle cx="{{ round($xCenter($i), 1) }}" cy="{{ round($yPct($m->margin_pct), 1) }}" r="4" fill="#fff" stroke="#7C3AED" stroke-width="2"/>
                @endforeach

                <polyline points="{{ $marginPts }}" fill="none" stroke="#2BB673" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                @foreach($history as $i => $m)
                <circle cx="{{ round($xCenter($i), 1) }}" cy="{{ round($yMoney($m->margin), 1) }}" r="4.2" fill="#2BB673" stroke="#fff" stroke-width="1.4"/>
                @endforeach
            </svg>
        </div>
        <div class="rpd-chart-legend">
            <span class="rpd-legend-item"><span class="rpd-legend-swatch" style="background:#5BB8E4;"></span> Libras</span>
            <span class="rpd-legend-item"><span class="rpd-legend-swatch rpd-legend-swatch--line" style="background:#2BB673;"></span> Ganancia ($)</span>
            <span class="rpd-legend-item"><span class="rpd-legend-swatch rpd-legend-swatch--dotted" style="background:#7C3AED;"></span> Margen %</span>
        </div>

        <div class="rpd-table-scroll">
            <table class="rpd-table rpd-table--plain">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="rpd-num">Libras</th>
                        <th class="rpd-num">Ingreso</th>
                        <th class="rpd-num">Costo</th>
                        <th class="rpd-num">Ganancia</th>
                        <th class="rpd-num">Margen %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $m)
                    <tr>
                        <td class="rpd-nowrap">{{ $m->label }}</td>
                        <td class="rpd-num">{{ number_format($m->lbs, 1) }} lb</td>
                        <td class="rpd-num"><span class="rpd-text-blue">${{ number_format($m->revenue, 2) }}</span></td>
                        <td class="rpd-num rpd-muted">${{ number_format($m->cost, 2) }}</td>
                        <td class="rpd-num"><strong class="{{ $m->margin >= 0 ? 'rpd-text-green' : 'rpd-text-red' }}">${{ number_format($m->margin, 2) }}</strong></td>
                        <td class="rpd-num"><strong class="{{ $m->margin_pct >= 15 ? 'rpd-text-green' : ($m->margin_pct > 0 ? 'rpd-text-amber' : 'rpd-text-orange') }}">{{ number_format($m->margin_pct, 1) }}%</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.rpd-page {
    --rpd-navy: #0A2D6F;
    --rpd-blue: #1E4FA8;
    --rpd-green: #16794C;
    --rpd-red: #D64545;
    --rpd-amber: #B27A0E;
    --rpd-line: #E8EEF8;
    --rpd-border: #C5D4EB;
    --rpd-soft: #F4F8FD;
    --rpd-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}
.rpd-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--rpd-line);
    border-radius: 1rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.rpd-header-left { display: flex; align-items: center; gap: 0.85rem; }
.rpd-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.72rem;
    color: #94a3b8;
    margin-bottom: 0.28rem;
}
.rpd-breadcrumb a { color: #64748b; text-decoration: none; font-weight: 600; }
.rpd-breadcrumb a:hover { color: var(--rpd-navy); }
.rpd-breadcrumb strong { color: #334155; font-weight: 700; }
.rpd-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #16794C, #2BB673);
    color: #fff;
    box-shadow: 0 6px 14px rgba(43, 182, 115, 0.32);
    flex-shrink: 0;
}
.rpd-title { margin: 0; font-size: 1.38rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.rpd-subtitle { margin: 0.18rem 0 0; font-size: 0.82rem; color: var(--rpd-muted); }

.rpd-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    font-size: 0.85rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.rpd-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.rpd-btn-secondary:hover { background: var(--rpd-soft); color: var(--rpd-navy); border-color: var(--rpd-border); }

.rpd-kpis {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 0.7rem;
    margin-bottom: 1.15rem;
}
.rpd-kpi-card {
    background: #fff;
    border: 1px solid var(--rpd-line);
    border-radius: 0.8rem;
    padding: 0.9rem 1rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    min-width: 0;
}
.rpd-kpi-card--green { border-color: #A7DFC3; background: linear-gradient(180deg, #fff 35%, #F2FBF6 140%); }
.rpd-kpi-card--amber { border-color: #F0D48A; background: linear-gradient(180deg, #fff 35%, #FDF7E8 140%); }
.rpd-kpi-card--red { border-color: #F6C9C9; background: linear-gradient(180deg, #fff 35%, #FDECEC 140%); }
.rpd-kpi-label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
}
.rpd-kpi-ico {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.45rem;
    height: 1.45rem;
    border-radius: 0.42rem;
    flex-shrink: 0;
}
.rpd-kpi-ico--blue { background: #EAF1FC; color: var(--rpd-blue); }
.rpd-kpi-ico--slate { background: #F1F5F9; color: #64748b; }
.rpd-kpi-ico--green { background: #EFFAF4; color: var(--rpd-green); }
.rpd-kpi-ico--amber { background: #FDF7E8; color: var(--rpd-amber); }
.rpd-kpi-ico--red { background: #FDECEC; color: var(--rpd-red); }
.rpd-kpi-ico--gray { background: #F1F5F9; color: #94a3b8; }
.rpd-kpi-value {
    font-size: 1.32rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.rpd-kpi-value small { font-size: 0.72rem; font-weight: 700; color: #94a3b8; }
.rpd-kpi-note { font-size: 0.72rem; font-weight: 700; }
.rpd-kpi-status {
    display: inline-flex;
    align-items: center;
    gap: 0.32rem;
    font-size: 1.02rem;
    font-weight: 800;
}
.rpd-status-green { color: #116039; }
.rpd-status-amber { color: #92610B; }
.rpd-status-red { color: #B03030; }
.rpd-status-gray { color: #64748b; }

.rpd-card {
    background: #fff;
    border: 1px solid var(--rpd-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.rpd-section-head {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin: 0 0.15rem 0.75rem;
}
.rpd-section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: var(--rpd-soft);
    border: 1px solid var(--rpd-line);
    color: var(--rpd-navy);
    flex-shrink: 0;
}
.rpd-section-icon--purple { background: #F5F0FE; border-color: #E2D5FB; color: #7C3AED; }
.rpd-section-title { margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a; }
.rpd-table-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--rpd-line);
}
.rpd-table-head-left { display: flex; align-items: center; gap: 0.55rem; }
.rpd-table-head-note { font-size: 0.75rem; color: #94a3b8; }

.rpd-table-scroll { overflow-x: auto; }
.rpd-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.rpd-table thead th {
    background: linear-gradient(135deg, var(--rpd-navy), var(--rpd-blue));
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.85rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.rpd-table thead th.rpd-num { text-align: right; }
.rpd-table td {
    padding: 0.66rem 0.85rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.rpd-table tbody tr:last-child td { border-bottom: none; }
.rpd-table tbody tr:hover td { background: var(--rpd-soft); }
.rpd-table tfoot td {
    background: var(--rpd-soft);
    border-top: 2px solid var(--rpd-line);
    font-weight: 700;
    color: #0f172a;
    padding: 0.72rem 0.85rem;
}
.rpd-tfoot-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; }
.rpd-table--plain thead th {
    background: var(--rpd-soft);
    color: #64748b;
    border-bottom: 1px solid var(--rpd-line);
}
.rpd-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.rpd-th-actions { text-align: right; }
.rpd-actions { text-align: right; }
.rpd-nowrap { white-space: nowrap; }
.rpd-muted { color: #94a3b8; }
.rpd-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }
.rpd-cost-main { color: #334155; font-weight: 600; }
.rpd-cost-rate { font-size: 0.66rem; color: #94a3b8; margin-top: 0.08rem; }

.rpd-guide-link {
    font-weight: 700;
    color: #0f172a;
    text-decoration: none;
    font-variant-numeric: tabular-nums;
}
.rpd-guide-link:hover { color: var(--rpd-blue); text-decoration: underline; }

.rpd-service {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.14rem 0.5rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}
.rpd-service--air { background: #EAF6FB; color: #0E6E8C; border: 1px solid #BFE3F0; }
.rpd-service--sea { background: #EAF1FC; color: var(--rpd-blue); border: 1px solid #C9DAF3; }

.rpd-pill {
    display: inline-flex;
    padding: 0.16rem 0.5rem;
    border-radius: 999px;
    font-size: 0.66rem;
    font-weight: 700;
    white-space: nowrap;
}
.rpd-pill--red { background: #FDECEC; color: #B03030; border: 1px solid #F6C9C9; }

.rpd-invoice-link {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    font-size: 0.8rem;
    font-weight: 800;
    color: var(--rpd-blue);
    text-decoration: none;
    white-space: nowrap;
}
.rpd-invoice-link:hover { color: var(--rpd-navy); text-decoration: underline; }

.rpd-chart-card { padding-bottom: 0.35rem; }
.rpd-chart-wrap { padding: 1rem 1rem 0.25rem; }
.rpd-chart { width: 100%; height: auto; display: block; }
.rpd-chart-tick { font-size: 11px; fill: #94a3b8; }
.rpd-chart-axis { font-size: 11px; fill: #64748b; font-weight: 600; }
.rpd-chart-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.15rem;
    padding: 0.45rem 1rem 0.9rem;
    border-bottom: 1px solid var(--rpd-line);
}
.rpd-legend-item { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.75rem; font-weight: 700; color: #475569; }
.rpd-legend-swatch { width: 1.15rem; height: 0.7rem; border-radius: 0.2rem; display: inline-block; }
.rpd-legend-swatch--line { height: 0.24rem; border-radius: 999px; }
.rpd-legend-swatch--dotted {
    height: 0.24rem;
    border-radius: 999px;
    -webkit-mask-image: linear-gradient(90deg, #000 60%, transparent 60%);
    mask-image: linear-gradient(90deg, #000 60%, transparent 60%);
    -webkit-mask-size: 6px 100%;
    mask-size: 6px 100%;
}

.rpd-text-green { color: var(--rpd-green); }
.rpd-text-red { color: var(--rpd-red); }
.rpd-text-blue { color: var(--rpd-blue); }
.rpd-text-amber { color: var(--rpd-amber); }
.rpd-text-orange { color: #D97706; }

@media (max-width: 1280px) {
    .rpd-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .rpd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rpd-header { padding: 0.9rem 1rem; }
}
@media (max-width: 520px) {
    .rpd-kpis { grid-template-columns: 1fr; }
}
</style>
@endsection
