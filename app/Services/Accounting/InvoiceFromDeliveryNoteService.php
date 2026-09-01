<?php

namespace App\Services\Accounting;

use App\Models\AccountingInvoice;
use App\Models\AccountingInvoiceLine;
use App\Models\AccountingRateCard;
use App\Models\DeliveryNote;
use App\Models\User;
use App\Support\ServiceType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceFromDeliveryNoteService
{
    /**
     * Preview de líneas agrupadas por servicio (AIR/SEA/CFT) para una hoja de salida.
     *
     * @param  array<string, float|null>  $rateOverrides  ej. ['AIR' => 3.5, 'SEA' => 1.2, 'CFT' => 8]
     * @return array{agency_id: int, lines: list<array<string, mixed>>, total_lbs: float, total_cft: float, total_usd: float}
     */
    public function preview(DeliveryNote $note, array $rateOverrides = []): array
    {
        $note->loadMissing(['deliveries.preregistration', 'agency']);

        if ($note->deliveries->isEmpty()) {
            throw new InvalidArgumentException('La hoja de salida no tiene paquetes.');
        }

        $agencyIds = $note->deliveries
            ->map(fn ($d) => $d->preregistration?->agency_id)
            ->filter()
            ->unique()
            ->values();
        if ($agencyIds->count() > 1) {
            throw new InvalidArgumentException('La hoja tiene paquetes de más de un cliente. No se puede facturar hasta unificar la cuenta.');
        }

        $agencyId = (int) ($note->agency_id ?? $agencyIds->first());
        if (! $agencyId) {
            throw new InvalidArgumentException('No se pudo determinar la agencia a facturar.');
        }

        $byService = [];
        foreach ($note->deliveries as $delivery) {
            $p = $delivery->preregistration;
            if (! $p) {
                continue;
            }
            $service = ServiceType::normalize($p->service_type);
            $qty = ServiceType::billedQuantity($p);
            if (! isset($byService[$service])) {
                $byService[$service] = ['qty' => 0.0, 'count' => 0];
            }
            $byService[$service]['qty'] += $qty;
            $byService[$service]['count']++;
        }

        if ($byService === []) {
            throw new InvalidArgumentException('No hay paquetes válidos para facturar.');
        }

        $lines = [];
        $totalLbs = 0.0;
        $totalCft = 0.0;
        $totalUsd = 0.0;
        $sort = 0;

        foreach (ServiceType::ALL as $service) {
            if (! isset($byService[$service])) {
                continue;
            }
            $qty = round($byService[$service]['qty'], 4);
            $rate = $this->resolveRate($agencyId, $service, $rateOverrides);
            $amount = round($qty * $rate, 2);
            $lines[] = [
                'service_type' => $service,
                'description' => ServiceType::freightDescription($service),
                'quantity_lbs' => $qty,
                'rate_per_lb' => $rate,
                'amount_usd' => $amount,
                'package_count' => $byService[$service]['count'],
                'unit' => ServiceType::unit($service),
                'sort_order' => $sort++,
            ];
            if (ServiceType::isCft($service)) {
                $totalCft += $qty;
            } else {
                $totalLbs += $qty;
            }
            $totalUsd += $amount;
        }

        return [
            'agency_id' => $agencyId,
            'lines' => $lines,
            'total_lbs' => round($totalLbs, 3),
            'total_cft' => round($totalCft, 4),
            'total_usd' => round($totalUsd, 2),
        ];
    }

    /**
     * @param  array<string, float|null>  $rateOverrides
     */
    public function create(DeliveryNote $note, User $user, array $rateOverrides = [], ?float $exchangeRate = null): AccountingInvoice
    {
        return DB::transaction(function () use ($note, $user, $rateOverrides, $exchangeRate) {
            $locked = DeliveryNote::query()->lockForUpdate()->findOrFail($note->id);
            $locked->load(['deliveries.preregistration', 'agency']);

            $existing = AccountingInvoice::query()
                ->where('delivery_note_id', $locked->id)
                ->where('status', '!=', 'void')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('Ya existe una Factura PrimeTrack activa para esta hoja de salida ('.$existing->folio.').');
            }

            $preview = $this->preview($locked, $rateOverrides);
            if ($preview['total_usd'] <= 0) {
                throw new InvalidArgumentException('No se puede emitir una factura en $0. Defina tarifas para esta cuenta o indique el precio del servicio.');
            }

            $rate = $exchangeRate ?? (float) \App\Models\AccountingSetting::current()->exchange_rate;
            if ($rate <= 0) {
                $rate = 1.0;
            }

            $invoice = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $invoice = AccountingInvoice::create([
                        'folio' => AccountingInvoice::generateFolio(),
                        'delivery_note_id' => $locked->id,
                        'agency_id' => $preview['agency_id'],
                        'status' => 'issued',
                        'issued_at' => now()->toDateString(),
                        'total_lbs' => $preview['total_lbs'],
                        'total_usd' => $preview['total_usd'],
                        'total_cor' => round($preview['total_usd'] * $rate, 2),
                        'exchange_rate' => $rate,
                        'amount_paid' => 0,
                        'created_by' => $user->id,
                    ]);
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    if ($attempt === 4) {
                        throw $e;
                    }
                }
            }

            if (! $invoice) {
                throw new InvalidArgumentException('No se pudo generar el folio de la factura. Intente de nuevo.');
            }

            foreach ($preview['lines'] as $line) {
                AccountingInvoiceLine::create([
                    'accounting_invoice_id' => $invoice->id,
                    'preregistration_id' => null,
                    'service_type' => $line['service_type'],
                    'description' => $line['description'],
                    'quantity_lbs' => $line['quantity_lbs'],
                    'rate_per_lb' => $line['rate_per_lb'],
                    'amount_usd' => $line['amount_usd'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            return $invoice->load(['lines', 'agency', 'deliveryNote', 'createdBy']);
        });
    }

    /**
     * @param  array<string, float|null>  $rateOverrides
     */
    private function resolveRate(int $agencyId, string $service, array $rateOverrides): float
    {
        $key = strtoupper($service);
        if (array_key_exists($key, $rateOverrides) && $rateOverrides[$key] !== null && $rateOverrides[$key] !== '') {
            return round((float) $rateOverrides[$key], 4);
        }

        $card = AccountingRateCard::currentFor($agencyId, $key);

        return $card ? round((float) $card->price_per_lb, 4) : 0.0;
    }
}
