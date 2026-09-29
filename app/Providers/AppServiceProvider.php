<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\Consolidation;
use App\Models\ConsolidationItem;
use App\Models\Delivery;
use App\Models\DeliveryNote;
use App\Models\Prealert;
use App\Models\Preregistration;
use App\Models\ReceiptNote;
use App\Models\User;
use App\Observers\OperationalAuditObserver;
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

        foreach ([
            DeliveryNote::class,
            Delivery::class,
            ReceiptNote::class,
            Prealert::class,
            Consolidation::class,
            ConsolidationItem::class,
            Agency::class,
            User::class,
        ] as $model) {
            $model::observe(OperationalAuditObserver::class);
        }
    }
}
