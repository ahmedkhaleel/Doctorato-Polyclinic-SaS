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
    users: Object,
    roles: Array,
});

function deleteUser(id) {
    if (window.confirm(t('a_confirm_delete_user'))) {
        router.delete(`/admin/users/${id}`);
    }
}

function toggleActive(user) {
    router.put(`/admin/users/${user.id}`, {
        name: user.name,
        email: user.email,
        role_id: user.role_id,
        is_active: !user.is_active,
        _method: 'PUT',
    }, { preserveState: true });
}

const roleColors = [
    'bg-red-100 text-red-800',
    'bg-blue-100 text-blue-800',
    'bg-green-100 text-green-800',
    'bg-purple-100 text-purple-800',
    'bg-yellow-100 text-yellow-800',
    'bg-indigo-100 text-indigo-800',
    'bg-pink-100 text-pink-800',
];

function roleBadgeClass(roleId) {
    return roleColors[(roleId - 1) % roleColors.length] || 'bg-gray-100 text-gray-800';
}
</script>

<template>
    <AdminLayout :title="$t('a_users')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_users') }}</h1>
                <Link
                    v-if="can('users.create')"
                    href="/admin/users/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_user') }}
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 ltr:text-left rtl:text-right">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_email') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_role') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">{{ $t('a_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold text-white" style="background-color: #C4A265;">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ user.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full"
                                    :class="roleBadgeClass(user.role_id)"
                                >
                                    {{ user.role?.display_name_en || 'No Role' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    v-if="can('users.update')"
                                    @click="toggleActive(user)"
                                    class="inline-flex items-center space-x-1.5 rtl:space-x-reverse"
                                >
                                    <span
                                        class="w-2.5 h-2.5 rounded-full"
                                        :class="user.is_active ? 'bg-green-500' : 'bg-red-500'"
                                    ></span>
                                    <span class="text-xs" :class="user.is_active ? 'text-green-700' : 'text-red-700'">
                                        {{ user.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </button>
                                <div v-else class="inline-flex items-center space-x-1.5 rtl:space-x-reverse">
                                    <span
                                        class="w-2.5 h-2.5 rounded-full"
                                        :class="user.is_active ? 'bg-green-500' : 'bg-red-500'"
                                    ></span>
                                    <span class="text-xs" :class="user.is_active ? 'text-green-700' : 'text-red-700'">
                                        {{ user.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 rtl:space-x-reverse">
                                <Link
                                    v-if="can('users.update')"
                                    :href="`/admin/users/${user.id}/edit`"
                                    class="text-sm font-medium hover:underline"
                                    style="color: #C4A265;"
                                >
                                    {{ $t('a_edit') }}
                                </Link>
                                <button
                                    v-if="can('users.delete')"
                                    @click="deleteUser(user.id)"
                                    class="text-sm font-medium text-red-600 hover:underline"
                                >
                                    {{ $t('a_delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!users.data || users.data.length === 0" class="p-8 text-center text-sm text-gray-500">
                    No users found.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="users.links && users.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ users.from }} {{ $t('a_to') }} {{ users.to }} {{ $t('a_of') }} {{ users.total }} {{ $t('a_results') }}</p>
                <nav class="flex space-x-1 rtl:space-x-reverse">
                    <template v-for="link in users.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 text-sm rounded border transition"
                            :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                            :style="link.active ? 'background-color: #C4A265;' : ''"
                            preserve-state
                        />
                        <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                    </template>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>
