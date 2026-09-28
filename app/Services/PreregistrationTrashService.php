<?php

namespace App\Services;

use App\Models\Preregistration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class PreregistrationTrashService
{
    public const RETENTION_DAYS = 7;

    public function __construct(
        protected PreregistrationPhotoService $photoService,
    ) {}

    public function restorableQuery(?Carbon $now = null): Builder
    {
        return Preregistration::onlyTrashed()
            ->where('deleted_at', '>=', $this->cutoff($now));
    }

    public function expiresAt(Preregistration $package): Carbon
    {
        return $package->deleted_at->copy()->addDays(self::RETENTION_DAYS);
    }

    public function daysRemaining(Preregistration $package, ?Carbon $now = null): int
    {
        $now = $now ?? now();
        $seconds = $now->diffInSeconds($this->expiresAt($package), false);
        if ($seconds <= 0) {
            return 0;
        }

        return (int) ceil($seconds / 86400);
    }

    public function restore(Preregistration $package): void
    {
        if (! $package->trashed()) {
            throw new RuntimeException('Este preregistro no está eliminado.');
        }

        if ($package->deleted_at->lt($this->cutoff())) {
            $this->purgeOne($package);

            throw new RuntimeException('El plazo para recuperarlo ya venció y el registro se eliminó de forma definitiva.');
        }

        $tracking = trim((string) $package->tracking_external);
        if ($tracking !== '' && Preregistration::query()->where('tracking_external', $tracking)->exists()) {
            throw new RuntimeException('No se puede recuperar: ya existe un preregistro activo con ese tracking.');
        }

        $package->restore();
    }

    public function purgeExpired(?Carbon $now = null): int
    {
        $packages = Preregistration::onlyTrashed()
            ->where('deleted_at', '<', $this->cutoff($now))
            ->with('photos')
            ->get();

        $count = 0;
        foreach ($packages as $package) {
            $this->purgeOne($package);
            $count++;
        }

        return $count;
    }

    public function purgeOne(Preregistration $package): void
    {
        $package->loadMissing('photos');
        foreach ($package->photos as $photo) {
            $this->photoService->deletePhoto($photo);
        }

        $package->forceDelete();
    }

    private function cutoff(?Carbon $now = null): Carbon
    {
        return ($now ?? now())->copy()->subDays(self::RETENTION_DAYS);
    }
}
