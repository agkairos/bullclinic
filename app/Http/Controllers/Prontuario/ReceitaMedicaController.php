<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use App\Models\ReceitaMedica;
use App\Models\TipoMovimentacao;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReceitaMedicaController extends Controller
{
    public function store(Request $request, Consulta $consulta): JsonResponse
    {
        $data = $request->validate([
            'instrucoes_gerais' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['nullable', 'exists:produtos,id'],
            'itens.*.medicamento_nome' => ['required', 'string'],
            'itens.*.dosagem' => ['nullable', 'string'],
            'itens.*.forma_farmaceutica' => ['nullable', 'string'],
            'itens.*.quantidade' => ['nullable', 'string'],
            'itens.*.frequencia' => ['nullable', 'string'],
            'itens.*.duracao_tratamento' => ['nullable', 'string'],
            'itens.*.via_administracao' => ['nullable', 'string'],
            'itens.*.instrucoes_especificas' => ['nullable', 'string'],
            'itens.*.vendido_pela_clinica' => ['nullable', 'boolean'],
        ]);

        $receita = ReceitaMedica::create([
            'clinica_id' => $request->user()->clinica_ativa_id,
            'consulta_id' => $consulta->id,
            'paciente_id' => $consulta->paciente_id,
            'veterinario_id' => $request->user()->id,
            'instrucoes_gerais' => $data['instrucoes_gerais'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
            'emitida_em' => now(),
        ]);

        // Tipo de movimentação "Prescrição" (saída) — busca ou cria
        $tipoSaida = TipoMovimentacao::firstOrCreate(
            ['clinica_id' => $request->user()->clinica_ativa_id, 'nome' => 'Prescrição'],
            ['tipo_base' => 'saida', 'descricao' => 'Saída por prescrição médica', 'ativo' => true]
        );

        foreach ($data['itens'] as $ordem => $item) {
            $receita->itens()->create([...$item, 'ordem' => $ordem]);

            // Gera movimentação de saída se vinculado a produto do estoque
            if (!empty($item['produto_id'])) {
                $produto = Produto::find($item['produto_id']);
                if ($produto) {
                    $qtd = isset($item['quantidade']) && is_numeric($item['quantidade']) ? (float) $item['quantidade'] : 1;

                    MovimentacaoEstoque::create([
                        'clinica_id' => $request->user()->clinica_ativa_id,
                        'produto_id' => $produto->id,
                        'tipo_movimentacao_id' => $tipoSaida->id,
                        'usuario_id' => $request->user()->id,
                        'paciente_id' => $consulta->paciente_id,
                        'consulta_id' => $consulta->id,
                        'quantidade' => $qtd,
                        'valor_unitario' => $produto->preco_venda,
                        'observacoes' => "Prescrição receita #{$receita->id} — {$item['medicamento_nome']}",
                        'movimentado_em' => now(),
                    ]);

                    $produto->decrement('quantidade_estoque', $qtd);
                }
            }
        }

        AuditService::criado($receita);

        return response()->json(['receita' => $receita->load('itens')]);
    }

    public function destroy(ReceitaMedica $receita): JsonResponse
    {
        AuditService::excluido($receita);
        $receita->delete();

        return response()->json(['ok' => true]);
    }
}
