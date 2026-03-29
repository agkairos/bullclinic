<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Membro = { user: { id: number; name: string; avatar: string | null } };
type Mensagem = {
    id: number; conversa_id: number; user_id: number;
    tipo: 'texto' | 'audio' | 'imagem';
    conteudo: string | null; arquivo_url: string | null;
    duracao_audio_segundos: number | null;
    fixada: boolean; created_at: string;
    user: { id: number; name: string; avatar: string | null };
};
type Conversa = {
    id: number; nome: string | null; tipo: string;
    nao_lidas: number;
    membros: Membro[];
    mensagens: Mensagem[];
};

const props = defineProps<{
    conversas: Conversa[];
    usuarios: Array<{ id: number; name: string; avatar: string | null }>;
}>();

const page = usePage<any>();
const meuId = computed(() => page.props.auth.user.id);

// ── Estado ────────────────────────────────────────────────────────────────────
const abaAtiva = ref<'todas' | 'nao_lidas'>('todas');
const conversaSelecionadaId = ref<number | null>(null);
const mensagens = ref<Mensagem[]>([]);
const novaMensagem = ref('');
const carregandoMensagens = ref(false);
const showNovaConversa = ref(false);
const novaConversaForm = ref({ nome: '', membros: [] as number[] });
const mensagensContainer = ref<HTMLElement>();

// Real-time
const digitandoUsuarios = ref<Record<string, string>>({}); // userId → userName
let digitandoTimeout: ReturnType<typeof setTimeout> | null = null;
let echoChannel: any = null;

// Gravação de áudio
const gravando = ref(false);
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];

// ── Helpers ───────────────────────────────────────────────────────────────────
const conversasFiltradas = computed(() => {
    if (abaAtiva.value === 'nao_lidas') return props.conversas.filter(c => c.nao_lidas > 0);
    return props.conversas;
});

const conversaSelecionada = computed(() => props.conversas.find(c => c.id === conversaSelecionadaId.value));
const totalNaoLidas = computed(() => props.conversas.reduce((s, c) => s + c.nao_lidas, 0));

function nomeConversa(c: Conversa) {
    if (c.nome) return c.nome;
    return c.membros.filter(m => m.user.id !== meuId.value).map(m => m.user.name).join(', ') || 'Conversa';
}

