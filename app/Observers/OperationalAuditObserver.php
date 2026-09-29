<?php

namespace App\Observers;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\ReceiptNote;
use App\Models\User;
use App\Support\AuditRecorder;
use Illuminate\Database\Eloquent\Model;

class OperationalAuditObserver
{
    public function created(Model $model): void
    {
        if (! $this->supports($model)) {
            return;
        }

        $snapshot = $this->snapshot($model);
        AuditRecorder::record(
            $this->type($model),
            $model->getKey(),
            'created',
            $this->createdSummary($model, $snapshot),
            null,
            $snapshot,
        );
    }

    public function updated(Model $model): void
    {
        if (! $this->supports($model)) {
            return;
        }

        $changes = $model->getChanges();
        foreach ($this->ignoredUpdateKeys($model) as $key) {
            unset($changes[$key]);
        }
        if ($changes === []) {
            return;
        }

        if ($model instanceof User && array_key_exists('password', $changes)) {
            $changes['password'] = true;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
            if ($key === 'password') {
                $old[$key] = true;
            }
        }

        $snapshot = $this->snapshot($model);
        $old = array_merge($this->contextExtras($model, true), $old);
        $new = array_merge($this->contextExtras($model, false), $changes);

        AuditRecorder::record(
            $this->type($model),
            $model->getKey(),
            'updated',
            $this->updatedSummary($model, $old, $new, $snapshot),
            $old,
            $new,
        );
    }

    public function deleting(Model $model): void
    {
        if (! $this->supports($model)) {
            return;
        }

        if ($model instanceof Consolidation) {
            $model->loadMissing('items.preregistration');
        }
        if ($model instanceof DeliveryNote) {
            $model->loadMissing(['deliveries.preregistration', 'agency']);
        }
        if ($model instanceof Delivery) {
            $model->loadMissing(['deliveryNote', 'preregistration']);
        }
        if ($model instanceof Agency) {
            $model->loadMissing('users');
        }
        if ($model instanceof ConsolidationItem) {
            $model->loadMissing(['consolidation', 'preregistration']);
        }
        if ($model instanceof ReceiptNote) {
            $model->loadMissing('agency');
        }
    }

    public function deleted(Model $model): void
    {
        if (! $this->supports($model)) {
            return;
        }

        AuditRecorder::record(
            $this->type($model),
            $model->getKey(),
            'deleted',
            $this->deletedSummary($model),
            $this->snapshot($model),
            null,
        );
    }

    private function supports(Model $model): bool
    {
        return in_array($model::class, [
            DeliveryNote::class,
            Delivery::class,
            ReceiptNote::class,
            Prealert::class,
            Consolidation::class,
            ConsolidationItem::class,
            Agency::class,
            User::class,
        ], true);
    }

    private function type(Model $model): string
    {
        return match ($model::class) {
            DeliveryNote::class => 'delivery_note',
            Delivery::class => 'delivery',
            ReceiptNote::class => 'receipt_note',
            Prealert::class => 'prealert',
            Consolidation::class => 'consolidation',
            ConsolidationItem::class => 'consolidation_item',
            Agency::class => 'agency',
            User::class => 'user',
            default => $model->getTable(),
        };
    }

