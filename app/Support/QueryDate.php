<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class QueryDate
{
    public static function parse(Request $request, string $key): ?Carbon
    {
        $raw = $request->input($key);
        if (! is_string($raw) && ! is_numeric($raw)) {
            return null;
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
