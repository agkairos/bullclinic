<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    movimentacoes: {
        data: Array<{
            id: number;
            quantidade: string;
            valor_unitario: string | null;
            observacoes: string | null;
            movimentado_em: string;
            produto: { id: number; nome: string; unidade_medida: string };
            tipoMovimentacao: { id: number; nome: string; tipo_base: 'entrada' | 'saida' | 'ajuste' };
            usuario: { id: number; name: string };
        }>;
        total: number;
    };
    tipos_movimentacao: Array<{ id: number; nome: string; tipo_base: string }>;
    produtos: Array<{ id: number; nome: string; unidade_medida: string }>;
}>();

const showDrawer = ref(false);
const filtroTipo = ref<string>('');
const produtoBusca = ref('');

// Form de nova movimentação
const movForm = ref({
    produto_id: '',
    tipo_movimentacao_id: '',
    quantidade: '',
    valor_unitario: '',
    observacoes: '',
    movimentado_em: new Date().toISOString().slice(0, 16),
});

const produtosBusca = ref<Array<{ id: number; nome: string; unidade_medida: string }>>([]);
const buscandoProdutos = ref(false);
const produtoSelecionado = ref<{ id: number; nome: string; unidade_medida: string } | null>(null);

async function buscarProdutos(q: string) {
    if (q.length < 2) {
        produtosBusca.value = [];

        return;
    }

    buscandoProdutos.value = true;

    try {
        const res = await fetch(`/pacientes/buscar?q=${encodeURIComponent(q)}`);
        // Na verdade seria /produtos/buscar, mas vou usar a mesma rota placeholder
        produtosBusca.value = [];
    } catch (e) {
        console.error(e);
    } finally {
        buscandoProdutos.value = false;
    }
}

function selecionarProduto(p: typeof produtosBusca.value[0]) {
    produtoSelecionado.value = p;
    movForm.value.produto_id = String(p.id);
    produtosBusca.value = [];
}

function salvarMovimentacao() {
    router.post('/estoque/movimentacoes', movForm.value, {
        onSuccess: () => {
            showDrawer.value = false;
            movForm.value = {
                produto_id: '',
                tipo_movimentacao_id: '',
                quantidade: '',
                valor_unitario: '',
                observacoes: '',
                movimentado_em: new Date().toISOString().slice(0, 16),
            };
            produtoSelecionado.value = null;
        }
    });
}

