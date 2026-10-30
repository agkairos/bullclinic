<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { useCep } from '@/composables/useCep';
import RichText from '@/components/RichText.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cliente?: {
        id: number; nome: string; cpf: string | null; rg: string | null;
        email: string | null; telefone: string | null; celular: string | null;
        cep: string | null; logradouro: string | null; numero: string | null;
        complemento: string | null; bairro: string | null; cidade: string | null;
        estado: string | null; observacoes: string | null;
    };
}>();

const form = ref({
    nome: props.cliente?.nome ?? '',
    cpf: props.cliente?.cpf ?? '',
    rg: props.cliente?.rg ?? '',
    email: props.cliente?.email ?? '',
    telefone: props.cliente?.telefone ?? '',
    celular: props.cliente?.celular ?? '',
    cep: props.cliente?.cep ?? '',
    logradouro: props.cliente?.logradouro ?? '',
    numero: props.cliente?.numero ?? '',
    complemento: props.cliente?.complemento ?? '',
    bairro: props.cliente?.bairro ?? '',
    cidade: props.cliente?.cidade ?? '',
    estado: props.cliente?.estado ?? '',
    observacoes: props.cliente?.observacoes ?? '',
});

const { buscarCep, buscandoCep, cepNaoEncontrado } = useCep();

async function onCepBlur() {
    const end = await buscarCep(form.value.cep);
    if (end) {
        form.value.logradouro = end.logradouro;
        form.value.bairro = end.bairro;
        form.value.cidade = end.cidade;
        form.value.estado = end.estado;
    }
}

function salvar() {
    if (props.cliente) {
        router.put(`/clientes/${props.cliente.id}`, form.value);
    } else {
        router.post('/clientes', form.value);
    }
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center gap-3">
            <Link href="/clientes" class="text-xs text-gray-400 hover:text-primary-500">← Clientes</Link>
            <span class="text-gray-200">/</span>
            <h1 class="text-xl font-bold text-gray-900">{{ cliente ? 'Editar cliente' : 'Novo cliente' }}</h1>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-5">
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Dados pessoais</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Nome completo *</label>
                            <input v-model="form.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">CPF</label>
                            <input v-model="form.cpf" type="text" placeholder="000.000.000-00" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">RG</label>
                            <input v-model="form.rg" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">E-mail</label>
                            <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Celular</label>
                            <input v-model="form.celular" type="text" placeholder="(00) 00000-0000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Telefone</label>
                            <input v-model="form.telefone" type="text" placeholder="(00) 0000-0000" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Endereço</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">CEP</label>
                            <div class="relative">
                                <input v-model="form.cep" type="text" placeholder="00000-000" @blur="onCepBlur"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                                    :class="cepNaoEncontrado ? 'border-red-300' : ''" />
                                <div v-if="buscandoCep" class="absolute right-2.5 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                            </div>
                            <p v-if="cepNaoEncontrado" class="mt-0.5 text-xs text-red-500">CEP não encontrado</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                            <input v-model="form.estado" type="text" maxlength="2" placeholder="SP" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
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

                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Observações</h2>
                    <RichText v-model="form.observacoes" min-height="80px" />
                </div>
            </div>

            <div>
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <button @click="salvar" class="w-full rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">
                        {{ cliente ? 'Salvar alterações' : 'Cadastrar cliente' }}
                    </button>
                    <Link href="/clientes" class="mt-2 block w-full rounded-lg border border-gray-200 py-2.5 text-center text-sm text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
