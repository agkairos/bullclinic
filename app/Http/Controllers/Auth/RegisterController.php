<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Conta;
use App\Models\Plano;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClinica;
use App\Notifications\NovoClienteRegistrado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users'],
            'password'     => ['required', 'min:8', 'confirmed'],
            'clinica_nome' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($request) {
            $planoGratis = Plano::where('slug','gratis')->firstOrFail();

            // Cria conta master
            $conta = Conta::create([
                'plano_id'        => $planoGratis->id,
                'nome'            => $request->clinica_nome,
                'email'           => $request->email,
                'status_pagamento' => 'trial',
                'data_trial_fim'  => now()->addDays(14),
                'ativa'           => true,
            ]);

            // Cria primeira unidade (clínica)
            $clinica = Clinica::create([
                'conta_id'  => $conta->id,
                'plano_id'  => $planoGratis->id,
                'nome'      => $request->clinica_nome,
                'slug'      => Str::slug($request->clinica_nome) . '-' . Str::random(6),
                'ativa'     => true,
            ]);

            // Cria usuário
            $user = User::create([
                'name'            => $request->name,
                'email'           => $request->email,
                'password'        => $request->password,
                'conta_ativa_id'  => $conta->id,
                'clinica_ativa_id' => $clinica->id,
                'ativo'           => true,
            ]);

            // Associa como admin da clínica
            $roleAdmin = Role::where('nome', 'admin')->whereNull('clinica_id')->first();
            UserClinica::create([
                'user_id'    => $user->id,
                'clinica_id' => $clinica->id,
                'role_id'    => $roleAdmin->id,
                'ativo'      => true,
            ]);

            return $user;
        });

        // Envia email de verificação
        $user->sendEmailVerificationNotification();

        // Notifica o admin sobre novo cadastro
        Notification::route('mail', 'contato@agenciakairos.com.br')
            ->notify(new NovoClienteRegistrado(
                $user,
                $request->clinica_nome,
                'Gratuito'
            ));

        // Faz login para acessar a página de verificação
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', 'Cadastro realizado! Verifique seu email para ativar sua conta.');
    }
}
