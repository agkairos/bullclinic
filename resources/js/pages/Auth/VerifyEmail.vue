<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage<any>();
const enviando = ref(false);

function reenviar() {
    enviando.value = true;
    router.post('/email/verification-notification', {}, {
        onFinish: () => { enviando.value = false; },
    });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 px-4">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="mb-2 text-2xl font-bold text-gray-900">Verifique seu email</h1>
                <p class="text-sm text-gray-600">
                    Enviamos um link de confirmação para <strong class="text-gray-900">{{ page.props.auth.user?.email }}</strong>
                </p>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-lg">
                <div class="mb-6 space-y-3 text-sm text-gray-600">
                    <p class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Acesse sua caixa de entrada e clique no link de confirmação.
                    </p>
                    <p class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        O link expira em 60 minutos.
                    </p>
                    <p class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Não esqueça de verificar a pasta de spam.
                    </p>
                </div>

                <div v-if="page.props.flash?.success" class="mb-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
                    {{ page.props.flash.success }}
                </div>

                <div v-if="page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ page.props.flash.error }}
                </div>

                <button @click="reenviar" :disabled="enviando"
                    class="w-full rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-50">
                    {{ enviando ? 'Enviando...' : 'Reenviar email de confirmação' }}
                </button>

                <div class="mt-4 text-center">
                    <a href="/login" class="text-sm text-gray-500 hover:text-primary-500">
                        ← Voltar para login
                    </a>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                Problemas? Entre em contato: <a href="mailto:suporte@bullclinic.com" class="text-primary-600 hover:underline">suporte@bullclinic.com</a>
            </p>
        </div>
    </div>
</template>
