<script setup>
import { ref, computed, onMounted } from 'vue';
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

const searchQuery = ref('');
const selectedRole = ref('');
const mounted = ref(false);

const filteredUsers = computed(() => {
    let list = props.users?.data || [];
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(u => u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q));
    }
    if (selectedRole.value) {
        list = list.filter(u => u.role_id == selectedRole.value);
    }
    return list;
});

const totalUsers = computed(() => props.users?.total || props.users?.data?.length || 0);
const activeUsers = computed(() => (props.users?.data || []).filter(u => u.is_active).length);
const inactiveUsers = computed(() => (props.users?.data || []).filter(u => !u.is_active).length);
const rolesCount = computed(() => {
    const set = new Set((props.users?.data || []).map(u => u.role_id));
    return set.size;
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

const roleColorMap = {
    1: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
    2: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
    3: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    4: { bg: 'bg-purple-50', text: 'text-purple-700', border: 'border-purple-200', dot: 'bg-purple-500' },
    5: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
    6: { bg: 'bg-indigo-50', text: 'text-indigo-700', border: 'border-indigo-200', dot: 'bg-indigo-500' },
    7: { bg: 'bg-pink-50', text: 'text-pink-700', border: 'border-pink-200', dot: 'bg-pink-500' },
};

function getRoleColors(roleId) {
    return roleColorMap[((roleId - 1) % 7) + 1] || { bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500' };
}

const avatarGradients = [
    'from-[#C4A265] to-[#A08245]',
    'from-blue-500 to-blue-600',
    'from-emerald-500 to-emerald-600',
    'from-purple-500 to-purple-600',
    'from-rose-500 to-rose-600',
    'from-indigo-500 to-indigo-600',
    'from-amber-500 to-amber-600',
];

function getAvatarGradient(id) {
    return avatarGradients[(id - 1) % avatarGradients.length];
}

onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });
</script>

<template>
    <AdminLayout :title="$t('a_users')">
        <div class="users-page space-y-6">

            <!-- ======================== HEADER ======================== -->
            <div class="relative overflow-hidden rounded-2xl shadow-xl header-animate">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
                    <div class="absolute inset-0 opacity-20">
                        <div class="hero-dots"></div>
                    </div>
                    <div class="absolute top-0 ltr:right-0 rtl:left-0 w-80 h-80 bg-[#C4A265] opacity-10 rounded-full blur-3xl floating-orb"></div>
                    <div class="absolute bottom-0 ltr:left-0 rtl:right-0 w-48 h-48 bg-blue-500 opacity-5 rounded-full blur-2xl floating-orb-delayed"></div>
                </div>

                <div class="relative z-10 px-8 py-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="header-info">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#A08245] flex items-center justify-center shadow-lg shadow-[#C4A265]/30">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ $t('a_users') }}</h1>
                                    <p class="text-gray-400 text-sm">{{ isRtl ? 'إدارة المستخدمين والصلاحيات' : 'Manage users & permissions' }}</p>
                                </div>
                            </div>
                        </div>

                        <Link
                            v-if="can('users.create')"
                            href="/admin/users/create"
                            class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl text-gray-900 text-sm font-semibold transition-all duration-300 bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:from-[#D4B275] hover:to-[#E4C285] shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/40 hover:scale-105"
                        >
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-90 duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ $t('a_add_user') }}
                        </Link>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                        <div class="stat-card bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/15 transition-all duration-300" style="--delay: 0ms">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-[#C4A265]/20 flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-white">{{ totalUsers }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ isRtl ? 'إجمالي' : 'Total' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/15 transition-all duration-300" style="--delay: 80ms">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-emerald-400">{{ activeUsers }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ isRtl ? 'نشط' : 'Active' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/15 transition-all duration-300" style="--delay: 160ms">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-red-400">{{ inactiveUsers }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ isRtl ? 'غير نشط' : 'Inactive' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/15 transition-all duration-300" style="--delay: 240ms">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-purple-400">{{ rolesCount }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ isRtl ? 'أدوار' : 'Roles' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================== SEARCH & FILTER ======================== -->
            <div class="flex flex-col sm:flex-row gap-3 content-animate" style="--delay: 200ms">
                <div class="relative flex-1">
                    <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالاسم أو البريد...' : 'Search by name or email...'"
                        class="w-full ltr:pl-11 rtl:pr-11 ltr:pr-4 rtl:pl-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all shadow-sm"
                    />
                </div>
                <select
                    v-model="selectedRole"
                    class="px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all shadow-sm min-w-[180px]"
                >
                    <option value="">{{ isRtl ? 'كل الأدوار' : 'All Roles' }}</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">
                        {{ isRtl ? role.display_name_ar : role.display_name_en }}
                    </option>
                </select>
            </div>

            <!-- ======================== USERS TABLE ======================== -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden content-animate" style="--delay: 300ms">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                                <th class="px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_name') }}</th>
                                <th class="px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_email') }}</th>
                                <th class="px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_role') }}</th>
                                <th class="px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                <th class="px-6 py-4 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(user, index) in filteredUsers"
                                :key="user.id"
                                class="group hover:bg-[#C4A265]/[0.02] transition-all duration-300 table-row-animate"
                                :style="{ '--row-delay': (index * 50) + 'ms' }"
                            >
                                <!-- Name + Avatar -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <div
                                                :class="`w-10 h-10 rounded-xl bg-gradient-to-br ${getAvatarGradient(user.id)} flex items-center justify-center text-sm font-bold text-white shadow-sm group-hover:scale-110 transition-transform duration-300`"
                                            >
                                                {{ user.name?.charAt(0)?.toUpperCase() }}
                                            </div>
                                            <div
                                                class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                                :class="user.is_active ? 'bg-emerald-500' : 'bg-gray-300'"
                                            ></div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 group-hover:text-[#C4A265] transition-colors">{{ user.name }}</p>
                                            <p class="text-xs text-gray-400 sm:hidden">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 hidden sm:table-cell">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        <span class="text-sm text-gray-500">{{ user.email }}</span>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold rounded-lg border"
                                        :class="[getRoleColors(user.role_id).bg, getRoleColors(user.role_id).text, getRoleColors(user.role_id).border]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getRoleColors(user.role_id).dot"></span>
                                        {{ isRtl ? (user.role?.display_name_ar || user.role?.display_name_en || 'No Role') : (user.role?.display_name_en || 'No Role') }}
                                    </span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-6 py-4">
                                    <button
                                        v-if="can('users.update')"
                                        @click="toggleActive(user)"
                                        class="relative inline-flex items-center h-7 w-[52px] rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        :class="user.is_active
                                            ? 'bg-emerald-500 focus:ring-emerald-500/30'
                                            : 'bg-gray-300 focus:ring-gray-300/30'"
                                    >
                                        <span
                                            class="inline-block w-5 h-5 bg-white rounded-full shadow-sm transition-all duration-300"
                                            :class="user.is_active ? 'ltr:translate-x-[26px] rtl:-translate-x-[26px]' : 'ltr:translate-x-[3px] rtl:-translate-x-[3px]'"
                                        ></span>
                                    </button>
                                    <div v-else class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        <span class="text-xs font-medium" :class="user.is_active ? 'text-emerald-600' : 'text-red-600'">
                                            {{ user.is_active ? $t('a_active') : $t('a_inactive') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                        <Link
                                            v-if="can('users.update')"
                                            :href="`/admin/users/${user.id}/edit`"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 transition-all duration-200"
                                            :title="$t('a_edit')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </Link>
                                        <button
                                            v-if="can('users.delete')"
                                            @click="deleteUser(user.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200"
                                            :title="$t('a_delete')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                    <!-- Always visible on mobile -->
                                    <div class="flex items-center justify-end gap-2 sm:hidden">
                                        <Link
                                            v-if="can('users.update')"
                                            :href="`/admin/users/${user.id}/edit`"
                                            class="text-xs font-semibold text-[#C4A265]"
                                        >{{ $t('a_edit') }}</Link>
                                        <button
                                            v-if="can('users.delete')"
                                            @click="deleteUser(user.id)"
                                            class="text-xs font-semibold text-red-500"
                                        >{{ $t('a_delete') }}</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center py-16">
                    <div class="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">{{ searchQuery ? (isRtl ? 'لا توجد نتائج' : 'No results found') : (isRtl ? 'لا يوجد مستخدمين' : 'No users found') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ searchQuery ? (isRtl ? 'جرب كلمة بحث مختلفة' : 'Try a different search') : '' }}</p>
                </div>
            </div>

            <!-- ======================== PAGINATION ======================== -->
            <div v-if="users.links && users.links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-4 content-animate" style="--delay: 400ms">
                <p class="text-sm text-gray-400">
                    {{ $t('a_showing') }}
                    <span class="font-semibold text-gray-700">{{ users.from }}</span>
                    {{ $t('a_to') }}
                    <span class="font-semibold text-gray-700">{{ users.to }}</span>
                    {{ $t('a_of') }}
                    <span class="font-semibold text-gray-700">{{ users.total }}</span>
                    {{ $t('a_results') }}
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in users.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-sm font-medium rounded-lg border transition-all duration-200"
                            :class="link.active
                                ? 'text-white border-transparent shadow-md shadow-[#C4A265]/20'
                                : 'text-gray-500 border-gray-200 hover:border-[#C4A265]/30 hover:text-[#C4A265] bg-white'"
                            :style="link.active ? 'background: linear-gradient(135deg, #C4A265, #D4B275);' : ''"
                            preserve-state
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="inline-flex items-center justify-center min-w-[36px] h-9 px-3 text-sm text-gray-300"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Header */
.header-animate {
    animation: slideUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(25px); }
    to { opacity: 1; transform: translateY(0); }
}

.header-info {
    animation: fadeSlide 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
}

@keyframes fadeSlide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Stats */
.stat-card {
    animation: statPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    animation-delay: calc(0.3s + var(--delay, 0ms));
}

@keyframes statPop {
    from { opacity: 0; transform: scale(0.8) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Content sections */
.content-animate {
    animation: contentFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: calc(0.4s + var(--delay, 0ms));
}

@keyframes contentFade {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Table rows */
.table-row-animate {
    animation: rowSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: calc(0.5s + var(--row-delay, 0ms));
}

@keyframes rowSlide {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}

[dir="rtl"] .table-row-animate {
    animation-name: rowSlideRtl;
}

@keyframes rowSlideRtl {
    from { opacity: 0; transform: translateX(-10px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Background effects */
.hero-dots {
    width: 100%;
    height: 100%;
    background-image: radial-gradient(circle at 1px 1px, rgba(196, 162, 101, 0.15) 1px, transparent 0);
    background-size: 40px 40px;
}

.floating-orb {
    animation: floatOrb 8s ease-in-out infinite;
}

.floating-orb-delayed {
    animation: floatOrb 10s ease-in-out 2s infinite;
}

@keyframes floatOrb {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(-15px, 10px) scale(1.1); }
    50% { transform: translate(8px, -8px) scale(0.95); }
    75% { transform: translate(-10px, -15px) scale(1.05); }
}
</style>
