<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\LancamentoFinanceiro;
use App\Models\Paciente;
use App\Models\Produto;
use App\Models\Vacinacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  public function index(Request $request): Response
  {
    $hoje = now()->toDateString();
    $user = $request->user();
    $clinicaId = $user->clinica_ativa_id;

    $agendamentosHoje = Agendamento::whereDate('data', $hoje)
      ->withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->get();

    $kpis = [
      'agendamentos_hoje' => $agendamentosHoje->count(),
      'realizados_hoje' => $agendamentosHoje->where('status', 'finalizado')->count(),
      'pendentes_hoje' => $agendamentosHoje->whereNotIn('status', ['finalizado', 'cancelado'])->count(),
      'em_atendimento' => $agendamentosHoje->where('status', 'em_atendimento')->count(),
      'em_espera' => $agendamentosHoje->where('status', 'aguardando')->count(),
      'total_pacientes' => Paciente::withoutGlobalScope('clinica')->where('clinica_id', $clinicaId)->count(),
      'novos_pacientes_hoje' => Paciente::withoutGlobalScope('clinica')
        ->where('clinica_id', $clinicaId)
        ->whereDate('created_at', $hoje)
        ->count(),
    ];

    // Taxa de comparecimento (últimos 30 dias)
    $agendamentos30d = Agendamento::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->whereDate('data', '>=', now()->subDays(30)->toDateString())
      ->whereNotIn('status', ['cancelado'])
      ->count();

    $finalizados30d = Agendamento::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->whereDate('data', '>=', now()->subDays(30)->toDateString())
      ->where('status', 'finalizado')
      ->count();

    $kpis['taxa_comparecimento'] = $agendamentos30d > 0
      ? round(($finalizados30d / $agendamentos30d) * 100)
      : 0;

    // Agendamentos do dia para sidebar
    $agendamentosDia = Agendamento::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->whereDate('data', $hoje)
      ->whereNotIn('status', ['cancelado'])
      ->with(['paciente', 'cliente', 'tipoServico', 'veterinario'])
      ->orderBy('hora_inicio')
      ->get();

    // Produtos com estoque crítico
    $estoqueCritico = Produto::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->where('ativo', true)
      ->whereColumn('quantidade_estoque', '<=', 'estoque_minimo')
      ->limit(5)
      ->get(['id', 'nome', 'quantidade_estoque', 'estoque_minimo', 'unidade_medida']);

    // Receitas vs Despesas do mês
    $inicioMes = now()->startOfMonth()->toDateString();
    $fimMes = now()->endOfMonth()->toDateString();

    $receitasMes = LancamentoFinanceiro::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->where('tipo', 'receita')
      ->where('status', 'pago')
      ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
      ->sum('valor');

    $despesasMes = LancamentoFinanceiro::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->where('tipo', 'despesa')
      ->where('status', 'pago')
      ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
      ->sum('valor');

    // Gráfico de atendimentos dos últimos 7 dias
    $atendimentos7dias = [];
    for ($i = 6; $i >= 0; $i--) {
      $data = now()->subDays($i)->toDateString();
      $count = Agendamento::withoutGlobalScope('clinica')
        ->where('clinica_id', $clinicaId)
        ->whereDate('data', $data)
        ->where('status', 'finalizado')
        ->count();

      $atendimentos7dias[] = [
        'data' => $data,
        'label' => now()->subDays($i)->format('d/m'),
        'count' => $count,
      ];
    }

    // Gráfico de receitas vs despesas por dia (últimos 30 dias)
    $financeiro30dias = LancamentoFinanceiro::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->where('status', 'pago')
      ->whereBetween('data_pagamento', [now()->subDays(29)->toDateString(), $hoje])
      ->select(
        DB::raw('DATE(data_pagamento) as data'),
        'tipo',
        DB::raw('SUM(valor) as total')
      )
      ->groupBy('data', 'tipo')
      ->orderBy('data')
      ->get();

    // Formatar dados do gráfico financeiro
    $financeiroChart = [];
    for ($i = 29; $i >= 0; $i--) {
      $data = now()->subDays($i)->toDateString();
      $receita = $financeiro30dias->where('data', $data)->where('tipo', 'receita')->sum('total');
      $despesa = $financeiro30dias->where('data', $data)->where('tipo', 'despesa')->sum('total');

      $financeiroChart[] = [
        'data' => $data,
        'label' => now()->subDays($i)->format('d/m'),
        'receitas' => (float) $receita,
        'despesas' => (float) $despesa,
      ];
    }

    // Próximas vacinações (vencendo nos próximos 30 dias)
    $proximasVacinacoes = Vacinacao::withoutGlobalScope('clinica')
      ->where('clinica_id', $clinicaId)
      ->whereNotNull('proxima_dose_em')
      ->whereBetween('proxima_dose_em', [$hoje, now()->addDays(30)->toDateString()])
      ->with(['paciente'])
      ->orderBy('proxima_dose_em')
      ->limit(5)
      ->get(['id', 'paciente_id', 'vacina_nome', 'proxima_dose_em']);

    return Inertia::render('Dashboard', [
      'kpis' => $kpis,
      'agendamentos_dia' => $agendamentosDia,
      'estoque_critico' => $estoqueCritico,
      'financeiro_mes' => [
        'receitas' => $receitasMes,
        'despesas' => $despesasMes,
        'saldo' => $receitasMes - $despesasMes,
      ],
      'atendimentos_chart' => $atendimentos7dias,
      'financeiro_chart' => $financeiroChart,
      'proximas_vacinacoes' => $proximasVacinacoes,
    ]);
  }
}

