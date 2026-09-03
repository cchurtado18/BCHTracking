<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Agency extends Model
{
    public const TYPE_ROOT = 'root';

    public const TYPE_SUBAGENCY = 'subagency';

    public const TYPE_DIRECT_CLIENT = 'direct_client';

    protected $fillable = ['parent_agency_id', 'code', 'name', 'phone', 'address', 'department', 'logo_path', 'is_active', 'is_main', 'account_type', 'credit_limit_usd', 'credit_days', 'credit_balance_usd', 'tax_id', 'billing_contact_name', 'billing_contact_phone', 'billing_email'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'credit_limit_usd' => 'decimal:2',
        'credit_days' => 'integer',
        'credit_balance_usd' => 'decimal:2',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'parent_agency_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Agency::class, 'parent_agency_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(AccountingCreditNote::class);
    }

    public function creditMovements(): HasMany
    {
        return $this->hasMany(AccountingCreditMovement::class);
    }

    public function scopeMainAgencies(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    public function isRootAccount(): bool
    {
        return $this->is_main || $this->account_type === self::TYPE_ROOT;
    }

    public function isDirectClient(): bool
    {
        return $this->account_type === self::TYPE_DIRECT_CLIENT;
    }

    /**
     * Marca en etiqueta: clientes propios de SLO usan logo y nombre de SkyLink One.
     */
    public function labelBrandAgency(): self
    {
        if (! $this->isDirectClient()) {
            return $this;
        }

        $parent = $this->relationLoaded('parent') && $this->parent
            ? $this->parent
            : $this->parent()->first();

        return $parent ?: $this;
    }

    public function canHaveChildren(): bool
    {
        return $this->is_main || $this->account_type === self::TYPE_SUBAGENCY || $this->account_type === self::TYPE_ROOT;
    }

    /**
     * Subagencia colgada de otra subagencia (no de SLO).
     * Su proveedor es la subagencia padre; no debe ver tarifas en entregas/facturas.
     */
    public function isNestedUnderPartner(): bool
    {
        if ($this->isRootAccount() || $this->isDirectClient()) {
            return false;
        }

        if (! $this->parent_agency_id) {
            return false;
        }

        $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();
        if (! $parent) {
            return false;
        }

        return ! $parent->isRootAccount();
    }

    /**
     * Destinatarios (libreta de entrega) solo en subagencias y SLO, no en clientes propios.
     */
    public function canManageDestinatarios(): bool
    {
        return ! $this->isDirectClient();
    }

    public function typeLabel(): string
    {
        if ($this->is_main || $this->account_type === self::TYPE_ROOT) {
            return 'SkyLink One';
        }

        return $this->isDirectClient() ? 'Cliente SLO' : 'Subagencia';
    }

    /**
     * IDs de hijas/nietas (sin incluir esta agencia).
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [(int) $this->id];
        $guard = 0;

        while ($frontier !== [] && $guard++ < 30) {
            $children = static::query()
                ->whereIn('parent_agency_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $children = array_values(array_diff($children, $ids, [(int) $this->id]));
            if ($children === []) {
                break;
            }
            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }

    /**
     * Esta cuenta + toda su descendencia.
     *
     * @return list<int>
     */
    public function networkIds(): array
    {
        return array_values(array_unique(array_merge([(int) $this->id], $this->descendantIds())));
    }

    /**
     * Raíz comercial para facturar juntas las hojas de esta red.
     * Cliente SLO o raíz: ella misma. Subagencia: sube hasta la agencia bajo SLO (incluye nietas).
     */
    public function invoiceFamilyRoot(): self
    {
        if ($this->isDirectClient() || $this->isRootAccount()) {
            return $this;
        }

        $current = $this;
        $guard = 0;
        while ($current->parent_agency_id && $guard++ < 20) {
            $parent = $current->relationLoaded('parent') && $current->parent
                ? $current->parent
                : $current->parent()->first();
            if (! $parent || $parent->isRootAccount()) {
                break;
            }
            $current = $parent;
        }

        return $current;
    }

    /**
     * IDs de la misma red facturable: la agencia y sus subagencias (incluyendo subagencia de subagencia).
     *
     * @return list<int>
     */
    public function invoiceFamilyIds(): array
    {
        $root = $this->invoiceFamilyRoot();
        if ($root->isDirectClient() || $root->isRootAccount()) {
            return [(int) $root->id];
        }

        return $root->networkIds();
    }

    /**
     * Paquetes que van juntos en una hoja de salida para este nodo comercial.
     * Subagencia: ella + todas sus subagencias. Cliente SLO: solo él. Raíz SLO: SLO + clientes propios (no mezcla redes de otras subagencias).
     *
     * @return list<int>
     */
    public function deliveryNetworkIds(): array
    {
        if ($this->isDirectClient()) {
            return [(int) $this->id];
        }

        if ($this->is_main || $this->account_type === self::TYPE_ROOT) {
            $direct = static::query()
                ->where('parent_agency_id', $this->id)
                ->where('account_type', self::TYPE_DIRECT_CLIENT)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return array_values(array_unique(array_merge([(int) $this->id], $direct)));
        }

        return $this->networkIds();
    }

    /**
     * @return list<object{id: int, name: string, code: string}>
     */
    public function ancestorChain(): array
    {
        $chain = [];
        $current = $this;
        $guard = 0;
        while ($current && $guard++ < 20) {
            $chain[] = $current;
            if (! $current->parent_agency_id) {
                break;
            }
            $current = $current->relationLoaded('parent') && $current->parent
                ? $current->parent
                : $current->parent()->first();
        }

        return $chain;
    }

    /**
     * Si esta agencia es CH LOGISTICS o está en su red (hija/nieta).
     */
    public function isChLogistics(): bool
    {
        foreach ($this->ancestorChain() as $node) {
            $name = strtoupper((string) ($node->name ?? ''));
            if ($name === 'CH LOGISTICS' || (string) $node->code === '0002') {
                return true;
            }
        }

        return false;
    }

    /**
     * Si esta agencia es SkyLink One o está en su árbol.
     */
    public function isSkyLinkOne(): bool
    {
        foreach ($this->ancestorChain() as $node) {
            $name = strtoupper((string) ($node->name ?? ''));
            if ($name === 'SKYLINK ONE' || (string) $node->code === '0001') {
                return true;
            }
        }

        return false;
    }

    /**
     * Cuentas que pueden tener subagencias: SLO y cualquier subagencia (no clientes propios de SLO).
     */
    public static function parentCandidates()
    {
        return static::query()
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->where('is_main', true)
                    ->orWhereNull('account_type')
                    ->orWhere('account_type', '!=', self::TYPE_DIRECT_CLIENT);
            })
            ->orderByDesc('is_main')
            ->orderBy('name');
    }

    /**
     * Subagencias a las que esta cuenta puede colgarse (no SLO, no ella misma ni su descendencia).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public function nestedParentOptions()
    {
        $blocked = array_values(array_unique(array_merge([(int) $this->id], $this->descendantIds())));

        $options = static::parentCandidates()
            ->whereNotIn('id', $blocked)
            ->get()
            ->filter(fn (self $agency) => ! $agency->isRootAccount())
            ->values();

        $current = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();
        if ($current && ! $current->isRootAccount() && ! $options->contains('id', $current->id)) {
            $options->prepend($current);
        }

        return $options;
    }

    public function affiliationScope(): string
    {
        $parent = $this->relationLoaded('parent') ? $this->parent : $this->parent()->first();

        return ($parent && ! $parent->isRootAccount()) ? 'nested' : 'slo';
    }

    /**
     * URL del logo (para etiquetas y vistas).
     */
    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->attributes['logo_path'] ?? $this->logo_path ?? null;
        if (empty($path) || ! is_string($path)) {
            return null;
        }
        try {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/'.$path);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function clients(): HasMany
    {
        return $this->hasMany(AgencyClient::class);
    }

    public function preregistrations(): HasMany
    {
        return $this->hasMany(Preregistration::class);
    }

    /**
     * Siguiente código numérico único para una nueva agencia/subagencia.
     * No usa CAST en SQL (evita fallos o resultados incorrectos con SQLite o códigos no numéricos).
     */
    public static function nextAvailableNumericCode(): string
    {
        $max = 0;
        foreach (static::query()->pluck('code') as $c) {
            if ($c === null) {
                continue;
            }
            $s = trim((string) $c);
            if ($s !== '' && preg_match('/^\d+$/', $s)) {
                $max = max($max, (int) $s);
            }
        }
        $next = $max + 1;

        return $next <= 9999
            ? str_pad((string) $next, 4, '0', STR_PAD_LEFT)
            : (string) $next;
    }

    /**
     * Correo al que se envían las facturas: el de cobranza, o el de acceso de la cuenta.
     */
    public function billingEmail(): ?string
    {
        $email = trim((string) $this->billing_email);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        $userEmail = trim((string) ($this->users->first()?->email ?? $this->users()->orderBy('id')->value('email')));

        return $userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL) ? $userEmail : null;
    }

    /**
     * Usuarios de acceso vinculados a esta agencia (inicio de sesión para la subagencia).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function accountingInvoices(): HasMany
    {
        return $this->hasMany(AccountingInvoice::class);
    }

    public function accountingPayments(): HasMany
    {
        return $this->hasMany(AccountingPayment::class);
    }
}
