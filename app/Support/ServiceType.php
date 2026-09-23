<?php

namespace App\Support;

use App\Models\Preregistration;

class ServiceType
{
    public const AIR = 'AIR';

    public const SEA = 'SEA';

    public const CFT = 'CFT';

    public const DELIVERY = 'DELIVERY';

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

    public const BILLING_LBS = 'LBS';

    /**
     * Aéreo o marítimo a partir del servicio almacenado (CFT viaja por mar).
     */
    public static function routeValue(?string $service): string
    {
        return self::isCft($service) ? self::SEA : self::normalize($service, self::AIR);
    }

    /**
     * LBS o CFT cuando el servicio es marítimo; null en aéreo.
     */
    public static function seaBillingValue(?string $service): ?string
    {
        if (self::isCft($service)) {
            return self::CFT;
        }

        return strtoupper((string) $service) === self::SEA ? self::BILLING_LBS : null;
    }

    /**
     * Servicio persistido desde la vía (AIR/SEA) y, en marítimo, el cobro (LBS/CFT).
     * Null si eligieron marítimo y aún no el cobro.
     */
    public static function fromRouteAndBilling(?string $route, ?string $billing): ?string
    {
        $route = strtoupper(trim((string) $route));
        $billing = strtoupper(trim((string) $billing));
        if ($route === self::AIR) {
            return self::AIR;
        }
        if ($route === self::SEA) {
            if ($billing === self::CFT) {
                return self::CFT;
            }
            if ($billing === self::BILLING_LBS || $billing === self::SEA) {
                return self::SEA;
            }

            return null;
        }

        return null;
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
            self::DELIVERY => 'Delivery',
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
            self::DELIVERY => 'Delivery',
            default => 'Flete',
        };
    }

    public static function unit(?string $value): string
    {
        $key = strtoupper((string) $value);
        if ($key === self::DELIVERY) {
            return '';
        }

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

    /**
     * Unidad de consolidación: aéreo = saco, marítimo = contenedor.
     */
    public static function consolidationNoun(?string $value, bool $plural = false): string
    {
        $sea = self::route($value) === self::SEA;
        if ($plural) {
            return $sea ? 'contenedores' : 'sacos';
        }

        return $sea ? 'contenedor' : 'saco';
    }

    public static function consolidationNounTitle(?string $value, bool $plural = false): string
    {
        $noun = self::consolidationNoun($value, $plural);

        return mb_strtoupper(mb_substr($noun, 0, 1)).mb_substr($noun, 1);
    }

    public static function transportNumberLabel(?string $value): string
    {
        return self::route($value) === self::SEA ? 'Número de contenedor' : 'Número de guía aérea';
    }
}
