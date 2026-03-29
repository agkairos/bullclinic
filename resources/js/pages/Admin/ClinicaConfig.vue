<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useCep } from '@/composables/useCep';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    clinica: {
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
        timezone: string | null;
        logo: string | null;
        plano: { nome: string; max_usuarios: number; max_consultas_mes: number; storage_gb: number } | null;
    };
}>();

const form = ref({ ...props.clinica });

const { buscarCep, buscandoCep, cepNaoEncontrado } = useCep();

const estados = [
    'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA',
    'MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN',
    'RS','RO','RR','SC','SP','SE','TO',
];

// ── Máscaras ──────────────────────────────────────────────────────────────────

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

async function onCepBlur() {
    const end = await buscarCep(form.value.cep ?? '');
    if (end) {
        form.value.logradouro = end.logradouro;
        form.value.bairro = end.bairro;
        form.value.cidade = end.cidade;
        form.value.estado = end.estado;
    }
}

function salvar() {
    router.put('/admin/clinica', form.value);
}

function uploadLogo(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    const data = new FormData();
    data.append('logo', file);
    router.post('/admin/clinica/logo', data);
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Configurações da clínica</h1>
            <button @click="salvar" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                Salvar alterações
            </button>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- Dados principais -->
            <div class="col-span-2 space-y-5">
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Dados da clínica</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Nome</label>
                            <input v-model="form.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">CNPJ</label>
                            <input
                                :value="form.cnpj"
                                @input="maskCnpj"
                                type="text"
                                placeholder="00.000.000/0000-00"
                                maxlength="18"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Telefone</label>
                            <input
                                :value="form.telefone"
                                @input="maskTelefone"
                                type="text"
                                placeholder="(00) 00000-0000"
                                maxlength="15"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                            />
                        </div>
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">E-mail</label>
                            <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Endereço</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">CEP</label>
                            <div class="relative">
                                <input
                                    :value="form.cep"
                                    @input="maskCep"
                                    @blur="onCepBlur"
                                    type="text"
                                    placeholder="00000-000"
                                    maxlength="9"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                                    :class="cepNaoEncontrado ? 'border-red-300' : ''"
                                />
                                <div v-if="buscandoCep" class="absolute right-2.5 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                            </div>
                            <p v-if="cepNaoEncontrado" class="mt-0.5 text-xs text-red-500">CEP não encontrado</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                            <select v-model="form.estado" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                                <option value="">Selecione</option>
                                <option v-for="uf in estados" :key="uf" :value="uf">{{ uf }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Logradouro</label>
                            <input v-model="form.logradouro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Número</label>
                            <input v-model="form.numero" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Complemento</label>
                            <input v-model="form.complemento" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Bairro</label>
                            <input v-model="form.bairro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Cidade</label>
                            <input v-model="form.cidade" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar direita -->
            <div class="space-y-5">
                <!-- Logo -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Logo</h2>
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-primary-50">
                            <img v-if="clinica.logo" :src="clinica.logo" class="h-full w-full rounded-xl object-cover" />
                            <svg v-else class="h-8 w-8 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </div>
                        <label class="cursor-pointer rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                            Alterar logo
                            <input type="file" accept="image/*" class="hidden" @change="uploadLogo" />
                        </label>
                    </div>
                </div>

                <!-- Plano -->
                <div v-if="clinica.plano" class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Plano atual</h2>
                    <p class="mb-3 text-lg font-bold text-primary-600">{{ clinica.plano.nome }}</p>
                    <ul class="space-y-1.5 text-xs text-gray-500">
                        <li class="flex items-center justify-between">
                            <span>Usuários</span>
                            <span class="font-medium text-gray-700">até {{ clinica.plano.max_usuarios }}</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span>Consultas/mês</span>
                            <span class="font-medium text-gray-700">{{ clinica.plano.max_consultas_mes === -1 ? 'Ilimitado' : clinica.plano.max_consultas_mes }}</span>
                        </li>
                        <li class="flex items-center justify-between">
                            <span>Armazenamento</span>
                            <span class="font-medium text-gray-700">{{ clinica.plano.storage_gb }} GB</span>
                        </li>
                    </ul>
                </div>

                <!-- Timezone -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Fuso horário</h2>
                    <select v-model="form.timezone" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                        <option value="America/Sao_Paulo">America/Sao_Paulo</option>
                        <option value="America/Manaus">America/Manaus</option>
                        <option value="America/Belem">America/Belem</option>
                        <option value="America/Fortaleza">America/Fortaleza</option>
                        <option value="America/Recife">America/Recife</option>
                        <option value="America/Noronha">America/Noronha</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
