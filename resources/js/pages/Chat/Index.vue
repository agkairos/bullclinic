<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch, inject } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Membro = { user: { id: number; name: string; avatar: string | null } };
type Leitura = { user_id: number; lida_em: string };
type Mensagem = {
    id: number; conversa_id: number; user_id: number;
    tipo: 'texto' | 'audio' | 'imagem';
    conteudo: string | null; arquivo_url: string | null;
    duracao_audio_segundos: number | null;
    fixada: boolean; created_at: string;
    user: { id: number; name: string; avatar: string | null };
    leituras?: Leitura[];
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
    conversa_ativa_id?: number | null;
}>();

const page = usePage<any>();
const meuId = computed(() => page.props.auth.user.id);
const erros = computed(() => (page.props.errors as Record<string, string>) ?? {});

// ── Verificação de plano ──────────────────────────────────────────────────────
const planoAtual = computed(() => {
    const user = page.props.auth.user;
    return user?.clinica_ativa?.plano?.slug || user?.conta_ativa?.plano?.slug || 'gratuito';
});
const planoPermiteAudio = computed(() => ['basico', 'empresarial', 'profissional'].includes(planoAtual.value));
const planoPermiteGrupos = computed(() => ['basico', 'empresarial', 'profissional'].includes(planoAtual.value));

const presenca = inject<{ usuariosOnline: any; statusOnline: (id: number) => 'online' | 'offline' }>('presenca');

// Retorna o status do outro membro da conversa (individual) ou null (grupo)
function statusConversa(c: Conversa): 'online' | 'offline' | null {
    if (c.tipo !== 'individual') return null;
    const outro = c.membros.find(m => m.user.id !== meuId.value);
    if (!outro) return null;
    return presenca?.statusOnline(outro.user.id) ?? null;
}

// ── Estado ────────────────────────────────────────────────────────────────────
const abaAtiva = ref<'todas' | 'nao_lidas'>('todas');
const conversaSelecionadaId = ref<number | null>(null);
const mensagens = ref<Mensagem[]>([]);
const novaMensagem = ref('');
const carregandoMensagens = ref(false);
const temMaisMensagens = ref(false);
const carregandoMais = ref(false);
const showNovaConversa = ref(false);
const novaConversaForm = ref({ nome: '', membros: [] as number[], tipo: 'individual' });
const mensagensContainer = ref<HTMLElement>();

// Real-time
const digitandoUsuarios = ref<Record<string, string>>({}); // userId → userName (na conversa aberta)
const digitandoPorConversa = ref<Record<number, boolean>>({}); // conversaId → alguém digitando
let digitandoTimeout: ReturnType<typeof setTimeout> | null = null;
let echoChannel: any = null;

// ── Gestão de grupo ───────────────────────────────────────────────────────────
const showGerenciarGrupo = ref(false);
const grupoForm = ref({ nome: '', membros: [] as number[] });

function abrirGerenciarGrupo() {
    if (!conversaSelecionada.value) return;
    grupoForm.value = {
        nome: conversaSelecionada.value.nome ?? '',
        membros: conversaSelecionada.value.membros
            .map(m => m.user.id)
            .filter(id => id !== meuId.value),
    };
    showGerenciarGrupo.value = true;
}

function salvarGrupo() {
    if (!conversaSelecionadaId.value) return;
    router.put(`/conversas/${conversaSelecionadaId.value}`, grupoForm.value, {
        onSuccess: () => { showGerenciarGrupo.value = false; },
    });
}

function sairOuExcluirConversa() {
    if (!conversaSelecionadaId.value) return;
    const ehCriador = conversaSelecionada.value?.membros.find(m => m.user.id === meuId.value);
    const label = conversaSelecionada.value?.tipo === 'grupo' && ehCriador
        ? 'Excluir grupo para todos?' : 'Sair desta conversa?';
    if (!confirm(label)) return;
    router.delete(`/conversas/${conversaSelecionadaId.value}`, {
        onSuccess: () => {
            conversasReativas.value = conversasReativas.value.filter(c => c.id !== conversaSelecionadaId.value);
            conversaSelecionadaId.value = null;
            mensagens.value = [];
        },
    });
}

