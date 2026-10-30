<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    paciente: {
        id: number;
        nome: string;
        especie: string;
        raca: string | null;
        sexo: string;
        cor: string | null;
        data_nascimento: string | null;
        peso: string | null;
        microchip: string | null;
        tatuagem: string | null;
        alergias: string | null;
        condicoes_especiais: string | null;
        restricoes_alimentares: string | null;
        medicamentos_cronicos: string | null;
        historico_resumido: string | null;
        ativo: boolean;
        clinica_id: number;
        cliente: { id: number; nome: string };
        fotos: Array<{ id: number; url: string; principal: boolean }>;
        tags: Array<{ id: number; tag: string }>;
        consultas: Array<{
            id: number; data: string; tipo: string | null;
            veterinario: { id: number; name: string } | null;
        }>;
        vacinacoes: Array<{
            id: number; vacina_nome: string; data_aplicacao: string; proxima_dose_em: string | null;
        }>;
        agendamentos: Array<{
            id: number; data: string; hora_inicio: string; status: string;
            tipoServico: { id: number; nome: string } | null;
            veterinario: { id: number; name: string } | null;
        }>;
    };
}>();

// ── helpers ──────────────────────────────────────────────────────────────────
const especieIcon: Record<string, string> = { cão: '🐕', cao: '🐕', gato: '🐈', ave: '🐦', coelho: '🐇', hamster: '🐹', peixe: '🐟' };
function getEspecieIcon(e: string) { return especieIcon[e.toLowerCase()] ?? '🐾'; }
function sexoLabel(s: string) { return s === 'macho' ? 'Macho' : s === 'femea' ? 'Fêmea' : 'N/I'; }
function idadeAnos(nascimento: string | null) {
    if (!nascimento) return null;
    const n = new Date(nascimento);
    const hoje = new Date();
    let anos = hoje.getFullYear() - n.getFullYear();
    if (hoje.getMonth() < n.getMonth() || (hoje.getMonth() === n.getMonth() && hoje.getDate() < n.getDate())) anos--;
    return anos >= 1 ? `${anos} ano${anos > 1 ? 's' : ''}` : `< 1 ano`;
}

// ── fotos ─────────────────────────────────────────────────────────────────────
const fotos = ref([...props.paciente.fotos]);
const fotoInput = ref<HTMLInputElement | null>(null);
const uploadando = ref(false);

function fotoAtual() {
    return fotos.value.find(f => f.principal) ?? fotos.value[0] ?? null;
}

async function uploadFoto(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    uploadando.value = true;

    const formData = new FormData();
    formData.append('foto', file);

    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/pacientes/${props.paciente.id}/fotos`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: formData,
    });
    if (res.ok) {
        const data = await res.json();
        fotos.value.push(data.foto);
    }
    uploadando.value = false;
    if (fotoInput.value) fotoInput.value.value = '';
}

async function setPrincipal(foto: typeof fotos.value[0]) {
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    await fetch(`/pacientes/${props.paciente.id}/fotos/${foto.id}/principal`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    });
    fotos.value = fotos.value.map(f => ({ ...f, principal: f.id === foto.id }));
}

async function removerFoto(foto: typeof fotos.value[0]) {
    if (!confirm('Remover esta foto?')) return;
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    const res = await fetch(`/pacientes/${props.paciente.id}/fotos/${foto.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    });
    if (res.ok) {
        fotos.value = fotos.value.filter(f => f.id !== foto.id);
        // se era principal, promove a primeira
        if (foto.principal && fotos.value.length) fotos.value[0].principal = true;
    }
}

// ── tags ──────────────────────────────────────────────────────────────────────
const tags = ref<string[]>(props.paciente.tags.map(t => t.tag));
const novaTag = ref('');
const salvandoTags = ref(false);

function adicionarTag() {
    const t = novaTag.value.trim();
    if (!t || tags.value.includes(t)) { novaTag.value = ''; return; }
    tags.value.push(t);
    novaTag.value = '';
    sincronizarTags();
}

function removerTag(tag: string) {
    tags.value = tags.value.filter(t => t !== tag);
    sincronizarTags();
}

async function sincronizarTags() {
    salvandoTags.value = true;
    const csrf = (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
    await fetch(`/pacientes/${props.paciente.id}/tags`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({ tags: tags.value }),
    });
    salvandoTags.value = false;
}

// ── excluir ───────────────────────────────────────────────────────────────────
function excluir() {
    if (!confirm(`Excluir ${props.paciente.nome}? Esta ação não pode ser desfeita.`)) return;
    router.delete(`/pacientes/${props.paciente.id}`);
}
</script>

