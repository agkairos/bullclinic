<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    consulta: {
        id: number;
        tipo_consulta: string;
        status: string;
        iniciada_em: string;
        anamnese: string | null;
        exame_fisico: string | null;
        diagnostico: string | null;
        prognostico: string | null;
        observacoes: string | null;
        veterinario: { name: string; crmv?: string | null };
        paciente: {
            id: number;
            nome: string;
            especie: string;
            raca: string | null;
            peso: string | null;
            alergias: string | null;
            cliente: { nome: string };
            fotos: Array<{ url: string; principal: boolean }>;
            tags: Array<{ tag: string }>;
        };
        receitas: Array<{
            id: number; emitida_em: string;
            itens: Array<{ medicamento_nome: string; dosagem: string | null }>;
        }>;
        exames: Array<{
            id: number; descricao: string | null; status: string;
            tipoExame: { nome: string } | null;
            laboratorio: { nome: string } | null;
        }>;
        procedimentos: Array<{ id: number; tipo_procedimento: string; descricao: string | null; status: string; valor_cobrado: string | null }>;
        vacinacoes: Array<{ id: number; vacina_nome: string; lote: string | null; data_aplicacao: string; proxima_dose_em: string | null }>;
        documentos: any[];
    };
}>();

const csrf = () => (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';

// ── Abas ─────────────────────────────────────────────────────────────────────
const abaAtiva = ref<'anamnese' | 'receitas' | 'exames' | 'procedimentos' | 'vacinas'>('anamnese');

// ── Autosave ──────────────────────────────────────────────────────────────────
const salvando = ref(false);
const savedAt = ref<string | null>(null);
const form = ref({
    anamnese: props.consulta.anamnese ?? '',
    exame_fisico: props.consulta.exame_fisico ?? '',
    diagnostico: props.consulta.diagnostico ?? '',
    prognostico: props.consulta.prognostico ?? '',
    observacoes: props.consulta.observacoes ?? '',
});
let saveTimer: ReturnType<typeof setTimeout> | null = null;
watch(form, () => {
    if (props.consulta.status !== 'em_andamento') return;
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(autoSave, 1500);
}, { deep: true });
async function autoSave() {
    salvando.value = true;
    const res = await fetch(`/prontuarios/consulta/${props.consulta.id}/salvar`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(form.value),
    });
    const d = await res.json();
    savedAt.value = d.saved_at;
    salvando.value = false;
}

function finalizar() {
    if (!confirm('Finalizar esta consulta?')) return;
    router.patch(`/prontuarios/consulta/${props.consulta.id}/finalizar`);
}

// ── Receitas ──────────────────────────────────────────────────────────────────
const showReceita = ref(false);
const receitaForm = ref({
    instrucoes_gerais: '',
    itens: [{ medicamento_nome: '', dosagem: '', frequencia: '', duracao_tratamento: '', via_administracao: '', instrucoes_especificas: '' }],
});
function addItem() {
    receitaForm.value.itens.push({ medicamento_nome: '', dosagem: '', frequencia: '', duracao_tratamento: '', via_administracao: '', instrucoes_especificas: '' });
}
function removeItem(i: number) {
    if (receitaForm.value.itens.length > 1) receitaForm.value.itens.splice(i, 1);
}
async function salvarReceita() {
    await fetch(`/prontuarios/consulta/${props.consulta.id}/receitas`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(receitaForm.value),
    });
    showReceita.value = false;
    router.reload({ only: ['consulta'] });
}

// ── Exames ────────────────────────────────────────────────────────────────────
const showExame = ref(false);
const exameForm = ref({ tipo_exame_id: '', laboratorio_id: '', descricao: '', instrucoes: '' });
async function salvarExame() {
    await fetch(`/prontuarios/consulta/${props.consulta.id}/exames`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(exameForm.value),
    });
    showExame.value = false;
    router.reload({ only: ['consulta'] });
}

// Upload de resultado de exame
const exameResultadoId = ref<number | null>(null);
const resultadoFile = ref<File | null>(null);
const resultadoInterpretacao = ref('');
const uploadandoResultado = ref(false);
const resultadoInput = ref<HTMLInputElement | null>(null);

