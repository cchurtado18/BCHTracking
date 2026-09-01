<?php

namespace App\Support;

use App\Models\Preregistration;

class ServiceType
{
    public const AIR = 'AIR';

    public const SEA = 'SEA';

    public const CFT = 'CFT';

    /** @var list<string> */
    public const ALL = [self::AIR, self::SEA, self::CFT];

    /** @var list<string> */
    public const ROUTES = [self::AIR, self::SEA];

    public static function rule(): string
    {
        return 'in:'.implode(',', self::ALL);
    }

    public static function routeRule(): string
    {
        return 'in:'.implode(',', self::ROUTES);
    }

    public static function isValid(?string $value): bool
    {
        return in_array(strtoupper((string) $value), self::ALL, true);
    }

    public static function normalize(?string $value, string $fallback = self::AIR): string
    {
        $key = strtoupper((string) $value);

        return self::isValid($key) ? $key : $fallback;
    }

    public static function isCft(?string $value): bool
    {
        return strtoupper((string) $value) === self::CFT;
    }

    /**
     * Vía de envío: pie cúbico viaja por mar.
     */
    public static function route(?string $value): string
    {
        return self::isCft($value) ? self::SEA : self::normalize($value, self::AIR);
    }

    public static function matchesRoute(?string $packageService, ?string $sackOrRoute): bool
    {
        return self::route($packageService) === self::route($sackOrRoute);
    }

    /**
     * @return list<string>
     */
    public static function servicesForRoute(?string $route): array
    {
        return self::route($route) === self::SEA ? [self::SEA, self::CFT] : [self::AIR];
    }

    public static function routeMark(?string $value): string
    {
        return self::route($value) === self::SEA ? 'M' : 'A';
    }

    public static function routeLabel(?string $value): string
    {
        return self::label(self::route($value));
    }

    public static function routeLabelLower(?string $value): string
    {
        return self::labelLower(self::route($value));
    }

    /**
     * Filtro operativo: marítimo incluye pie cúbico (misma vía).
     * CFT solo si se pide explícitamente.
     *
     * @return list<string>
     */
    public static function operationalFilter(?string $value): array
    {
        $key = strtoupper((string) $value);
        if ($key === self::CFT) {
            return [self::CFT];
        }

        return self::servicesForRoute($key);
    }

    public static function label(?string $value): string
    {
        return match (strtoupper((string) $value)) {
            self::AIR => 'Aéreo',
            self::SEA => 'Marítimo',
            self::CFT => 'Pie cúbico',
            default => $value ?: '—',
        };
    }

    public static function labelLower(?string $value): string
    {
        return match (strtoupper((string) $value)) {
            self::AIR => 'aéreo',
            self::SEA => 'marítimo',
            self::CFT => 'pie cúbico',
            default => strtolower((string) $value),
        };
    }

    public static function freightDescription(string $value): string
    {
        return match (strtoupper($value)) {
            self::AIR => 'Flete Aereo',
            self::SEA => 'Flete Maritimo',
            self::CFT => 'Flete Pie Cubico',
            default => 'Flete',
        };
    }

    public static function unit(?string $value): string
    {
        return self::isCft($value) ? 'pie³' : 'lb';
    }

    public static function unitPriceLabel(?string $value): string
    {
        return self::isCft($value) ? 'USD/pie³' : 'USD/lb';
    }

    public static function icon(?string $value): string
    {
        return match (strtoupper((string) $value)) {
            self::AIR => '✈',
            self::SEA => '⚓',
            self::CFT => '▣',
            default => '',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::AIR => 'Aéreo',
            self::SEA => 'Marítimo',
            self::CFT => 'Pie cúbico',
        ];
    }

    public static function billedQuantity(Preregistration $package): float
    {
        if (self::isCft($package->service_type)) {
            return (float) ($package->cubic_feet ?? 0);
        }

        return (float) ($package->verified_weight_lbs ?? $package->intake_weight_lbs ?? 0);
    }
}