// ── Separadores de data ────────────────────────────────────────────────────────
function fmtData(iso: string) {
    const d = new Date(iso);
    const hoje = new Date();
    const ontem = new Date(); ontem.setDate(hoje.getDate() - 1);
    if (d.toDateString() === hoje.toDateString()) return 'Hoje';
    if (d.toDateString() === ontem.toDateString()) return 'Ontem';
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
}

// Lista de mensagens intercalada com separadores de data
type MsgOuData = Mensagem | { tipo: 'separador'; label: string; key: string };
const mensagensComDatas = computed((): MsgOuData[] => {
    const result: MsgOuData[] = [];
    let ultimaData = '';
    for (const msg of mensagens.value) {
        const data = new Date(msg.created_at).toDateString();
        if (data !== ultimaData) {
            ultimaData = data;
            result.push({ tipo: 'separador', label: fmtData(msg.created_at), key: `sep-${data}` });
        }
        result.push(msg);
    }
    return result;
});

// Gravação de áudio
const gravando = ref(false);
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];

// ── Helpers ───────────────────────────────────────────────────────────────────
const conversasReativas = ref(props.conversas.map(c => ({ ...c })));
const conversasFiltradas2 = computed(() => {
    if (abaAtiva.value === 'nao_lidas') return conversasReativas.value.filter(c => c.nao_lidas > 0);
    return conversasReativas.value;
});
const conversaSelecionada = computed(() => conversasReativas.value.find(c => c.id === conversaSelecionadaId.value));
const totalNaoLidas = computed(() => conversasReativas.value.reduce((s, c) => s + c.nao_lidas, 0));

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

