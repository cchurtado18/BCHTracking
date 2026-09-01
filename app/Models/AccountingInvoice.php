<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingInvoice extends Model
{
    protected $fillable = [
        'folio',
        'delivery_note_id',
        'agency_id',
        'status',
        'issued_at',
        'total_lbs',
        'total_usd',
        'total_cor',
        'exchange_rate',
        'amount_paid',
        'created_by',
        'notes',
        'emailed_at',
        'void_reason',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'emailed_at' => 'datetime',
        'voided_at' => 'datetime',
        'total_lbs' => 'decimal:3',
        'total_usd' => 'decimal:2',
        'total_cor' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_paid' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccountingInvoiceLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(AccountingPaymentAllocation::class, 'accounting_invoice_id');
    }

    /**
     * Recalcula amount_paid y estado a partir de cobros activos y crédito aplicado.
     */
    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->paymentAllocations()
            ->whereHas('payment', fn ($q) => $q->where('status', 'active'))
            ->sum('amount_usd');

        $credit = app(\App\Services\Accounting\ClientCreditService::class)->creditAppliedToInvoice($this);
        $paid = round($paid + $credit, 2);

        $this->amount_paid = $paid;

        if (! $this->isVoid()) {
            if ($paid <= 0) {
                $this->status = 'issued';
            } elseif ($paid + 0.005 >= (float) $this->total_usd) {
                $this->status = 'paid';
            } else {
                $this->status = 'partially_paid';
            }
        }

        $this->save();
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

    public function canVoid(): bool
    {
        if ($this->isVoid()) {
            return false;
        }

        $cashPaid = (float) $this->paymentAllocations()
            ->whereHas('payment', fn ($q) => $q->where('status', 'active'))
            ->sum('amount_usd');

        return $cashPaid <= 0.005;
    }

    public function canDelete(): bool
    {
        if (! ($this->isVoid() || $this->status === 'draft')) {
            return false;
        }

        return ! $this->paymentAllocations()->exists();
    }

    public function balanceUsd(): float
    {
        return max(0, round((float) $this->total_usd - (float) $this->amount_paid, 2));
    }

    /**
     * Fecha de vencimiento: emisión + días de crédito del cliente (30 si no hay plazo).
     */
    public function dueAt(): ?\Carbon\CarbonInterface
    {
        if (! $this->issued_at) {
            return null;
        }

        $days = (int) ($this->agency?->credit_days ?? 30);

        return $this->issued_at->copy()->addDays(max(0, $days));
    }

    public function daysOverdue(): int
    {
        if ($this->balanceUsd() <= 0) {
            return 0;
        }

        $due = $this->dueAt();
        if (! $due) {
            return 0;
        }

        $today = now()->startOfDay();
        $dueDay = $due->copy()->startOfDay();
        if ($today->lte($dueDay)) {
            return 0;
        }

        return (int) $dueDay->diffInDays($today);
    }

    public function arStatus(): string
    {
        if ($this->status === 'paid' || $this->balanceUsd() <= 0) {
            return 'paid';
        }

        return $this->daysOverdue() > 0 ? 'overdue' : 'current';
    }

    public function arStatusLabel(): string
    {
        return match ($this->arStatus()) {
            'paid' => 'Pagada',
            'overdue' => 'En mora',
            default => 'Al Día',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'BORRADOR',
            'issued' => 'PENDIENTE',
            'partially_paid' => 'PARCIAL',
            'paid' => 'PAGADO',
            'void' => 'ANULADA',
            default => strtoupper((string) $this->status),
        };
    }

    public static function generateFolio(): string
    {
        $prefix = (string) (AccountingSetting::current()->folio_prefix ?: config('accounting.folio_prefix', 'FP-'));
        $last = static::where('folio', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('folio');

        $seq = 1;
        if ($last && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
