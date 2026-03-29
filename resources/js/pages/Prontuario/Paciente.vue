<script setup lang="ts">
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    paciente: {
        id: number;
        nome: string;
        especie: string;
        raca: string | null;
        sexo: string;
        data_nascimento: string | null;
        peso: string | null;
        pelagem: string | null;
        castrado: boolean;
        microchip: string | null;
        cliente: { id: number; nome: string; telefone: string | null; email: string | null };
        fotos: Array<{ url: string; principal: boolean }>;
        tags: Array<{ nome: string; cor: string }>;
        consultas: Array<{
            id: number;
            tipo_consulta: string;
            status: string;
            iniciada_em: string;
            finalizada_em: string | null;
            diagnostico: string | null;
            veterinario: { name: string };
            receitas: any[];
            exames: any[];
            procedimentos: any[];
            vacinacoes: any[];
        }>;
        vacinacoes: Array<{
            id: number;
            vacina_nome: string;
            data_aplicacao: string;
            proxima_dose: string | null;
            veterinario: { name: string };
        }>;
    };
}>();

const abaAtiva = ref<'historico' | 'vacinas'>('historico');
const showNovaConsulta = ref(false);
const novaConsultaForm = ref({ tipo_consulta: 'primeira_consulta', agendamento_id: '' });

function abrirConsulta() {
    router.post(`/prontuarios/paciente/${props.paciente.id}/abrir`, novaConsultaForm.value, {
        onSuccess: () => { showNovaConsulta.value = false; },
    });
}

function tipoLabel(t: string) {
    return { primeira_consulta: 'Primeira consulta', retorno: 'Retorno', emergencia: 'Emergência' }[t] ?? t;
}

function statusClass(s: string) {
    return { em_andamento: 'bg-yellow-50 text-yellow-600', finalizada: 'bg-green-50 text-green-600', cancelada: 'bg-gray-100 text-gray-400' }[s] ?? 'bg-gray-100 text-gray-500';
}

function idadeLabel(data: string | null) {
    if (!data) return null;
    const anos = Math.floor((Date.now() - new Date(data).getTime()) / (1000 * 60 * 60 * 24 * 365.25));
    return anos < 1 ? 'menos de 1 ano' : `${anos} ${anos === 1 ? 'ano' : 'anos'}`;
}
</script>

