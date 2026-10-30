<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RichText from '@/components/RichText.vue';

defineOptions({ layout: AppLayout });

type Agendamento = {
    id: number;
    data: string; // cast 'date' → serializado como ISO datetime pelo Laravel
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
    veterinarios: Array<{ id: number; name: string; avatar: string | null; crmv?: string | null; especialidades: string[] }>;
    tipos_servico: Array<{ id: number; nome: string; cor: string; duracao_minutos: number }>;
    especialidades: Array<{ id: number; nome: string }>;
    semana_inicio: string;
    dias_ativos: number[]; // 0=dom, 1=seg … 6=sáb
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
const horaAtualTop = computed(() => Math.max(0, ((horaAtualMinutos - 360) / 60) * 96));
const horaAtualLabel = computed(() => `${String(agora.getHours()).padStart(2, '0')}:${String(agora.getMinutes()).padStart(2, '0')}`);

function agendamentoPosicao(ag: Agendamento) {
    const [h, m] = ag.hora_inicio.split(':').map(Number);
    const [hf, mf] = ag.hora_fim.split(':').map(Number);
    const top = ((h * 60 + m) - 360) / 60 * 96;
    const height = ((hf * 60 + mf) - (h * 60 + m)) / 60 * 96;
    return { top: Math.max(0, top), height: Math.max(80, height) };
}

function toMin(t: string) {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

// Retorna { col, total } para cada agendamento de um dia, calculando colunas por sobreposição
function styleCardSemana(ag: Agendamento, ags: Agendamento[]): Record<string, string> {
    const pos = agendamentoPosicao(ag);
    const cols = calcularColunas(ags);
    const { col, total } = cols.get(ag.id) ?? { col: 0, total: 1 };
    const cor = vetCor.value[ag.veterinario.id];
    const pct = 100 / total;
    return {
        top: pos.top + 'px',
        height: pos.height + 'px',
        left: `calc(${col * pct}% + 2px)`,
        width: `calc(${pct}% - 4px)`,
        backgroundColor: blendWithWhite(cor, 0.12),
        border: '1px solid ' + blendWithWhite(cor, 0.4),
        borderLeft: '4px solid ' + cor,
        outline: '2px solid white',
    };
}

function calcularColunas(ags: Agendamento[]): Map<number, { col: number; total: number }> {
    const result = new Map<number, { col: number; total: number }>();
    // Ordena por hora de início
    const sorted = [...ags].sort((a, b) => toMin(a.hora_inicio) - toMin(b.hora_inicio));

    // Grupos de sobreposição: cada grupo contém agendamentos que se intersectam
    const groups: Agendamento[][] = [];

    for (const ag of sorted) {
        const agStart = toMin(ag.hora_inicio);
        const agEnd = toMin(ag.hora_fim);
        // Tenta encaixar num grupo existente
        let placed = false;
        for (const group of groups) {
            const groupEnd = Math.max(...group.map(g => toMin(g.hora_fim)));
            if (agStart < groupEnd) {
                group.push(ag);
                placed = true;
                break;
            }
        }
        if (!placed) groups.push([ag]);
    }

    for (const group of groups) {
        // Atribui colunas dentro do grupo evitando sobreposição
        const cols: Agendamento[][] = [];
        for (const ag of group) {
            const agStart = toMin(ag.hora_inicio);
            let placed = false;
            for (const col of cols) {
                const lastEnd = Math.max(...col.map(c => toMin(c.hora_fim)));
                if (agStart >= lastEnd) {
                    col.push(ag);
                    placed = true;
                    break;
                }
            }
            if (!placed) cols.push([ag]);
        }
        const total = cols.length;
        cols.forEach((col, colIdx) => {
            col.forEach(ag => result.set(ag.id, { col: colIdx, total }));
        });
    }

    return result;
}

function diaBloqueado(dateStr: string) {
    const dow = new Date(dateStr + 'T12:00:00').getDay(); // 0=dom
    return props.dias_ativos.length > 0 && !props.dias_ativos.includes(dow);
}

function navSemana(dir: number) {
    const d = new Date(props.semana_inicio + 'T00:00:00');
    d.setDate(d.getDate() + dir * 7);
    router.get('/agenda', {
        semana: d.toISOString().split('T')[0],
        veterinario_id: filtroVet.value || undefined,
        especialidade_id: filtroEsp.value || undefined
    }, { preserveState: true });
}

const labelSemana = computed(() => {
    const fim = new Date(props.semana_inicio + 'T00:00:00');
    fim.setDate(fim.getDate() + 6);
    const mesAno = fim.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }).replace('.', '');
    const d1 = new Date(props.semana_inicio + 'T00:00:00').getDate();
    return `${d1} – ${fim.getDate()} de ${mesAno}`;
});

