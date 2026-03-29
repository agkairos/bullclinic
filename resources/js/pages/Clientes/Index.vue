<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    clientes: {
        data: Array<{
            id: number;
            nome: string;
            cpf: string | null;
            celular: string | null;
            email: string | null;
            cidade: string | null;
            ativo: boolean;
            pacientes_count: number;
        }>;
        links: any[];
    };
    filters: { search?: string; status?: string };
}>();

const search = ref(props.filters.search ?? '');

function buscar() {
    router.get('/clientes', { search: search.value }, { preserveState: true, replace: true });
}

// Drawer
const drawer = ref(false);
const editandoId = ref<number | null>(null);
const form = ref({
    nome: '', cpf: '', rg: '', email: '', telefone: '', celular: '',
    cep: '', logradouro: '', numero: '', complemento: '',
    bairro: '', cidade: '', estado: '', observacoes: '',
});

function abrirNovo() {
    editandoId.value = null;
    form.value = { nome: '', cpf: '', rg: '', email: '', telefone: '', celular: '', cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '', observacoes: '' };
    drawer.value = true;
}

function abrirEditar(c: typeof props.clientes.data[0]) {
    editandoId.value = c.id;
    form.value = {
        nome: c.nome, cpf: c.cpf ?? '', rg: '', email: c.email ?? '',
        telefone: '', celular: c.celular ?? '',
        cep: '', logradouro: '', numero: '', complemento: '',
        bairro: '', cidade: c.cidade ?? '', estado: '', observacoes: '',
    };
    drawer.value = true;
}

function fechar() {
    drawer.value = false;
}

function salvar() {
    if (editandoId.value) {
        router.put(`/clientes/${editandoId.value}`, form.value, { onSuccess: fechar });
    } else {
        router.post('/clientes', form.value, { onSuccess: fechar });
    }
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
                                <button @click="abrirEditar(c)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <Link :href="`/clientes/${c.id}`" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Overlay -->
    <Transition name="fade">
        <div v-if="drawer" class="fixed inset-0 z-40 bg-black/20" @click="fechar" />
    </Transition>

    <!-- Drawer -->
    <Transition name="slide">
        <div v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-[460px] flex-col bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ editandoId ? 'Editar cliente' : 'Novo cliente' }}
                </h2>
                <button @click="fechar" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Conteúdo com scroll -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <!-- Seção: Dados pessoais -->
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Dados pessoais</p>
                <div class="mb-5 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Nome completo *</label>
                        <input v-model="form.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">CPF</label>
                            <input v-model="form.cpf" type="text" placeholder="000.000.000-00" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">RG</label>
                            <input v-model="form.rg" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">E-mail</label>
                        <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Celular</label>
                            <input v-model="form.celular" type="text" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Telefone</label>
                            <input v-model="form.telefone" type="text" placeholder="(00) 0000-0000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                    </div>
                </div>

                <!-- Seção: Endereço -->
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Endereço</p>
                <div class="mb-5 space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">CEP</label>
                            <input v-model="form.cep" type="text" placeholder="00000-000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">UF</label>
                            <input v-model="form.estado" type="text" maxlength="2" placeholder="SP" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 uppercase" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Logradouro</label>
                            <input v-model="form.logradouro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Número</label>
                            <input v-model="form.numero" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Bairro</label>
                            <input v-model="form.bairro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Cidade</label>
                            <input v-model="form.cidade" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Complemento</label>
                        <input v-model="form.complemento" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10" />
                    </div>
                </div>

                <!-- Seção: Observações -->
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-widest text-gray-400">Observações</p>
                <textarea v-model="form.observacoes" rows="3" placeholder="Anotações internas sobre o cliente..."
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/10 resize-none"></textarea>
            </div>

            <!-- Footer fixo -->
            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="fechar" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
                <button @click="salvar" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                    {{ editandoId ? 'Salvar alterações' : 'Cadastrar cliente' }}
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
