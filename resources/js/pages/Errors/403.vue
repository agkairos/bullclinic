<script setup lang="ts">
import { router } from '@inertiajs/vue3';

defineProps<{
    status?: number;
    message?: string;
}>();

function goBack() {
    window.history.back();
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
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            <!-- Código do erro -->
            <h1 class="mb-2 text-6xl font-bold text-gray-900">403</h1>

            <!-- Mensagem -->
            <h2 class="mb-3 text-2xl font-semibold text-gray-800">Acesso Negado</h2>
            <p class="mb-8 text-gray-600">
                {{ message || 'Você não tem permissão para acessar esta página.' }}
            </p>

            <!-- Botões -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <button
                    @click="goBack"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
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