// ── Filtro por veterinário e especialidade ───────────────────────────────────
const filtroVet = ref<number | ''>('');
const filtroEsp = ref<number | ''>('');

function agendamentosNoDia(data: string) {
    return props.agendamentos.filter(a =>
        String(a.data).substring(0, 10) === data && (filtroVet.value === '' || a.veterinario.id === filtroVet.value)
    );
}

function aplicarFiltroVet(id: number | '') {
    filtroVet.value = id;
    router.get('/agenda', {
        semana: props.semana_inicio,
        veterinario_id: id || undefined,
        especialidade_id: filtroEsp.value || undefined
    }, { preserveState: true });
}

function aplicarFiltroEsp(id: number | '') {
    filtroEsp.value = id;
    filtroVet.value = ''; // Limpa filtro de veterinário ao filtrar por especialidade
    router.get('/agenda', {
        semana: props.semana_inicio,
        especialidade_id: id || undefined
    }, { preserveState: true });
}

// ── View mode (semana / dia) ──────────────────────────────────────────────────
const viewMode = ref<'semana' | 'dia'>('semana');

const diaAtual = ref(new Date().toISOString().split('T')[0]);

const labelDia = computed(() => {
    const d = new Date(diaAtual.value + 'T12:00:00');
    return d.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
});

function navDia(dir: number) {
    const d = new Date(diaAtual.value + 'T12:00:00');
    d.setDate(d.getDate() + dir);
    const nova = d.toISOString().split('T')[0];
    diaAtual.value = nova;
    // Se sair da semana carregada, navegar no servidor
    const semanaFim = new Date(props.semana_inicio + 'T00:00:00');
    semanaFim.setDate(semanaFim.getDate() + 6);
    const semanaFimStr = semanaFim.toISOString().split('T')[0];
    if (nova < props.semana_inicio || nova > semanaFimStr) {
        router.get('/agenda', {
            semana: nova,
            veterinario_id: filtroVet.value || undefined,
            especialidade_id: filtroEsp.value || undefined
        }, {
            preserveState: true,
            onSuccess: () => { diaAtual.value = nova; },
        });
    }
}

// Paleta de cores por veterinário (índice → cor)
const VET_CORES = [
    '#6366f1', // indigo
    '#ec4899', // pink
    '#f59e0b', // amber
    '#10b981', // emerald
    '#3b82f6', // blue
    '#8b5cf6', // violet
    '#ef4444', // red
    '#14b8a6', // teal
];

// Mistura uma cor hex com branco. mix=0 → branco puro, mix=1 → cor pura
function blendWithWhite(hex: string, mix: number): string {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const nr = Math.round(255 + (r - 255) * mix);
    const ng = Math.round(255 + (g - 255) * mix);
    const nb = Math.round(255 + (b - 255) * mix);
    return `rgb(${nr},${ng},${nb})`;
}

const vetCor = computed(() => {
    const map: Record<number, string> = {};
    props.veterinarios.forEach((v, i) => { map[v.id] = VET_CORES[i % VET_CORES.length]; });
    return map;
});

