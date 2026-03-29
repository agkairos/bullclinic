<?php

namespace App\Http\Controllers\Chat;

use App\Events\NovaMensagemEvent;
use App\Events\UsuarioDigitandoEvent;
use App\Http\Controllers\Controller;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Models\MensagemLeitura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MensagemController extends Controller
{
    public function store(Request $request, Conversa $conversa): JsonResponse
    {
        $request->validate([
            'tipo' => ['required', 'in:texto,audio,imagem'],
            'conteudo' => ['nullable', 'string', 'required_if:tipo,texto'],
            'arquivo' => ['nullable', 'file', 'required_if:tipo,audio,imagem'],
            'resposta_a_id' => ['nullable', 'exists:mensagens,id'],
        ]);

        $user = $request->user();

        $dados = [
            'conversa_id' => $conversa->id,
            'user_id' => $user->id,
            'tipo' => $request->tipo,
            'conteudo' => $request->conteudo,
            'resposta_a_id' => $request->resposta_a_id,
        ];

        if ($request->hasFile('arquivo')) {
            $arquivo = $request->file('arquivo');

            // Valida duração de áudio conforme plano
            if ($request->tipo === 'audio') {
                $plano = $user->clinicaAtiva->plano;
                $duracaoMax = $plano->chat_audio_duracao_segundos;
                $duracao = (int) $request->duracao_segundos;

                if ($duracaoMax > 0 && $duracao > $duracaoMax) {
                    return response()->json(['error' => 'Duração de áudio excede o limite do plano.'], 422);
                }
                $dados['duracao_audio_segundos'] = $duracao;
            }

            $path = $arquivo->store("clinica/{$user->clinica_ativa_id}/chat", 's3');
            $dados['arquivo_url'] = $path;
            $dados['arquivo_tamanho_bytes'] = $arquivo->getSize();

            // Atualiza storage da clínica
            $user->clinicaAtiva->increment('limite_storage_usado_gb', round($arquivo->getSize() / 1073741824, 4));
        }

        $mensagem = Mensagem::create($dados);
        $mensagem->load('user');

        // Marcar como lida pelo remetente imediatamente
        MensagemLeitura::firstOrCreate([
            'mensagem_id' => $mensagem->id,
            'user_id' => $user->id,
        ]);

        // Toca updated_at da conversa para reordenar lista
        $conversa->touch();

        // Broadcast em tempo real
        broadcast(new NovaMensagemEvent($mensagem));

        return response()->json(['mensagem' => $mensagem->load('user', 'respostaA.user')]);
    }

    public function digitando(Request $request, Conversa $conversa): JsonResponse
    {
        $user = $request->user();
        broadcast(new UsuarioDigitandoEvent(
            conversaId: $conversa->id,
            userId: $user->id,
            userName: $user->name,
            digitando: (bool) $request->input('digitando', true),
        ));

        return response()->json(['ok' => true]);
    }

    public function destroy(Mensagem $mensagem, Request $request): JsonResponse
    {
        Gate::authorize('delete', $mensagem);

        $mensagem->update(['deletada_em' => now()]);

        return response()->json(['ok' => true]);
    }

    public function fixar(Mensagem $mensagem): JsonResponse
    {
        $mensagem->update(['fixada' => ! $mensagem->fixada]);
        return response()->json(['fixada' => $mensagem->fixada]);
    }

    public function marcarLida(Mensagem $mensagem, Request $request): JsonResponse
    {
        MensagemLeitura::firstOrCreate([
            'mensagem_id' => $mensagem->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['ok' => true]);
    }
}