    /**
     * @return list<string>
     */
    private function ignoredUpdateKeys(Model $model): array
    {
        $keys = ['updated_at', 'created_at'];
        if ($model instanceof User) {
            $keys[] = 'remember_token';
        }
        if ($model instanceof Agency) {
            $keys[] = 'credit_balance_usd';
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Model $model): array
    {
        $base = match (true) {
            $model instanceof DeliveryNote => [
                'code' => $model->code,
                'agency_id' => $model->agency_id,
                'packages' => $model->relationLoaded('deliveries')
                    ? $model->deliveries->map(fn (Delivery $d) => $this->packageLabel($d->preregistration))->filter()->values()->all()
                    : null,
            ],
            $model instanceof Delivery => $this->deliverySnapshot($model),
            $model instanceof ReceiptNote => [
                'code' => $model->code,
                'agency_id' => $model->agency_id,
                'delivered_by' => $model->delivered_by,
                'delivered_by_id_number' => $model->delivered_by_id_number,
                'delivered_by_phone' => $model->delivered_by_phone,
                'received_by_user_id' => $model->received_by_user_id,
                'notes' => $model->notes,
            ],
            $model instanceof Prealert => [
                'tracking' => $model->tracking,
                'name' => $model->name,
                'agency_id' => $model->agency_id,
                'agency_name' => $model->agency_name,
                'service_type' => $model->service_type,
                'description' => $model->description,
                'status' => $model->status,
                'preregistration_id' => $model->preregistration_id,
            ],
            $model instanceof Consolidation => [
                'code' => $model->code,
                'service_type' => $model->service_type,
                'status' => $model->status,
                'transport_number' => $model->transport_number,
                'notes' => $model->notes,
                'sent_at' => $model->sent_at?->format('c'),
                'items' => $model->relationLoaded('items')
                    ? $model->items->map(fn (ConsolidationItem $item) => $this->consolidationItemLabel($item))->all()
                    : null,
            ],
            $model instanceof ConsolidationItem => [
                'code' => $model->consolidation?->code,
                'consolidation_id' => $model->consolidation_id,
                'preregistration_id' => $model->preregistration_id,
                'warehouse_code' => $model->preregistration?->warehouse_code,
                'tracking_external' => $model->preregistration?->tracking_external,
                'unmatched_code' => $model->unmatched_code,
                'agency_id' => $model->preregistration?->agency_id,
            ],
            $model instanceof Agency => [
                'code' => $model->code,
                'name' => $model->name,
                'agency_id' => $model->id,
                'parent_agency_id' => $model->parent_agency_id,
                'account_type' => $model->account_type,
                'phone' => $model->phone,
                'is_active' => $model->is_active,
                'tax_id' => $model->tax_id,
                'billing_email' => $model->billing_email,
                'users' => $model->relationLoaded('users')
                    ? $model->users->map(fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ])->all()
                    : null,
            ],
            $model instanceof User => [
                'name' => $model->name,
                'email' => $model->email,
                'is_admin' => $model->is_admin,
                'agency_id' => $model->agency_id,
                'permissions' => $model->permissions,
            ],
            default => $model->getAttributes(),
        };

        return array_filter(
            $base,
            fn ($value) => $value !== null && $value !== [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function deliverySnapshot(Delivery $delivery): array
    {
        $delivery->loadMissing(['deliveryNote', 'preregistration']);
        $package = $delivery->preregistration;

        return array_filter([
            'code' => $delivery->deliveryNote?->code,
            'delivery_note_id' => $delivery->delivery_note_id,
            'preregistration_id' => $delivery->preregistration_id,
            'warehouse_code' => $package?->warehouse_code,
            'tracking_external' => $package?->tracking_external,
            'label_name' => $package?->label_name,
            'agency_id' => $delivery->deliveryNote?->agency_id ?? $package?->agency_id,
            'delivered_to' => $delivery->delivered_to,
            'retirer_id_number' => $delivery->retirer_id_number,
            'retirer_phone' => $delivery->retirer_phone,
            'delivered_at' => $delivery->delivered_at?->format('c'),
            'delivery_type' => $delivery->delivery_type,
            'invoice_number' => $delivery->invoice_number,
            'notes' => $delivery->notes,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Extra fields so searches and the audit UI still show hoja/paquete after a change.
     *
     * @return array<string, mixed>
     */
    private function contextExtras(Model $model, bool $original): array
    {
        if (! $model instanceof Delivery) {
            if ($model instanceof DeliveryNote) {
                return array_filter([
                    'code' => $model->code,
                    'agency_id' => $original ? ($model->getOriginal('agency_id') ?? $model->agency_id) : $model->agency_id,
                ], fn ($value) => $value !== null && $value !== '');
            }

            return [];
        }

        $noteId = $original
            ? ($model->getOriginal('delivery_note_id') ?? $model->delivery_note_id)
            : $model->delivery_note_id;
        $note = $noteId ? DeliveryNote::query()->find($noteId) : $model->deliveryNote;
        $package = $model->preregistration ?: Preregistration::query()->find($model->preregistration_id);

        return array_filter([
            'code' => $note?->code,
            'previous_note_code' => $original ? $note?->code : null,
            'warehouse_code' => $package?->warehouse_code,
            'tracking_external' => $package?->tracking_external,
            'label_name' => $package?->label_name,
            'agency_id' => $note?->agency_id ?? $package?->agency_id,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function createdSummary(Model $model, array $snapshot): string
    {
        return match (true) {
            $model instanceof DeliveryNote => 'Hoja de salida '.($snapshot['code'] ?? '—').' creada',
            $model instanceof Delivery => sprintf(
                'Paquete %s agregado a hoja %s (retiró %s)',
                $snapshot['warehouse_code'] ?? $snapshot['tracking_external'] ?? '—',
                $snapshot['code'] ?? '—',
                $snapshot['delivered_to'] ?? '—'
            ),
            $model instanceof ReceiptNote => 'Nota de recepción '.($snapshot['code'] ?? '—').' creada',
            $model instanceof Prealert => 'Prealerta creada (tracking: '.($snapshot['tracking'] ?? '—').')',
            $model instanceof Consolidation => ($model->unitNounTitle()).' '.($snapshot['code'] ?? '—').' creado',
            $model instanceof ConsolidationItem => sprintf(
                'Paquete %s agregado a %s %s',
                $this->consolidationItemLabel($model),
                $model->consolidation?->unitNoun() ?? 'consolidado',
                $snapshot['code'] ?? '—'
            ),
            $model instanceof Agency => 'Cuenta creada: '.trim(($snapshot['code'] ?? '').' '.($snapshot['name'] ?? '')),
            $model instanceof User => 'Usuario creado: '.($snapshot['name'] ?? '—').' ('.($snapshot['email'] ?? '—').')',
            default => class_basename($model).' creado',
        };
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $snapshot
     */
    private function updatedSummary(Model $model, array $old, array $new, array $snapshot): string
    {
        if ($model instanceof Delivery && array_key_exists('delivery_note_id', $new)) {
            $oldNote = DeliveryNote::query()->find($old['delivery_note_id'] ?? 0);
            $newNote = DeliveryNote::query()->find($new['delivery_note_id'] ?? 0);
            $pkg = $snapshot['warehouse_code'] ?? $snapshot['tracking_external'] ?? '—';

            return sprintf(
                'Paquete %s movido de hoja %s a hoja %s',
                $pkg,
                $oldNote?->code ?? '—',
                $newNote?->code ?? '—'
            );
        }

        $label = match (true) {
            $model instanceof DeliveryNote => 'Hoja '.($snapshot['code'] ?? $model->code ?? '—'),
            $model instanceof Delivery => 'Salida de '.$this->packageLabel($model->preregistration).' en hoja '.($snapshot['code'] ?? '—'),
            $model instanceof ReceiptNote => 'Nota de recepción '.($snapshot['code'] ?? '—'),
            $model instanceof Prealert => 'Prealerta '.($snapshot['tracking'] ?? '—'),
            $model instanceof Consolidation => ($model->unitNounTitle()).' '.($snapshot['code'] ?? '—'),
            $model instanceof ConsolidationItem => 'Ítem '.$this->consolidationItemLabel($model),
            $model instanceof Agency => 'Cuenta '.trim(($snapshot['code'] ?? '').' '.($snapshot['name'] ?? '')),
            $model instanceof User => 'Usuario '.($snapshot['name'] ?? $snapshot['email'] ?? '—'),
            default => class_basename($model),
        };

        $parts = [];
        foreach ($new as $key => $value) {
            if (in_array($key, ['code', 'warehouse_code', 'tracking_external', 'label_name', 'previous_note_code'], true)
                && ! array_key_exists($key, $model->getChanges())) {
                continue;
            }
            if ($key === 'password') {
                $parts[] = 'Contraseña actualizada';

                continue;
            }
            if ($key === 'permissions') {
                $parts[] = 'Permisos modificados';

                continue;
            }
            $parts[] = AuditLog::fieldLabel($key).': '
                .AuditLog::formatValue($key, $old[$key] ?? null)
                .' → '
                .AuditLog::formatValue($key, $value);
        }

        if ($parts === []) {
            return $label.' modificado';
        }

        return $label.' modificado: '.implode('; ', array_slice($parts, 0, 8));
    }

    private function deletedSummary(Model $model): string
    {
        $snapshot = $this->snapshot($model);

        return match (true) {
            $model instanceof DeliveryNote => 'Hoja de salida '.($snapshot['code'] ?? '—').' eliminada',
            $model instanceof Delivery => sprintf(
                'Paquete %s quitado de hoja %s',
                $snapshot['warehouse_code'] ?? $snapshot['tracking_external'] ?? '—',
                $snapshot['code'] ?? '—'
            ),
            $model instanceof ReceiptNote => 'Nota de recepción '.($snapshot['code'] ?? '—').' eliminada',
            $model instanceof Prealert => 'Prealerta eliminada (tracking: '.($snapshot['tracking'] ?? '—').')',
            $model instanceof Consolidation => ($model->unitNounTitle()).' '.($snapshot['code'] ?? '—').' eliminado',
            $model instanceof ConsolidationItem => sprintf(
                'Paquete %s quitado de %s %s',
                $this->consolidationItemLabel($model),
                $model->consolidation?->unitNoun() ?? 'consolidado',
                $snapshot['code'] ?? '—'
            ),
            $model instanceof Agency => 'Cuenta eliminada: '.trim(($snapshot['code'] ?? '').' '.($snapshot['name'] ?? '')),
            $model instanceof User => 'Usuario eliminado: '.($snapshot['name'] ?? '—').' ('.($snapshot['email'] ?? '—').')',
            default => class_basename($model).' eliminado',
        };
    }

    private function packageLabel(?Preregistration $package): string
    {
        if (! $package) {
            return '—';
        }

        return $package->warehouse_code ?: ($package->tracking_external ?: ('#'.$package->id));
    }

    private function consolidationItemLabel(ConsolidationItem $item): string
    {
        return $item->preregistration
            ? $this->packageLabel($item->preregistration)
            : (string) ($item->unmatched_code ?: ('#'.$item->id));
    }
}
