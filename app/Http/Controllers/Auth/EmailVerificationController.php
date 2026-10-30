<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    /** Página de aviso para verificar email */
    public function notice(): Response
    {
        return Inertia::render('Auth/VerifyEmail');
    }

    /** Verifica o email via link assinado */
    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Verifica se o hash corresponde
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Link de verificação inválido.');
        }

        // Verifica se o link expirou
        if (! $request->hasValidSignature()) {
            return redirect()->route('verification.notice')
                ->with('error', 'O link de verificação expirou. Solicite um novo.');
        }

        // Se já foi verificado
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email já verificado! Faça login.');
        }

        // Marca como verificado
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Faz login automático após verificação
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Email verificado com sucesso!');
    }

    /** Reenvia email de verificação */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link de verificação reenviado!');
    }
}

