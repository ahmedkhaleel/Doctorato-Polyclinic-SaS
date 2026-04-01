<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    roles: Array,
});

function deleteRole(id) {
    if (window.confirm(t('a_confirm_delete_role'))) {
        router.delete(`/admin/roles/${id}`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_roles_permissions')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_roles_permissions') }}</h1>
                <Link
                    v-if="can('roles.create')"
                    href="/admin/roles/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_role') }}
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 ltr:text-left rtl:text-right">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_role_name') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_arabic_name') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_users') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_permissions') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_type') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">{{ $t('a_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span class="font-medium text-gray-900">{{ role.display_name_en }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600" dir="rtl">{{ role.display_name_ar }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ role.users_count }} user{{ role.users_count !== 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="role.permissions.includes('*')" class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                    {{ $t('a_all_permissions') }}
                                </span>
                                <span v-else class="text-xs text-gray-500">
                                    {{ role.permissions.length }} permission{{ role.permissions.length !== 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    v-if="role.is_system"
                                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800"
                                >
                                    System
                                </span>
                                <span
                                    v-else
                                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800"
                                >
                                    Custom
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 rtl:space-x-reverse">
                                <Link
                                    v-if="can('roles.update')"
                                    :href="`/admin/roles/${role.id}/edit`"
                                    class="text-sm font-medium hover:underline"
                                    style="color: #C4A265;"
                                >
                                    {{ role.is_system ? $t('a_view') : $t('a_edit') }}
                                </Link>
                                <button
                                    v-if="can('roles.delete') && !role.is_system"
                                    @click="deleteRole(role.id)"
                                    class="text-sm font-medium text-red-600 hover:underline"
                                >
                                    {{ $t('a_delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!roles || roles.length === 0" class="p-8 text-center text-sm text-gray-500">
                    No roles found.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
