<?php

namespace App\Policies;

use App\Models\Documento;
use App\Models\User;

class DocumentoPolicy
{
    public function delete(User $user, Documento $documento): bool
    {
        return $user->clinica_ativa_id === $documento->clinica_id || $user->isSuperAdmin();
    }
}
