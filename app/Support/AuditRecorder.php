<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditRecorder
{
    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(
        string $type,
        int|string|null $id,
        string $action,
        string $summary,
        ?array $old = null,
        ?array $new = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $type,
            'auditable_id' => $id,
            'action' => $action,
            'summary' => $summary,
            'old_values' => self::sanitize($old),
            'new_values' => self::sanitize($new),
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    public static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        unset($values['password'], $values['remember_token']);

        foreach ($values as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('c');
            }
        }

        return $values;
    }
}
