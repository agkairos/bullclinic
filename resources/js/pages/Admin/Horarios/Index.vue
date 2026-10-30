<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Intervalo = {
    id: number; dia_semana: number; ordem: number;
    hora_inicio: string; hora_fim: string;
    duracao_consulta_minutos: number; intervalo_entre_consultas_minutos: number; ativo: boolean;
};

const props = defineProps<{
    veterinarios: Array<{ id: number; name: string; crmv: string | null }>;
    // horarios[user_id][dia_semana] = Intervalo[]
    horarios: Record<string, Record<string, Intervalo[]>>;
}>();

// Preserva o vet selecionado via query string
const urlVet = new URLSearchParams(window.location.search).get('vet');
const vetSelecionado = ref<number | null>(
    urlVet ? Number(urlVet) : (props.veterinarios[0]?.id ?? null)
);

function selecionarVet(id: number) {
    vetSelecionado.value = id;
    router.get('/admin/horarios', { vet: id }, { preserveState: true, replace: true });
}

const dias = [
    { num: 1, label: 'Segunda' },
    { num: 2, label: 'Terça' },
    { num: 3, label: 'Quarta' },
    { num: 4, label: 'Quinta' },
    { num: 5, label: 'Sexta' },
    { num: 6, label: 'Sábado' },
    { num: 0, label: 'Domingo' },
];

function intervalosNoDia(dia: number): Intervalo[] {
    if (!vetSelecionado.value) return [];
    return props.horarios[String(vetSelecionado.value)]?.[String(dia)] ?? [];
}

function diaTem(dia: number): boolean {
    return intervalosNoDia(dia).length > 0;
}

function diaAtivo(dia: number): boolean {
    return intervalosNoDia(dia).some(i => i.ativo);
}

// ── Drawer ────────────────────────────────────────────────────────────────────
const drawer = ref(false);
const diaEditando = ref<number | null>(null);
const ordemEditando = ref<1 | 2>(1);

type FormIntervalo = {
    hora_inicio: string; hora_fim: string;
    duracao_consulta_minutos: number; intervalo_entre_consultas_minutos: number;
};

const formVazio = (): FormIntervalo => ({
    hora_inicio: '08:00', hora_fim: '12:00',
    duracao_consulta_minutos: 30, intervalo_entre_consultas_minutos: 10,
});

const form = ref<FormIntervalo>(formVazio());

function abrirIntervalo(dia: number, ordem: 1 | 2) {
    diaEditando.value = dia;
    ordemEditando.value = ordem;
    const existente = intervalosNoDia(dia).find(i => i.ordem === ordem);
    if (existente) {
        form.value = {
            hora_inicio: existente.hora_inicio.substring(0, 5),
            hora_fim: existente.hora_fim.substring(0, 5),
            duracao_consulta_minutos: existente.duracao_consulta_minutos,
            intervalo_entre_consultas_minutos: existente.intervalo_entre_consultas_minutos,
        };
    } else {
        // Sugerir horário de tarde para intervalo 2
        form.value = ordem === 2
            ? { hora_inicio: '13:00', hora_fim: '18:00', duracao_consulta_minutos: 30, intervalo_entre_consultas_minutos: 10 }
            : formVazio();
    }
    drawer.value = true;
}

const csrf = () => (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';

async function salvar() {
    await fetch('/admin/horarios', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({
            user_id: vetSelecionado.value,
            dia_semana: diaEditando.value,
            ordem: ordemEditando.value,
            ...form.value,
        }),
    });
    drawer.value = false;
    router.reload({ only: ['horarios'] });
}

