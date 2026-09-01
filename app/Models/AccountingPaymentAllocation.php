<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPaymentAllocation extends Model
{
    protected $fillable = ['payment_id', 'accounting_invoice_id', 'amount_usd'];

    protected $casts = [
        'amount_usd' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AccountingPayment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AccountingInvoice::class, 'accounting_invoice_id');
    }
}
