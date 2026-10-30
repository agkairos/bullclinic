<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Produto = {
    id: number;
    nome: string;
    tipo: 'material' | 'medicamento';
    quantidade_estoque: string;
    estoque_minimo: string;
    unidade_medida: string;
    preco_custo?: string;
    preco_venda?: string;
    codigo_barras?: string;
    ativo?: boolean;
    created_at: string;
    categoria: { id: number; nome: string; tipo: string } | null;
    fornecedor: { id: number; razao_social: string } | null;
    forma_farmaceutica?: string;
    dosagem_padrao?: string;
    via_administracao_padrao?: string;
    frequencia_padrao?: string;
    duracao_padrao?: string;
    controlado?: boolean;
};

const props = defineProps<{
    produtos: { data: Produto[]; total: number };
    categorias: Array<{ id: number; nome: string; tipo: string }>;
    fornecedores: Array<{ id: number; razao_social: string }>;
    filters: { search?: string; categoria_id?: string; tipo?: string; tab?: string };
}>();

const tab = ref<'todos' | 'baixo_estoque'>(props.filters.tab === 'baixo_estoque' ? 'baixo_estoque' : 'todos');
const search = ref(props.filters.search ?? '');
const filtroTipo = ref(props.filters.tipo ?? '');

function navegar(extra: Record<string, string> = {}) {
    router.get('/estoque', {
        tab: tab.value, search: search.value,
        tipo: filtroTipo.value || undefined,
        ...extra,
    }, { preserveState: true });
}

function changeTab(t: string) { tab.value = t as any; navegar(); }
function buscar() { navegar(); }
function filtrarTipo(t: string) { filtroTipo.value = t; navegar(); }

