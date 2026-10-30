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
            $mensagem = $this->getMensagemErro($permissao);

            if ($request->expectsJson() || $request->header('X-Inertia')) {
                abort(403, $mensagem);
            }

            return redirect()->back()->with('error', $mensagem);
        }

        return $next($request);
    }

    /**
     * Retorna mensagem de erro amigável baseada na permissão.
     */
    protected function getMensagemErro(string $permissao): string
    {
        $modulo = $this->getModuloNome($permissao);

        return $modulo
            ? "Você não tem permissão para acessar {$modulo}."
            : 'Você não tem permissão para realizar esta ação.';
    }

    /**
     * Mapeia permissão para nome amigável do módulo.
     */
    protected function getModuloNome(string $permissao): ?string
    {
        return match (true) {
            str_contains($permissao, 'agenda') => 'a Agenda',
            str_contains($permissao, 'cliente') => 'Clientes',
            str_contains($permissao, 'paciente') => 'Pacientes',
            str_contains($permissao, 'prontuario') || str_contains($permissao, 'consulta') => 'Prontuários',
            str_contains($permissao, 'estoque') || str_contains($permissao, 'produto') => 'o Estoque',
            str_contains($permissao, 'financeiro') || str_contains($permissao, 'lancamento') => 'o Financeiro',
            str_contains($permissao, 'chat') || str_contains($permissao, 'conversa') || str_contains($permissao, 'mensagem') => 'o Chat',
            str_contains($permissao, 'usuario') || str_contains($permissao, 'role') || str_contains($permissao, 'clinica') || str_contains($permissao, 'auditoria') || str_contains($permissao, 'unidade') => 'a Administração',
            default => null,
        };
    }
}
