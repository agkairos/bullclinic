<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Conta;
use App\Models\Plano;
use App\Models\Role;
use App\Models\User;
use App\Models\UserClinica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($request) {
            $planoGratis = Plano::where('slug', 'gratis')->firstOrFail();

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
            ]);

            // Associa como super_admin da conta
            $roleAdmin = Role::where('nome', 'admin')->whereNull('clinica_id')->first();
            UserClinica::create([
                'user_id'    => $user->id,
                'clinica_id' => $clinica->id,
                'role_id'    => $roleAdmin->id,
                'ativo'      => true,
            ]);

            Auth::login($user);
        });

        return redirect()->route('dashboard');
    }
}
