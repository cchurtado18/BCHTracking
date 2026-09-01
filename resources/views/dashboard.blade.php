@extends('layouts.app')

@section('title', 'Panel')

@section('content')
@php
    $user = auth()->user();
    $isAgencyUser = $user && $user->isAgencyUser();
    $displayTz = $displayTz ?? (config('app.display_timezone') ?: 'America/New_York');
    $hour = now($displayTz)->hour;
    $hello = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $firstName = explode(' ', trim((string) ($user->name ?? 'Usuario')))[0];
    $roleLabel = $user && $user->is_admin ? 'Administración' : ($isAgencyUser ? 'Agencia' : 'Operaciones');
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
    $rankColors = ['#0A2D6F', '#1E4FA8', '#3b82f6', '#64748b', '#94a3b8'];
    $kpiTotalLbs = (float) ($totalLbsPeriod ?? 0);
    $kpiAirLbs = (float) ($lbsAirPeriod ?? 0);
    $kpiSeaLbs = (float) ($lbsSeaPeriod ?? 0);
    $kpiAirBar = $kpiTotalLbs > 0 ? round(($kpiAirLbs / $kpiTotalLbs) * 100) : 0;
    $kpiSeaBar = $kpiTotalLbs > 0 ? round(($kpiSeaLbs / $kpiTotalLbs) * 100) : 0;
    $barToday = max(1, max($packagesToday ?? 0, $packagesYesterday ?? 0));
    $inFlow = collect($pipeline ?? [])->whereNotIn('key', ['DELIVERED'])->sum('count');
@endphp

