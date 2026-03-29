<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicaActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->clinica_ativa_id) {
            return redirect()->route('clinica.selecionar')
                ->with('error', 'Selecione uma clínica para continuar.');
        }

        $clinica = $user->clinicaAtiva;

        if (! $clinica || ! $clinica->ativa) {
            return redirect()->route('clinica.inativa');
        }

        // Status de assinatura agora fica na Conta
        $conta = $clinica->conta ?? $user->contaAtiva;

        if ($conta && ! $conta->estaAtiva()) {
            return redirect()->route('assinatura.pendente');
        }

        return $next($request);
    }
}
