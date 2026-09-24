<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'agency_id',
        'permissions',
    ];

    /**
     * Agencia asignada (si es usuario de subagencia).
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Fichajes de entrada/salida (solo aplica a usuarios centrales en la práctica).
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * True si el usuario es de una subagencia (solo ve datos de esa agencia).
     */
    public function isAgencyUser(): bool
    {
        return $this->agency_id !== null;
    }

    /**
     * Usuario de una subagencia anidada (no hija directa de SLO).
     * Solo accede al módulo de paquetes: entregas y facturas revelarían tarifas del proveedor.
     */
    public function isPackagesOnlyPortal(): bool
    {
        if (! $this->isAgencyUser()) {
            return false;
        }

        $agency = $this->relationLoaded('agency') ? $this->agency : $this->agency()->first();

        return $agency?->isNestedUnderPartner() ?? false;
    }

    public function canViewCommercialModules(): bool
    {
        return ! $this->isPackagesOnlyPortal();
    }

    /**
     * True si es usuario central (sin agencia asignada).
     */
    public function isCentral(): bool
    {
        return $this->agency_id === null;
    }

    /**
     * IDs de agencias que este usuario puede consultar o modificar.
     * Null = acceso total (usuario central).
     */
    public function allowedAgencyIds(): ?array
    {
        if (! $this->isAgencyUser()) {
            return null;
        }

        $ids = [(int) $this->agency_id];
        $agency = $this->relationLoaded('agency') ? $this->agency : Agency::find($this->agency_id);

        if ($agency && $agency->canHaveChildren()) {
            $ids = $agency->networkIds();
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function canAccessAgencyId(?int $agencyId): bool
    {
        if ($agencyId === null || $agencyId <= 0) {
            return ! $this->isAgencyUser();
        }

        $allowed = $this->allowedAgencyIds();

        return $allowed === null || in_array((int) $agencyId, $allowed, true);
    }

    public function canAccessAgency(?Agency $agency): bool
    {
        return $this->canAccessAgencyId($agency ? (int) $agency->id : null);
    }

    /**
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        if ($this->isAgencyUser()) {
            return [];
        }
        if ($this->is_admin) {
            return Permission::allKeys();
        }
        if (! is_array($this->permissions)) {
            return Permission::operationalDefaults();
        }

        return Permission::sanitize($this->permissions);
    }

    public function hasPermission(string $key): bool
    {
        if ($this->isAgencyUser()) {
            return false;
        }

        return in_array($key, $this->effectivePermissions(), true);
    }

    public function canAccessModule(string $key): bool
    {
        if ($this->isAgencyUser()) {
            return match ($key) {
                Permission::MODULE_PACKAGES, Permission::MODULE_PREALERTS => true,
                Permission::MODULE_DELIVERIES, Permission::MODULE_ACCOUNTING => ! $this->isPackagesOnlyPortal(),
                default => false,
            };
        }

        return $this->hasPermission($key);
    }

    public function homePath(): string
    {
        if ($this->isAgencyUser() || $this->canAccessModule(Permission::MODULE_PACKAGES)) {
            return route('packages.index', absolute: false);
        }
        if ($this->hasPermission(Permission::MODULE_PREREGISTRATIONS)) {
            return route('preregistrations.index', absolute: false);
        }
        if ($this->hasPermission(Permission::MODULE_DASHBOARD)) {
            return route('dashboard', absolute: false);
        }

        return route('tracking.index', absolute: false);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
