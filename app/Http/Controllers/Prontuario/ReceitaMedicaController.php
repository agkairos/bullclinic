<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\ReceitaMedica;
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
            'itens.*.medicamento_nome' => ['required', 'string'],
            'itens.*.dosagem' => ['nullable', 'string'],
            'itens.*.forma_farmaceutica' => ['nullable', 'string'],
            'itens.*.quantidade' => ['nullable', 'string'],
            'itens.*.frequencia' => ['nullable', 'string'],
            'itens.*.duracao_tratamento' => ['nullable', 'string'],
            'itens.*.via_administracao' => ['nullable', 'string'],
            'itens.*.instrucoes_especificas' => ['nullable', 'string'],
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

        foreach ($data['itens'] as $ordem => $item) {
            $receita->itens()->create([...$item, 'ordem' => $ordem]);
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
