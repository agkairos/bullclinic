<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Plano = {
    id: number;
    nome: string;
    slug: string;
    descricao: string;
    preco_mensal: string;
    preco_anual: string;
    limite_usuarios: number;
    limite_storage_gb: number;
    consultas_ilimitadas: boolean;
    limite_consultas_mes: number;
    chat_audio: boolean;
    chat_grupos: boolean;
    stripe_price_mensal_id: string | null;
    stripe_price_anual_id: string | null;
};

type Fatura = {
    id: string;
    numero: string;
    valor: number;
    status: string;
    data: string;
    pdf_url: string | null;
};

const props = defineProps<{
    planos: Plano[];
    conta: {
        plano_id: number | null;
        plano_slug: string | null;
        status_pagamento: string;
        data_vencimento: string | null;
        data_trial_fim: string | null;
        stripe_subscription_id: string | null;
        total_unidades: number;
        limite_unidades: number | null;
    };
    faturas: Fatura[];
}>();

const aba = ref<'planos' | 'historico'>('planos');
const periodicidade = ref<'mensal' | 'anual'>('mensal');

// Flash da URL
const urlParams = new URLSearchParams(window.location.search);
const flashCheckout = ref(urlParams.get('checkout'));

// ── Modal PIX ─────────────────────────────────────────────────────────────────
const showPix = ref(false);
const pixData = ref<{ pix_code: string; qr_image_url: string; valor: number; expiracao: string | null } | null>(null);
const carregandoPix = ref(false);
const planoSelecionado = ref<Plano | null>(null);

async function assinarStripe(plano: Plano) {
    router.post('/planos/checkout/stripe', {
        plano_id: plano.id,
        periodicidade: periodicidade.value,
    });
}

async function assinarPix(plano: Plano) {
    planoSelecionado.value = plano;
    carregandoPix.value = true;
    showPix.value = true;
    pixData.value = null;

    const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '';

    try {
        const res = await fetch('/planos/checkout/pix', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(csrfToken),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ plano_id: plano.id, periodicidade: periodicidade.value }),
        });

        if (!res.ok) {
            const err = await res.json();
            alert(err.error ?? 'Erro ao gerar PIX.');
            showPix.value = false;
            return;
        }

        pixData.value = await res.json();
    } finally {
        carregandoPix.value = false;
    }
}

function copiarPix() {
    if (pixData.value?.pix_code) {
        navigator.clipboard.writeText(pixData.value.pix_code);
        pixCopiado.value = true;
        setTimeout(() => pixCopiado.value = false, 2000);
    }
}

const pixCopiado = ref(false);

function abrirPortal() {
    router.post('/planos/portal');
}

function cancelarAssinatura() {
    if (!confirm('Tem certeza que deseja cancelar a assinatura? O acesso permanece até o fim do período pago.')) return;
    router.post('/planos/cancelar');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt(v: string | number) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(v));
}

const statusBadge = computed(() => {
    const m: Record<string, string> = {
        trial: 'bg-blue-50 text-blue-600',
        pago: 'bg-green-50 text-green-600',
        inadimplente: 'bg-red-50 text-red-600',
        cancelado: 'bg-gray-100 text-gray-500',
        inativo: 'bg-gray-100 text-gray-400',
    };
    return m[props.conta.status_pagamento] ?? 'bg-gray-100 text-gray-500';
});

const statusLabel = computed(() => {
    const m: Record<string, string> = {
        trial: 'Trial', pago: 'Ativo', inadimplente: 'Inadimplente',
        cancelado: 'Cancelado', inativo: 'Inativo',
    };
    return m[props.conta.status_pagamento] ?? props.conta.status_pagamento;
});

function isPlanoAtual(plano: Plano) {
    return plano.id === props.conta.plano_id;
}

function precoAtual(plano: Plano) {
    return periodicidade.value === 'anual'
        ? parseFloat(plano.preco_anual)
        : parseFloat(plano.preco_mensal);
}

function precoMensalEfetivo(plano: Plano) {
    if (periodicidade.value === 'anual') {
        return parseFloat(plano.preco_anual) / 12;
    }
    return parseFloat(plano.preco_mensal);
}

function economiaAnual(plano: Plano) {
    const anualPago = parseFloat(plano.preco_anual);
    const anualSeMensal = parseFloat(plano.preco_mensal) * 12;
    return Math.round(((anualSeMensal - anualPago) / anualSeMensal) * 100);
}

function temStripe(plano: Plano) {
    return periodicidade.value === 'anual' ? !!plano.stripe_price_anual_id : !!plano.stripe_price_mensal_id;
}

const faturaStatusClass: Record<string, string> = {
    paid: 'bg-green-50 text-green-600',
    open: 'bg-yellow-50 text-yellow-600',
    void: 'bg-gray-100 text-gray-400',
    uncollectible: 'bg-red-50 text-red-500',
};

