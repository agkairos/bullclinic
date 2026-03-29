<?php

namespace App\Http\Middleware;

use App\Models\Conversa;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user?->load('clinicaAtiva.plano', 'contaAtiva.plano'),
                'permissions' => $user && $user->clinica_ativa_id
                    ? \App\Models\Permission::whereHas('roles', fn($q) =>
                        $q->whereHas('userClinicas', fn($q) =>
                            $q->where('user_id', $user->id)
                              ->where('clinica_id', $user->clinica_ativa_id)
                        )
                    )->pluck('nome')->toArray()
                    : [],
                'is_super_admin' => $user?->isSuperAdmin() ?? false,
            ],
            'clinicas' => $user ? $user->clinicas()
                ->wherePivot('ativo', true)
                ->when($user->conta_ativa_id, fn($q) => $q->where('clinicas.conta_id', $user->conta_ativa_id))
                ->get(['clinicas.id', 'nome', 'logo'])
                : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'chat_nao_lidas' => $user ? Conversa::withoutGlobalScope('clinica')
                ->where('clinica_id', $user->clinica_ativa_id)
                ->whereHas('membros', fn($q) => $q->where('user_id', $user->id))
                ->withCount([
                    'mensagens as nao_lidas' => fn($q) => $q
                        ->whereNull('deletada_em')
                        ->where('user_id', '!=', $user->id)
                        ->whereDoesntHave('leituras', fn($q) => $q->where('user_id', $user->id)),
                ])
                ->get()
                ->sum('nao_lidas')
            : 0,
        ];
    }
}
