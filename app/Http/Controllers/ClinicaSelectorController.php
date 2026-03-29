<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicaSelectorController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['clinica_id' => ['required', 'integer']]);

        $user = $request->user();

        // Verifica se o usuário tem acesso à clínica
        $acesso = $user->userClinicas()
            ->where('clinica_id', $request->clinica_id)
            ->where('ativo', true)
            ->exists();

        if (! $acesso) {
            abort(403, 'Sem acesso a esta clínica.');
        }

        // Garante que a clínica pertence à conta ativa do usuário
        $clinica = \App\Models\Clinica::findOrFail($request->clinica_id);
        if ($user->conta_ativa_id && $clinica->conta_id !== $user->conta_ativa_id) {
            abort(403, 'Esta unidade não pertence à sua conta.');
        }

        $user->update(['clinica_ativa_id' => $request->clinica_id]);

        return redirect()->route('dashboard');
    }
}
