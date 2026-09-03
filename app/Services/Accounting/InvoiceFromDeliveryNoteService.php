<?php

namespace App\Services\Accounting;

use App\Models\AccountingInvoice;
use App\Models\AccountingInvoiceLine;
use App\Models\AccountingRateCard;
use App\Models\Agency;
use App\Models\DeliveryNote;
use App\Models\User;
use App\Support\ServiceType;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceFromDeliveryNoteService
{
    /**
     * Preview de líneas agrupadas por servicio (AIR/SEA/CFT) para una hoja de salida.
     *
     * @param  array<string, float|null>  $rateOverrides  ej. ['AIR' => 3.5, 'SEA' => 1.2, 'CFT' => 8]
     * @return array{agency_id: int, lines: list<array<string, mixed>>, total_lbs: float, total_cft: float, total_usd: float, delivery_fee_usd: float, freight_usd: float}
     */
    public function preview(DeliveryNote $note, array $rateOverrides = [], float $deliveryFee = 0): array
    {
        return $this->previewNotes(collect([$note]), $rateOverrides, $deliveryFee);
    }

    /**
     * @param  Collection<int, DeliveryNote>|iterable<DeliveryNote>  $notes
     * @param  array<string, float|null>  $rateOverrides
     * @return array{agency_id: int, lines: list<array<string, mixed>>, total_lbs: float, total_cft: float, total_usd: float, delivery_fee_usd: float, freight_usd: float}
     */
    public function previewNotes(iterable $notes, array $rateOverrides = [], float $deliveryFee = 0): array
    {
        $notes = $this->normalizeNotes($notes);
        $this->assertSameInvoiceFamily($notes);

        $byService = [];
        foreach ($notes as $note) {
            $note->loadMissing(['deliveries.preregistration', 'agency']);
            if ($note->deliveries->isEmpty()) {
                throw new InvalidArgumentException('La hoja '.$note->code.' no tiene paquetes.');
            }

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
        }

        if ($byService === []) {
            throw new InvalidArgumentException('No hay paquetes válidos para facturar.');
        }

        $agencyId = $this->resolveBillToAgencyId($notes);
        $familyIds = $this->familyIdsForAgency($agencyId);
        $packageAgencyIds = $notes
            ->flatMap(fn (DeliveryNote $note) => $note->deliveries->map(fn ($d) => $d->preregistration?->agency_id))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $outside = $packageAgencyIds->first(fn (int $id) => ! in_array($id, $familyIds, true));
        if ($outside !== null) {
            throw new InvalidArgumentException('Hay paquetes de otra red de agencia. No se pueden facturar juntos.');
        }

        $lines = [];
        $totalLbs = 0.0;
        $totalCft = 0.0;
        $freightUsd = 0.0;
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
            $freightUsd += $amount;
        }

        $deliveryFee = max(0, round($deliveryFee, 2));
        if ($deliveryFee > 0) {
            $lines[] = [
                'service_type' => ServiceType::DELIVERY,
                'description' => ServiceType::freightDescription(ServiceType::DELIVERY),
                'quantity_lbs' => 1,
                'rate_per_lb' => $deliveryFee,
                'amount_usd' => $deliveryFee,
                'package_count' => 0,
                'unit' => '',
                'sort_order' => $sort++,
            ];
        }

        return [
            'agency_id' => $agencyId,
            'lines' => $lines,
            'total_lbs' => round($totalLbs, 3),
            'total_cft' => round($totalCft, 4),
            'freight_usd' => round($freightUsd, 2),
            'delivery_fee_usd' => $deliveryFee,
            'total_usd' => round($freightUsd + $deliveryFee, 2),
        ];
    }

    /**
     * @param  array<string, float|null>  $rateOverrides
     * @param  iterable<DeliveryNote>  $additionalNotes
     */
    public function create(
        DeliveryNote $note,
        User $user,
        array $rateOverrides = [],
        ?float $exchangeRate = null,
        float $deliveryFee = 0,
        iterable $additionalNotes = [],
    ): AccountingInvoice {
        return DB::transaction(function () use ($note, $user, $rateOverrides, $exchangeRate, $deliveryFee, $additionalNotes) {
            $ids = collect([$note->id])
                ->merge(collect($additionalNotes)->map(fn (DeliveryNote $n) => $n->id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $locked = DeliveryNote::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->with(['deliveries.preregistration', 'agency.parent.parent.parent'])
                ->get()
                ->sortBy(fn (DeliveryNote $n) => array_search((int) $n->id, $ids, true))
                ->values();

            if ($locked->count() !== count($ids)) {
                throw new InvalidArgumentException('Una o más hojas de salida no existen.');
            }

            foreach ($locked as $lockedNote) {
                $existing = AccountingInvoice::query()
                    ->coveringNote((int) $lockedNote->id)
                    ->where('status', '!=', 'void')
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    throw new InvalidArgumentException('Ya existe una Factura PrimeTrack activa para la hoja '.$lockedNote->code.' ('.$existing->folio.').');
                }
            }

            $preview = $this->previewNotes($locked, $rateOverrides, $deliveryFee);
            if ($preview['total_usd'] <= 0) {
                throw new InvalidArgumentException('No se puede emitir una factura en $0. Defina tarifas para esta cuenta, indique el precio del servicio o un cargo de delivery.');
            }

            $rate = $exchangeRate ?? (float) \App\Models\AccountingSetting::current()->exchange_rate;
            if ($rate <= 0) {
                $rate = 1.0;
            }

            $primary = $locked->first();
            $invoice = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $invoice = AccountingInvoice::create([
                        'folio' => AccountingInvoice::generateFolio(),
                        'delivery_note_id' => $primary->id,
                        'agency_id' => $preview['agency_id'],
                        'status' => 'issued',
                        'issued_at' => now()->toDateString(),
                        'total_lbs' => $preview['total_lbs'],
                        'total_usd' => $preview['total_usd'],
                        'delivery_fee_usd' => $preview['delivery_fee_usd'],
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

            $invoice->deliveryNotes()->sync($locked->pluck('id')->all());

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

            return $invoice->load(['lines', 'agency', 'deliveryNote', 'deliveryNotes', 'createdBy']);
        });
    }

    /**
     * @param  iterable<DeliveryNote>  $notes
     * @return Collection<int, DeliveryNote>
     */
    private function normalizeNotes(iterable $notes): Collection
    {
        $collection = collect($notes)->filter()->unique(fn (DeliveryNote $n) => $n->id)->values();
        if ($collection->isEmpty()) {
            throw new InvalidArgumentException('Seleccione al menos una hoja de salida.');
        }

        return $collection;
    }

    /**
     * @param  Collection<int, DeliveryNote>  $notes
     */
    private function assertSameInvoiceFamily(Collection $notes): void
    {
        $family = null;
        foreach ($notes as $note) {
            $ids = $this->noteInvoiceFamilyIds($note);
            if ($family === null) {
                $family = $ids;

                continue;
            }
            if ($family !== $ids) {
                throw new InvalidArgumentException('Solo se pueden facturar juntas hojas de la misma agencia o de sus subagencias.');
            }
        }
    }

    /**
     * @return list<int>
     */
    /**
     * @return list<int>
     */
    private function noteInvoiceFamilyIds(DeliveryNote $note): array
    {
        $ids = $note->invoiceFamilyIds();
        if ($ids === []) {
            throw new InvalidArgumentException('La hoja '.$note->code.' no tiene agencia asignada.');
        }

        return $ids;
    }

    /**
     * @param  Collection<int, DeliveryNote>  $notes
     */
    private function resolveBillToAgencyId(Collection $notes): int
    {
        $packageAgencies = $notes
            ->flatMap(fn (DeliveryNote $note) => $note->deliveries->map(fn ($d) => $d->preregistration?->agency_id))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($packageAgencies->count() === 1) {
            return (int) $packageAgencies->first();
        }

        $billTo = $notes->first()?->billingAgency();
        if ($billTo && $packageAgencies->isEmpty()) {
            return (int) $billTo->id;
        }

        if ($billTo) {
            $family = $billTo->invoiceFamilyIds();
            $outside = $packageAgencies->first(fn (int $id) => ! in_array($id, $family, true));
            if ($outside === null) {
                return (int) $billTo->id;
            }
        }

        throw new InvalidArgumentException('No se pudo determinar el cliente a facturar. Las hojas deben ser de la misma cuenta.');
    }

    /**
     * @return list<int>
     */
    private function familyIdsForAgency(int $agencyId): array
    {
        $agency = Agency::query()->with('parent.parent.parent')->find($agencyId);

        return $agency ? $agency->invoiceFamilyIds() : [$agencyId];
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
