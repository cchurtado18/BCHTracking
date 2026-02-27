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
            $preregistrationIds = $consolidation->items()->pluck('preregistration_id');
            
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
        $items = $consolidation->items()->with('preregistration')->get();

        $totalItems = $items->count();
        $totalLbs = $items->sum(function ($item) {
            return $item->preregistration->intake_weight_lbs ?? 0;
        });
        $scannedCount = $items->whereNotNull('scanned_at')->count();
        $missingCount = $totalItems - $scannedCount;

        return [
            'total_items' => $totalItems,
            'total_lbs' => round($totalLbs, 2),
            'scanned_count' => $scannedCount,
            'missing_count' => $missingCount,
        ];
    }
}

