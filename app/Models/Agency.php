<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Agency extends Model
{
    protected $fillable = ['parent_agency_id', 'code', 'name', 'phone', 'address', 'department', 'logo_path', 'is_active', 'is_main'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'parent_agency_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Agency::class, 'parent_agency_id');
    }

    public function scopeMainAgencies(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    /**
     * Si esta agencia es CH LOGISTICS o subagencia de CH LOGISTICS (encomienda familiar).
     * Se usa para mostrar la "Nota de cobro" con diseño CH Logistics en lugar del comprobante BCH.
     */
    public function isChLogistics(): bool
    {
        $name = strtoupper((string) ($this->name ?? ''));
        if ($name === 'CH LOGISTICS' || (string) $this->code === '0002') {
            return true;
        }
        if ($this->parent_agency_id && $this->relationLoaded('parent') && $this->parent) {
            return strtoupper((string) $this->parent->name) === 'CH LOGISTICS' || (string) $this->parent->code === '0002';
        }
        return false;
    }

    /**
     * Si esta agencia es SkyLink One o subagencia de SkyLink One.
     * Se usa para renderizar la etiqueta con el diseño "SkyLink One Logistics".
     */
    public function isSkyLinkOne(): bool
    {
        $name = strtoupper((string) ($this->name ?? ''));
        if ($name === 'SKYLINK ONE' || (string) $this->code === '0001') {
            return true;
        }
        if ($this->parent_agency_id && $this->relationLoaded('parent') && $this->parent) {
            return strtoupper((string) $this->parent->name) === 'SKYLINK ONE' || (string) $this->parent->code === '0001';
        }
        return false;
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
                return asset('storage/' . $path);
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
     * Usuarios de acceso vinculados a esta agencia (inicio de sesión para la subagencia).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
