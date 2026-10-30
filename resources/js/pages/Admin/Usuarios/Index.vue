<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    usuarios: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            cargo: string | null;
            crmv: string | null;
            invite_accepted_at: string | null;
            invite_sent_at: string | null;
            user_clinicas: Array<{ role: { id: number; display_name: string } | null; ativo: boolean }> | null;
            especialidades: Array<{ id: number; nome: string }> | null;
        }>;
        links: any[];
        meta: any;
    };
    roles: Array<{ id: number; nome: string; display_name: string }>;
    especialidades: Array<{ id: number; nome: string }>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');
const showModal = ref(false);
const editando = ref<typeof props.usuarios.data[0] | null>(null);

const form = ref({ name: '', email: '', role_id: '', crmv: '', cargo: '', ativo: true, especialidade_ids: [] as number[] });

function abrirNovo() {
    editando.value = null;
    form.value = { name: '', email: '', role_id: String(props.roles[0]?.id ?? ''), crmv: '', cargo: '', ativo: true, especialidade_ids: [] };
    showModal.value = true;
}

function abrirEditar(u: typeof props.usuarios.data[0]) {
    editando.value = u;
    form.value = {
        name: u.name,
        email: u.email,
        role_id: String(u.user_clinicas?.[0]?.role?.id ?? ''),
        crmv: u.crmv ?? '',
        cargo: u.cargo ?? '',
        ativo: u.user_clinicas?.[0]?.ativo ?? true,
        especialidade_ids: u.especialidades?.map(e => e.id) ?? [],
    };
    showModal.value = true;
}

function toggleEspecialidade(id: number) {
    const idx = form.value.especialidade_ids.indexOf(id);
    if (idx === -1) form.value.especialidade_ids.push(id);
    else form.value.especialidade_ids.splice(idx, 1);
}

function salvar() {
    if (editando.value) {
        router.put(`/admin/usuarios/${editando.value.id}`, form.value, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        router.post('/admin/usuarios', form.value, {
            onSuccess: () => { showModal.value = false; },
        });
    }
}

function reenviarConvite(u: typeof props.usuarios.data[0]) {
    if (!confirm(`Reenviar convite para ${u.email}?`)) return;
    router.post(`/admin/usuarios/${u.id}/reenviar-convite`);
}

function buscar() {
    router.get('/admin/usuarios', { search: search.value }, { preserveState: true, replace: true });
}

function statusConvite(u: typeof props.usuarios.data[0]) {
    if (u.user_clinicas?.[0]?.ativo) return { label: 'Ativo', class: 'bg-green-50 text-green-600' };
    if (u.invite_sent_at) return { label: 'Convite enviado', class: 'bg-yellow-50 text-yellow-600' };
    return { label: 'Inativo', class: 'bg-gray-100 text-gray-400' };
}
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Usuários</h1>
            <button @click="abrirNovo" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Convidar usuário
            </button>
        </div>

        <!-- Busca -->
        <div class="mb-4">
            <div class="relative w-72">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar por nome ou e-mail..."
                    class="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>

        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Nome</th>
                        <th class="px-5 py-3 text-left">E-mail</th>
                        <th class="px-5 py-3 text-left">Cargo</th>
                        <th class="px-5 py-3 text-left">Perfil</th>
                        <th class="px-5 py-3 text-left">Especialidades</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!usuarios.data.length">
                        <td colspan="7" class="py-10 text-center text-gray-400">Nenhum usuário encontrado</td>
                    </tr>
                    <tr v-for="u in usuarios.data" :key="u.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700">
                                    {{ u.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-gray-800">{{ u.name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ u.email }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ u.cargo ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ u.user_clinicas?.[0]?.role?.display_name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="e in u.especialidades" :key="e.id"
                                    class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-medium text-primary-600">
                                    {{ e.nome }}
                                </span>
                                <span v-if="!u.especialidades?.length" class="text-xs text-gray-400">—</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex flex-col items-center gap-1">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusConvite(u).class">
                                    {{ statusConvite(u).label }}
                                </span>
                                <!-- Botão reenviar se convite pendente -->
                                <button v-if="u.invite_sent_at && !u.invite_accepted_at"
                                    @click="reenviarConvite(u)"
                                    class="text-[11px] text-primary-500 hover:underline">
                                    Reenviar
                                </button>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <button @click="abrirEditar(u)" class="rounded p-1 text-gray-400 hover:text-primary-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30" @click.self="showModal = false">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">{{ editando ? 'Editar usuário' : 'Convidar usuário' }}</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Info sobre o fluxo de convite (só ao criar) -->
            <div v-if="!editando" class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Um e-mail de convite será enviado para o usuário criar sua própria senha.
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Nome *</label>
                    <input v-model="form.name" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div v-if="!editando">
                    <label class="mb-1 block text-xs font-medium text-gray-600">E-mail *</label>
                    <input v-model="form.email" type="email" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Cargo</label>
                        <input v-model="form.cargo" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">CRMV</label>
                        <input v-model="form.crmv" type="text" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Perfil de acesso *</label>
                    <select v-model="form.role_id" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                        <option v-for="r in roles" :key="r.id" :value="String(r.id)">{{ r.display_name }}</option>
                    </select>
                </div>
                <div v-if="editando" class="flex items-center gap-2">
                    <input v-model="form.ativo" type="checkbox" id="ativo" class="h-4 w-4 rounded text-primary-500" />
                    <label for="ativo" class="text-sm text-gray-600">Usuário ativo</label>
                </div>

                <!-- Especialidades -->
                <div v-if="especialidades.length">
                    <label class="mb-2 block text-xs font-medium text-gray-600">Especialidades</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="e in especialidades" :key="e.id"
                            type="button"
                            @click="toggleEspecialidade(e.id)"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition"
                            :class="form.especialidade_ids.includes(e.id)
                                ? 'border-primary-400 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-500 hover:border-primary-300'">
                            {{ e.nome }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button @click="showModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="salvar" class="flex items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">
                    <svg v-if="!editando" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.22 1.2 2 2 0 012.2 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                    {{ editando ? 'Salvar' : 'Enviar convite' }}
                </button>
            </div>
        </div>
    </div>
</template>
