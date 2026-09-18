<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryNote extends Model
{
    protected $fillable = ['code', 'agency_id'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Primera entrega registrada (la más antigua por delivered_at).
     * Útil para listados que muestran "Retirado por" / "Fecha" de la nota
     * sin caer en el bug de eager-load con limit(1) global.
     */
    public function firstDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->ofMany('delivered_at', 'min');
    }

    /**
     * Factura PrimeTrack activa (no anulada) vinculada a esta hoja de salida.
     */
    public function accountingInvoice(): HasOne
    {
        return $this->hasOne(AccountingInvoice::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', '!=', 'void')
        );
    }

    public function accountingInvoices(): HasMany
    {
        return $this->hasMany(AccountingInvoice::class);
    }

    public function linkedInvoices(): BelongsToMany
    {
        return $this->belongsToMany(AccountingInvoice::class, 'accounting_invoice_delivery_notes')
            ->withTimestamps();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutActiveInvoice(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('accountingInvoices', fn ($q) => $q->where('status', '!=', 'void'))
            ->whereDoesntHave('linkedInvoices', fn ($q) => $q->where('status', '!=', 'void'));
    }

    /**
     * Cuentas comerciales (bill-to) de los paquetes de esta hoja.
     *
     * @return \Illuminate\Support\Collection<int, Agency>
     */
    public function packageBillToAgencies()
    {
        $this->loadMissing(['deliveries.preregistration.agency.parent.parent.parent']);
        $sloClientsByName = Agency::sloDirectClientsKeyedByName();

        $resolved = $this->deliveries
            ->map(function ($delivery) use ($sloClientsByName) {
                $package = $delivery->preregistration;
                if (! $package) {
                    return null;
                }

                $billTo = $package->billToAgency($sloClientsByName);
                if (! $billTo) {
                    return null;
                }

                return [
                    'billTo' => $billTo,
                    'label' => Agency::normalizePersonNameForMatch($package->label_name),
                ];
            })
            ->filter();

        $billTos = $resolved
            ->map(fn (array $row) => $row['billTo'])
            ->unique(fn (Agency $agency) => (int) $agency->id)
            ->values();

        if ($billTos->count() <= 1) {
            return $billTos;
        }

        $nonRoot = $billTos->reject(fn (Agency $agency) => $agency->isRootAccount())->values();
        $labels = $resolved->pluck('label')->filter()->unique()->values();

        if ($labels->count() === 1) {
            $match = $sloClientsByName[$labels->first()] ?? null;
            if ($match instanceof Agency) {
                return collect([$match])->values();
            }
        }

        if ($nonRoot->count() === 1 && ($labels->count() <= 1 || $this->sloLabelsBelongToClient($resolved, $nonRoot->first()))) {
            return collect([$nonRoot->first()])->values();
        }

        if ($labels->count() === 1 && $nonRoot->count() <= 1) {
            $match = $nonRoot->first();
            if ($match instanceof Agency) {
                return collect([$match])->values();
            }
        }

        return $billTos;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{billTo: Agency, label: string}>  $resolved
     */
    private function sloLabelsBelongToClient($resolved, Agency $client): bool
    {
        $clientKey = Agency::normalizePersonNameForMatch($client->name);
        $clientLabels = $resolved
            ->filter(fn (array $row) => (int) $row['billTo']->id === (int) $client->id)
            ->pluck('label')
            ->filter();

        return $resolved
            ->filter(fn (array $row) => $row['billTo']->isRootAccount())
            ->pluck('label')
            ->filter()
            ->every(fn (string $label) => $label === $clientKey || $clientLabels->contains($label));
    }

    public function hasMixedBillTos(): bool
    {
        return $this->packageBillToAgencies()->count() > 1;
    }

    /**
     * Hojas con paquetes de más de una cuenta (Magali + Brenda en la misma SLO, etc.).
     */
    public function scopeWithMultiplePackageAgencies(Builder $query): Builder
    {
        return $query->whereRaw('(
            SELECT COUNT(DISTINCT preregistrations.agency_id)
            FROM deliveries
            INNER JOIN preregistrations ON preregistrations.id = deliveries.preregistration_id
            WHERE deliveries.delivery_note_id = delivery_notes.id
        ) > 1');
    }

    /**
     * Cliente a facturar: si todos los paquetes caen en una cuenta comercial, esa;
     * si hay varias, la agencia de la hoja (salida de red, p. ej. SkyLink One).
     */
    public function billingAgency(): ?Agency
    {
        $billTos = $this->packageBillToAgencies();
        if ($billTos->count() === 1) {
            return $billTos->first();
        }

        $this->loadMissing(['agency.parent.parent.parent']);
        if (! $this->agency) {
            return null;
        }

        return $this->agency->commercialBillTo();
    }

    /**
     * @return list<int>
     */
    public function invoiceFamilyIds(): array
    {
        $billTos = $this->packageBillToAgencies();
        if ($billTos->count() > 1) {
            return [-(int) $this->id];
        }

        $agency = $billTos->first() ?? $this->billingAgency();
        if (! $agency) {
            return [];
        }

        $ids = $agency->invoiceFamilyIds();
        sort($ids);

        return $ids;
    }

    public function invoiceFamilyKey(): string
    {
        return $this->invoiceGroupKey();
    }

    /**
     * Clave para juntar hojas en una misma factura: el cliente canónico, no el id crudo de agencia.
     */
    public function invoiceGroupKey(): string
    {
        if ($this->hasMixedBillTos()) {
            return 'mixed:'.$this->id;
        }

        $billTo = $this->billingAgency();
        if (! $billTo) {
            return 'none:'.$this->id;
        }

        $canonical = $billTo->canonicalInvoiceBillTo();
        if ($canonical->isDirectClient() || $canonical->isRootAccount()) {
            return 'billto:'.(int) $canonical->id;
        }

        return 'family:'.(int) $canonical->invoiceFamilyRoot()->id;
    }

    public function currentInvoice(): ?AccountingInvoice
    {
        if ($this->accountingInvoice) {
            return $this->accountingInvoice;
        }

        $linked = $this->relationLoaded('linkedInvoices')
            ? $this->linkedInvoices
            : $this->linkedInvoices()->where('status', '!=', 'void')->orderByDesc('accounting_invoices.id')->get();

        return $linked->first(fn (AccountingInvoice $invoice) => $invoice->status !== 'void');
    }

    /**
     * Genera el siguiente código único para una nota de entrega: SLO-0001, SLO-0002, ...
     * También considera códigos históricos BCH- para no reiniciar la secuencia.
     */
    public static function generateCode(): string
    {
        $prefix = 'SLO-';
        $codes = static::query()
            ->where(function ($query) {
                $query->where('code', 'like', 'SLO-%')
                    ->orWhere('code', 'like', 'BCH-%');
            })
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^(?:SLO|BCH)-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $prefix.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