function statusBadge(p: Produto) {
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

// ── Modal Novo Produto ────────────────────────────────────────────────────────
const showModal = ref(false);
const produtoForm = ref({
    nome: '', tipo: 'material', categoria_id: '', fornecedor_id: '',
    unidade_medida: 'un', quantidade_estoque: '0', estoque_minimo: '5',
    preco_custo: '', preco_venda: '', codigo_barras: '',
    forma_farmaceutica: '', dosagem_padrao: '', via_administracao_padrao: '',
    frequencia_padrao: '', duracao_padrao: '', controlado: false,
});

const categoriasFiltradasNovo = computed(() =>
    props.categorias.filter(c => c.tipo === 'ambos' || c.tipo === produtoForm.value.tipo)
);

function salvarProduto() {
    router.post('/estoque', produtoForm.value, { onSuccess: () => { showModal.value = false; } });
}

// ── Drawer de Edição ──────────────────────────────────────────────────────────
const showEditDrawer = ref(false);
const produtoEditando = ref<Produto | null>(null);
const editForm = ref({
    nome: '', tipo: 'material', categoria_id: '', fornecedor_id: '',
    unidade_medida: 'un', estoque_minimo: '0',
    preco_custo: '', preco_venda: '', codigo_barras: '', ativo: true,
    forma_farmaceutica: '', dosagem_padrao: '', via_administracao_padrao: '',
    frequencia_padrao: '', duracao_padrao: '', controlado: false,
});

const categoriasFiltradasEdit = computed(() =>
    props.categorias.filter(c => c.tipo === 'ambos' || c.tipo === editForm.value.tipo)
);

function abrirEdicao(produto: Produto) {
    produtoEditando.value = produto;
    editForm.value = {
        nome: produto.nome,
        tipo: produto.tipo,
        categoria_id: produto.categoria?.id ? String(produto.categoria.id) : '',
        fornecedor_id: produto.fornecedor?.id ? String(produto.fornecedor.id) : '',
        unidade_medida: produto.unidade_medida,
        estoque_minimo: produto.estoque_minimo,
        preco_custo: produto.preco_custo || '',
        preco_venda: produto.preco_venda || '',
        codigo_barras: produto.codigo_barras || '',
        ativo: produto.ativo !== false,
        forma_farmaceutica: produto.forma_farmaceutica || '',
        dosagem_padrao: produto.dosagem_padrao || '',
        via_administracao_padrao: produto.via_administracao_padrao || '',
        frequencia_padrao: produto.frequencia_padrao || '',
        duracao_padrao: produto.duracao_padrao || '',
        controlado: produto.controlado || false,
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
    if (!produtoEditando.value || !confirm('Excluir este produto?')) return;
    router.delete(`/estoque/${produtoEditando.value.id}`, {
        onSuccess: () => { showEditDrawer.value = false; }
    });
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Estoque</h1>
            <div class="flex items-center gap-3">
                <Link href="/estoque/movimentacoes" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Movimentações
                </Link>
                <Link href="/estoque/fornecedores" class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Fornecedores
                </Link>
                <button @click="showModal = true" class="flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo item
                </button>
            </div>
        </div>

        <!-- Tabs + filtro tipo -->
        <div class="mb-5 flex items-center justify-between border-b border-gray-100">
            <div class="flex items-center gap-6">
                <button @click="changeTab('todos')" class="pb-2.5 text-sm transition"
                    :class="tab === 'todos' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                    Todos
                </button>
                <button @click="changeTab('baixo_estoque')" class="flex items-center gap-1.5 pb-2.5 text-sm transition"
                    :class="tab === 'baixo_estoque' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    Baixo estoque
                </button>
            </div>
            <!-- Filtro por tipo -->
            <div class="flex items-center gap-1 pb-1">
                <button v-for="[val, label] in [['', 'Todos'], ['material', 'Materiais'], ['medicamento', 'Medicamentos']]"
                    :key="val" @click="filtrarTipo(val)"
                    class="rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="filtroTipo === val ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                    {{ label }}
                </button>
            </div>
        </div>

        <!-- Barra de busca -->
        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                <span class="font-semibold text-gray-900">{{ produtos.total }}</span>
                {{ produtos.total === 1 ? 'resultado' : 'resultados' }}
            </p>
            <div class="relative">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar..."
                    class="rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 w-56" />
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
                        <th class="px-4 py-3 text-left">Item</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Categoria</th>
                        <th class="px-4 py-3 text-left">Qtde.</th>
                        <th class="px-4 py-3 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!produtos.data.length">
                        <td colspan="5" class="py-12 text-center text-sm text-gray-400">Nenhum item encontrado</td>
                    </tr>
                    <tr v-for="p in produtos.data" :key="p.id" @click="abrirEdicao(p)" class="cursor-pointer hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ p.tipo === 'medicamento' ? '💊' : '📦' }}</span>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.nome }}</p>
                                    <p v-if="p.tipo === 'medicamento' && p.forma_farmaceutica" class="text-[11px] text-gray-400">{{ p.forma_farmaceutica }}{{ p.dosagem_padrao ? ` · ${p.dosagem_padrao}` : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="p.tipo === 'medicamento' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500'">
                                {{ p.tipo === 'medicamento' ? 'Medicamento' : 'Material' }}
                            </span>
                            <span v-if="p.controlado" class="ml-1 rounded-full bg-orange-50 px-2 py-0.5 text-[11px] font-medium text-orange-600">Controlado</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ p.categoria?.nome ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ p.quantidade_estoque }} {{ p.unidade_medida }}</td>
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

    <!-- Modal Novo Item -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showModal = false">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Novo item de estoque</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <!-- Tipo -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Tipo *</label>
                    <div class="flex gap-2">
                        <button v-for="[val, label] in [['material', '📦 Material'], ['medicamento', '💊 Medicamento']]"
                            :key="val" @click="produtoForm.tipo = val"
                            class="flex-1 rounded-lg border py-2 text-sm font-medium transition"
                            :class="produtoForm.tipo === val ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                            {{ label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome *</label>
                    <input v-model="produtoForm.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Categoria</label>
                        <select v-model="produtoForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhuma</option>
                            <option v-for="c in categoriasFiltradasNovo" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
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
                            <option value="mg">mg</option>
                            <option value="g">g</option>
                            <option value="kg">kg</option>
                            <option value="L">L</option>
                            <option value="comp">comp</option>
                            <option value="fr">fr</option>
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
                        <label class="mb-1 block text-xs font-medium text-gray-600">Preço custo (R$) *</label>
                        <input v-model="produtoForm.preco_custo" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Preço venda (R$) *</label>
                        <input v-model="produtoForm.preco_venda" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Código de barras</label>
                        <input v-model="produtoForm.codigo_barras" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>

                <!-- Campos farmacêuticos (só medicamento) -->
                <template v-if="produtoForm.tipo === 'medicamento'">
                    <p class="pt-1 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Informações farmacêuticas</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Forma farmacêutica</label>
                            <input v-model="produtoForm.forma_farmaceutica" type="text" placeholder="Ex: Comprimido, Solução..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Dosagem padrão</label>
                            <input v-model="produtoForm.dosagem_padrao" type="text" placeholder="Ex: 500mg, 10mg/ml..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Via de administração</label>
                            <select v-model="produtoForm.via_administracao_padrao" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option value="">Selecione</option>
                                <option>Oral</option>
                                <option>Subcutânea</option>
                                <option>Intramuscular</option>
                                <option>Intravenosa</option>
                                <option>Tópica</option>
                                <option>Ocular</option>
                                <option>Auricular</option>
                                <option>Inalatória</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Frequência padrão</label>
                            <input v-model="produtoForm.frequencia_padrao" type="text" placeholder="Ex: A cada 8h, 1x ao dia..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Duração padrão</label>
                            <input v-model="produtoForm.duracao_padrao" type="text" placeholder="Ex: 7 dias, 10 dias..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div class="flex items-center gap-2 pt-4">
                            <input v-model="produtoForm.controlado" type="checkbox" id="novo_controlado" class="h-4 w-4 rounded border-gray-300 text-primary-500" />
                            <label for="novo_controlado" class="text-sm font-medium text-gray-600">Medicamento controlado</label>
                        </div>
                    </div>
                </template>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarProduto" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Drawer de Edição -->
    <Transition name="slide">
        <div v-if="showEditDrawer" class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">Editar item</h2>
                <button @click="showEditDrawer = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <!-- Tipo (read-only no edit para não quebrar rastreabilidade) -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Tipo</label>
                    <div class="flex gap-2">
                        <button v-for="[val, label] in [['material', '📦 Material'], ['medicamento', '💊 Medicamento']]"
                            :key="val" @click="editForm.tipo = val"
                            class="flex-1 rounded-lg border py-2 text-sm font-medium transition"
                            :class="editForm.tipo === val ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                            {{ label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Nome *</label>
                    <input v-model="editForm.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Categoria</label>
                        <select v-model="editForm.categoria_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                            <option value="">Nenhuma</option>
                            <option v-for="c in categoriasFiltradasEdit" :key="c.id" :value="String(c.id)">{{ c.nome }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Fornecedor</label>
                        <select v-model="editForm.fornecedor_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                            <option value="">Nenhum</option>
                            <option v-for="f in fornecedores" :key="f.id" :value="String(f.id)">{{ f.razao_social }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Unidade *</label>
                        <select v-model="editForm.unidade_medida" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                            <option value="un">un</option>
                            <option value="cx">cx</option>
                            <option value="ml">ml</option>
                            <option value="mg">mg</option>
                            <option value="g">g</option>
                            <option value="kg">kg</option>
                            <option value="L">L</option>
                            <option value="comp">comp</option>
                            <option value="fr">fr</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Estoque mínimo *</label>
                        <input v-model="editForm.estoque_minimo" type="number" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    </div>
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

                <!-- Campos farmacêuticos -->
                <template v-if="editForm.tipo === 'medicamento'">
                    <p class="pt-2 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Informações farmacêuticas</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Forma farmacêutica</label>
                            <input v-model="editForm.forma_farmaceutica" type="text" placeholder="Ex: Comprimido, Solução..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Dosagem padrão</label>
                            <input v-model="editForm.dosagem_padrao" type="text" placeholder="Ex: 500mg, 10mg/ml..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Via de administração</label>
                            <select v-model="editForm.via_administracao_padrao" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                                <option value="">Selecione</option>
                                <option>Oral</option>
                                <option>Subcutânea</option>
                                <option>Intramuscular</option>
                                <option>Intravenosa</option>
                                <option>Tópica</option>
                                <option>Ocular</option>
                                <option>Auricular</option>
                                <option>Inalatória</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Frequência padrão</label>
                            <input v-model="editForm.frequencia_padrao" type="text" placeholder="Ex: A cada 8h, 1x ao dia..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Duração padrão</label>
                            <input v-model="editForm.duracao_padrao" type="text" placeholder="Ex: 7 dias..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div class="flex items-center gap-2 pt-4">
                            <input v-model="editForm.controlado" type="checkbox" id="edit_controlado" class="h-4 w-4 rounded border-gray-300 text-primary-500" />
                            <label for="edit_controlado" class="text-sm font-medium text-gray-700">Medicamento controlado</label>
                        </div>
                    </div>
                </template>

                <div class="flex items-center gap-2 pt-2">
                    <input v-model="editForm.ativo" type="checkbox" id="edit_ativo" class="h-4 w-4 rounded border-gray-300 text-primary-500" />
                    <label for="edit_ativo" class="text-sm font-medium text-gray-700">Item ativo</label>
                </div>

                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <p class="text-xs font-medium text-gray-500">Quantidade em estoque</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ produtoEditando?.quantidade_estoque }} {{ produtoEditando?.unidade_medida }}</p>
                    <p class="mt-1 text-xs text-gray-500">Use "Movimentações" para ajustar o estoque</p>
                </div>
            </div>

            <div class="border-t border-gray-100 px-6 py-4 flex gap-3">
                <button @click="excluirProduto" class="flex-1 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-100">Excluir</button>
                <button @click="atualizarProduto" class="flex-1 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
