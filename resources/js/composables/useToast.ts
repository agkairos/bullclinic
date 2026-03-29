import { ref } from 'vue';

export type Toast = {
    id: string;
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
};

const toasts = ref<Toast[]>([]);

export function useToast() {
    function add(message: string, type: Toast['type'] = 'success', duration = 4000) {
        const id = Math.random().toString(36).slice(2);
        toasts.value.push({ id, type, message });
        setTimeout(() => remove(id), duration);
    }

    function remove(id: string) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return {
        toasts,
        success: (msg: string) => add(msg, 'success'),
        error: (msg: string) => add(msg, 'error', 6000),
        info: (msg: string) => add(msg, 'info'),
        warning: (msg: string) => add(msg, 'warning'),
        remove,
    };
}
