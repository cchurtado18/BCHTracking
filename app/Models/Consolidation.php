<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consolidation extends Model
{
    protected $fillable = ['code', 'service_type', 'status', 'notes', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ConsolidationItem::class);
    }
}
