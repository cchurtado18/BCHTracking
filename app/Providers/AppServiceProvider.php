<?php

namespace App\Providers;

use App\Models\Preregistration;
use App\Observers\PreregistrationObserver;
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
     * Las fechas se guardan en UTC; en las vistas se convierten a America/New_York (Miami).
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'UTC'));

        Preregistration::observe(PreregistrationObserver::class);
    }
}
