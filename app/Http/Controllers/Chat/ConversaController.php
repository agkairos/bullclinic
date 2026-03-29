<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversa;
use App\Models\ConversaMembro;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversas = Conversa::whereHas('membros', fn($q) => $q
            ->where('user_id', $user->id)
            ->whereNull('saiu_em')
        )
        ->with([
            'membros.user',
            'mensagens' => fn($q) => $q->whereNull('deletada_em')->latest()->limit(1),
        ])
        ->withCount([
            'mensagens as nao_lidas' => fn($q) => $q
                ->whereNull('deletada_em')
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('leituras', fn($q) => $q->where('user_id', $user->id)),
        ])
        ->orderByDesc('updated_at')
        ->get();

        $usuarios = User::whereHas('userClinicas', fn($q) => $q
            ->where('clinica_id', $user->clinica_ativa_id)
            ->where('ativo', true)
        )
        ->where('id', '!=', $user->id)
        ->get(['id', 'name', 'avatar']);

        return Inertia::render('Chat/Index', [
            'conversas' => $conversas,
            'usuarios' => $usuarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => ['required', 'in:individual,grupo,canal'],
            'nome' => ['nullable', 'string', 'max:255', 'required_if:tipo,grupo,canal'],
            'membros' => ['required', 'array', 'min:1'],
            'membros.*' => ['exists:users,id'],
        ]);

        $user = $request->user();

        // Para conversas individuais, verifica se já existe
        if ($request->tipo === 'individual' && count($request->membros) === 1) {
            $existente = Conversa::whereHas('membros', fn($q) => $q->where('user_id', $user->id))
                ->whereHas('membros', fn($q) => $q->where('user_id', $request->membros[0]))
                ->where('tipo', 'individual')
                ->first();

            if ($existente) {
                return response()->json(['conversa' => $existente]);
            }
        }

        $conversa = Conversa::create([
            'clinica_id' => $user->clinica_ativa_id,
            'criado_por_id' => $user->id,
            'tipo' => $request->tipo,
            'nome' => $request->nome,
        ]);

        // Adiciona criador + membros
        $membros = array_unique(array_merge([$user->id], $request->membros));
        foreach ($membros as $membroId) {
            ConversaMembro::create([
                'conversa_id' => $conversa->id,
                'user_id' => $membroId,
                'entrou_em' => now(),
            ]);
        }

        return response()->json(['conversa' => $conversa->load('membros.user')]);
    }

    public function show(Conversa $conversa, Request $request): JsonResponse
    {
        $user = $request->user();

        $mensagens = $conversa->mensagens()
            ->whereNull('deletada_em')
            ->with(['user', 'respostaA.user', 'leituras'])
            ->orderBy('created_at')
            ->paginate(50);

        // Marca como lidas
        $conversa->mensagens()
            ->whereNull('deletada_em')
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('leituras', fn($q) => $q->where('user_id', $user->id))
            ->each(fn($msg) => $msg->leituras()->create([
                'user_id' => $user->id,
                'lida_em' => now(),
            ]));

        return response()->json(['mensagens' => $mensagens]);
    }
}
