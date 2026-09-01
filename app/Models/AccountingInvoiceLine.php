<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingInvoiceLine extends Model
{
    protected $fillable = [
        'accounting_invoice_id',
        'preregistration_id',
        'service_type',
        'description',
        'quantity_lbs',
        'rate_per_lb',
        'amount_usd',
        'sort_order',
    ];

    protected $casts = [
        'quantity_lbs' => 'decimal:3',
        'rate_per_lb' => 'decimal:4',
        'amount_usd' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AccountingInvoice::class, 'accounting_invoice_id');
    }

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }
}
