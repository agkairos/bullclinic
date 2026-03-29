<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado de conversa — só membros ativos podem se inscrever
Broadcast::channel('conversa.{conversaId}', function ($user, $conversaId) {
    return \App\Models\ConversaMembro::where('conversa_id', $conversaId)
        ->where('user_id', $user->id)
        ->whereNull('saiu_em')
        ->exists();
});
