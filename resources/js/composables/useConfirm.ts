import { ref } from 'vue';

type ConfirmOptions = {
    title?: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    danger?: boolean;
};

type ConfirmState = ConfirmOptions & {
    visible: boolean;
    resolve: ((v: boolean) => void) | null;
};

const state = ref<ConfirmState>({
    visible: false,
    message: '',
    title: '',
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    danger: false,
    resolve: null,
});

export function useConfirm() {
    function confirm(opts: ConfirmOptions | string): Promise<boolean> {
        const options: ConfirmOptions = typeof opts === 'string' ? { message: opts } : opts;

        return new Promise((resolve) => {
            state.value = {
                visible: true,
                message: options.message,
                title: options.title ?? '',
                confirmLabel: options.confirmLabel ?? 'Confirmar',
                cancelLabel: options.cancelLabel ?? 'Cancelar',
                danger: options.danger ?? false,
                resolve,
            };
        });
    }

    function respond(value: boolean) {
        state.value.resolve?.(value);
        state.value.visible = false;
    }

    return { confirm, respond, state };
}
