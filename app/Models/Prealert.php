<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prealert extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'agency_id',
        'tracking',
        'service_type',
        'description',
        'status',
        'created_by',
        'preregistration_id',
        'matched_at',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
    ];

    public static function toUpper(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        return mb_strtoupper($trimmed, 'UTF-8');
    }

    public static function normalizeTracking(?string $value): string
    {
        return \App\Support\TrackingCode::canonical($value);
    }

    public static function findOpenByTracking(?string $tracking): ?self
    {
        $code = self::normalizeTracking($tracking);
        if ($code === '') {
            return null;
        }

        return static::query()
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) use ($tracking) {
                \App\Support\TrackingCode::constrainLookup($query, 'tracking', $tracking);
            })
            ->first();
    }

    public function warehouseNotice(): array
    {
        $this->loadMissing('agency:id,code,name,account_type,is_main,parent_agency_id');

        return [
            'id' => $this->id,
            'tracking' => $this->tracking,
            'name' => $this->name,
            'description' => $this->description,
            'service_type' => $this->service_type,
            'service_label' => \App\Support\ServiceType::label($this->service_type),
            'agency_id' => $this->agency_id,
            'agency_name' => $this->agency?->listingAccountLabel(),
            'agency_code' => $this->agency?->code,
        ];
    }

    public function matchToPreregistration(Preregistration $preregistration): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_MATCHED,
            'preregistration_id' => $preregistration->id,
            'matched_at' => now(),
        ]);

        $packageUpdates = [];
        if ($this->service_type && (
            ! \App\Support\ServiceType::isValid($preregistration->service_type)
            || $preregistration->status === 'PHOTO_PENDING'
        )) {
            $packageUpdates['service_type'] = $this->service_type;
        }
        if ($this->agency_id && ! $preregistration->agency_id) {
            $packageUpdates['agency_id'] = $this->agency_id;
        }
        if ($this->name && in_array((string) $preregistration->label_name, ['', '[PENDIENTE]'], true)) {
            $packageUpdates['label_name'] = $this->name;
        }
        if ($this->description && ! $preregistration->description) {
            $packageUpdates['description'] = $this->description;
        }
        if ($packageUpdates !== []) {
            $preregistration->update($packageUpdates);
        }

        return true;
    }

    public static function matchOpenByTracking(?string $tracking, Preregistration $preregistration): ?self
    {
        $prealert = self::findOpenByTracking($tracking);
        if (! $prealert) {
            return null;
        }

        $prealert->matchToPreregistration($preregistration);

        return $prealert;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_MATCHED => 'Ingresado',
            self::STATUS_CANCELLED => 'Cancelada',
            default => 'Pendiente',
        };
    }

    protected function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = self::toUpper($value);
    }

    protected function getNameAttribute(?string $value): ?string
    {
        return self::toUpper($value);
    }

    protected function setTrackingAttribute(?string $value): void
    {
        $this->attributes['tracking'] = self::normalizeTracking($value);
    }

    protected function getTrackingAttribute(?string $value): ?string
    {
        return self::normalizeTracking($value);
    }

    protected function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = self::toUpper($value);
    }

    protected function getDescriptionAttribute(?string $value): ?string
    {
        return self::toUpper($value);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(Preregistration::class);
    }
}
