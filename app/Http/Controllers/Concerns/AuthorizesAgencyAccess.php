<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Agency;
use App\Models\Preregistration;

trait AuthorizesAgencyAccess
{
    /**
     * IDs de agencias que el usuario actual puede ver. Null = acceso total.
     */
    protected function userAllowedAgencyIds(): ?array
    {
        return auth()->user()?->allowedAgencyIds();
    }

    protected function ensureUserCanAccessAgency(?Agency $agency): void
    {
        $user = auth()->user();
        abort_unless($user, 403, 'No autorizado.');

        if (! $user->canAccessAgency($agency)) {
            abort(403, 'No tiene permiso para esta agencia.');
        }
    }

    protected function ensureUserCanAccessAgencyId(?int $agencyId): void
    {
        $user = auth()->user();
        abort_unless($user, 403, 'No autorizado.');

        if (! $user->canAccessAgencyId($agencyId)) {
            abort(403, 'No tiene permiso para este recurso.');
        }
    }

    protected function ensureUserCanAccessPreregistration(?Preregistration $preregistration): void
    {
        $this->ensureUserCanAccessAgencyId(
            $preregistration?->agency_id ? (int) $preregistration->agency_id : null
        );
    }
}
