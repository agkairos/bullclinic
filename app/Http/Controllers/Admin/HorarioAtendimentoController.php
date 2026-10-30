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
            ->where('clinica_id', $clinicaId)
            ->where('ativo', true)
            ->whereHas('role', fn($r) => $r->whereIn('nome', ['veterinario', 'admin', 'super_admin']))
        )->get(['id', 'name', 'crmv']);

        // Agrupado por user_id → dia_semana → array de intervalos ordenados
        $horarios = HorarioAtendimento::where('clinica_id', $clinicaId)
            ->orderBy('ordem')
            ->get()
            ->groupBy('user_id')
            ->map(fn($items) => $items->groupBy('dia_semana'));

        return Inertia::render('Admin/Horarios/Index', [
            'veterinarios' => $veterinarios,
            'horarios'     => $horarios,
        ]);
    }

    /** Salva um intervalo (criação ou atualização por user+dia+ordem) */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'                           => ['required', 'exists:users,id'],
            'dia_semana'                        => ['required', 'integer', 'min:0', 'max:6'],
            'ordem'                             => ['required', 'integer', 'min:1', 'max:2'],
            'hora_inicio'                       => ['required', 'date_format:H:i'],
            'hora_fim'                          => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracao_consulta_minutos'          => ['required', 'integer', 'min:5'],
            'intervalo_entre_consultas_minutos' => ['required', 'integer', 'min:0'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;

        $horario = HorarioAtendimento::updateOrCreate(
            [
                'clinica_id'  => $clinicaId,
                'user_id'     => $data['user_id'],
                'dia_semana'  => $data['dia_semana'],
                'ordem'       => $data['ordem'],
            ],
            [
                'hora_inicio'                       => $data['hora_inicio'],
                'hora_fim'                          => $data['hora_fim'],
                'duracao_consulta_minutos'          => $data['duracao_consulta_minutos'],
                'intervalo_entre_consultas_minutos' => $data['intervalo_entre_consultas_minutos'],
                'ativo'                             => true,
            ]
        );

        return response()->json(['horario' => $horario]);
    }

    /** Remove um intervalo específico */
    public function destroy(HorarioAtendimento $horarioAtendimento): JsonResponse
    {
        $horarioAtendimento->delete();
        return response()->json(['ok' => true]);
    }

    /** Ativa ou desativa TODOS os intervalos de um dia */
    public function toggleDia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => ['required', 'exists:users,id'],
            'dia_semana' => ['required', 'integer', 'min:0', 'max:6'],
            'ativo'      => ['required', 'boolean'],
        ]);

        $clinicaId = $request->user()->clinica_ativa_id;

        HorarioAtendimento::where('clinica_id', $clinicaId)
            ->where('user_id', $data['user_id'])
            ->where('dia_semana', $data['dia_semana'])
            ->update(['ativo' => $data['ativo']]);

        return response()->json(['ok' => true]);
    }
}
