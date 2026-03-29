<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\CategoriaFinanceira;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaFinanceiraController extends Controller
{
    public function index(): Response
    {
        $categorias = CategoriaFinanceira::orderBy('tipo')->orderBy('nome')->get();

        return Inertia::render('Admin/CategoriasFinanceiras', [
            'categorias' => $categorias,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:receita,despesa,ambos'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);

        CategoriaFinanceira::create($data);

        return back()->with('success', 'Categoria criada.');
    }

    public function update(Request $request, CategoriaFinanceira $categoriaFinanceira): RedirectResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'tipo' => ['required', 'in:receita,despesa,ambos'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['boolean'],
        ]);

        $categoriaFinanceira->update($data);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(CategoriaFinanceira $categoriaFinanceira): RedirectResponse
    {
        $categoriaFinanceira->update(['ativo' => false]);

        return back()->with('success', 'Categoria desativada.');
    }
}
