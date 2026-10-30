<script setup lang="ts">
import { router } from '@inertiajs/vue3';

defineProps<{
    status?: number;
    message?: string;
}>();

function reload() {
    window.location.reload();
}

function goHome() {
    router.visit('/');
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4">
        <div class="w-full max-w-md text-center">
            <!-- Ícone -->
            <div class="mb-6 flex justify-center">
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-orange-100">
                    <svg class="h-12 w-12 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <!-- Código do erro -->
            <h1 class="mb-2 text-6xl font-bold text-gray-900">500</h1>

            <!-- Mensagem -->
            <h2 class="mb-3 text-2xl font-semibold text-gray-800">Erro Interno do Servidor</h2>
            <p class="mb-8 text-gray-600">
                {{ message || 'Ocorreu um erro inesperado. Nossa equipe foi notificada e está trabalhando na correção.' }}
            </p>

            <!-- Botões -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <button
                    @click="reload"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Recarregar página
                </button>
                <button
                    @click="goHome"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Ir para o início
                </button>
            </div>
        </div>
    </div>
</template>
