<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ConsolidationItem;
use App\Models\Preregistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PreregistrationAdminResetService
{
    /**
     * Estados desde los que un admin puede devolver el paquete a Miami y desvincularlo del saco.
     */
    private const RESETTABLE_STATUSES = [
        'RECEIVED_MIAMI',
        'IN_TRANSIT',
        'IN_WAREHOUSE_NIC',
        'READY',
    ];

    public static function canResetToMiami(Preregistration $preregistration): bool
    {
        return self::resetBlockReason($preregistration) === null;
    }

    /**
     * Texto explicando por qué no aplica el reset, o null si aplica.
     */
    public static function resetBlockReason(Preregistration $preregistration): ?string
    {
        if (! in_array($preregistration->status, self::RESETTABLE_STATUSES, true)) {
            return 'Este estado no admite “volver a Miami” desde el panel (solo tránsito, almacén NIC, listo o en Miami con saco).';
        }

        if ($preregistration->status === 'RECEIVED_MIAMI' && ! $preregistration->consolidationItem) {
            return 'El paquete ya está en Miami sin saco; no hay nada que revertir.';
        }

        if ($preregistration->relationLoaded('delivery')) {
            if ($preregistration->delivery !== null) {
                return 'No se puede revertir: el paquete tiene un registro de entrega.';
            }
        } elseif ($preregistration->delivery()->exists()) {
            return 'No se puede revertir: el paquete tiene un registro de entrega.';
        }

        return null;
    }

    /**
     * Devuelve el paquete a RECEIVED_MIAMI, elimina el ítem del saco y limpia datos posteriores a Miami.
     * Registra un evento en auditoría (sin duplicar el observer del preregistro).
     *
     * @throws \InvalidArgumentException
     */
    public function resetToMiami(Preregistration $preregistration, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('El motivo es obligatorio.');
        }

        $block = self::resetBlockReason($preregistration);
        if ($block !== null) {
            throw new \InvalidArgumentException($block);
        }

        $oldSnapshot = $this->snapshotForAudit($preregistration);

        DB::transaction(function () use ($preregistration): void {
            $id = $preregistration->id;

            Preregistration::withoutEvents(function () use ($id): void {
                $p = Preregistration::query()->lockForUpdate()->findOrFail($id);

                $blockInside = self::resetBlockReason($p);
                if ($blockInside !== null) {
                    throw new \InvalidArgumentException($blockInside);
                }

                $item = ConsolidationItem::query()
                    ->where('preregistration_id', $p->id)
                    ->lockForUpdate()
                    ->first();

                if ($item !== null) {
                    $item->delete();
                }

                $p->update([
                    'status' => 'RECEIVED_MIAMI',
                    'received_nic_at' => null,
                    'ready_at' => null,
                    'verified_weight_lbs' => null,
                    'label_print_count' => 0,
                    'label_last_printed_at' => null,
                    'assignment_status' => null,
                    'holding_reason' => null,
                ]);
            });
        });

        $fresh = Preregistration::query()->findOrFail($preregistration->id);
        $newSnapshot = $this->snapshotForAudit($fresh);

        $code = $fresh->warehouse_code ?? $fresh->tracking_external ?? (string) $fresh->id;
        $summary = 'Admin: volver a Miami (desvincular saco). Motivo: '.mb_substr($reason, 0, 350)
            .(mb_strlen($reason) > 350 ? '…' : '')
            ." | Código/ref: {$code}";
        $summary = mb_substr($summary, 0, 500);

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => 'preregistration',
            'auditable_id' => $fresh->id,
            'action' => 'admin_reset_to_miami',
            'summary' => $summary,
            'old_values' => $oldSnapshot,
            'new_values' => $newSnapshot,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotForAudit(Preregistration $p): array
    {
        $item = $p->relationLoaded('consolidationItem')
            ? $p->consolidationItem
            : $p->consolidationItem()->with('consolidation')->first();

        $out = [
            'status' => $p->status,
            'received_nic_at' => $p->received_nic_at?->toIso8601String(),
            'ready_at' => $p->ready_at?->toIso8601String(),
            'verified_weight_lbs' => $p->verified_weight_lbs,
            'label_print_count' => $p->label_print_count,
            'label_last_printed_at' => $p->label_last_printed_at?->toIso8601String(),
            'assignment_status' => $p->assignment_status,
            'holding_reason' => $p->holding_reason,
        ];

        if ($item) {
            $out['consolidation_item_id'] = $item->id;
            $out['consolidation_id'] = $item->consolidation_id;
            $out['consolidation_code'] = $item->relationLoaded('consolidation')
                ? $item->consolidation?->code
                : $item->consolidation()->value('code');
            $out['consolidation_item_scanned_at'] = $item->scanned_at?->toIso8601String();
        } else {
            $out['consolidation_item_id'] = null;
        }

        return $out;
    }
}
