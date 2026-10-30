<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RichText from '@/components/RichText.vue';

defineOptions({ layout: AppLayout });

type Lancamento = {
    id: number;
    descricao: string;
    valor: string;
    data_vencimento: string;
    data_pagamento: string | null;
    status: string;
    forma_pagamento: string | null;
    categoria: { id: number; nome: string } | null;
    observacoes: string | null;
    consulta?: {
        id: number;
        agendamento?: { tipo_servico?: { nome: string; preco: string } };
        procedimentos: Array<{ tipo_procedimento: string; valor_cobrado: string | null }>;
        produtos_utilizados: Array<{ produto: { nome: string }; quantidade: string; valor_unitario: string; subtotal: number }>;
        receitas: Array<{ itens: Array<{ medicamento_nome: string; produto?: { preco_venda: string }; vendido_pela_clinica: boolean; quantidade: string }> }>;
    } | null;
};

const props = defineProps<{
    receitas: { data: Lancamento[]; links: any[] };
    despesas: { data: Lancamento[]; links: any[] };
    resumo: { total_receitas: number; total_despesas: number; saldo: number; a_receber: number; a_pagar: number };
    categorias: Array<{ id: number; nome: string; tipo: string }>;
    periodo: string;
    grafico: Array<{ mes: string; receitas: number; despesas: number }>;
    filters: {
        periodo?: string;
        status?: string;
        data_inicio?: string;
        data_fim?: string;
    };
}>();

const abaAtiva = ref<'receitas' | 'despesas'>('receitas');
const periodo = ref(props.periodo);
const filtroStatus = ref('');

// Filtros de data
const filters = reactive({
    data_inicio: props.filters.data_inicio ?? '',
    data_fim: props.filters.data_fim ?? '',
});

function aplicarFiltros() {
    router.get('/financeiro', {
        periodo: periodo.value,
        status: filtroStatus.value || undefined,
        data_inicio: filters.data_inicio || undefined,
        data_fim: filters.data_fim || undefined,
    }, { preserveState: true });
}

function limparFiltrosData() {
    filters.data_inicio = '';
    filters.data_fim = '';
    aplicarFiltros();
}

function pageLabel(label: string): string {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}

// ── Modal novo lançamento ────────────────────────────────────────────────────
const showModal = ref(false);
const lancForm = ref({
    descricao: '', valor: '', data_vencimento: '',
    categoria_id: '', status: 'pendente', forma_pagamento: '',
    data_pagamento: '', observacoes: '',
});

function abrirNovo() {
    lancForm.value = {
        descricao: '', valor: '', data_vencimento: '',
        categoria_id: '', status: 'pendente', forma_pagamento: '',
        data_pagamento: '', observacoes: '',
    };
    showModal.value = true;
}

function salvarLancamento() {
    router.post('/financeiro', { ...lancForm.value, tipo: abaAtiva.value === 'receitas' ? 'receita' : 'despesa' }, {
        onSuccess: () => { showModal.value = false; },
    });
}

// ── Drawer edição ─────────────────────────────────────────────────────────────
const showDrawer = ref(false);
const editForm = ref<Lancamento & { categoria_id: string; data_pagamento_edit: string }>({
    id: 0, descricao: '', valor: '', data_vencimento: '', data_pagamento: null,
    status: 'pendente', forma_pagamento: null, categoria: null, observacoes: null,
    categoria_id: '', data_pagamento_edit: '',
});

function abrirEditar(l: Lancamento) {
    editForm.value = {
        ...l,
        categoria_id: l.categoria ? String(l.categoria.id) : '',
        data_pagamento_edit: l.data_pagamento ?? '',
    };
    showDrawer.value = true;
}

function salvarEdicao() {
    router.put(`/financeiro/${editForm.value.id}`, {
        descricao: editForm.value.descricao,
        valor: editForm.value.valor,
        data_vencimento: editForm.value.data_vencimento,
        data_pagamento: editForm.value.data_pagamento_edit || null,
        forma_pagamento: editForm.value.forma_pagamento,
        categoria_id: editForm.value.categoria_id || null,
        status: editForm.value.status,
        observacoes: editForm.value.observacoes,
    }, { onSuccess: () => { showDrawer.value = false; } });
}

