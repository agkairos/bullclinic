<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    fila: Array<{
        id: number;
        hora_inicio: string;
        checkin_em: string | null;
        status: string;
        observacoes_publicas: string | null;
        paciente: { id: number; nome: string; especie: string; fotos: Array<{ url: string }> };
        cliente: { id: number; nome: string };
        veterinario: { id: number; name: string };
        tipo_servico: { nome: string; cor: string } | null;
    }>;
}>();

function atualizarStatus(id: number, status: string) {
    fetch(`/agendamentos/${id}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status }),
    }).then(() => router.reload());
}

function tempoEspera(checkin: string | null) {
    if (!checkin) return null;
    const mins = Math.floor((Date.now() - new Date(checkin).getTime()) / 60000);
    if (mins < 60) return `${mins} min`;
    return `${Math.floor(mins / 60)}h${mins % 60 > 0 ? ` ${mins % 60}min` : ''}`;
}

function especieIcon(e: string) {
    if (e === 'felino') return '🐱';
    if (e === 'canino') return '🐶';
    return '🐾';
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Em espera</h1>
                <p class="mt-0.5 text-xs text-gray-400">{{ new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }) }}</p>
            </div>
            <button @click="router.reload()" class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.07-9"/></svg>
                Atualizar
            </button>
        </div>

        <div v-if="!fila.length" class="rounded-xl border border-gray-100 bg-white p-12 text-center shadow-sm">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p class="text-sm text-gray-400">Nenhum paciente aguardando no momento</p>
        </div>

        <div class="space-y-3">
            <div v-for="(ag, i) in fila" :key="ag.id"
                class="flex items-center gap-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">

                <!-- Posição -->
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600">
                    {{ i + 1 }}
                </div>

                <!-- Paciente -->
                <div class="flex items-center gap-2.5 w-48 shrink-0">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-50 text-xl">
                        {{ especieIcon(ag.paciente.especie) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ ag.paciente.nome }}</p>
                        <p class="text-xs text-gray-400">{{ ag.cliente.nome }}</p>
                    </div>
                </div>

                <!-- Serviço -->
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span v-if="ag.tipo_servico" class="rounded-full px-2.5 py-1 text-xs font-medium text-white"
                            :style="{ backgroundColor: ag.tipo_servico.cor }">
                            {{ ag.tipo_servico.nome }}
                        </span>
                        <span class="text-xs text-gray-400">{{ ag.hora_inicio }} · {{ ag.veterinario.name }}</span>
                    </div>
                    <div v-if="ag.observacoes_publicas" class="mt-0.5 text-xs text-gray-600 prose prose-sm max-w-none" v-html="ag.observacoes_publicas"></div>
                </div>

                <!-- Tempo de espera -->
                <div v-if="tempoEspera(ag.checkin_em)" class="text-center">
                    <p class="text-xs text-gray-400">Aguardando</p>
                    <p class="text-sm font-semibold" :class="parseInt(tempoEspera(ag.checkin_em)!) > 30 ? 'text-red-500' : 'text-gray-700'">
                        {{ tempoEspera(ag.checkin_em) }}
                    </p>
                </div>

                <!-- Ações -->
                <div class="flex gap-2">
                    <button @click="atualizarStatus(ag.id, 'em_atendimento')"
                        class="rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                        Chamar
                    </button>
                    <button @click="atualizarStatus(ag.id, 'cancelado')"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
