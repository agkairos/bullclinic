<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    produtos: {
        data: Array<{
            id: number;
            nome: string;
            quantidade_estoque: string;
            estoque_minimo: string;
            unidade_medida: string;
            preco_custo?: string;
            preco_venda?: string;
            codigo_barras?: string;
            ativo?: boolean;
            created_at: string;
            categoria: { id: number; nome: string } | null;
            fornecedor: { id: number; razao_social: string } | null;
        }>;
        total: number;
    };
    categorias: Array<{ id: number; nome: string }>;
    fornecedores: Array<{ id: number; razao_social: string }>;
    filters: { search?: string; categoria_id?: string; tab?: string };
}>();

const tab = ref<'todos' | 'baixo_estoque'>(props.filters.tab === 'baixo_estoque' ? 'baixo_estoque' : 'todos');
const search = ref(props.filters.search ?? '');

function changeTab(t: string) {
    tab.value = t as any;
    router.get('/estoque', { tab: t, search: search.value }, { preserveState: true });
}

function buscar() {
    router.get('/estoque', { tab: tab.value, search: search.value }, { preserveState: true });
}

function statusBadge(p: typeof props.produtos.data[0]) {
    const q = parseFloat(p.quantidade_estoque);
    const min = parseFloat(p.estoque_minimo);
    if (q <= 0) return { label: 'Sem estoque', class: 'bg-red-50 text-red-600' };
    if (q <= min) return { label: 'Crítico', class: 'bg-red-50 text-red-600' };
    if (q <= min * 1.5) return { label: 'Baixo', class: 'bg-yellow-50 text-yellow-600' };
    return { label: 'Em est.', class: 'bg-green-50 text-green-600' };
}

function formatData(dateStr: string) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR') + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Emoji simples por categoria
function itemIcon(nome: string) {
    const n = nome.toLowerCase();
    if (n.includes('luva') || n.includes('descartáv')) return '🧤';
    if (n.includes('máscara')) return '😷';
    if (n.includes('seringa')) return '💉';
    if (n.includes('vacina')) return '💊';
    if (n.includes('ração')) return '🐾';
    return '📦';
}

const showModal = ref(false);
const produtoForm = ref({
    nome: '', categoria_id: '', fornecedor_id: '', unidade_medida: 'un',
    quantidade_estoque: '0', estoque_minimo: '5',
    preco_custo: '', preco_venda: '', codigo_barras: '',
});

function salvarProduto() {
    router.post('/estoque', produtoForm.value, { onSuccess: () => { showModal.value = false; } });
}

// Drawer de edição
const showEditDrawer = ref(false);
const produtoEditando = ref<typeof props.produtos.data[0] | null>(null);
const editForm = ref({
    nome: '', categoria_id: '', fornecedor_id: '', unidade_medida: 'un',
    estoque_minimo: '0', preco_custo: '', preco_venda: '',
    codigo_barras: '', ativo: true,
});

function abrirEdicao(produto: typeof props.produtos.data[0]) {
    produtoEditando.value = produto;
    editForm.value = {
        nome: produto.nome,
        categoria_id: produto.categoria?.id ? String(produto.categoria.id) : '',
        fornecedor_id: produto.fornecedor?.id ? String(produto.fornecedor.id) : '',
        unidade_medida: produto.unidade_medida,
        estoque_minimo: produto.estoque_minimo,
        preco_custo: produto.preco_custo || '',
        preco_venda: produto.preco_venda || '',
        codigo_barras: produto.codigo_barras || '',
        ativo: produto.ativo !== false,
    };
    showEditDrawer.value = true;
}

function atualizarProduto() {
    if (!produtoEditando.value) return;
    router.put(`/estoque/${produtoEditando.value.id}`, editForm.value, {
        onSuccess: () => { showEditDrawer.value = false; }
    });
}

