<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cliente: {
        id: number; nome: string; cpf: string | null; rg: string | null;
        email: string | null; telefone: string | null; celular: string | null;
        cep: string | null; logradouro: string | null; numero: string | null;
        complemento: string | null; bairro: string | null; cidade: string | null;
        estado: string | null; observacoes: string | null; ativo: boolean;
        pacientes: Array<{
            id: number; nome: string; especie: string; raca: string | null;
            sexo: string; consultas_count: number;
        }>;
    };
}>();

function excluir() {
    if (!confirm(`Excluir cliente "${props.cliente.nome}"?`)) return;
    router.delete(`/clientes/${props.cliente.id}`);
}

function especieIcon(e: string) {
    if (e === 'felino') return '🐱';
    if (e === 'canino') return '🐶';
    if (e === 'ave') return '🦜';
    return '🐾';
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-4 flex items-center gap-2 text-xs text-gray-400">
            <Link href="/clientes" class="hover:text-primary-500">Clientes</Link>
            <span>/</span>
            <span class="text-gray-600">{{ cliente.nome }}</span>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- Info principal -->
            <div class="col-span-2 space-y-5">
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700">
                                {{ cliente.nome.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900">{{ cliente.nome }}</h2>
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="cliente.ativo ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400'">
                                    {{ cliente.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="`/clientes/${cliente.id}/editar`" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                Editar
                            </Link>
                            <button @click="excluir" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-500 hover:bg-red-50">
                                Excluir
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div v-if="cliente.cpf">
                            <p class="text-xs text-gray-400">CPF</p>
                            <p class="font-medium text-gray-700">{{ cliente.cpf }}</p>
                        </div>
                        <div v-if="cliente.email">
                            <p class="text-xs text-gray-400">E-mail</p>
                            <p class="font-medium text-gray-700">{{ cliente.email }}</p>
                        </div>
                        <div v-if="cliente.celular">
                            <p class="text-xs text-gray-400">Celular</p>
                            <p class="font-medium text-gray-700">{{ cliente.celular }}</p>
                        </div>
                        <div v-if="cliente.telefone">
                            <p class="text-xs text-gray-400">Telefone</p>
                            <p class="font-medium text-gray-700">{{ cliente.telefone }}</p>
                        </div>
                        <div v-if="cliente.cidade" class="col-span-2">
                            <p class="text-xs text-gray-400">Endereço</p>
                            <p class="font-medium text-gray-700">
                                {{ [cliente.logradouro, cliente.numero, cliente.bairro, cliente.cidade, cliente.estado].filter(Boolean).join(', ') }}
                            </p>
                        </div>
                        <div v-if="cliente.observacoes" class="col-span-2">
                            <p class="text-xs text-gray-400">Observações</p>
                            <p class="text-gray-600">{{ cliente.observacoes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Animais -->
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">Animais ({{ cliente.pacientes.length }})</h3>
                    <div v-if="!cliente.pacientes.length" class="py-4 text-center text-sm text-gray-400">Nenhum animal cadastrado</div>
                    <div class="divide-y divide-gray-50">
                        <div v-for="p in cliente.pacientes" :key="p.id" class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">{{ especieIcon(p.especie) }}</span>
                                <div>
                                    <p class="font-medium text-gray-800">{{ p.nome }}</p>
                                    <p class="text-xs text-gray-400">{{ p.especie }} {{ p.raca ? `· ${p.raca}` : '' }} · {{ p.sexo === 'M' ? 'Macho' : 'Fêmea' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400">{{ p.consultas_count }} consulta(s)</span>
                                <Link :href="`/prontuarios/paciente/${p.id}`" class="rounded border border-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">
                                    Prontuário
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações rápidas -->
            <div class="space-y-3">
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Ações</p>
                    <div class="space-y-2">
                        <Link :href="`/clientes/${cliente.id}/editar`" class="flex w-full items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Editar dados
                        </Link>
                        <Link href="/agenda" class="flex w-full items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Agendar consulta
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
