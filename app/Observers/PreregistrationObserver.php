<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Preregistration;
use Illuminate\Support\Facades\Request;

class PreregistrationObserver
{
    public function created(Preregistration $preregistration): void
    {
        $code = $preregistration->warehouse_code ?? $preregistration->tracking_external ?? '—';
        $this->log('created', $preregistration, null, $preregistration->getAttributes(), "Paquete creado (código/tracking: {$code})");
    }

    public function updated(Preregistration $preregistration): void
    {
        $changes = $preregistration->getChanges();
        unset($changes['updated_at']);
        if (empty($changes)) {
            return;
        }
        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $preregistration->getOriginal($key);
        }
        $summary = $this->buildUpdateSummary($preregistration, $old, $changes);
        $this->log('updated', $preregistration, $old, $changes, $summary);
    }

    public function deleted(Preregistration $preregistration): void
    {
        $code = $preregistration->warehouse_code ?? $preregistration->tracking_external ?? '—';
        $this->log('deleted', $preregistration, $preregistration->getAttributes(), null, "Paquete eliminado (código/tracking: {$code})");
    }

    private function log(string $action, Preregistration $preregistration, ?array $oldValues, ?array $newValues, string $summary): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => 'preregistration',
            'auditable_id' => $preregistration->id,
            'action' => $action,
            'summary' => $summary,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }

    private function buildUpdateSummary(Preregistration $preregistration, array $old, array $new): string
    {
        $parts = [];
        if (isset($new['intake_weight_lbs']) || isset($new['verified_weight_lbs'])) {
            if (isset($new['verified_weight_lbs'])) {
                $oldVal = $old['verified_weight_lbs'] ?? '—';
                $newVal = $new['verified_weight_lbs'];
                $parts[] = "Peso verificado: {$oldVal} → {$newVal} lbs";
            }
            if (isset($new['intake_weight_lbs']) && !isset($new['verified_weight_lbs'])) {
                $oldVal = $old['intake_weight_lbs'] ?? '—';
                $newVal = $new['intake_weight_lbs'];
                $parts[] = "Peso ingreso: {$oldVal} → {$newVal} lbs";
            }
        }
        if (isset($new['status'])) {
            $parts[] = "Estado: " . ($old['status'] ?? '—') . " → " . $new['status'];
        }
        if (isset($new['agency_id'])) {
            $parts[] = "Agencia asignada (ID: {$new['agency_id']})";
        }
        if (isset($new['label_name'])) {
            $parts[] = "Nombre/etiqueta modificado";
        }
        if (isset($new['tracking_external'])) {
            $parts[] = "Tracking modificado";
        }
        if (isset($new['label_print_count'])) {
            $parts[] = "Etiqueta reimpresa";
        }
        $rest = array_diff_key($new, array_flip(['intake_weight_lbs', 'verified_weight_lbs', 'status', 'agency_id', 'label_name', 'tracking_external', 'label_print_count', 'updated_at']));
        if (!empty($rest)) {
            $parts[] = "Otros campos actualizados";
        }
        $code = $preregistration->warehouse_code ?? $preregistration->tracking_external ?? $preregistration->id;
        return "Paquete modificado ({$code}): " . implode('; ', $parts);
    }
}
