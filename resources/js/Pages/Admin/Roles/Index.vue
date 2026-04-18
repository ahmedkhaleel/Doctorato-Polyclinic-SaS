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
    roles: Array,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const searchQuery = ref('');

const filteredRoles = computed(() => {
    if (!props.roles) return [];
    if (!searchQuery.value) return props.roles;
    const q = searchQuery.value.toLowerCase();
    return props.roles.filter(r =>
        r.display_name_en?.toLowerCase().includes(q) ||
        r.display_name_ar?.includes(q) ||
        r.name?.toLowerCase().includes(q)
    );
});

const systemRoles = computed(() => filteredRoles.value.filter(r => r.is_system));
const customRoles = computed(() => filteredRoles.value.filter(r => !r.is_system));

const totalUsers = computed(() => (props.roles || []).reduce((s, r) => s + (parseInt(r.users_count) || 0), 0));
const totalPerms = computed(() => {
    const custom = (props.roles || []).filter(r => !r.is_system);
    if (!custom.length) return 0;
    const all = new Set();
    custom.forEach(r => (r.permissions || []).forEach(p => all.add(p)));
    return all.size;
});

/* ── Role color mapping ──────────────────────────── */
const roleColors = {
    super_admin:     { bg: 'bg-[#1B365D]/10', text: 'text-[#1B365D]', border: 'border-slate-200', gradient: 'from-[#1B365D] to-[#1B365D]', dot: 'bg-[#1B365D]' },
    admin:           { bg: 'bg-[#1B365D]/10', text: 'text-[#1B365D]', border: 'border-slate-200', gradient: 'from-[#1B365D] to-[#1B365D]', dot: 'bg-[#1B365D]' },
    doctor:          { bg: 'bg-emerald-500/10', text: 'text-emerald-600', border: 'border-emerald-200', gradient: 'from-emerald-500 to-teal-600', dot: 'bg-emerald-500' },
    secretary:       { bg: 'bg-[#1B365D]/10', text: 'text-[#1B365D]', border: 'border-slate-200', gradient: 'from-[#1B365D] to-[#1B365D]', dot: 'bg-[#1B365D]' },
    receptionist:    { bg: 'bg-[#1B365D]/10', text: 'text-[#1B365D]', border: 'border-slate-200', gradient: 'from-[#1B365D] to-teal-500', dot: 'bg-[#1B365D]' },
    accountant:      { bg: 'bg-emerald-500/10', text: 'text-emerald-600', border: 'border-emerald-200', gradient: 'from-emerald-500 to-emerald-600', dot: 'bg-emerald-500' },
    marketing:       { bg: 'bg-amber-500/10', text: 'text-amber-600', border: 'border-amber-200', gradient: 'from-amber-500 to-[#C4A265]', dot: 'bg-amber-500' },
    webmaster:       { bg: 'bg-[#1B365D]/10', text: 'text-[#1B365D]', border: 'border-slate-200', gradient: 'from-[#1B365D] to-[#1B365D]', dot: 'bg-[#1B365D]' },
    admin_manager:   { bg: 'bg-[#C4A265]/10', text: 'text-[#C4A265]', border: 'border-amber-200', gradient: 'from-[#C4A265] to-[#C4A265]', dot: 'bg-[#C4A265]' },
};

const defaultColor = { bg: 'bg-gray-500/10', text: 'text-gray-600', border: 'border-gray-200', gradient: 'from-gray-500 to-gray-600', dot: 'bg-gray-500' };

function getRoleColor(role) {
    return roleColors[role.name] || defaultColor;
}

/* ── Role icons ──────────────────────────────────── */
const roleIcons = {
    super_admin: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    admin: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    doctor: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    secretary: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    receptionist: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    accountant: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    marketing: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    webmaster: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    admin_manager: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
};

function getRoleIcon(role) {
    return roleIcons[role.name] || 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z';
}

function getPermissionLabel(role) {
    if (role.permissions?.includes('*')) return isRtl.value ? 'كل الصلاحيات' : 'All Permissions';
    const count = role.permissions?.length || 0;
    return isRtl.value ? `${count} صلاحية` : `${count} permission${count !== 1 ? 's' : ''}`;
}

function getUsersLabel(count) {
    if (count === 0) return isRtl.value ? 'لا يوجد مستخدمين' : 'No users';
    return isRtl.value ? `${count} مستخدم` : `${count} user${count !== 1 ? 's' : ''}`;
}

const deletingId = ref(null);

