<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'action',
        'summary',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAuditableLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            'preregistration' => 'Paquete / Preregistro',
            default => $this->auditable_type,
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Creado',
            'updated' => 'Modificado',
            'deleted' => 'Eliminado',
            default => $this->action,
        };
    }
}
