@extends('layouts.app')

@section('title', 'Panel')

@section('content')
@php
    $isAgencyUser = auth()->user() && auth()->user()->isAgencyUser();
    $displayTz = $displayTz ?? (config('app.display_timezone') ?: 'America/New_York');
    $fmtLbs = function ($n) {
        $n = (float) $n;
        return number_format($n, $n == (int) $n ? 0 : 2);
    };
    $statusMeta = [
        'RECEIVED_MIAMI' => ['label' => 'RECIBIDO', 'event' => 'Escaneo de ingreso', 'location' => 'Miami Warehouse', 'class' => 'opx-badge-green'],
        'IN_TRANSIT' => ['label' => 'TRÁNSITO', 'event' => 'Consolidación / salida', 'location' => 'Miami Warehouse', 'class' => 'opx-badge-blue'],
        'IN_WAREHOUSE_NIC' => ['label' => 'ALMACÉN NIC', 'event' => 'Recepción en almacén', 'location' => 'Almacén Nicaragua', 'class' => 'opx-badge-purple'],
        'READY' => ['label' => 'LISTO RETIRO', 'event' => 'Disponible para retiro', 'location' => 'Almacén Nicaragua', 'class' => 'opx-badge-amber'],
        'DELIVERED' => ['label' => 'ENTREGADO', 'event' => 'Entrega en ventanilla', 'location' => 'Almacén Nicaragua', 'class' => 'opx-badge-gray'],
    ];
    $donutTotal = collect($statusDistribution)->sum('count');
    $donutStops = [];
    $acc = 0;
    foreach ($statusDistribution as $seg) {
        if ($donutTotal > 0 && $seg['count'] > 0) {
            $from = ($acc / $donutTotal) * 100;
            $acc += $seg['count'];
            $to = ($acc / $donutTotal) * 100;
            $donutStops[] = "{$seg['color']} {$from}% {$to}%";
        }
    }
    $donutGradient = $donutTotal > 0 ? 'conic-gradient(' . implode(', ', $donutStops) . ')' : 'conic-gradient(#e5e7eb 0% 100%)';
    $rankMax = max(1, ($agenciesRanking ?? collect())->max('packages_count') ?? 1);
    $rankColors = ['#059669', '#3b82f6', '#f59e0b', '#8b5cf6', '#64748b'];
@endphp

