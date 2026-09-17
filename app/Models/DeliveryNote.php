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

        return $this->deliveries
            ->map(fn ($d) => $d->preregistration?->agency)
            ->filter()
            ->map(fn (Agency $agency) => $agency->commercialBillTo())
            ->unique(fn (Agency $agency) => (int) $agency->id)
            ->values();
    }

    public function hasMixedBillTos(): bool
    {
        return $this->packageBillToAgencies()->count() > 1;
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
        if ($this->hasMixedBillTos()) {
            return 'mixed:'.$this->id;
        }

        return implode(',', $this->invoiceFamilyIds());
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
