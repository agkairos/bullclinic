<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    fornecedores: {
        data: Array<{
            id: number;
            razao_social: string;
            nome_fantasia: string | null;
            cnpj: string | null;
            telefone: string | null;
            email: string | null;
            site: string | null;
            cep: string | null;
            logradouro: string | null;
            numero: string | null;
            complemento: string | null;
            bairro: string | null;
            cidade: string | null;
            estado: string | null;
            observacoes: string | null;
            ativo: boolean;
            produtos_count: number;
        }>;
        total: number;
    };
    filters: { search?: string; ativo?: string };
}>();

const search = ref(props.filters.search ?? '');
const filtroAtivo = ref(props.filters.ativo ?? '');

function buscar() {
    router.get('/estoque/fornecedores', { search: search.value, ativo: filtroAtivo.value }, { preserveState: true });
}

const showDrawer = ref(false);
const fornecedorEditando = ref<typeof props.fornecedores.data[0] | null>(null);
const form = ref({
    razao_social: '',
    nome_fantasia: '',
    cnpj: '',
    telefone: '',
    email: '',
    site: '',
    cep: '',
    logradouro: '',
    numero: '',
    complemento: '',
    bairro: '',
    cidade: '',
    estado: '',
    observacoes: '',
    ativo: true,
});

function novoFornecedor() {
    fornecedorEditando.value = null;
    form.value = {
        razao_social: '',
        nome_fantasia: '',
        cnpj: '',
        telefone: '',
        email: '',
        site: '',
        cep: '',
        logradouro: '',
        numero: '',
        complemento: '',
        bairro: '',
        cidade: '',
        estado: '',
        observacoes: '',
        ativo: true,
    };
    showDrawer.value = true;
}

function editarFornecedor(f: typeof props.fornecedores.data[0]) {
    fornecedorEditando.value = f;
    form.value = {
        razao_social: f.razao_social,
        nome_fantasia: f.nome_fantasia || '',
        cnpj: f.cnpj || '',
        telefone: f.telefone || '',
        email: f.email || '',
        site: f.site || '',
        cep: f.cep || '',
        logradouro: f.logradouro || '',
        numero: f.numero || '',
        complemento: f.complemento || '',
        bairro: f.bairro || '',
        cidade: f.cidade || '',
        estado: f.estado || '',
        observacoes: f.observacoes || '',
        ativo: f.ativo,
    };
    showDrawer.value = true;
}

function salvar() {
    if (fornecedorEditando.value) {
        router.put(`/estoque/fornecedores/${fornecedorEditando.value.id}`, form.value, {
            onSuccess: () => {
 showDrawer.value = false; 
}
        });
    } else {
        router.post('/estoque/fornecedores', form.value, {
            onSuccess: () => {
 showDrawer.value = false; 
}
        });
    }
}

function excluir() {
    if (!fornecedorEditando.value) {
return;
}

    if (confirm(`Excluir fornecedor "${fornecedorEditando.value.razao_social}"?`)) {
        router.delete(`/estoque/fornecedores/${fornecedorEditando.value.id}`, {
            onSuccess: () => {
 showDrawer.value = false; 
}
        });
    }
}

function formatCNPJ(cnpj: string | null) {
    if (!cnpj) {
return '-';
}

    return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}

function formatTelefone(tel: string | null) {
    if (!tel) {
return '-';
}

    return tel.replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3');
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
                    <h1 class="text-xl font-bold text-gray-900">Fornecedores</h1>
                </div>
                <p class="text-sm text-gray-500">Gerencie os fornecedores de produtos</p>
            </div>
            <button @click="novoFornecedor" class="flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo fornecedor
            </button>
        </div>

        <!-- Filtros -->
        <div class="mb-4 flex items-center gap-3">
            <div class="relative flex-1">
                <input
                    v-model="search"
                    @keydown.enter="buscar"
                    type="text"
                    placeholder="Buscar fornecedor..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                />
                <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <select v-model="filtroAtivo" @change="buscar" class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                <option value="">Todos</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>

        <!-- Total -->
        <p class="mb-4 text-sm text-gray-500">
            <span class="font-semibold text-gray-900">{{ fornecedores.total }}</span>
            {{ fornecedores.total === 1 ? 'fornecedor encontrado' : 'fornecedores encontrados' }}
        </p>

        <!-- Lista -->
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-3 text-left">Fornecedor</th>
                        <th class="px-4 py-3 text-left">CNPJ</th>
                        <th class="px-4 py-3 text-left">Contato</th>
                        <th class="px-4 py-3 text-left">Produtos</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!fornecedores.data.length">
                        <td colspan="6" class="py-12 text-center text-sm text-gray-400">Nenhum fornecedor encontrado</td>
                    </tr>
                    <tr v-for="f in fornecedores.data" :key="f.id" class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ f.razao_social }}</p>
                            <p v-if="f.nome_fantasia" class="text-xs text-gray-500">{{ f.nome_fantasia }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ formatCNPJ(f.cnpj) }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            <p v-if="f.telefone">{{ formatTelefone(f.telefone) }}</p>
                            <p v-if="f.email" class="text-xs text-gray-500">{{ f.email }}</p>
                            <p v-if="!f.telefone && !f.email" class="text-gray-400">-</p>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ f.produtos_count }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="f.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ f.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click="editarFornecedor(f)" class="text-primary-600 hover:text-primary-800 font-medium text-xs">
                                Editar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Drawer -->
    <div v-if="showDrawer" class="fixed inset-0 z-50 flex items-start justify-end bg-black/20" @click="showDrawer = false">
        <div @click.stop class="h-full w-full max-w-lg overflow-y-auto bg-white shadow-xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ fornecedorEditando ? 'Editar fornecedor' : 'Novo fornecedor' }}
                </h2>
                <button @click="showDrawer = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="space-y-5 p-6">
                <!-- Dados principais -->
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Dados principais</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Razão social *</label>
                            <input v-model="form.razao_social" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Nome fantasia</label>
                            <input v-model="form.nome_fantasia" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">CNPJ</label>
                            <input v-model="form.cnpj" type="text" maxlength="18" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                    </div>
                </div>

                <!-- Contato -->
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Contato</h3>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Telefone</label>
                                <input v-model="form.telefone" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                                <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Site</label>
                            <input v-model="form.site" type="url" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Endereço</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">CEP</label>
                            <input v-model="form.cep" type="text" maxlength="9" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Logradouro</label>
                            <input v-model="form.logradouro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Número</label>
                                <input v-model="form.numero" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Complemento</label>
                                <input v-model="form.complemento" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Bairro</label>
                            <input v-model="form.bairro" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Cidade</label>
                                <input v-model="form.cidade" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">UF</label>
                                <input v-model="form.estado" type="text" maxlength="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm uppercase outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Observações</label>
                    <textarea v-model="form.observacoes" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input v-model="form.ativo" type="checkbox" id="ativo" class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                    <label for="ativo" class="text-sm font-medium text-gray-700">Fornecedor ativo</label>
                </div>
            </div>

            <div class="sticky bottom-0 border-t border-gray-100 bg-white px-6 py-4">
                <div class="flex gap-3">
                    <button v-if="fornecedorEditando" @click="excluir" class="flex-1 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-100">
                        Excluir
                    </button>
                    <button @click="salvar" class="flex-1 rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">
                        {{ fornecedorEditando ? 'Salvar alterações' : 'Cadastrar fornecedor' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
