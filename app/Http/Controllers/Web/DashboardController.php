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
        if (auth()->user() && ! auth()->user()->is_admin && ! auth()->user()->isAgencyUser()) {
            return redirect()->route('packages.index');
        }
        // Leer parámetros GET (el formulario envía method="GET")
        $dateFromRaw = $request->input('date_from');
        $dateToRaw = $request->input('date_to');
        $agencyIdRaw = $request->input('agency_id');
        $serviceTypeRaw = $request->input('service_type');

        $today = now()->toDateString();
        $dateFrom = $this->normalizeDate($dateFromRaw) ?? $today;
        $dateTo = $this->normalizeDate($dateToRaw) ?? $today;

        // Si solo enviaron una fecha, usar la misma para desde y hasta
        if ($this->normalizeDate($dateFromRaw) !== null && $this->normalizeDate($dateToRaw) === null) {
            $dateTo = $dateFrom;
        }
        if ($this->normalizeDate($dateToRaw) !== null && $this->normalizeDate($dateFromRaw) === null) {
            $dateFrom = $dateTo;
        }

        // Asegurar que desde <= hasta
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $agencyId = $this->normalizeAgencyId($agencyIdRaw);
        if (auth()->user() && auth()->user()->isAgencyUser()) {
            $agencyId = (int) auth()->user()->agency_id;
        }
        $serviceType = in_array($serviceTypeRaw, ['AIR', 'SEA'], true) ? $serviceTypeRaw : null;
        $isFiltered = $this->normalizeDate($dateFromRaw) !== null
            || $this->normalizeDate($dateToRaw) !== null
            || $agencyId !== null
            || $serviceType !== null;
        $periodLabel = $isFiltered ? "Del {$dateFrom} al {$dateTo}" : 'Hoy';

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

        // Un solo día para las tarjetas de periodo (Paquetes, Lbs Aéreo, Lbs Marítimo): al pasar al siguiente día inicia desde 0
        $periodDay = $dateFrom;
        $periodQuerySingleDay = Preregistration::whereDate('created_at', $periodDay);
        if ($agencyId) {
            $periodQuerySingleDay->where('agency_id', $agencyId);
        }
        if ($serviceType) {
            $periodQuerySingleDay->where('service_type', $serviceType);
        }

        // Paquetes en el día (periodo = un solo día)
        $packagesInPeriod = (clone $periodQuerySingleDay)->count();

        // Lbs totales (sin filtro de fecha; si es usuario de agencia, solo de su agencia)
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

        // Lbs en el día por servicio (periodo = un solo día)
        $lbsAirPeriod = (float) (clone $periodQuerySingleDay)->where('service_type', 'AIR')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');
        $lbsSeaPeriod = (float) (clone $periodQuerySingleDay)->where('service_type', 'SEA')
            ->selectRaw('COALESCE(SUM(COALESCE(verified_weight_lbs, intake_weight_lbs)), 0) as total')
            ->value('total');

        // Agencias que más mueven en el día (mismo día para consistencia)
        $agenciesByPeriod = Preregistration::query()
            ->whereDate('created_at', $periodDay)
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

        // Métricas generales (si es usuario de agencia, filtradas por su agencia)
        $metricsBase = Preregistration::query();
        if ($agencyId) {
            $metricsBase->where('agency_id', $agencyId);
        }
        $preregistrationsCount = (clone $metricsBase)->count();
        $preregistrationsReceived = (clone $metricsBase)->where('status', 'RECEIVED_MIAMI')->count();
        $preregistrationsInTransit = (clone $metricsBase)->where('status', 'IN_TRANSIT')->count();
        $preregistrationsReady = (clone $metricsBase)->where('status', 'READY')->count();

        $isAgencyUser = auth()->user() && auth()->user()->isAgencyUser();
        $consolidationsCount = $isAgencyUser ? 0 : Consolidation::count();
        $consolidationsOpen = $isAgencyUser ? 0 : Consolidation::where('status', 'OPEN')->count();
        $consolidationsSent = $isAgencyUser ? 0 : Consolidation::where('status', 'SENT')->count();

        // Alertas: requiere atención (si es agencia, solo alertas de sus paquetes y URLs a packages)
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
                    'title' => 'Sacos abiertos hace más de 7 días',
                    'count' => $sacosOpenOld,
                    'url' => route('consolidations.index', ['status' => 'OPEN']),
                ];
            }
        }
        if ($preregistrationsReady > 0) {
            $alerts[] = [
                'title' => 'Paquetes listos para retiro (pendientes de entrega)',
                'count' => $preregistrationsReady,
                'url' => route('packages.index', ['status' => 'READY']),
            ];
        }

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
            'alerts'
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
        $serviceType = in_array($serviceTypeRaw, ['AIR', 'SEA'], true) ? $serviceTypeRaw : null;

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
