<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingOperatingCost extends Model
{
    protected $fillable = [
        'service_type',
        'cost_per_unit',
        'effective_from',
        'period_from',
        'period_to',
        'amount_paid_usd',
        'quantity',
        'quantity_unit',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:4',
        'effective_from' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'amount_paid_usd' => 'decimal:2',
        'quantity' => 'decimal:4',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function currentFor(string $serviceType, $onDate = null): ?self
    {
        $date = $onDate ? \Carbon\Carbon::parse($onDate)->toDateString() : now()->toDateString();

        return static::query()
            ->where('service_type', strtoupper($serviceType))
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, self|null>
     */
    public static function currentMap($onDate = null): array
    {
        $map = [];
        foreach (\App\Support\ServiceType::ALL as $service) {
            $map[$service] = static::currentFor($service, $onDate);
        }

        return $map;
    }
}
