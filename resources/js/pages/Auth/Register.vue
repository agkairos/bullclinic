<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    clinica_nome: '',
});

function submit() {
    form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <div>
        <h1 class="mb-1 text-xl font-bold text-gray-900">Criar conta</h1>
        <p class="mb-6 text-sm text-gray-500">14 dias grátis, sem cartão de crédito</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nome completo</label>
                <input v-model="form.name" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" :class="{ 'border-red-400': form.errors.name }" placeholder="Dr. João Silva" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nome da clínica</label>
                <input v-model="form.clinica_nome" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" :class="{ 'border-red-400': form.errors.clinica_nome }" placeholder="Clínica Vet Feliz" />
                <p v-if="form.errors.clinica_nome" class="mt-1 text-xs text-red-500">{{ form.errors.clinica_nome }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" :class="{ 'border-red-400': form.errors.email }" placeholder="seu@email.com" />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Senha</label>
                    <input v-model="form.password" type="password" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" :class="{ 'border-red-400': form.errors.password }" placeholder="••••••••" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirmar</label>
                    <input v-model="form.password_confirmation" type="password" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" placeholder="••••••••" />
                </div>
            </div>

            <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-primary-500 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-60">
                {{ form.processing ? 'Criando conta...' : 'Criar conta grátis' }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-500">
            Já tem conta?
            <a href="/login" class="font-medium text-primary-500 hover:underline">Entrar</a>
        </p>
    </div>
</template>