// Retorna true se a mensagem foi lida por pelo menos um membro além do remetente
function foiLida(msg: Mensagem): boolean {
    if (!msg.leituras?.length) return false;
    return msg.leituras.some(l => l.user_id !== msg.user_id);
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
let canalAtualId: number | null = null;

function subscreverCanal(conversaId: number) {
    canalAtualId = conversaId;
    // Listeners já registrados via subscreverCanalBackground — apenas marca o canal ativo
}

// ── Ações ─────────────────────────────────────────────────────────────────────
async function abrirConversa(id: number) {
    if (conversaSelecionadaId.value === id) return;
    conversaSelecionadaId.value = id;
    mensagens.value = [];
    temMaisMensagens.value = false;
    digitandoUsuarios.value = {};
    carregandoMensagens.value = true;

    subscreverCanal(id);

    try {
        const res = await fetch(`/conversas/${id}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const d = await res.json();
        mensagens.value = d.mensagens ?? [];
        temMaisMensagens.value = d.tem_mais ?? false;
        // Zera badge localmente
        const conv = conversasReativas.value.find(c => c.id === id);
        if (conv) conv.nao_lidas = 0;
    } finally {
        carregandoMensagens.value = false;
    }

    await nextTick();
    scrollParaBaixo();
}

async function carregarMaisMensagens() {
    if (!conversaSelecionadaId.value || carregandoMais.value || !temMaisMensagens.value) return;
    const primeiroId = mensagens.value[0]?.id;
    if (!primeiroId) return;

    carregandoMais.value = true;
    // Guarda a altura atual para restaurar posição após inserir mensagens no topo
    const container = mensagensContainer.value;
    const alturaAntes = container?.scrollHeight ?? 0;

    try {
        const res = await fetch(`/conversas/${conversaSelecionadaId.value}?antes_do_id=${primeiroId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        });
        const d = await res.json();
        const novas: Mensagem[] = d.mensagens ?? [];
        mensagens.value = [...novas, ...mensagens.value];
        temMaisMensagens.value = d.tem_mais ?? false;

        await nextTick();
        // Mantém posição do scroll após inserir mensagens no topo
        if (container) container.scrollTop = container.scrollHeight - alturaAntes;
    } finally {
        carregandoMais.value = false;
    }
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
    if (!planoPermiteAudio.value) {
        alert('Recurso de áudio disponível apenas nos planos Básico, Profissional e Empresarial. Faça upgrade do seu plano!');
        return;
    }

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
    novaConversaForm.value.tipo = novaConversaForm.value.membros.length > 1 ? 'grupo' : 'individual';

    // Verifica se está tentando criar grupo com plano gratuito
    if (novaConversaForm.value.tipo === 'grupo' && !planoPermiteGrupos.value) {
        alert('Recurso de grupos disponível apenas nos planos Básico, Profissional e Empresarial. Faça upgrade do seu plano!');
        return;
    }

    router.post('/conversas', novaConversaForm.value, {
        onSuccess: () => {
            showNovaConversa.value = false;
            novaConversaForm.value = { nome: '', membros: [], tipo: 'individual' };
        },
    });
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
const canaisBackground = new Set<number>();

function subscreverCanalBackground(c: Conversa) {
    if (canaisBackground.has(c.id)) return;
    canaisBackground.add(c.id);

    window.Echo?.private(`conversa.${c.id}`)
        ?.listen('.mensagem-lida', (e: { mensagem_id: number; user_id: number }) => {
            const msg = mensagens.value.find(m => m.id === e.mensagem_id);
            if (msg) {
                if (!msg.leituras) msg.leituras = [];
                if (!msg.leituras.find(l => l.user_id === e.user_id)) {
                    msg.leituras.push({ user_id: e.user_id, lida_em: new Date().toISOString() });
                }
            }
        })
        ?.listen('.nova-mensagem', (e: { mensagem: Mensagem }) => {
            // Limpa "digitando..." ao receber mensagem
            digitandoPorConversa.value[c.id] = false;
            delete digitandoUsuarios.value[e.mensagem.user_id];

            // Atualiza última mensagem na lista lateral
            const conv = conversasReativas.value.find(x => x.id === c.id);
            if (conv) conv.mensagens = [e.mensagem];

            if (conversaSelecionadaId.value === c.id) {
                if (e.mensagem.user_id !== meuId.value && !mensagens.value.find(m => m.id === e.mensagem.id)) {
                    mensagens.value.push(e.mensagem);
                    scrollParaBaixo();
                    marcarLida(e.mensagem.id);
                }
            } else {
                if (conv) conv.nao_lidas++;
            }
        })
        ?.listen('.digitando', (e: { user_id: number; user_name: string; digitando: boolean }) => {
            if (e.user_id === meuId.value) return;
            // Atualiza indicador na lista lateral
            digitandoPorConversa.value[c.id] = e.digitando;
            // Atualiza indicador na conversa aberta
            if (conversaSelecionadaId.value === c.id) {
                if (e.digitando) {
                    digitandoUsuarios.value[e.user_id] = e.user_name;
                } else {
                    delete digitandoUsuarios.value[e.user_id];
                }
            }
        });
}

// Sincroniza conversasReativas com props.conversas (Inertia atualiza props sem remontar)
watch(() => props.conversas, (novas) => {
    // Adiciona novas conversas que ainda não existem
    novas.forEach(c => {
        if (!conversasReativas.value.find(x => x.id === c.id)) {
            conversasReativas.value.unshift({ ...c });
        }
        subscreverCanalBackground(c);
    });
}, { deep: false });

onMounted(() => {
    conversasReativas.value.forEach(c => subscreverCanalBackground(c));

    // Canal do usuário — recebe novas conversas criadas por outros
    window.Echo?.private(`user.${meuId.value}`)
        ?.listen('.nova-conversa', (e: { conversa: Conversa }) => {
            if (!conversasReativas.value.find(c => c.id === e.conversa.id)) {
                conversasReativas.value.unshift(e.conversa);
                subscreverCanalBackground(e.conversa);
            }
        });

    if (props.conversa_ativa_id) {
        abrirConversa(props.conversa_ativa_id);
    }
});

onUnmounted(() => {
    canaisBackground.forEach(id => window.Echo?.leave(`conversa.${id}`));
    window.Echo?.leave(`user.${meuId.value}`);
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
                <div v-if="!conversasFiltradas2.length" class="py-12 text-center text-sm text-gray-400">Nenhuma conversa</div>
                <button v-for="c in conversasFiltradas2" :key="c.id" @click="abrirConversa(c.id)"
                    class="flex w-full items-center gap-2.5 px-4 py-3 text-left transition hover:bg-gray-50"
                    :class="conversaSelecionadaId === c.id ? 'bg-primary-50' : ''">
                    <!-- Avatar com indicador de status -->
                    <div class="relative shrink-0">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold"
                            :class="conversaSelecionadaId === c.id ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600'">
                            {{ iniciais(nomeConversa(c)) }}
                        </div>
                        <span v-if="statusConversa(c)"
                            class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white"
                            :class="statusConversa(c) === 'online' ? 'bg-green-400' : 'bg-gray-300'">
                        </span>
                    </div>
                    <!-- Nome + última mensagem -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-1">
                            <p class="truncate text-xs font-semibold text-gray-800">{{ nomeConversa(c) }}</p>
                            <span class="shrink-0 text-[10px] text-gray-400">{{ c.mensagens[0] ? fmtHora(c.mensagens[0].created_at) : '' }}</span>
                        </div>
                        <p v-if="digitandoPorConversa[c.id]" class="truncate text-[11px] italic text-primary-500">digitando...</p>
                        <div v-else class="flex items-center gap-0.5">
                            <svg v-if="c.mensagens[0]?.user_id === meuId" viewBox="0 0 16 11" class="h-2.5 w-3.5 shrink-0"
                                :class="foiLida(c.mensagens[0]) ? 'text-blue-500' : 'text-gray-300'">
                                <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M1 5.5l3 3 5-6"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M6 5.5l3 3 5-6"/>
                            </svg>
                            <p class="truncate text-[11px] text-gray-400">{{ ultimaMensagem(c) }}</p>
                        </div>
                    </div>
                    <!-- Badge não lidas -->
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
                    <!-- Botões de ação do grupo/conversa -->
                    <div class="flex items-center gap-2">
                        <button v-if="conversaSelecionada.tipo === 'grupo'"
                            @click="abrirGerenciarGrupo"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Gerenciar grupo">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        </button>
                        <button @click="sairOuExcluirConversa"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500" title="Sair / Excluir">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Mensagens -->
                <div ref="mensagensContainer" class="flex-1 overflow-y-auto px-6 py-4 space-y-2"
                    @scroll.passive="(e) => { if ((e.target as HTMLElement).scrollTop < 80 && temMaisMensagens) carregarMaisMensagens() }">
                    <!-- Spinner de carregar mais (topo) -->
                    <div v-if="carregandoMais" class="flex justify-center pb-2">
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                    </div>
                    <div v-if="carregandoMensagens" class="flex justify-center py-8">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                    </div>
                    <div v-else-if="!mensagens.length" class="py-8 text-center text-sm text-gray-400">Nenhuma mensagem ainda. Diga olá! 👋</div>

                    <template v-for="item in mensagensComDatas" :key="'label' in item ? item.key : item.id">
                        <!-- Separador de data -->
                        <div v-if="'label' in item" class="flex items-center gap-3 py-2">
                            <div class="h-px flex-1 bg-gray-100"></div>
                            <span class="shrink-0 rounded-full bg-gray-100 px-3 py-0.5 text-[11px] text-gray-400">{{ item.label }}</span>
                            <div class="h-px flex-1 bg-gray-100"></div>
                        </div>
                        <!-- Mensagem -->
                        <div v-else class="flex" :class="item.user_id === meuId ? 'justify-end' : 'justify-start'">
                            <!-- Avatar (mensagens de outros) -->
                            <div v-if="item.user_id !== meuId" class="mr-2 flex h-6 w-6 shrink-0 items-center justify-center self-end rounded-full bg-gray-200 text-[9px] font-bold text-gray-600">
                                {{ iniciais(item.user.name) }}
                            </div>
                            <div class="max-w-xs lg:max-w-sm">
                                <!-- Nome (grupos) -->
                                <p v-if="item.user_id !== meuId && conversaSelecionada.tipo === 'grupo'" class="mb-0.5 text-[10px] text-gray-400 pl-1">{{ item.user.name }}</p>
                                <!-- Áudio -->
                                <div v-if="item.tipo === 'audio'" class="rounded-2xl px-4 py-2.5" :class="item.user_id === meuId ? 'bg-primary-500' : 'bg-gray-100'">
                                    <audio controls class="h-8 max-w-50" :src="item.arquivo_url ?? undefined"></audio>
                                </div>
                                <!-- Texto -->
                                <div v-else class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                    :class="item.user_id === meuId ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-800'">
                                    {{ item.conteudo }}
                                </div>
                                <div class="mt-0.5 flex items-center gap-1" :class="item.user_id === meuId ? 'justify-end' : ''">
                                    <p class="text-[10px] text-gray-400">{{ fmtHora(item.created_at) }}</p>
                                    <svg v-if="item.user_id === meuId" viewBox="0 0 16 11" class="h-3 w-4 shrink-0"
                                        :class="foiLida(item) ? 'text-blue-500' : 'text-gray-300'">
                                        <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M1 5.5l3 3 5-6"/>
                                        <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M6 5.5l3 3 5-6"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
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
                            :title="planoPermiteAudio ? '' : 'Disponível nos planos Básico, Profissional e Empresarial'"
                            class="shrink-0 transition relative"
                            :class="[
                                gravando ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-gray-600',
                                !planoPermiteAudio && 'opacity-40'
                            ]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
                                <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                                <line x1="12" y1="19" x2="12" y2="23"/>
                                <line x1="8" y1="23" x2="16" y2="23"/>
                            </svg>
                            <span v-if="!planoPermiteAudio" class="absolute -top-1 -right-1 flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-yellow-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-yellow-500"></span>
                            </span>
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
                        <input v-model="novaConversaForm.nome" type="text" placeholder="Ex: Equipe cirurgia"
                            class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-primary-500"
                            :class="erros.nome ? 'border-red-300' : 'border-gray-200'" />
                        <p v-if="erros.nome" class="mt-1 text-xs text-red-500">{{ erros.nome }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-600">Participantes *</label>
                        <div class="max-h-40 space-y-1.5 overflow-y-auto"
                            :class="erros.membros ? 'rounded-lg border border-red-300 p-1' : ''">
                            <label v-for="u in usuarios" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" :value="u.id" v-model="novaConversaForm.membros" class="rounded" />
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </div>
                                {{ u.name }}
                            </label>
                        </div>
                        <p v-if="erros.membros" class="mt-1 text-xs text-red-500">{{ erros.membros }}</p>
                    </div>
                    <!-- Aviso de plano para grupos -->
                    <div v-if="!planoPermiteGrupos && novaConversaForm.membros.length > 1"
                        class="rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                        <strong class="font-semibold">⚠ Grupos disponíveis apenas em planos pagos</strong>
                        <p class="mt-0.5">Selecione apenas 1 participante ou faça upgrade para Básico, Profissional ou Empresarial.</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="showNovaConversa = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                    <button @click="criarConversa" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Criar</button>
                </div>
            </div>
        </div>

        <!-- ── Modal Gerenciar Grupo ──────────────────────────────────────────── -->
        <div v-if="showGerenciarGrupo" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showGerenciarGrupo = false">
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800">Gerenciar grupo</h2>
                    <button @click="showGerenciarGrupo = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Nome do grupo</label>
                        <input v-model="grupoForm.nome" type="text"
                            class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:border-primary-500"
                            :class="erros.nome ? 'border-red-300' : 'border-gray-200'" />
                        <p v-if="erros.nome" class="mt-1 text-xs text-red-500">{{ erros.nome }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-600">Participantes</label>
                        <div class="max-h-40 space-y-1.5 overflow-y-auto"
                            :class="erros.membros ? 'rounded-lg border border-red-300 p-1' : ''">
                            <label v-for="u in usuarios" :key="u.id" class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" :value="u.id" v-model="grupoForm.membros" class="rounded" />
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </div>
                                {{ u.name }}
                            </label>
                        </div>
                        <p v-if="erros.membros" class="mt-1 text-xs text-red-500">{{ erros.membros }}</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="showGerenciarGrupo = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                    <button @click="salvarGrupo" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</template>