function iniciais(nome: string) {
    return nome.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

function ultimaMensagem(c: Conversa) {
    const m = c.mensagens[0];
    if (!m) return 'Nenhuma mensagem';
    if (m.tipo === 'audio') return '🎙️ Áudio';
    if (m.tipo === 'imagem') return '🖼️ Imagem';
    return m.conteudo ?? '';
}

function fmtHora(iso: string) {
    return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function getCsrf() {
    return (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '';
}

const digitandoLabel = computed(() => {
    const nomes = Object.values(digitandoUsuarios.value);
    if (!nomes.length) return '';
    if (nomes.length === 1) return `${nomes[0]} está digitando...`;
    return `${nomes.join(', ')} estão digitando...`;
});

// ── WebSocket ─────────────────────────────────────────────────────────────────
function subscreverCanal(conversaId: number) {
    if (echoChannel) {
        echoChannel.stopListening('.nova-mensagem');
        echoChannel.stopListening('.digitando');
        window.Echo?.leave(`conversa.${conversaId}`);
    }

    echoChannel = window.Echo?.private(`conversa.${conversaId}`)
        ?.listen('.nova-mensagem', (e: { mensagem: Mensagem }) => {
            // Evitar duplicatas (própria mensagem já adicionada otimisticamente)
            if (!mensagens.value.find(m => m.id === e.mensagem.id)) {
                mensagens.value.push(e.mensagem);
                scrollParaBaixo();
            }
            // Marcar como lida se for de outro usuário
            if (e.mensagem.user_id !== meuId.value) {
                marcarLida(e.mensagem.id);
            }
        })
        ?.listen('.digitando', (e: { user_id: number; user_name: string; digitando: boolean }) => {
            if (e.user_id === meuId.value) return;
            if (e.digitando) {
                digitandoUsuarios.value[e.user_id] = e.user_name;
            } else {
                delete digitandoUsuarios.value[e.user_id];
            }
        });
}

// ── Ações ─────────────────────────────────────────────────────────────────────
async function abrirConversa(id: number) {
    if (conversaSelecionadaId.value === id) return;
    conversaSelecionadaId.value = id;
    mensagens.value = [];
    digitandoUsuarios.value = {};
    carregandoMensagens.value = true;

    subscreverCanal(id);

    try {
        const res = await fetch(`/conversas/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const d = await res.json();
        mensagens.value = (d.mensagens?.data ?? []).reverse();
    } finally {
        carregandoMensagens.value = false;
    }

    await nextTick();
    scrollParaBaixo();
    marcarConversaLida(id);
}

async function marcarConversaLida(conversaId: number) {
    // Marcar todas as mensagens não lidas da conversa
    const naoLidas = mensagens.value.filter(m => m.user_id !== meuId.value);
    for (const m of naoLidas) {
        await marcarLida(m.id);
    }
    // Atualizar contador localmente
    router.reload({ only: ['conversas'] });
}

async function marcarLida(mensagemId: number) {
    await fetch(`/mensagens/${mensagemId}/lida`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
    }).catch(() => {});
}

async function enviar() {
    if (!novaMensagem.value.trim() || !conversaSelecionadaId.value) return;
    const conteudo = novaMensagem.value.trim();
    novaMensagem.value = '';

    // Parar indicador de "digitando"
    notificarParouDigitar();

    // Adicionar otimisticamente
    const tempMsg: Mensagem = {
        id: Date.now(),
        conversa_id: conversaSelecionadaId.value,
        user_id: meuId.value,
        tipo: 'texto',
        conteudo,
        arquivo_url: null,
        duracao_audio_segundos: null,
        fixada: false,
        created_at: new Date().toISOString(),
        user: { id: meuId.value, name: page.props.auth.user.name, avatar: page.props.auth.user.avatar },
    };
    mensagens.value.push(tempMsg);
    await nextTick();
    scrollParaBaixo();

    const res = await fetch(`/conversas/${conversaSelecionadaId.value}/mensagens`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ tipo: 'texto', conteudo }),
    });

    const d = await res.json();
    // Substituir mensagem temporária pelo ID real
    const idx = mensagens.value.findIndex(m => m.id === tempMsg.id);
    if (idx !== -1) mensagens.value[idx] = d.mensagem;
}

// ── Indicador "digitando..." ──────────────────────────────────────────────────
function onInput() {
    if (!conversaSelecionadaId.value) return;

    if (digitandoTimeout) clearTimeout(digitandoTimeout);
    else notificarDigitando(true);

    digitandoTimeout = setTimeout(() => {
        notificarParouDigitar();
    }, 3000);
}

function notificarDigitando(state: boolean) {
    if (!conversaSelecionadaId.value) return;
    fetch(`/conversas/${conversaSelecionadaId.value}/digitando`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ digitando: state }),
    }).catch(() => {});
}

function notificarParouDigitar() {
    if (digitandoTimeout) { clearTimeout(digitandoTimeout); digitandoTimeout = null; }
    notificarDigitando(false);
}

// ── Gravação de áudio ─────────────────────────────────────────────────────────
async function toggleGravacao() {
    if (gravando.value) {
        mediaRecorder?.stop();
        gravando.value = false;
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream);

        mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data);
        mediaRecorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            const blob = new Blob(audioChunks, { type: 'audio/webm' });
            await enviarAudio(blob);
        };

        mediaRecorder.start();
        gravando.value = true;
    } catch {
        alert('Permissão de microfone negada.');
    }
}

async function enviarAudio(blob: Blob) {
    if (!conversaSelecionadaId.value) return;
    const form = new FormData();
    form.append('tipo', 'audio');
    form.append('arquivo', blob, 'audio.webm');
    form.append('duracao_segundos', '0');

    const res = await fetch(`/conversas/${conversaSelecionadaId.value}/mensagens`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        body: form,
    });
    const d = await res.json();
    mensagens.value.push(d.mensagem);
    await nextTick();
    scrollParaBaixo();
}

// ── Scroll ────────────────────────────────────────────────────────────────────
function scrollParaBaixo() {
    nextTick(() => {
        if (mensagensContainer.value) {
            mensagensContainer.value.scrollTop = mensagensContainer.value.scrollHeight;
        }
    });
}

// ── Nova conversa ─────────────────────────────────────────────────────────────
function criarConversa() {
    router.post('/conversas', novaConversaForm.value, {
        onSuccess: () => {
            showNovaConversa.value = false;
            novaConversaForm.value = { nome: '', membros: [] };
        },
    });
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onUnmounted(() => {
    if (conversaSelecionadaId.value) {
        window.Echo?.leave(`conversa.${conversaSelecionadaId.value}`);
    }
});

watch(mensagens, () => scrollParaBaixo(), { deep: false });
</script>

<template>
    <div class="flex h-full overflow-hidden">
        <!-- ── Lista de conversas ──────────────────────────────────────────── -->
        <div class="flex w-64 shrink-0 flex-col border-r border-gray-100 bg-white">
            <div class="border-b border-gray-100 px-4 py-4">
                <div class="mb-3 flex items-center justify-between">
                    <h1 class="text-lg font-bold text-gray-900">Conversas</h1>
                    <button @click="showNovaConversa = true" class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-500 text-white hover:bg-primary-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
                <div class="flex gap-3 text-sm">
                    <button @click="abaAtiva = 'todas'" class="pb-1 transition" :class="abaAtiva === 'todas' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500'">Todas</button>
                    <button @click="abaAtiva = 'nao_lidas'" class="flex items-center gap-1 pb-1 transition" :class="abaAtiva === 'nao_lidas' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500'">
                        Não lidas
                        <span v-if="totalNaoLidas" class="rounded-full bg-primary-500 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ totalNaoLidas }}</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div v-if="!conversasFiltradas.length" class="py-12 text-center text-sm text-gray-400">Nenhuma conversa</div>
                <button v-for="c in conversasFiltradas" :key="c.id" @click="abrirConversa(c.id)"
                    class="flex w-full items-start gap-2.5 px-4 py-2.5 text-left transition hover:bg-gray-50"
                    :class="conversaSelecionadaId === c.id ? 'bg-primary-50' : ''">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                        :class="conversaSelecionadaId === c.id ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'">
                        {{ iniciais(nomeConversa(c)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <p class="truncate text-xs font-semibold text-gray-800">{{ nomeConversa(c) }}</p>
                            <span class="shrink-0 text-[10px] text-gray-400">{{ c.mensagens[0] ? fmtHora(c.mensagens[0].created_at) : '' }}</span>
                        </div>
                        <p class="truncate text-[11px] text-gray-400">{{ ultimaMensagem(c) }}</p>
                    </div>
                    <span v-if="c.nao_lidas" class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 text-[10px] font-bold text-white">{{ c.nao_lidas }}</span>
                </button>
            </div>
        </div>

        <!-- ── Área de mensagens ───────────────────────────────────────────── -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Sem conversa -->
            <div v-if="!conversaSelecionada" class="flex flex-1 items-center justify-center text-gray-400">
                <div class="text-center">
                    <svg class="mx-auto mb-3 h-12 w-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <p class="text-sm">Selecione uma conversa</p>
                </div>
            </div>

            <template v-else>
                <!-- Header -->
                <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                            {{ iniciais(nomeConversa(conversaSelecionada)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ nomeConversa(conversaSelecionada) }}</p>
                            <p v-if="digitandoLabel" class="text-[11px] text-primary-500 animate-pulse">{{ digitandoLabel }}</p>
                            <p v-else class="text-[11px] text-gray-400">{{ conversaSelecionada.membros.length }} participante{{ conversaSelecionada.membros.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Mensagens -->
                <div ref="mensagensContainer" class="flex-1 overflow-y-auto px-6 py-4 space-y-2">
                    <div v-if="carregandoMensagens" class="flex justify-center py-8">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                    </div>
                    <div v-else-if="!mensagens.length" class="py-8 text-center text-sm text-gray-400">Nenhuma mensagem ainda. Diga olá! 👋</div>

                    <div v-for="msg in mensagens" :key="msg.id" class="flex" :class="msg.user_id === meuId ? 'justify-end' : 'justify-start'">
                        <!-- Avatar (mensagens de outros) -->
                        <div v-if="msg.user_id !== meuId" class="mr-2 flex h-6 w-6 shrink-0 items-center justify-center self-end rounded-full bg-gray-200 text-[9px] font-bold text-gray-600">
                            {{ iniciais(msg.user.name) }}
                        </div>

                        <div class="max-w-xs lg:max-w-sm">
                            <!-- Nome (grupos) -->
                            <p v-if="msg.user_id !== meuId && conversaSelecionada.tipo === 'grupo'" class="mb-0.5 text-[10px] text-gray-400 pl-1">{{ msg.user.name }}</p>

                            <!-- Áudio -->
                            <div v-if="msg.tipo === 'audio'" class="rounded-2xl px-4 py-2.5" :class="msg.user_id === meuId ? 'bg-primary-500' : 'bg-gray-100'">
                                <audio controls class="h-8 max-w-[200px]" :src="msg.arquivo_url ?? undefined"></audio>
                            </div>

                            <!-- Texto -->
                            <div v-else
                                class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                :class="msg.user_id === meuId ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-800'"
                            >
                                {{ msg.conteudo }}
                            </div>

                            <div class="mt-0.5 flex items-center gap-1" :class="msg.user_id === meuId ? 'justify-end' : ''">
                                <p class="text-[10px] text-gray-400">{{ fmtHora(msg.created_at) }}</p>
                                <!-- ✓ enviado / ✓✓ lido placeholder -->
                                <span v-if="msg.user_id === meuId" class="text-[10px] text-gray-300">✓✓</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="shrink-0 border-t border-gray-100 px-4 py-3">
                    <!-- Gravando... -->
                    <div v-if="gravando" class="mb-2 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-1.5">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                        <span class="text-xs font-medium text-red-500">Gravando áudio... Clique no microfone para enviar</span>
                    </div>

                    <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                        <input
                            v-model="novaMensagem"
                            @keydown.enter.prevent="enviar"
                            @input="onInput"
                            @blur="notificarParouDigitar"
                            type="text"
                            placeholder="Mensagem..."
                            class="flex-1 bg-transparent text-sm outline-none placeholder:text-gray-400"
                        />
                        <!-- Microfone -->
                        <button @click="toggleGravacao"
                            class="shrink-0 transition"
                            :class="gravando ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-gray-600'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="23"/>
                                <line x1="8" y1="23" x2="16" y2="23"/>
                            </svg>
                        </button>
                        <!-- Enviar -->
                        <button @click="enviar" :disabled="!novaMensagem.trim()"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition"
                            :class="novaMensagem.trim() ? 'bg-primary-500 text-white hover:bg-primary-600' : 'bg-gray-200 text-gray-400'">
                            <svg class="h-4 w-4 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- ── Modal Nova Conversa ─────────────────────────────────────────── -->
        <div v-if="showNovaConversa" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showNovaConversa = false">
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800">Nova conversa</h2>
                    <button @click="showNovaConversa = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Nome do grupo (opcional)</label>
                        <input v-model="novaConversaForm.nome" type="text" placeholder="Ex: Equipe cirurgia" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-600">Participantes *</label>
                        <div class="max-h-40 space-y-1.5 overflow-y-auto">
                            <label v-for="u in usuarios" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" :value="u.id" v-model="novaConversaForm.membros" class="rounded" />
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </div>
                                {{ u.name }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="showNovaConversa = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                    <button @click="criarConversa" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Criar</button>
                </div>
            </div>
        </div>
    </div>
</template>