<div class="opx-page">
    <section class="opx-hero">
        <div class="opx-hero-copy">
            <p class="opx-hero-kicker">PrimeTrack Group · {{ $roleLabel }}</p>
            <h1 class="opx-hero-title">{{ $hello }}, {{ $firstName }}</h1>
            <p class="opx-hero-lead">
                Esta es su vista de entrada: el estado del flujo Miami → Nicaragua y los movimientos de {{ strtolower($periodLabel) }}.
            </p>
            <p class="opx-hero-date">{{ now($displayTz)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }} · hora Miami</p>
        </div>
        <div class="opx-hero-stats">
            <div>
                <span class="opx-hero-stat-label">En flujo ahora</span>
                <strong>{{ number_format($inFlow) }}</strong>
                <small>paquetes sin entregar</small>
            </div>
            <div>
                <span class="opx-hero-stat-label">{{ $periodLabel }}</span>
                <strong>{{ number_format($packagesInPeriod ?? 0) }}</strong>
                <small>ingresos · {{ $fmtLbs($kpiTotalLbs) }} lbs</small>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('dashboard') }}" class="opx-toolbar" id="opx-filters-form">
        @if(($activePeriod ?? null) !== null)
        <input type="hidden" name="period" value="{{ $activePeriod }}" id="opx-period-input">
        @endif
        <nav class="opx-period-toggle">
            @foreach(['today' => 'Hoy', 'week' => 'Semana', 'month' => 'Mes'] as $key => $label)
            <a href="{{ route('dashboard', array_filter(['period' => $key, 'agency_id' => $agencyId, 'service_type' => $serviceType])) }}"
               class="opx-period-btn {{ ($activePeriod ?? null) === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        @if($isAgencyUser)
        <input type="hidden" name="agency_id" value="{{ $user->agency_id }}">
        @else
        <label class="opx-chip">
            <select name="agency_id" class="opx-chip-select" onchange="this.form.submit()">
                <option value="">Todas las agencias</option>
                @foreach($agenciesForFilter ?? [] as $a)
                <option value="{{ $a->id }}" {{ (int)($agencyId ?? 0) === (int)$a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
        </label>
        @endif
        <label class="opx-chip">
            <select name="service_type" class="opx-chip-select" onchange="this.form.submit()">
                <option value="">Todos los servicios</option>
                <option value="AIR" {{ ($serviceType ?? '') === 'AIR' ? 'selected' : '' }}>Aéreo</option>
                <option value="SEA" {{ ($serviceType ?? '') === 'SEA' ? 'selected' : '' }}>Marítimo</option>
                <option value="CFT" {{ ($serviceType ?? '') === 'CFT' ? 'selected' : '' }}>Pie cúbico</option>
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
        <a href="{{ route('dashboard') }}" class="opx-chip opx-chip-clear">Limpiar</a>
        @endif
    </form>

    <section class="opx-kpis">
        <article class="opx-kpi">
            <span class="opx-kpi-label">Volumen del período</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiTotalLbs) }}</span>
                <span class="opx-kpi-sub">lbs · {{ $periodLabel }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill" style="width: {{ $kpiTotalLbs > 0 ? 100 : 0 }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Aéreo</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiAirLbs) }}</span>
                <span class="opx-kpi-sub">{{ $airSharePct }}% del volumen</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill" style="width: {{ $kpiAirBar }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Marítimo</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ $fmtLbs($kpiSeaLbs) }}</span>
                <span class="opx-kpi-sub">{{ $seaSharePct }}% del volumen</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill opx-fill-blue" style="width: {{ $kpiSeaBar }}%"></div></div>
        </article>
        <article class="opx-kpi">
            <span class="opx-kpi-label">Bultos de hoy</span>
            <div class="opx-kpi-value-row">
                <span class="opx-kpi-value">{{ number_format($packagesToday ?? 0) }}</span>
                <span class="opx-kpi-sub">Ayer: {{ number_format($packagesYesterday ?? 0) }}</span>
            </div>
            <div class="opx-bar"><div class="opx-bar-fill" style="width: {{ round((($packagesToday ?? 0) / $barToday) * 100) }}%"></div></div>
        </article>
    </section>

    <section class="opx-row opx-row-2-1">
        <article class="opx-card">
            <div class="opx-card-head">
                <div>
                    <h2 class="opx-card-title">Volumen de los últimos 7 días</h2>
                    <p class="opx-card-sub">Libras recibidas por modalidad</p>
                </div>
                <div class="opx-legend">
                    <span class="opx-legend-item"><span class="opx-dot" style="background:#0A2D6F"></span> Aéreo</span>
                    <span class="opx-legend-item"><span class="opx-dot" style="background:#1E4FA8"></span> Marítimo</span>
                </div>
            </div>
            <div class="opx-chart">
                @foreach($weeklyVolume ?? [] as $day)
                @php
                    $airH = $day['air'] > 0 ? max(3, round(($day['air'] / $weeklyMax) * 100)) : 0;
                    $seaH = $day['sea'] > 0 ? max(3, round(($day['sea'] / $weeklyMax) * 100)) : 0;
                    $dayTitle = $day['date_label'] ?? $day['label'];
                @endphp
                <div class="opx-chart-col">
                    <div class="opx-chart-bars">
                        <div class="opx-chart-bar {{ $airH > 0 ? '' : 'is-empty' }}" style="height: {{ $airH }}%; background:#0A2D6F">
                            <span class="opx-chart-tip">
                                <em>{{ $dayTitle }}</em>
                                <strong>Aéreo</strong>
                                {{ number_format($day['air'], 1) }} lbs
                            </span>
                        </div>
                        <div class="opx-chart-bar {{ $seaH > 0 ? '' : 'is-empty' }}" style="height: {{ $seaH }}%; background:#1E4FA8">
                            <span class="opx-chart-tip">
                                <em>{{ $dayTitle }}</em>
                                <strong>Marítimo</strong>
                                {{ number_format($day['sea'], 1) }} lbs
                            </span>
                        </div>
                    </div>
                    <span class="opx-chart-label">{{ $day['label'] }}</span>
                </div>
                @endforeach
            </div>
        </article>

        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Actividad del período</h2>
                <span class="opx-pill">{{ $periodLabel }}</span>
            </div>
            <div class="opx-donut-wrap">
                <div class="opx-donut" style="background: {{ $donutGradient }}">
                    <div class="opx-donut-hole">
                        <span class="opx-donut-total">{{ number_format($donutTotal) }}</span>
                        <span class="opx-donut-caption">INGRESOS</span>
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

    <section class="opx-row {{ $isAgencyUser ? '' : 'opx-row-1-1' }}">
        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Actividad de recepción</h2>
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
                    <div class="opx-heatcell opx-heat-{{ $cell['count'] === null ? 'future' : $cell['level'] }}{{ $cell['count'] === null ? '' : ' has-tip' }}">
                        @if($cell['count'] !== null)
                        <span class="opx-heat-tip">
                            <em>{{ $cell['date_label'] ?? $cell['date'] }}</em>
                            <strong>{{ $cell['count'] === 1 ? '1 paquete' : number_format($cell['count']).' paquetes' }}</strong>
                        </span>
                        @endif
                    </div>
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
                <span>Más</span>
            </div>
        </article>

        @if(!$isAgencyUser)
        <article class="opx-card">
            <div class="opx-card-head">
                <h2 class="opx-card-title">Agencias con más movimiento</h2>
                <span class="opx-pill">{{ $periodLabel }}</span>
            </div>
            @if(($agenciesRanking ?? collect())->isNotEmpty())
            <div class="opx-ranking">
                @foreach($agenciesRanking->take(5) as $i => $row)
                <div class="opx-rank-item">
                    <div class="opx-rank-head">
                        <span class="opx-rank-name">{{ $row['agency']->name ?? '—' }}</span>
                        <span class="opx-rank-count">{{ number_format($row['packages_count']) }} · {{ number_format($row['total_lbs'], 1) }} lbs</span>
                    </div>
                    <div class="opx-bar"><div class="opx-bar-fill" style="width: {{ round(($row['packages_count'] / $rankMax) * 100) }}%; background: {{ $rankColors[$i % count($rankColors)] }}"></div></div>
                </div>
                @endforeach
            </div>
            @else
            <p class="opx-empty">Sin movimientos de agencias en el período.</p>
            @endif
            <a href="{{ route('reporte.solicitar') }}" class="opx-card-footer-link">Ver reporte por agencia <span>›</span></a>
        </article>
        @endif
    </section>

    <section class="opx-card opx-table-card">
        <div class="opx-card-head">
            <div>
                <h2 class="opx-card-title">Últimos movimientos</h2>
                <p class="opx-card-sub">Registros que se actualizaron más reciente</p>
            </div>
            <a href="{{ route('reporte.solicitar') }}" class="opx-btn-outline">Exportar reporte</a>
        </div>
        <div class="opx-table-wrap">
            <table class="opx-table">
                <thead>
                    <tr>
                        <th>Guía</th>
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
.app-main-inner:has(.opx-page) { max-width: none; }
.opx-page { max-width: none; margin: 0; padding: 0.35rem 0 2.5rem; width: 100%; }

