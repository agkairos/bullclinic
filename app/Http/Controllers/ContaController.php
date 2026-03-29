<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Role;
use App\Models\UserClinica;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContaController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $conta = $user->contaAtiva ?? $user->clinicaAtiva?->conta;

        if (! $conta) {
            return Inertia::render('Admin/Unidades/Index', [
                'conta'    => null,
                'unidades' => [],
            ]);
        }

        $conta->load('plano');
        $unidades = $conta->clinicas()->orderBy('nome')->get(['id','conta_id','nome','cnpj','telefone','email','cep','logradouro','numero','complemento','bairro','cidade','estado','ativa']);

        return Inertia::render('Admin/Unidades/Index', [
            'conta'    => $conta,
            'unidades' => $unidades,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $conta = $user->contaAtiva->load('plano');

        if (! $conta->podeAdicionarUnidade()) {
            return back()->with('error', "Seu plano permite no máximo {$conta->plano->limite_clinicas} unidade(s). Faça upgrade para adicionar mais.");
        }

        $request->validate([
            'nome'        => ['required', 'string', 'max:255'],
            'cnpj'        => ['nullable', 'string', 'max:20'],
            'telefone'    => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email'],
            'cep'         => ['nullable', 'string', 'max:10'],
            'logradouro'  => ['nullable', 'string', 'max:255'],
            'numero'      => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['nullable', 'string', 'max:100'],
            'cidade'      => ['nullable', 'string', 'max:100'],
            'estado'      => ['nullable', 'string', 'size:2'],
        ]);

        $clinica = Clinica::create([
            'conta_id'    => $conta->id,
            'plano_id'    => $conta->plano_id,
            'nome'        => $request->nome,
            'slug'        => Str::slug($request->nome) . '-' . Str::random(6),
            'cnpj'        => $request->cnpj,
            'telefone'    => $request->telefone,
            'email'       => $request->email,
            'cep'         => $request->cep,
            'logradouro'  => $request->logradouro,
            'numero'      => $request->numero,
            'complemento' => $request->complemento,
            'bairro'      => $request->bairro,
            'cidade'      => $request->cidade,
            'estado'      => $request->estado,
            'ativa'       => true,
        ]);

        // Associar o usuário criador como admin da nova unidade
        $roleAdmin = Role::where('nome', 'admin')->whereNull('clinica_id')->first();
        if ($roleAdmin) {
            UserClinica::create([
                'user_id'    => $user->id,
                'clinica_id' => $clinica->id,
                'role_id'    => $roleAdmin->id,
                'ativo'      => true,
            ]);
        }

        return back()->with('success', "Unidade \"{$clinica->nome}\" criada com sucesso.");
    }

    public function update(Request $request, Clinica $clinica): RedirectResponse
    {
        $this->autorizarUnidade($request, $clinica);

        $request->validate([
            'nome'        => ['required', 'string', 'max:255'],
            'cnpj'        => ['nullable', 'string', 'max:20'],
            'telefone'    => ['nullable', 'string', 'max:20'],
            'email'       => ['nullable', 'email'],
            'cep'         => ['nullable', 'string', 'max:10'],
            'logradouro'  => ['nullable', 'string', 'max:255'],
            'numero'      => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['nullable', 'string', 'max:100'],
            'cidade'      => ['nullable', 'string', 'max:100'],
            'estado'      => ['nullable', 'string', 'size:2'],
        ]);

        $clinica->update($request->only('nome', 'cnpj', 'telefone', 'email', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado'));

        return back()->with('success', 'Unidade atualizada.');
    }

    public function ativar(Request $request, Clinica $clinica): RedirectResponse
    {
        $this->autorizarUnidade($request, $clinica);

        $clinica->update(['ativa' => ! $clinica->ativa]);
        $status = $clinica->ativa ? 'ativada' : 'desativada';

        return back()->with('success', "Unidade {$status}.");
    }

    private function autorizarUnidade(Request $request, Clinica $clinica): void
    {
        $user = $request->user();
        if ($clinica->conta_id !== $user->conta_ativa_id) {
            abort(403, 'Esta unidade não pertence à sua conta.');
        }
    }
}
