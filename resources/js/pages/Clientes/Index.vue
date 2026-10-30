<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RichText from '@/components/RichText.vue';

defineOptions({ layout: AppLayout });

type ClienteRow = {
    id: number; nome: string; cpf: string | null; celular: string | null;
    email: string | null; cidade: string | null; ativo: boolean; pacientes_count: number;
};

type ClienteDetalhe = {
    id: number; nome: string; cpf: string | null; rg: string | null;
    email: string | null; telefone: string | null; celular: string | null;
    cep: string | null; logradouro: string | null; numero: string | null;
    complemento: string | null; bairro: string | null; cidade: string | null;
    estado: string | null; observacoes: string | null; ativo: boolean;
    pacientes: Array<{ id: number; nome: string; especie: string; raca: string | null; sexo: string; consultas_count: number }>;
};

const props = defineProps<{
    clientes: { data: ClienteRow[]; links: any[] };
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
function buscar() {
    router.get('/clientes', { search: search.value }, { preserveState: true, replace: true });
}

// ── Drawer form (criar/editar) ───────────────────────────────────────────────
const drawerForm = ref(false);
const editandoId = ref<number | null>(null);

const form = useForm({
    nome: '', cpf: '', rg: '', email: '', telefone: '', celular: '',
    cep: '', logradouro: '', numero: '', complemento: '',
    bairro: '', cidade: '', estado: '', observacoes: '',
});

const touched = ref<Record<string, boolean>>({});
function touch(field: string) { touched.value[field] = true; }
function nomeError() { return (touched.value['nome'] && !form.nome) ? 'Nome é obrigatório.' : (form.errors.nome ?? ''); }

function abrirNovo() {
    editandoId.value = null;
    form.reset();
    form.clearErrors();
    touched.value = {};
    drawerForm.value = true;
}

async function abrirEditar(c: ClienteRow) {
    editandoId.value = c.id;
    form.reset();
    form.clearErrors();
    touched.value = {};
    drawerForm.value = true;
    const res = await fetch(`/clientes/${c.id}`, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '',
        },
    });
    const d: ClienteDetalhe = await res.json();
    form.nome = d.nome; form.cpf = d.cpf ?? ''; form.rg = d.rg ?? '';
    form.email = d.email ?? ''; form.telefone = d.telefone ?? ''; form.celular = d.celular ?? '';
    form.cep = d.cep ?? ''; form.logradouro = d.logradouro ?? ''; form.numero = d.numero ?? '';
    form.complemento = d.complemento ?? ''; form.bairro = d.bairro ?? '';
    form.cidade = d.cidade ?? ''; form.estado = d.estado ?? ''; form.observacoes = d.observacoes ?? '';
}

function fecharForm() { drawerForm.value = false; }

function salvar() {
    touched.value['nome'] = true;
    if (!form.nome) return;
    if (editandoId.value) {
        form.put(`/clientes/${editandoId.value}`, { onSuccess: fecharForm });
    } else {
        form.post('/clientes', { onSuccess: fecharForm });
    }
}

// ── Drawer detalhe (visualizar) ──────────────────────────────────────────────
const drawerDetalhe = ref(false);
const clienteDetalhe = ref<ClienteDetalhe | null>(null);
const carregandoDetalhe = ref(false);

async function verCliente(id: number) {
    carregandoDetalhe.value = true;
    clienteDetalhe.value = null;
    drawerDetalhe.value = true;
    const res = await fetch(`/clientes/${id}`, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '',
        },
    });
    clienteDetalhe.value = await res.json();
    carregandoDetalhe.value = false;
}

function fecharDetalhe() { drawerDetalhe.value = false; }

function especieIcon(e: string) {
    return e === 'felino' ? '🐱' : e === 'canino' ? '🐶' : e === 'ave' ? '🦜' : '🐾';
}

// ── Máscaras ─────────────────────────────────────────────────────────────────
function maskCpf(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
    form.cpf = v;
}

