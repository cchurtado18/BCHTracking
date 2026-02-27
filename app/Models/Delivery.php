<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'preregistration_id',
        'delivered_at',
        'delivered_to',
        'retirer_id_number',
        'retirer_phone',
        'delivery_type',
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }
}