<template>
    <div class="px-8 py-6 max-w-5xl">
        <!-- Header -->
        <div class="mb-6 flex items-start justify-between">
            <div class="flex items-center gap-4">
                <!-- Foto / avatar -->
                <div class="relative group">
                    <div v-if="fotoAtual()"
                        class="h-14 w-14 rounded-2xl overflow-hidden border border-gray-100 shadow-sm cursor-pointer"
                        @click="fotoInput?.click()">
                        <img :src="fotoAtual()!.url" alt="Foto" class="h-full w-full object-cover" />
                    </div>
                    <button v-else
                        @click="fotoInput?.click()"
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-3xl hover:bg-primary-100 transition">
                        {{ getEspecieIcon(paciente.especie) }}
                    </button>
                    <div v-if="uploadando" class="absolute inset-0 flex items-center justify-center rounded-2xl bg-black/30">
                        <svg class="h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </div>
                    <input ref="fotoInput" type="file" accept="image/*" class="hidden" @change="uploadFoto" />
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ paciente.nome }}</h1>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="paciente.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                            {{ paciente.ativo ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ paciente.especie }}
                        <span v-if="paciente.raca"> · {{ paciente.raca }}</span>
                        <span v-if="paciente.data_nascimento"> · {{ idadeAnos(paciente.data_nascimento) }}</span>
                        <span> · {{ sexoLabel(paciente.sexo) }}</span>
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">
                        Tutor:
                        <Link :href="`/clientes/${paciente.cliente.id}`" class="text-primary-600 hover:underline">
                            {{ paciente.cliente.nome }}
                        </Link>
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Link :href="`/prontuarios/paciente/${paciente.id}`"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                    Prontuário
                </Link>
                <button @click="excluir" class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-red-400 hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Col 1-2 -->
            <div class="col-span-2 space-y-4">

                <!-- Identificação -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Identificação</h2>
                    <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                        <div v-if="paciente.cor">
                            <dt class="text-xs text-gray-400">Cor / pelagem</dt>
                            <dd class="font-medium text-gray-800">{{ paciente.cor }}</dd>
                        </div>
                        <div v-if="paciente.peso">
                            <dt class="text-xs text-gray-400">Peso</dt>
                            <dd class="font-medium text-gray-800">{{ paciente.peso }} kg</dd>
                        </div>
                        <div v-if="paciente.data_nascimento">
                            <dt class="text-xs text-gray-400">Nascimento</dt>
                            <dd class="font-medium text-gray-800">{{ new Date(paciente.data_nascimento).toLocaleDateString('pt-BR') }}</dd>
                        </div>
                        <div v-if="paciente.microchip">
                            <dt class="text-xs text-gray-400">Microchip</dt>
                            <dd class="font-medium text-gray-800 font-mono text-xs">{{ paciente.microchip }}</dd>
                        </div>
                        <div v-if="paciente.tatuagem">
                            <dt class="text-xs text-gray-400">Tatuagem</dt>
                            <dd class="font-medium text-gray-800">{{ paciente.tatuagem }}</dd>
                        </div>
                    </dl>
                    <p v-if="!paciente.cor && !paciente.peso && !paciente.microchip && !paciente.data_nascimento" class="text-xs text-gray-400">Nenhum dado adicional registrado.</p>
                </div>

                <!-- Saúde -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Saúde</h2>
                    <div class="space-y-3 text-sm">
                        <div v-if="paciente.alergias">
                            <p class="text-xs font-medium text-red-500">Alergias</p>
                            <p class="mt-0.5 text-gray-700">{{ paciente.alergias }}</p>
                        </div>
                        <div v-if="paciente.condicoes_especiais">
                            <p class="text-xs font-medium text-amber-500">Condições especiais</p>
                            <p class="mt-0.5 text-gray-700">{{ paciente.condicoes_especiais }}</p>
                        </div>
                        <div v-if="paciente.restricoes_alimentares">
                            <p class="text-xs font-medium text-gray-500">Restrições alimentares</p>
                            <p class="mt-0.5 text-gray-700">{{ paciente.restricoes_alimentares }}</p>
                        </div>
                        <div v-if="paciente.medicamentos_cronicos">
                            <p class="text-xs font-medium text-primary-600">Medicamentos crônicos</p>
                            <p class="mt-0.5 text-gray-700">{{ paciente.medicamentos_cronicos }}</p>
                        </div>
                        <p v-if="!paciente.alergias && !paciente.condicoes_especiais && !paciente.restricoes_alimentares && !paciente.medicamentos_cronicos"
                            class="text-xs text-gray-400">Nenhuma informação de saúde registrada.</p>
                    </div>
                </div>

                <!-- Consultas recentes -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Consultas recentes</h2>
                    <div v-if="paciente.consultas.length" class="space-y-2">
                        <div v-for="c in paciente.consultas" :key="c.id"
                            class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                            <div>
                                <span class="font-medium text-gray-800">{{ c.tipo ?? 'Consulta' }}</span>
                                <span v-if="c.veterinario" class="ml-2 text-xs text-gray-400">· Dr(a). {{ c.veterinario.name }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ c.data ? new Date(c.data).toLocaleDateString('pt-BR') : '—' }}</span>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">Nenhuma consulta registrada.</p>
                </div>

                <!-- Galeria de fotos -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">Fotos</h2>
                        <button @click="fotoInput?.click()"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-50">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar foto
                        </button>
                    </div>
                    <div v-if="fotos.length" class="grid grid-cols-4 gap-2">
                        <div v-for="f in fotos" :key="f.id" class="group relative aspect-square overflow-hidden rounded-lg border border-gray-100">
                            <img :src="f.url" class="h-full w-full object-cover" />
                            <!-- overlay com ações -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <button v-if="!f.principal" @click="setPrincipal(f)"
                                    class="rounded bg-white/90 px-2 py-0.5 text-[10px] font-semibold text-gray-700 hover:bg-white">
                                    Principal
                                </button>
                                <span v-else class="rounded bg-primary-500/90 px-2 py-0.5 text-[10px] font-semibold text-white">✓ Principal</span>
                                <button @click="removerFoto(f)" class="rounded bg-red-500/90 px-2 py-0.5 text-[10px] font-semibold text-white hover:bg-red-500">
                                    Remover
                                </button>
                            </div>
                        </div>
                        <!-- placeholder de upload -->
                        <button @click="fotoInput?.click()"
                            class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-gray-200 text-gray-400 hover:border-primary-300 hover:text-primary-500 transition">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>
                    <button v-else @click="fotoInput?.click()"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-gray-200 py-6 text-sm text-gray-400 hover:border-primary-300 hover:text-primary-500 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Clique para adicionar a primeira foto
                    </button>
                </div>
            </div>

            <!-- Col 3: sidebar -->
            <div class="space-y-4">

                <!-- Tags -->
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Tags</h2>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <span v-for="t in tags" :key="t"
                            class="flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700">
                            {{ t }}
                            <button @click="removerTag(t)" class="text-primary-400 hover:text-red-500 leading-none">×</button>
                        </span>
                        <span v-if="!tags.length" class="text-xs text-gray-400">Nenhuma tag</span>
                    </div>
                    <div class="flex gap-1.5">
                        <input v-model="novaTag" @keydown.enter.prevent="adicionarTag"
                            type="text" placeholder="Nova tag..."
                            class="flex-1 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs outline-none focus:border-primary-400" />
                        <button @click="adicionarTag"
                            class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-500 hover:bg-gray-50">
                            +
                        </button>
                    </div>
                </div>

                <!-- Vacinações -->
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Vacinas</h2>
                    <div v-if="paciente.vacinacoes.length" class="space-y-2">
                        <div v-for="v in paciente.vacinacoes" :key="v.id"
                            class="rounded-lg p-2 text-xs"
                            :class="v.proxima_dose_em && new Date(v.proxima_dose_em) < new Date() ? 'bg-red-50' : v.proxima_dose_em && (new Date(v.proxima_dose_em).getTime() - Date.now()) <= 30*86400*1000 ? 'bg-amber-50' : 'bg-gray-50'">
                            <p class="font-medium text-gray-800">{{ v.vacina_nome }}</p>
                            <p class="text-gray-400">{{ new Date(v.data_aplicacao).toLocaleDateString('pt-BR') }}</p>
                            <p v-if="v.proxima_dose_em"
                                :class="new Date(v.proxima_dose_em) < new Date() ? 'text-red-600 font-semibold' : (new Date(v.proxima_dose_em).getTime() - Date.now()) <= 30*86400*1000 ? 'text-amber-600 font-semibold' : 'text-primary-500'">
                                {{ new Date(v.proxima_dose_em) < new Date() ? '⚠ Vencida: ' : '→ ' }}
                                {{ new Date(v.proxima_dose_em).toLocaleDateString('pt-BR') }}
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">Nenhuma vacina registrada.</p>
                </div>

                <!-- Agendamentos -->
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold text-gray-700">Agendamentos</h2>
                    <div v-if="paciente.agendamentos.length" class="space-y-2">
                        <div v-for="a in paciente.agendamentos" :key="a.id" class="rounded-lg bg-gray-50 px-3 py-2 text-xs">
                            <p class="font-medium text-gray-800">{{ a.tipoServico?.nome ?? 'Consulta' }}</p>
                            <p class="text-gray-400">{{ new Date(a.data).toLocaleDateString('pt-BR') }} {{ a.hora_inicio.substring(0, 5) }}</p>
                            <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium mt-1"
                                :class="{
                                    'bg-yellow-50 text-yellow-600': a.status === 'agendado',
                                    'bg-green-50 text-green-600': a.status === 'confirmado',
                                    'bg-red-50 text-red-500': a.status === 'cancelado',
                                    'bg-blue-50 text-blue-600': a.status === 'concluido',
                                }">{{ a.status }}</span>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">Nenhum agendamento.</p>
                </div>
            </div>
        </div>
    </div>
</template>
