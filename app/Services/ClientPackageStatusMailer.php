<?php

namespace App\Services;

use App\Mail\PackageReadyForPickup;
use App\Mail\PackageReceivedInMiami;
use App\Models\Preregistration;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class ClientPackageStatusMailer
{
    public function notifyReceivedInMiami(Preregistration $package): void
    {
        $this->sendOnce($package, 'miami_received_notified_at', fn (Preregistration $p) => new PackageReceivedInMiami($p));
    }

    public function notifyReadyForPickup(Preregistration $package): void
    {
        $package->loadMissing(['agency.parent.parent.parent']);
        if ($package->agency?->isNestedUnderPartner()) {
            return;
        }

        $this->sendOnce($package, 'ready_notified_at', fn (Preregistration $p) => new PackageReadyForPickup($p));
    }

    /**
     * @param  callable(Preregistration): Mailable  $mailable
     */
    private function sendOnce(Preregistration $package, string $column, callable $mailable): void
    {
        $package->refresh();
        if ($package->{$column} !== null) {
            return;
        }

        $package->loadMissing(['agency.users']);
        $email = $package->agency?->billingEmail();
        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send($mailable($package));
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $package->forceFill([$column => now()])->save();
    }
}
