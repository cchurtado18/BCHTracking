<?php

namespace App\Support;

class TrackingCode
{
    public static function compact(?string $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim((string) $value)) ?? '');
    }

    public static function canonical(?string $value): string
    {
        $code = self::compact($value);
        if ($code === '' || preg_match('/^\d{6}$/', $code)) {
            return $code;
        }

        return self::extractUsps($code) ?? $code;
    }

    /**
     * @return list<string>
     */
    public static function lookupValues(?string $value): array
    {
        $raw = self::compact($value);
        $canonical = self::canonical($raw);

        return array_values(array_unique(array_filter(
            [$raw, $canonical],
            fn (string $item) => $item !== ''
        )));
    }

    public static function matches(?string $stored, ?string $scanned): bool
    {
        $left = self::canonical($stored);
        $right = self::canonical($scanned);

        return $left !== '' && $left === $right;
    }

    public static function uspsSuffixForLike(?string $value): ?string
    {
        $canonical = self::canonical($value);
        if (! preg_match('/^9[0-5]\d{18,20}$/', $canonical)) {
            return null;
        }

        return $canonical;
    }

    public static function constrainLookup($query, string $column, ?string $code): void
    {
        $values = self::lookupValues($code);
        if ($values === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $suffix = self::uspsSuffixForLike($code);
        $query->where(function ($inner) use ($column, $values, $suffix) {
            $inner->whereIn($column, $values);
            if ($suffix !== null) {
                $inner->orWhere($column, 'like', '%'.$suffix);
            }
        });
    }

    private static function extractUsps(string $code): ?string
    {
        $code = self::stripRoutingPrefix($code);

        if (preg_match('/^([A-Z]{2}\d{9}[A-Z]{2})/', $code, $match)) {
            return $match[1];
        }
        if (preg_match('/^(9[0-5]\d{20})/', $code, $match)) {
            return $match[1];
        }
        if (preg_match('/^(9[0-5]\d{18})/', $code, $match)) {
            return $match[1];
        }

        return null;
    }

    private static function stripRoutingPrefix(string $code): string
    {
        if (! str_starts_with($code, '420') || strlen($code) < 8) {
            return $code;
        }

        $afterZip5 = substr($code, 8);
        $afterZip9 = strlen($code) > 12 ? substr($code, 12) : '';

        if (self::looksLikeUspsStart($afterZip5)) {
            return $afterZip5;
        }
        if ($afterZip9 !== '' && self::looksLikeUspsStart($afterZip9)) {
            return $afterZip9;
        }

        return $afterZip5 !== '' ? $afterZip5 : $code;
    }

    private static function looksLikeUspsStart(string $code): bool
    {
        return (bool) preg_match('/^9[0-5]\d{17,}/', $code)
            || (bool) preg_match('/^[A-Z]{2}\d{9}[A-Z]{2}/', $code);
    }
}