function excluir(id: number) {
    if (!confirm('Excluir este lançamento?')) return;
    router.delete(`/financeiro/${id}`, { onSuccess: () => { showDrawer.value = false; } });
}

// ── Marcar como pago ──────────────────────────────────────────────────────────
function marcarPago(l: Lancamento) {
    router.put(`/financeiro/${l.id}`, {
        descricao: l.descricao,
        valor: l.valor,
        data_vencimento: l.data_vencimento,
        data_pagamento: new Date().toISOString().slice(0, 10),
        forma_pagamento: l.forma_pagamento,
        categoria_id: l.categoria?.id ?? null,
        status: 'pago',
        observacoes: l.observacoes,
    }, { preserveScroll: true });
}

// ── Filtros da tabela ─────────────────────────────────────────────────────────
const listaAtiva = computed(() => {
    const lista = abaAtiva.value === 'receitas' ? props.receitas.data : props.despesas.data;
    return filtroStatus.value ? lista.filter(l => l.status === filtroStatus.value) : lista;
});

// ── Gráfico receitas vs despesas ──────────────────────────────────────────────
const maxGrafico = computed(() => Math.max(...props.grafico.map(g => Math.max(g.receitas, g.despesas)), 1));

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt(v: number) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
}

function fmtData(d: string | null) {
    if (!d) return '—';
    const date = new Date(d + 'T00:00:00');
    return isNaN(date.getTime()) ? '—' : date.toLocaleDateString('pt-BR');
}

function statusClass(s: string) {
    const m: Record<string, string> = {
        pago: 'bg-green-50 text-green-600',
        pendente: 'bg-yellow-50 text-yellow-600',
        atrasado: 'bg-red-50 text-red-600',
        cancelado: 'bg-gray-100 text-gray-400',
    };
    return m[s] ?? 'bg-gray-100 text-gray-500';
}

function statusLabel(s: string) {
    return { pago: 'Pago', pendente: 'Pendente', atrasado: 'Atrasado', cancelado: 'Cancelado' }[s] ?? s;
}

