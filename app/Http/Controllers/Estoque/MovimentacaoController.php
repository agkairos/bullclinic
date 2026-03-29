<?php

namespace App\Http\Controllers\Estoque;

use App\Http\Controllers\Controller;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\TipoMovimentacao;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovimentacaoController extends Controller
{
  public function index(Request $request): Response
  {
    $movimentacoes = MovimentacaoEstoque::with(['produto', 'tipoMovimentacao', 'usuario'])
      ->when($request->produto_id, fn($q) => $q->where('produto_id', $request->produto_id))
      ->orderByDesc('movimentado_em')
      ->paginate(20)
      ->withQueryString();

    $tiposMovimentacao = TipoMovimentacao::where('ativo', true)->orderBy('nome')->get();
    $produtos = Produto::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'unidade_medida']);

    return Inertia::render('Estoque/Movimentacoes/Index', [
      'movimentacoes' => $movimentacoes,
      'tipos_movimentacao' => $tiposMovimentacao,
      'produtos' => $produtos,
    ]);
  }

  public function store(Request $request): RedirectResponse
  {
    $data = $request->validate([
      'produto_id' => ['required', 'exists:produtos,id'],
      'tipo_movimentacao_id' => ['required', 'exists:tipos_movimentacao,id'],
      'quantidade' => ['required', 'numeric', 'min:0.001'],
      'valor_unitario' => ['nullable', 'numeric', 'min:0'],
      'observacoes' => ['nullable', 'string'],
      'movimentado_em' => ['nullable', 'date'],
    ]);

    $data['usuario_id'] = $request->user()->id;
    $data['movimentado_em'] ??= now();

    $tipo = TipoMovimentacao::find($data['tipo_movimentacao_id']);
    $produto = Produto::findOrFail($data['produto_id']);

    $movimentacao = MovimentacaoEstoque::create($data);

    // Atualiza quantidade em estoque
    if ($tipo->tipo_base === 'entrada') {
      $produto->increment('quantidade_estoque', $data['quantidade']);
    } elseif ($tipo->tipo_base === 'saida') {
      $produto->decrement('quantidade_estoque', $data['quantidade']);
    } else {
      // ajuste: quantidade informada é o novo total
      $produto->update(['quantidade_estoque' => $data['quantidade']]);
    }

    AuditService::criado($movimentacao);

    return back()->with('success', 'Movimentação registrada.');
  }
}