<template>
    <div class="px-8 py-6">
        <!-- Breadcrumb -->
        <div class="mb-4 flex items-center gap-2 text-xs text-gray-400">
            <Link href="/prontuarios" class="hover:text-primary-500">Prontuários</Link>
            <span>/</span>
            <span class="text-gray-600">{{ paciente.nome }}</span>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- Card do paciente -->
            <div class="space-y-4">
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-2xl">
                            <img v-if="paciente.fotos.find(f => f.principal)?.url" :src="paciente.fotos.find(f => f.principal)!.url" class="h-full w-full rounded-xl object-cover" />
                            <span v-else>🐾</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">{{ paciente.nome }}</h2>
                            <p class="text-xs text-gray-400">{{ paciente.especie }} {{ paciente.raca ? `· ${paciente.raca}` : '' }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Sexo</span>
                            <span class="font-medium text-gray-700">{{ paciente.sexo === 'M' ? 'Macho' : 'Fêmea' }}{{ paciente.castrado ? ' · Castrado' : '' }}</span>
                        </div>
                        <div v-if="paciente.data_nascimento" class="flex justify-between">
                            <span class="text-gray-400">Idade</span>
                            <span class="font-medium text-gray-700">{{ idadeLabel(paciente.data_nascimento) }}</span>
                        </div>
                        <div v-if="paciente.peso" class="flex justify-between">
                            <span class="text-gray-400">Peso</span>
                            <span class="font-medium text-gray-700">{{ paciente.peso }} kg</span>
                        </div>
                        <div v-if="paciente.microchip" class="flex justify-between">
                            <span class="text-gray-400">Microchip</span>
                            <span class="font-medium text-gray-700 font-mono text-[11px]">{{ paciente.microchip }}</span>
                        </div>
                    </div>

                    <div v-if="paciente.tags.length" class="mt-3 flex flex-wrap gap-1">
                        <span v-for="tag in paciente.tags" :key="tag.nome"
                            class="rounded-full px-2 py-0.5 text-[10px] font-medium text-white"
                            :style="{ backgroundColor: tag.cor }">
                            {{ tag.nome }}
                        </span>
                    </div>
                </div>

                <!-- Tutor -->
                <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Tutor</p>
                    <p class="font-medium text-gray-800">{{ paciente.cliente.nome }}</p>
                    <p v-if="paciente.cliente.telefone" class="mt-0.5 text-xs text-gray-500">{{ paciente.cliente.telefone }}</p>
                    <p v-if="paciente.cliente.email" class="text-xs text-gray-500">{{ paciente.cliente.email }}</p>
                </div>

                <!-- Nova consulta -->
                <button @click="showNovaConsulta = true" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-500 py-2.5 text-sm font-semibold text-white hover:bg-primary-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova consulta
                </button>
            </div>

            <!-- Histórico -->
            <div class="col-span-2">
                <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex border-b border-gray-100 px-5 pt-4">
                        <button v-for="aba in [['historico', 'Histórico'], ['vacinas', 'Vacinações']]" :key="aba[0]"
                            @click="abaAtiva = aba[0] as any"
                            class="mr-4 pb-3 text-sm transition"
                            :class="abaAtiva === aba[0] ? 'border-b-2 border-primary-500 font-semibold text-primary-600' : 'text-gray-500 hover:text-gray-700'">
                            {{ aba[1] }}
                        </button>
                    </div>

                    <!-- Histórico de consultas -->
                    <div v-if="abaAtiva === 'historico'" class="divide-y divide-gray-50">
                        <div v-if="!paciente.consultas.length" class="py-10 text-center text-sm text-gray-400">Nenhuma consulta registrada</div>
                        <div v-for="c in paciente.consultas" :key="c.id" class="p-5">
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(c.status)">{{ c.status === 'em_andamento' ? 'Em andamento' : c.status === 'finalizada' ? 'Finalizada' : 'Cancelada' }}</span>
                                    <span class="text-sm font-medium text-gray-800">{{ tipoLabel(c.tipo_consulta) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">{{ new Date(c.iniciada_em).toLocaleDateString('pt-BR') }}</span>
                                    <Link :href="`/prontuarios/consulta/${c.id}`" class="rounded border border-gray-200 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50">
                                        Abrir
                                    </Link>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400">{{ c.veterinario.name }}</p>
                            <p v-if="c.diagnostico" class="mt-1.5 text-sm text-gray-600">{{ c.diagnostico }}</p>
                            <div v-if="c.receitas.length || c.exames.length || c.procedimentos.length" class="mt-2 flex gap-2">
                                <span v-if="c.receitas.length" class="rounded bg-blue-50 px-2 py-0.5 text-[11px] text-blue-600">{{ c.receitas.length }} receita(s)</span>
                                <span v-if="c.exames.length" class="rounded bg-purple-50 px-2 py-0.5 text-[11px] text-purple-600">{{ c.exames.length }} exame(s)</span>
                                <span v-if="c.procedimentos.length" class="rounded bg-orange-50 px-2 py-0.5 text-[11px] text-orange-600">{{ c.procedimentos.length }} procedimento(s)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vacinações -->
                    <div v-if="abaAtiva === 'vacinas'" class="divide-y divide-gray-50">
                        <div v-if="!paciente.vacinacoes.length" class="py-10 text-center text-sm text-gray-400">Nenhuma vacinação registrada</div>
                        <div v-for="v in paciente.vacinacoes" :key="v.id" class="flex items-center justify-between p-5">
                            <div>
                                <p class="font-medium text-gray-800">{{ v.vacina_nome }}</p>
                                <p class="text-xs text-gray-400">{{ v.veterinario.name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-medium text-gray-600">{{ new Date(v.data_aplicacao).toLocaleDateString('pt-BR') }}</p>
                                <p v-if="v.proxima_dose" class="text-[11px] text-gray-400">
                                    Próxima: {{ new Date(v.proxima_dose).toLocaleDateString('pt-BR') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal nova consulta -->
    <div v-if="showNovaConsulta" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Nova consulta</h2>
                <button @click="showNovaConsulta = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Tipo</label>
                <select v-model="novaConsultaForm.tipo_consulta" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                    <option value="primeira_consulta">Primeira consulta</option>
                    <option value="retorno">Retorno</option>
                    <option value="emergencia">Emergência</option>
                </select>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="showNovaConsulta = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button @click="abrirConsulta" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600">Abrir consulta</button>
            </div>
        </div>
    </div>
</template>
