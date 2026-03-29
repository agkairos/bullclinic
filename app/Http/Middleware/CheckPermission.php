<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissao): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->pode($permissao)) {
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                abort(403, 'Sem permissão para esta ação.');
            }

            return redirect()->back()->with('error', 'Sem permissão para esta ação.');
        }

        return $next($request);
    }
}
