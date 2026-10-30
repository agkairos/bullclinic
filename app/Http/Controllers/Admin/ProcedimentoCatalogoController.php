<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcedimentoCatalogo;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcedimentoCatalogoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ProcedimentosCatalogo/Index', [
            'procedimentos' => ProcedimentoCatalogo::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => ['required', 'string', 'max:255'],
            'descricao'    => ['nullable', 'string'],
            'valor_padrao' => ['nullable', 'numeric', 'min:0'],
        ]);

        $proc = ProcedimentoCatalogo::create($data);
        AuditService::criado($proc);

        return back()->with('success', 'Procedimento cadastrado.');
    }

    public function update(Request $request, ProcedimentoCatalogo $procedimentoCatalogo): RedirectResponse
    {
        $data = $request->validate([
            'nome'         => ['required', 'string', 'max:255'],
            'descricao'    => ['nullable', 'string'],
            'valor_padrao' => ['nullable', 'numeric', 'min:0'],
            'ativo'        => ['boolean'],
        ]);

        $antes = $procedimentoCatalogo->toArray();
        $procedimentoCatalogo->update($data);
        AuditService::editado($procedimentoCatalogo, $antes);

        return back()->with('success', 'Procedimento atualizado.');
    }

    public function destroy(ProcedimentoCatalogo $procedimentoCatalogo): RedirectResponse
    {
        AuditService::excluido($procedimentoCatalogo);
        $procedimentoCatalogo->delete();

        return back()->with('success', 'Procedimento removido.');
    }
}
