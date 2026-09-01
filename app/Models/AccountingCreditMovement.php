<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingCreditMovement extends Model
{
    public const TYPE_OVERPAYMENT = 'overpayment';

    public const TYPE_CREDIT_NOTE = 'credit_note';

    public const TYPE_APPLIED = 'applied';

    public const TYPE_VOID_REVERSAL = 'void_reversal';

    protected $fillable = [
        'agency_id',
        'amount_usd',
        'type',
        'payment_id',
        'credit_note_id',
        'accounting_invoice_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(AccountingPayment::class, 'payment_id');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(AccountingCreditNote::class, 'credit_note_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AccountingInvoice::class, 'accounting_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_OVERPAYMENT => 'Pago extra',
            self::TYPE_CREDIT_NOTE => 'Nota de crédito',
            self::TYPE_APPLIED => 'Aplicado a factura',
            self::TYPE_VOID_REVERSAL => 'Reverso por anulación',
            default => (string) $this->type,
        };
    }
}
