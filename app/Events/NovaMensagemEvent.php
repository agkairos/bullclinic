<?php

namespace App\Events;

use App\Models\Mensagem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaMensagemEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Mensagem $mensagem)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversa.{$this->mensagem->conversa_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nova-mensagem';
    }

    public function broadcastWith(): array
    {
        return [
            'mensagem' => [
                'id' => $this->mensagem->id,
                'conversa_id' => $this->mensagem->conversa_id,
                'user_id' => $this->mensagem->user_id,
                'tipo' => $this->mensagem->tipo,
                'conteudo' => $this->mensagem->conteudo,
                'arquivo_url' => $this->mensagem->arquivo_url,
                'duracao_audio_segundos' => $this->mensagem->duracao_audio_segundos,
                'fixada' => $this->mensagem->fixada,
                'created_at' => $this->mensagem->created_at->toISOString(),
                'user' => [
                    'id' => $this->mensagem->user->id,
                    'name' => $this->mensagem->user->name,
                    'avatar' => $this->mensagem->user->avatar,
                ],
            ],
        ];
    }
}
