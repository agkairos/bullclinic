<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Agendamento = {
    id: number;
    data: string;
    hora_inicio: string;
    hora_fim: string;
    status: string;
    urgencia: boolean;
    primeira_consulta: boolean;
    observacoes_publicas: string | null;
    observacoes_internas: string | null;
    valor_estimado: string | null;
    paciente: { id: number; nome: string; especie: string };
    cliente: { id: number; nome: string };
    veterinario: { id: number; name: string };
    tipo_servico: { id: number; nome: string; cor: string } | null;
};

const props = defineProps<{
    agendamentos: Agendamento[];
    veterinarios: Array<{ id: number; name: string; avatar: string | null; crmv?: string | null }>;
    tipos_servico: Array<{ id: number; nome: string; cor: string; duracao_minutos: number }>;
    semana_inicio: string;
}>();

// ── Semana / navegação ────────────────────────────────────────────────────────
const diasSemana = computed(() => {
    const inicio = new Date(props.semana_inicio + 'T00:00:00');
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(inicio);
        d.setDate(d.getDate() + i);
        return {
            date: d.toISOString().split('T')[0],
            label: d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
            num: d.getDate(),
            isToday: d.toISOString().split('T')[0] === new Date().toISOString().split('T')[0],
        };
    });
});

const horas = Array.from({ length: 16 }, (_, i) => `${String(i + 6).padStart(2, '0')}:00`);

const agora = new Date();
const horaAtualMinutos = agora.getHours() * 60 + agora.getMinutes();
const horaAtualTop = computed(() => Math.max(0, ((horaAtualMinutos - 360) / 60) * 64));
const horaAtualLabel = computed(() => `${String(agora.getHours()).padStart(2, '0')}:${String(agora.getMinutes()).padStart(2, '0')}`);

function agendamentoPosicao(ag: Agendamento) {
    const [h, m] = ag.hora_inicio.split(':').map(Number);
    const [hf, mf] = ag.hora_fim.split(':').map(Number);
    const top = ((h * 60 + m) - 360) / 60 * 64;
    const height = ((hf * 60 + mf) - (h * 60 + m)) / 60 * 64;
    return { top: Math.max(0, top), height: Math.max(24, height) };
}

function navSemana(dir: number) {
    const d = new Date(props.semana_inicio + 'T00:00:00');
    d.setDate(d.getDate() + dir * 7);
    router.get('/agenda', { semana: d.toISOString().split('T')[0], veterinario_id: filtroVet.value || undefined }, { preserveState: true });
}

const labelSemana = computed(() => {
    const fim = new Date(props.semana_inicio + 'T00:00:00');
    fim.setDate(fim.getDate() + 6);
    const mesAno = fim.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }).replace('.', '');
    const d1 = new Date(props.semana_inicio + 'T00:00:00').getDate();
    return `${d1} – ${fim.getDate()} de ${mesAno}`;
});

// ── Filtro por veterinário ────────────────────────────────────────────────────
const filtroVet = ref<number | ''>('');

function agendamentosNoDia(data: string) {
    return props.agendamentos.filter(a =>
        a.data === data && (filtroVet.value === '' || a.veterinario.id === filtroVet.value)
    );
}

function aplicarFiltroVet(id: number | '') {
    filtroVet.value = id;
    router.get('/agenda', { semana: props.semana_inicio, veterinario_id: id || undefined }, { preserveState: true });
}

// ── Aba ───────────────────────────────────────────────────────────────────────
const abaSelecionada = ref<'agenda' | 'relatorios'>('agenda');

// ── Drawer de detalhes ────────────────────────────────────────────────────────
const drawerAg = ref(false);
const agSelecionado = ref<Agendamento | null>(null);
const atualizandoStatus = ref(false);

function abrirDetalhes(ag: Agendamento) {
    agSelecionado.value = { ...ag };
    drawerAg.value = true;
}

const statusLabel: Record<string, string> = {
    agendado: 'Agendado', confirmado: 'Confirmado', aguardando: 'Em espera',
    em_atendimento: 'Em atendimento', finalizado: 'Finalizado', cancelado: 'Cancelado',
};
const statusCor: Record<string, string> = {
    agendado: 'bg-gray-100 text-gray-600',
    confirmado: 'bg-blue-50 text-blue-600',
    aguardando: 'bg-yellow-50 text-yellow-600',
    em_atendimento: 'bg-primary-50 text-primary-700',
    finalizado: 'bg-green-50 text-green-700',
    cancelado: 'bg-red-50 text-red-500',
};

