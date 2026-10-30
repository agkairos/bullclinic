<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\ConsultaProduto;
use App\Models\Produto;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaProdutoController extends Controller
{
  public function store(Request $request, Consulta $consulta): JsonResponse
  {
    $data = $request->validate([
      'produto_id' => ['required', 'exists:produtos,id'],
      'quantidade' => ['required', 'numeric', 'min:0.001'],
      'valor_unitario' => ['required', 'numeric', 'min:0'],
      'aplicado_na_clinica' => ['boolean'],
      'observacoes' => ['nullable', 'string', 'max:1000'],
    ]);

    // Validar que a consulta está em andamento
    if ($consulta->status !== 'em_andamento') {
      return response()->json(['message' => 'Só é possível adicionar itens em consultas em andamento.'], 422);
    }

    // Validar estoque disponível
    $produto = Produto::find($data['produto_id']);
    if ($produto->quantidade_estoque < $data['quantidade']) {
      return response()->json([
        'message' => "Estoque insuficiente. Disponível: {$produto->quantidade_estoque}"
      ], 422);
    }

    $consultaProduto = ConsultaProduto::create([
      'clinica_id' => $consulta->clinica_id,
      'consulta_id' => $consulta->id,
      'produto_id' => $data['produto_id'],
      'quantidade' => $data['quantidade'],
      'valor_unitario' => $data['valor_unitario'],
      'aplicado_na_clinica' => $data['aplicado_na_clinica'] ?? true,
      'observacoes' => $data['observacoes'] ?? null,
      'usuario_id' => auth()->id(),
    ]);

    AuditService::log('adicionou item à consulta', $consultaProduto);

    return response()->json([
      'success' => true,
      'produto' => $consultaProduto->load('produto'),
    ]);
  }

  public function update(Request $request, Consulta $consulta, ConsultaProduto $consultaProduto): JsonResponse
  {
    $data = $request->validate([
      'quantidade' => ['required', 'numeric', 'min:0.001'],
      'valor_unitario' => ['required', 'numeric', 'min:0'],
      'aplicado_na_clinica' => ['boolean'],
      'observacoes' => ['nullable', 'string', 'max:1000'],
    ]);

    // Validar que pertence à consulta
    if ($consultaProduto->consulta_id !== $consulta->id) {
      return response()->json(['message' => 'Item não pertence a esta consulta.'], 403);
    }

    // Validar que a consulta está em andamento
    if ($consulta->status !== 'em_andamento') {
      return response()->json(['message' => 'Só é possível editar itens em consultas em andamento.'], 422);
    }

    // Validar estoque disponível (considerando que já estava usando uma quantidade)
    $produto = $consultaProduto->produto;
    $estoqueDisponivel = $produto->quantidade_estoque + $consultaProduto->quantidade;

    if ($estoqueDisponivel < $data['quantidade']) {
      return response()->json([
        'message' => "Estoque insuficiente. Disponível: {$estoqueDisponivel}"
      ], 422);
    }

    $consultaProduto->update($data);

    AuditService::log('atualizou item da consulta', $consultaProduto);

    return response()->json([
      'success' => true,
      'produto' => $consultaProduto->fresh()->load('produto'),
    ]);
  }

  public function destroy(Consulta $consulta, ConsultaProduto $consultaProduto): JsonResponse
  {
    // Validar que pertence à consulta
    if ($consultaProduto->consulta_id !== $consulta->id) {
      return response()->json(['message' => 'Item não pertence a esta consulta.'], 403);
    }

    // Validar que a consulta está em andamento
    if ($consulta->status !== 'em_andamento') {
      return response()->json(['message' => 'Só é possível remover itens em consultas em andamento.'], 422);
    }

    AuditService::log('removeu item da consulta', $consultaProduto);

    $consultaProduto->delete();

    return response()->json(['success' => true]);
  }
}

