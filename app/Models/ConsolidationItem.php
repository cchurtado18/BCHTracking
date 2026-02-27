<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationItem extends Model
{
    protected $fillable = ['consolidation_id', 'preregistration_id', 'scanned_at'];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function consolidation(): BelongsTo
    {
        return $this->belongsTo(Consolidation::class);
    }

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }
}
