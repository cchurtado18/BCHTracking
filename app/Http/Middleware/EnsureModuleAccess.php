<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->canAccessModule($permission)) {
            return redirect()->to($user->homePath())
                ->with('error', 'No tiene permiso para este módulo.');
        }

        return $next($request);
    }
}
