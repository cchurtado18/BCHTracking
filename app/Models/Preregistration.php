<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Preregistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'intake_type',
        'tracking_external',
        'warehouse_code',
        'label_name',
        'service_type',
        'intake_weight_lbs',
        'dimension',
        'description',
        'bulto_index',
        'bultos_total',
        'status',
        'received_nic_at',
        'agency_id',
        'agency_client_id',
        'assignment_status',
        'holding_reason',
        'verified_weight_lbs',
        'ready_at',
        'label_print_count',
        'label_last_printed_at',
    ];

    /**
     * Pies cúbicos calculados desde dimensión (L x W x H en pulgadas) / 1728.
     * Ej: "10 x 8 x 5" → (10*8*5)/1728 ≈ 0.23
     */
    public function getCubicFeetAttribute(): ?float
    {
        $dim = $this->attributes['dimension'] ?? $this->dimension ?? null;
        if (empty($dim) || ! is_string($dim)) {
            return null;
        }
        $parsed = self::parseDimensionToInches($dim);
        if ($parsed === null) {
            return null;
        }
        return round(($parsed[0] * $parsed[1] * $parsed[2]) / 1728, 4);
    }

    /**
     * Parsea una cadena de dimensión (ej. "10 x 8 x 5", "10x8x5 in") y devuelve [L, W, H] en pulgadas o null.
     */
    public static function parseDimensionToInches(?string $dimension): ?array
    {
        if (empty($dimension) || ! is_string($dimension)) {
            return null;
        }
        $dimension = preg_replace('/\s*in\.?\s*$/i', '', trim($dimension));
        if (preg_match_all('/\d+(?:\.\d+)?/', $dimension, $m) && count($m[0]) >= 3) {
            $nums = array_map('floatval', array_slice($m[0], 0, 3));
            if ($nums[0] > 0 && $nums[1] > 0 && $nums[2] > 0) {
                return $nums;
            }
        }
        return null;
    }

    /**
     * Siempre guardar tracking en mayúsculas.
     */
    protected function setTrackingExternalAttribute(?string $value): void
    {
        $this->attributes['tracking_external'] = $value !== null && $value !== ''
            ? strtoupper($value)
            : $value;
    }

    protected $casts = [
        'intake_weight_lbs' => 'decimal:2',
        'verified_weight_lbs' => 'decimal:2',
        'received_nic_at' => 'datetime',
        'ready_at' => 'datetime',
        'label_last_printed_at' => 'datetime',
        'label_print_count' => 'integer',
        'bulto_index' => 'integer',
        'bultos_total' => 'integer',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(PreregistrationPhoto::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agencyClient(): BelongsTo
    {
        return $this->belongsTo(AgencyClient::class);
    }

    public function consolidationItem(): HasOne
    {
        return $this->hasOne(ConsolidationItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }
}
