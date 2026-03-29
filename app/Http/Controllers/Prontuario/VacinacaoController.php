<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Vacinacao;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacinacaoController extends Controller
{
    public function store(Request $request, Consulta $consulta): JsonResponse
    {
        $data = $request->validate([
            'vacina_nome' => ['required', 'string', 'max:255'],
            'fabricante' => ['nullable', 'string', 'max:255'],
            'lote' => ['nullable', 'string', 'max:100'],
            'data_aplicacao' => ['required', 'date'],
            'proxima_dose_em' => ['nullable', 'date', 'after:data_aplicacao'],
            'observacoes' => ['nullable', 'string'],
            'reacoes_adversas' => ['nullable', 'string'],
        ]);

        $vacinacao = Vacinacao::create([
            'clinica_id' => $request->user()->clinica_ativa_id,
            'consulta_id' => $consulta->id,
            'paciente_id' => $consulta->paciente_id,
            'veterinario_id' => $request->user()->id,
            ...$data,
        ]);

        AuditService::criado($vacinacao);

        return response()->json(['vacinacao' => $vacinacao]);
    }

    public function destroy(Vacinacao $vacinacao): JsonResponse
    {
        $vacinacao->delete();
        return response()->json(['ok' => true]);
    }
}