function mesAbrev(m: string) {
    const [, mes] = m.split('-');
    return ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'][parseInt(mes) - 1];
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Financeiro</h1>
            <div class="flex items-center gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Período mensal</label>
                    <input type="month" v-model="periodo"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-500"
                        @change="router.get('/financeiro', { periodo }, { preserveState: true })" />
                </div>
                <span class="mt-5 text-gray-400">ou</span>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Data início</label>
                    <input v-model="filters.data_inicio" type="date"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Data fim</label>
                    <input v-model="filters.data_fim" type="date"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="mt-5 flex items-center gap-2">
                    <button @click="aplicarFiltros"
                        class="rounded-lg bg-primary-500 px-4 py-1.5 text-sm text-white hover:bg-primary-600">
                        Filtrar
                    </button>
                    <button v-if="filters.data_inicio || filters.data_fim" @click="limparFiltrosData"
                        class="rounded-lg border border-gray-200 px-4 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                        Limpar
                    </button>
                </div>
            </div>
        </div>

        <!-- Cards de resumo -->
        <div class="mb-6 grid grid-cols-5 gap-4">
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">Receitas pagas</p>
                <p class="text-xl font-bold text-green-600">{{ fmt(resumo.total_receitas) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">Despesas pagas</p>
                <p class="text-xl font-bold text-red-500">{{ fmt(resumo.total_despesas) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">Saldo</p>
                <p class="text-xl font-bold" :class="resumo.saldo >= 0 ? 'text-primary-600' : 'text-red-500'">{{ fmt(resumo.saldo) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">A receber</p>
                <p class="text-xl font-bold text-blue-500">{{ fmt(resumo.a_receber) }}</p>
            </div>
            <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400">A pagar</p>
                <p class="text-xl font-bold text-orange-500">{{ fmt(resumo.a_pagar) }}</p>
            </div>
        </div>

        <!-- Gráfico receitas vs despesas (últimos 6 meses) -->
        <div v-if="grafico.length" class="mb-6 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <p class="mb-4 text-xs font-semibold uppercase tracking-wide text-gray-400">Receitas vs Despesas — últimos 6 meses</p>
            <div class="flex items-end gap-4">
                <div v-for="g in grafico" :key="g.mes" class="flex flex-1 flex-col items-center gap-1">
                    <div class="flex w-full items-end gap-1" style="height:80px">
                        <div class="flex-1 rounded-t bg-green-400 transition-all"
                            :style="`height:${Math.round((g.receitas / maxGrafico) * 80)}px`"
                            :title="`Receitas: ${fmt(g.receitas)}`"></div>
                        <div class="flex-1 rounded-t bg-red-300 transition-all"
                            :style="`height:${Math.round((g.despesas / maxGrafico) * 80)}px`"
                            :title="`Despesas: ${fmt(g.despesas)}`"></div>
                    </div>
                    <span class="text-[10px] text-gray-400">{{ mesAbrev(g.mes) }}</span>
                </div>
            </div>
            <div class="mt-3 flex gap-4">
                <span class="flex items-center gap-1 text-xs text-gray-500"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-green-400"></span> Receitas</span>
                <span class="flex items-center gap-1 text-xs text-gray-500"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-red-300"></span> Despesas</span>
            </div>
        </div>

        <!-- Tabs + tabela -->
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 pt-4">
                <div class="flex gap-4">
                    <button v-for="t in ['receitas', 'despesas']" :key="t" @click="abaAtiva = t as any; filtroStatus = ''"
                        class="pb-3 text-sm capitalize transition"
                        :class="abaAtiva === t ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                        {{ t }}
                    </button>
                </div>
                <div class="mb-2 flex items-center gap-2">
                    <select v-model="filtroStatus" class="rounded-lg border border-gray-200 px-2 py-1.5 text-xs outline-none focus:border-primary-500">
                        <option value="">Todos os status</option>
                        <option value="pendente">Pendente</option>
                        <option value="pago">Pago</option>
                        <option value="atrasado">Atrasado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nova {{ abaAtiva === 'receitas' ? 'receita' : 'despesa' }}
                    </button>
                </div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Descrição</th>
                        <th class="px-5 py-3 text-left">Categoria</th>
                        <th class="px-5 py-3 text-left">Vencimento</th>
                        <th class="px-5 py-3 text-right">Valor</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!listaAtiva.length">
                        <td colspan="6" class="py-10 text-center text-gray-400">Nenhum lançamento no período</td>
                    </tr>
                    <tr v-for="l in listaAtiva" :key="l.id" class="cursor-pointer hover:bg-gray-50/50" @click="abrirEditar(l)">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ l.descricao }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ l.categoria?.nome ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ fmtData(l.data_vencimento) }}</td>
                        <td class="px-5 py-3 text-right font-semibold" :class="abaAtiva === 'receitas' ? 'text-green-600' : 'text-red-500'">{{ fmt(parseFloat(l.valor)) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(l.status)">{{ statusLabel(l.status) }}</span>
                        </td>
                        <td class="px-5 py-3 text-right" @click.stop>
                            <button v-if="l.status !== 'pago' && l.status !== 'cancelado'"
                                @click="marcarPago(l)"
                                class="rounded-lg border border-green-200 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-600 hover:bg-green-100">
                                Marcar pago
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginação -->
            <div v-if="(abaAtiva === 'receitas' ? receitas.links : despesas.links)?.length > 3" class="flex justify-center gap-1 border-t border-gray-50 px-5 py-3">
                <template v-for="link in (abaAtiva === 'receitas' ? receitas.links : despesas.links)" :key="link.label">
                    <button v-if="link.url" @click="router.get(link.url)"
                        class="rounded px-2.5 py-1 text-xs"
                        :class="link.active ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'">
                        {{ pageLabel(link.label) }}
                    </button>
                    <span v-else class="px-1 text-xs text-gray-300">{{ pageLabel(link.label) }}</span>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Novo Lançamento -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Nova {{ abaAtiva === 'receitas' ? 'receita' : 'despesa' }}</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Descrição *</label>
                    <input v-model="lancForm.descricao" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Valor *</label>
                        <input v-model="lancForm.valor" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Vencimento *</label>
                        <input v-model="lancForm.data_vencimento" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Categoria</label>
                        <select v-model="lancForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhuma</option>
                            <option v-for="c in categorias.filter(c => c.tipo === (abaAtiva === 'receitas' ? 'receita' : 'despesa') || c.tipo === 'ambos')" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Status *</label>
                        <select v-model="lancForm.status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                            <option value="atrasado">Atrasado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Forma de pagamento</label>
                    <select v-model="lancForm.forma_pagamento" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                        <option value="">—</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="pix">PIX</option>
                        <option value="cartao_credito">Cartão de crédito</option>
                        <option value="cartao_debito">Cartão de débito</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Observações</label>
                    <RichText v-model="lancForm.observacoes" min-height="60px" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarLancamento" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Drawer Edição -->
    <Transition enter-from-class="translate-x-full" enter-active-class="transition-transform duration-300" leave-to-class="translate-x-full" leave-active-class="transition-transform duration-300">
        <div v-if="showDrawer" class="fixed inset-0 z-50 flex justify-end">
            <div class="absolute inset-0 bg-black/20" @click="showDrawer = false"></div>
            <div class="relative flex h-full w-115 flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-800">Editar lançamento</h2>
                    <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                    <!-- Detalhamento da consulta -->
                    <div v-if="editForm.consulta" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h3 class="mb-3 text-xs font-semibold text-gray-700">Detalhamento da consulta</h3>
                        <div class="space-y-2 text-xs">
                            <!-- Serviço base -->
                            <div v-if="editForm.consulta.agendamento?.tipo_servico" class="flex items-center justify-between">
                                <span class="text-gray-600">{{ editForm.consulta.agendamento.tipo_servico.nome }}</span>
                                <span class="font-medium text-gray-800">{{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(editForm.consulta.agendamento.tipo_servico.preco)) }}</span>
                            </div>

                            <!-- Procedimentos -->
                            <div v-for="p in editForm.consulta.procedimentos.filter(p => p.valor_cobrado)" :key="'proc-' + p.tipo_procedimento" class="flex items-center justify-between">
                                <span class="text-gray-600">Procedimento: {{ p.tipo_procedimento }}</span>
                                <span class="font-medium text-gray-800">{{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(p.valor_cobrado || '0')) }}</span>
                            </div>

                            <!-- Produtos utilizados -->
                            <div v-for="prod in editForm.consulta.produtos_utilizados" :key="'prod-' + prod.produto.nome" class="flex items-center justify-between">
                                <span class="text-gray-600">Item: {{ prod.produto.nome }} ({{ prod.quantidade }}x)</span>
                                <span class="font-medium text-gray-800">{{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(prod.subtotal) }}</span>
                            </div>

                            <!-- Medicamentos vendidos -->
                            <template v-for="(receita, ri) in editForm.consulta.receitas" :key="'rec-' + ri">
                                <div v-for="(item, ii) in receita.itens.filter(i => i.vendido_pela_clinica && i.produto)" :key="'med-' + ii" class="flex items-center justify-between">
                                    <span class="text-gray-600">Medicamento: {{ item.medicamento_nome }} ({{ item.quantidade }})</span>
                                    <span class="font-medium text-gray-800">
                                        {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
                                            parseFloat(item.quantidade || '1') * parseFloat(item.produto?.preco_venda || '0')
                                        ) }}
                                    </span>
                                </div>
                            </template>

                            <div class="mt-3 flex items-center justify-between border-t border-gray-300 pt-2 font-semibold text-gray-800">
                                <span>Total</span>
                                <span>{{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(editForm.valor)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Descrição *</label>
                        <input v-model="editForm.descricao" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Valor *</label>
                            <input v-model="editForm.valor" type="number" step="0.01" min="0.01" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Vencimento *</label>
                            <input v-model="editForm.data_vencimento" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Data de pagamento</label>
                            <input v-model="editForm.data_pagamento_edit" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Status *</label>
                            <select v-model="editForm.status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option value="pendente">Pendente</option>
                                <option value="pago">Pago</option>
                                <option value="atrasado">Atrasado</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Categoria</label>
                        <select v-model="editForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhuma</option>
                            <option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Forma de pagamento</label>
                        <select v-model="editForm.forma_pagamento" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">—</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="pix">PIX</option>
                            <option value="cartao_credito">Cartão de crédito</option>
                            <option value="cartao_debito">Cartão de débito</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Observações</label>
                        <RichText v-model="editForm.observacoes" min-height="80px" />
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                    <button @click="excluir(editForm.id)" class="rounded-lg border border-red-200 px-4 py-2 text-sm text-red-500 hover:bg-red-50">Excluir</button>
                    <div class="flex gap-2">
                        <button @click="showDrawer = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                        <button @click="salvarEdicao" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