const faturaStatusLabel: Record<string, string> = {
    paid: 'Pago', open: 'Em aberto', void: 'Cancelada', uncollectible: 'Irrecuperável',
};
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Planos e Assinatura</h1>

            <!-- Status atual -->
            <div class="flex items-center gap-3">
                <span v-if="clinica.data_vencimento" class="text-xs text-gray-400">
                    Vence em {{ conta.data_vencimento }}
                </span>
                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="statusBadge">
                    {{ statusLabel }}
                </span>
                <button v-if="clinica.stripe_subscription_id" @click="abrirPortal"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                    Gerenciar assinatura
                </button>
            </div>
        </div>

        <!-- Flash checkout -->
        <div v-if="flashCheckout === 'sucesso'" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
            ✅ Assinatura ativada com sucesso! Obrigado por assinar o BullClinic.
        </div>
        <div v-if="flashCheckout === 'cancelado'" class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm text-yellow-700">
            O checkout foi cancelado. Seu plano atual foi mantido.
        </div>

        <!-- Tabs -->
        <div class="mb-6 flex gap-4 border-b border-gray-100">
            <button @click="aba = 'planos'" class="pb-3 text-sm transition"
                :class="aba === 'planos' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                Comparativo de planos
            </button>
            <button @click="aba = 'historico'" class="pb-3 text-sm transition"
                :class="aba === 'historico' ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                Histórico de faturas
                <span v-if="faturas.length" class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">{{ faturas.length }}</span>
            </button>
        </div>

        <!-- ── ABA PLANOS ────────────────────────────────────────────────── -->
        <template v-if="aba === 'planos'">
            <!-- Toggle mensal/anual -->
            <div class="mb-8 flex justify-center">
                <div class="flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1">
                    <button @click="periodicidade = 'mensal'"
                        class="rounded-lg px-5 py-1.5 text-sm transition"
                        :class="periodicidade === 'mensal' ? 'bg-white font-semibold text-gray-800 shadow-sm' : 'text-gray-500'">
                        Mensal
                    </button>
                    <button @click="periodicidade = 'anual'"
                        class="rounded-lg px-5 py-1.5 text-sm transition"
                        :class="periodicidade === 'anual' ? 'bg-white font-semibold text-gray-800 shadow-sm' : 'text-gray-500'">
                        Anual
                        <span class="ml-1 rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-600">-17%</span>
                    </button>
                </div>
            </div>

            <!-- Cards de planos -->
            <div class="grid grid-cols-4 gap-5">
                <div v-for="plano in planos" :key="plano.id"
                    class="relative flex flex-col rounded-2xl border bg-white p-6 shadow-sm transition hover:shadow-md"
                    :class="plano.slug === 'profissional' ? 'border-primary-400 ring-1 ring-primary-300' : 'border-gray-100'">

                    <!-- Badge popular -->
                    <div v-if="plano.slug === 'profissional'"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-500 px-3 py-0.5 text-xs font-bold text-white">
                        Mais popular
                    </div>

                    <!-- Plano atual badge -->
                    <div v-if="isPlanoAtual(plano)"
                        class="absolute right-3 top-3 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold text-green-600">
                        Plano atual
                    </div>

                    <p class="mb-1 text-sm font-bold uppercase tracking-wide text-gray-400">{{ plano.nome }}</p>
                    <p class="mb-3 text-xs text-gray-400">{{ plano.descricao }}</p>

                    <!-- Preço -->
                    <div class="mb-1">
                        <span v-if="precoAtual(plano) === 0" class="text-3xl font-bold text-gray-800">Grátis</span>
                        <template v-else>
                            <span class="text-3xl font-bold text-gray-800">{{ fmt(precoMensalEfetivo(plano)) }}</span>
                            <span class="text-sm text-gray-400">/mês</span>
                        </template>
                    </div>
                    <p v-if="periodicidade === 'anual' && precoAtual(plano) > 0" class="mb-1 text-xs text-gray-400">
                        Cobrado {{ fmt(precoAtual(plano)) }}/ano
                    </p>
                    <p v-if="periodicidade === 'anual' && economiaAnual(plano) > 0" class="mb-4 text-xs font-medium text-green-600">
                        Economia de {{ economiaAnual(plano) }}% vs mensal
                    </p>
                    <div v-else class="mb-4"></div>

                    <!-- Features -->
                    <ul class="mb-6 flex-1 space-y-2 text-xs text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="h-3.5 w-3.5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ plano.consultas_ilimitadas ? 'Consultas ilimitadas' : `${plano.limite_consultas_mes} consultas/mês` }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-3.5 w-3.5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ plano.limite_usuarios }} {{ plano.limite_usuarios === 1 ? 'usuário' : 'usuários' }}
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-3.5 w-3.5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ plano.limite_storage_gb }} GB de armazenamento
                        </li>
                        <li class="flex items-center gap-2" :class="plano.chat_audio ? 'text-gray-500' : 'text-gray-300'">
                            <svg class="h-3.5 w-3.5 shrink-0" :class="plano.chat_audio ? 'text-primary-500' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Áudio no chat
                        </li>
                        <li class="flex items-center gap-2" :class="plano.chat_grupos ? 'text-gray-500' : 'text-gray-300'">
                            <svg class="h-3.5 w-3.5 shrink-0" :class="plano.chat_grupos ? 'text-primary-500' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Grupos de chat
                        </li>
                    </ul>

                    <!-- Ações -->
                    <template v-if="precoAtual(plano) === 0 || isPlanoAtual(plano)">
                        <button disabled class="w-full rounded-xl border border-gray-200 py-2 text-sm text-gray-400">
                            {{ isPlanoAtual(plano) ? 'Plano atual' : 'Grátis' }}
                        </button>
                    </template>
                    <template v-else>
                        <div class="flex flex-col gap-2">
                            <button v-if="temStripe(plano)" @click="assinarStripe(plano)"
                                class="w-full rounded-xl py-2 text-sm font-semibold transition"
                                :class="plano.slug === 'profissional' ? 'bg-primary-500 text-white hover:bg-primary-600' : 'border border-primary-400 text-primary-600 hover:bg-primary-50'">
                                Assinar com cartão
                            </button>
                            <button @click="assinarPix(plano)"
                                class="w-full rounded-xl border border-gray-200 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">
                                Pagar com PIX
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Cancelar assinatura -->
            <div v-if="clinica.stripe_subscription_id" class="mt-8 text-center">
                <button @click="cancelarAssinatura" class="text-xs text-red-400 hover:text-red-600 hover:underline">
                    Cancelar assinatura
                </button>
            </div>
        </template>

        <!-- ── ABA HISTÓRICO ─────────────────────────────────────────────── -->
        <template v-else>
            <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 text-left">Fatura</th>
                            <th class="px-5 py-3 text-left">Data</th>
                            <th class="px-5 py-3 text-right">Valor</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="!faturas.length">
                            <td colspan="5" class="py-12 text-center text-sm text-gray-400">
                                Nenhuma fatura encontrada
                            </td>
                        </tr>
                        <tr v-for="f in faturas" :key="f.id" class="hover:bg-gray-50/50">
                            <td class="px-5 py-3 font-medium text-gray-700">{{ f.numero || f.id }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ f.data }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-800">{{ fmt(f.valor) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="faturaStatusClass[f.status] ?? 'bg-gray-100 text-gray-500'">
                                    {{ faturaStatusLabel[f.status] ?? f.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a v-if="f.pdf_url" :href="f.pdf_url" target="_blank"
                                    class="text-xs text-primary-600 hover:underline">
                                    Baixar PDF
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Modal PIX ─────────────────────────────────────────────────────────── -->
    <div v-if="showPix" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showPix = false">
        <div class="w-full max-w-sm rounded-2xl bg-white p-7 shadow-2xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Pagamento via PIX</h2>
                <button @click="showPix = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Carregando -->
            <div v-if="carregandoPix" class="flex flex-col items-center gap-3 py-8">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary-300 border-t-primary-600"></div>
                <p class="text-sm text-gray-500">Gerando cobrança PIX...</p>
            </div>

            <!-- QR Code -->
            <template v-else-if="pixData">
                <div class="mb-4 text-center">
                    <img v-if="pixData.qr_image_url" :src="pixData.qr_image_url" alt="QR Code PIX"
                        class="mx-auto mb-3 h-48 w-48 rounded-lg border border-gray-100" />
                    <p class="text-2xl font-bold text-gray-800">{{ fmt(pixData.valor) }}</p>
                    <p v-if="pixData.expiracao" class="mt-1 text-xs text-gray-400">
                        Expira em {{ pixData.expiracao }}
                    </p>
                </div>

                <div class="mb-4 rounded-lg bg-gray-50 p-3">
                    <p class="mb-1 text-[10px] font-medium uppercase text-gray-400">Código PIX Copia e Cola</p>
                    <p class="break-all text-[11px] text-gray-600 leading-relaxed">{{ pixData.pix_code }}</p>
                </div>

                <button @click="copiarPix"
                    class="w-full rounded-xl py-2.5 text-sm font-semibold transition"
                    :class="pixCopiado ? 'bg-green-500 text-white' : 'bg-primary-500 text-white hover:bg-primary-600'">
                    {{ pixCopiado ? '✓ Copiado!' : 'Copiar código PIX' }}
                </button>

                <p class="mt-3 text-center text-xs text-gray-400">
                    Após o pagamento seu plano será ativado automaticamente
                </p>
            </template>
        </div>
    </div>
</template>
