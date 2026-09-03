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
     * Cliente a facturar: si todos los paquetes son de una cuenta, esa (o su padre comercial
     * si es subagencia anidada); si no, la agencia de la hoja.
     */
    public function billingAgency(): ?Agency
    {
        $this->loadMissing(['deliveries.preregistration', 'agency.parent.parent.parent']);
        $packageIds = $this->deliveries
            ->map(fn ($d) => $d->preregistration?->agency_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $agencyId = $packageIds->count() === 1
            ? (int) $packageIds->first()
            : (int) $this->agency_id;

        if (! $agencyId) {
            return null;
        }

        $agency = $this->agency && (int) $this->agency->id === $agencyId
            ? $this->agency
            : Agency::query()->with('parent.parent.parent')->find($agencyId);

        if (! $agency) {
            return null;
        }

        $agency->loadMissing('parent.parent.parent');

        return $agency->commercialBillTo();
    }

    /**
     * @return list<int>
     */
    public function invoiceFamilyIds(): array
    {
        $agency = $this->billingAgency();
        if (! $agency) {
            return [];
        }

        $ids = $agency->invoiceFamilyIds();
        sort($ids);

        return $ids;
    }

    public function invoiceFamilyKey(): string
    {
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
