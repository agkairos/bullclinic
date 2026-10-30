<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Events\NovaConversaEvent;
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
            'conversa_ativa_id' => $request->integer('conversa') ?: null,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
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
                return redirect()->route('chat.index', ['conversa' => $existente->id]);
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

        // Notifica cada membro (exceto o criador) sobre a nova conversa
        foreach ($request->membros as $membroId) {
            if ($membroId !== $user->id) {
                broadcast(new NovaConversaEvent($conversa, $membroId));
            }
        }

        return redirect()->route('chat.index', ['conversa' => $conversa->id]);
    }

    public function show(Conversa $conversa, Request $request): JsonResponse
    {
        $user = $request->user();
        $antesDoId = $request->integer('antes_do_id', 0);

        $query = $conversa->mensagens()
            ->whereNull('deletada_em')
            ->with(['user', 'respostaA.user', 'leituras'])
            ->orderByDesc('id');

        if ($antesDoId > 0) {
            $query->where('id', '<', $antesDoId);
        }

        $mensagens = $query->limit(50)->get()->reverse()->values();
        $temMais = $antesDoId > 0
            ? $conversa->mensagens()->whereNull('deletada_em')->where('id', '<', $mensagens->first()?->id ?? $antesDoId)->exists()
            : $conversa->mensagens()->whereNull('deletada_em')->count() > 50;

        // Marca como lidas apenas na carga inicial e faz broadcast para o remetente
        if (!$antesDoId) {
            $conversa->mensagens()
                ->whereNull('deletada_em')
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('leituras', fn($q) => $q->where('user_id', $user->id))
                ->each(function ($msg) use ($user, $conversa) {
                    $criada = $msg->leituras()->firstOrCreate(
                        ['user_id' => $user->id],
                        ['lida_em' => now()]
                    );
                    if ($criada->wasRecentlyCreated) {
                        broadcast(new \App\Events\MensagemLidaEvent($msg->id, $conversa->id, $user->id));
                    }
                });
        }

        return response()->json([
            'mensagens' => $mensagens,
            'tem_mais' => $temMais,
        ]);
    }

    public function update(Request $request, Conversa $conversa): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'nome'    => ['required', 'string', 'max:255'],
            'membros' => ['required', 'array', 'min:1'],
            'membros.*' => ['exists:users,id'],
        ]);

        $user = $request->user();
        abort_unless($conversa->criado_por_id === $user->id, 403);

        $conversa->update(['nome' => $request->nome]);

        // Sincroniza membros: adiciona novos, remove ausentes (exceto o criador)
        $novosIds = array_unique(array_merge([$user->id], $request->membros));
        $atuaisIds = $conversa->membros()->whereNull('saiu_em')->pluck('user_id')->toArray();

        // Adiciona quem não está
        foreach ($novosIds as $id) {
            if (!in_array($id, $atuaisIds)) {
                ConversaMembro::create([
                    'conversa_id' => $conversa->id,
                    'user_id'     => $id,
                    'entrou_em'   => now(),
                ]);
                broadcast(new NovaConversaEvent($conversa, $id));
            }
        }

        // Remove quem saiu (exceto criador)
        foreach ($atuaisIds as $id) {
            if (!in_array($id, $novosIds) && $id !== $user->id) {
                $conversa->membros()->where('user_id', $id)->update(['saiu_em' => now()]);
            }
        }

        return redirect()->route('chat.index', ['conversa' => $conversa->id]);
    }

    public function destroy(Request $request, Conversa $conversa): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($conversa->criado_por_id === $user->id) {
            // Criador: exclui a conversa inteira
            $conversa->delete();
        } else {
            // Membro comum: sai da conversa
            $conversa->membros()->where('user_id', $user->id)->update(['saiu_em' => now()]);
        }

        return redirect()->route('chat.index');
    }
}
