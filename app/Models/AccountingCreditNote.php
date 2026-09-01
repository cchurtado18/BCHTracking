<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCreditNote extends Model
{
    protected $fillable = [
        'folio',
        'agency_id',
        'amount_usd',
        'reason',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AccountingCreditMovement::class, 'credit_note_id');
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function appliedUsd(): float
    {
        $applied = abs((float) $this->movements
            ->where('type', AccountingCreditMovement::TYPE_APPLIED)
            ->sum('amount_usd'));

        $reversed = (float) $this->movements
            ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL)
            ->filter(fn ($m) => $m->accounting_invoice_id)
            ->sum('amount_usd');

        return round(max(0, $applied - max(0, $reversed)), 2);
    }

    public function remainingUsd(): float
    {
        if ($this->isVoid()) {
            return 0.0;
        }

        return round(max(0, (float) $this->amount_usd - $this->appliedUsd()), 2);
    }

    public function usageStatus(): string
    {
        if ($this->isVoid()) {
            return 'void';
        }
        if ($this->remainingUsd() <= 0.005) {
            return 'applied';
        }
        if ($this->appliedUsd() > 0.005) {
            return 'partial';
        }

        return 'available';
    }

    public function usageStatusLabel(): string
    {
        return match ($this->usageStatus()) {
            'void' => 'Anulada',
            'applied' => 'Aplicada',
            'partial' => 'Parcial',
            default => 'Disponible',
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function applicationRows()
    {
        $reversals = $this->movements
            ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL)
            ->filter(fn ($m) => $m->accounting_invoice_id)
            ->groupBy(fn ($m) => (int) $m->accounting_invoice_id)
            ->map(fn ($group) => round((float) $group->sum('amount_usd'), 2));

        return $this->movements
            ->where('type', AccountingCreditMovement::TYPE_APPLIED)
            ->groupBy(fn ($m) => (int) $m->accounting_invoice_id)
            ->map(function ($group, $invoiceId) use ($reversals) {
                $applied = round((float) $group->sum(fn ($m) => abs((float) $m->amount_usd)), 2);
                $reversed = (float) ($reversals[$invoiceId] ?? 0);
                $net = round(max(0, $applied - max(0, $reversed)), 2);
                $first = $group->sortBy('id')->first();

                return (object) [
                    'invoice' => $first?->invoice,
                    'applied_usd' => $applied,
                    'reversed_usd' => round(max(0, $reversed), 2),
                    'net_usd' => $net,
                    'at' => $first?->created_at,
                    'is_reversed' => $net <= 0.005,
                ];
            })
            ->values();
    }

    public static function generateFolio(): string
    {
        $prefix = 'NC-';
        $last = static::query()
            ->where('folio', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('folio');

        $seq = 1;
        if ($last && preg_match('/^NC-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
