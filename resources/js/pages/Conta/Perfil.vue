<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        telefone?: string;
        cargo?: string;
    };
}>();

// Form para dados básicos
const form = useForm({
    name: props.user.name,
    telefone: props.user.telefone || '',
    cargo: props.user.cargo || '',
});

// Form para senha (separado)
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showPasswordSection = ref(false);

// Máscara de telefone
function maskTelefone(e: Event) {
    let v = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 11);
    if (v.length <= 10) {
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
    } else {
        v = v.replace(/^(\d{2})(\d)/, '($1) $2');
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
    }
    form.telefone = v;
}

function salvarPerfil() {
    form.put(route('conta.perfil.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Form resetado automaticamente pelo Inertia
        },
    });
}

function salvarSenha() {
    passwordForm.put(route('conta.perfil.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showPasswordSection.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Minha Conta</h1>
                <p class="text-sm text-gray-600">Gerencie suas informações pessoais e senha</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Informações Pessoais -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Informações Pessoais</h2>

                    <form @submit.prevent="salvarPerfil" class="space-y-4">
                        <!-- Nome -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email (readonly) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                            <input
                                :value="user.email"
                                type="email"
                                readonly
                                disabled
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 cursor-not-allowed"
                            />
                            <p class="mt-1 text-xs text-gray-500">O e-mail não pode ser alterado</p>
                        </div>

                        <!-- Telefone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input
                                :value="form.telefone"
                                @input="maskTelefone"
                                type="text"
                                placeholder="(00) 00000-0000"
                                maxlength="15"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                :class="{ 'border-red-500': form.errors.telefone }"
                            />
                            <p v-if="form.errors.telefone" class="mt-1 text-xs text-red-600">{{ form.errors.telefone }}</p>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo/Função</label>
                            <input
                                v-model="form.cargo"
                                type="text"
                                placeholder="Ex: Veterinário, Recepcionista, etc."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                :class="{ 'border-red-500': form.errors.cargo }"
                            />
                            <p v-if="form.errors.cargo" class="mt-1 text-xs text-red-600">{{ form.errors.cargo }}</p>
                        </div>

                        <!-- Botão Salvar -->
                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Segurança -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Segurança</h2>

                    <div v-if="!showPasswordSection">
                        <p class="mb-4 text-sm text-gray-600">Altere sua senha regularmente para manter sua conta segura.</p>
                        <button
                            @click="showPasswordSection = true"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Alterar Senha
                        </button>
                    </div>

                    <form v-else @submit.prevent="salvarSenha" class="space-y-4">
                        <!-- Senha Atual -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha atual</label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                :class="{ 'border-red-500': passwordForm.errors.current_password }"
                            />
                            <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <!-- Nova Senha -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                :class="{ 'border-red-500': passwordForm.errors.password }"
                            />
                            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password }}</p>
                        </div>

                        <!-- Confirmar Nova Senha -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
                        </div>

                        <!-- Botões -->
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showPasswordSection = false; passwordForm.reset()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="passwordForm.processing"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-700 disabled:opacity-50"
                            >
                                {{ passwordForm.processing ? 'Salvando...' : 'Alterar Senha' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
