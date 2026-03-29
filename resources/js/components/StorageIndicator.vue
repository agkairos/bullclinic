<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage<any>();
const clinica = computed(() => page.props.auth?.user?.clinica_ativa);
const plano = computed(() => clinica.value?.plano);

const usadoGb = computed(() => parseFloat(clinica.value?.limite_storage_usado_gb ?? 0));
const limiteGb = computed(() => parseFloat(plano.value?.limite_storage_gb ?? 5));
const percentual = computed(() => limiteGb.value > 0 ? Math.min(100, Math.round((usadoGb.value / limiteGb.value) * 100)) : 0);

const barColor = computed(() => {
    if (percentual.value >= 90) return 'bg-red-400';
    if (percentual.value >= 70) return 'bg-orange-400';
    return 'bg-primary-400';
});
</script>

<template>
    <div v-if="clinica" class="px-3 py-2">
        <div class="mb-1 flex items-center justify-between">
            <span class="text-[10px] text-gray-400">Storage</span>
            <Link href="/documentos" class="text-[10px] text-primary-500 hover:underline">{{ percentual }}%</Link>
        </div>
        <div class="h-1 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full transition-all" :class="barColor" :style="`width:${percentual}%`"></div>
        </div>
        <p v-if="percentual >= 90" class="mt-1 text-[10px] text-red-400">Espaço quase esgotado</p>
    </div>
</template>
