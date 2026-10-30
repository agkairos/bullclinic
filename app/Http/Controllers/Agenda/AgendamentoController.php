<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Especialidade;
use App\Models\HorarioAtendimento;
use App\Models\TipoServico;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DisponibilidadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgendamentoController extends Controller
{
    public function index(Request $request): Response
    {
        $semana = $request->semana ?? now()->startOfWeek()->toDateString();
        $clinicaId = $request->user()->clinica_ativa_id;

        $inicio = \Carbon\Carbon::parse($semana)->startOfWeek();
        $fim = $inicio->copy()->endOfWeek();

        $agendamentos = Agendamento::with(['cliente', 'paciente', 'veterinario', 'tipoServico'])
            ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
            ->when($request->veterinario_id, fn($q) => $q->where('veterinario_id', $request->veterinario_id))
            ->get();

        // Veterinários da clínica com suas especialidades
        $veterinariosQuery = User::with(['especialidades' => function ($q) use ($clinicaId) {
                $q->wherePivot('clinica_id', $clinicaId);
            }])
            ->whereHas('userClinicas', fn($q) => $q
                ->where('clinica_id', $clinicaId)
                ->where('ativo', true)
            );

        // Filtro por especialidade
        if ($request->especialidade_id) {
            $veterinariosQuery->whereHas('especialidades', fn($q) => $q
                ->where('especialidade_id', $request->especialidade_id)
                ->where('user_especialidades.clinica_id', $clinicaId)
            );
        }

        $veterinarios = $veterinariosQuery->get(['id', 'name', 'avatar', 'crmv']);

        // Mapear especialidades de cada veterinário
        $veterinarios = $veterinarios->map(function ($vet) {
            return [
                'id' => $vet->id,
                'name' => $vet->name,
                'avatar' => $vet->avatar,
                'crmv' => $vet->crmv,
                'especialidades' => $vet->especialidades->pluck('nome')->toArray(),
            ];
        });

        $tiposServico = TipoServico::where('ativo', true)->get(['id', 'nome', 'cor', 'duracao_minutos', 'preco']);

        // Especialidades ativas da clínica
        $especialidades = Especialidade::where('clinica_id', $clinicaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        // Dias da semana com ao menos um horário ativo na clínica (0=dom … 6=sáb)
        $diasAtivos = HorarioAtendimento::where('clinica_id', $clinicaId)
            ->where('ativo', true)
            ->pluck('dia_semana')
            ->unique()
            ->values();

        return Inertia::render('Agenda/Index', [
            'agendamentos' => $agendamentos,
            'veterinarios' => $veterinarios,
            'tipos_servico' => $tiposServico,
            'especialidades' => $especialidades,
            'semana_inicio' => $inicio->toDateString(),
            'dias_ativos' => $diasAtivos,
        ]);
    }

    /** GET /agendamentos/disponibilidade?veterinario_id=1&data=2026-03-28 */
    public function disponibilidade(Request $request): JsonResponse
    {
        $request->validate([
            'veterinario_id' => ['required', 'integer'],
            'data'           => ['required', 'date'],
        ]);

        $slots = (new DisponibilidadeService())->slotsLivres(
            (int) $request->veterinario_id,
            $request->data
        );

        return response()->json($slots);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'veterinario_id' => ['required', 'exists:users,id'],
            'tipo_servico_id' => ['nullable', 'exists:tipos_servico,id'],
            'data' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'primeira_consulta' => ['boolean'],
            'urgencia' => ['boolean'],
            'observacoes_publicas' => ['nullable', 'string'],
            'observacoes_internas' => ['nullable', 'string'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $duracao = \Carbon\Carbon::parse($data['hora_inicio'])
            ->diffInMinutes(\Carbon\Carbon::parse($data['hora_fim']));
        $data['duracao_estimada_minutos'] = $duracao;

        $agendamento = Agendamento::create($data);
        AuditService::criado($agendamento);

        return back()->with('success', 'Agendamento criado.');
    }

    public function update(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'veterinario_id' => ['required', 'exists:users,id'],
            'tipo_servico_id' => ['nullable', 'exists:tipos_servico,id'],
            'data' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'primeira_consulta' => ['boolean'],
            'urgencia' => ['boolean'],
            'observacoes_publicas' => ['nullable', 'string'],
            'observacoes_internas' => ['nullable', 'string'],
            'valor_estimado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $antes = $agendamento->toArray();
        $agendamento->update($data);
        AuditService::editado($agendamento, $antes);

        return back()->with('success', 'Agendamento atualizado.');
    }

    public function atualizarStatus(Request $request, Agendamento $agendamento): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['confirmado', 'aguardando', 'em_atendimento', 'finalizado', 'cancelado'])],
        ]);

        $timestamps = [
            'confirmado' => 'confirmado_em',
            'aguardando' => 'checkin_em',
            'em_atendimento' => 'atendimento_iniciado_em',
            'finalizado' => 'atendimento_finalizado_em',
            'cancelado' => 'cancelado_em',
        ];

        $update = ['status' => $request->status];

        if (isset($timestamps[$request->status])) {
            $update[$timestamps[$request->status]] = now();
        }

        if ($request->status === 'cancelado') {
            $update['cancelado_por_id'] = $request->user()->id;
        }

        $agendamento->update($update);

        return response()->json(['agendamento' => $agendamento->fresh()]);
    }

    public function destroy(Agendamento $agendamento): RedirectResponse
    {
        AuditService::excluido($agendamento);
        $agendamento->delete();

        return back()->with('success', 'Agendamento removido.');
    }
}
