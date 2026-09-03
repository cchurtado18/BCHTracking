<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyPackagesOnlyPortal
{
    /**
     * Subagencias anidadas no deben ver entregas ni facturas (tarifas de su proveedor).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isPackagesOnlyPortal()) {
            return redirect()->route('packages.index');
        }

        return $next($request);
    }
}
