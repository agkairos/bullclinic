<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

defineProps<{
    placeholder?: string;
    minHeight?: string; // e.g. '80px', '120px'
}>();

const model = defineModel<string>({ default: '' });

const toolbar = [
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    ['clean'],
];
</script>

<template>
    <div class="rich-text-wrapper rounded-lg border border-gray-200 bg-white text-sm focus-within:border-primary-500"
        :style="{ '--min-height': minHeight ?? '80px' }">
        <QuillEditor
            v-model:content="model"
            content-type="html"
            theme="snow"
            :toolbar="toolbar"
            :placeholder="placeholder ?? ''"
        />
    </div>
</template>

<style>
.rich-text-wrapper .ql-toolbar {
    border: none;
    border-bottom: 1px solid #f3f4f6;
    padding: 4px 8px;
}
.rich-text-wrapper .ql-container {
    border: none;
    font-size: 0.875rem;
    font-family: inherit;
}
.rich-text-wrapper .ql-editor {
    min-height: var(--min-height);
    padding: 8px 12px;
    line-height: 1.5;
}
.rich-text-wrapper .ql-editor.ql-blank::before {
    color: #9ca3af;
    font-style: normal;
}
.rich-text-wrapper .ql-toolbar .ql-formats button {
    width: 24px;
    height: 24px;
}
</style>
