import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user);
    const role = computed(() => user.value?.role);
    const permissions = computed(() => user.value?.permissions || []);

    function can(permission) {
        if (permissions.value.includes('*')) return true;
        return permissions.value.includes(permission);
    }

    function canAny(permissionList) {
        return permissionList.some(p => can(p));
    }

    function hasRole(roleName) {
        return role.value === roleName;
    }

    function isSuperAdmin() {
        return role.value === 'super_admin';
    }

    return { user, role, permissions, can, canAny, hasRole, isSuperAdmin };
}
