<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiptNote extends Model
{
    protected $fillable = [
        'code',
        'delivered_by',
        'delivered_by_id_number',
        'delivered_by_phone',
        'agency_id',
        'received_by_user_id',
        'notes',
    ];

    /**
     * Genera el siguiente código único: REC-00001, REC-00002, ...
     */
    public static function generateCode(): string
    {
        $prefix = 'REC-';
        $last = static::where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/^REC-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public function preregistrations(): HasMany
    {
        return $this->hasMany(Preregistration::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