<div class="opx-page">
    {{-- ===== Encabezado ===== --}}
    <header class="opx-header">
        <div class="opx-header-left">
            <h1 class="opx-title">{{ $isAgencyUser ? 'Panel de tu Agencia' : 'Panel de Control Operativo' }}</h1>
            <p class="opx-date">{{ now($displayTz)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
        </div>
        <div class="opx-header-right">
            <nav class="opx-period-toggle">
                @foreach(['today' => 'Hoy', 'week' => 'Semana', 'month' => 'Mes'] as $key => $label)
                <a href="{{ route('dashboard', array_filter(['period' => $key, 'agency_id' => $agencyId, 'service_type' => $serviceType])) }}"
                   class="opx-period-btn {{ ($activePeriod ?? null) === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="opx-user">
                <div class="opx-user-info">
                    <span class="opx-user-name">{{ auth()->user()->name ?? 'Usuario' }}</span>
                    <span class="opx-user-role">{{ auth()->user() && auth()->user()->is_admin ? 'ADMINISTRADOR' : ($isAgencyUser ? 'AGENCIA' : 'OPERACIONES') }}</span>
                </div>
                <div class="opx-avatar">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
            </div>
        </div>
    </header>

    {{-- ===== Filtros (chips) ===== --}}
    <form method="GET" action="{{ route('dashboard') }}" class="opx-filters" id="opx-filters-form">
        @if(($activePeriod ?? null) !== null)
        <input type="hidden" name="period" value="{{ $activePeriod }}" id="opx-period-input">
        @endif
        @if($isAgencyUser)
        <input type="hidden" name="agency_id" value="{{ auth()->user()->agency_id }}">
        @else
        <label class="opx-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="opx-chip-icon"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
            <select name="agency_id" class="opx-chip-select" onchange="this.form.submit()">
                <option value="">Todas las Agencias</option>
                @foreach($agenciesForFilter ?? [] as $a)
                    <option value="{{ $a->id }}" {{ (int)($agencyId ?? 0) === (int)$a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
        </label>
        @endif
        <label class="opx-chip">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="opx-chip-icon"><path d="M5 17h14l2-6H7L5 3H2m6 18a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
            <select name="service_type" class="opx-chip-select" onchange="this.form.submit()">
                <option value="">Todos los Servicios</option>
                <option value="AIR" {{ ($serviceType ?? '') === 'AIR' ? 'selected' : '' }}>Aéreo</option>
                <option value="SEA" {{ ($serviceType ?? '') === 'SEA' ? 'selected' : '' }}>Marítimo</option>
            </select>
        </label>
        <label class="opx-chip">
            <span class="opx-chip-label">Desde</span>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="opx-chip-date" onchange="opxDateChanged(this.form)">
        </label>
        <label class="opx-chip">
            <span class="opx-chip-label">Hasta</span>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="opx-chip-date" onchange="opxDateChanged(this.form)">
        </label>
        @if(($isFiltered ?? false) || ($activePeriod ?? 'today') !== 'today')
        <a href="{{ route('dashboard') }}" class="opx-chip opx-chip-clear">Limpiar ✕</a>
        @endif
    </form>

    {{-- ===== KPIs ===== --}}
    <section class="opx-kpis">
        @php
            $kpiTotalLbs = (float) ($totalLbsPeriod ?? 0);
            $kpiAirLbs = (float) ($lbsAirPeriod ?? 0);
            $kpiSeaLbs = (float) ($lbsSeaPeriod ?? 0);
            $kpiAirBar = $kpiTotalLbs > 0 ? round(($kpiAirLbs / $kpiTotalLbs) * 100) : 0;
            $kpiSeaBar = $kpiTotalLbs > 0 ? round(($kpiSeaLbs / $kpiTotalLbs) * 100) : 0;
            $barToday = max(1, max($packagesToday ?? 0, $packagesYesterday ?? 0));
        @endphp
        <article class="opx-kpi">
            <span class="opx-kpi-label">Volumen Total (lbs)</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiTotalLbs) }}</span>
                <span class="opx-kpi-sub">{{ $periodLabel }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill opx-fill-green" style="width: {{ $kpiTotalLbs > 0 ? 100 : 0 }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Libras Aéreas</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiAirLbs) }}</span>
                <span class="opx-kpi-sub">lbs · {{ $periodLabel }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill opx-fill-green" style="width: {{ $kpiAirBar }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Libras Marítimas</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiSeaLbs) }}</span>
                <span class="opx-kpi-sub">lbs · {{ $periodLabel }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill opx-fill-blue" style="width: {{ $kpiSeaBar }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Bultos Recibidos del Día</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ number_format($packagesToday ?? 0) }}</span>
                <span class="opx-kpi-sub">Ayer: {{ number_format($packagesYesterday ?? 0) }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill opx-fill-green" style="width: {{ round((($packagesToday ?? 0) / $barToday) * 100) }}%"></div></div>
        </article>
    </section>

    {{-- ===== Gráfico semanal + Donut ===== --}}
    <section class="opx-row opx-row-2-1">
        <article class="opx-card">
            <div class="opx-card-head">
                <div>
                    <h2 class="opx-card-title">Volumen de Carga Semanal</h2>
                    <p class="opx-card-sub">Comparativa de peso por modalidad de transporte · últimos 7 días</p>
                </div>
                <div class="opx-legend">
                    <span class="opx-legend-item"><span class="opx-dot" style="background:#059669"></span> Aéreo</span>
                    <span class="opx-legend-item"><span class="opx-dot" style="background:#3b82f6"></span> Marítimo</span>
                </div>
            </div>
            <div class="opx-chart">
                @foreach($weeklyVolume ?? [] as $day)
                <div class="opx-chart-col">
                    <div class="opx-chart-bars">
                        <div class="opx-chart-bar" style="height: {{ $day['air'] > 0 ? max(3, round(($day['air'] / $weeklyMax) * 100)) : 0 }}%; background:#059669"
                             title="Aéreo: {{ number_format($day['air'], 1) }} lbs"></div>
                        <div class="opx-chart-bar" style="height: {{ $day['sea'] > 0 ? max(3, round(($day['sea'] / $weeklyMax) * 100)) : 0 }}%; background:#3b82f6"
                             title="Marítimo: {{ number_format($day['sea'], 1) }} lbs"></div>
                    </div>
                    <span class="opx-chart-label">{{ $day['label'] }}</span>
                </div>
                @endforeach
            </div>
        </article>

        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Distribución de Estados</h2>
                <span class="opx-pill">{{ $periodLabel }}</span>
            </div>
            <div class="opx-donut-wrap">
                <div class="opx-donut" style="background: {{ $donutGradient }}">
                    <div class="opx-donut-hole">
                        <span class="opx-donut-total">{{ number_format($donutTotal) }}</span>
                        <span class="opx-donut-caption">TOTAL</span>
                    </div>
                </div>
            </div>
            <div class="opx-donut-legend">
                @foreach($statusDistribution ?? [] as $seg)
                <span class="opx-legend-item">
                    <span class="opx-dot" style="background: {{ $seg['color'] }}"></span>
                    {{ $seg['label'] }} <strong>{{ number_format($seg['count']) }}</strong>
                </span>
                @endforeach
            </div>
        </article>
    </section>

    {{-- ===== Heatmap + Top agencias ===== --}}
    <section class="opx-row {{ $isAgencyUser ? '' : 'opx-row-1-1' }}">
        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Actividad de Recepción</h2>
                <span class="opx-pill">Últimas 4 semanas</span>
            </div>
            <div class="opx-heatmap">
                <div class="opx-heatmap-days">
                    @foreach(['LUN','MAR','MIE','JUE','VIE','SAB','DOM'] as $d)
                    <span>{{ $d }}</span>
                    @endforeach
                </div>
                @foreach($heatmapWeeks ?? [] as $week)
                <div class="opx-heatmap-week">
                    @foreach($week as $cell)
                    <div class="opx-heatcell opx-heat-{{ $cell['count'] === null ? 'future' : $cell['level'] }}"
                         title="{{ $cell['date'] }}{{ $cell['count'] !== null ? ' · ' . $cell['count'] . ' paquetes' : '' }}"></div>
                    @endforeach
                </div>
                @endforeach
            </div>
            <div class="opx-heatmap-scale">
                <span>Menos</span>
                <div class="opx-heatcell opx-heat-0"></div>
                <div class="opx-heatcell opx-heat-1"></div>
                <div class="opx-heatcell opx-heat-2"></div>
                <div class="opx-heatcell opx-heat-3"></div>
                <div class="opx-heatcell opx-heat-4"></div>
                <span>Más volumen</span>
            </div>
        </article>

        @if(!$isAgencyUser)
        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Agencias Top Performance</h2>
                <span class="opx-pill">{{ $periodLabel }}</span>
            </div>
            @if(($agenciesRanking ?? collect())->isNotEmpty())
            <div class="opx-ranking">
                @foreach($agenciesRanking->take(5) as $i => $row)
                <div class="opx-rank-item">
                    <div class="opx-rank-head">
                        <span class="opx-rank-name">{{ $row['agency']->name ?? '—' }}</span>
                        <span class="opx-rank-count">{{ number_format($row['packages_count']) }} {{ $row['packages_count'] === 1 ? 'paquete' : 'paquetes' }} · {{ number_format($row['total_lbs'], 1) }} lbs</span>
                    </div>
                    <div class="opx-bar"><div class="opx-bar-fill" style="width: {{ round(($row['packages_count'] / $rankMax) * 100) }}%; background: {{ $rankColors[$i % count($rankColors)] }}"></div></div>
                </div>
                @endforeach
            </div>
            @else
            <p class="opx-empty">Sin movimientos de agencias en el periodo.</p>
            @endif
            <a href="{{ route('reporte.solicitar') }}" class="opx-card-footer-link">Ver reporte completo por agencia <span>›</span></a>
        </article>
        @endif
    </section>

    {{-- ===== Alertas ===== --}}
    @if(!empty($alerts) && count($alerts) > 0)
    <section class="opx-alerts">
        @foreach($alerts as $alert)
        <a href="{{ $alert['url'] ?? '#' }}" class="opx-alert">
            <span class="opx-alert-count">{{ $alert['count'] }}</span>
            <span class="opx-alert-title">{{ $alert['title'] }}</span>
            <span class="opx-alert-arrow">→</span>
        </a>
        @endforeach
    </section>
    @endif

    {{-- ===== Registros recientes ===== --}}
    <section class="opx-card opx-table-card">
        <div class="opx-card-head">
            <h2 class="opx-card-title">Registros Operativos Recientes</h2>
            <a href="{{ route('reporte.solicitar') }}" class="opx-btn-outline">Exportar reporte</a>
        </div>
        <div class="opx-table-wrap">
            <table class="opx-table">
                <thead>
                    <tr>
                        <th>ID Paquete</th>
                        <th>Evento</th>
                        <th>Agencia</th>
                        <th>Ubicación</th>
                        <th>Hora</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRecords ?? [] as $rec)
                    @php $meta = $statusMeta[$rec->status] ?? ['label' => $rec->status, 'event' => 'Actualización', 'location' => '—', 'class' => 'opx-badge-gray']; @endphp
                    <tr>
                        <td>
                            <a href="{{ route($isAgencyUser ? 'packages.show' : 'preregistrations.show', $rec->id) }}" class="opx-table-id">
                                #{{ $rec->warehouse_code ?? $rec->id }}
                            </a>
                        </td>
                        <td>{{ $meta['event'] }}</td>
                        <td>{{ $rec->agency->name ?? '—' }}</td>
                        <td>{{ $meta['location'] }}</td>
                        <td class="opx-table-time">{{ $rec->updated_at?->timezone($displayTz)->format('d/m H:i') }}</td>
                        <td><span class="opx-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="opx-empty">No hay registros recientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
