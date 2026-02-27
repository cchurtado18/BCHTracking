<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyClient extends Model
{
    protected $fillable = ['agency_id', 'full_name', 'phone', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function preregistrations(): HasMany
    {
        return $this->hasMany(Preregistration::class);
    }
}
