<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'summary',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAuditableLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            'preregistration' => 'Paquete / Preregistro',
            'accounting_invoice' => 'Factura PrimeTrack',
            'accounting_payment' => 'Cobro',
            'accounting_credit_note' => 'Nota de crédito',
            'accounting_expense' => 'Gasto',
            default => $this->auditable_type,
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Creado',
            'updated' => 'Modificado',
            'deleted' => 'Eliminado',
            'admin_reset_to_miami' => 'Admin: volver a Miami',
            'admin_change_intake_type' => 'Admin: cambiar tipo de ingreso',
            'invoice_voided' => 'Factura anulada',
            'invoice_deleted' => 'Factura eliminada',
            'payment_registered' => 'Cobro registrado',
            'payment_voided' => 'Cobro cancelado',
            'invoice_emailed' => 'Factura enviada',
            'credit_note_registered' => 'Nota de crédito',
            'credit_note_voided' => 'Nota de crédito anulada',
            'expense_registered' => 'Gasto registrado',
            'expense_deleted' => 'Gasto eliminado',
            default => $this->action,
        };
    }

    public function actorName(): string
    {
        return $this->user?->name ?: 'Sistema';
    }

    public function actorEmail(): ?string
    {
        return $this->user?->email;
    }

    public function actorRoleLabel(): string
    {
        $user = $this->user;
        if (! $user) {
            return 'Automático';
        }
        if ($user->is_admin) {
            return 'Administrador';
        }

        return $user->isAgencyUser() ? 'Cliente' : 'Operaciones';
    }

    public function actionClass(): string
    {
        return match ($this->action) {
            'created', 'payment_registered', 'credit_note_registered', 'expense_registered', 'invoice_emailed' => 'is-created',
            'updated', 'admin_change_intake_type' => 'is-updated',
            'deleted', 'invoice_voided', 'invoice_deleted', 'payment_voided', 'credit_note_voided', 'expense_deleted' => 'is-deleted',
            default => 'is-admin',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return array_merge($this->old_values ?? [], $this->new_values ?? []);
    }

    public function snapshotGet(string $key): mixed
    {
        $new = $this->new_values ?? [];
        if (array_key_exists($key, $new) && $new[$key] !== null && $new[$key] !== '') {
            return $new[$key];
        }

        return ($this->old_values ?? [])[$key] ?? null;
    }

    /**
     * Datos actuales del registro relacionado, si todavía existe.
     *
     * @var array<string, mixed>|null
     */
    public ?array $liveContext = null;

    public function displayCode(): ?string
    {
        foreach (['warehouse_code', 'folio', 'tracking_external'] as $key) {
            $value = $this->snapshotGet($key);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        $fallback = $this->liveContext['code'] ?? null;

        return $fallback !== null && $fallback !== '' ? (string) $fallback : null;
    }

    public function snapshotAgencyId(): ?int
    {
        $id = (int) ($this->snapshotGet('agency_id') ?? 0);
        if ($id > 0) {
            return $id;
        }
        $id = (int) ($this->liveContext['agency_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function snapshotAgencyClientId(): ?int
    {
        $id = (int) ($this->snapshotGet('agency_client_id') ?? 0);
        if ($id > 0) {
            return $id;
        }
        $id = (int) ($this->liveContext['agency_client_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @return array<string, string>
     */
    public static function actionOptions(): array
    {
        return [
            'created' => 'Creado',
            'updated' => 'Modificado',
            'deleted' => 'Eliminado',
            'admin_reset_to_miami' => 'Admin: volver a Miami',
            'admin_change_intake_type' => 'Admin: tipo de ingreso',
            'invoice_emailed' => 'Factura enviada',
            'invoice_voided' => 'Factura anulada',
            'invoice_deleted' => 'Factura eliminada',
            'payment_registered' => 'Cobro registrado',
            'payment_voided' => 'Cobro cancelado',
            'credit_note_registered' => 'Nota de crédito',
            'credit_note_voided' => 'Nota de crédito anulada',
            'expense_registered' => 'Gasto registrado',
            'expense_deleted' => 'Gasto eliminado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'preregistration' => 'Paquetes',
            'accounting_invoice' => 'Facturas',
            'accounting_payment' => 'Cobros',
            'accounting_credit_note' => 'Notas de crédito',
            'accounting_expense' => 'Gastos',
        ];
    }

    /**
     * @return list<string>
     */
    public static function hiddenValueKeys(): array
    {
        return ['id', 'password', 'remember_token'];
    }

    public static function fieldLabel(string $key): string
    {
        return match ($key) {
            'warehouse_code' => 'Código',
            'tracking_external' => 'Tracking',
            'label_name' => 'Nombre en etiqueta',
            'service_type' => 'Servicio',
            'intake_type' => 'Tipo de ingreso',
            'intake_weight_lbs' => 'Peso de ingreso',
            'verified_weight_lbs' => 'Peso verificado',
            'dimension' => 'Dimensiones',
            'description' => 'Descripción',
            'status' => 'Estado',
            'agency_id' => 'Cliente',
            'agency_client_id' => 'Destinatario',
            'parent_agency_id' => 'Cuenta padre',
            'ready_at' => 'Listo para retiro',
            'received_nic_at' => 'Recibido en NIC',
            'bulto_index' => 'Bulto',
            'bultos_total' => 'Total de bultos',
            'label_print_count' => 'Impresiones de etiqueta',
            'label_last_printed_at' => 'Última impresión',
            'photo_path' => 'Foto',
            'photo_intake_path' => 'Foto de ingreso',
            'created_at' => 'Fecha de registro',
            'updated_at' => 'Última actualización',
            'folio' => 'Folio',
            'total_usd' => 'Total USD',
            'amount_usd' => 'Monto USD',
            'amount_paid' => 'Pagado USD',
            'overpay' => 'Excedente USD',
            'apply_credit' => 'Crédito aplicado',
            'exchange_rate' => 'Tipo de cambio',
            'issued_at' => 'Fecha de emisión',
            'void_reason' => 'Motivo de anulación',
            'deposit_account' => 'Cuenta de depósito',
            'reference' => 'Referencia',
            'notes' => 'Notas',
            'note' => 'Nota',
            'reason' => 'Motivo',
            'email' => 'Correo',
            'category_id' => 'Categoría',
            'spent_at' => 'Fecha del gasto',
            'paid_at' => 'Fecha del cobro',
            'method' => 'Método de pago',
            'emailed_at' => 'Enviada por correo',
            default => str_replace('_', ' ', $key),
        };
    }

    public static function formatValue(string $key, mixed $value, string $timezone = 'America/New_York', array $agencyNames = [], array $recipientNames = [], array $categoryNames = []): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $raw = (string) $value;

        if (in_array($key, ['photo_path', 'photo_intake_path'], true)) {
            return 'Sí';
        }
        if ($key === 'agency_client_id') {
            $id = (int) $raw;

            return $recipientNames[$id] ?? ('#'.$id);
        }
        if ($key === 'category_id') {
            $id = (int) $raw;

            return $categoryNames[$id] ?? ('#'.$id);
        }
        if ($key === 'method') {
            return AccountingPayment::METHODS[$raw] ?? $raw;
        }
        if ($key === 'deposit_account') {
            return AccountingPayment::ACCOUNTS[$raw] ?? $raw;
        }
        if ($key === 'status') {
            return match ($raw) {
                'PHOTO_PENDING' => 'Pendiente de datos',
                'RECEIVED_MIAMI' => 'Recibido en Miami',
                'IN_TRANSIT' => 'En tránsito',
                'IN_WAREHOUSE_NIC' => 'En almacén NIC',
                'READY' => 'Listo para retiro',
                'DELIVERED' => 'Entregado',
                'CANCELLED' => 'Inactivo',
                'issued' => 'Emitida',
                'paid' => 'Pagada',
                'void' => 'Anulada',
                'draft' => 'Borrador',
                'active' => 'Activo',
                default => $raw,
            };
        }
        if ($key === 'service_type') {
            return \App\Support\ServiceType::label($raw);
        }
        if ($key === 'intake_type') {
            return match ($raw) {
                'COURIER' => 'Courier',
                'DROP_OFF' => 'Drop Off',
                default => $raw,
            };
        }
        if (in_array($key, ['agency_id', 'parent_agency_id'], true)) {
            $id = (int) $raw;

            return $agencyNames[$id] ?? ('#'.$id);
        }
        if (str_ends_with($key, '_at') || in_array($key, ['created_at', 'updated_at'], true)) {
            try {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                    return \Illuminate\Support\Carbon::parse($raw)->format('d/m/Y');
                }

                return \Illuminate\Support\Carbon::parse($raw, 'UTC')
                    ->timezone($timezone)
                    ->format('d/m/Y H:i');
            } catch (\Throwable) {
                return $raw;
            }
        }
        if (str_ends_with($key, '_lbs') && is_numeric($raw)) {
            return number_format((float) $raw, 2).' lb';
        }
        if (str_ends_with($key, '_usd') && is_numeric($raw)) {
            return '$'.number_format((float) $raw, 2);
        }

        return $raw;
    }
}
