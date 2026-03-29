<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\CategoriaFinanceira;
use App\Models\LancamentoFinanceiro;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LancamentoController extends Controller
{
    public function index(Request $request): Response
    {
        $periodo = $request->periodo ?? now()->format('Y-m');
        [$ano, $mes] = explode('-', $periodo);

        $receitas = LancamentoFinanceiro::with(['categoria', 'cliente'])
            ->where('tipo', 'receita')
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('data_vencimento')
            ->get();

        $despesas = LancamentoFinanceiro::with(['categoria', 'fornecedor'])
            ->where('tipo', 'despesa')
            ->whereYear('data_vencimento', $ano)
            ->whereMonth('data_vencimento', $mes)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('data_vencimento')
            ->get();

        $resumo = [
            'total_receitas' => $receitas->where('status', 'pago')->sum('valor'),
            'total_despesas' => $despesas->where('status', 'pago')->sum('valor'),
            'a_receber' => $receitas->whereIn('status', ['pendente', 'atrasado'])->sum('valor'),
            'a_pagar' => $despesas->whereIn('status', ['pendente', 'atrasado'])->sum('valor'),
        ];
        $resumo['saldo'] = $resumo['total_receitas'] - $resumo['total_despesas'];

        $categorias = CategoriaFinanceira::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'tipo']);

        // Gráfico: últimos 6 meses
        $grafico = collect(range(5, 0))->map(function ($i) {
            $m = now()->subMonths($i);
            $mes = $m->format('Y-m');
            $r = LancamentoFinanceiro::where('tipo', 'receita')
                ->where('status', 'pago')
                ->whereYear('data_vencimento', $m->year)
                ->whereMonth('data_vencimento', $m->month)
                ->sum('valor');
            $d = LancamentoFinanceiro::where('tipo', 'despesa')
                ->where('status', 'pago')
                ->whereYear('data_vencimento', $m->year)
                ->whereMonth('data_vencimento', $m->month)
                ->sum('valor');
            return ['mes' => $mes, 'receitas' => (float) $r, 'despesas' => (float) $d];
        });

        return Inertia::render('Financeiro/Index', [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'resumo' => $resumo,
            'categorias' => $categorias,
            'periodo' => $periodo,
            'grafico' => $grafico,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:receita,despesa'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
            'data_pagamento' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', 'in:dinheiro,cartao_credito,cartao_debito,pix,boleto,transferencia,outro'],
            'categoria_id' => ['nullable', 'exists:categorias_financeiras,id'],
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'fornecedor_id' => ['nullable', 'exists:fornecedores,id'],
            'status' => ['required', 'in:pago,pendente,atrasado,cancelado'],
            'recorrente' => ['boolean'],
            'periodicidade' => ['nullable', 'in:mensal,anual'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $data['usuario_id'] = $request->user()->id;

        $lancamento = LancamentoFinanceiro::create($data);
        AuditService::criado($lancamento);

        return back()->with('success', 'Lançamento registrado.');
    }

    public function update(Request $request, LancamentoFinanceiro $lancamento): RedirectResponse
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
            'data_pagamento' => ['nullable', 'date'],
            'forma_pagamento' => ['nullable', 'in:dinheiro,cartao_credito,cartao_debito,pix,boleto,transferencia,outro'],
            'categoria_id' => ['nullable', 'exists:categorias_financeiras,id'],
            'status' => ['required', 'in:pago,pendente,atrasado,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $antes = $lancamento->toArray();
        $lancamento->update($data);
        AuditService::editado($lancamento, $antes);

        return back()->with('success', 'Lançamento atualizado.');
    }

    public function destroy(LancamentoFinanceiro $lancamento): RedirectResponse
    {
        AuditService::excluido($lancamento);
        $lancamento->delete();

        return back()->with('success', 'Lançamento removido.');
    }
}
