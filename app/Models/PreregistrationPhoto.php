<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreregistrationPhoto extends Model
{
    protected $fillable = ['preregistration_id', 'path', 'mime', 'size_bytes', 'sort_order'];

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }
}