.opx-hero {
    display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1.25rem;
    background: linear-gradient(135deg, #0A2D6F 0%, #123A86 52%, #1E4FA8 100%);
    color: #fff; border-radius: 1.15rem; padding: 1.45rem 1.5rem 1.5rem;
    margin-bottom: 1rem; box-shadow: 0 16px 36px rgba(10, 45, 111, 0.22);
}
.opx-hero-kicker { margin: 0 0 0.35rem; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(255,255,255,0.7); }
.opx-hero-title { margin: 0; font-size: 1.7rem; font-weight: 800; letter-spacing: -0.03em; }
.opx-hero-lead { margin: 0.45rem 0 0; max-width: 38rem; font-size: 0.92rem; line-height: 1.5; color: rgba(255,255,255,0.82); }
.opx-hero-date { margin: 0.7rem 0 0; font-size: 0.8rem; color: rgba(255,255,255,0.58); text-transform: capitalize; }
.opx-hero-stats { display: flex; gap: 0.75rem; align-items: stretch; }
.opx-hero-stats > div {
    min-width: 8.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.16);
    border-radius: 0.85rem; padding: 0.85rem 1rem; display: flex; flex-direction: column; gap: 0.15rem;
}
.opx-hero-stat-label { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.65); }
.opx-hero-stats strong { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.03em; }
.opx-hero-stats small { font-size: 0.72rem; color: rgba(255,255,255,0.65); }

