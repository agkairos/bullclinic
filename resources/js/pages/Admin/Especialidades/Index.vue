<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    especialidades: Array<{ id: number; nome: string; descricao: string | null; ativo: boolean }>;
}>();

const drawer = ref(false);
const editando = ref<typeof props.especialidades[0] | null>(null);
const form = ref({ nome: '', descricao: '' });

function abrirNovo() {
    editando.value = null;
    form.value = { nome: '', descricao: '' };
    drawer.value = true;
}

function abrirEditar(e: typeof props.especialidades[0]) {
    editando.value = e;
    form.value = { nome: e.nome, descricao: e.descricao ?? '' };
    drawer.value = true;
}

function salvar() {
    if (editando.value) {
        router.put(`/admin/especialidades/${editando.value.id}`, form.value, { onSuccess: () => { drawer.value = false; } });
    } else {
        router.post('/admin/especialidades', form.value, { onSuccess: () => { drawer.value = false; } });
    }
}

function excluir(id: number) {
    if (!confirm('Excluir esta especialidade?')) return;
    router.delete(`/admin/especialidades/${id}`);
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Especialidades</h1>
                <p class="mt-0.5 text-xs text-gray-400">Áreas de atuação dos veterinários</p>
            </div>
            <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nova especialidade
            </button>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Nome</th>
                        <th class="px-5 py-3 text-left">Descrição</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!especialidades.length">
                        <td colspan="4" class="py-10 text-center text-gray-400">Nenhuma especialidade cadastrada</td>
                    </tr>
                    <tr v-for="e in especialidades" :key="e.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ e.nome }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ e.descricao ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="e.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                                {{ e.ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="abrirEditar(e)" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button @click="excluir(e.id)" class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500">
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
                <h2 class="text-base font-semibold text-gray-800">{{ editando ? 'Editar especialidade' : 'Nova especialidade' }}</h2>
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
                    <textarea v-model="form.descricao" rows="3" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
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