// Transições de status possíveis a partir do status atual
const proximosStatus: Record<string, string[]> = {
    agendado: ['confirmado', 'aguardando', 'cancelado'],
    confirmado: ['aguardando', 'cancelado'],
    aguardando: ['em_atendimento', 'cancelado'],
    em_atendimento: ['finalizado', 'cancelado'],
    finalizado: [],
    cancelado: [],
};

async function mudarStatus(novoStatus: string) {
    if (!agSelecionado.value) return;
    atualizandoStatus.value = true;
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/agendamentos/${agSelecionado.value.id}/status`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ status: novoStatus }),
    });
    if (res.ok) {
        const data = await res.json();
        agSelecionado.value = { ...agSelecionado.value, status: data.agendamento.status };
        // Atualiza na lista sem reload completo
        router.reload({ only: ['agendamentos'] });
    }
    atualizandoStatus.value = false;
}

function excluirAgendamento() {
    if (!agSelecionado.value) return;
    if (!confirm('Excluir este agendamento?')) return;
    drawerAg.value = false;
    router.delete(`/agendamentos/${agSelecionado.value.id}`);
}

// ── Modal novo agendamento ────────────────────────────────────────────────────
const showModal = ref(false);
const agForm = ref({
    cliente_id: '' as string | number,
    paciente_id: '' as string | number,
    veterinario_id: '',
    tipo_servico_id: '',
    data: new Date().toISOString().split('T')[0],
    hora_inicio: '08:00',
    hora_fim: '08:30',
    observacoes_publicas: '',
    urgencia: false,
});

// Slots livres do DisponibilidadeService
const slotsLivres = ref<Array<{ hora_inicio: string; hora_fim: string }>>([]);
const carregandoSlots = ref(false);

async function carregarSlots() {
    if (!agForm.value.veterinario_id || !agForm.value.data) { slotsLivres.value = []; return; }
    carregandoSlots.value = true;
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/agendamentos/disponibilidade?veterinario_id=${agForm.value.veterinario_id}&data=${agForm.value.data}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    slotsLivres.value = res.ok ? await res.json() : [];
    carregandoSlots.value = false;
}

function selecionarSlot(slot: { hora_inicio: string; hora_fim: string }) {
    agForm.value.hora_inicio = slot.hora_inicio;
    agForm.value.hora_fim = slot.hora_fim;
}

function abrirModal() {
    agForm.value = {
        cliente_id: '', paciente_id: '', veterinario_id: '',
        tipo_servico_id: '', data: new Date().toISOString().split('T')[0],
        hora_inicio: '08:00', hora_fim: '08:30',
        observacoes_publicas: '', urgencia: false,
    };
    pacienteSelecionado.value = null;
    buscaPaciente.value = '';
    resultadosPaciente.value = [];
    slotsLivres.value = [];
    showModal.value = true;
}

function salvarAgendamento() {
    router.post('/agendamentos', agForm.value, { onSuccess: () => { showModal.value = false; } });
}

// ── Autocomplete de paciente ──────────────────────────────────────────────────
type PacienteResult = {
    id: number; nome: string; especie: string; raca: string | null;
    foto_url: string | null; cliente: { id: number; nome: string };
};

const buscaPaciente = ref('');
const resultadosPaciente = ref<PacienteResult[]>([]);
const pacienteSelecionado = ref<PacienteResult | null>(null);
const buscandoPaciente = ref(false);
const mostrarDropdown = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

async function onBuscaPaciente() {
    clearTimeout(debounceTimer);
    pacienteSelecionado.value = null;
    agForm.value.paciente_id = '';
    agForm.value.cliente_id = '';
    if (buscaPaciente.value.length < 2) { resultadosPaciente.value = []; mostrarDropdown.value = false; return; }
    debounceTimer = setTimeout(async () => {
        buscandoPaciente.value = true;
        const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/pacientes/buscar?q=${encodeURIComponent(buscaPaciente.value)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        resultadosPaciente.value = res.ok ? await res.json() : [];
        mostrarDropdown.value = true;
        buscandoPaciente.value = false;
    }, 280);
}

function selecionarPaciente(p: PacienteResult) {
    pacienteSelecionado.value = p;
    agForm.value.paciente_id = p.id;
    agForm.value.cliente_id = p.cliente.id;
    buscaPaciente.value = p.nome;
    mostrarDropdown.value = false;
    resultadosPaciente.value = [];
}

function limparPaciente() {
    pacienteSelecionado.value = null;
    agForm.value.paciente_id = '';
    agForm.value.cliente_id = '';
    buscaPaciente.value = '';
}

const especieIcon: Record<string, string> = { cão: '🐕', cao: '🐕', gato: '🐈', ave: '🐦', coelho: '🐇', hamster: '🐹', peixe: '🐟', canino: '🐕', felino: '🐈' };
function getEspecieIcon(e: string) { return especieIcon[e.toLowerCase()] ?? '🐾'; }

// ── Relatórios (computed a partir dos agendamentos da semana) ─────────────────
const relatorios = computed(() => {
    const total = props.agendamentos.length;
    const finalizados = props.agendamentos.filter(a => a.status === 'finalizado').length;
    const cancelados = props.agendamentos.filter(a => a.status === 'cancelado').length;
    const taxaComp = total > 0 ? Math.round((finalizados / (total - cancelados || 1)) * 100) : 0;

    const porVet = props.veterinarios.map(v => {
        const ags = props.agendamentos.filter(a => a.veterinario.id === v.id);
        return {
            vet: v,
            total: ags.length,
            finalizados: ags.filter(a => a.status === 'finalizado').length,
            cancelados: ags.filter(a => a.status === 'cancelado').length,
        };
    }).filter(r => r.total > 0);

    const porDia = diasSemana.value.map(d => ({
        label: d.label,
        num: d.num,
        count: props.agendamentos.filter(a => a.data === d.date).length,
    }));

    const maxDia = Math.max(...porDia.map(d => d.count), 1);

    return { total, finalizados, cancelados, taxaComp, porVet, porDia, maxDia };
});
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 bg-white px-8 py-4">
            <h1 class="text-xl font-bold text-gray-900">Agenda</h1>

            <div class="flex items-center gap-3">
                <!-- Tabs -->
                <div class="flex gap-1">
                    <button v-for="tab in ['agenda', 'relatorios']" :key="tab"
                        @click="abaSelecionada = tab as any"
                        class="px-3 py-1.5 text-sm capitalize transition"
                        :class="abaSelecionada === tab ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                        {{ tab === 'agenda' ? 'Agenda' : 'Relatórios' }}
                    </button>
                </div>

                <!-- Navegação de semana -->
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <button @click="navSemana(-1)" class="rounded p-1 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="font-medium text-xs">{{ labelSemana }}</span>
                    <button @click="navSemana(1)" class="rounded p-1 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Filtro por veterinário -->
                <div class="flex items-center gap-1">
                    <button @click="aplicarFiltroVet('')"
                        class="rounded-full px-3 py-1 text-xs font-medium transition"
                        :class="filtroVet === '' ? 'bg-primary-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'">
                        Todos
                    </button>
                    <button v-for="v in veterinarios" :key="v.id"
                        @click="aplicarFiltroVet(v.id)"
                        class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition"
                        :class="filtroVet === v.id ? 'bg-primary-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'">
                        <span class="flex h-4 w-4 items-center justify-center rounded-full bg-primary-100 text-[9px] font-bold text-primary-700"
                            :class="filtroVet === v.id ? 'bg-white/20! text-white!' : ''">
                            {{ v.name.charAt(0) }}
                        </span>
                        {{ v.name.split(' ')[0] }}
                    </button>
                </div>

                <button @click="abrirModal" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo agendamento
                </button>
            </div>
        </div>

        <!-- ABA: Agenda -->
        <div v-if="abaSelecionada === 'agenda'" class="flex flex-1 overflow-hidden bg-white">
            <!-- Coluna de horas -->
            <div class="w-16 shrink-0 border-r border-gray-100">
                <div class="h-14 border-b border-gray-100"></div>
                <div class="relative">
                    <div v-for="hora in horas" :key="hora" class="flex h-16 items-start justify-end pr-2 pt-1">
                        <span class="text-[11px] text-gray-400">{{ hora }}</span>
                    </div>
                </div>
            </div>

            <!-- Grid dos dias -->
            <div class="flex flex-1 overflow-x-auto overflow-y-auto">
                <div v-for="dia in diasSemana" :key="dia.date"
                    class="flex min-w-0 flex-1 flex-col border-r border-gray-100 last:border-r-0">
                    <div class="sticky top-0 z-10 flex h-14 flex-col items-center justify-center border-b border-gray-100 bg-white">
                        <span class="text-[11px] uppercase text-gray-400">{{ dia.label }}</span>
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                            :class="dia.isToday ? 'bg-primary-500 text-white' : 'text-gray-700'">
                            {{ dia.num }}
                        </span>
                    </div>

                    <div class="relative flex-1">
                        <div v-for="hora in horas" :key="hora" class="h-16 border-b border-gray-50"></div>

                        <!-- Linha do horário atual -->
                        <div v-if="dia.isToday" class="absolute left-0 right-0 z-10 flex items-center" :style="{ top: horaAtualTop + 'px' }">
                            <span class="shrink-0 rounded-r bg-red-500 px-1 py-0.5 text-[9px] font-bold text-white">{{ horaAtualLabel }}</span>
                            <div class="h-px flex-1 bg-red-400"></div>
                        </div>

                        <!-- Agendamentos -->
                        <div v-for="ag in agendamentosNoDia(dia.date)" :key="ag.id"
                            @click="abrirDetalhes(ag)"
                            class="absolute left-1 right-1 z-20 cursor-pointer overflow-hidden rounded-md px-2 py-1 text-xs shadow-sm transition hover:shadow-md hover:brightness-95"
                            :class="ag.urgencia ? 'ring-2 ring-red-400 ring-offset-0' : ''"
                            :style="{
                                top: agendamentoPosicao(ag).top + 'px',
                                height: agendamentoPosicao(ag).height + 'px',
                                backgroundColor: ag.tipo_servico?.cor ?? '#00c1b3',
                            }">
                            <div class="flex items-start justify-between gap-1">
                                <p class="truncate font-semibold text-white leading-tight">{{ ag.paciente?.nome }}</p>
                                <!-- Urgência -->
                                <svg v-if="ag.urgencia" class="h-3 w-3 shrink-0 text-red-200" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            </div>
                            <p class="truncate text-white/80">{{ ag.tipo_servico?.nome ?? 'Consulta' }}</p>
                            <p class="text-white/70">{{ ag.hora_inicio.substring(0,5) }} – {{ ag.hora_fim.substring(0,5) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA: Relatórios -->
        <div v-else class="flex-1 overflow-y-auto px-8 py-6">
            <div class="max-w-4xl space-y-5">
                <!-- Cards de resumo -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ relatorios.total }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Total na semana</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-green-600">{{ relatorios.finalizados }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Realizados</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-red-500">{{ relatorios.cancelados }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Cancelados</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-primary-600">{{ relatorios.taxaComp }}%</p>
                        <p class="text-xs text-gray-400 mt-0.5">Taxa de comparecimento</p>
                    </div>
                </div>

                <!-- Gráfico por dia (barras simples) -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">Agendamentos por dia</h3>
                    <div class="flex items-end gap-3 h-24">
                        <div v-for="d in relatorios.porDia" :key="d.label"
                            class="flex flex-1 flex-col items-center gap-1">
                            <span class="text-xs font-semibold text-gray-700">{{ d.count || '' }}</span>
                            <div class="w-full rounded-t-lg bg-primary-200 transition-all"
                                :style="{ height: d.count ? `${(d.count / relatorios.maxDia) * 80}px` : '4px' }"
                                :class="d.count ? 'bg-primary-400' : 'bg-gray-100'">
                            </div>
                            <span class="text-[11px] text-gray-400 capitalize">{{ d.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Por veterinário -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">Por veterinário</h3>
                    <div v-if="relatorios.porVet.length" class="space-y-3">
                        <div v-for="r in relatorios.porVet" :key="r.vet.id" class="flex items-center gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-700">
                                {{ r.vet.name.charAt(0) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-800">{{ r.vet.name }}</span>
                                    <span class="text-xs text-gray-400">{{ r.finalizados }}/{{ r.total }} realizados</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full bg-primary-400 transition-all"
                                        :style="{ width: r.total ? `${(r.finalizados / r.total) * 100}%` : '0%' }">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">Nenhum agendamento nesta semana.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Drawer de detalhes do agendamento ─────────────────────────────────── -->
    <Transition name="fade">
        <div v-if="drawerAg" class="fixed inset-0 z-40 bg-black/20" @click="drawerAg = false" />
    </Transition>
    <Transition name="slide">
        <div v-if="drawerAg && agSelecionado" class="fixed inset-y-0 right-0 z-50 flex w-100 flex-col bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="h-3 w-3 rounded-full" :style="{ background: agSelecionado.tipo_servico?.cor ?? '#00c1b3' }"></div>
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ agSelecionado.tipo_servico?.nome ?? 'Consulta' }}
                    </h2>
                    <svg v-if="agSelecionado.urgencia" class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <button @click="drawerAg = false" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Conteúdo -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <!-- Status atual + transições -->
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400">Status</p>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold"
                        :class="statusCor[agSelecionado.status] ?? 'bg-gray-100 text-gray-600'">
                        {{ statusLabel[agSelecionado.status] ?? agSelecionado.status }}
                    </span>
                    <div v-if="proximosStatus[agSelecionado.status]?.length" class="mt-3 flex flex-wrap gap-2">
                        <button v-for="s in proximosStatus[agSelecionado.status]" :key="s"
                            @click="mudarStatus(s)"
                            :disabled="atualizandoStatus"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition disabled:opacity-50"
                            :class="{
                                'border-green-200 text-green-700 hover:bg-green-50': s === 'finalizado',
                                'border-red-200 text-red-500 hover:bg-red-50': s === 'cancelado',
                                'border-primary-200 text-primary-700 hover:bg-primary-50': !['finalizado','cancelado'].includes(s),
                            }">
                            → {{ statusLabel[s] }}
                        </button>
                    </div>
                </div>

                <!-- Dados do atendimento -->
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400">Atendimento</p>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Data</dt>
                            <dd class="font-medium text-gray-800">{{ new Date(agSelecionado.data + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short' }) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Horário</dt>
                            <dd class="font-medium text-gray-800">{{ agSelecionado.hora_inicio.substring(0,5) }} – {{ agSelecionado.hora_fim.substring(0,5) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Veterinário</dt>
                            <dd class="font-medium text-gray-800">{{ agSelecionado.veterinario.name }}</dd>
                        </div>
                        <div v-if="agSelecionado.valor_estimado" class="flex justify-between">
                            <dt class="text-gray-500">Valor estimado</dt>
                            <dd class="font-medium text-gray-800">R$ {{ Number(agSelecionado.valor_estimado).toFixed(2) }}</dd>
                        </div>
                        <div v-if="agSelecionado.primeira_consulta" class="flex justify-between">
                            <dt class="text-gray-500">Tipo</dt>
                            <dd class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600">Primeira consulta</dd>
                        </div>
                    </dl>
                </div>

                <!-- Paciente -->
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400">Paciente</p>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ agSelecionado.paciente.nome }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ agSelecionado.paciente.especie }} · Tutor: {{ agSelecionado.cliente.nome }}</p>
                        </div>
                        <Link :href="`/pacientes/${agSelecionado.paciente.id}`"
                            class="rounded p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-600">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </Link>
                    </div>
                </div>

                <!-- Observações -->
                <div v-if="agSelecionado.observacoes_publicas">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-gray-400">Observações</p>
                    <p class="rounded-lg bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ agSelecionado.observacoes_publicas }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="excluirAgendamento" class="text-xs text-red-400 hover:text-red-500">Excluir</button>
                <div class="flex gap-2">
                    <Link v-if="['em_atendimento','finalizado'].includes(agSelecionado.status)"
                        :href="`/prontuarios/paciente/${agSelecionado.paciente.id}`"
                        class="rounded-lg bg-primary-500 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-600">
                        Abrir prontuário
                    </Link>
                    <button v-else @click="drawerAg = false"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-xs text-gray-600 hover:bg-gray-50">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <!-- ── Modal Novo Agendamento ─────────────────────────────────────────────── -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Novo agendamento</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">

                <!-- Busca de paciente -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Paciente *</label>
                    <div v-if="pacienteSelecionado"
                        class="flex items-center justify-between rounded-lg border border-primary-300 bg-primary-50 px-3 py-2">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ getEspecieIcon(pacienteSelecionado.especie) }}</span>
                            <div class="leading-tight">
                                <p class="text-sm font-semibold text-primary-800">{{ pacienteSelecionado.nome }}</p>
                                <p class="text-[11px] text-primary-500">{{ pacienteSelecionado.especie }}{{ pacienteSelecionado.raca ? ' · ' + pacienteSelecionado.raca : '' }} · {{ pacienteSelecionado.cliente.nome }}</p>
                            </div>
                        </div>
                        <button @click="limparPaciente" class="text-primary-400 hover:text-red-500 text-lg leading-none">×</button>
                    </div>
                    <div v-else class="relative">
                        <input v-model="buscaPaciente" @input="onBuscaPaciente"
                            @focus="mostrarDropdown = resultadosPaciente.length > 0"
                            @blur="setTimeout(() => mostrarDropdown = false, 180)"
                            type="text" placeholder="Buscar por nome ou microchip..."
                            class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                        <svg v-if="!buscandoPaciente" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <svg v-else class="absolute left-3 top-2.5 h-4 w-4 animate-spin text-primary-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <div v-if="mostrarDropdown && resultadosPaciente.length"
                            class="absolute z-50 mt-1 w-full rounded-xl border border-gray-100 bg-white shadow-lg overflow-hidden">
                            <button v-for="p in resultadosPaciente" :key="p.id"
                                @mousedown.prevent="selecionarPaciente(p)"
                                class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 border-b border-gray-50 last:border-b-0">
                                <span class="text-base">{{ getEspecieIcon(p.especie) }}</span>
                                <div class="min-w-0 flex-1 leading-tight">
                                    <p class="font-medium text-gray-800 truncate">{{ p.nome }}</p>
                                    <p class="text-[11px] text-gray-400 truncate">{{ p.especie }}{{ p.raca ? ' · ' + p.raca : '' }} · {{ p.cliente.nome }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Veterinário + tipo serviço -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Veterinário *</label>
                        <select v-model="agForm.veterinario_id" @change="carregarSlots"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Selecionar...</option>
                            <option v-for="v in veterinarios" :key="v.id" :value="String(v.id)">{{ v.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Tipo de serviço</label>
                        <select v-model="agForm.tipo_servico_id"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Nenhum</option>
                            <option v-for="t in tipos_servico" :key="t.id" :value="String(t.id)">{{ t.nome }}</option>
                        </select>
                    </div>
                </div>

                <!-- Data -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Data *</label>
                    <input v-model="agForm.data" @change="carregarSlots" type="date"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>

                <!-- Slots livres -->
                <div v-if="agForm.veterinario_id && agForm.data">
                    <label class="mb-1 block text-xs font-medium text-gray-600">
                        Horários disponíveis
                        <span v-if="carregandoSlots" class="text-gray-400">(carregando...)</span>
                        <span v-else-if="!slotsLivres.length" class="text-amber-500">(nenhum slot — defina horário manualmente)</span>
                    </label>
                    <div v-if="slotsLivres.length" class="flex flex-wrap gap-1.5 mb-2">
                        <button v-for="slot in slotsLivres" :key="slot.hora_inicio"
                            @click="selecionarSlot(slot)"
                            type="button"
                            class="rounded-lg border px-2.5 py-1 text-xs font-medium transition"
                            :class="agForm.hora_inicio === slot.hora_inicio
                                ? 'border-primary-400 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-600 hover:border-primary-300 hover:bg-gray-50'">
                            {{ slot.hora_inicio }}
                        </button>
                    </div>
                </div>

                <!-- Horário manual -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Início *</label>
                        <input v-model="agForm.hora_inicio" type="time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Fim *</label>
                        <input v-model="agForm.hora_fim" type="time" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                            <input v-model="agForm.urgencia" type="checkbox" class="rounded" />
                            <svg class="h-3.5 w-3.5 text-red-400" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            Urgência
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Observações</label>
                    <textarea v-model="agForm.observacoes_publicas" rows="2"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarAgendamento"
                    :disabled="!agForm.paciente_id || !agForm.veterinario_id"
                    class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    Agendar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
