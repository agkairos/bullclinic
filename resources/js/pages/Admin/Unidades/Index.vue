<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    conta: {
        id: number;
        nome: string;
        plano: {
            nome: string;
            limite_clinicas: number;
            clinicas_ilimitadas: boolean;
        } | null;
    };
    unidades: Array<{
        id: number;
        nome: string;
        cnpj: string | null;
        telefone: string | null;
        email: string | null;
        cep: string | null;
        logradouro: string | null;
        numero: string | null;
        complemento: string | null;
        bairro: string | null;
        cidade: string | null;
        estado: string | null;
        ativa: boolean;
    }>;
}>();

const drawer = ref(false);
const editando = ref<typeof props.unidades[0] | null>(null);
const buscandoCep = ref(false);

const formVazio = () => ({
    nome: '', cnpj: '', telefone: '', email: '',
    cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', estado: '',
});
const form = ref(formVazio());

const limiteAtingido = computed(() => {
    if (props.conta.plano?.clinicas_ilimitadas) return false;
    return props.unidades.length >= (props.conta.plano?.limite_clinicas ?? 1);
});

const estados = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA',
    'MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN',
    'RS','RO','RR','SC','SP','SE','TO',
];

function abrirNovo() {
    editando.value = null;
    form.value = formVazio();
    drawer.value = true;
}

function abrirEditar(u: typeof props.unidades[0]) {
    editando.value = u;
    form.value = {
        nome: u.nome,
        cnpj: u.cnpj ?? '',
        telefone: u.telefone ?? '',
        email: u.email ?? '',
        cep: u.cep ?? '',
        logradouro: u.logradouro ?? '',
        numero: u.numero ?? '',
        complemento: u.complemento ?? '',
        bairro: u.bairro ?? '',
        cidade: u.cidade ?? '',
        estado: u.estado ?? '',
    };
    drawer.value = true;
}

function salvar() {
    if (editando.value) {
        router.put(`/conta/unidades/${editando.value.id}`, form.value, {
            onSuccess: () => { drawer.value = false; },
        });
    } else {
        router.post('/conta/unidades', form.value, {
            onSuccess: () => { drawer.value = false; },
        });
    }
}

function toggleAtiva(u: typeof props.unidades[0]) {
    if (props.unidades.filter(x => x.ativa).length === 1 && u.ativa) return;
    router.patch(`/conta/unidades/${u.id}/ativar`);
}

function maskCnpj(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    form.value.cnpj = v;
}

function maskTelefone(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
    }
    form.value.telefone = v;
}

function maskCep(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    form.value.cep = v;
}

