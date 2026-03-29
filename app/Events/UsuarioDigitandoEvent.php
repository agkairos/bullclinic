<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsuarioDigitandoEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $conversaId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly bool $digitando,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversa.{$this->conversaId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'digitando';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'digitando' => $this->digitando,
        ];
    }
}
