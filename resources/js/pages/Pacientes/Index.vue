<script setup lang="ts">
import { ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    pacientes: {
        data: Array<{
            id: number;
            nome: string;
            especie: string;
            raca: string | null;
            sexo: string;
            peso: string | null;
            data_nascimento: string | null;
            ativo: boolean;
            consultas_count: number;
            cliente: { id: number; nome: string };
            fotos: Array<{ url: string }>;
        }>;
        links: any[];
    };
    filters: { search?: string; especie?: string };
}>();

// Busca clientes para o drawer (via fetch)
const clientes = ref<Array<{ id: number; nome: string }>>([]);
// ── Autocomplete de cliente (tutor) ──────────────────────────────────────────
type ClienteResult = { id: number; nome: string; celular: string | null; email: string | null };

const buscaCliente = ref('');
const resultadosCliente = ref<ClienteResult[]>([]);
const clienteSelecionado = ref<ClienteResult | null>(null);
const buscandoCliente = ref(false);
const mostrarDropdownCliente = ref(false);
let debounceCliente: ReturnType<typeof setTimeout>;

async function onBuscaCliente() {
    clearTimeout(debounceCliente);
    clienteSelecionado.value = null;
    form.value.cliente_id = '';

    if (buscaCliente.value.length < 2) {
        resultadosCliente.value = [];
        mostrarDropdownCliente.value = false;
        return;
    }

    debounceCliente = setTimeout(async () => {
        buscandoCliente.value = true;
        const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/clientes/buscar?q=${encodeURIComponent(buscaCliente.value)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        resultadosCliente.value = res.ok ? await res.json() : [];
        mostrarDropdownCliente.value = true;
        buscandoCliente.value = false;
    }, 280);
}

function selecionarCliente(c: ClienteResult) {
    clienteSelecionado.value = c;
    form.value.cliente_id = String(c.id);
    buscaCliente.value = c.nome;
    mostrarDropdownCliente.value = false;
    resultadosCliente.value = [];
}

function limparCliente() {
    clienteSelecionado.value = null;
    form.value.cliente_id = '';
    buscaCliente.value = '';
}

const search = ref(props.filters.search ?? '');
const especieFiltro = ref(props.filters.especie ?? '');

function buscar() {
    router.get('/pacientes', { search: search.value, especie: especieFiltro.value || undefined }, { preserveState: true, replace: true });
}

// Drawer
const drawer = ref(false);
const editando = ref<typeof props.pacientes.data[0] | null>(null);
const form = ref({
    cliente_id: '', nome: '', especie: 'canino', raca: '', sexo: 'macho',
    data_nascimento: '', cor: '', peso: '', microchip: '', tatuagem: '',
    alergias: '', condicoes_especiais: '', restricoes_alimentares: '', medicamentos_cronicos: '',
});

function abrirNovo() {
    editando.value = null;
    form.value = { cliente_id: '', nome: '', especie: 'canino', raca: '', sexo: 'macho', data_nascimento: '', cor: '', peso: '', microchip: '', tatuagem: '', alergias: '', condicoes_especiais: '', restricoes_alimentares: '', medicamentos_cronicos: '' };
    clienteSelecionado.value = null;
    buscaCliente.value = '';
    resultadosCliente.value = [];
    drawer.value = true;
}

function abrirEditar(p: typeof props.pacientes.data[0]) {
    editando.value = p;
    form.value = {
        cliente_id: String(p.cliente.id), nome: p.nome, especie: p.especie,
        raca: p.raca ?? '', sexo: p.sexo, data_nascimento: p.data_nascimento ?? '',
        cor: '', peso: p.peso ?? '', microchip: '', tatuagem: '',
        alergias: '', condicoes_especiais: '', restricoes_alimentares: '', medicamentos_cronicos: '',
    };
    // Pré-preenche o campo de busca com o nome do tutor atual
    clienteSelecionado.value = { id: p.cliente.id, nome: p.cliente.nome, celular: null, email: null };
    buscaCliente.value = p.cliente.nome;
    drawer.value = true;
}

function salvar() {
    if (editando.value) {
        router.put(`/pacientes/${editando.value.id}`, form.value, { onSuccess: () => { drawer.value = false; } });
    } else {
        router.post('/pacientes', form.value, { onSuccess: () => { drawer.value = false; } });
    }
}

function especieIcon(e: string) {
    const map: Record<string, string> = { felino: '🐱', canino: '🐶', ave: '🦜', roedor: '🐭', reptil: '🦎' };
    return map[e] ?? '🐾';
}

function idadeLabel(data: string | null) {
    if (!data) return null;
    const anos = Math.floor((Date.now() - new Date(data).getTime()) / (1000 * 60 * 60 * 24 * 365.25));
    return anos < 1 ? '< 1 ano' : `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
}

const especies = ['canino', 'felino', 'ave', 'roedor', 'reptil', 'outro'];
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Pacientes</h1>
            <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo paciente
            </button>
        </div>

        <!-- Filtros -->
        <div class="mb-4 flex items-center gap-3">
            <div class="relative w-72">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar por nome ou microchip..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <select v-model="especieFiltro" @change="buscar" class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                <option value="">Todas as espécies</option>
                <option v-for="e in especies" :key="e" :value="e" class="capitalize">{{ e }}</option>
            </select>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Tutor</th>
                        <th class="px-5 py-3 text-left">Espécie / Raça</th>
                        <th class="px-5 py-3 text-left">Idade / Peso</th>
                        <th class="px-5 py-3 text-center">Consultas</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!pacientes.data.length">
                        <td colspan="6" class="py-10 text-center text-gray-400">Nenhum paciente encontrado</td>
                    </tr>
                    <tr v-for="p in pacientes.data" :key="p.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-lg">
                                    <img v-if="p.fotos[0]" :src="p.fotos[0].url" class="h-full w-full rounded-xl object-cover" />
                                    <span v-else>{{ especieIcon(p.especie) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.nome }}</p>
                                    <p class="text-[11px] text-gray-400">{{ p.sexo === 'macho' ? '♂ Macho' : p.sexo === 'femea' ? '♀ Fêmea' : 'Não inf.' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ p.cliente.nome }}</td>
                        <td class="px-5 py-3 text-gray-500 capitalize">
                            {{ p.especie }}
                            <span v-if="p.raca" class="text-gray-400">· {{ p.raca }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            <p v-if="p.data_nascimento">{{ idadeLabel(p.data_nascimento) }}</p>
                            <p v-if="p.peso">{{ p.peso }} kg</p>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ p.consultas_count }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="abrirEditar(p)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <Link :href="`/pacientes/${p.id}`" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </Link>
                                <Link :href="`/prontuarios/paciente/${p.id}`" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-500">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div v-if="pacientes.links?.length > 3" class="mt-4 flex justify-center gap-1">
            <template v-for="link in pacientes.links" :key="link.label">
                <button v-if="link.url" @click="router.get(link.url)"
                    class="rounded px-3 py-1.5 text-xs"
                    :class="link.active ? 'bg-primary-500 text-white font-semibold' : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    v-html="link.label">
                </button>
            </template>
        </div>
    </div>

    <!-- Overlay -->
    <Transition name="fade">
        <div v-if="drawer" class="fixed inset-0 z-40 bg-black/20" @click="drawer = false" />
    </Transition>

    <!-- Drawer -->
    <Transition name="slide">
        <div v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-[480px] flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">{{ editando ? 'Editar paciente' : 'Novo paciente' }}</h2>
                <button @click="drawer = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">

                <!-- Identificação -->
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Identificação</p>
                <div class="mb-5 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Tutor (cliente) *</label>

                        <!-- Selecionado -->
                        <div v-if="clienteSelecionado"
                            class="flex items-center justify-between rounded-lg border border-primary-300 bg-primary-50 px-3 py-2">
                            <div class="leading-tight">
                                <p class="text-sm font-semibold text-primary-800">{{ clienteSelecionado.nome }}</p>
                                <p v-if="clienteSelecionado.celular" class="text-[11px] text-primary-500">{{ clienteSelecionado.celular }}</p>
                            </div>
                            <button @click="limparCliente" class="text-primary-400 hover:text-red-500 text-lg leading-none">×</button>
                        </div>

                        <!-- Busca -->
                        <div v-else class="relative">
                            <input
                                v-model="buscaCliente"
                                @input="onBuscaCliente"
                                @focus="mostrarDropdownCliente = resultadosCliente.length > 0"
                                @blur="setTimeout(() => mostrarDropdownCliente = false, 180)"
                                type="text"
                                placeholder="Buscar por nome, CPF ou celular..."
                                class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500"
                            />
                            <svg v-if="!buscandoCliente" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <svg v-else class="absolute left-3 top-2.5 h-4 w-4 animate-spin text-primary-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>

                            <div v-if="mostrarDropdownCliente && resultadosCliente.length"
                                class="absolute z-50 mt-1 w-full rounded-xl border border-gray-100 bg-white shadow-lg overflow-hidden">
                                <button
                                    v-for="c in resultadosCliente"
                                    :key="c.id"
                                    @mousedown.prevent="selecionarCliente(c)"
                                    class="flex w-full flex-col px-3 py-2.5 text-left hover:bg-gray-50 border-b border-gray-50 last:border-b-0">
                                    <span class="text-sm font-medium text-gray-800">{{ c.nome }}</span>
                                    <span class="text-[11px] text-gray-400">{{ c.celular ?? c.email ?? '' }}</span>
                                </button>
                            </div>

                            <p v-if="mostrarDropdownCliente && !buscandoCliente && !resultadosCliente.length && buscaCliente.length >= 2"
                                class="mt-1 text-xs text-gray-400">Nenhum cliente encontrado.</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Nome *</label>
                        <input v-model="form.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Espécie *</label>
                            <select v-model="form.especie" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option v-for="e in especies" :key="e" :value="e" class="capitalize">{{ e }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Sexo *</label>
                            <select v-model="form.sexo" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option value="macho">Macho</option>
                                <option value="femea">Fêmea</option>
                                <option value="nao_informado">Não informado</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Raça</label>
                            <input v-model="form.raca" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Cor / Pelagem</label>
                            <input v-model="form.cor" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Nascimento</label>
                            <input v-model="form.data_nascimento" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Peso (kg)</label>
                            <input v-model="form.peso" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Microchip</label>
                            <input v-model="form.microchip" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Tatuagem</label>
                            <input v-model="form.tatuagem" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>
                </div>

                <!-- Saúde -->
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Informações de saúde</p>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Alergias</label>
                        <textarea v-model="form.alergias" rows="2" placeholder="Ex: Amoxicilina, frango..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Condições especiais</label>
                        <textarea v-model="form.condicoes_especiais" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Restrições alimentares</label>
                        <textarea v-model="form.restricoes_alimentares" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Medicamentos crônicos</label>
                        <textarea v-model="form.medicamentos_cronicos" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="drawer = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvar" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                    {{ editando ? 'Salvar alterações' : 'Cadastrar paciente' }}
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
