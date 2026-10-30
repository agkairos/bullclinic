<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\ExameSolicitado;
use App\Models\ResultadoExame;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExameController extends Controller
{
  public function store(Request $request, Consulta $consulta): JsonResponse
  {
    $data = $request->validate([
      'tipo_exame_id' => ['nullable', 'exists:tipos_exame,id'],
      'laboratorio_id' => ['nullable', 'exists:laboratorios,id'],
      'descricao' => ['nullable', 'string'],
      'instrucoes' => ['nullable', 'string'],
      'previsao_em' => ['nullable', 'date'],
    ]);

    $exame = ExameSolicitado::create([
      'clinica_id' => $request->user()->clinica_ativa_id,
      'consulta_id' => $consulta->id,
      'paciente_id' => $consulta->paciente_id,
      'veterinario_id' => $request->user()->id,
      'solicitado_em' => now(),
      ...$data,
    ]);

    AuditService::criado($exame);

    return response()->json(['exame' => $exame->load('tipoExame', 'laboratorio')]);
  }

  public function registrarResultado(Request $request, ExameSolicitado $exame): JsonResponse
  {
    $request->validate([
      'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
      'interpretacao' => ['nullable', 'string'],
    ]);

    $arquivo = $request->file('arquivo');
    $path = $arquivo->store("clinica_{$request->user()->clinica_ativa_id}/resultado_exames", 's3_images');

    $resultado = ResultadoExame::create([
      'exame_solicitado_id' => $exame->id,
      'arquivo_url' => $path,
      'arquivo_tamanho_bytes' => $arquivo->getSize(),
      'interpretacao' => $request->interpretacao,
      'recebido_em' => now(),
    ]);

    $exame->update(['status' => 'concluido', 'concluido_em' => now()]);

    // Atualiza storage da clínica
    $clinica = $request->user()->clinicaAtiva;
    $clinica->increment('limite_storage_usado_gb', round($arquivo->getSize() / 1073741824, 4));

    return response()->json(['resultado' => $resultado]);
  }

  public function destroy(ExameSolicitado $exame): JsonResponse
  {
    AuditService::excluido($exame);
    $exame->delete();

    return response()->json(['ok' => true]);
  }
}
