<script setup lang="ts">
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#f8f9fb] p-4">
        <!-- Logo -->
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 11.5A6.5 6.5 0 1011 18H4.5v-6.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900">BullClinic</span>
            </div>
            <div class="rounded-2xl bg-white p-8 shadow-sm border border-gray-100">
                <slot />
            </div>
        </div>
    </div>
</template>
