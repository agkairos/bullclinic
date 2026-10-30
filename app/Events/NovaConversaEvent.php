<?php

namespace App\Events;

use App\Models\Conversa;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaConversaEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversa $conversa,
        public readonly int $destinatarioId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->destinatarioId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nova-conversa';
    }

    public function broadcastWith(): array
    {
        $this->conversa->load('membros.user');

        return [
            'conversa' => [
                'id'       => $this->conversa->id,
                'nome'     => $this->conversa->nome,
                'tipo'     => $this->conversa->tipo,
                'nao_lidas' => 0,
                'membros'  => $this->conversa->membros->map(fn($m) => [
                    'user' => [
                        'id'     => $m->user->id,
                        'name'   => $m->user->name,
                        'avatar' => $m->user->avatar,
                    ],
                ])->values()->toArray(),
                'mensagens' => [],
            ],
        ];
    }
}
