<?php

namespace App\Policies;

use App\Models\LancamentoFinanceiro;
use App\Models\User;

class LancamentoFinanceiroPolicy
{
    public function update(User $user, LancamentoFinanceiro $lancamento): bool
    {
        return $user->clinica_ativa_id === $lancamento->clinica_id && $user->pode('editar_lancamentos');
    }

    public function delete(User $user, LancamentoFinanceiro $lancamento): bool
    {
        return $user->clinica_ativa_id === $lancamento->clinica_id && $user->pode('excluir_lancamentos');
    }
}
