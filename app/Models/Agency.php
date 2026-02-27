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
     * URL del logo (para etiquetas y vistas).
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }
        return Storage::disk('public')->exists($this->logo_path)
            ? asset('storage/' . $this->logo_path)
            : null;
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
