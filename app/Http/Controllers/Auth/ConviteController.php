<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConviteController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $user = User::where('invite_token', $token)->first();

        if (!$user) {
            return redirect('/login')->with('error', 'Link de convite inválido ou expirado.');
        }

        if ($user->invite_accepted_at) {
            return redirect('/login')->with('error', 'Este convite já foi utilizado.');
        }

        // Expiração de 72h
        if ($user->invite_sent_at && $user->invite_sent_at->diffInHours(now()) > 72) {
            return redirect('/login')->with('error', 'Este link de convite expirou. Solicite um novo convite.');
        }

        return Inertia::render('Auth/AcceptInvite', [
            'token' => $token,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $user = User::where('invite_token', $token)->first();

        if (!$user || $user->invite_accepted_at) {
            return redirect('/login')->with('error', 'Link inválido ou já utilizado.');
        }

        if ($user->invite_sent_at && $user->invite_sent_at->diffInHours(now()) > 72) {
            return redirect('/login')->with('error', 'Link expirado. Solicite um novo convite.');
        }

        $request->validate([
            'password'              => ['required', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ], [
            'password.required'  => 'A senha é obrigatória.',
            'password.min'       => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $user->update([
            'password'             => $request->password, // cast 'hashed' aplica automaticamente
            'invite_token'         => null,
            'invite_accepted_at'   => now(),
            'ativo'                => true,
            'email_verified_at'    => now(),
        ]);

        // Ativa o vínculo com a clínica
        $user->userClinicas()->update(['ativo' => true]);

        // Recarrega o usuário para garantir que as mudanças estejam refletidas
        $user->refresh();

        auth()->login($user);

        // Regenera a sessão para garantir que os dados atualizados sejam carregados
        request()->session()->regenerate();

        return redirect(route('dashboard'))->with('success', 'Bem-vindo! Sua conta foi ativada.');
    }
}
