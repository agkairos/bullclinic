<?php

namespace App\Http\Controllers\Prontuario;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\CategoriaFinanceira;
use App\Models\Consulta;
use App\Models\LancamentoFinanceiro;
use App\Models\MovimentacaoEstoque;
use App\Models\Paciente;
use App\Models\TipoMovimentacao;
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

  public function show(Request $request, Paciente $paciente): Response|RedirectResponse
  {
    // Se veio com agendamento_id, verifica se já há consulta aberta vinculada
    if ($request->agendamento_id) {
      $consultaAberta = Consulta::where('agendamento_id', $request->agendamento_id)
        ->where('status', 'em_andamento')
        ->first();

      if ($consultaAberta) {
        return redirect()->route('prontuario.consulta', $consultaAberta);
      }
    }

    $paciente->load([
      'cliente',
      'fotos',
      'tags',
      'consultas' => fn($q) => $q->latest()->with([
        'veterinario',
        'receitas.itens',
        'exames.tipoExame',
        'procedimentos',
        'vacinacoes',
      ]),
      'vacinacoes' => fn($q) => $q->latest('data_aplicacao')->with('veterinario'),
    ]);

    $agendamento = $request->agendamento_id
      ? Agendamento::find($request->agendamento_id)
      : null;

    return Inertia::render('Prontuario/Paciente', [
      'paciente' => $paciente,
      'agendamento' => $agendamento,
    ]);
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
      'paciente.cliente',
      'paciente.fotos',
      'paciente.tags',
      'paciente.vacinacoes',
      'veterinario',
      'agendamento',
      'receitas.itens',
      'exames.tipoExame',
      'exames.laboratorio',
      'exames.resultado',
      'procedimentos',
      'produtosUtilizados.produto',
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

    // ─── Cálculo do Total ────────────────────────────────────────────

    // 1. Serviço base do agendamento
    $valorServico = 0;
    if ($consulta->agendamento_id) {
      $agendamento = Agendamento::with('tipoServico')->find($consulta->agendamento_id);
      $valorServico = (float) ($agendamento?->tipoServico?->preco ?? $agendamento?->valor_estimado ?? 0);
    }

    // 2. Procedimentos realizados
    $totalProcedimentos = $consulta->procedimentos()
      ->where('status', 'realizado')
      ->whereNotNull('valor_cobrado')
      ->sum('valor_cobrado');

    // 3. Produtos utilizados na consulta
    $totalProdutosUtilizados = $consulta->produtosUtilizados()->sum('subtotal');

    // 4. Medicamentos vendidos (receitas com vendido_pela_clinica = true)
    $medicamentosVendidos = $consulta->receitas()
      ->with('itens.produto')
      ->get()
      ->flatMap->itens
      ->where('vendido_pela_clinica', true);

    $totalMedicamentosVendidos = $medicamentosVendidos->sum(function ($item) {
      // Parse quantidade que pode ser string "30 comprimidos" -> 30
      $qtd = (float) filter_var($item->quantidade ?? 1, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      $preco = (float) ($item->produto->preco_venda ?? 0);
      return $qtd * $preco;
    });

    $totalGeral = $valorServico + $totalProcedimentos + $totalProdutosUtilizados + $totalMedicamentosVendidos;

    // ─── Movimentações de Estoque ────────────────────────────────────

    // Tipo "Uso em Consulta" (produtos aplicados/utilizados)
    $tipoUsoConsulta = TipoMovimentacao::firstOrCreate(
      ['clinica_id' => $consulta->clinica_id, 'nome' => 'Uso em Consulta'],
      ['tipo_base' => 'saida', 'descricao' => 'Produto utilizado durante consulta', 'ativo' => true]
    );

    // Criar movimentações para produtos utilizados
    foreach ($consulta->produtosUtilizados as $cp) {
      MovimentacaoEstoque::create([
        'clinica_id' => $consulta->clinica_id,
        'produto_id' => $cp->produto_id,
        'tipo_movimentacao_id' => $tipoUsoConsulta->id,
        'quantidade' => -$cp->quantidade,
        'paciente_id' => $consulta->paciente_id,
        'consulta_id' => $consulta->id,
        'usuario_id' => $cp->usuario_id,
        'valor_unitario' => $cp->valor_unitario,
        'observacoes' => "Produto utilizado na consulta #{$consulta->id}" . ($cp->observacoes ? " - {$cp->observacoes}" : ''),
        'movimentado_em' => now(),
      ]);
    }

    // Tipo "Venda de Medicamento" (prescrito e vendido)
    if ($medicamentosVendidos->count() > 0) {
      $tipoVendaMedicamento = TipoMovimentacao::firstOrCreate(
        ['clinica_id' => $consulta->clinica_id, 'nome' => 'Venda de Medicamento'],
        ['tipo_base' => 'saida', 'descricao' => 'Medicamento prescrito e vendido pela clínica', 'ativo' => true]
      );

      foreach ($medicamentosVendidos as $item) {
        if (!$item->produto_id) continue;

        $qtd = (float) filter_var($item->quantidade ?? 1, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        MovimentacaoEstoque::create([
          'clinica_id' => $consulta->clinica_id,
          'produto_id' => $item->produto_id,
          'tipo_movimentacao_id' => $tipoVendaMedicamento->id,
          'quantidade' => -$qtd,
          'paciente_id' => $consulta->paciente_id,
          'consulta_id' => $consulta->id,
          'usuario_id' => auth()->id(),
          'valor_unitario' => $item->produto->preco_venda ?? 0,
          'observacoes' => "Medicamento vendido: {$item->medicamento_nome}",
          'movimentado_em' => now(),
        ]);
      }
    }

    // ─── Lançamento Financeiro ───────────────────────────────────────

    if ($totalGeral > 0) {
      $categoriaConsulta = CategoriaFinanceira::where('nome', 'like', '%consulta%')->first();
      LancamentoFinanceiro::create([
        'tipo' => 'receita',
        'descricao' => "Consulta #{$consulta->id} — {$consulta->paciente->nome}",
        'valor' => $totalGeral,
        'data_vencimento' => now()->toDateString(),
        'status' => 'pendente',
        'categoria_id' => $categoriaConsulta?->id,
        'cliente_id' => $consulta->paciente->cliente_id,
        'usuario_id' => $consulta->veterinario_id,
        'consulta_id' => $consulta->id,
      ]);
    }

    return redirect()->route('prontuario.paciente', $consulta->paciente_id)
      ->with('success', 'Consulta finalizada.');
  }
}
