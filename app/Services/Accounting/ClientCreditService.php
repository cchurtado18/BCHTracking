<?php

namespace App\Services\Accounting;

use App\Models\AccountingCreditMovement;
use App\Models\AccountingCreditNote;
use App\Models\AccountingInvoice;
use App\Models\AccountingPayment;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClientCreditService
{
    public function balance(Agency $agency): float
    {
        return round((float) $agency->credit_balance_usd, 2);
    }

    public function recalculate(Agency $agency): float
    {
        $sum = round((float) AccountingCreditMovement::query()
            ->where('agency_id', $agency->id)
            ->sum('amount_usd'), 2);

        $agency->credit_balance_usd = $sum;
        $agency->save();

        return $sum;
    }

    /**
     * @param  array{payment_id?: int|null, credit_note_id?: int|null, accounting_invoice_id?: int|null, notes?: string|null, created_by?: int|null}  $meta
     */
    public function add(Agency $agency, float $amount, string $type, array $meta = []): AccountingCreditMovement
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('El monto de crédito debe ser mayor que cero.');
        }

        $movement = AccountingCreditMovement::create([
            'agency_id' => $agency->id,
            'amount_usd' => $amount,
            'type' => $type,
            'payment_id' => $meta['payment_id'] ?? null,
            'credit_note_id' => $meta['credit_note_id'] ?? null,
            'accounting_invoice_id' => $meta['accounting_invoice_id'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'created_by' => $meta['created_by'] ?? null,
        ]);

        $this->recalculate($agency->fresh() ?? $agency);

        return $movement;
    }

    public function applyToInvoice(Agency $agency, AccountingInvoice $invoice, float $amount, ?int $userId = null): ?AccountingCreditMovement
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($agency, $invoice, $amount, $userId) {
            $agency = Agency::query()->whereKey($agency->id)->lockForUpdate()->firstOrFail();
            $invoice = AccountingInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ((int) $invoice->agency_id !== (int) $agency->id) {
                throw new InvalidArgumentException('La factura no pertenece a este cliente.');
            }

            $this->recalculate($agency);
            $apply = min($amount, $this->balance($agency), $invoice->balanceUsd());
            if ($apply <= 0) {
                return null;
            }

            $left = $apply;
            $last = null;

            foreach ($this->availableCreditBuckets($agency) as $bucket) {
                if ($left <= 0.005) {
                    break;
                }

                $take = round(min($left, $bucket['remaining']), 2);
                if ($take <= 0) {
                    continue;
                }

                $last = AccountingCreditMovement::create([
                    'agency_id' => $agency->id,
                    'amount_usd' => -1 * $take,
                    'type' => AccountingCreditMovement::TYPE_APPLIED,
                    'accounting_invoice_id' => $invoice->id,
                    'credit_note_id' => $bucket['credit_note_id'],
                    'payment_id' => $bucket['payment_id'],
                    'notes' => $bucket['credit_note_id']
                        ? 'Aplicación de nota de crédito'
                        : 'Aplicación de excedente de cobro',
                    'created_by' => $userId,
                ]);

                $left = round($left - $take, 2);
            }

            if ($left > 0.005) {
                $last = AccountingCreditMovement::create([
                    'agency_id' => $agency->id,
                    'amount_usd' => -1 * $left,
                    'type' => AccountingCreditMovement::TYPE_APPLIED,
                    'accounting_invoice_id' => $invoice->id,
                    'created_by' => $userId,
                ]);
            }

            $this->recalculate($agency->fresh() ?? $agency);
            $invoice->refreshPaymentStatus();

            return $last;
        });
    }

    public function reverseAppliedToInvoice(AccountingInvoice $invoice): void
    {
        $applied = AccountingCreditMovement::query()
            ->where('accounting_invoice_id', $invoice->id)
            ->where('type', AccountingCreditMovement::TYPE_APPLIED)
            ->get();

        if ($applied->isEmpty()) {
            return;
        }

        $groups = $applied->groupBy(fn ($m) => (string) ($m->credit_note_id ?? 'n').':'.(string) ($m->payment_id ?? 'p'));

        foreach ($groups as $group) {
            $first = $group->first();
            $appliedAmt = round((float) $group->sum(fn ($m) => abs((float) $m->amount_usd)), 2);
            $alreadyQuery = AccountingCreditMovement::query()
                ->where('accounting_invoice_id', $invoice->id)
                ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL);

            if ($first->credit_note_id) {
                $alreadyQuery->where('credit_note_id', $first->credit_note_id);
            } else {
                $alreadyQuery->whereNull('credit_note_id');
            }

            if ($first->payment_id) {
                $alreadyQuery->where('payment_id', $first->payment_id);
            } else {
                $alreadyQuery->whereNull('payment_id');
            }

            $net = round($appliedAmt - max(0, (float) $alreadyQuery->sum('amount_usd')), 2);
            if ($net <= 0) {
                continue;
            }

            AccountingCreditMovement::create([
                'agency_id' => $invoice->agency_id,
                'amount_usd' => $net,
                'type' => AccountingCreditMovement::TYPE_VOID_REVERSAL,
                'accounting_invoice_id' => $invoice->id,
                'credit_note_id' => $first->credit_note_id,
                'payment_id' => $first->payment_id,
                'notes' => 'Reverso de crédito aplicado a '.$invoice->folio,
            ]);
        }

        $agency = Agency::find($invoice->agency_id);
        if ($agency) {
            $this->recalculate($agency);
        }
    }

    public function reverseOverpaymentForPayment(AccountingPayment $payment): void
    {
        $overpay = round((float) AccountingCreditMovement::query()
            ->where('payment_id', $payment->id)
            ->where('type', AccountingCreditMovement::TYPE_OVERPAYMENT)
            ->sum('amount_usd'), 2);

        if ($overpay <= 0) {
            return;
        }

        $agency = $payment->agency ?? Agency::find($payment->agency_id);
        if (! $agency) {
            return;
        }

        if ($this->overpaymentRemaining($overpay, $payment->id) + 0.005 < $overpay) {
            throw new InvalidArgumentException('No se puede anular: el crédito generado por este cobro ya se aplicó a otra factura.');
        }

        AccountingCreditMovement::create([
            'agency_id' => $agency->id,
            'amount_usd' => -1 * $overpay,
            'type' => AccountingCreditMovement::TYPE_VOID_REVERSAL,
            'payment_id' => $payment->id,
            'notes' => 'Reverso de excedente del cobro #'.$payment->id,
        ]);

        $this->recalculate($agency);
    }

    public function reverseCreditNote(AccountingCreditNote $note): void
    {
        $credited = round((float) AccountingCreditMovement::query()
            ->where('credit_note_id', $note->id)
            ->where('type', AccountingCreditMovement::TYPE_CREDIT_NOTE)
            ->sum('amount_usd'), 2);

        if ($credited <= 0) {
            return;
        }

        $agency = $note->agency ?? Agency::find($note->agency_id);
        if (! $agency) {
            return;
        }

        $note->loadMissing('movements');
        if ($note->appliedUsd() > 0.005) {
            throw new InvalidArgumentException('No se puede anular: el crédito de esta nota ya se aplicó a una factura.');
        }

        AccountingCreditMovement::create([
            'agency_id' => $agency->id,
            'amount_usd' => -1 * $credited,
            'type' => AccountingCreditMovement::TYPE_VOID_REVERSAL,
            'credit_note_id' => $note->id,
            'notes' => 'Reverso de '.$note->folio,
        ]);

        $this->recalculate($agency);
    }

    public function creditAppliedToInvoice(AccountingInvoice $invoice): float
    {
        $applied = (float) AccountingCreditMovement::query()
            ->where('accounting_invoice_id', $invoice->id)
            ->where('type', AccountingCreditMovement::TYPE_APPLIED)
            ->sum('amount_usd');

        $reversed = (float) AccountingCreditMovement::query()
            ->where('accounting_invoice_id', $invoice->id)
            ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL)
            ->sum('amount_usd');

        return round(abs($applied) - max(0, $reversed), 2);
    }

    /**
     * @return list<array{credit_note_id: int|null, payment_id: int|null, remaining: float}>
     */
    private function availableCreditBuckets(Agency $agency): array
    {
        $buckets = [];

        $notes = AccountingCreditNote::query()
            ->where('agency_id', $agency->id)
            ->where('status', 'active')
            ->with('movements')
            ->orderBy('id')
            ->get();

        foreach ($notes as $note) {
            $remaining = $note->remainingUsd();
            if ($remaining <= 0.005) {
                continue;
            }

            $buckets[] = [
                'sort' => $note->created_at?->getTimestamp() ?? 0,
                'seq' => $note->id,
                'credit_note_id' => $note->id,
                'payment_id' => null,
                'remaining' => $remaining,
            ];
        }

        $overpays = AccountingCreditMovement::query()
            ->where('agency_id', $agency->id)
            ->where('type', AccountingCreditMovement::TYPE_OVERPAYMENT)
            ->orderBy('id')
            ->get();

        foreach ($overpays as $overpay) {
            $remaining = $this->overpaymentRemaining((float) $overpay->amount_usd, $overpay->payment_id);
            if ($remaining <= 0.005) {
                continue;
            }

            $buckets[] = [
                'sort' => $overpay->created_at?->getTimestamp() ?? 0,
                'seq' => $overpay->id,
                'credit_note_id' => null,
                'payment_id' => $overpay->payment_id ? (int) $overpay->payment_id : null,
                'remaining' => $remaining,
            ];
        }

        usort($buckets, function ($a, $b) {
            return $a['sort'] <=> $b['sort'] ?: $a['seq'] <=> $b['seq'];
        });

        return $buckets;
    }

    private function overpaymentRemaining(float $overpayAmount, ?int $paymentId): float
    {
        if (! $paymentId || $overpayAmount <= 0) {
            return 0.0;
        }

        $applied = abs((float) AccountingCreditMovement::query()
            ->where('payment_id', $paymentId)
            ->where('type', AccountingCreditMovement::TYPE_APPLIED)
            ->sum('amount_usd'));

        $applicationReversals = (float) AccountingCreditMovement::query()
            ->where('payment_id', $paymentId)
            ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL)
            ->whereNotNull('accounting_invoice_id')
            ->sum('amount_usd');

        $sourceReversals = (float) AccountingCreditMovement::query()
            ->where('payment_id', $paymentId)
            ->where('type', AccountingCreditMovement::TYPE_VOID_REVERSAL)
            ->whereNull('accounting_invoice_id')
            ->sum('amount_usd');

        return round(max(0, $overpayAmount + $sourceReversals - $applied + max(0, $applicationReversals)), 2);
    }
}
