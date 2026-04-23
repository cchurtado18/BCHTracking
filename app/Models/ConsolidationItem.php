<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidationItem extends Model
{
    protected $fillable = ['consolidation_id', 'preregistration_id', 'scanned_at', 'unmatched_code'];

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

    public function isUnmatchedOnly(): bool
    {
        return $this->preregistration_id === null && filled($this->unmatched_code);
    }
}
