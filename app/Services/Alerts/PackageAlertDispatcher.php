<?php

namespace App\Services\Alerts;

use App\Mail\PackageAlertsDigest;
use App\Models\PackageAlert;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PackageAlertDispatcher
{
    public function __construct(private PackageAlertScanner $scanner)
    {
    }

    /**
     * Detecta casos nuevos y, si hay, manda un correo a cada administrador.
     *
     * @return Collection<int, PackageAlert>
     */
    public function dispatch(bool $sendMail = true): Collection
    {
        $created = $this->scanner->scan();

        if ($sendMail && $created->isNotEmpty()) {
            $this->emailAdmins($created);
        }

        return $created;
    }

    /**
     * Como máximo una pasada cada 15 minutos (web o cron).
     */
    public function dispatchIfDue(): void
    {
        try {
            $ttl = max(60, (int) config('alerts.check_every_minutes', 15) * 60);
            if (! Cache::add('alerts:dispatch:due', 1, $ttl)) {
                return;
            }
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        try {
            $this->dispatch(true);
        } catch (\Throwable $e) {
            Cache::forget('alerts:dispatch:due');
            report($e);
        }
    }

    /**
     * @param  Collection<int, PackageAlert>  $created
     */
    private function emailAdmins(Collection $created): void
    {
        $admins = User::query()
            ->where('is_admin', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'name', 'email']);

        if ($admins->isEmpty()) {
            return;
        }

        $payload = PackageAlert::query()
            ->with(['preregistration.agency'])
            ->whereIn('id', $created->pluck('id'))
            ->get();

        $sent = 0;
        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(new PackageAlertsDigest($payload));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($sent === 0) {
            return;
        }

        PackageAlert::query()
            ->whereIn('id', $created->pluck('id'))
            ->update(['emailed_at' => now()]);
    }
}