function agendamentosNoDiaPorVet(vetId: number) {
    return props.agendamentos.filter(a =>
        String(a.data).substring(0, 10) === diaAtual.value && a.veterinario.id === vetId
    );
}

const vetsComAgendamentosHoje = computed(() =>
    props.veterinarios.filter(v => agendamentosNoDiaPorVet(v.id).length > 0 || filtroVet.value === v.id || filtroVet.value === '')
);

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
const agForm = useForm({
    cliente_id: '' as string | number,
    paciente_id: '' as string | number,
    veterinario_id: '',
    tipo_servico_id: '',
    agData: new Date().toISOString().split('T')[0],
    hora_inicio: '08:00',
    hora_fim: '08:30',
    observacoes_publicas: '',
    urgencia: false,
});

// Slots livres do DisponibilidadeService
const slotsLivres = ref<Array<{ hora_inicio: string; hora_fim: string }>>([]);
const carregandoSlots = ref(false);

async function carregarSlots() {
    if (!agForm.veterinario_id || !agForm.agData) { slotsLivres.value = []; return; }
    carregandoSlots.value = true;
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/agendamentos/disponibilidade?veterinario_id=${agForm.veterinario_id}&data=${agForm.agData}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    slotsLivres.value = res.ok ? await res.json() : [];
    carregandoSlots.value = false;
}

function selecionarSlot(slot: { hora_inicio: string; hora_fim: string }) {
    agForm.hora_inicio = slot.hora_inicio;
    agForm.hora_fim = slot.hora_fim;
}

function abrirModal() {
    agForm.reset();
    agForm.agData = new Date().toISOString().split('T')[0];
    tutorSelecionado.value = null;
    petsDoTutor.value = [];
    pacienteSelecionado.value = null;
    buscaTutor.value = '';
    resultadosTutor.value = [];
    slotsLivres.value = [];
    showModal.value = true;
}

function salvarAgendamento() {
    agForm.transform(d => {
        const { agData, ...rest } = d as any;
        return { ...rest, data: agData };
    }).post('/agendamentos', { onSuccess: () => { showModal.value = false; } });
}

// ── Busca por tutor → selecionar pet ─────────────────────────────────────────
type TutorResult = { id: number; nome: string; celular: string | null; email: string | null };
type PetResult   = { id: number; nome: string; especie: string; raca: string | null };

const buscaTutor = ref('');
const resultadosTutor = ref<TutorResult[]>([]);
const tutorSelecionado = ref<TutorResult | null>(null);
const petsDoTutor = ref<PetResult[]>([]);
const pacienteSelecionado = ref<PetResult | null>(null);
const buscandoTutor = ref(false);
const mostrarDropdownTutor = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

async function onBuscaTutor() {
    clearTimeout(debounceTimer);
    tutorSelecionado.value = null;
    petsDoTutor.value = [];
    pacienteSelecionado.value = null;
    agForm.paciente_id = '';
    agForm.cliente_id = '';
    if (buscaTutor.value.length < 2) { resultadosTutor.value = []; mostrarDropdownTutor.value = false; return; }
    debounceTimer = setTimeout(async () => {
        buscandoTutor.value = true;
        const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/clientes/buscar?q=${encodeURIComponent(buscaTutor.value)}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        resultadosTutor.value = res.ok ? await res.json() : [];
        mostrarDropdownTutor.value = true;
        buscandoTutor.value = false;
    }, 280);
}

async function selecionarTutor(t: TutorResult) {
    tutorSelecionado.value = t;
    agForm.cliente_id = t.id;
    buscaTutor.value = t.nome;
    mostrarDropdownTutor.value = false;
    resultadosTutor.value = [];
    // Carrega pets do tutor
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/clientes/${t.id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } });
    if (res.ok) {
        const data = await res.json();
        petsDoTutor.value = data.pacientes ?? [];
        // Se tiver apenas 1 pet, seleciona automaticamente
        if (petsDoTutor.value.length === 1) selecionarPet(petsDoTutor.value[0]);
    }
}

