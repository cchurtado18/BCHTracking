<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('alerts:dispatch')->everyFifteenMinutes()->withoutOverlapping(10);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'central' => \App\Http\Middleware\EnsureCentralUser::class,
            'central.worker' => \App\Http\Middleware\EnsureCentralWorker::class,
            'not-packages-only' => \App\Http\Middleware\DenyPackagesOnlyPortal::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\DispatchDuePackageAlerts::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
