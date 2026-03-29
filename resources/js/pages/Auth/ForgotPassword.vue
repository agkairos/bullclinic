<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({ email: '' });
</script>

<template>
    <div>
        <h1 class="mb-1 text-xl font-bold text-gray-900">Redefinir senha</h1>
        <p class="mb-6 text-sm text-gray-500">Enviaremos um link de redefinição para seu e-mail.</p>

        <div v-if="$page.props.flash?.status" class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ $page.props.flash.status }}
        </div>

        <form @submit.prevent="form.post('/forgot-password')" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" placeholder="seu@email.com" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>
            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-60">
                {{ form.processing ? 'Enviando...' : 'Enviar link' }}
            </button>
        </form>

        <p class="mt-4 text-center text-sm">
            <a href="/login" class="text-primary-500 hover:underline">Voltar ao login</a>
        </p>
    </div>
</template>
