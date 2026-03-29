<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <div>
        <h1 class="mb-1 text-xl font-bold text-gray-900">Entrar na sua conta</h1>
        <p class="mb-6 text-sm text-gray-500">Gerencie sua clínica veterinária</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                    :class="{ 'border-red-400': form.errors.email }"
                    placeholder="seu@email.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700">Senha</label>
                    <a href="/forgot-password" class="text-xs text-primary-500 hover:underline">Esqueceu?</a>
                </div>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                    :class="{ 'border-red-400': form.errors.password }"
                    placeholder="••••••••"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2">
                <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                <span class="text-sm text-gray-600">Lembrar de mim</span>
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-60"
            >
                {{ form.processing ? 'Entrando...' : 'Entrar' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Não tem conta?
            <a href="/register" class="font-medium text-primary-500 hover:underline">Criar conta grátis</a>
        </p>
    </div>
</template>
