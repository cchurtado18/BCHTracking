<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AccountingExpense;
use App\Models\AccountingInvoice;
use App\Models\AccountingPayment;
use App\Services\Accounting\ProfitabilityCalculator;
use App\Support\QueryDate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingReportController extends Controller
{
    private const MONTHS_ES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private const MONTHS_ES_SHORT = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    public function index(Request $request, ProfitabilityCalculator $calculator)
    {
        [$from, $to, $rangeKey, $periodLabel] = $this->resolvePeriod($request);

        // ——— Comparativo fijo: mes actual vs mes anterior (independiente del filtro) ———
        $curFrom = now()->copy()->startOfMonth();
        $curTo = now()->copy()->endOfDay();
        $prevFrom = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $prevTo = now()->copy()->subMonthNoOverflow()->endOfMonth();

        $currentMonth = $this->monthMetrics($curFrom, $curTo);
        $previousMonth = $this->monthMetrics($prevFrom, $prevTo);
        $comparisonLabel = strtoupper(self::MONTHS_ES[$curFrom->month].' '.$curFrom->year.' vs '.self::MONTHS_ES[$prevFrom->month].' '.$prevFrom->year);

        // ——— Serie últimos 6 meses (facturado vs cobrado) ———
        $monthlySeries = collect(range(5, 0))->map(function (int $i) {
            $start = now()->copy()->subMonthsNoOverflow($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $m = $this->monthMetrics($start, $end);

            return (object) [
                'label' => self::MONTHS_ES_SHORT[$start->month].'. '.$start->format('y'),
                'invoiced' => $m['invoiced'],
                'collected' => $m['collected'],
            ];
        })->values();

        // ——— Datos del período filtrado ———
        $operations = $calculator->calculate($from, $to);

        $invoiced = AccountingInvoice::query()
            ->where('status', '!=', 'void')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('COUNT(*) as count_invoices, COALESCE(SUM(total_usd),0) as total_usd, COALESCE(SUM(total_lbs),0) as total_lbs')
            ->first();

        $collected = (float) AccountingPayment::query()
            ->where('status', 'active')
            ->whereDate('paid_at', '>=', $from->toDateString())
            ->whereDate('paid_at', '<=', $to->toDateString())
            ->sum('amount_usd');

        // Cobros del período por método de pago (ranking + distribución)
        $paymentsByMethod = AccountingPayment::query()
            ->where('status', 'active')
            ->whereDate('paid_at', '>=', $from->toDateString())
            ->whereDate('paid_at', '<=', $to->toDateString())
            ->selectRaw('method, COUNT(*) as movs, COALESCE(SUM(amount_usd),0) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => (object) [
                'method' => $row->method,
                'label' => AccountingPayment::METHODS[$row->method] ?? (string) $row->method,
                'movs' => (int) $row->movs,
                'total' => round((float) $row->total, 2),
            ]);
        $paymentsTotal = round((float) $paymentsByMethod->sum('total'), 2);

        // Top 10 clientes facturados del período
        $topClients = AccountingInvoice::query()
            ->with('agency:id,code,name,account_type,is_main')
            ->where('status', '!=', 'void')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('agency_id, COUNT(*) as invoices, COALESCE(SUM(total_usd),0) as invoiced_usd, COALESCE(SUM(amount_paid),0) as paid_usd')
            ->groupBy('agency_id')
            ->orderByDesc('invoiced_usd')
            ->limit(10)
            ->get()
            ->map(fn ($row) => (object) [
                'agency' => $row->agency,
                'invoices' => (int) $row->invoices,
                'invoiced' => round((float) $row->invoiced_usd, 2),
                'paid' => round((float) $row->paid_usd, 2),
                'balance' => max(0, round((float) $row->invoiced_usd - (float) $row->paid_usd, 2)),
            ]);
        $topClientsInvoicedTotal = round((float) $topClients->sum('invoiced'), 2);

        // Gastos extras (sin flete de vía: ese flete ya entra al costo/unidad en Parámetros)
        $expensesByCategory = AccountingExpense::query()
            ->with('category:id,name')
            ->whereDate('spent_at', '>=', $from->toDateString())
            ->whereDate('spent_at', '<=', $to->toDateString())
            ->whereNull('service_type')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($items) => (object) [
                'category' => $items->first()->category,
                'total' => round((float) $items->sum('amount_usd'), 2),
            ])
            ->sortByDesc('total')
            ->values();
        $totalExpenses = round((float) $expensesByCategory->sum('total'), 2);

        // CxC: saldo pendiente actual (foto al día de hoy, no del período)
        $openInvoices = AccountingInvoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get(['id', 'total_usd', 'amount_paid']);
        $receivables = round($openInvoices->sum(fn ($i) => $i->balanceUsd()), 2);

        // Estado de resultados del período
        $invoicedUsd = round((float) $invoiced->total_usd, 2);
        $estimatedCost = $operations->totals->cost;
        $netResult = round($invoicedUsd - $estimatedCost - $totalExpenses, 2);

        return view('accounting.reports.index', [
            'from' => $from,
            'to' => $to,
            'rangeKey' => $rangeKey,
            'periodLabel' => $periodLabel,
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'comparisonLabel' => $comparisonLabel,
            'monthlySeries' => $monthlySeries,
            'paymentsByMethod' => $paymentsByMethod,
            'paymentsTotal' => $paymentsTotal,
            'topClients' => $topClients,
            'topClientsInvoicedTotal' => $topClientsInvoicedTotal,
            'operations' => $operations,
            'invoicedCount' => (int) $invoiced->count_invoices,
            'invoicedUsd' => $invoicedUsd,
            'invoicedLbs' => round((float) $invoiced->total_lbs, 2),
            'collected' => round($collected, 2),
            'expensesByCategory' => $expensesByCategory,
            'totalExpenses' => $totalExpenses,
            'receivables' => $receivables,
            'estimatedCost' => $estimatedCost,
            'netResult' => $netResult,
        ]);
    }

    /**
     * Resuelve el rango del período: atajos (?range=) o fechas manuales (?from&to).
     *
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    private function resolvePeriod(Request $request): array
    {
        $range = (string) $request->query('range', '');

        if ($range === 'last_month') {
            $from = now()->copy()->subMonthNoOverflow()->startOfMonth();
            $to = now()->copy()->subMonthNoOverflow()->endOfMonth();

            return [$from, $to, 'last_month', 'Mes anterior ('.self::MONTHS_ES[$from->month].' '.$from->year.')'];
        }

        if ($range === 'last_30') {
            return [now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay(), 'last_30', 'Últimos 30 días'];
        }

        if ($range === 'quarter') {
            return [now()->copy()->startOfQuarter(), now()->copy()->endOfDay(), 'quarter', 'Trimestre actual'];
        }

        if ($range === 'year') {
            return [now()->copy()->startOfYear(), now()->copy()->endOfDay(), 'year', 'Año actual ('.now()->year.')'];
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = QueryDate::parse($request, 'from')?->startOfDay() ?? now()->startOfMonth();
            $to = QueryDate::parse($request, 'to')?->endOfDay() ?? now()->endOfDay();

            return [$from, $to, 'custom', 'Rango personalizado ('.$from->format('d/m/Y').' — '.$to->format('d/m/Y').')'];
        }

        $from = now()->copy()->startOfMonth();

        return [$from, now()->copy()->endOfDay(), 'this_month', 'Mes actual ('.self::MONTHS_ES[$from->month].' '.$from->year.')'];
    }

    /**
     * KPIs de un mes: facturado, cobrado, saldo CxC de facturas emitidas en el mes y ticket promedio.
     *
     * @return array{invoiced: float, collected: float, receivables: float, avg_ticket: float, invoices: int}
     */
    private function monthMetrics(Carbon $from, Carbon $to): array
    {
        $invoiceRow = AccountingInvoice::query()
            ->where('status', '!=', 'void')
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('COUNT(*) as count_invoices, COALESCE(SUM(total_usd),0) as total_usd, COALESCE(SUM(total_usd - amount_paid),0) as balance_usd')
            ->first();

        $collected = (float) AccountingPayment::query()
            ->where('status', 'active')
            ->whereDate('paid_at', '>=', $from->toDateString())
            ->whereDate('paid_at', '<=', $to->toDateString())
            ->sum('amount_usd');

        $count = (int) $invoiceRow->count_invoices;
        $invoiced = round((float) $invoiceRow->total_usd, 2);

        return [
            'invoiced' => $invoiced,
            'collected' => round($collected, 2),
            'receivables' => max(0, round((float) $invoiceRow->balance_usd, 2)),
            'avg_ticket' => $count > 0 ? round($invoiced / $count, 2) : 0.0,
            'invoices' => $count,
        ];
    }
}
