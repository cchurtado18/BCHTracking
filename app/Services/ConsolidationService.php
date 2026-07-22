<?php

namespace App\Services;

use App\Models\Consolidation;
use Illuminate\Support\Facades\DB;

class ConsolidationService
{
    /**
     * Generate a unique consolidation code in format: SAC-YYYYMM-0001
     * 
     * @return string
     */
    public function generateCode(): string
    {
        $yearMonth = now()->format('Y-m');
        $prefix = 'SAC-' . now()->format('Ym') . '-';

        // Get the last code for this month
        $lastCode = Consolidation::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->value('code');

        if ($lastCode) {
            // Extract the number part (last 4 digits)
            $lastNumber = (int) substr($lastCode, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            // First consolidation of the month
            $nextNumber = 1;
        }

        // Format with leading zeros (4 digits)
        $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . $formattedNumber;
    }

    /**
     * Send a consolidation (change status to SENT and update preregistrations)
     * 
     * @param Consolidation $consolidation
     * @return void
     * @throws \Exception
     */
    public function sendConsolidation(Consolidation $consolidation): void
    {
        if ($consolidation->status !== 'OPEN') {
            throw new \Exception('Solo se pueden enviar consolidaciones con estado OPEN.');
        }

        DB::transaction(function () use ($consolidation) {
            // Update consolidation
            $consolidation->update([
                'status' => 'SENT',
                'sent_at' => now(),
            ]);

            // Update all preregistrations to IN_TRANSIT
            $preregistrationIds = $consolidation->items()
                ->whereNotNull('preregistration_id')
                ->pluck('preregistration_id');

            DB::table('preregistrations')
                ->whereIn('id', $preregistrationIds)
                ->update(['status' => 'IN_TRANSIT']);
        });
    }

    /**
     * Get consolidation report data
     * 
     * @param Consolidation $consolidation
     * @return array
     */
    public function getReport(Consolidation $consolidation): array
    {
        $items = $consolidation->relationLoaded('items')
            ? $consolidation->items
            : $consolidation->items()->with('preregistration')->get();

        $linkedItems = $items->whereNotNull('preregistration_id');
        $unmatchedItems = $items->whereNull('preregistration_id');

        $totalItems = $items->count();
        $totalLbs = $linkedItems->sum(function ($item) {
            return $item->preregistration?->verified_weight_lbs
                ?? $item->preregistration?->intake_weight_lbs
                ?? 0;
        });
        $totalCubicFeet = $linkedItems->sum(
            fn ($item) => $item->preregistration?->cubic_feet ?? 0
        );
        $expectedPackages = $linkedItems
            ->groupBy(function ($item) {
                $package = $item->preregistration;

                return $package?->warehouse_code
                    ?: $package?->tracking_external
                    ?: 'item-'.$item->id;
            })
            ->sum(function ($group) {
                $declaredTotal = $group
                    ->max(fn ($item) => (int) ($item->preregistration?->bultos_total ?? 0));

                return max($declaredTotal, $group->count());
            }) + $unmatchedItems->count();
        $scannedCount = $linkedItems->whereNotNull('scanned_at')->count();
        $missingCount = $linkedItems->count() - $scannedCount;

        return [
            'total_items' => $totalItems,
            'expected_packages' => $expectedPackages,
            'total_lbs' => round($totalLbs, 2),
            'total_cubic_feet' => round($totalCubicFeet, 2),
            'scanned_count' => $scannedCount,
            'missing_count' => $missingCount,
            'unmatched_count' => $unmatchedItems->count(),
        ];
    }
}

