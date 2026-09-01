<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPayment extends Model
{
    protected $fillable = [
        'agency_id',
        'amount_usd',
        'paid_at',
        'method',
        'deposit_account',
        'reference',
        'notes',
        'status',
        'void_reason',
        'voided_at',
        'voided_by',
        'created_by',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'paid_at' => 'date',
        'voided_at' => 'datetime',
    ];

    public const METHODS = [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'check' => 'Cheque',
        'other' => 'Otro',
    ];

    public const ACCOUNTS = [
        'cash_general' => '1.1.01 Caja General',
        'bank_bac' => '1.1.02 Banco BAC',
        'bank_lafise' => '1.1.04 Banco Lafise',
    ];

    public static function defaultAccountForMethod(string $method): string
    {
        return match ($method) {
            'cash', 'other' => 'cash_general',
            'card' => 'bank_lafise',
            default => 'bank_bac',
        };
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AccountingPaymentAllocation::class, 'payment_id');
    }

    public function creditMovements(): HasMany
    {
        return $this->hasMany(AccountingCreditMovement::class, 'payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? (string) $this->method;
    }

    public function accountLabel(): string
    {
        $key = (string) ($this->deposit_account ?: self::defaultAccountForMethod((string) $this->method));

        return self::ACCOUNTS[$key] ?? self::ACCOUNTS['cash_general'];
    }
}
