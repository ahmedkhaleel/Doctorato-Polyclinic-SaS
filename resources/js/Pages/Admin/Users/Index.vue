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
        router.post(`/admin/users/${id}/delete`);
    }
}

function toggleActive(user) {
    router.post(`/admin/users/${user.id}/update`, {
        name: user.name,
        email: user.email,
        role_id: user.role_id,
        is_active: !user.is_active,
        _method: 'PUT',
    }, { preserveState: true });
}

const roleColorMap = {
    1: { bg: 'bg-red-50', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
    2: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    3: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    4: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    5: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
    6: { bg: 'bg-slate-50', text: 'text-[#1B365D]', border: 'border-slate-200', dot: 'bg-[#1B365D]' },
    7: { bg: 'bg-amber-50', text: 'text-[#C4A265]', border: 'border-amber-200', dot: 'bg-[#C4A265]' },
};

function getRoleColors(roleId) {
    return roleColorMap[((roleId - 1) % 7) + 1] || { bg: 'bg-gray-50', text: 'text-gray-700', border: 'border-gray-200', dot: 'bg-gray-500' };
}

const avatarGradients = [
    'from-[#C4A265] to-[#A08245]',
    'from-[#1B365D] to-[#1B365D]',
    'from-emerald-500 to-emerald-600',
    'from-[#1B365D] to-[#1B365D]',
    'from-[#C4A265] to-[#C4A265]',
    'from-[#1B365D] to-[#1B365D]',
    'from-amber-500 to-amber-600',
];

function getAvatarGradient(id) {
    return avatarGradients[(id - 1) % avatarGradients.length];
}

/* ── Credentials Popup ────────────────────────────── */
const showCredentials = ref(false);
const credentials = ref(null);
const copiedField = ref('');

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);

    // Check for flashed credentials
    const flash = page.props.flash;
    if (flash?.credentials) {
        credentials.value = flash.credentials;
        showCredentials.value = true;
    }
});

function copyToClipboard(text, field) {
    navigator.clipboard.writeText(text).then(() => {
        copiedField.value = field;
        setTimeout(() => { copiedField.value = ''; }, 2000);
    });
}

