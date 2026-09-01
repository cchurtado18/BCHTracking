<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingRateCard extends Model
{
    protected $fillable = [
        'agency_id',
        'service_type',
        'price_per_lb',
        'cost_per_lb',
        'currency',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    protected $casts = [
        'price_per_lb' => 'decimal:4',
        'cost_per_lb' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tarifa vigente para agencia + servicio en una fecha.
     */
    public static function currentFor(int $agencyId, string $serviceType, $onDate = null): ?self
    {
        $date = $onDate ? \Carbon\Carbon::parse($onDate)->toDateString() : now()->toDateString();

        return static::query()
            ->where('agency_id', $agencyId)
            ->where('service_type', strtoupper($serviceType))
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