function abrirResultado(exameId: number) {
    exameResultadoId.value = exameId;
    resultadoFile.value = null;
    resultadoInterpretacao.value = '';
}
function onResultadoFile(e: Event) {
    resultadoFile.value = (e.target as HTMLInputElement).files?.[0] ?? null;
}
async function enviarResultado() {
    if (!resultadoFile.value || !exameResultadoId.value) return;
    uploadandoResultado.value = true;
    const fd = new FormData();
    fd.append('arquivo', resultadoFile.value);
    if (resultadoInterpretacao.value) fd.append('interpretacao', resultadoInterpretacao.value);
    await fetch(`/exames/${exameResultadoId.value}/resultado`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: fd,
    });
    exameResultadoId.value = null;
    uploadandoResultado.value = false;
    router.reload({ only: ['consulta'] });
}

// ── Procedimentos ─────────────────────────────────────────────────────────────
const showProcedimento = ref(false);
const editandoProcedimento = ref<typeof props.consulta.procedimentos[0] | null>(null);
const procForm = ref({ tipo_procedimento: '', descricao: '', status: 'realizado', valor_cobrado: '' });

function abrirNovoProcedimento() {
    editandoProcedimento.value = null;
    procForm.value = { tipo_procedimento: '', descricao: '', status: 'realizado', valor_cobrado: '' };
    showProcedimento.value = true;
}
function abrirEditarProcedimento(p: typeof props.consulta.procedimentos[0]) {
    editandoProcedimento.value = p;
    procForm.value = { tipo_procedimento: p.tipo_procedimento, descricao: p.descricao ?? '', status: p.status, valor_cobrado: p.valor_cobrado ?? '' };
    showProcedimento.value = true;
}
async function salvarProcedimento() {
    const url = editandoProcedimento.value
        ? `/procedimentos/${editandoProcedimento.value.id}`
        : `/prontuarios/consulta/${props.consulta.id}/procedimentos`;
    const method = editandoProcedimento.value ? 'PUT' : 'POST';
    await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(procForm.value),
    });
    showProcedimento.value = false;
    router.reload({ only: ['consulta'] });
}
async function excluirProcedimento(id: number) {
    if (!confirm('Excluir este procedimento?')) return;
    await fetch(`/procedimentos/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf() } });
    router.reload({ only: ['consulta'] });
}

// ── Vacinações ────────────────────────────────────────────────────────────────
const showVacina = ref(false);
const vacinaForm = ref({ vacina_nome: '', fabricante: '', lote: '', data_aplicacao: new Date().toISOString().split('T')[0], proxima_dose_em: '', observacoes: '' });

async function salvarVacina() {
    await fetch(`/prontuarios/consulta/${props.consulta.id}/vacinacoes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(vacinaForm.value),
    });
    showVacina.value = false;
    router.reload({ only: ['consulta'] });
}
async function excluirVacina(id: number) {
    if (!confirm('Excluir esta vacinação?')) return;
    await fetch(`/vacinacoes/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf() } });
    router.reload({ only: ['consulta'] });
}

// Alerta de vencimento de vacina
function vacinaVencida(proxima: string | null) {
    if (!proxima) return false;
    return new Date(proxima) < new Date();
}
function vacinaVenceEm30(proxima: string | null) {
    if (!proxima) return false;
    const d = new Date(proxima);
    const hoje = new Date();
    return d >= hoje && (d.getTime() - hoje.getTime()) <= 30 * 86400 * 1000;
}

const procStatusLabel: Record<string, string> = { planejado: 'Planejado', realizado: 'Realizado', cancelado: 'Cancelado' };
const procStatusCor: Record<string, string> = {
    planejado: 'bg-yellow-50 text-yellow-600',
    realizado: 'bg-green-50 text-green-700',
    cancelado: 'bg-gray-100 text-gray-400',
};
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 bg-white px-8 py-3">
            <div class="flex items-center gap-3">
                <Link :href="`/prontuarios/paciente/${consulta.paciente.id}`" class="text-xs text-gray-400 hover:text-primary-500">
                    ← {{ consulta.paciente.nome }}
                </Link>
                <span class="text-gray-200">/</span>
                <span class="text-sm font-medium text-gray-700">
                    {{ { primeira_consulta: 'Primeira consulta', retorno: 'Retorno', emergencia: 'Emergência' }[consulta.tipo_consulta] ?? consulta.tipo_consulta }}
                </span>
                <span v-if="consulta.status === 'em_andamento'" class="rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-600">Em andamento</span>
                <span v-else class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-600">Finalizada</span>
            </div>
            <div class="flex items-center gap-2">
                <span v-if="salvando" class="text-xs text-gray-400">Salvando...</span>
                <span v-else-if="savedAt" class="text-xs text-gray-400">Salvo às {{ savedAt }}</span>
                <!-- PDF do prontuário completo -->
                <a :href="`/prontuarios/paciente/${consulta.paciente.id}/pdf`" target="_blank"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Prontuário PDF
                </a>
                <button v-if="consulta.status === 'em_andamento'" @click="finalizar"
                    class="rounded-lg bg-green-500 px-4 py-2 text-sm font-semibold text-white hover:bg-green-600">
                    Finalizar consulta
                </button>
            </div>
        </div>

        <div class="flex flex-1 overflow-hidden">
            <!-- Sidebar paciente -->
            <div class="flex w-56 shrink-0 flex-col border-r border-gray-100 bg-white p-4 overflow-y-auto">
                <div class="mb-3 flex items-center gap-2">
                    <div class="flex h-10 w-10 shrink-0 overflow-hidden items-center justify-center rounded-xl bg-primary-50 text-xl">
                        <img v-if="consulta.paciente.fotos[0]" :src="consulta.paciente.fotos[0].url" class="h-full w-full object-cover" />
                        <span v-else>🐾</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ consulta.paciente.nome }}</p>
                        <p class="text-[11px] text-gray-400 capitalize">{{ consulta.paciente.especie }}</p>
                    </div>
                </div>
                <div class="space-y-1.5 text-xs">
                    <div v-if="consulta.paciente.peso" class="flex justify-between">
                        <span class="text-gray-400">Peso</span>
                        <span class="font-medium text-gray-700">{{ consulta.paciente.peso }} kg</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Tutor</span>
                        <span class="font-medium text-gray-700 truncate ml-2">{{ consulta.paciente.cliente.nome }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Veterinário</span>
                        <span class="font-medium text-gray-700 truncate ml-2">{{ consulta.veterinario.name }}</span>
                    </div>
                </div>
                <!-- Alerta de alergias -->
                <div v-if="consulta.paciente.alergias" class="mt-3 rounded-lg bg-red-50 border border-red-100 px-2.5 py-2">
                    <p class="text-[10px] font-bold text-red-500 uppercase">⚠ Alergias</p>
                    <p class="text-[11px] text-red-600 mt-0.5">{{ consulta.paciente.alergias }}</p>
                </div>
                <div v-if="consulta.paciente.tags.length" class="mt-3 flex flex-wrap gap-1">
                    <span v-for="tag in consulta.paciente.tags" :key="tag.tag"
                        class="rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-medium text-primary-700">
                        {{ tag.tag }}
                    </span>
                </div>
            </div>

            <!-- Conteúdo principal -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <!-- Tabs -->
                <div class="flex border-b border-gray-100 bg-white px-6">
                    <button v-for="[k, label] in [['anamnese','Anamnese'],['receitas','Receitas'],['exames','Exames'],['procedimentos','Procedimentos'],['vacinas','Vacinas']]"
                        :key="k" @click="abaAtiva = k as any"
                        class="mr-4 py-3 text-sm transition"
                        :class="abaAtiva === k ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                        {{ label }}
                        <span v-if="k === 'receitas' && consulta.receitas.length" class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px] text-gray-500">{{ consulta.receitas.length }}</span>
                        <span v-if="k === 'exames' && consulta.exames.length" class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px] text-gray-500">{{ consulta.exames.length }}</span>
                        <span v-if="k === 'procedimentos' && consulta.procedimentos.length" class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px] text-gray-500">{{ consulta.procedimentos.length }}</span>
                        <span v-if="k === 'vacinas' && consulta.vacinacoes.length" class="ml-1 rounded-full bg-gray-100 px-1.5 text-[10px] text-gray-500">{{ consulta.vacinacoes.length }}</span>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6">

                    <!-- ── Anamnese ─────────────────────────────────────────── -->
                    <div v-if="abaAtiva === 'anamnese'" class="space-y-4">
                        <div v-for="[field, label] in [['anamnese','Anamnese / Queixa principal'],['exame_fisico','Exame físico'],['diagnostico','Diagnóstico'],['prognostico','Prognóstico'],['observacoes','Observações']]" :key="field">
                            <label class="mb-1 block text-xs font-semibold text-gray-600">{{ label }}</label>
                            <textarea v-model="(form as any)[field]" rows="3"
                                :disabled="consulta.status !== 'em_andamento'"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 disabled:bg-gray-50 disabled:text-gray-500 resize-none"></textarea>
                        </div>
                    </div>

                    <!-- ── Receitas ────────────────────────────────────────── -->
                    <div v-if="abaAtiva === 'receitas'">
                        <div class="mb-4 flex justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Receitas prescritas</h3>
                            <button v-if="consulta.status === 'em_andamento'" @click="showReceita = true"
                                class="flex items-center gap-1 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Nova receita
                            </button>
                        </div>
                        <div v-if="!consulta.receitas.length" class="py-8 text-center text-sm text-gray-400">Nenhuma receita prescrita</div>
                        <div v-for="r in consulta.receitas" :key="r.id" class="mb-3 rounded-xl border border-gray-100 p-4">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-xs text-gray-400">{{ new Date(r.emitida_em).toLocaleDateString('pt-BR') }}</p>
                                <a :href="`/receitas/${r.id}/pdf`" target="_blank"
                                    class="flex items-center gap-1 rounded border border-gray-200 px-2 py-0.5 text-[11px] text-gray-500 hover:bg-gray-50">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    PDF
                                </a>
                            </div>
                            <div class="space-y-1">
                                <div v-for="(item, i) in r.itens" :key="i" class="text-sm text-gray-700">
                                    <span class="font-medium">{{ item.medicamento_nome }}</span>
                                    <span v-if="item.dosagem" class="text-gray-500"> — {{ item.dosagem }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Exames ──────────────────────────────────────────── -->
                    <div v-if="abaAtiva === 'exames'">
                        <div class="mb-4 flex justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Exames solicitados</h3>
                            <button v-if="consulta.status === 'em_andamento'" @click="showExame = true"
                                class="flex items-center gap-1 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Solicitar exame
                            </button>
                        </div>
                        <div v-if="!consulta.exames.length" class="py-8 text-center text-sm text-gray-400">Nenhum exame solicitado</div>
                        <div v-for="e in consulta.exames" :key="e.id" class="mb-3 rounded-xl border border-gray-100 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">{{ e.tipoExame?.nome ?? e.descricao ?? 'Exame' }}</p>
                                    <p v-if="e.laboratorio" class="text-xs text-gray-400">{{ e.laboratorio.nome }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="{ pendente: 'bg-yellow-50 text-yellow-600', concluido: 'bg-green-50 text-green-600' }[e.status] ?? 'bg-gray-100 text-gray-500'">
                                        {{ { pendente: 'Pendente', concluido: 'Concluído' }[e.status] ?? e.status }}
                                    </span>
                                    <!-- Upload de resultado -->
                                    <button v-if="e.status === 'pendente'" @click="abrirResultado(e.id)"
                                        class="rounded border border-gray-200 px-2 py-1 text-[11px] text-gray-500 hover:bg-gray-50">
                                        Anexar resultado
                                    </button>
                                </div>
                            </div>
                            <!-- Formulário inline de resultado -->
                            <div v-if="exameResultadoId === e.id" class="mt-3 rounded-lg bg-gray-50 p-3 space-y-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600">Arquivo (PDF/imagem) *</label>
                                    <input ref="resultadoInput" type="file" accept=".pdf,.jpg,.jpeg,.png"
                                        @change="onResultadoFile"
                                        class="w-full text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600">Interpretação (opcional)</label>
                                    <textarea v-model="resultadoInterpretacao" rows="2" class="w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs outline-none focus:border-primary-500 resize-none"></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button @click="exameResultadoId = null" class="rounded border border-gray-200 px-3 py-1 text-xs text-gray-500">Cancelar</button>
                                    <button @click="enviarResultado" :disabled="!resultadoFile || uploadandoResultado"
                                        class="rounded-lg bg-primary-500 px-3 py-1 text-xs font-semibold text-white disabled:opacity-40">
                                        {{ uploadandoResultado ? 'Enviando...' : 'Enviar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Procedimentos ───────────────────────────────────── -->
                    <div v-if="abaAtiva === 'procedimentos'">
                        <div class="mb-4 flex justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Procedimentos</h3>
                            <button v-if="consulta.status === 'em_andamento'" @click="abrirNovoProcedimento"
                                class="flex items-center gap-1 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Novo procedimento
                            </button>
                        </div>
                        <div v-if="!consulta.procedimentos.length" class="py-8 text-center text-sm text-gray-400">Nenhum procedimento registrado</div>
                        <div v-for="p in consulta.procedimentos" :key="p.id"
                            class="mb-3 flex items-center justify-between rounded-xl border border-gray-100 p-4">
                            <div>
                                <p class="font-medium text-gray-800">{{ p.tipo_procedimento }}</p>
                                <p v-if="p.descricao" class="text-xs text-gray-400 mt-0.5">{{ p.descricao }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span v-if="p.valor_cobrado" class="text-sm font-semibold text-gray-600">
                                    {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(p.valor_cobrado)) }}
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="procStatusCor[p.status]">
                                    {{ procStatusLabel[p.status] ?? p.status }}
                                </span>
                                <div v-if="consulta.status === 'em_andamento'" class="flex gap-1">
                                    <button @click="abrirEditarProcedimento(p)" class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button @click="excluirProcedimento(p.id)" class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Vacinas ─────────────────────────────────────────── -->
                    <div v-if="abaAtiva === 'vacinas'">
                        <div class="mb-4 flex justify-between">
                            <h3 class="text-sm font-semibold text-gray-700">Vacinações nesta consulta</h3>
                            <button v-if="consulta.status === 'em_andamento'" @click="showVacina = true"
                                class="flex items-center gap-1 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Registrar vacina
                            </button>
                        </div>
                        <div v-if="!consulta.vacinacoes.length" class="py-8 text-center text-sm text-gray-400">Nenhuma vacinação nesta consulta</div>
                        <div v-for="v in consulta.vacinacoes" :key="v.id"
                            class="mb-3 rounded-xl border p-4"
                            :class="vacinaVencida(v.proxima_dose_em) ? 'border-red-200 bg-red-50' : vacinaVenceEm30(v.proxima_dose_em) ? 'border-amber-200 bg-amber-50' : 'border-gray-100'">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium text-gray-800">{{ v.vacina_nome }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Aplicada em {{ new Date(v.data_aplicacao).toLocaleDateString('pt-BR') }}
                                        <span v-if="v.lote"> · Lote: {{ v.lote }}</span>
                                    </p>
                                    <p v-if="v.proxima_dose_em" class="text-xs mt-1"
                                        :class="vacinaVencida(v.proxima_dose_em) ? 'text-red-600 font-semibold' : vacinaVenceEm30(v.proxima_dose_em) ? 'text-amber-600 font-semibold' : 'text-gray-500'">
                                        {{ vacinaVencida(v.proxima_dose_em) ? '⚠ Vencida — ' : vacinaVenceEm30(v.proxima_dose_em) ? '⏰ Vence em breve — ' : 'Próxima dose: ' }}
                                        {{ new Date(v.proxima_dose_em!).toLocaleDateString('pt-BR') }}
                                    </p>
                                </div>
                                <button v-if="consulta.status === 'em_andamento'" @click="excluirVacina(v.id)"
                                    class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ── Modal Receita ────────────────────────────────────────────────────── -->
    <div v-if="showReceita" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showReceita = false">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Nova receita</h2>
                <button @click="showReceita = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="mb-3">
                <label class="mb-1 block text-xs font-medium text-gray-600">Instruções gerais</label>
                <textarea v-model="receitaForm.instrucoes_gerais" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
            </div>
            <div class="mb-3 space-y-3">
                <div v-for="(item, i) in receitaForm.itens" :key="i" class="rounded-lg border border-gray-100 p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold text-gray-500">Medicamento {{ i + 1 }}</p>
                        <button v-if="receitaForm.itens.length > 1" @click="removeItem(i)" class="text-xs text-red-400 hover:text-red-500">Remover</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <input v-model="item.medicamento_nome" placeholder="Nome do medicamento *" class="w-full rounded border border-gray-200 px-2 py-1.5 text-sm outline-none focus:border-primary-500" />
                        </div>
                        <input v-model="item.dosagem" placeholder="Dosagem" class="rounded border border-gray-200 px-2 py-1.5 text-sm outline-none focus:border-primary-500" />
                        <input v-model="item.frequencia" placeholder="Frequência" class="rounded border border-gray-200 px-2 py-1.5 text-sm outline-none focus:border-primary-500" />
                        <input v-model="item.duracao_tratamento" placeholder="Duração" class="rounded border border-gray-200 px-2 py-1.5 text-sm outline-none focus:border-primary-500" />
                        <input v-model="item.via_administracao" placeholder="Via de administração" class="rounded border border-gray-200 px-2 py-1.5 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
            </div>
            <button @click="addItem" class="mb-4 text-xs text-primary-500 hover:text-primary-600">+ Adicionar medicamento</button>
            <div class="flex justify-end gap-2">
                <button @click="showReceita = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarReceita" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar receita</button>
            </div>
        </div>
    </div>

    <!-- ── Modal Exame ──────────────────────────────────────────────────────── -->
    <div v-if="showExame" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showExame = false">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Solicitar exame</h2>
                <button @click="showExame = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Descrição</label>
                    <input v-model="exameForm.descricao" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Instruções para o paciente</label>
                    <textarea v-model="exameForm.instrucoes" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showExame = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarExame" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Solicitar</button>
            </div>
        </div>
    </div>

    <!-- ── Modal Procedimento ───────────────────────────────────────────────── -->
    <div v-if="showProcedimento" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showProcedimento = false">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">{{ editandoProcedimento ? 'Editar procedimento' : 'Novo procedimento' }}</h2>
                <button @click="showProcedimento = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Procedimento *</label>
                    <input v-model="procForm.tipo_procedimento" type="text" placeholder="Ex: Curativo, Tricotomia, Cateterismo..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Descrição</label>
                    <textarea v-model="procForm.descricao" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                        <select v-model="procForm.status" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                            <option value="planejado">Planejado</option>
                            <option value="realizado">Realizado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Valor (R$)</label>
                        <input v-model="procForm.valor_cobrado" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showProcedimento = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarProcedimento" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
            </div>
        </div>
    </div>

    <!-- ── Modal Vacinação ──────────────────────────────────────────────────── -->
    <div v-if="showVacina" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showVacina = false">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Registrar vacinação</h2>
                <button @click="showVacina = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Vacina *</label>
                    <input v-model="vacinaForm.vacina_nome" type="text" placeholder="Ex: V10, Antirrábica, Giárdia..." class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Fabricante</label>
                        <input v-model="vacinaForm.fabricante" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Lote</label>
                        <input v-model="vacinaForm.lote" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Data aplicação *</label>
                        <input v-model="vacinaForm.data_aplicacao" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Próxima dose</label>
                        <input v-model="vacinaForm.proxima_dose_em" type="date" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Observações</label>
                    <textarea v-model="vacinaForm.observacoes" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500 resize-none"></textarea>
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showVacina = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvarVacina" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Registrar</button>
            </div>
        </div>
    </div>
</template>