function opxDateChanged(form) {
    var periodInput = document.getElementById('opx-period-input');
    if (periodInput) periodInput.remove();
    form.submit();
}
</script>

<style>
.opx-page { max-width: 76rem; margin: 0 auto; padding: 1.25rem 0 2.5rem; width: 100%; }

/* ===== Header ===== */
.opx-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.opx-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; }
.opx-date { margin: 0.15rem 0 0; font-size: 0.85rem; color: #64748b; text-transform: capitalize; }
.opx-header-right { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.opx-period-toggle { display: inline-flex; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.6rem; padding: 0.2rem; gap: 0.15rem; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
.opx-period-btn { padding: 0.35rem 0.9rem; font-size: 0.82rem; font-weight: 600; color: #64748b; border-radius: 0.45rem; text-decoration: none; transition: background 0.15s, color 0.15s; }
.opx-period-btn:hover { color: #0f172a; }
.opx-period-btn.is-active { background: #0f172a; color: #fff; }
.opx-user { display: flex; align-items: center; gap: 0.6rem; }
.opx-user-info { display: flex; flex-direction: column; align-items: flex-end; }
.opx-user-name { font-size: 0.85rem; font-weight: 700; color: #0f172a; }
.opx-user-role { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.06em; color: #059669; }
.opx-avatar { width: 2.4rem; height: 2.4rem; border-radius: 50%; background: linear-gradient(135deg, #059669, #059669); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }

/* ===== Filters (chips) ===== */
.opx-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e2e8f0; }
.opx-chip { display: inline-flex; align-items: center; gap: 0.4rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.55rem; padding: 0.35rem 0.7rem; font-size: 0.82rem; color: #334155; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.opx-chip-icon { width: 0.95rem; height: 0.95rem; color: #64748b; flex-shrink: 0; }
.opx-chip-select { border: none; background: transparent; font-size: 0.82rem; font-weight: 600; color: #334155; cursor: pointer; outline: none; max-width: 12rem; }
.opx-chip-label { font-size: 0.72rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
.opx-chip-date { border: none; background: transparent; font-size: 0.82rem; font-weight: 600; color: #334155; outline: none; }
.opx-chip-clear { color: #dc2626; font-weight: 600; text-decoration: none; }
.opx-chip-clear:hover { background: #fef2f2; border-color: #fecaca; }

/* ===== KPI cards ===== */
.opx-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(215px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
.opx-kpi { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; padding: 1.1rem 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; gap: 0.55rem; }
.opx-kpi-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #64748b; }
.opx-kpi-value-row { display: flex; align-items: baseline; gap: 0.5rem; flex-wrap: wrap; }
.opx-kpi-value { font-size: 1.75rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; line-height: 1; }
.opx-kpi-sub { font-size: 0.72rem; color: #94a3b8; font-weight: 500; }
.opx-kpi-delta { font-size: 0.72rem; font-weight: 700; }
.opx-kpi-delta.is-up { color: #059669; }
.opx-kpi-delta.is-down { color: #dc2626; }

/* Progress bars */
.opx-bar { height: 0.4rem; background: #e2e8f0; border-radius: 9999px; overflow: hidden; }
.opx-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.4s ease; }
.opx-fill-green { background: #059669; }
.opx-fill-blue { background: #3b82f6; }

/* ===== Cards / rows ===== */
.opx-row { display: grid; gap: 1rem; margin-bottom: 1.25rem; }
.opx-row-2-1 { grid-template-columns: 2fr 1fr; }
.opx-row-1-1 { grid-template-columns: 1fr 1fr; }
@media (max-width: 900px) { .opx-row-2-1, .opx-row-1-1 { grid-template-columns: 1fr; } }
.opx-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.85rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; }
.opx-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1rem; }
.opx-card-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
.opx-card-sub { margin: 0.2rem 0 0; font-size: 0.78rem; color: #94a3b8; }
.opx-pill { font-size: 0.72rem; font-weight: 600; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 9999px; padding: 0.25rem 0.7rem; }
.opx-legend { display: flex; gap: 0.9rem; flex-wrap: wrap; }
.opx-legend-item { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #475569; }
.opx-legend-item strong { color: #0f172a; }
.opx-dot { width: 0.55rem; height: 0.55rem; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.opx-empty { color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1.5rem 0; margin: 0; }
.opx-card-footer-link { margin-top: auto; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.82rem; font-weight: 600; color: #059669; text-decoration: none; display: flex; justify-content: space-between; align-items: center; }
.opx-card-footer-link:hover { color: #047857; }

/* ===== Weekly chart ===== */
.opx-chart { display: flex; align-items: flex-end; gap: 0.5rem; height: 13rem; padding-top: 0.5rem; }
.opx-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; height: 100%; }
.opx-chart-bars { flex: 1; display: flex; align-items: flex-end; justify-content: center; gap: 0.3rem; width: 100%; border-bottom: 1px solid #e2e8f0; padding: 0 0.35rem; }
.opx-chart-bar { width: 100%; max-width: 1.6rem; border-radius: 0.25rem 0.25rem 0 0; min-height: 0; }
.opx-chart-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; color: #94a3b8; }

/* ===== Donut ===== */
.opx-donut-wrap { display: flex; justify-content: center; padding: 0.5rem 0 1rem; }
.opx-donut { width: 11rem; height: 11rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.opx-donut-hole { width: 7.2rem; height: 7.2rem; border-radius: 50%; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 0 0 1px #f1f5f9 inset; }
.opx-donut-total { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
.opx-donut-caption { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.1em; color: #94a3b8; margin-top: 0.25rem; }
.opx-donut-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 0.75rem; }

/* ===== Heatmap ===== */
.opx-heatmap { display: flex; flex-direction: column; gap: 0.45rem; }
.opx-heatmap-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.45rem; }
.opx-heatmap-days span { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; color: #94a3b8; text-align: center; }
.opx-heatmap-week { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.45rem; }
.opx-heatcell { aspect-ratio: 1.6 / 1; border-radius: 0.35rem; min-height: 1.6rem; }
.opx-heat-future { background: transparent; border: 1px dashed #e2e8f0; }
.opx-heat-0 { background: #f1f5f9; }
.opx-heat-1 { background: #d1fae5; }
.opx-heat-2 { background: #6ee7b7; }
.opx-heat-3 { background: #10b981; }
.opx-heat-4 { background: #047857; }
.opx-heatmap-scale { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.9rem; font-size: 0.7rem; color: #94a3b8; }
.opx-heatmap-scale .opx-heatcell { width: 1.1rem; min-height: 0.8rem; aspect-ratio: auto; height: 0.8rem; flex-shrink: 0; }

/* ===== Ranking agencias ===== */
.opx-ranking { display: flex; flex-direction: column; gap: 1rem; }
.opx-rank-head { display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; margin-bottom: 0.35rem; flex-wrap: wrap; }
.opx-rank-name { font-size: 0.85rem; font-weight: 700; color: #0f172a; }
.opx-rank-count { font-size: 0.72rem; color: #64748b; font-weight: 500; }

/* ===== Alertas ===== */
.opx-alerts { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem; }
.opx-alert { display: flex; align-items: center; gap: 0.75rem; background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #d97706; border-radius: 0.6rem; padding: 0.65rem 1rem; text-decoration: none; transition: background 0.15s; }
.opx-alert:hover { background: #fef3c7; }
.opx-alert-count { font-weight: 800; font-size: 0.95rem; color: #b45309; background: #fef3c7; border-radius: 0.4rem; padding: 0.15rem 0.55rem; min-width: 2rem; text-align: center; }
.opx-alert-title { flex: 1; font-size: 0.85rem; font-weight: 600; color: #92400e; }
.opx-alert-arrow { color: #d97706; }

/* ===== Tabla ===== */
.opx-table-card { padding: 0; }
.opx-table-card .opx-card-head { padding: 1.25rem 1.25rem 0; }
.opx-btn-outline { font-size: 0.8rem; font-weight: 600; color: #334155; background: #fff; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.4rem 0.9rem; text-decoration: none; transition: background 0.15s; }
.opx-btn-outline:hover { background: #f8fafc; color: #0f172a; }
.opx-table-wrap { overflow-x: auto; }
.opx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.opx-table th { text-align: left; padding: 0.7rem 1.25rem; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; background: #f8fafc; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
.opx-table td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
.opx-table tbody tr:last-child td { border-bottom: none; }
.opx-table tbody tr:hover { background: #f8fafc; }
.opx-table-id { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; color: #059669; text-decoration: none; }
.opx-table-id:hover { text-decoration: underline; }
.opx-table-time { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.78rem; color: #64748b; white-space: nowrap; }
.opx-badge { display: inline-block; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.05em; padding: 0.25rem 0.55rem; border-radius: 0.35rem; white-space: nowrap; }
.opx-badge-green { background: #d1fae5; color: #047857; }
.opx-badge-blue { background: #dbeafe; color: #1d4ed8; }
.opx-badge-purple { background: #ede9fe; color: #6d28d9; }
.opx-badge-amber { background: #fef3c7; color: #b45309; }
.opx-badge-gray { background: #f1f5f9; color: #475569; }

@media (max-width: 640px) {
    .opx-header-right { width: 100%; justify-content: space-between; }
    .opx-kpi-value { font-size: 1.45rem; }
}
</style>
@endsection
