<?php

namespace App\Services;

use App\Models\Preregistration;
use Illuminate\Support\Facades\DB;

class PackageProcessingService
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Process a package (assign agency, verify weight, generate warehouse code if needed)
     * 
     * @param Preregistration $preregistration
     * @param int $agencyId
     * @param float $verifiedWeightLbs
     * @return Preregistration
     * @throws \Exception
     */
    public function processPackage(Preregistration $preregistration, int $agencyId, float $verifiedWeightLbs): Preregistration
    {
        if ($preregistration->status !== 'IN_WAREHOUSE_NIC') {
            throw new \Exception('Solo se pueden procesar paquetes con estado IN_WAREHOUSE_NIC.');
        }

        return DB::transaction(function () use ($preregistration, $agencyId, $verifiedWeightLbs) {
            $data = [
                'agency_id' => $agencyId,
                'verified_weight_lbs' => $verifiedWeightLbs,
                'ready_at' => now(),
                'label_print_count' => ($preregistration->label_print_count ?? 0) + 1,
                'label_last_printed_at' => now(),
                'status' => 'READY',
            ];

            if (!$preregistration->warehouse_code) {
                $data['warehouse_code'] = $this->warehouseService->generateWarehouseCode();
            }

            $preregistration->update($data);

            return $preregistration->fresh();
        });
    }

    /**
     * Reprint label for a package
     * 
     * @param Preregistration $preregistration
     * @return Preregistration
     * @throws \Exception
     */
    public function reprintLabel(Preregistration $preregistration): Preregistration
    {
        if (!$preregistration->warehouse_code) {
            throw new \Exception('No se puede reimprimir la etiqueta: el paquete no tiene warehouse_code.');
        }

        $preregistration->update([
            'label_print_count' => $preregistration->label_print_count + 1,
            'label_last_printed_at' => now(),
        ]);

        return $preregistration->fresh();
    }
}