function deleteRole(id) {
    if (window.confirm(t('a_confirm_delete_role'))) {
        deletingId.value = id;
        router.post(`/admin/roles/${id}/delete`, {
            onFinish: () => { deletingId.value = null; }
        });
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_roles_permissions')">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div
                class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_roles_permissions') }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">{{ isRtl ? 'إدارة الأدوار وصلاحيات المستخدمين' : 'Manage roles and user permissions' }}</p>
                    </div>
                </div>

                <Link
                    v-if="can('roles.create')"
                    href="/admin/roles/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 group"
                    style="background: linear-gradient(135deg, #C4A265, #a8884f);"
                >
                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_role') }}
                </Link>
            </div>

            <!-- ── Stats Bar ──────────────────────────────────── -->
            <div
                class="grid grid-cols-2 sm:grid-cols-4 gap-3 transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionDelay: '100ms' }"
            >
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#C4A265]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">{{ roles?.length || 0 }}</p>
                        <p class="text-[11px] text-gray-400 font-medium">{{ isRtl ? 'إجمالي الأدوار' : 'Total Roles' }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1B365D]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">{{ totalUsers }}</p>
                        <p class="text-[11px] text-gray-400 font-medium">{{ isRtl ? 'إجمالي المستخدمين' : 'Total Users' }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#1B365D]/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">{{ systemRoles.length + customRoles.length > 0 ? (roles || []).filter(r => r.is_system).length : 0 }}</p>
                        <p class="text-[11px] text-gray-400 font-medium">{{ isRtl ? 'أدوار النظام' : 'System Roles' }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                    </div>
                    <div>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">{{ (roles || []).filter(r => !r.is_system).length }}</p>
                        <p class="text-[11px] text-gray-400 font-medium">{{ isRtl ? 'أدوار مخصصة' : 'Custom Roles' }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Search ─────────────────────────────────────── -->
            <div
                class="transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionDelay: '180ms' }"
            >
                <div class="relative">
                    <svg class="absolute start-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="isRtl ? 'البحث عن دور...' : 'Search roles...'"
                        class="doctorato-input w-full ps-12 pe-4 py-3 bg-white border border-gray-200 rounded-2xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none transition-all duration-200 shadow-sm"
                    />
                </div>
            </div>

            <!-- ── System Roles Section ───────────────────────── -->
            <div v-if="systemRoles.length">
                <div
                    class="flex items-center gap-2 mb-3 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    :style="{ transitionDelay: '250ms' }"
                >
                    <div class="w-1.5 h-5 rounded-full bg-[#1B365D]"></div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'أدوار النظام' : 'System Roles' }}</h2>
                    <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-medium">{{ systemRoles.length }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="(role, index) in systemRoles"
                        :key="role.id"
                        class="group relative bg-white rounded-2xl border overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1"
                        :class="[getRoleColor(role).border, mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8']"
                        :style="{ transitionDelay: `${300 + index * 80}ms` }"
                    >
                        <!-- Top accent gradient -->
                        <div :class="`absolute top-0 inset-x-0 h-1 bg-gradient-to-r ${getRoleColor(role).gradient}`"></div>

                        <!-- Decorative circle -->
                        <div :class="`absolute -end-8 -top-8 w-28 h-28 rounded-full ${getRoleColor(role).bg} opacity-0 group-hover:opacity-60 transition-all duration-700 blur-2xl`"></div>

                        <div class="relative p-5">
                            <!-- Header row -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div :class="getRoleColor(role).bg" class="w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg">
                                        <svg :class="getRoleColor(role).text" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getRoleIcon(role)" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-[15px] font-bold text-gray-900">{{ isRtl ? role.display_name_ar : role.display_name_en }}</h3>
                                        <p class="text-[11px] text-gray-400 mt-0.5" :dir="isRtl ? 'ltr' : 'rtl'">{{ isRtl ? role.display_name_en : role.display_name_ar }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-50 text-[#1B365D] border border-slate-100">
                                    {{ isRtl ? 'نظام' : 'System' }}
                                </span>
                            </div>

                            <!-- Info chips -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 text-[11px] font-medium text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ getUsersLabel(role.users_count) }}
                                </div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-medium"
                                    :class="role.permissions?.includes('*') ? 'bg-slate-50 text-[#1B365D]' : 'bg-gray-50 text-gray-600'">
                                    <svg class="w-3.5 h-3.5" :class="role.permissions?.includes('*') ? 'text-slate-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                    {{ getPermissionLabel(role) }}
                                </div>
                            </div>

                            <!-- Slug + Action -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <code class="text-[10px] font-mono text-gray-400 bg-gray-50 px-2 py-0.5 rounded">{{ role.name }}</code>
                                <Link
                                    v-if="can('roles.update')"
                                    :href="`/admin/roles/${role.id}/edit`"
                                    class="inline-flex items-center gap-1 text-[12px] font-semibold transition-all duration-200 hover:gap-2"
                                    :class="getRoleColor(role).text"
                                >
                                    {{ $t('a_view') }}
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Custom Roles Section ───────────────────────── -->
            <div v-if="customRoles.length">
                <div
                    class="flex items-center gap-2 mb-3 transition-all duration-700"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    :style="{ transitionDelay: `${300 + systemRoles.length * 80 + 50}ms` }"
                >
                    <div class="w-1.5 h-5 rounded-full bg-[#C4A265]"></div>
                    <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ isRtl ? 'أدوار مخصصة' : 'Custom Roles' }}</h2>
                    <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full font-medium">{{ customRoles.length }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div
                        v-for="(role, index) in customRoles"
                        :key="role.id"
                        class="group relative bg-white rounded-2xl border overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1"
                        :class="[getRoleColor(role).border, mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8']"
                        :style="{ transitionDelay: `${300 + systemRoles.length * 80 + 120 + index * 80}ms` }"
                    >
                        <div :class="`absolute top-0 inset-x-0 h-1 bg-gradient-to-r ${getRoleColor(role).gradient}`"></div>
                        <div :class="`absolute -end-8 -top-8 w-28 h-28 rounded-full ${getRoleColor(role).bg} opacity-0 group-hover:opacity-60 transition-all duration-700 blur-2xl`"></div>

                        <div class="relative p-5">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div :class="getRoleColor(role).bg" class="w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:shadow-lg">
                                        <svg :class="getRoleColor(role).text" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="getRoleIcon(role)" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-[15px] font-bold text-gray-900">{{ isRtl ? role.display_name_ar : role.display_name_en }}</h3>
                                        <p class="text-[11px] text-gray-400 mt-0.5" :dir="isRtl ? 'ltr' : 'rtl'">{{ isRtl ? role.display_name_en : role.display_name_ar }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#C4A265]/10 text-[#C4A265] border border-[#C4A265]/20">
                                    {{ isRtl ? 'مخصص' : 'Custom' }}
                                </span>
                            </div>

                            <!-- Permission progress bar -->
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[11px] font-medium text-gray-500">{{ isRtl ? 'الصلاحيات' : 'Permissions' }}</span>
                                    <span class="text-[11px] font-bold" :class="getRoleColor(role).text">{{ role.permissions?.length || 0 }}</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-1000 ease-out"
                                        :class="`bg-gradient-to-r ${getRoleColor(role).gradient}`"
                                        :style="{ width: mounted ? `${Math.min((role.permissions?.length || 0) / 50 * 100, 100)}%` : '0%', transitionDelay: `${500 + index * 100}ms` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Info chips -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 text-[11px] font-medium text-gray-600">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    {{ getUsersLabel(role.users_count) }}
                                </div>
                            </div>

                            <!-- Slug + Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <code class="text-[10px] font-mono text-gray-400 bg-gray-50 px-2 py-0.5 rounded">{{ role.name }}</code>
                                <div class="flex items-center gap-3">
                                    <Link
                                        v-if="can('roles.update')"
                                        :href="`/admin/roles/${role.id}/edit`"
                                        class="inline-flex items-center gap-1 text-[12px] font-semibold transition-all duration-200 hover:gap-2"
                                        :class="getRoleColor(role).text"
                                    >
                                        {{ $t('a_edit') }}
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    </Link>
                                    <button
                                        v-if="can('roles.delete')"
                                        @click="deleteRole(role.id)"
                                        :disabled="deletingId === role.id"
                                        class="inline-flex items-center gap-1 text-[12px] font-semibold text-gray-400 hover:text-red-500 transition-all duration-200 disabled:opacity-50"
                                    >
                                        <svg v-if="deletingId === role.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Empty State ────────────────────────────────── -->
            <div
                v-if="filteredRoles.length === 0"
                class="bg-white rounded-2xl border border-gray-100 p-12 text-center transition-all duration-700"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">
                    {{ searchQuery ? (isRtl ? 'لا توجد نتائج' : 'No results found') : (isRtl ? 'لا توجد أدوار' : 'No roles found') }}
                </h3>
                <p class="text-sm text-gray-400">
                    {{ searchQuery ? (isRtl ? 'جرب البحث بكلمات أخرى' : 'Try a different search term') : (isRtl ? 'أنشئ دورًا جديدًا للبدء' : 'Create a new role to get started') }}
                </p>
            </div>

        </div>
    </AdminLayout>
</template>
