<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingExchangeRate extends Model
{
    protected $fillable = ['rate', 'effective_from', 'created_by'];

    protected $casts = [
        'rate' => 'decimal:4',
        'effective_from' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