function copyAllCredentials() {
    if (!credentials.value) return;
    const lines = [];
    lines.push(`Login URL: ${credentials.value.login_url}`);
    if (credentials.value.username) lines.push(`Username: ${credentials.value.username}`);
    lines.push(`Email: ${credentials.value.email}`);
    if (credentials.value.password) lines.push(`Password: ${credentials.value.password}`);
    copyToClipboard(lines.join('\n'), 'all');
}
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
                    <div class="absolute bottom-0 ltr:left-0 rtl:right-0 w-48 h-48 bg-[#1B365D] opacity-5 rounded-full blur-2xl floating-orb-delayed"></div>
                </div>

                <div class="relative z-10 px-8 py-8">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="header-info">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#A08245] flex items-center justify-center shadow-lg shadow-[#C4A265]/30">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <div>
                                    <h1 class="text-xl md:text-2xl font-bold text-white tracking-tight">{{ $t('a_users') }}</h1>
                                    <p class="text-gray-400 text-sm">{{ isRtl ? 'إدارة المستخدمين والصلاحيات' : 'Manage users & permissions' }}</p>
                                </div>
                            </div>
                        </div>

                        <Link
                            v-if="can('users.create')"
                            href="/admin/users/create"
                            class="group inline-flex items-center gap-2 px-4 md:px-6 py-3 rounded-xl text-gray-900 text-sm font-semibold transition-all duration-300 bg-gradient-to-r from-[#C4A265] to-[#D4B275] hover:from-[#D4B275] hover:to-[#E4C285] shadow-lg shadow-[#C4A265]/20 hover:shadow-[#C4A265]/40 hover:scale-105"
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
                                    <p class="text-xl md:text-2xl font-bold text-white">{{ totalUsers }}</p>
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
                                    <p class="text-xl md:text-2xl font-bold text-emerald-400">{{ activeUsers }}</p>
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
                                    <p class="text-xl md:text-2xl font-bold text-red-400">{{ inactiveUsers }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ isRtl ? 'غير نشط' : 'Inactive' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 hover:bg-white/15 transition-all duration-300" style="--delay: 240ms">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-[#1B365D]/20 flex items-center justify-center">
                                    <svg class="w-4.5 h-4.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                </div>
                                <div>
                                    <p class="text-xl md:text-2xl font-bold text-slate-400">{{ rolesCount }}</p>
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
                        class="doctorato-input w-full ltr:pl-11 rtl:pr-11 ltr:pr-4 rtl:pl-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all shadow-sm"
                    />
                </div>
                <select
                    v-model="selectedRole"
                    class="doctorato-input px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-all shadow-sm min-w-[180px]"
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
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_name') }}</th>
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_email') }}</th>
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_role') }}</th>
                                <th class="px-4 md:px-6 py-4 ltr:text-left rtl:text-right text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_actions') }}</th>
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
                                <td class="px-4 md:px-6 py-4">
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
                                <td class="px-4 md:px-6 py-4 hidden sm:table-cell">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        <span class="text-sm text-gray-500">{{ user.email }}</span>
                                    </div>
                                </td>

                                <!-- Role Badge -->
                                <td class="px-4 md:px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold rounded-lg border"
                                        :class="[getRoleColors(user.role_id).bg, getRoleColors(user.role_id).text, getRoleColors(user.role_id).border]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getRoleColors(user.role_id).dot"></span>
                                        {{ isRtl ? (user.role?.display_name_ar || user.role?.display_name_en || 'No Role') : (user.role?.display_name_en || 'No Role') }}
                                    </span>
                                </td>

                                <!-- Status Toggle -->
                                <td class="px-4 md:px-6 py-4">
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
                                <td class="px-4 md:px-6 py-4">
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

        <!-- ============ CREDENTIALS POPUP ============ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCredentials && credentials" class="fixed inset-0 z-[9999] flex items-center justify-center p-4" @click.self="showCredentials = false">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div v-if="showCredentials" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden" @click.stop>
                            <!-- Header -->
                            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 px-4 md:px-6 py-5 text-white relative overflow-hidden">
                                <div class="absolute inset-0 opacity-10">
                                    <svg class="w-full h-full" viewBox="0 0 400 200"><defs><pattern id="credDots" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse"><circle cx="15" cy="15" r="1.5" fill="white" /></pattern></defs><rect fill="url(#credDots)" width="400" height="200" /></svg>
                                </div>
                                <div class="relative flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold">{{ credentials.password ? (isRtl ? 'بيانات الدخول' : 'Login Credentials') : (isRtl ? 'بيانات المستخدم المحدثة' : 'Updated User Info') }}</h3>
                                        <p class="text-sm text-white/80">{{ credentials.name }}</p>
                                    </div>
                                </div>
                                <button @click="showCredentials = false" class="absolute top-4 end-4 w-8 h-8 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <!-- Credential Fields -->
                            <div class="p-4 md:p-6 space-y-3">
                                <!-- Login URL -->
                                <div class="group flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer" @click="copyToClipboard(credentials.login_url, 'url')">
                                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'رابط الدخول' : 'Login URL' }}</p>
                                        <p class="text-sm font-mono text-gray-800 truncate" dir="ltr">{{ credentials.login_url }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="copiedField === 'url'" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ isRtl ? 'تم النسخ' : 'Copied!' }}</span>
                                        <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-[#C4A265] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>

                                <!-- Username -->
                                <div v-if="credentials.username" class="group flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer" @click="copyToClipboard(credentials.username, 'username')">
                                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'اسم المستخدم' : 'Username' }}</p>
                                        <p class="text-sm font-semibold text-gray-800" dir="ltr">{{ credentials.username }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="copiedField === 'username'" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ isRtl ? 'تم النسخ' : 'Copied!' }}</span>
                                        <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-[#C4A265] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="group flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer" @click="copyToClipboard(credentials.email, 'email')">
                                    <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</p>
                                        <p class="text-sm font-semibold text-gray-800" dir="ltr">{{ credentials.email }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="copiedField === 'email'" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ isRtl ? 'تم النسخ' : 'Copied!' }}</span>
                                        <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-[#C4A265] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div v-if="credentials.password" class="group flex items-center gap-3 p-3 rounded-xl bg-red-50/50 hover:bg-red-50 border border-red-100 transition-colors cursor-pointer" @click="copyToClipboard(credentials.password, 'password')">
                                    <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-semibold text-red-400 uppercase tracking-wider">{{ isRtl ? 'كلمة المرور' : 'Password' }}</p>
                                        <p class="text-sm font-bold text-red-700 font-mono" dir="ltr">{{ credentials.password }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span v-if="copiedField === 'password'" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ isRtl ? 'تم النسخ' : 'Copied!' }}</span>
                                        <svg v-else class="w-4 h-4 text-gray-400 group-hover:text-red-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    </div>
                                </div>

                                <!-- Warning -->
                                <div v-if="credentials.password" class="flex items-start gap-2 p-3 rounded-xl bg-amber-50 border border-amber-100">
                                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                    <p class="text-[11px] text-amber-700 leading-relaxed">
                                        {{ isRtl ? 'احفظ هذه البيانات في مكان آمن. لن تتمكن من رؤية كلمة المرور مرة أخرى.' : 'Save these credentials securely. You won\'t be able to see the password again.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-4 md:px-6 pb-5 flex gap-3">
                                <button @click="copyAllCredentials"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 hover:shadow-lg"
                                    style="background: linear-gradient(135deg, #C4A265, #a8884f);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    {{ copiedField === 'all' ? (isRtl ? 'تم نسخ الكل!' : 'All Copied!') : (isRtl ? 'نسخ الكل' : 'Copy All') }}
                                </button>
                                <button @click="showCredentials = false"
                                    class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                                    {{ isRtl ? 'إغلاق' : 'Close' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

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