function selecionarPet(p: PetResult) {
    pacienteSelecionado.value = p;
    agForm.paciente_id = p.id;
}

function limparTutor() {
    tutorSelecionado.value = null;
    petsDoTutor.value = [];
    pacienteSelecionado.value = null;
    agForm.paciente_id = '';
    agForm.cliente_id = '';
    buscaTutor.value = '';
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

    // Relatório por especialidade
    const porEspecialidade = props.especialidades.map(esp => {
        // Encontra todos os vet que têm essa especialidade
        const vetsComEsp = props.veterinarios.filter(v => v.especialidades.includes(esp.nome));
        const vetsIds = vetsComEsp.map(v => v.id);

        // Filtra agendamentos desses vets
        const ags = props.agendamentos.filter(a => vetsIds.includes(a.veterinario.id));

        return {
            especialidade: esp,
            total: ags.length,
            finalizados: ags.filter(a => a.status === 'finalizado').length,
            cancelados: ags.filter(a => a.status === 'cancelado').length,
            veterinarios: vetsComEsp.length,
        };
    }).filter(r => r.total > 0);

    const porDia = diasSemana.value.map(d => ({
        label: d.label,
        num: d.num,
        count: props.agendamentos.filter(a => a.data === d.date).length,
    }));

    const maxDia = Math.max(...porDia.map(d => d.count), 1);

    return { total, finalizados, cancelados, taxaComp, porVet, porEspecialidade, porDia, maxDia };
});
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden">

        <!-- Header -->
        <div class="border-b border-gray-100 bg-white px-8">
            <!-- Linha principal -->
            <div class="flex items-center justify-between py-4">
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

                    <!-- Toggle semana / dia -->
                    <div v-if="abaSelecionada === 'agenda'" class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                        <button @click="viewMode = 'semana'"
                            class="px-3 py-1.5 transition"
                            :class="viewMode === 'semana' ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-50'">
                            Semana
                        </button>
                        <button @click="viewMode = 'dia'"
                            class="px-3 py-1.5 border-l border-gray-200 transition"
                            :class="viewMode === 'dia' ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-50'">
                            Dia
                        </button>
                    </div>

                    <!-- Navegação de semana -->
                    <div v-if="abaSelecionada === 'agenda' && viewMode === 'semana'" class="flex items-center gap-2 text-sm text-gray-600">
                        <button @click="navSemana(-1)" class="rounded p-1 hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="font-medium text-xs">{{ labelSemana }}</span>
                        <button @click="navSemana(1)" class="rounded p-1 hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- Navegação de dia -->
                    <div v-if="abaSelecionada === 'agenda' && viewMode === 'dia'" class="flex items-center gap-2 text-sm text-gray-600">
                        <button @click="navDia(-1)" class="rounded p-1 hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="font-medium text-xs capitalize">{{ labelDia }}</span>
                        <button @click="navDia(1)" class="rounded p-1 hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <button @click="abrirModal" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Novo agendamento
                    </button>
                </div>
            </div>

            <!-- Filtro por Especialidade (view semana) -->
            <div v-if="abaSelecionada === 'agenda' && viewMode === 'semana' && especialidades.length > 0" class="flex items-center gap-2 pb-2 border-b border-gray-100 mb-2">
                <span class="text-xs font-medium text-gray-600">Especialidade:</span>
                <button @click="aplicarFiltroEsp('')"
                    class="rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="filtroEsp === '' ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:bg-gray-50'">
                    Todas
                </button>
                <button v-for="esp in especialidades" :key="esp.id"
                    @click="aplicarFiltroEsp(esp.id)"
                    class="rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="filtroEsp === esp.id ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:bg-gray-50'">
                    {{ esp.nome }}
                </button>
            </div>

            <!-- Filtro por Veterinário (view semana) -->
            <div v-if="abaSelecionada === 'agenda' && viewMode === 'semana'" class="flex items-center gap-2 pb-3">
                <span class="text-xs font-medium text-gray-600">Veterinário:</span>
                <button @click="aplicarFiltroVet('')"
                    class="rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="filtroVet === '' ? 'bg-primary-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'">
                    Todos
                </button>
                <button v-for="v in veterinarios" :key="v.id"
                    @click="aplicarFiltroVet(v.id)"
                    class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium transition"
                    :class="filtroVet === v.id ? 'bg-primary-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                        :style="{ backgroundColor: vetCor[v.id] }">
                        {{ v.name.charAt(0) }}
                    </span>
                    {{ v.name.split(' ')[0] }}
                </button>
            </div>
        </div>

        <!-- ABA: Agenda — view semana -->
        <div v-if="abaSelecionada === 'agenda' && viewMode === 'semana'" class="flex flex-1 flex-col overflow-hidden bg-white">
            <!-- Header fixo: canto + nomes dos dias -->
            <div class="flex shrink-0 border-b border-gray-100">
                <div class="w-16 shrink-0 border-r border-gray-100"></div>
                <div class="flex flex-1 overflow-x-hidden">
                    <div v-for="dia in diasSemana" :key="dia.date"
                        class="flex min-w-0 flex-1 flex-col items-center justify-center py-2 border-r border-gray-100 last:border-r-0">
                        <span class="text-[11px] uppercase text-gray-400">{{ dia.label }}</span>
                        <span class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                            :class="dia.isToday ? 'bg-primary-500 text-white' : 'text-gray-700'">
                            {{ dia.num }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Body rolável: horas + eventos juntos -->
            <div class="relative flex flex-1 overflow-y-auto overflow-x-auto">
                <!-- Linha do horário atual — cobre coluna de horas + grid -->
                <div class="pointer-events-none absolute left-0 right-0 z-30 flex items-center" :style="{ top: horaAtualTop + 'px' }">
                    <span class="shrink-0 rounded-r bg-red-500 px-1 py-0.5 text-[9px] font-bold text-white">{{ horaAtualLabel }}</span>
                    <div class="h-px flex-1 bg-red-400"></div>
                </div>

                <!-- Coluna de horas (rola junto) -->
                <div class="w-16 shrink-0 border-r border-gray-100">
                    <div v-for="hora in horas" :key="hora" class="flex h-24 items-start justify-end pr-2 pt-1">
                        <span class="text-[11px] text-gray-400">{{ hora }}</span>
                    </div>
                </div>

                <!-- Grid dos dias -->
                <div class="relative flex flex-1">

                    <div v-for="dia in diasSemana" :key="dia.date"
                        class="relative min-w-0 flex-1 border-r border-gray-100 last:border-r-0">
                        <!-- Fundo com linhas diagonais para dias sem funcionamento -->
                        <div v-if="diaBloqueado(dia.date)"
                            class="absolute inset-0 z-0 pointer-events-none"
                            style="background: repeating-linear-gradient(135deg, transparent, transparent 8px, rgba(0,0,0,0.035) 8px, rgba(0,0,0,0.035) 10px);">
                        </div>

                        <div v-for="hora in horas" :key="hora" class="h-24 border-b border-gray-50"></div>

                        <!-- Agendamentos -->
                        <template v-if="agendamentosNoDia(dia.date).length">
                        <div v-for="ag in agendamentosNoDia(dia.date)" :key="ag.id"
                            @click="abrirDetalhes(ag)"
                            :title="ag.status === 'finalizado' ? 'Consulta finalizada' : ''"
                            class="absolute z-20 cursor-pointer overflow-hidden rounded-md pl-3 pr-2 py-1.5 text-xs transition hover:brightness-95"
                            :class="ag.status === 'finalizado' ? 'opacity-40' : ''"
                            :style="styleCardSemana(ag, agendamentosNoDia(dia.date))">
                            <div class="flex items-start justify-between gap-1">
                                <p class="truncate font-bold text-gray-800 leading-tight text-xs">{{ ag.cliente?.nome }}</p>
                                <svg v-if="ag.urgencia" class="h-3 w-3 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            </div>
                            <p class="truncate text-gray-600 text-[11px] leading-tight">{{ getEspecieIcon(ag.paciente.especie) }} {{ ag.paciente?.nome }}</p>
                            <p class="truncate text-[10px] font-semibold leading-tight" :style="{ color: vetCor[ag.veterinario.id] }">{{ ag.hora_inicio.substring(0,5) }} – {{ ag.hora_fim.substring(0,5) }}</p>
                            <!-- Pill do tipo de serviço -->
                            <span v-if="ag.tipo_servico"
                                class="mt-1 inline-block rounded px-1.5 py-0.5 text-[9px] font-semibold leading-none text-white"
                                :style="{ backgroundColor: vetCor[ag.veterinario.id] }">
                                {{ ag.tipo_servico.nome }}
                            </span>
                        </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA: Agenda — view dia -->
        <div v-else-if="abaSelecionada === 'agenda' && viewMode === 'dia'" class="flex flex-1 flex-col overflow-hidden bg-white">
            <!-- Avatars dos vets no topo -->
            <div class="flex shrink-0 items-center gap-3 border-b border-gray-100 px-4 py-3">
                <div v-for="v in props.veterinarios" :key="v.id"
                    class="flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
                    :style="{ borderColor: vetCor[v.id], color: vetCor[v.id], backgroundColor: vetCor[v.id] + '18' }">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                        :style="{ backgroundColor: vetCor[v.id] }">
                        {{ v.name.charAt(0) }}
                    </span>
                    {{ v.name.split(' ')[0] }}
                    <span class="ml-0.5 rounded-full px-1.5 text-[10px]"
                        :style="{ backgroundColor: vetCor[v.id], color: '#fff' }">
                        {{ agendamentosNoDiaPorVet(v.id).length }}
                    </span>
                </div>
            </div>

            <!-- Header: hora + colunas por vet -->
            <div class="flex shrink-0 border-b border-gray-100">
                <div class="w-16 shrink-0 border-r border-gray-100"></div>
                <div class="flex flex-1 overflow-x-hidden">
                    <div v-for="v in vetsComAgendamentosHoje" :key="v.id"
                        class="flex min-w-0 flex-1 items-center justify-center gap-2 py-2.5 border-r border-gray-100 last:border-r-0">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white"
                            :style="{ backgroundColor: vetCor[v.id] }">
                            {{ v.name.charAt(0) }}
                        </span>
                        <span class="text-xs font-semibold text-gray-700">{{ v.name.split(' ')[0] }}</span>
                    </div>
                </div>
            </div>

            <!-- Body rolável -->
            <div class="flex flex-1 overflow-y-auto overflow-x-auto">
                <!-- Coluna de horas -->
                <div class="w-16 shrink-0 border-r border-gray-100">
                    <div v-for="hora in horas" :key="hora" class="flex h-24 items-start justify-end pr-2 pt-1">
                        <span class="text-[11px] text-gray-400">{{ hora }}</span>
                    </div>
                </div>

                <!-- Colunas por vet -->
                <div class="flex flex-1">
                    <div v-for="v in vetsComAgendamentosHoje" :key="v.id"
                        class="relative min-w-0 flex-1 border-r border-gray-100 last:border-r-0">
                        <!-- Grid de horas -->
                        <div v-for="hora in horas" :key="hora" class="h-24 border-b border-gray-50"></div>

                        <!-- Linha do horário atual (hoje) -->
                        <div v-if="diaAtual === new Date().toISOString().split('T')[0]"
                            class="absolute left-0 right-0 z-10 flex items-center"
                            :style="{ top: horaAtualTop + 'px' }">
                            <div class="h-px flex-1 bg-red-400"></div>
                            <span class="shrink-0 rounded-l bg-red-500 px-1 py-0.5 text-[9px] font-bold text-white">{{ horaAtualLabel }}</span>
                        </div>

                        <!-- Cards de agendamento -->
                        <div v-for="ag in agendamentosNoDiaPorVet(v.id)" :key="ag.id"
                            @click="abrirDetalhes(ag)"
                            :title="ag.status === 'finalizado' ? 'Consulta finalizada' : ''"
                            class="absolute left-1 right-1 z-20 cursor-pointer overflow-hidden rounded-lg pl-3 pr-2.5 py-2 transition hover:brightness-95"
                            :class="ag.status === 'finalizado' ? 'opacity-40' : ''"
                            :style="{
                                top: agendamentoPosicao(ag).top + 'px',
                                height: agendamentoPosicao(ag).height + 'px',
                                backgroundColor: blendWithWhite(vetCor[v.id], 0.12),
                                border: '1px solid ' + blendWithWhite(vetCor[v.id], 0.4),
                                borderLeft: '4px solid ' + vetCor[v.id],
                            }">
                            <!-- Nome do cliente -->
                            <div class="flex items-start justify-between gap-1">
                                <p class="truncate font-bold text-gray-800 leading-tight text-xs">{{ ag.cliente.nome }}</p>
                            </div>
                            <!-- Nome do pet + espécie -->
                            <p class="truncate text-gray-600 text-[11px] leading-tight mt-0.5">
                                {{ getEspecieIcon(ag.paciente.especie) }} {{ ag.paciente.nome }}
                            </p>
                            <!-- Hora -->
                            <p class="text-[10px] font-semibold leading-tight mt-0.5" :style="{ color: vetCor[v.id] }">
                                {{ ag.hora_inicio.substring(0,5) }} – {{ ag.hora_fim.substring(0,5) }}
                            </p>
                            <!-- Tipo de serviço -->
                            <span v-if="ag.tipo_servico"
                                class="mt-1 inline-block rounded px-1.5 py-0.5 text-[9px] font-semibold leading-none text-white"
                                :style="{ backgroundColor: vetCor[v.id] }">
                                {{ ag.tipo_servico.nome }}
                            </span>
                            <!-- Urgência -->
                            <svg v-if="ag.urgencia" class="absolute right-1.5 top-1.5 h-3 w-3 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
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

                <!-- Por especialidade -->
                <div v-if="relatorios.porEspecialidade.length > 0" class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">Por especialidade</h3>
                    <div class="space-y-3">
                        <div v-for="r in relatorios.porEspecialidade" :key="r.especialidade.id" class="flex items-center gap-4">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                {{ r.especialidade.nome.charAt(0) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div>
                                        <span class="text-sm font-medium text-gray-800">{{ r.especialidade.nome }}</span>
                                        <span class="ml-2 text-xs text-gray-400">({{ r.veterinarios }} vet{{ r.veterinarios > 1 ? 's' : '' }})</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ r.finalizados }}/{{ r.total }} realizados</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full bg-blue-400 transition-all"
                                        :style="{ width: r.total ? `${(r.finalizados / r.total) * 100}%` : '0%' }">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <div class="rounded-lg bg-gray-50 px-3 py-2.5 text-sm text-gray-700 prose prose-sm max-w-none" v-html="agSelecionado.observacoes_publicas"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4">
                <button @click="excluirAgendamento" class="text-xs text-red-400 hover:text-red-500">Excluir</button>
                <div class="flex gap-2">
                    <Link v-if="['em_atendimento','finalizado'].includes(agSelecionado.status)"
                        :href="agSelecionado.status === 'em_atendimento'
                            ? `/prontuarios/paciente/${agSelecionado.paciente.id}?agendamento_id=${agSelecionado.id}`
                            : `/prontuarios/paciente/${agSelecionado.paciente.id}`"
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

                <!-- Busca tutor → selecionar pet -->
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Tutor (cliente) *</label>
                    <div v-if="tutorSelecionado"
                        class="flex items-center justify-between rounded-lg border border-primary-300 bg-primary-50 px-3 py-2">
                        <div class="leading-tight">
                            <p class="text-sm font-semibold text-primary-800">{{ tutorSelecionado.nome }}</p>
                            <p v-if="tutorSelecionado.celular" class="text-[11px] text-primary-500">{{ tutorSelecionado.celular }}</p>
                        </div>
                        <button @click="limparTutor" class="text-primary-400 hover:text-red-500 text-lg leading-none">×</button>
                    </div>
                    <div v-else class="relative">
                        <input v-model="buscaTutor" @input="onBuscaTutor"
                            @focus="mostrarDropdownTutor = resultadosTutor.length > 0"
                            @blur="window.setTimeout(() => mostrarDropdownTutor = false, 180)"
                            type="text" placeholder="Buscar tutor por nome, CPF ou celular..."
                            class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                        <svg v-if="!buscandoTutor" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <svg v-else class="absolute left-3 top-2.5 h-4 w-4 animate-spin text-primary-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <div v-if="mostrarDropdownTutor && resultadosTutor.length"
                            class="absolute z-50 mt-1 w-full rounded-xl border border-gray-100 bg-white shadow-lg overflow-hidden">
                            <button v-for="t in resultadosTutor" :key="t.id"
                                @mousedown.prevent="selecionarTutor(t)"
                                class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 border-b border-gray-50 last:border-b-0">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ t.nome.charAt(0) }}
                                </div>
                                <div class="min-w-0 flex-1 leading-tight">
                                    <p class="font-medium text-gray-800 truncate">{{ t.nome }}</p>
                                    <p v-if="t.celular" class="text-[11px] text-gray-400">{{ t.celular }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Seleção do pet (aparece após selecionar tutor) -->
                <div v-if="tutorSelecionado">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Animal *</label>
                    <div v-if="!petsDoTutor.length" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        Este tutor não possui animais cadastrados.
                    </div>
                    <div v-else class="flex flex-wrap gap-2">
                        <button v-for="p in petsDoTutor" :key="p.id"
                            @click="selecionarPet(p)"
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="pacienteSelecionado?.id === p.id
                                ? 'border-primary-400 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-600 hover:border-primary-300 hover:bg-gray-50'">
                            <span>{{ getEspecieIcon(p.especie) }}</span>
                            {{ p.nome }}
                            <span v-if="p.raca" class="text-gray-400">· {{ p.raca }}</span>
                        </button>
                    </div>
                </div>

                <!-- Veterinário + tipo serviço -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Veterinário *</label>
                        <select v-model="agForm.veterinario_id" @change="carregarSlots"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="">Selecionar...</option>
                            <option v-for="v in veterinarios" :key="v.id" :value="String(v.id)">
                                {{ v.name }}{{ v.especialidades.length > 0 ? ` (${v.especialidades.join(', ')})` : '' }}
                            </option>
                        </select>
                        <!-- Badge de especialidades do veterinário selecionado -->
                        <div v-if="agForm.veterinario_id" class="mt-1 flex flex-wrap gap-1">
                            <span
                                v-for="esp in veterinarios.find(v => v.id === parseInt(agForm.veterinario_id))?.especialidades"
                                :key="esp"
                                class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700"
                            >
                                {{ esp }}
                            </span>
                        </div>
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
                    <input v-model="agForm.agData" @change="carregarSlots" type="date"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>

                <!-- Slots livres -->
                <div v-if="agForm.veterinario_id && agForm.agData">
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
                    <RichText v-model="agForm.observacoes_publicas" min-height="60px" />
                </div>
            </div>

            <!-- Erros de validação do backend -->
            <div v-if="Object.keys(agForm.errors).length" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                <p v-for="(msg, field) in agForm.errors" :key="field" class="text-xs text-red-600">{{ msg }}</p>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarAgendamento"
                    :disabled="agForm.processing || !agForm.paciente_id || !agForm.veterinario_id || !agForm.cliente_id"
                    class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-40 disabled:cursor-not-allowed">
                    {{ agForm.processing ? 'Salvando...' : 'Agendar' }}
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
