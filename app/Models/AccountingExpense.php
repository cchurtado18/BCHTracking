<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingExpense extends Model
{
    protected $fillable = [
        'category_id',
        'agency_id',
        'service_type',
        'amount_usd',
        'spent_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'spent_at' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingExpenseCategory::class, 'category_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
