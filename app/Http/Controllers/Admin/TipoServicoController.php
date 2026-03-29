<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoServico;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TipoServicoController extends Controller
{
    public function index(): Response
    {
        $tipos = TipoServico::orderBy('nome')->get();

        return Inertia::render('Admin/TiposServico/Index', ['tipos' => $tipos]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'              => ['required', 'string', 'max:100'],
            'descricao'         => ['nullable', 'string', 'max:255'],
            'duracao_minutos'   => ['required', 'integer', 'min:5'],
            'preco'             => ['required', 'numeric', 'min:0'],
            'cor'               => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $tipo = TipoServico::create($data);
        AuditService::criado($tipo);

        return back()->with('success', 'Tipo de serviço criado.');
    }

    public function update(Request $request, TipoServico $tipoServico): RedirectResponse
    {
        $data = $request->validate([
            'nome'              => ['required', 'string', 'max:100'],
            'descricao'         => ['nullable', 'string', 'max:255'],
            'duracao_minutos'   => ['required', 'integer', 'min:5'],
            'preco'             => ['required', 'numeric', 'min:0'],
            'cor'               => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ativo'             => ['boolean'],
        ]);

        $antes = $tipoServico->toArray();
        $tipoServico->update($data);
        AuditService::editado($tipoServico, $antes);

        return back()->with('success', 'Tipo de serviço atualizado.');
    }

    public function destroy(TipoServico $tipoServico): RedirectResponse
    {
        AuditService::excluido($tipoServico);
        $tipoServico->delete();

        return back()->with('success', 'Tipo de serviço removido.');
    }
}
