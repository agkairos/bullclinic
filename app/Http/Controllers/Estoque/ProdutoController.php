<?php

namespace App\Http\Controllers\Estoque;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProduto;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProdutoController extends Controller
{
  public function index(Request $request): Response|\Illuminate\Http\JsonResponse
  {
    $produtos = Produto::query()
      ->when($request->search, fn($q) => $q->where('nome', 'like', "%{$request->search}%")
        ->orWhere('codigo_barras', 'like', "%{$request->search}%"))
      ->when($request->categoria_id, fn($q) => $q->where('categoria_id', $request->categoria_id))
      ->when($request->tipo, fn($q) => $q->where('tipo', $request->tipo))
      ->when($request->tab === 'baixo_estoque', fn($q) => $q->whereColumn('quantidade_estoque', '<=', 'estoque_minimo'))
      ->when($request->has('com_estoque'), fn($q) => $q->where('quantidade_estoque', '>', 0))
      ->with(['categoria', 'fornecedor'])
      ->orderBy('nome');

    // Se requisição for JSON, retornar dados sem paginação
    if ($request->wantsJson()) {
      return response()->json([
        'produtos' => $produtos->get(['id', 'nome', 'preco_venda', 'quantidade_estoque', 'unidade_medida'])
      ]);
    }

    $produtos = $produtos->paginate(20)->withQueryString();

    $categorias = CategoriaProduto::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']);
    $fornecedores = Fornecedor::where('ativo', true)->orderBy('razao_social')->get(['id', 'razao_social']);

    return Inertia::render('Estoque/Index', [
      'produtos' => $produtos,
      'categorias' => $categorias,
      'fornecedores' => $fornecedores,
      'filters' => $request->only('search', 'categoria_id', 'tipo', 'tab'),
    ]);
  }

  public function store(Request $request): RedirectResponse
  {
    $data = $request->validate([
      'nome'                   => ['required', 'string', 'max:255'],
      'tipo'                   => ['required', 'in:material,medicamento'],
      'categoria_id'           => ['nullable', 'exists:categorias_produto,id'],
      'fornecedor_id'          => ['nullable', 'exists:fornecedores,id'],
      'codigo_barras'          => ['nullable', 'string', 'max:50'],
      'unidade_medida'         => ['required', 'string', 'max:20'],
      'quantidade_estoque'     => ['required', 'numeric', 'min:0'],
      'estoque_minimo'         => ['required', 'numeric', 'min:0'],
      'preco_custo'            => ['required', 'numeric', 'min:0'],
      'preco_venda'            => ['required', 'numeric', 'min:0'],
      // Campos farmacêuticos (só relevantes quando tipo = medicamento)
      'forma_farmaceutica'     => ['nullable', 'string', 'max:100'],
      'dosagem_padrao'         => ['nullable', 'string', 'max:100'],
      'via_administracao_padrao' => ['nullable', 'string', 'max:100'],
      'frequencia_padrao'      => ['nullable', 'string', 'max:100'],
      'duracao_padrao'         => ['nullable', 'string', 'max:100'],
      'controlado'             => ['boolean'],
    ]);

    $produto = Produto::create($data);
    AuditService::criado($produto);

    return back()->with('success', 'Produto cadastrado.');
  }

  public function update(Request $request, Produto $produto): RedirectResponse
  {
    $data = $request->validate([
      'nome'                   => ['required', 'string', 'max:255'],
      'tipo'                   => ['required', 'in:material,medicamento'],
      'categoria_id'           => ['nullable', 'exists:categorias_produto,id'],
      'fornecedor_id'          => ['nullable', 'exists:fornecedores,id'],
      'codigo_barras'          => ['nullable', 'string', 'max:50'],
      'unidade_medida'         => ['required', 'string', 'max:20'],
      'estoque_minimo'         => ['required', 'numeric', 'min:0'],
      'preco_custo'            => ['required', 'numeric', 'min:0'],
      'preco_venda'            => ['required', 'numeric', 'min:0'],
      'ativo'                  => ['boolean'],
      'forma_farmaceutica'     => ['nullable', 'string', 'max:100'],
      'dosagem_padrao'         => ['nullable', 'string', 'max:100'],
      'via_administracao_padrao' => ['nullable', 'string', 'max:100'],
      'frequencia_padrao'      => ['nullable', 'string', 'max:100'],
      'duracao_padrao'         => ['nullable', 'string', 'max:100'],
      'controlado'             => ['boolean'],
    ]);

    $antes = $produto->toArray();
    $produto->update($data);
    AuditService::editado($produto, $antes);

    return back()->with('success', 'Produto atualizado.');
  }

  public function destroy(Produto $produto): RedirectResponse
  {
    AuditService::excluido($produto);
    $produto->delete();

    return back()->with('success', 'Produto removido.');
  }
}
