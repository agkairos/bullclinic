<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HorarioAtendimento;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HorarioAtendimentoController extends Controller
{
    public function index(Request $request): Response
    {
        $clinicaId = $request->user()->clinica_ativa_id;

        $veterinarios = User::whereHas('userClinicas', fn($q) => $q
            ->where('clinica_id', $clinicaId)->where('ativo', true)
        )->get(['id', 'name', 'crmv']);

        $horarios = HorarioAtendimento::where('clinica_id', $clinicaId)
            ->get()
            ->groupBy('user_id');

        return Inertia::render('Admin/Horarios/Index', [
            'veterinarios' => $veterinarios,
            'horarios'     => $horarios,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'                           => ['required', 'exists:users,id'],
            'dia_semana'                        => ['required', 'integer', 'min:0', 'max:6'],
            'hora_inicio'                       => ['required', 'date_format:H:i'],
            'hora_fim'                          => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracao_consulta_minutos'          => ['required', 'integer', 'min:5'],
            'intervalo_entre_consultas_minutos' => ['required', 'integer', 'min:0'],
        ]);

        $data['clinica_id'] = $request->user()->clinica_ativa_id;
        $data['ativo'] = true;

        // Remove horário existente para o mesmo vet+dia e recria
        HorarioAtendimento::where('clinica_id', $data['clinica_id'])
            ->where('user_id', $data['user_id'])
            ->where('dia_semana', $data['dia_semana'])
            ->delete();

        $horario = HorarioAtendimento::create($data);

        return response()->json(['horario' => $horario]);
    }

    public function destroy(HorarioAtendimento $horarioAtendimento): JsonResponse
    {
        $horarioAtendimento->delete();

        return response()->json(['ok' => true]);
    }
}
