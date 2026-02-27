<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = ['code', 'agency_id'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Genera el siguiente código único para una nota de entrega: BCH-0001, BCH-0002, ...
     */
    public static function generateCode(): string
    {
        $prefix = 'BCH-';
        $last = static::where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/^BCH-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
