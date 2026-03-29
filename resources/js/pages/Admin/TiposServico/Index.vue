<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    tipos: Array<{ id: number; nome: string; descricao: string | null; duracao_minutos: number; preco: string; cor: string; ativo: boolean }>;
}>();

const drawer = ref(false);
const editando = ref<typeof props.tipos[0] | null>(null);
const form = ref({ nome: '', descricao: '', duracao_minutos: 30, preco: '', cor: '#00c1b3' });

function abrirNovo() {
    editando.value = null;
    form.value = { nome: '', descricao: '', duracao_minutos: 30, preco: '', cor: '#00c1b3' };
    drawer.value = true;
}

function abrirEditar(t: typeof props.tipos[0]) {
    editando.value = t;
    form.value = { nome: t.nome, descricao: t.descricao ?? '', duracao_minutos: t.duracao_minutos, preco: t.preco, cor: t.cor };
    drawer.value = true;
}

function salvar() {
    if (editando.value) {
        router.put(`/admin/tipos-servico/${editando.value.id}`, form.value, { onSuccess: () => { drawer.value = false; } });
    } else {
        router.post('/admin/tipos-servico', form.value, { onSuccess: () => { drawer.value = false; } });
    }
}

function excluir(id: number) {
    if (!confirm('Excluir este tipo de serviço?')) return;
    router.delete(`/admin/tipos-servico/${id}`);
}

function fmt(v: string) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(v));
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Tipos de serviço</h1>
                <p class="mt-0.5 text-xs text-gray-400">Serviços oferecidos na agenda com cor, duração e preço</p>
            </div>
            <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo tipo
            </button>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Serviço</th>
                        <th class="px-5 py-3 text-left">Duração</th>
                        <th class="px-5 py-3 text-right">Preço</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!tipos.length">
                        <td colspan="5" class="py-10 text-center text-gray-400">Nenhum tipo cadastrado</td>
                    </tr>
                    <tr v-for="t in tipos" :key="t.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="h-3 w-3 rounded-full shrink-0" :style="{ backgroundColor: t.cor }"></span>
                                <div>
                                    <p class="font-medium text-gray-800">{{ t.nome }}</p>
                                    <p v-if="t.descricao" class="text-[11px] text-gray-400">{{ t.descricao }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ t.duracao_minutos }} min</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-700">{{ fmt(t.preco) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="t.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                                {{ t.ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="abrirEditar(t)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="excluir(t.id)" class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Transition name="fade">
        <div v-if="drawer" class="fixed inset-0 z-40 bg-black/20" @click="drawer = false" />
    </Transition>
    <Transition name="slide">
        <div v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-[400px] flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">{{ editando ? 'Editar tipo de serviço' : 'Novo tipo de serviço' }}</h2>
                <button @click="drawer = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome *</label>
                    <input v-model="form.nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Descrição</label>
                    <input v-model="form.descricao" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Duração (min) *</label>
                        <input v-model="form.duracao_minutos" type="number" min="5" step="5" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Preço (R$) *</label>
                        <input v-model="form.preco" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Cor na agenda *</label>
                    <div class="flex items-center gap-3">
                        <input v-model="form.cor" type="color" class="h-10 w-16 cursor-pointer rounded-lg border border-gray-200 p-1" />
                        <input v-model="form.cor" type="text" maxlength="7" class="flex-1 rounded-lg border border-gray-200 px-3 py-2 text-sm font-mono outline-none focus:border-primary-500" />
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button v-for="c in ['#00c1b3','#6366f1','#ef4444','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#0ea5e9','#10b981']"
                            :key="c" @click="form.cor = c"
                            class="h-6 w-6 rounded-full border-2 transition"
                            :style="{ backgroundColor: c, borderColor: form.cor === c ? '#374151' : 'transparent' }">
                        </button>
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
