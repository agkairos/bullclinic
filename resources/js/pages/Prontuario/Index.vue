<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
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
            data_nascimento: string | null;
            consultas_count: number;
            cliente: { id: number; nome: string };
            fotos: Array<{ url: string }>;
        }>;
        links: any[];
    };
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function buscar() {
    router.get('/prontuarios', { search: search.value }, { preserveState: true, replace: true });
}

function idadeLabel(data: string | null) {
    if (!data) return null;
    const anos = Math.floor((Date.now() - new Date(data).getTime()) / (1000 * 60 * 60 * 24 * 365.25));
    return anos < 1 ? 'menos de 1 ano' : `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
}

function especieIcon(e: string) {
    if (e === 'felino') return '🐱';
    if (e === 'canino') return '🐶';
    if (e === 'ave') return '🦜';
    if (e === 'roedor') return '🐭';
    if (e === 'reptil') return '🦎';
    return '🐾';
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Prontuários</h1>
            <Link href="/pacientes" class="flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Cadastrar Paciente
            </Link>
        </div>

        <!-- Busca -->
        <div class="mb-4">
            <div class="relative w-72">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar paciente..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Tutor</th>
                        <th class="px-5 py-3 text-left">Espécie / Raça</th>
                        <th class="px-5 py-3 text-left">Idade</th>
                        <th class="px-5 py-3 text-center">Consultas</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!pacientes.data.length">
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <span class="text-4xl">🐾</span>
                                <p class="text-sm font-medium text-gray-500">Nenhum paciente encontrado</p>
                                <p v-if="!search" class="text-xs text-gray-400">Cadastre um paciente para acessar o prontuário</p>
                                <Link v-if="!search" href="/pacientes" class="mt-1 rounded-lg bg-primary-600 px-4 py-2 text-xs font-medium text-white hover:bg-primary-700">
                                    Ir para Pacientes
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="p in pacientes.data" :key="p.id" class="cursor-pointer hover:bg-gray-50/50" @click="router.visit(`/prontuarios/paciente/${p.id}`)">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 text-base">
                                    <img v-if="p.fotos[0]" :src="p.fotos[0].url" class="h-full w-full rounded-full object-cover" />
                                    <span v-else>{{ especieIcon(p.especie) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.nome }}</p>
                                    <p class="text-[11px] text-gray-400">{{ p.sexo === 'M' ? 'Macho' : 'Fêmea' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ p.cliente.nome }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ p.especie }} {{ p.raca ? `· ${p.raca}` : '' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ idadeLabel(p.data_nascimento) ?? '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ p.consultas_count }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link :href="`/prontuarios/paciente/${p.id}`" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                Ver prontuário
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