function maskFone(e: Event, field: 'telefone' | 'celular') {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 11);
    v = v.length <= 10
        ? v.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2')
        : v.replace(/^(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
    form[field] = v;
}

function maskCep(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 8);
    form.cep = v.replace(/(\d{5})(\d)/, '$1-$2');
}

const buscandoCep = ref(false);
async function buscarCep() {
    const cep = form.cep.replace(/\D/g, '');
    if (cep.length !== 8) return;
    buscandoCep.value = true;
    try {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            form.logradouro = data.logradouro ?? '';
            form.bairro = data.bairro ?? '';
            form.cidade = data.localidade ?? '';
            form.estado = data.uf ?? '';
        }
    } finally { buscandoCep.value = false; }
}

const estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

function pageLabel(label: string) {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Clientes</h1>
            <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo cliente
            </button>
        </div>

        <div class="mb-4">
            <div class="relative w-72">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar por nome, CPF ou e-mail..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Nome</th>
                        <th class="px-5 py-3 text-left">CPF</th>
                        <th class="px-5 py-3 text-left">Celular</th>
                        <th class="px-5 py-3 text-left">Cidade</th>
                        <th class="px-5 py-3 text-center">Animais</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!clientes.data.length">
                        <td colspan="7" class="py-10 text-center text-gray-400">Nenhum cliente encontrado</td>
                    </tr>
                    <tr v-for="c in clientes.data" :key="c.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ c.nome.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-gray-800">{{ c.nome }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ c.cpf ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ c.celular ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ c.cidade ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ c.pacientes_count }}</span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="c.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                                {{ c.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="abrirEditar(c)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Editar">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="verCliente(c.id)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Ver detalhes">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="clientes.links?.length > 3" class="flex justify-center gap-1 border-t border-gray-50 px-5 py-3">
                <template v-for="link in clientes.links" :key="link.label">
                    <button v-if="link.url" @click="router.get(link.url)"
                        class="rounded px-2.5 py-1 text-xs"
                        :class="link.active ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'"
                    >{{ pageLabel(link.label) }}</button>
                    <span v-else class="px-1 text-xs text-gray-300">{{ pageLabel(link.label) }}</span>
                </template>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <Transition name="fade">
        <div v-if="drawerForm || drawerDetalhe" class="fixed inset-0 z-40 bg-black/20"
            @click="drawerForm ? fecharForm() : fecharDetalhe()" />
    </Transition>

    <!-- ── Drawer: Criar / Editar ── -->
    <Transition name="slide">
        <div v-if="drawerForm" class="fixed inset-y-0 right-0 z-50 flex w-[460px] flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">{{ editandoId ? 'Editar cliente' : 'Novo cliente' }}</h2>
                <button @click="fecharForm" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-3">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Dados pessoais</p>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome completo *</label>
                    <input v-model="form.nome" @blur="touch('nome')" type="text"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500/10"
                        :class="nomeError() ? 'border-red-400' : 'border-gray-200 focus:border-primary-500'" />
                    <p v-if="nomeError()" class="mt-1 text-xs text-red-500">{{ nomeError() }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">CPF</label>
                        <input :value="form.cpf" @input="maskCpf" type="text" placeholder="000.000.000-00" maxlength="14"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        <p v-if="form.errors.cpf" class="mt-1 text-xs text-red-500">{{ form.errors.cpf }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">RG</label>
                        <input v-model="form.rg" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">E-mail</label>
                    <input v-model="form.email" type="email"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Celular</label>
                        <input :value="form.celular" @input="maskFone($event, 'celular')" type="text"
                            placeholder="(00) 00000-0000" maxlength="15"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Telefone</label>
                        <input :value="form.telefone" @input="maskFone($event, 'telefone')" type="text"
                            placeholder="(00) 0000-0000" maxlength="14"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <p class="pt-1 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Endereço</p>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">CEP</label>
                    <div class="relative">
                        <input :value="form.cep" @input="maskCep" @blur="buscarCep" type="text"
                            placeholder="00000-000" maxlength="9"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        <span v-if="buscandoCep" class="absolute right-3 top-2.5 text-xs text-gray-400">buscando...</span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Logradouro</label>
                        <input v-model="form.logradouro" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Número</label>
                        <input v-model="form.numero" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Bairro</label>
                        <input v-model="form.bairro" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Cidade</label>
                        <input v-model="form.cidade" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                        <select v-model="form.estado"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10">
                            <option value="">Selecione</option>
                            <option v-for="uf in estados" :key="uf" :value="uf">{{ uf }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Complemento</label>
                        <input v-model="form.complemento" type="text"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <p class="pt-1 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Observações</p>
                <RichText v-model="form.observacoes" placeholder="Anotações internas..." min-height="80px" />
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="fecharForm" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvar" :disabled="form.processing"
                    class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-60">
                    {{ editandoId ? 'Salvar alterações' : 'Cadastrar cliente' }}
                </button>
            </div>
        </div>
    </Transition>

    <!-- ── Drawer: Visualizar ── -->
    <Transition name="slide">
        <div v-if="drawerDetalhe" class="fixed inset-y-0 right-0 z-50 flex w-[480px] flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">Detalhes do cliente</h2>
                <button @click="fecharDetalhe" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div v-if="carregandoDetalhe" class="flex items-center justify-center py-16 text-sm text-gray-400">Carregando...</div>

                <template v-else-if="clienteDetalhe">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">
                            {{ clienteDetalhe.nome.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ clienteDetalhe.nome }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="clienteDetalhe.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                                {{ clienteDetalhe.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <button @click="() => { fecharDetalhe(); abrirEditar(clienteDetalhe as any); }"
                            class="ml-auto rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                            Editar
                        </button>
                    </div>

                    <div class="mb-5 grid grid-cols-2 gap-3 text-sm">
                        <div v-if="clienteDetalhe.cpf">
                            <p class="text-xs text-gray-400">CPF</p>
                            <p class="font-medium text-gray-700">{{ clienteDetalhe.cpf }}</p>
                        </div>
                        <div v-if="clienteDetalhe.email">
                            <p class="text-xs text-gray-400">E-mail</p>
                            <p class="truncate font-medium text-gray-700">{{ clienteDetalhe.email }}</p>
                        </div>
                        <div v-if="clienteDetalhe.celular">
                            <p class="text-xs text-gray-400">Celular</p>
                            <p class="font-medium text-gray-700">{{ clienteDetalhe.celular }}</p>
                        </div>
                        <div v-if="clienteDetalhe.telefone">
                            <p class="text-xs text-gray-400">Telefone</p>
                            <p class="font-medium text-gray-700">{{ clienteDetalhe.telefone }}</p>
                        </div>
                        <div v-if="clienteDetalhe.cidade" class="col-span-2">
                            <p class="text-xs text-gray-400">Endereço</p>
                            <p class="font-medium text-gray-700">
                                {{ [clienteDetalhe.logradouro, clienteDetalhe.numero, clienteDetalhe.bairro, clienteDetalhe.cidade, clienteDetalhe.estado].filter(Boolean).join(', ') }}
                            </p>
                        </div>
                        <div v-if="clienteDetalhe.observacoes" class="col-span-2">
                            <p class="text-xs text-gray-400">Observações</p>
                            <div class="text-gray-600 prose prose-sm max-w-none" v-html="clienteDetalhe.observacoes"></div>
                        </div>
                    </div>

                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Animais ({{ clienteDetalhe.pacientes.length }})</p>
                    <div v-if="!clienteDetalhe.pacientes.length" class="py-4 text-center text-sm text-gray-400">Nenhum animal cadastrado</div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="p in clienteDetalhe.pacientes" :key="p.id" class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">{{ especieIcon(p.especie) }}</span>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.nome }}</p>
                                    <p class="text-xs text-gray-400">{{ p.especie }}{{ p.raca ? ` · ${p.raca}` : '' }} · {{ p.sexo === 'M' ? 'Macho' : 'Fêmea' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400">{{ p.consultas_count }} consulta(s)</span>
                                <a :href="`/prontuarios/paciente/${p.id}`" class="rounded border border-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">
                                    Prontuário
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
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
