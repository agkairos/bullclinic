<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Procedimento;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcedimentoController extends Controller
{
    public function store(Request $request, Consulta $consulta): JsonResponse
    {
        $data = $request->validate([
            'tipo_procedimento' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'status' => ['in:planejado,realizado,cancelado'],
            'realizado_em' => ['nullable', 'date'],
            'valor_cobrado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $procedimento = Procedimento::create([
            'clinica_id' => $request->user()->clinica_ativa_id,
            'consulta_id' => $consulta->id,
            'paciente_id' => $consulta->paciente_id,
            'veterinario_id' => $request->user()->id,
            ...$data,
        ]);

        AuditService::criado($procedimento);

        return response()->json(['procedimento' => $procedimento]);
    }

    public function update(Request $request, Procedimento $procedimento): JsonResponse
    {
        $data = $request->validate([
            'tipo_procedimento' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'status' => ['in:planejado,realizado,cancelado'],
            'realizado_em' => ['nullable', 'date'],
            'valor_cobrado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $procedimento->update($data);

        return response()->json(['procedimento' => $procedimento]);
    }

    public function destroy(Procedimento $procedimento): JsonResponse
    {
        $procedimento->delete();
        return response()->json(['ok' => true]);
    }
}
