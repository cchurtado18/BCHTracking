<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralUser
{
    /**
     * Solo usuarios centrales (bodega). Bloquea usuarios de subagencia.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        if ($request->user()->isAgencyUser()) {
            return redirect()->route('packages.index');
        }

        return $next($request);
    }
}
