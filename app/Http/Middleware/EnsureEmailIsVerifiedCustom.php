<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedCustom
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Se o usuário aceitou um convite, consideramos o email como verificado
        if ($user->invite_accepted_at) {
            return $next($request);
        }

        // Caso contrário, verifica se implementa MustVerifyEmail e se verificou
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                    ? abort(403, 'Seu endereço de email não foi verificado.')
                    : Redirect::route('verification.notice');
        }

        return $next($request);
    }
}

