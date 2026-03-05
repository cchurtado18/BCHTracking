<?php

namespace App\Providers;

use App\Models\Preregistration;
use App\Observers\PreregistrationObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     * Fijamos la zona horaria de Miami para que al guardar paquetes (created_at, etc.)
     * y al mostrar fechas se use siempre hora de Miami.
     */
    public function boot(): void
    {
        $tz = config('app.timezone', 'America/New_York');
        date_default_timezone_set($tz);
        Carbon::setDefaultTimezone($tz);

        Preregistration::observe(PreregistrationObserver::class);
    }
}