.opx-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
.opx-period-toggle { display: inline-flex; background: #fff; border: 1px solid #E8EEF8; border-radius: 0.65rem; padding: 0.2rem; gap: 0.15rem; }
.opx-period-btn { padding: 0.38rem 0.85rem; font-size: 0.8rem; font-weight: 700; color: #64748b; border-radius: 0.5rem; text-decoration: none; }
.opx-period-btn.is-active { background: #0A2D6F; color: #fff; }
.opx-chip { display: inline-flex; align-items: center; gap: 0.4rem; background: #fff; border: 1px solid #E8EEF8; border-radius: 0.6rem; padding: 0.38rem 0.7rem; font-size: 0.8rem; }
.opx-chip-select, .opx-chip-date { border: none; background: transparent; font-size: 0.8rem; font-weight: 700; color: #334155; outline: none; }
.opx-chip-label { font-size: 0.68rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; }
.opx-chip-clear { color: #D64545; font-weight: 700; text-decoration: none; }

.opx-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; margin-bottom: 1rem; }
.opx-kpi { background: #fff; border: 1px solid #E8EEF8; border-radius: 0.9rem; padding: 1rem 1.1rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); display: flex; flex-direction: column; gap: 0.5rem; }
.opx-kpi-label { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; }
.opx-kpi-value-row { display: flex; align-items: baseline; gap: 0.45rem; flex-wrap: wrap; }
.opx-kpi-value { font-size: 1.55rem; font-weight: 800; color: #0f172a; letter-spacing: -0.03em; line-height: 1; }
.opx-kpi-sub { font-size: 0.72rem; color: #94a3b8; font-weight: 600; }
.opx-bar { height: 0.38rem; background: #EEF2F7; border-radius: 9999px; overflow: hidden; }
.opx-bar-fill { height: 100%; border-radius: 9999px; background: #0A2D6F; }
.opx-fill-blue { background: #1E4FA8; }

.opx-row { display: grid; gap: 1rem; margin-bottom: 1rem; }
.opx-row-2-1 { grid-template-columns: 2fr 1fr; }
.opx-row-1-1 { grid-template-columns: 1fr 1fr; }
.opx-card { background: #fff; border: 1px solid #E8EEF8; border-radius: 0.95rem; padding: 1.15rem 1.2rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04); display: flex; flex-direction: column; }
.opx-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.95rem; }
.opx-card-title { margin: 0; font-size: 1.02rem; font-weight: 800; color: #0f172a; }
.opx-card-sub { margin: 0.2rem 0 0; font-size: 0.78rem; color: #94a3b8; }
.opx-pill { font-size: 0.7rem; font-weight: 700; color: #475569; background: #F4F8FD; border: 1px solid #E8EEF8; border-radius: 9999px; padding: 0.22rem 0.65rem; }
.opx-legend { display: flex; gap: 0.85rem; flex-wrap: wrap; }
.opx-legend-item { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #475569; }
.opx-legend-item strong { color: #0f172a; }
.opx-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; display: inline-block; }
.opx-empty { color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 1.4rem 0; margin: 0; }
.opx-card-footer-link { margin-top: auto; padding-top: 0.95rem; border-top: 1px solid #F1F5F9; font-size: 0.82rem; font-weight: 700; color: #0A2D6F; text-decoration: none; display: flex; justify-content: space-between; }

.opx-chart { display: flex; align-items: flex-end; gap: 0.55rem; height: 14.5rem; padding: 1.75rem 0.25rem 0; overflow: visible; }
.opx-chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.45rem; height: 100%; min-width: 0; }
.opx-chart-bars { flex: 1; display: flex; align-items: flex-end; justify-content: center; gap: 0.32rem; width: 100%; border-bottom: 1px solid #E8EEF8; padding: 0 0.35rem; overflow: visible; }
.opx-chart-bar {
    position: relative; width: 100%; max-width: 2.1rem; min-height: 0;
    border-radius: 0.28rem 0.28rem 0 0; cursor: pointer;
    transition: filter 0.12s ease, transform 0.12s ease;
}
.opx-chart-bar.is-empty { pointer-events: none; }
.opx-chart-bar:hover { filter: brightness(1.12); transform: translateY(-2px); }
.opx-chart-tip {
    position: absolute; left: 50%; bottom: calc(100% + 10px); transform: translateX(-50%) translateY(4px);
    background: #0A2D6F; color: #fff; border-radius: 0.55rem; padding: 0.45rem 0.65rem;
    font-size: 0.72rem; line-height: 1.25; white-space: nowrap; text-align: center;
    box-shadow: 0 10px 22px rgba(10, 45, 111, 0.28);
    opacity: 0; pointer-events: none; z-index: 20; transition: opacity 0.12s ease, transform 0.12s ease;
}
.opx-chart-tip::after {
    content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
    border: 6px solid transparent; border-top-color: #0A2D6F;
}
.opx-chart-tip em { display: block; font-style: normal; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(255,255,255,0.7); margin-bottom: 0.12rem; }
.opx-chart-tip strong { display: block; font-size: 0.78rem; font-weight: 800; }
.opx-chart-bar:hover .opx-chart-tip { opacity: 1; transform: translateX(-50%) translateY(0); }
.opx-chart-label { font-size: 0.66rem; font-weight: 800; letter-spacing: 0.05em; color: #94a3b8; }

.opx-donut-wrap { display: flex; justify-content: center; padding: 0.35rem 0 0.9rem; }
.opx-donut { width: 10.5rem; height: 10.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.opx-donut-hole { width: 6.8rem; height: 6.8rem; border-radius: 50%; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.opx-donut-total { font-size: 1.45rem; font-weight: 800; color: #0f172a; line-height: 1; }
.opx-donut-caption { font-size: 0.6rem; font-weight: 800; letter-spacing: 0.1em; color: #94a3b8; margin-top: 0.2rem; }
.opx-donut-legend { display: grid; grid-template-columns: 1fr 1fr; gap: 0.45rem 0.7rem; }

.opx-heatmap { display: flex; flex-direction: column; gap: 0.4rem; overflow: visible; }
.opx-heatmap-days, .opx-heatmap-week { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.4rem; }
.opx-heatmap-week { overflow: visible; }
.opx-heatmap-days span { font-size: 0.6rem; font-weight: 800; letter-spacing: 0.05em; color: #94a3b8; text-align: center; }
.opx-heatcell { position: relative; aspect-ratio: 1.55 / 1; border-radius: 0.35rem; min-height: 1.45rem; }
.opx-heatcell.has-tip { cursor: pointer; }
.opx-heatcell.has-tip:hover { z-index: 8; filter: brightness(1.08); }
.opx-heat-future { background: transparent; border: 1px dashed #E8EEF8; }
.opx-heat-0 { background: #F1F5F9; }
.opx-heat-1 { background: #E8EEF8; }
.opx-heat-2 { background: #9BB5D9; }
.opx-heat-3 { background: #1E4FA8; }
.opx-heat-4 { background: #0A2D6F; }
.opx-heat-tip {
    position: absolute; left: 50%; bottom: calc(100% + 8px); transform: translateX(-50%) translateY(4px);
    background: #0A2D6F; color: #fff; border-radius: 0.55rem; padding: 0.42rem 0.6rem;
    font-size: 0.72rem; line-height: 1.25; white-space: nowrap; text-align: center;
    box-shadow: 0 10px 22px rgba(10, 45, 111, 0.28);
    opacity: 0; pointer-events: none; z-index: 20; transition: opacity 0.12s ease, transform 0.12s ease;
}
.opx-heat-tip::after {
    content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%);
    border: 6px solid transparent; border-top-color: #0A2D6F;
}
.opx-heat-tip em { display: block; font-style: normal; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(255,255,255,0.7); margin-bottom: 0.1rem; }
.opx-heat-tip strong { display: block; font-size: 0.78rem; font-weight: 800; }
.opx-heatcell.has-tip:hover .opx-heat-tip { opacity: 1; transform: translateX(-50%) translateY(0); }
.opx-heatmap-scale { display: flex; align-items: center; gap: 0.3rem; margin-top: 0.8rem; font-size: 0.68rem; color: #94a3b8; }
.opx-heatmap-scale .opx-heatcell { width: 1rem; min-height: 0.7rem; aspect-ratio: auto; height: 0.7rem; flex-shrink: 0; }

.opx-ranking { display: flex; flex-direction: column; gap: 0.9rem; }
.opx-rank-head { display: flex; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.3rem; flex-wrap: wrap; }
.opx-rank-name { font-size: 0.85rem; font-weight: 800; color: #0f172a; }
.opx-rank-count { font-size: 0.72rem; color: #64748b; font-weight: 600; }

.opx-table-card { padding: 0; }
.opx-table-card .opx-card-head { padding: 1.15rem 1.2rem 0; }
.opx-btn-outline { font-size: 0.8rem; font-weight: 700; color: #334155; background: #fff; border: 1px solid #d1d9e6; border-radius: 0.55rem; padding: 0.4rem 0.85rem; text-decoration: none; }
.opx-table-wrap { overflow-x: auto; }
.opx-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
.opx-table th { text-align: left; padding: 0.65rem 1.15rem; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; color: #94a3b8; background: #FAFCFF; border-top: 1px solid #F1F5F9; border-bottom: 1px solid #E8EEF8; }
.opx-table td { padding: 0.75rem 1.15rem; border-bottom: 1px solid #F4F7FB; color: #334155; }
.opx-table tbody tr:hover { background: #F4F8FD; }
.opx-table-id { font-family: ui-monospace, Menlo, monospace; font-weight: 800; color: #0A2D6F; text-decoration: none; }
.opx-table-time { font-family: ui-monospace, Menlo, monospace; font-size: 0.76rem; color: #64748b; white-space: nowrap; }
.opx-badge { display: inline-block; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.05em; padding: 0.22rem 0.5rem; border-radius: 0.35rem; }
.opx-badge-green { background: #E8EEF8; color: #0A2D6F; }
.opx-badge-blue { background: #EAF1FC; color: #1E4FA8; }
.opx-badge-purple { background: #F3EEFF; color: #5B21B6; }
.opx-badge-amber { background: #FFF6E8; color: #9A6700; }
.opx-badge-gray { background: #F1F5F9; color: #475569; }

@media (max-width: 980px) {
    .opx-row-2-1, .opx-row-1-1, .opx-kpis { grid-template-columns: 1fr; }
    .opx-hero-stats { width: 100%; }
    .opx-hero-stats > div { flex: 1; }
}
@media (max-width: 640px) {
    .opx-hero-title { font-size: 1.35rem; }
    .opx-kpi-value { font-size: 1.35rem; }
}
</style>
@endsection
