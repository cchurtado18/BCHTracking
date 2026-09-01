<?php

namespace App\Console\Commands;

use App\Services\Alerts\PackageAlertDispatcher;
use Illuminate\Console\Command;

class DispatchPackageAlerts extends Command
{
    protected $signature = 'alerts:dispatch {--no-mail : Solo detecta, no envía correo}';

    protected $description = 'Detecta paquetes detenidos o lotes incompletos y notifica a los administradores';

    public function handle(PackageAlertDispatcher $dispatcher): int
    {
        $created = $dispatcher->dispatch(! $this->option('no-mail'));

        $this->info($created->count().' alerta(s) nueva(s).');

        return self::SUCCESS;
    }
}
