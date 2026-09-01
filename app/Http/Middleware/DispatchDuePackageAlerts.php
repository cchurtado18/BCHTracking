<?php

namespace App\Http\Middleware;

use App\Services\Alerts\PackageAlertDispatcher;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatchDuePackageAlerts
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (app()->environment('testing') || $request->is('up')) {
            return $response;
        }

        dispatch(function () {
            app(PackageAlertDispatcher::class)->dispatchIfDue();
        })->afterResponse();

        return $response;
    }
}