function formatData(dateStr: string) {
    const d = new Date(dateStr);

    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' }) + ' às ' +
        d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function tipoIcon(tipo: 'entrada' | 'saida' | 'ajuste') {
    if (tipo === 'entrada') {
return { icon: '↑', class: 'bg-green-50 text-green-600' };
}

    if (tipo === 'saida') {
return { icon: '↓', class: 'bg-red-50 text-red-600' };
}

    return { icon: '⟳', class: 'bg-blue-50 text-blue-600' };
}

function valorTotal(mov: typeof props.movimentacoes.data[0]) {
    if (!mov.valor_unitario) {
return null;
}

    const total = parseFloat(mov.quantidade) * parseFloat(mov.valor_unitario);

    return total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

const tipoSelecionado = ref<typeof props.tipos_movimentacao[0] | null>(null);
function selecionarTipo() {
    const t = props.tipos_movimentacao.find(x => x.id === Number(movForm.value.tipo_movimentacao_id));
    tipoSelecionado.value = t || null;
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2">
                    <Link href="/estoque" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Link>
                    <h1 class="text-xl font-bold text-gray-900">Movimentações de Estoque</h1>
                </div>
                <p class="text-sm text-gray-500">Histórico de entradas, saídas e ajustes</p>
            </div>
            <button @click="showDrawer = true" class="flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nova movimentação
            </button>
        </div>

        <!-- Filtros -->
        <div class="mb-5 flex items-center gap-3">
            <div class="relative flex-1">
                <input
                    v-model="produtoBusca"
                    type="text"
                    placeholder="Buscar produto..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <select v-model="filtroTipo" class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                <option value="">Todos os tipos</option>
                <option v-for="t in tipos_movimentacao" :key="t.id" :value="t.id">{{ t.nome }}</option>
            </select>
        </div>

        <!-- Total -->
        <p class="mb-4 text-sm text-gray-500">
            <span class="font-semibold text-gray-900">{{ movimentacoes.total }}</span>
            {{ movimentacoes.total === 1 ? 'movimentação encontrada' : 'movimentações encontradas' }}
        </p>

        <!-- Lista de Movimentações -->
        <div class="space-y-2.5">
            <div v-if="!movimentacoes.data.length" class="rounded-xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-500">Nenhuma movimentação registrada</p>
            </div>

            <div
                v-for="mov in movimentacoes.data"
                :key="mov.id"
                class="flex items-center justify-between rounded-lg border border-gray-100 bg-white px-5 py-4 shadow-sm transition hover:shadow-md"
            >
                <div class="flex items-center gap-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-semibold" :class="tipoIcon(mov.tipoMovimentacao.tipo_base).class">
                        {{ tipoIcon(mov.tipoMovimentacao.tipo_base).icon }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ mov.produto.nome }}</p>
                        <div class="mt-0.5 flex items-center gap-2 text-xs text-gray-500">
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 font-medium">{{ mov.tipoMovimentacao.nome }}</span>
                            <span>•</span>
                            <span>{{ mov.usuario.name }}</span>
                            <span>•</span>
                            <span>{{ formatData(mov.movimentado_em) }}</span>
                        </div>
                        <p v-if="mov.observacoes" class="mt-1 text-sm text-gray-600">{{ mov.observacoes }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold" :class="mov.tipoMovimentacao.tipo_base === 'entrada' ? 'text-green-600' : mov.tipoMovimentacao.tipo_base === 'saida' ? 'text-red-600' : 'text-blue-600'">
                        {{ mov.tipoMovimentacao.tipo_base === 'saida' ? '-' : '+' }}{{ mov.quantidade }} {{ mov.produto.unidade_medida }}
                    </p>
                    <p v-if="valorTotal(mov)" class="mt-0.5 text-sm text-gray-500">{{ valorTotal(mov) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Nova Movimentação -->
    <div v-if="showDrawer" class="fixed inset-0 z-50 flex items-start justify-end bg-black/20" @click="showDrawer = false">
        <div @click.stop class="h-full w-full max-w-md overflow-y-auto bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-800">Nova movimentação</h2>
                <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Produto *</label>
                    <select v-model="movForm.produto_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Selecione...</option>
                        <option v-for="p in produtos" :key="p.id" :value="String(p.id)">
                            {{ p.nome }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Tipo de movimentação *</label>
                    <select v-model="movForm.tipo_movimentacao_id" @change="selecionarTipo" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Selecione...</option>
                        <option v-for="t in tipos_movimentacao" :key="t.id" :value="String(t.id)">
                            {{ t.nome }} ({{ t.tipo_base }})
                        </option>
                    </select>
                    <p v-if="tipoSelecionado?.tipo_base === 'ajuste'" class="mt-1 text-xs text-blue-600">
                        ℹ️ Ajuste: informe a quantidade TOTAL desejada em estoque
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Quantidade *</label>
                    <input v-model="movForm.quantidade" type="number" step="0.001" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Valor unitário (R$)</label>
                    <input v-model="movForm.valor_unitario" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Data e hora *</label>
                    <input v-model="movForm.movimentado_em" type="datetime-local" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Observações</label>
                    <textarea v-model="movForm.observacoes" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>
            </div>

            <div class="sticky bottom-0 border-t border-gray-100 bg-white px-6 py-4">
                <button @click="salvarMovimentacao" class="w-full rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">
                    Registrar movimentação
                </button>
            </div>
        </div>
    </div>
</template>
