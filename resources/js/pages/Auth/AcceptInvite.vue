<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const props = defineProps<{
    token: string;
    name: string;
    email: string;
}>();

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(`/convite/${props.token}`, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}

const senhaForca = computed(() => {
    const p = form.password;
    if (!p) return 0;
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    return score;
});

const forcaCor = computed(() => {
    if (senhaForca.value <= 1) return 'bg-red-400';
    if (senhaForca.value === 2) return 'bg-yellow-400';
    if (senhaForca.value === 3) return 'bg-blue-400';
    return 'bg-green-400';
});

const forcaTextoClass = computed(() => {
    if (senhaForca.value <= 1) return 'text-red-500';
    if (senhaForca.value === 2) return 'text-yellow-500';
    if (senhaForca.value === 3) return 'text-blue-500';
    return 'text-green-500';
});

const forcaLabel = computed(() => {
    if (senhaForca.value <= 1) return 'Senha fraca';
    if (senhaForca.value === 2) return 'Senha razoável';
    if (senhaForca.value === 3) return 'Senha boa';
    return 'Senha forte';
});
</script>

<template>
    <div>
        <h1 class="mb-1 text-xl font-bold text-gray-900">Ativar sua conta</h1>
        <p class="mb-6 text-sm text-gray-500">Olá, <strong>{{ name }}</strong>! Crie sua senha para acessar o sistema.</p>

        <!-- Info do usuário -->
        <div class="mb-5 rounded-lg bg-gray-50 px-4 py-3 text-sm">
            <p class="font-medium text-gray-800">{{ name }}</p>
            <p class="text-gray-400">{{ email }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nova senha</label>
                <input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                    :class="{ 'border-red-400': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Confirmar senha</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Repita a senha"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
                    :class="{ 'border-red-400': form.errors.password_confirmation }"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
            </div>

            <!-- Indicador de força -->
            <div v-if="form.password" class="space-y-1">
                <div class="flex gap-1">
                    <div v-for="i in 4" :key="i"
                        class="h-1 flex-1 rounded-full transition-colors"
                        :class="senhaForca >= i ? forcaCor : 'bg-gray-100'">
                    </div>
                </div>
                <p class="text-xs" :class="forcaTextoClass">{{ forcaLabel }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-primary-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:opacity-60">
                {{ form.processing ? 'Ativando...' : 'Ativar conta' }}
            </button>
        </form>
    </div>
</template>