async function removerIntervalo(id: number) {
    if (!confirm('Remover este intervalo?')) return;
    await fetch(`/admin/horarios/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf() },
    });
    router.reload({ only: ['horarios'] });
}

async function toggleDia(dia: number) {
    const ativo = !diaAtivo(dia);
    await fetch('/admin/horarios/toggle-dia', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ user_id: vetSelecionado.value, dia_semana: dia, ativo }),
    });
    router.reload({ only: ['horarios'] });
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Horários de atendimento</h1>
            <p class="mt-0.5 text-xs text-gray-400">Configure os dias e intervalos de cada profissional. Cada dia pode ter até 2 intervalos (ex: manhã e tarde).</p>
        </div>

        <!-- Seletor de veterinário -->
        <div class="mb-5 flex flex-wrap gap-2">
            <button v-for="v in veterinarios" :key="v.id"
                @click="selecionarVet(v.id)"
                class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition"
                :class="vetSelecionado === v.id ? 'border-primary-500 bg-primary-50 font-medium text-primary-600' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[11px] font-bold text-primary-700">
                    {{ v.name.charAt(0) }}
                </div>
                {{ v.name }}
            </button>
        </div>

        <!-- Grade de dias -->
        <div class="grid grid-cols-7 gap-3">
            <div v-for="dia in dias" :key="dia.num"
                class="rounded-xl border bg-white p-3 shadow-sm transition"
                :class="diaTem(dia.num) && diaAtivo(dia.num) ? 'border-gray-100' : diaTem(dia.num) ? 'border-gray-100 opacity-60' : 'border-gray-100'">

                <!-- Cabeçalho do dia -->
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-600">{{ dia.label }}</p>
                    <button v-if="diaTem(dia.num)"
                        @click="toggleDia(dia.num)"
                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold transition"
                        :class="diaAtivo(dia.num)
                            ? 'bg-green-100 text-green-700 hover:bg-red-50 hover:text-red-500'
                            : 'bg-gray-100 text-gray-400 hover:bg-green-50 hover:text-green-600'">
                        {{ diaAtivo(dia.num) ? 'Ativo' : 'Inativo' }}
                    </button>
                </div>

                <!-- Intervalos existentes -->
                <div class="space-y-2">
                    <template v-for="ordem in [1, 2]" :key="ordem">
                        <template v-for="intervalo in [intervalosNoDia(dia.num).find(i => i.ordem === ordem)]" :key="ordem + '-i'">
                        <div v-if="intervalo">
                            <div class="rounded-lg bg-primary-50 px-2.5 py-2">
                                <p class="text-[11px] font-semibold text-primary-600">
                                    Intervalo {{ ordem }}
                                </p>
                                <p class="text-xs font-bold text-primary-700">
                                    {{ intervalo.hora_inicio.substring(0,5) }} – {{ intervalo.hora_fim.substring(0,5) }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-primary-400">
                                    {{ intervalo.duracao_consulta_minutos }}min · {{ intervalo.intervalo_entre_consultas_minutos }}min intervalo
                                </p>
                            </div>
                            <div class="mt-1 flex gap-1">
                                <button @click="abrirIntervalo(dia.num, ordem as 1|2)"
                                    class="flex-1 rounded border border-gray-200 py-1 text-[11px] text-gray-500 hover:bg-gray-50">
                                    Editar
                                </button>
                                <button @click="removerIntervalo(intervalo.id)"
                                    class="rounded border border-gray-200 px-2 py-1 text-[11px] text-red-400 hover:bg-red-50">✕</button>
                            </div>
                        </div>
                        <div v-else>
                            <button @click="abrirIntervalo(dia.num, ordem as 1|2)"
                                class="flex w-full items-center justify-center gap-1 rounded-lg border border-dashed border-gray-200 py-3 text-[11px] text-gray-400 hover:border-primary-300 hover:text-primary-500 transition">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Intervalo {{ ordem }}
                            </button>
                        </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <Transition name="fade">
        <div v-if="drawer" class="fixed inset-0 z-40 bg-black/20" @click="drawer = false" />
    </Transition>
    <Transition name="slide">
        <div v-if="drawer" class="fixed inset-y-0 right-0 z-50 flex w-90 flex-col bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ dias.find(d => d.num === diaEditando)?.label }} · Intervalo {{ ordemEditando }}
                </h2>
                <button @click="drawer = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Início *</label>
                        <input v-model="form.hora_inicio" type="time"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Fim *</label>
                        <input v-model="form.hora_fim" type="time"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Duração da consulta (min)</label>
                        <input v-model.number="form.duracao_consulta_minutos" type="number" min="5" step="5"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Intervalo entre consultas (min)</label>
                        <input v-model.number="form.intervalo_entre_consultas_minutos" type="number" min="0" step="5"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
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
