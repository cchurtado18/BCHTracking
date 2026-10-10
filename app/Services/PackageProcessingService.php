<?php

namespace App\Services;

use App\Models\Preregistration;
use Illuminate\Support\Facades\DB;

class PackageProcessingService
{
    public function __construct(
        protected WarehouseService $warehouseService,
        protected ClientPackageStatusMailer $clientMailer,
    ) {
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

        $processed = DB::transaction(function () use ($preregistration, $agencyId, $verifiedWeightLbs) {
            $data = [
                'agency_id' => $agencyId,
                'verified_weight_lbs' => $verifiedWeightLbs,
                'ready_at' => now(),
                'label_print_count' => ($preregistration->label_print_count ?? 0) + 1,
                'label_last_printed_at' => now(),
                'status' => 'READY',
            ];

            if (! $preregistration->warehouse_code) {
                $data['warehouse_code'] = $this->warehouseService->generateWarehouseCode();
            }

            $preregistration->update($data);

            return $preregistration->fresh();
        });

        $this->clientMailer->notifyReadyForPickup($processed);

        return $processed;
    }

    /**
     * Corrige el peso verificado de un paquete listo o entregado.
     * La hoja de salida y la próxima factura leen ese peso del paquete.
     */
    public function correctVerifiedWeight(Preregistration $preregistration, float $verifiedWeightLbs): Preregistration
    {
        if (! in_array($preregistration->status, ['READY', 'DELIVERED'], true)) {
            throw new \Exception('Solo se puede corregir el peso de paquetes listos para retiro o entregados.');
        }

        $preregistration->loadMissing([
            'delivery.deliveryNote.accountingInvoice',
            'delivery.deliveryNote.linkedInvoices',
        ]);
        $invoice = $preregistration->delivery?->deliveryNote?->currentInvoice();
        if ($invoice) {
            throw new \Exception(
                'La hoja ya tiene la factura '.$invoice->folio.' activa. Anúlela, corrija el peso y emita de nuevo para cobrar lo correcto.'
            );
        }

        $preregistration->update([
            'verified_weight_lbs' => $verifiedWeightLbs,
        ]);

        return $preregistration->fresh();
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
        if (! $preregistration->warehouse_code) {
            if ($preregistration->status === 'PHOTO_PENDING') {
                throw new \Exception('No se puede reimprimir la etiqueta: complete y guarde el preregistro para asignar el código.');
            }
            $this->warehouseService->ensureWarehouseCode($preregistration);
        }

        $preregistration->update([
            'label_print_count' => $preregistration->label_print_count + 1,
            'label_last_printed_at' => now(),
        ]);

        return $preregistration->fresh();
    }
}

