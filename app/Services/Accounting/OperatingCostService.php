<?php

namespace App\Services\Accounting;

use App\Models\AccountingExpense;
use App\Models\AccountingOperatingCost;
use App\Models\Delivery;
use App\Models\Preregistration;
use App\Support\ServiceType;
use Carbon\CarbonInterface;

class OperatingCostService
{
    /**
     * Volumen real y flete pagado del período, por servicio.
     *
     * @return array<string, object{
     *     service: string,
     *     received_qty: float,
     *     received_packages: int,
     *     delivered_qty: float,
     *     delivered_packages: int,
     *     freight_paid: float,
     *     implied: float|null,
     *     lines: \Illuminate\Support\Collection,
     *     current: ?AccountingOperatingCost
     * }>
     */
    public function snapshot(CarbonInterface $from, CarbonInterface $to): array
    {
        $received = Preregistration::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['id', 'service_type', 'intake_weight_lbs', 'verified_weight_lbs', 'dimension']);

        $delivered = Delivery::query()
            ->with(['preregistration:id,service_type,intake_weight_lbs,verified_weight_lbs,dimension'])
            ->whereBetween('delivered_at', [$from, $to])
            ->get();

        $freight = AccountingExpense::query()
            ->with('category:id,name')
            ->whereDate('spent_at', '>=', $from->toDateString())
            ->whereDate('spent_at', '<=', $to->toDateString())
            ->whereIn('service_type', ServiceType::ALL)
            ->orderBy('spent_at')
            ->orderBy('id')
            ->get();

        $out = [];
        foreach (ServiceType::ALL as $service) {
            $recv = $received->filter(fn ($p) => ServiceType::normalize($p->service_type) === $service);
            $del = $delivered->filter(fn ($d) => $d->preregistration && ServiceType::normalize($d->preregistration->service_type) === $service);
            $lines = $freight->where('service_type', $service)->values();
            $paid = round((float) $lines->sum('amount_usd'), 2);
            $recvQty = round((float) $recv->sum(fn ($p) => ServiceType::billedQuantity($p)), 4);
            $implied = $recvQty > 0 && $paid > 0 ? round($paid / $recvQty, 4) : null;

            $out[$service] = (object) [
                'service' => $service,
                'received_qty' => $recvQty,
                'received_packages' => $recv->count(),
                'delivered_qty' => round((float) $del->sum(fn ($d) => ServiceType::billedQuantity($d->preregistration)), 4),
                'delivered_packages' => $del->count(),
                'freight_paid' => $paid,
                'implied' => $implied,
                'lines' => $lines,
                'current' => AccountingOperatingCost::currentFor($service),
            ];
        }

        return $out;
    }
}
