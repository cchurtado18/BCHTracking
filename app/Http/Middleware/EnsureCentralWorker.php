<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralWorker
{
    /**
     * Solo usuarios centrales (sin agency_id): trabajadores internos, no portal de subagencia.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }
        if ($user->isAgencyUser()) {
            abort(403, 'El fichaje no está disponible para usuarios de subagencia.');
        }

        return $next($request);
    }
}
