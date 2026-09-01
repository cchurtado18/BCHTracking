<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingExpense;
use App\Models\AccountingInvoice;
use App\Models\AccountingOperatingCost;
use App\Models\Agency;
use App\Services\Accounting\ProfitabilityCalculator;
use App\Support\QueryDate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingProfitabilityController extends Controller
{
    private const MONTHS_ES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function index(Request $request, ProfitabilityCalculator $calculator)
    {
        [$preset, $from, $to, $prevFrom, $prevTo, $periodLabel, $compareLabel] = $this->resolvePeriod($request);
        $agencyId = $request->filled('agency_id') ? (int) $request->agency_id : null;

        $result = $calculator->calculate($from, $to, $agencyId);
        $previous = $calculator->calculate($prevFrom, $prevTo, $agencyId);

        $expensesTotal = $this->expensesBetween($from, $to, $agencyId);
        $prevExpensesTotal = $this->expensesBetween($prevFrom, $prevTo, $agencyId);

        $netResult = round($result->totals->margin - $expensesTotal, 2);
        $prevNetResult = round($previous->totals->margin - $prevExpensesTotal, 2);

        $kpis = [
            ['label' => 'Libras enviadas', 'type' => 'lb', 'current' => $result->totals->lbs, 'previous' => $previous->totals->lbs, 'icon' => 'scale'],
            ['label' => 'Ingreso bruto', 'type' => 'usd', 'current' => $result->totals->revenue, 'previous' => $previous->totals->revenue, 'icon' => 'dollar'],
            ['label' => 'Costo operativo', 'type' => 'usd', 'current' => $result->totals->cost, 'previous' => $previous->totals->cost, 'icon' => 'minus'],
            ['label' => 'Ganancia bruta', 'type' => 'usd', 'current' => $result->totals->margin, 'previous' => $previous->totals->margin, 'icon' => 'trend'],
            ['label' => 'Gastos extras', 'type' => 'usd', 'current' => $expensesTotal, 'previous' => $prevExpensesTotal, 'icon' => 'receipt'],
            ['label' => 'Resultado neto', 'type' => 'usd', 'current' => $netResult, 'previous' => $prevNetResult, 'icon' => 'wallet'],
        ];
        foreach ($kpis as &$kpi) {
            $kpi['delta'] = $kpi['previous'] != 0.0
                ? round((($kpi['current'] - $kpi['previous']) / abs($kpi['previous'])) * 100, 1)
                : null;
        }
        unset($kpi);

        // Rentabilidad agrupada por cliente (agencia) con desglose por servicio
        $clients = collect($result->rows)
            ->filter(fn ($r) => $r->agency !== null)
            ->groupBy(fn ($r) => $r->agency->id)
            ->map(function ($group) {
                $first = $group->first();
                $revenue = round($group->sum('revenue'), 2);
                $cost = round($group->sum('cost'), 2);
                $margin = round($revenue - $cost, 2);
                $lbs = round($group->sum('lbs'), 2);

                return (object) [
                    'agency' => $first->agency,
                    'packages' => (int) $group->sum('packages'),
                    'lbs' => $lbs,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'margin' => $margin,
                    'margin_pct' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : null,
                    'avg_rate' => $lbs > 0 ? round($revenue / $lbs, 2) : null,
                    'services' => $group->mapWithKeys(fn ($r) => [$r->service => round($r->lbs, 2)])->all(),
                    'missing_rate' => $group->contains(fn ($r) => $r->missing_rate),
                ];
            })
            ->sortByDesc('margin')
            ->values();

        $activeCosts = collect(AccountingOperatingCost::currentMap())
            ->filter()
            ->mapWithKeys(fn ($row, $service) => [$service => round((float) $row->cost_per_unit, 4)]);

        // Punto de equilibrio: ganancia bruta vs gastos del período
        $marginPerLb = $result->totals->lbs > 0 ? $result->totals->margin / $result->totals->lbs : 0;
        $breakeven = (object) [
            'reached' => $result->totals->margin >= $expensesTotal,
            'gap' => round(max(0, $expensesTotal - $result->totals->margin), 2),
            'lbsNeeded' => $marginPerLb > 0 ? (int) ceil(max(0, $expensesTotal - $result->totals->margin) / $marginPerLb) : null,
        ];

        // Proyección lineal al cierre del mes (solo cuando se ve el mes en curso)
        $projection = null;
        if ($preset === 'this_month') {
            $dayOfMonth = now()->day;
            $daysInMonth = now()->daysInMonth;
            $fraction = $dayOfMonth / $daysInMonth;
            if ($fraction > 0) {
                $projMargin = round($result->totals->margin / $fraction, 2);
                $projExpenses = round($expensesTotal / $fraction, 2);
                $projection = (object) [
                    'day' => $dayOfMonth,
                    'daysInMonth' => $daysInMonth,
                    'pct' => round($fraction * 100, 1),
                    'lbs' => round($result->totals->lbs / $fraction, 1),
                    'revenue' => round($result->totals->revenue / $fraction, 2),
                    'margin' => $projMargin,
                    'net' => round($projMargin - $projExpenses, 2),
                ];
            }
        }

        $agencies = Agency::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.profitability.index', [
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $periodLabel,
            'compareLabel' => $compareLabel,
            'agencyId' => $agencyId,
            'agencies' => $agencies,
            'kpis' => $kpis,
            'totals' => $result->totals,
            'expensesTotal' => $expensesTotal,
            'netResult' => $netResult,
            'clients' => $clients,
            'activeCosts' => $activeCosts,
            'breakeven' => $breakeven,
            'projection' => $projection,
            'withoutRateLbs' => $result->withoutRateLbs,
        ]);
    }

    public function show(Request $request, Agency $agency, ProfitabilityCalculator $calculator)
    {
        [$preset, $from, $to, , , $periodLabel] = $this->resolvePeriod($request);

        $rows = $calculator->detailRows($from, $to, $agency->id);

        $lbs = round($rows->sum('lbs'), 2);
        $revenue = round($rows->sum('revenue'), 2);
        $cost = round($rows->sum('cost'), 2);
        $margin = round($revenue - $cost, 2);
        $totals = (object) [
            'packages' => $rows->count(),
            'lbs' => $lbs,
            'revenue' => $revenue,
            'cost' => $cost,
            'margin' => $margin,
            'margin_pct' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : null,
            'avg_rate' => $lbs > 0 ? round($revenue / $lbs, 2) : null,
            'missing_rate' => $rows->contains(fn ($r) => $r->missing_rate),
        ];

        $noteIds = $rows->pluck('delivery_note_id')->filter()->unique()->values();
        $invoicesByNote = $noteIds->isEmpty()
            ? collect()
            : AccountingInvoice::query()
                ->whereIn('delivery_note_id', $noteIds)
                ->where('agency_id', $agency->id)
                ->where('status', '!=', 'void')
                ->get(['id', 'folio', 'delivery_note_id'])
                ->keyBy('delivery_note_id');

        // Histórico de los últimos 6 meses (incluye el mes en curso)
        $shortMonths = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
        $history = collect(range(5, 0))->map(function ($i) use ($calculator, $agency, $shortMonths) {
            $start = now()->subMonthsNoOverflow($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $t = $calculator->calculate($start, $end, $agency->id)->totals;

            return (object) [
                'label' => $shortMonths[$start->month].'. '.$start->format('y'),
                'lbs' => $t->lbs,
                'revenue' => $t->revenue,
                'cost' => $t->cost,
                'margin' => $t->margin,
                'margin_pct' => $t->revenue > 0 ? round(($t->margin / $t->revenue) * 100, 1) : 0,
            ];
        });

        return view('accounting.profitability.show', [
            'agency' => $agency,
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'totals' => $totals,
            'invoicesByNote' => $invoicesByNote,
            'history' => $history,
            'backQuery' => array_filter([
                'period' => $preset !== 'this_month' && $preset !== 'custom' ? $preset : null,
                'from' => $preset === 'custom' ? $from->toDateString() : null,
                'to' => $preset === 'custom' ? $to->toDateString() : null,
            ]),
        ]);
    }

    /**
     * @return array{0: string, 1: Carbon, 2: Carbon, 3: Carbon, 4: Carbon, 5: string, 6: string}
     */
    private function resolvePeriod(Request $request): array
    {
        $preset = (string) $request->input('period', 'this_month');
        if ($request->filled('from') || $request->filled('to')) {
            $preset = 'custom';
        }

        $now = now();
        $monthName = fn (Carbon $d) => self::MONTHS_ES[$d->month].' '.$d->year;

        switch ($preset) {
            case 'last_month':
                $from = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $to = $now->copy()->subMonthNoOverflow()->endOfMonth();
                $prevFrom = $now->copy()->subMonthsNoOverflow(2)->startOfMonth();
                $prevTo = $now->copy()->subMonthsNoOverflow(2)->endOfMonth();
                $label = 'Mes anterior ('.$monthName($from).')';
                $compare = mb_strtoupper($monthName($from).' vs '.$monthName($prevFrom));
                break;
            case 'last_30':
                $from = $now->copy()->subDays(29)->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subDays(59)->startOfDay();
                $prevTo = $now->copy()->subDays(30)->endOfDay();
                $label = 'Últimos 30 días';
                $compare = 'ÚLTIMOS 30 DÍAS VS 30 DÍAS PREVIOS';
                break;
            case 'quarter':
                $from = $now->copy()->firstOfQuarter()->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subQuarter()->firstOfQuarter()->startOfDay();
                $prevTo = $now->copy()->subQuarter()->lastOfQuarter()->endOfDay();
                $label = 'Trimestre en curso';
                $compare = 'TRIMESTRE ACTUAL VS TRIMESTRE ANTERIOR';
                break;
            case 'year':
                $from = $now->copy()->startOfYear();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subYear()->startOfYear();
                $prevTo = $now->copy()->subYear()->endOfYear();
                $label = 'Año '.$now->year;
                $compare = mb_strtoupper('Año '.$now->year.' vs Año '.($now->year - 1));
                break;
            case 'custom':
                $from = QueryDate::parse($request, 'from')?->startOfDay() ?? $now->copy()->startOfMonth();
                $to = QueryDate::parse($request, 'to')?->endOfDay() ?? $now->copy()->endOfDay();
                $days = max(1, (int) $from->diffInDays($to) + 1);
                $prevTo = $from->copy()->subDay()->endOfDay();
                $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();
                $label = 'Período '.$from->format('d/m/Y').' — '.$to->format('d/m/Y');
                $compare = 'PERÍODO VS PERÍODO ANTERIOR EQUIVALENTE';
                break;
            case 'this_month':
            default:
                $preset = 'this_month';
                $from = $now->copy()->startOfMonth();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $prevTo = $now->copy()->subMonthNoOverflow()->endOfMonth();
                $label = 'Mes actual ('.$monthName($now).')';
                $compare = mb_strtoupper($monthName($now).' vs '.$monthName($prevFrom));
                break;
        }

        return [$preset, $from, $to, $prevFrom, $prevTo, $label, $compare];
    }

    private function expensesBetween(Carbon $from, Carbon $to, ?int $agencyId): float
    {
        return round((float) AccountingExpense::query()
            ->whereDate('spent_at', '>=', $from->toDateString())
            ->whereDate('spent_at', '<=', $to->toDateString())
            ->whereNull('service_type')
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
            ->sum('amount_usd'), 2);
    }
}
