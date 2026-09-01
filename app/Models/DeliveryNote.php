<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
