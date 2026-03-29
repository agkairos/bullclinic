<?php

namespace App\Policies;

use App\Models\Mensagem;
use App\Models\User;

class MensagemPolicy
{
    public function delete(User $user, Mensagem $mensagem): bool
    {
        return $user->id === $mensagem->user_id || $user->isSuperAdmin();
    }

    public function fixar(User $user, Mensagem $mensagem): bool
    {
        // Apenas membros da conversa podem fixar (verificado via ConversaMembro)
        return $mensagem->conversa->membros()->where('user_id', $user->id)->exists()
            || $user->isSuperAdmin();
    }
}
