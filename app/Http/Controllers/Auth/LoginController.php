<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->update(['ultimo_acesso_em' => now()]);

        AuditService::log('login');

        // Se não tem clínica ativa, redireciona para seleção
        if (! $user->clinica_ativa_id) {
            $primeiraClinica = $user->clinicas()->wherePivot('ativo', true)->first();
            if ($primeiraClinica) {
                $user->update(['clinica_ativa_id' => $primeiraClinica->id]);
            } else {
                return redirect()->route('onboarding.clinica');
            }
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditService::log('logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