function excluirProduto() {
    if (!produtoEditando.value) return;
    if (confirm('Excluir este produto?')) {
        router.delete(`/estoque/${produtoEditando.value.id}`, {
            onSuccess: () => { showEditDrawer.value = false; }
        });
    }
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Estoque</h1>
            <div class="flex items-center gap-3">
                <Link href="/estoque/movimentacoes" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Movimentações
                </Link>
                <Link href="/estoque/fornecedores" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Fornecedores
                </Link>
                <button @click="showModal = true" class="flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo produto
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-5 flex items-center gap-6 border-b border-gray-100">
            <button
                @click="changeTab('todos')"
                class="pb-2.5 text-sm transition"
                :class="tab === 'todos' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'"
            >
                Todos
            </button>
            <button
                @click="changeTab('baixo_estoque')"
                class="flex items-center gap-1.5 pb-2.5 text-sm transition"
                :class="tab === 'baixo_estoque' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                Baixo estoque
            </button>
        </div>

        <!-- Barra de busca + total -->
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-900">{{ produtos.total }}</span>
                {{ produtos.total === 1 ? 'resultado' : 'resultados' }}
            </p>
            <div class="relative">
                <input
                    v-model="search"
                    @keydown.enter="buscar"
                    type="text"
                    placeholder="Buscar produto..."
                    class="rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 w-56"
                />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
        </div>

        <!-- Tabela -->
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="w-8 px-4 py-3">
                            <input type="checkbox" class="rounded border-gray-300">
                        </th>
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center gap-1">Item
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center gap-1">Qtde.
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">
                            <div class="flex items-center gap-1">Adicionado em
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left">Tag</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!produtos.data.length">
                        <td colspan="5" class="py-12 text-center text-sm text-gray-400">Nenhum produto encontrado</td>
                    </tr>
                    <tr v-for="p in produtos.data" :key="p.id" @click="abrirEdicao(p)" class="cursor-pointer hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3" @click.stop>
                            <input type="checkbox" class="rounded border-gray-300">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">{{ itemIcon(p.nome) }}</span>
                                <span class="font-medium text-gray-800">{{ p.nome }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ p.quantidade_estoque }} {{ p.unidade_medida }}s
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ formatData(p.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadge(p).class">
                                {{ statusBadge(p).label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Novo Produto -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Novo produto</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome *</label>
                    <input v-model="produtoForm.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Categoria</label>
                        <select v-model="produtoForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhuma</option>
                            <option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Fornecedor</label>
                        <select v-model="produtoForm.fornecedor_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhum</option>
                            <option v-for="f in fornecedores" :key="f.id" :value="String(f.id)">{{ f.razao_social }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Unidade *</label>
                        <select v-model="produtoForm.unidade_medida" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="un">un</option>
                            <option value="cx">cx</option>
                            <option value="ml">ml</option>
                            <option value="g">g</option>
                            <option value="kg">kg</option>
                            <option value="L">L</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Qtde inicial *</label>
                        <input v-model="produtoForm.quantidade_estoque" type="number" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Estoque mínimo *</label>
                        <input v-model="produtoForm.estoque_minimo" type="number" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Preço custo *</label>
                        <input v-model="produtoForm.preco_custo" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Preço venda *</label>
                        <input v-model="produtoForm.preco_venda" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Código de barras</label>
                    <input v-model="produtoForm.codigo_barras" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarProduto" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Drawer de Edição -->
    <div v-if="showEditDrawer" class="fixed inset-0 z-50 flex items-start justify-end bg-black/20" @click="showEditDrawer = false">
        <div @click.stop class="h-full w-full max-w-md overflow-y-auto bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-800">Editar produto</h2>
                <button @click="showEditDrawer = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Nome *</label>
                    <input v-model="editForm.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Categoria</label>
                    <select v-model="editForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Nenhuma</option>
                        <option v-for="c in categorias" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Fornecedor</label>
                    <select v-model="editForm.fornecedor_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                        <option value="">Nenhum</option>
                        <option v-for="f in fornecedores" :key="f.id" :value="String(f.id)">{{ f.razao_social }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Unidade *</label>
                        <select v-model="editForm.unidade_medida" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                            <option value="un">un</option>
                            <option value="cx">cx</option>
                            <option value="ml">ml</option>
                            <option value="g">g</option>
                            <option value="kg">kg</option>
                            <option value="L">L</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Estoque mínimo *</label>
                        <input v-model="editForm.estoque_minimo" type="number" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Preço custo (R$) *</label>
                        <input v-model="editForm.preco_custo" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Preço venda (R$) *</label>
                        <input v-model="editForm.preco_venda" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Código de barras</label>
                    <input v-model="editForm.codigo_barras" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div class="flex items-center gap-2">
                    <input v-model="editForm.ativo" type="checkbox" id="ativo" class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    <label for="ativo" class="text-sm font-medium text-gray-700">Produto ativo</label>
                </div>

                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <p class="text-xs font-medium text-gray-500">Quantidade em estoque</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ produtoEditando?.quantidade_estoque }} {{ produtoEditando?.unidade_medida }}</p>
                    <p class="mt-1 text-xs text-gray-500">Use "Movimentações" para ajustar o estoque</p>
                </div>
            </div>

            <div class="sticky bottom-0 border-t border-gray-100 bg-white px-6 py-4">
                <div class="flex gap-3">
                    <button @click="excluirProduto" class="flex-1 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-100">
                        Excluir
                    </button>
                    <button @click="atualizarProduto" class="flex-1 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">
                        Salvar alterações
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