async function buscarCep() {
    const cep = form.value.cep.replace(/\D/g, '');
    if (cep.length !== 8) return;
    buscandoCep.value = true;
    try {
        const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            form.value.logradouro = data.logradouro ?? '';
            form.value.bairro = data.bairro ?? '';
            form.value.cidade = data.localidade ?? '';
            form.value.estado = data.uf ?? '';
        }
    } finally {
        buscandoCep.value = false;
    }
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Unidades</h1>
                <p class="mt-0.5 text-xs text-gray-400">
                    {{ unidades.length }}
                    {{ conta.plano?.clinicas_ilimitadas ? '' : `/ ${conta.plano?.limite_clinicas}` }}
                    unidade(s) · Plano {{ conta.plano?.nome ?? '—' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span v-if="limiteAtingido" class="text-xs text-amber-600 font-medium bg-amber-50 px-3 py-1.5 rounded-lg">
                    Limite do plano atingido
                </span>
                <button
                    @click="abrirNovo"
                    :disabled="limiteAtingido"
                    class="flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
                    :class="limiteAtingido ? 'bg-gray-300 cursor-not-allowed' : 'bg-primary-500 hover:bg-primary-600'"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova unidade
                </button>
            </div>
        </div>

        <!-- Alerta de upgrade -->
        <div v-if="limiteAtingido" class="mb-5 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <div>
                <p class="text-sm font-medium text-amber-800">Limite de unidades atingido</p>
                <p class="text-xs text-amber-600">Faça upgrade do plano para adicionar mais unidades.</p>
            </div>
            <a href="/planos" class="ml-auto rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">
                Ver planos
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="u in unidades" :key="u.id"
                class="rounded-xl border bg-white p-5 shadow-sm transition"
                :class="u.ativa ? 'border-gray-100' : 'border-gray-100 opacity-60'"
            >
                <div class="mb-3 flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-800">{{ u.nome }}</p>
                        <p v-if="u.cnpj" class="text-xs text-gray-400">{{ u.cnpj }}</p>
                        <p v-if="u.cidade" class="text-xs text-gray-400">{{ u.cidade }}{{ u.estado ? ` · ${u.estado}` : '' }}</p>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="u.ativa ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'"
                    >
                        {{ u.ativa ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>

                <div class="mb-4 space-y-1 text-xs text-gray-500">
                    <p v-if="u.logradouro">📍 {{ u.logradouro }}{{ u.numero ? `, ${u.numero}` : '' }}{{ u.bairro ? ` — ${u.bairro}` : '' }}</p>
                    <p v-if="u.telefone">📞 {{ u.telefone }}</p>
                    <p v-if="u.email">✉️ {{ u.email }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="abrirEditar(u)"
                        class="flex-1 rounded-lg border border-gray-200 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50"
                    >
                        Editar
                    </button>
                    <button
                        @click="toggleAtiva(u)"
                        :disabled="unidades.filter(x => x.ativa).length === 1 && u.ativa"
                        class="flex-1 rounded-lg border py-1.5 text-xs font-medium transition"
                        :class="u.ativa
                            ? 'border-red-100 text-red-500 hover:bg-red-50'
                            : 'border-green-100 text-green-600 hover:bg-green-50'"
                    >
                        {{ u.ativa ? 'Desativar' : 'Ativar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer -->
    <Transition name="fade">
        <div v-if="drawer" class="fixed inset-0 z-40 bg-black/20" @click="drawer = false" />
    </Transition>
    <Transition name="slide">
        <div v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-115 flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">{{ editando ? 'Editar unidade' : 'Nova unidade' }}</h2>
                <button @click="drawer = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <!-- Nome -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome da unidade *</label>
                    <input v-model="form.nome" type="text" placeholder="Ex: Unidade Centro" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>

                <!-- CNPJ + Telefone -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">CNPJ</label>
                        <input :value="form.cnpj" @input="maskCnpj" type="text" placeholder="00.000.000/0000-00" maxlength="18" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Telefone</label>
                        <input :value="form.telefone" @input="maskTelefone" type="text" placeholder="(00) 00000-0000" maxlength="15" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>

                <!-- E-mail -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">E-mail</label>
                    <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>

                <div class="border-t border-gray-100 pt-2">
                    <p class="mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Endereço</p>

                    <!-- CEP -->
                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">CEP</label>
                        <div class="relative">
                            <input :value="form.cep" @input="maskCep" @blur="buscarCep" type="text" placeholder="00000-000" maxlength="9" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                            <span v-if="buscandoCep" class="absolute right-3 top-2.5 text-xs text-gray-400">buscando...</span>
                        </div>
                    </div>

                    <!-- Logradouro + Número -->
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Logradouro</label>
                            <input v-model="form.logradouro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Número</label>
                            <input v-model="form.numero" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>

                    <!-- Complemento + Bairro -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Complemento</label>
                            <input v-model="form.complemento" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Bairro</label>
                            <input v-model="form.bairro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>

                    <!-- Cidade + Estado -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Cidade</label>
                            <input v-model="form.cidade" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                            <select v-model="form.estado" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option value="">Selecione</option>
                                <option v-for="uf in estados" :key="uf" :value="uf">{{ uf }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="drawer = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvar" class="rounded-lg bg-primary-500 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
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
