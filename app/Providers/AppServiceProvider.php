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
     */
    public function boot(): void
    {
        Preregistration::observe(PreregistrationObserver::class);
    }
}
