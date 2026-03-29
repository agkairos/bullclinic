import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage<any>();

    const permissions = computed<string[]>(() => page.props.auth?.permissions ?? []);
    const isSuperAdmin = computed<boolean>(() => page.props.auth?.is_super_admin ?? false);

    function can(permission: string): boolean {
        if (isSuperAdmin.value) return true;
        return permissions.value.includes(permission);
    }

    function canAny(...perms: string[]): boolean {
        if (isSuperAdmin.value) return true;
        return perms.some(p => permissions.value.includes(p));
    }

    return { can, canAny, permissions, isSuperAdmin };
}
