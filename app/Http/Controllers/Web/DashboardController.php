<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\Preregistration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()?->is_admin) {
            return redirect()->route('packages.index');
        }

        $displayTz = config('app.display_timezone') ?: 'America/New_York';
        $nowLocal = now($displayTz);
        $today = $nowLocal->toDateString();
        $yesterday = $nowLocal->copy()->subDay()->toDateString();

        // Leer parámetros GET (el formulario envía method="GET")
        $dateFromRaw = $request->input('date_from');
        $dateToRaw = $request->input('date_to');
        $agencyIdRaw = $request->input('agency_id');
        $serviceTypeRaw = $request->input('service_type');

        $dateFrom = $this->normalizeDate($dateFromRaw) ?? $today;
        $dateTo = $this->normalizeDate($dateToRaw) ?? $today;

        // Si solo enviaron una fecha, usar la misma para desde y hasta
        if ($this->normalizeDate($dateFromRaw) !== null && $this->normalizeDate($dateToRaw) === null) {
            $dateTo = $dateFrom;
        }
        if ($this->normalizeDate($dateToRaw) !== null && $this->normalizeDate($dateFromRaw) === null) {
            $dateFrom = $dateTo;
        }

        // Presets rápidos: Hoy / Semana / Mes (en zona operativa Miami)
        $activePeriod = null;
        $periodPreset = $request->input('period');
        if (in_array($periodPreset, ['today', 'week', 'month'], true)) {
            $activePeriod = $periodPreset;
            $dateTo = $today;
            $dateFrom = match ($periodPreset) {
                'today' => $today,
                'week' => $nowLocal->copy()->startOfWeek()->toDateString(),
                'month' => $nowLocal->copy()->startOfMonth()->toDateString(),
            };
        } elseif ($this->normalizeDate($dateFromRaw) === null && $this->normalizeDate($dateToRaw) === null) {
            $activePeriod = 'today';
        }

        // Asegurar que desde <= hasta
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $agencyId = $this->normalizeAgencyId($agencyIdRaw);
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            $agencyId = (int) auth()->user()->agency_id;
        }
        $serviceType = \App\Support\ServiceType::isValid($serviceTypeRaw) ? strtoupper((string) $serviceTypeRaw) : null;
        $isFiltered = $this->normalizeDate($dateFromRaw) !== null
            || $this->normalizeDate($dateToRaw) !== null
            || $agencyId !== null
            || $serviceType !== null;
        $periodLabel = match ($activePeriod) {
            'today' => 'Hoy',
            'week' => 'Esta semana',
            'month' => 'Este mes',
            default => "Del {$dateFrom} al {$dateTo}",
        };

        $agenciesForFilter = Agency::where('is_active', true)->orderBy('name')->get();
        $selectedAgency = $agencyId ? Agency::find($agencyId) : null;
        if ($selectedAgency) {
            $periodLabel .= ' · ' . $selectedAgency->name;
        }
        if ($serviceType === 'AIR') {
            $periodLabel .= ' · Aéreo';
        }
        if ($serviceType === 'SEA') {
            $periodLabel .= ' · Marítimo';
        }

        // Rango del periodo en UTC (días calendario de Miami)
        [$periodStartUtc, $periodEndUtc] = $this->localDateRangeToUtc($dateFrom, $dateTo, $displayTz);

        // Consulta base del periodo seleccionado
        $periodQuery = Preregistration::whereBetween('created_at', [$periodStartUtc, $periodEndUtc]);
        if ($agencyId) {
            $periodQuery->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $periodQuery->where('service_type', $serviceType);
        }

        // Paquetes en el periodo
        $packagesInPeriod = (clone $periodQuery)->count();

        // Bultos recibidos hoy vs. ayer (día operativo Miami)
        $dayCountQuery = function (string $day) use ($agencyId, $serviceType, $displayTz) {
            [$startUtc, $endUtc] = $this->localDateRangeToUtc($day, $day, $displayTz);
            $q = Preregistration::whereBetween('created_at', [$startUtc, $endUtc]);
            if ($agencyId) {
                $q->where('agency_id', $agencyId);
            }
            if ($serviceType) {
                $q->where('service_type', $serviceType);
            }

            return $q->count();
        };
        $packagesToday = $dayCountQuery($today);
        $packagesYesterday = $dayCountQuery($yesterday);
        $packagesDeltaPct = $packagesYesterday > 0
            ? round((($packagesToday - $packagesYesterday) / $packagesYesterday) * 100, 1)
            : null;

        // Lbs totales históricos (sin filtro de fecha; si es usuario de agencia, solo de su agencia)
        $lbsBaseQuery = Preregistration::query();
        if ($agencyId) {
            $lbsBaseQuery->where('agency_id', $agencyId);
        }
        $lbsAir = (float) (clone $lbsBaseQuery)->where('service_type', 'AIR')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');
        $lbsSea = (float) (clone $lbsBaseQuery)->where('service_type', 'SEA')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');

        // Lbs en el periodo por servicio (desglose sin reaplicar filtro de servicio)
        $periodLbsBase = Preregistration::whereBetween('created_at', [$periodStartUtc, $periodEndUtc]);
        if ($agencyId) {
            $periodLbsBase->where('agency_id', $agencyId);
        }
        $lbsAirPeriod = (float) (clone $periodLbsBase)->where('service_type', 'AIR')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');
        $lbsSeaPeriod = (float) (clone $periodLbsBase)->where('service_type', 'SEA')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');
        // Total del periodo sí respeta el filtro de servicio si está activo
        $totalLbsPeriod = $serviceType === 'AIR'
            ? $lbsAirPeriod
            : ($serviceType === 'SEA' ? $lbsSeaPeriod : ($lbsAirPeriod + $lbsSeaPeriod));
        $airSharePct = ($lbsAirPeriod + $lbsSeaPeriod) > 0 ? round(($lbsAirPeriod / ($lbsAirPeriod + $lbsSeaPeriod)) * 100, 1) : 0;
        $seaSharePct = ($lbsAirPeriod + $lbsSeaPeriod) > 0 ? round(($lbsSeaPeriod / ($lbsAirPeriod + $lbsSeaPeriod)) * 100, 1) : 0;

        // Agencias que más mueven en el periodo
        $agenciesByPeriod = Preregistration::query()
            ->whereBetween('created_at', [$periodStartUtc, $periodEndUtc])
            ->whereNotNull('agency_id');
        if ($agencyId) {
            $agenciesByPeriod->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $agenciesByPeriod->where('service_type', $serviceType);
        }
        $agenciesByPeriod = $agenciesByPeriod
            ->select('agency_id')
            ->selectRaw('COUNT(*) as packages_count')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total_lbs')
            ->groupBy('agency_id')
            ->orderByDesc('packages_count')
            ->get();

        $agencyIds = $agenciesByPeriod->pluck('agency_id')->filter()->unique()->values()->all();
        $agencies = Agency::whereIn('id', $agencyIds)->get()->keyBy('id');

        $agenciesRanking = $agenciesByPeriod->map(function ($row) use ($agencies) {
            return [
                'agency' => $agencies->get($row->agency_id),
                'packages_count' => (int) $row->packages_count,
                'total_lbs' => (float) $row->total_lbs,
            ];
        })->filter(fn ($row) => $row['agency'] !== null)->values();

        // Métricas del periodo (donut y alertas de listos)
        $preregistrationsCount = $packagesInPeriod;
        $preregistrationsReceived = (clone $periodQuery)->where('status', 'RECEIVED_MIAMI')->count();
        $preregistrationsInTransit = (clone $periodQuery)->where('status', 'IN_TRANSIT')->count();
        $preregistrationsReady = (clone $periodQuery)->where('status', 'READY')->count();
        $preregistrationsNic = (clone $periodQuery)->where('status', 'IN_WAREHOUSE_NIC')->count();
        $preregistrationsDelivered = (clone $periodQuery)->where('status', 'DELIVERED')->count();

        // Distribución de estados del periodo (donut)
        $statusDistribution = [
            ['key' => 'RECEIVED_MIAMI', 'label' => 'Recibido Miami', 'count' => $preregistrationsReceived, 'color' => '#16a34a'],
            ['key' => 'IN_TRANSIT', 'label' => 'En tránsito', 'count' => $preregistrationsInTransit, 'color' => '#3b82f6'],
            ['key' => 'IN_WAREHOUSE_NIC', 'label' => 'Almacén NIC', 'count' => $preregistrationsNic, 'color' => '#8b5cf6'],
            ['key' => 'READY', 'label' => 'Listo retiro', 'count' => $preregistrationsReady, 'color' => '#f59e0b'],
            ['key' => 'DELIVERED', 'label' => 'Entregado', 'count' => $preregistrationsDelivered, 'color' => '#94a3b8'],
        ];

        // Volumen de carga de los últimos 7 días (calendario Miami) por servicio
        $chartStartLocal = $nowLocal->copy()->subDays(6)->startOfDay();
        [$chartStartUtc, $chartEndUtc] = $this->localDateRangeToUtc($chartStartLocal->toDateString(), $today, $displayTz);
        $weeklyRawQuery = Preregistration::query()
            ->whereBetween('created_at', [$chartStartUtc, $chartEndUtc]);
        if ($agencyId) {
            $weeklyRawQuery->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $weeklyRawQuery->where('service_type', $serviceType);
        }
        $weeklyRows = $weeklyRawQuery
            ->get(['created_at', 'service_type', 'verified_weight_lbs', 'intake_weight_lbs']);

        $weeklyByDay = [];
        foreach ($weeklyRows as $row) {
            $dayKey = $row->created_at->copy()->timezone($displayTz)->toDateString();
            $svc = in_array((string) $row->service_type, ['SEA', 'CFT'], true) ? 'sea' : 'air';
            $lbs = (float) ($row->verified_weight_lbs ?? $row->intake_weight_lbs ?? 0);
            $weeklyByDay[$dayKey][$svc] = ($weeklyByDay[$dayKey][$svc] ?? 0) + $lbs;
        }

        $dayNames = ['LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB', 'DOM'];
        $weeklyVolume = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $nowLocal->copy()->subDays($i);
            $key = $date->toDateString();
            $weeklyVolume[] = [
                'label' => $dayNames[$date->dayOfWeekIso - 1],
                'date_label' => $date->locale('es')->isoFormat('dddd D MMM'),
                'air' => (float) ($weeklyByDay[$key]['air'] ?? 0),
                'sea' => (float) ($weeklyByDay[$key]['sea'] ?? 0),
            ];
        }
        $weeklyMax = max(1, max(array_map(fn ($d) => max($d['air'], $d['sea']), $weeklyVolume)));

        // Actividad de recepción: heatmap de las últimas 4 semanas (calendario Miami)
        $heatStartLocal = $nowLocal->copy()->startOfWeek()->subWeeks(3)->startOfDay();
        [$heatStartUtc] = $this->localDateRangeToUtc($heatStartLocal->toDateString(), $today, $displayTz);
        $heatRawQuery = Preregistration::query()
            ->where('created_at', '>=', $heatStartUtc);
        if ($agencyId) {
            $heatRawQuery->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $heatRawQuery->where('service_type', $serviceType);
        }
        $heatCounts = [];
        foreach ($heatRawQuery->get(['created_at']) as $row) {
            $dayKey = $row->created_at->copy()->timezone($displayTz)->toDateString();
            $heatCounts[$dayKey] = ($heatCounts[$dayKey] ?? 0) + 1;
        }
        $heatmapMax = max(1, empty($heatCounts) ? 0 : max($heatCounts));

        $heatmapWeeks = [];
        for ($w = 0; $w < 4; $w++) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $date = $heatStartLocal->copy()->addWeeks($w)->addDays($d);
                $key = $date->toDateString();
                $count = $date->isFuture() ? null : (int) ($heatCounts[$key] ?? 0);
                $week[] = [
                    'date' => $date->format('d/m'),
                    'date_label' => $date->locale('es')->isoFormat('dddd D MMM'),
                    'count' => $count,
                    'level' => $count === null || $count === 0 ? 0 : (int) ceil(($count / $heatmapMax) * 4),
                ];
            }
            $heatmapWeeks[] = $week;
        }

        // Registros operativos recientes
        $recentQuery = Preregistration::with('agency')->orderByDesc('updated_at')->limit(8);
        if ($agencyId) {
            $recentQuery->where('agency_id', $agencyId);
        }
        $recentRecords = $recentQuery->get();

        $isAgencyUser = auth()->user() && auth()->user()->isAgencyUser();
        $consolidationsCount = $isAgencyUser ? 0 : Consolidation::count();
        $consolidationsOpen = $isAgencyUser ? 0 : Consolidation::where('status', 'OPEN')->count();
        $consolidationsSent = $isAgencyUser ? 0 : Consolidation::where('status', 'SENT')->count();

        // Alertas: requiere atención (estado actual, no solo del periodo)
        $alerts = [];
        $miamiOldQuery = Preregistration::where('status', 'RECEIVED_MIAMI')->where('created_at', '<', now()->subHours(36));
        if ($agencyId) {
            $miamiOldQuery->where('agency_id', $agencyId);
        }
        $miamiOld = $miamiOldQuery->count();
        if ($miamiOld > 0) {
            $alerts[] = [
                'title' => 'Paquetes en Miami más de 36 horas sin cambiar a estado de tránsito',
                'count' => $miamiOld,
                'url' => $isAgencyUser ? route('packages.index', ['status' => 'RECEIVED_MIAMI']) : route('preregistrations.index', ['status' => 'RECEIVED_MIAMI']),
            ];
        }
        if (! $isAgencyUser) {
            $sacosOpenOld = Consolidation::where('status', 'OPEN')->where('created_at', '<', now()->subDays(7))->count();
            if ($sacosOpenOld > 0) {
                $alerts[] = [
                    'title' => 'Consolidaciones abiertas hace más de 7 días',
                    'count' => $sacosOpenOld,
                    'url' => route('consolidations.index', ['status' => 'OPEN']),
                ];
            }
        }
        // Listos para retiro: estado actual (operativo), no limitado al periodo del dashboard
        $readyNowQuery = Preregistration::where('status', 'READY');
        if ($agencyId) {
            $readyNowQuery->where('agency_id', $agencyId);
        }
        $readyNow = $readyNowQuery->count();
        if ($readyNow > 0) {
            $alerts[] = [
                'title' => 'Paquetes listos para retiro (pendientes de entrega)',
                'count' => $readyNow,
                'url' => route('packages.index', ['status' => 'READY']),
            ];
        }

        $pipelineBase = Preregistration::query();
        if ($agencyId) {
            $pipelineBase->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $pipelineBase->where('service_type', $serviceType);
        }
        $packagesIndex = $isAgencyUser ? 'packages.index' : 'preregistrations.index';
        $pipeline = [
            [
                'key' => 'RECEIVED_MIAMI',
                'step' => '01',
                'label' => 'Miami',
                'hint' => 'Ingreso al almacén',
                'count' => (clone $pipelineBase)->where('status', 'RECEIVED_MIAMI')->count(),
                'url' => route($packagesIndex, ['status' => 'RECEIVED_MIAMI']),
            ],
            [
                'key' => 'IN_TRANSIT',
                'step' => '02',
                'label' => 'Tránsito',
                'hint' => 'En ruta a Nicaragua',
                'count' => (clone $pipelineBase)->where('status', 'IN_TRANSIT')->count(),
                'url' => route($packagesIndex, ['status' => 'IN_TRANSIT']),
            ],
            [
                'key' => 'IN_WAREHOUSE_NIC',
                'step' => '03',
                'label' => 'Almacén NIC',
                'hint' => 'Llegó a destino',
                'count' => (clone $pipelineBase)->where('status', 'IN_WAREHOUSE_NIC')->count(),
                'url' => route($packagesIndex, ['status' => 'IN_WAREHOUSE_NIC']),
            ],
            [
                'key' => 'READY',
                'step' => '04',
                'label' => 'Listo',
                'hint' => 'Pendiente de retiro',
                'count' => (clone $pipelineBase)->where('status', 'READY')->count(),
                'url' => route($packagesIndex, ['status' => 'READY']),
            ],
            [
                'key' => 'DELIVERED',
                'step' => '05',
                'label' => 'Entregado',
                'hint' => 'Cerrado en ventanilla',
                'count' => (clone $pipelineBase)->where('status', 'DELIVERED')->count(),
                'url' => route($packagesIndex, ['status' => 'DELIVERED']),
            ],
        ];

        return view('dashboard', compact(
            'dateFrom',
            'dateTo',
            'agencyId',
            'serviceType',
            'agenciesForFilter',
            'selectedAgency',
            'isFiltered',
            'periodLabel',
            'packagesInPeriod',
            'lbsAir',
            'lbsSea',
            'lbsAirPeriod',
            'lbsSeaPeriod',
            'agenciesRanking',
            'preregistrationsCount',
            'preregistrationsReceived',
            'preregistrationsInTransit',
            'consolidationsCount',
            'consolidationsOpen',
            'consolidationsSent',
            'preregistrationsReady',
            'preregistrationsNic',
            'preregistrationsDelivered',
            'alerts',
            'activePeriod',
            'packagesToday',
            'packagesYesterday',
            'packagesDeltaPct',
            'totalLbsPeriod',
            'airSharePct',
            'seaSharePct',
            'statusDistribution',
            'weeklyVolume',
            'weeklyMax',
            'heatmapWeeks',
            'heatmapMax',
            'recentRecords',
            'displayTz',
            'pipeline',
            'isAgencyUser'
        ));
    }

    /**
     * Vista para solicitar el reporte: formulario con filtros (agencia, rango de fechas, servicio).
     * Al enviar redirige a reporte.paquetes con los parámetros (o abre en nueva pestaña).
     */
    public function reporteSolicitar(Request $request)
    {
        if (auth()->user() && ! auth()->user()->is_admin && ! auth()->user()->isAgencyUser()) {
            return redirect()->route('packages.index');
        }

        $now = now();
        $firstOfMonth = $now->copy()->startOfMonth()->format('Y-m-d');
        $lastOfMonth = $now->copy()->endOfMonth()->format('Y-m-d');

        $agencies = Agency::where('is_active', true)->orderBy('name')->get();
        $isAgencyUser = auth()->user() && auth()->user()->isAgencyUser();
        $currentAgency = null;
        if ($isAgencyUser && auth()->user()->agency_id) {
            $currentAgency = Agency::find(auth()->user()->agency_id);
        }

        return view('reporte-solicitar', [
            'agencies' => $agencies,
            'isAgencyUser' => $isAgencyUser,
            'currentAgency' => $currentAgency,
            'defaultDateFrom' => $firstOfMonth,
            'defaultDateTo' => $lastOfMonth,
        ]);
    }

    /**
     * Reporte de paquetes: solo tabla con detalle (sin dashboard, sin foto).
     * Acepta los mismos filtros: date_from, date_to, agency_id, service_type.
     */
    public function reportePaquetes(Request $request)
    {
        if (auth()->user() && ! auth()->user()->is_admin && ! auth()->user()->isAgencyUser()) {
            return redirect()->route('packages.index');
        }
        $dateFromRaw = $request->input('date_from');
        $dateToRaw = $request->input('date_to');
        $agencyIdRaw = $request->input('agency_id');
        $serviceTypeRaw = $request->input('service_type');

        $today = now()->toDateString();
        $hasDateFilter = $this->normalizeDate($dateFromRaw) !== null || $this->normalizeDate($dateToRaw) !== null;
        $dateFrom = null;
        $dateTo = null;

        if ($hasDateFilter) {
            $dateFrom = $this->normalizeDate($dateFromRaw) ?? $today;
            $dateTo = $this->normalizeDate($dateToRaw) ?? $today;
            if ($this->normalizeDate($dateFromRaw) !== null && $this->normalizeDate($dateToRaw) === null) {
                $dateTo = $dateFrom;
            }
            if ($this->normalizeDate($dateToRaw) !== null && $this->normalizeDate($dateFromRaw) === null) {
                $dateFrom = $dateTo;
            }
            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        }

        $agencyId = $this->normalizeAgencyId($agencyIdRaw);
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            $agencyId = (int) auth()->user()->agency_id;
        }
        $serviceType = \App\Support\ServiceType::isValid($serviceTypeRaw) ? strtoupper((string) $serviceTypeRaw) : null;

        $query = Preregistration::query();
        if ($dateFrom !== null && $dateTo !== null) {
            $query->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo);
        }
        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        $paquetes = $query->with('agency')
            ->orderBy('created_at', 'desc')
            ->get();

        $periodLabel = $hasDateFilter ? "Del {$dateFrom} al {$dateTo}" : 'Todos los periodos';
        $selectedAgency = $agencyId ? Agency::find($agencyId) : null;
        if ($selectedAgency) {
            $periodLabel .= ' · ' . $selectedAgency->name;
        }
        if ($serviceType === 'AIR') {
            $periodLabel .= ' · Aéreo';
        }
        if ($serviceType === 'SEA') {
            $periodLabel .= ' · Marítimo';
        }

        return view('reporte-paquetes', compact('paquetes', 'periodLabel'));
    }

    /**
     * Convierte un rango de fechas calendario (Y-m-d) en zona operativa a UTC [start, end].
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function localDateRangeToUtc(string $dateFrom, string $dateTo, string $displayTz): array
    {
        $start = \Carbon\Carbon::parse($dateFrom, $displayTz)->startOfDay()->utc();
        $end = \Carbon\Carbon::parse($dateTo, $displayTz)->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Devuelve la fecha en formato Y-m-d si el valor es válido, o null.
     */
    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * Devuelve el ID de agencia como int o null si no viene o es vacío.
     */
    private function normalizeAgencyId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
}
