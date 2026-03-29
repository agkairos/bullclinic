<script setup lang="ts">
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { toasts, remove, success, error } = useToast();
const page = usePage<any>();

// Consumir flash do Inertia automaticamente
watch(
    () => page.props.flash,
    (flash: any) => {
        if (flash?.success) success(flash.success);
        if (flash?.error) error(flash.error);
    },
    { deep: true, immediate: true }
);

function iconFor(type: string) {
    if (type === 'success') return '✓';
    if (type === 'error') return '✕';
    if (type === 'warning') return '⚠';
    return 'ℹ';
}

function colorFor(type: string) {
    return {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-orange-400',
        info: 'bg-blue-500',
    }[type] ?? 'bg-gray-700';
}
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed bottom-5 right-5 z-[9999] flex flex-col gap-2">
            <TransitionGroup
                enter-active-class="transition-all duration-300"
                enter-from-class="translate-x-full opacity-0"
                leave-active-class="transition-all duration-300"
                leave-to-class="translate-x-full opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-white shadow-lg"
                    :class="colorFor(toast.type)"
                    style="min-width: 260px; max-width: 380px"
                >
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold">
                        {{ iconFor(toast.type) }}
                    </span>
                    <span class="flex-1 leading-snug">{{ toast.message }}</span>
                    <button @click="remove(toast.id)" class="ml-1 shrink-0 opacity-60 hover:opacity-100">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
