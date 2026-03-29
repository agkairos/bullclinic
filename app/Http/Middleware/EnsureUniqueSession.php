<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUniqueSession
{
    /**
     * Garante que apenas uma sessão ativa por usuário existe por vez.
     * Ao fazer login, um token de sessão é gravado no usuário e na sessão.
     * Se o token da sessão atual não bater com o do usuário, o usuário é deslogado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $sessionToken = $request->session()->get('unique_session_token');

        // Primeira vez nesta sessão — gravar token
        if (!$sessionToken) {
            $token = Str::random(40);
            $request->session()->put('unique_session_token', $token);

            $user->forceFill(['session_token' => $token])->save();

            return $next($request);
        }

        // Token da sessão não bate com o do banco → outra sessão assumiu o controle
        if ($user->session_token !== $sessionToken) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sua conta foi acessada em outro dispositivo. Faça login novamente.');
        }

        return $next($request);
    }
}
