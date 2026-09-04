<?php

namespace App\Services;

use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use App\Support\ServiceType;
use Illuminate\Support\Facades\DB;

class ConsolidationService
{
    /**
     * Código único: SAC-YYYYMM-0001 (aéreo) o CNT-YYYYMM-0001 (marítimo).
     */
    public function generateCode(string $serviceType): string
    {
        $prefix = ServiceType::route($serviceType) === ServiceType::SEA
            ? 'CNT-'.now()->format('Ym').'-'
            : 'SAC-'.now()->format('Ym').'-';

        $lastCode = Consolidation::where('code', 'like', $prefix.'%')
            ->orderBy('code', 'desc')
            ->value('code');

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
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

    /**
     * Tracking / warehouse / código escaneado comparable: mayúsculas, sin espacios.
     */
    public static function normalizeScanCode(?string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $code)) ?? '');
    }

    public function packageMatchesCode(Preregistration $package, string $normalizedCode): bool
    {
        if ($normalizedCode === '') {
            return false;
        }

        return $normalizedCode === self::normalizeScanCode($package->tracking_external)
            || $normalizedCode === self::normalizeScanCode($package->warehouse_code);
    }

    /**
     * Enlaza códigos ya guardados en el saco cuando el preregistro ya existe
     * (o se creó antes de que el observer estuviera activo).
     */
    public function resolveUnmatchedItems(Consolidation $consolidation): int
    {
        $items = $consolidation->items()
            ->whereNull('preregistration_id')
            ->whereNotNull('unmatched_code')
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $resolved = 0;

        foreach ($items as $item) {
            $code = self::normalizeScanCode($item->unmatched_code);
            if ($code === '') {
                continue;
            }

            $package = $this->findPackageByScanCode($code, $consolidation->service_type, false);
            if ($package) {
                $this->attachPackageToUnmatchedItem($item, $package, $consolidation);
                $resolved++;

                continue;
            }

            $alreadyOnSack = $this->findPackageByScanCode($code, $consolidation->service_type, true);
            if ($alreadyOnSack && (int) $alreadyOnSack->consolidationItem?->consolidation_id === (int) $consolidation->id) {
                $item->delete();
                $resolved++;
            }
        }

        return $resolved;
    }

    /**
     * Si el paquete coincide con un código ya escaneado en un saco (sin preregistro),
     * lo enlaza para que el saco reconozca tracking y peso.
     */
    public function linkUnmatchedItemsFor(Preregistration $package): void
    {
        if ($package->consolidationItem()->exists()) {
            return;
        }

        if (in_array($package->status, ['PHOTO_PENDING', 'CANCELLED'], true)) {
            return;
        }

        $codes = collect([
            self::normalizeScanCode($package->tracking_external),
            self::normalizeScanCode($package->warehouse_code),
        ])->filter()->unique()->values();

        if ($codes->isEmpty()) {
            return;
        }

        $item = ConsolidationItem::query()
            ->with('consolidation')
            ->whereNull('preregistration_id')
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhereRaw("REPLACE(UPPER(TRIM(COALESCE(unmatched_code, ''))), ' ', '') = ?", [$code]);
                }
            })
            ->orderBy('id')
            ->first();

        if (! $item) {
            return;
        }

        $sack = $item->consolidation;
        if ($sack && $package->service_type && ! ServiceType::matchesRoute($package->service_type, $sack->service_type)) {
            return;
        }

        $this->attachPackageToUnmatchedItem($item, $package, $sack);
    }

    public function findAvailableForScan(string $code, string $serviceType, bool $anyService = false): ?Preregistration
    {
        return $this->findPackageByScanCode($code, $serviceType, false, $anyService, true);
    }

    private function attachPackageToUnmatchedItem(ConsolidationItem $item, Preregistration $package, ?Consolidation $sack): void
    {
        $item->update([
            'preregistration_id' => $package->id,
        ]);

        if ($sack?->status === 'SENT' && $package->status === 'RECEIVED_MIAMI') {
            $package->update(['status' => 'IN_TRANSIT']);
        }
    }

    /**
     * @param  bool  $alreadyInSack  Si true, busca paquetes que ya tienen ítem de consolidación.
     */
    private function findPackageByScanCode(
        string $code,
        ?string $sackService,
        bool $alreadyInSack,
        bool $anyService = false,
        bool $miamiOnly = false
    ): ?Preregistration {
        $normalized = self::normalizeScanCode($code);
        if ($normalized === '') {
            return null;
        }

        $query = Preregistration::query()
            ->with('consolidationItem')
            ->where(function ($q) use ($normalized) {
                $q->whereRaw("REPLACE(UPPER(TRIM(COALESCE(tracking_external, ''))), ' ', '') = ?", [$normalized])
                    ->orWhereRaw("REPLACE(UPPER(TRIM(COALESCE(warehouse_code, ''))), ' ', '') = ?", [$normalized]);
            })
            ->orderBy('id');

        if ($miamiOnly) {
            $query->where('status', 'RECEIVED_MIAMI');
        } else {
            $query->whereNotIn('status', ['CANCELLED']);
        }

        if ($alreadyInSack) {
            $query->whereHas('consolidationItem');
        } else {
            $query->whereDoesntHave('consolidationItem');
        }

        if (! $anyService && $sackService && ! $alreadyInSack) {
            $query->where(function ($q) use ($sackService) {
                $q->whereIn('service_type', ServiceType::servicesForRoute($sackService))
                    ->orWhereNull('service_type')
                    ->orWhere('service_type', '');
            });
        }

        $candidates = $query->get();

        return $candidates->first(function (Preregistration $package) use ($normalized, $sackService, $anyService) {
            if (! $anyService && $package->service_type && $sackService && ! ServiceType::matchesRoute($package->service_type, $sackService)) {
                return false;
            }

            return $this->packageMatchesCode($package, $normalized);
        });
    }
}

