<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import AppToast from '@/components/AppToast.vue';
import AppConfirm from '@/components/AppConfirm.vue';
import StorageIndicator from '@/components/StorageIndicator.vue';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const clinicas = computed(() => page.props.clinicas);
const chatNaoLidas = computed(() => page.props.chat_nao_lidas);
const clinicaAtiva = computed(() => user.value?.clinica_ativa);

const navItems = computed(() => [
    { label: 'Resumo',      href: '/',              icon: 'home',           name: 'dashboard' },
    { label: 'Agenda',      href: '/agenda',        icon: 'calendar',       name: 'agenda' },
    { label: 'Em espera',   href: '/em-espera',     icon: 'clock',          name: 'fila' },
    { label: 'Clientes',    href: '/clientes',      icon: 'users',          name: 'clientes' },
    { label: 'Pacientes',   href: '/pacientes',     icon: 'paw',            name: 'pacientes' },
    { label: 'Prontuários', href: '/prontuarios',   icon: 'file-text',      name: 'prontuario' },
    { label: 'Estoque',     href: '/estoque',       icon: 'box',            name: 'estoque' },
    { label: 'Financeiro',  href: '/financeiro',    icon: 'dollar-sign',    name: 'financeiro' },
    {
        label: 'Conversas',
        href: '/conversas',
        icon: 'message-circle',
        name: 'chat',
        badge: chatNaoLidas.value || null,
    },
    { label: 'Documentos',  href: '/documentos',    icon: 'folder',         name: 'docs' },
    { label: 'Planos',      href: '/planos',        icon: 'credit-card',    name: 'planos' },
    { label: 'Unidades',    href: '/conta/unidades', icon: 'git-branch',     name: 'unidades' },
    { label: 'Admin',       href: '/admin/clinica', icon: 'settings',       name: 'admin' },
]);

const currentRoute = computed(() => {
    const path = page.url.split('?')[0];
    if (path === '/') return 'dashboard';
    if (path.startsWith('/agenda')) return 'agenda';
    if (path.startsWith('/em-espera')) return 'fila';
    if (path.startsWith('/prontuario')) return 'prontuario';
    if (path.startsWith('/estoque')) return 'estoque';
    if (path.startsWith('/clientes')) return 'clientes';
    if (path.startsWith('/pacientes')) return 'pacientes';
    if (path.startsWith('/conversas')) return 'chat';
    if (path.startsWith('/documentos')) return 'docs';
    if (path.startsWith('/financeiro')) return 'financeiro';
    if (path.startsWith('/planos')) return 'planos';
    if (path.startsWith('/conta/unidades')) return 'unidades';
    if (path.startsWith('/admin')) return 'admin';
    return '';
});

