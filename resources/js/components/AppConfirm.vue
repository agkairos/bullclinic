<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm';

const { state, respond } = useConfirm();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="state.visible" class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/30 p-4">
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="state.visible" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                        <div v-if="state.title" class="mb-2 text-base font-semibold text-gray-900">
                            {{ state.title }}
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ state.message }}</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button
                                @click="respond(false)"
                                class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                            >
                                {{ state.cancelLabel }}
                            </button>
                            <button
                                @click="respond(true)"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                                :class="state.danger ? 'bg-red-500 hover:bg-red-600' : 'bg-primary-500 hover:bg-primary-600'"
                            >
                                {{ state.confirmLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
