<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de presença por clínica
Broadcast::channel('presenca.{clinicaId}', function ($user, $clinicaId) {
    $membro = \App\Models\UserClinica::where('user_id', $user->id)
        ->where('clinica_id', $clinicaId)
        ->where('ativo', true)
        ->exists();

    if (!$membro) return false;

    return ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatar];
});

// Canal privado de conversa — só membros ativos podem se inscrever
Broadcast::channel('conversa.{conversaId}', function ($user, $conversaId) {
    return \App\Models\ConversaMembro::where('conversa_id', $conversaId)
        ->where('user_id', $user->id)
        ->whereNull('saiu_em')
        ->exists();
});