function switchClinica(id: number) {
    if (id === user.value?.clinica_ativa_id) return;
    fetch('/clinica/switch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content },
        body: JSON.stringify({ clinica_id: id }),
    }).then(() => window.location.href = '/dashboard');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-[#f8f9fb]">

        <!-- Strip esquerdo -->
        <div class="flex w-11 flex-col items-center border-r border-gray-100 bg-gray-50 py-3">
            <!-- Logo -->
            <img src="/mini.png" alt="BullClinic" class="h-8 w-8 object-contain" />
        </div>

        <!-- Sidebar principal -->
        <aside class="flex w-52 flex-col border-r border-gray-100 bg-white">
            <!-- Header: seletor de clínica -->
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                <div class="relative group">
                    <button class="flex items-center gap-1.5 text-sm font-semibold text-gray-800 hover:text-gray-900">
                        {{ clinicaAtiva?.nome ?? 'Clinic Up' }}
                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- Dropdown de clínicas -->
                    <div v-if="clinicas.length > 1" class="absolute left-0 top-full z-50 mt-1 hidden w-48 rounded-lg border border-gray-100 bg-white py-1 shadow-lg group-focus-within:block">
                        <button
                            v-for="c in clinicas"
                            :key="c.id"
                            @click="switchClinica(c.id)"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-gray-50"
                            :class="c.id === user?.clinica_ativa_id ? 'text-primary-500 font-medium' : 'text-gray-700'"
                        >
                            {{ c.nome }}
                        </button>
                    </div>
                </div>
                <Link href="/admin/clinica" class="rounded p-1 text-gray-400 hover:bg-gray-50 hover:text-gray-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-2 py-3">
                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Geral</p>
                <ul class="space-y-0.5">
                    <li v-for="item in navItems" :key="item.name">
                        <Link
                            :href="item.href"
                            class="group flex items-center gap-2.5 rounded-md px-2 py-2 text-sm transition-colors"
                            :class="currentRoute === item.name
                                ? 'border-l-2 border-primary-500 bg-primary-50 pl-[7px] font-medium text-primary-600'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'"
                        >
                            <!-- Icons -->
                            <svg v-if="item.icon === 'home'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <svg v-else-if="item.icon === 'calendar'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <svg v-else-if="item.icon === 'clock'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <svg v-else-if="item.icon === 'file-text'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                            </svg>
                            <svg v-else-if="item.icon === 'box'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                            <svg v-else-if="item.icon === 'message-circle'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <svg v-else-if="item.icon === 'folder'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            </svg>
                            <svg v-else-if="item.icon === 'users'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <svg v-else-if="item.icon === 'dollar-sign'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            <svg v-else-if="item.icon === 'paw'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <circle cx="7" cy="4" r="1.5"/><circle cx="17" cy="4" r="1.5"/><circle cx="4" cy="10" r="1.5"/><circle cx="20" cy="10" r="1.5"/>
                                <path d="M12 22c-3.5 0-6-2-6-5 0-2 1-3.5 2.5-4.5S11 11 12 11s2 .5 3.5 1.5S18 15 18 17c0 3-2.5 5-6 5z"/>
                            </svg>
                            <svg v-else-if="item.icon === 'git-branch'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/>
                            </svg>
                            <svg v-else-if="item.icon === 'credit-card'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                            </svg>
                            <svg v-else-if="item.icon === 'settings'" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>

                            <span class="flex-1">{{ item.label }}</span>

                            <!-- Badge -->
                            <span v-if="item.badge" class="flex h-4 min-w-4 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-bold text-white">
                                {{ item.badge }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Footer: usuário -->
            <div class="border-t border-gray-100 px-3 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">
                        {{ user?.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium text-gray-800">{{ user?.name }}</p>
                        <p class="truncate text-[10px] text-gray-400">{{ user?.cargo ?? 'Usuário' }}</p>
                    </div>
                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="(document as any)?.querySelector?.('[name=csrf-token]')?.content">
                        <button type="submit" class="rounded p-1 text-gray-400 hover:text-gray-600">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Storage indicator -->
            <StorageIndicator />
        </aside>

        <!-- Área de conteúdo -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Sub-nav Admin -->
            <div v-if="currentRoute === 'admin'" class="flex shrink-0 items-center gap-1 border-b border-gray-100 bg-white px-6">
                <Link v-for="item in [
                    { label: 'Clínica', href: '/admin/clinica' },
                    { label: 'Usuários', href: '/admin/usuarios' },
                    { label: 'Tipos de serviço', href: '/admin/tipos-servico' },
                    { label: 'Especialidades', href: '/admin/especialidades' },
                    { label: 'Laboratórios', href: '/admin/laboratorios' },
                    { label: 'Horários', href: '/admin/horarios' },
                ]" :key="item.href" :href="item.href"
                    class="px-3 py-2.5 text-xs transition border-b-2"
                    :class="$page.url.startsWith(item.href) ? 'border-primary-500 font-semibold text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                    {{ item.label }}
                </Link>
            </div>

<main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>

    <AppToast />
    <AppConfirm />
</template>
