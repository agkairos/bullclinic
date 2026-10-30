<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensagemLidaEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $mensagemId,
        public readonly int $conversaId,
        public readonly int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversa.{$this->conversaId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'mensagem-lida';
    }

    public function broadcastWith(): array
    {
        return [
            'mensagem_id' => $this->mensagemId,
            'user_id'     => $this->userId,
        ];
    }
}
