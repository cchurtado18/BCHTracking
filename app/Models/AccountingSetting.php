<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    protected $fillable = [
        'company_name',
        'company_tax_id',
        'company_address',
        'company_phones',
        'voucher_footer',
        'folio_prefix',
        'exchange_rate',
        'updated_by',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Fila única de parámetros (se crea con defaults de config si no existe).
     */
    public static function current(): self
    {
        return static::query()->orderBy('id')->firstOrCreate([], [
            'company_name' => (string) config('accounting.company.name', 'PrimeTrack Group'),
            'company_tax_id' => (string) config('accounting.company.tax_id', '') ?: null,
            'company_address' => (string) config('accounting.company.address', '') ?: null,
            'company_phones' => (string) config('accounting.company.phones', '') ?: null,
            'voucher_footer' => (string) config('accounting.company.footer', 'Es un gusto atenderle!'),
            'folio_prefix' => (string) config('accounting.folio_prefix', 'FP-'),
            'exchange_rate' => (float) config('accounting.default_exchange_rate', 36.6243),
        ]);
    }

    /**
     * Datos de empresa con la misma forma que config('accounting.company').
     *
     * @return array{name: string, tax_id: string, address: string, phones: string, footer: string}
     */
    public function toCompanyArray(): array
    {
        return [
            'name' => (string) $this->company_name,
            'tax_id' => (string) ($this->company_tax_id ?? ''),
            'address' => (string) ($this->company_address ?? ''),
            'phones' => (string) ($this->company_phones ?? ''),
            'footer' => (string) $this->voucher_footer,
        ];
    }
}
