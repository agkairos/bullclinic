<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\CategoriaFinanceira;
use App\Models\Consulta;
use App\Models\LancamentoFinanceiro;
use App\Models\Paciente;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsultaController extends Controller
{
    public function index(Request $request): Response
    {
        $pacientes = Paciente::query()
            ->when($request->search, fn($q) => $q->where('nome', 'like', "%{$request->search}%"))
            ->with(['cliente', 'fotos' => fn($q) => $q->where('principal', true)])
            ->withCount('consultas')
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Prontuario/Index', ['pacientes' => $pacientes, 'filters' => $request->only('search')]);
    }

    public function show(Paciente $paciente): Response
    {
        $paciente->load([
            'cliente', 'fotos', 'tags',
            'consultas' => fn($q) => $q->latest()->with([
                'veterinario', 'receitas.itens', 'exames.tipoExame', 'procedimentos', 'vacinacoes',
            ]),
            'vacinacoes' => fn($q) => $q->latest('data_aplicacao')->with('veterinario'),
        ]);

        return Inertia::render('Prontuario/Paciente', ['paciente' => $paciente]);
    }

    public function abrir(Request $request, Paciente $paciente): RedirectResponse
    {
        $request->validate([
            'agendamento_id' => ['nullable', 'exists:agendamentos,id'],
            'tipo_consulta' => ['required', 'in:primeira_consulta,retorno,emergencia'],
        ]);

        $consulta = Consulta::create([
            'clinica_id' => $request->user()->clinica_ativa_id,
            'agendamento_id' => $request->agendamento_id,
            'paciente_id' => $paciente->id,
            'veterinario_id' => $request->user()->id,
            'tipo_consulta' => $request->tipo_consulta,
            'status' => 'em_andamento',
            'iniciada_em' => now(),
        ]);

        // Atualiza agendamento se vinculado
        if ($request->agendamento_id) {
            Agendamento::find($request->agendamento_id)?->update([
                'status' => 'em_atendimento',
                'atendimento_iniciado_em' => now(),
            ]);
        }

        AuditService::criado($consulta);

        return redirect()->route('prontuario.consulta', $consulta);
    }

    public function consulta(Consulta $consulta): Response
    {
        $consulta->load([
            'paciente.cliente', 'paciente.fotos', 'paciente.tags', 'paciente.vacinacoes',
            'veterinario', 'agendamento',
            'receitas.itens',
            'exames.tipoExame', 'exames.laboratorio', 'exames.resultado',
            'procedimentos',
            'vacinacoes',
            'documentos',
        ]);

        return Inertia::render('Prontuario/Consulta', ['consulta' => $consulta]);
    }

    public function salvar(Request $request, Consulta $consulta): JsonResponse
    {
        // Auto-save — aceita campos parciais
        $data = $request->validate([
            'anamnese' => ['nullable', 'string'],
            'exame_fisico' => ['nullable', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'prognostico' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $consulta->update($data);

        return response()->json(['saved_at' => now()->toTimeString()]);
    }

    public function finalizar(Consulta $consulta): RedirectResponse
    {
        $consulta->update([
            'status' => 'finalizada',
            'finalizada_em' => now(),
        ]);

        // Atualiza agendamento vinculado
        if ($consulta->agendamento_id) {
            Agendamento::find($consulta->agendamento_id)?->update([
                'status' => 'finalizado',
                'atendimento_finalizado_em' => now(),
            ]);
        }

        AuditService::log('finalizada', $consulta);

        // Gerar receita automaticamente se houver procedimentos com valor
        $totalProcedimentos = $consulta->procedimentos()
            ->where('status', 'realizado')
            ->whereNotNull('valor_cobrado')
            ->sum('valor_cobrado');

        if ($totalProcedimentos > 0) {
            $categoriaConsulta = CategoriaFinanceira::where('nome', 'like', '%consulta%')->first();
            LancamentoFinanceiro::create([
                'tipo' => 'receita',
                'descricao' => "Consulta #{$consulta->id} — {$consulta->paciente->nome}",
                'valor' => $totalProcedimentos,
                'data_vencimento' => now()->toDateString(),
                'status' => 'pendente',
                'categoria_id' => $categoriaConsulta?->id,
                'cliente_id' => $consulta->paciente->cliente_id,
                'usuario_id' => $consulta->veterinario_id,
            ]);
        }

        return redirect()->route('prontuario.paciente', $consulta->paciente_id)
            ->with('success', 'Consulta finalizada.');
    }
}
