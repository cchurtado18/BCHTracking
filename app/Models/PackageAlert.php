<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageAlert extends Model
{
    public const RULE_STUCK_AIR = 'stuck_air';

    public const RULE_STUCK_SEA = 'stuck_sea';

    public const RULE_SPLIT_LOT = 'split_lot';

    public const RULES = [
        self::RULE_STUCK_AIR => 'Aéreo parado en almacén (24 h)',
        self::RULE_STUCK_SEA => 'Marítimo / pie cúbico parado en almacén (3 días)',
        self::RULE_SPLIT_LOT => 'Mismo día de recepción: unos entregados y otros no',
    ];

    protected $fillable = [
        'rule',
        'fingerprint',
        'preregistration_id',
        'status_at_open',
        'message',
        'emailed_at',
        'resolved_at',
        'dismissed_by',
    ];

    protected $casts = [
        'emailed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function ruleLabel(): string
    {
        return self::RULES[$this->rule] ?? $this->rule;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'RECEIVED_MIAMI' => 'Recibido Miami',
            'IN_TRANSIT' => 'En tránsito',
            'IN_WAREHOUSE_NIC' => 'En almacén NIC',
            'READY' => 'Listo para retiro',
            'DELIVERED' => 'Entregado',
            default => $status ?: '—',
        };
    }
}
