<?php

namespace App\Services\Accounting;

use App\Models\AccountingOperatingCost;
use App\Models\AccountingRateCard;
use App\Models\Delivery;
use App\Support\ServiceType;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProfitabilityCalculator
{
    /**
     * Rentabilidad de las salidas entregadas en el período:
     * ingreso = lbs/pie³ × precio del cliente; costo = lbs/pie³ × costo de operación vigente.
     *
     * @return object{rows: \Illuminate\Support\Collection, totals: object, withoutRateLbs: float}
     */
    public function calculate(CarbonInterface $from, CarbonInterface $to, ?int $agencyId = null): object
    {
        $deliveries = Delivery::query()
            ->with(['preregistration:id,agency_id,service_type,intake_weight_lbs,verified_weight_lbs,dimension', 'preregistration.agency:id,code,name'])
            ->whereBetween('delivered_at', [$from, $to])
            ->when($agencyId, fn ($q) => $q->whereHas('preregistration', fn ($p) => $p->where('agency_id', $agencyId)))
            ->get();

        $rateCards = AccountingRateCard::query()
            ->orderBy('effective_from')
            ->get()
            ->groupBy(fn ($r) => $r->agency_id.'|'.$r->service_type);

        $operatingCosts = AccountingOperatingCost::query()
            ->orderBy('effective_from')
            ->get()
            ->groupBy('service_type');

        $groups = [];
        $withoutRateLbs = 0.0;

        foreach ($deliveries as $delivery) {
            $p = $delivery->preregistration;
            if (! $p || ! $p->agency_id) {
                continue;
            }
            $service = ServiceType::normalize($p->service_type);
            $qty = ServiceType::billedQuantity($p);
            $day = $delivery->delivered_at;
            $rate = $this->resolveRate($rateCards, (int) $p->agency_id, $service, $day);
            $opCost = $this->resolveOperatingCost($operatingCosts, $service, $day);

            $key = $p->agency_id.'|'.$service;
            if (! isset($groups[$key])) {
                $groups[$key] = (object) [
                    'agency' => $p->agency,
                    'service' => $service,
                    'packages' => 0,
                    'lbs' => 0.0,
                    'revenue' => 0.0,
                    'cost' => 0.0,
                    'missing_rate' => false,
                ];
            }

            $groups[$key]->packages++;
            $groups[$key]->lbs += $qty;

            if ($rate) {
                $groups[$key]->revenue += $qty * (float) $rate->price_per_lb;
                $groups[$key]->cost += $qty * (float) ($opCost?->cost_per_unit ?? 0);
            } else {
                $groups[$key]->missing_rate = true;
                $withoutRateLbs += $qty;
            }
        }

        $rows = collect($groups)
            ->map(function ($g) {
                $g->lbs = round($g->lbs, 2);
                $g->revenue = round($g->revenue, 2);
                $g->cost = round($g->cost, 2);
                $g->margin = round($g->revenue - $g->cost, 2);

                return $g;
            })
            ->sortByDesc('revenue')
            ->values();

        $totals = (object) [
            'packages' => $rows->sum('packages'),
            'lbs' => round($rows->sum('lbs'), 2),
            'revenue' => round($rows->sum('revenue'), 2),
            'cost' => round($rows->sum('cost'), 2),
            'margin' => round($rows->sum('revenue') - $rows->sum('cost'), 2),
        ];

        return (object) [
            'rows' => $rows,
            'totals' => $totals,
            'withoutRateLbs' => round($withoutRateLbs, 2),
        ];
    }

    /**
     * Detalle por paquete (entrega) de una agencia en el período.
     */
    public function detailRows(CarbonInterface $from, CarbonInterface $to, int $agencyId): Collection
    {
        $deliveries = Delivery::query()
            ->with(['preregistration:id,agency_id,service_type,intake_weight_lbs,verified_weight_lbs,dimension,tracking_external,warehouse_code'])
            ->whereBetween('delivered_at', [$from, $to])
            ->whereHas('preregistration', fn ($p) => $p->where('agency_id', $agencyId))
            ->orderByDesc('delivered_at')
            ->get();

        $rateCards = AccountingRateCard::query()
            ->where('agency_id', $agencyId)
            ->orderBy('effective_from')
            ->get()
            ->groupBy('service_type');

        $operatingCosts = AccountingOperatingCost::query()
            ->orderBy('effective_from')
            ->get()
            ->groupBy('service_type');

        return $deliveries->map(function ($delivery) use ($rateCards, $operatingCosts) {
            $p = $delivery->preregistration;
            if (! $p) {
                return null;
            }
            $service = ServiceType::normalize($p->service_type);
            $lbs = ServiceType::billedQuantity($p);
            $day = $delivery->delivered_at;

            $rate = ($rateCards->get($service) ?? collect())
                ->filter(fn ($r) => $r->effective_from->toDateString() <= ($day?->toDateString() ?? now()->toDateString())
                    && ($r->effective_to === null || $r->effective_to->toDateString() >= ($day?->toDateString() ?? now()->toDateString())))
                ->sortByDesc('effective_from')
                ->first();

            $opCost = $this->resolveOperatingCost($operatingCosts, $service, $day);
            $unitCost = $opCost ? (float) $opCost->cost_per_unit : null;

            $revenue = $rate ? round($lbs * (float) $rate->price_per_lb, 2) : 0.0;
            $cost = $rate && $unitCost !== null ? round($lbs * $unitCost, 2) : 0.0;
            $margin = round($revenue - $cost, 2);

            return (object) [
                'delivered_at' => $delivery->delivered_at,
                'delivery_note_id' => $delivery->delivery_note_id,
                'preregistration_id' => $p->id,
                'tracking' => $p->warehouse_code ?: ($p->tracking_external ?: ('#'.$p->id)),
                'service' => $service,
                'lbs' => round($lbs, 2),
                'price_per_lb' => $rate ? (float) $rate->price_per_lb : null,
                'cost_per_lb' => $unitCost,
                'revenue' => $revenue,
                'cost' => $cost,
                'margin' => $margin,
                'margin_pct' => $revenue > 0 ? round(($margin / $revenue) * 100, 1) : null,
                'missing_rate' => $rate === null,
            ];
        })->filter()->values();
    }

    private function resolveRate(Collection $rateCards, int $agencyId, string $service, $date): ?AccountingRateCard
    {
        $candidates = $rateCards->get($agencyId.'|'.$service);
        if (! $candidates) {
            return null;
        }
        $day = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

        return $candidates
            ->filter(fn ($r) => $r->effective_from->toDateString() <= $day
                && ($r->effective_to === null || $r->effective_to->toDateString() >= $day))
            ->sortByDesc('effective_from')
            ->first();
    }

    private function resolveOperatingCost(Collection $operatingCosts, string $service, $date): ?AccountingOperatingCost
    {
        $candidates = $operatingCosts->get($service);
        if (! $candidates) {
            return null;
        }
        $day = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

        return $candidates
            ->filter(fn ($r) => $r->effective_from->toDateString() <= $day)
            ->sortByDesc(fn ($r) => $r->effective_from->format('Ymd').str_pad((string) $r->id, 10, '0', STR_PAD_LEFT))
            ->first();
    }
}
