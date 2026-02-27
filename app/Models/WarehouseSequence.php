<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseSequence extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'next_number'];

    protected $casts = [
        'next_number' => 'integer',
    ];
}
