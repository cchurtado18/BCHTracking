<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consolidation extends Model
{
    protected $fillable = ['code', 'service_type', 'status', 'notes', 'sent_at', 'transport_number'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ConsolidationItem::class)->orderByDesc('id');
    }

    public function unitNoun(bool $plural = false): string
    {
        return \App\Support\ServiceType::consolidationNoun($this->service_type, $plural);
    }

    public function unitNounTitle(bool $plural = false): string
    {
        return \App\Support\ServiceType::consolidationNounTitle($this->service_type, $plural);
    }

    public function transportNumberLabel(): string
    {
        return \App\Support\ServiceType::transportNumberLabel($this->service_type);
    }
}
