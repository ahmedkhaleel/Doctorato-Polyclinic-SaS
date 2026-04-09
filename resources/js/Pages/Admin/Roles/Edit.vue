<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    role: Object,
    availablePermissions: Object,
    permissionGroups: Object,
});

const isSystem = props.role.is_system;

const form = useForm({
    display_name_en: props.role.display_name_en,
    display_name_ar: props.role.display_name_ar,
    permissions: [...(props.role.permissions || [])],
    _method: 'PUT',
});

// Track which groups are expanded
const expandedGroups = ref(Object.keys(props.permissionGroups || {}));

function toggleGroup(groupKey) {
    const idx = expandedGroups.value.indexOf(groupKey);
    if (idx > -1) expandedGroups.value.splice(idx, 1);
    else expandedGroups.value.push(groupKey);
}

// Action labels bilingual
const actionLabels = {
    view: { en: 'View', ar: 'عرض' },
    create: { en: 'Create', ar: 'إنشاء' },
    update: { en: 'Update', ar: 'تعديل' },
    delete: { en: 'Delete', ar: 'حذف' },
    export: { en: 'Export', ar: 'تصدير' },
    edit_services: { en: 'Edit Services', ar: 'تعديل الخدمات' },
    assign: { en: 'Assign', ar: 'تعيين' },
    convert: { en: 'Convert', ar: 'تحويل' },
    approve: { en: 'Approve', ar: 'موافقة' },
    cancel: { en: 'Cancel', ar: 'إلغاء' },
    process_payment: { en: 'Process Pay', ar: 'معالجة دفع' },
    view_sensitive_medical: { en: 'View Sensitive', ar: 'عرض حساس' },
    update_sensitive_medical: { en: 'Update Sensitive', ar: 'تعديل حساس' },
};

function getActionLabel(action) {
    const label = actionLabels[action];
    if (!label) return action;
    return isRtl.value ? label.ar : label.en;
}

// Group modules by their group key
const groupedModules = computed(() => {
    const groups = {};
    for (const [module, config] of Object.entries(props.availablePermissions)) {
        const groupKey = config.group || 'other';
        if (!groups[groupKey]) groups[groupKey] = [];
        groups[groupKey].push({ key: module, ...config });
    }
    return groups;
});

// Get unique actions for a specific group
function getGroupActions(groupKey) {
    const actions = new Set();
    (groupedModules.value[groupKey] || []).forEach(m => m.actions.forEach(a => actions.add(a)));
    return Array.from(actions);
}

// Permission helpers
function isChecked(module, action) {
    if (isSystem) return true;
    return form.permissions.includes(`${module}.${action}`);
}

function togglePermission(module, action) {
    if (isSystem) return;
    const perm = `${module}.${action}`;
    const idx = form.permissions.indexOf(perm);
    if (idx > -1) form.permissions.splice(idx, 1);
    else form.permissions.push(perm);
}

function toggleModuleAll(module, actions) {
    if (isSystem) return;
    const allChecked = actions.every(a => form.permissions.includes(`${module}.${a}`));
    actions.forEach(a => {
        const perm = `${module}.${a}`;
        const idx = form.permissions.indexOf(perm);
        if (allChecked) { if (idx > -1) form.permissions.splice(idx, 1); }
        else { if (idx === -1) form.permissions.push(perm); }
    });
}

function isModuleAllChecked(module, actions) {
    if (isSystem) return true;
    return actions.every(a => form.permissions.includes(`${module}.${a}`));
}

// Group-level toggle
function toggleGroupAll(groupKey) {
    if (isSystem) return;
    const modules = groupedModules.value[groupKey] || [];
    const allPerms = [];
    modules.forEach(m => m.actions.forEach(a => allPerms.push(`${m.key}.${a}`)));
    const allChecked = allPerms.every(p => form.permissions.includes(p));
    allPerms.forEach(p => {
        const idx = form.permissions.indexOf(p);
        if (allChecked) { if (idx > -1) form.permissions.splice(idx, 1); }
        else { if (idx === -1) form.permissions.push(p); }
    });
}

function isGroupAllChecked(groupKey) {
    if (isSystem) return true;
    const modules = groupedModules.value[groupKey] || [];
    return modules.every(m => m.actions.every(a => form.permissions.includes(`${m.key}.${a}`)));
}

function groupPermCount(groupKey) {
    if (isSystem) return groupTotalPerms(groupKey);
    const modules = groupedModules.value[groupKey] || [];
    let count = 0;
    modules.forEach(m => m.actions.forEach(a => {
        if (form.permissions.includes(`${m.key}.${a}`)) count++;
    }));
    return count;
}

function groupTotalPerms(groupKey) {
    const modules = groupedModules.value[groupKey] || [];
    let count = 0;
    modules.forEach(m => count += m.actions.length);
    return count;
}

// Select all / deselect all
function toggleAll() {
    if (isSystem) return;
    const allPerms = [];
    Object.entries(props.availablePermissions).forEach(([module, config]) => {
        config.actions.forEach(action => allPerms.push(`${module}.${action}`));
    });
    form.permissions = form.permissions.length === allPerms.length ? [] : [...allPerms];
}

const isAllChecked = computed(() => {
    if (isSystem) return true;
    let total = 0;
    Object.values(props.availablePermissions).forEach(c => total += c.actions.length);
    return form.permissions.length === total;
});

const totalPerms = computed(() => {
    let t = 0;
    Object.values(props.availablePermissions).forEach(c => t += c.actions.length);
    return t;
});

function submit() {
    form.post(`/admin/roles/${props.role.id}`);
}
</script>

<template>
    <AdminLayout :title="isSystem ? $t('a_view_role') : $t('a_edit_role')">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ isSystem ? $t('a_view_role') : $t('a_edit_role') }}</h1>
                    <p class="text-sm text-gray-400 mt-1">{{ isRtl ? (isSystem ? 'عرض تفاصيل الدور والصلاحيات' : 'تعديل الدور وصلاحياته') : (isSystem ? 'View role details and permissions' : 'Edit role and its permissions') }}</p>
                </div>
                <Link href="/admin/roles" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ $t('a_back') }}
                </Link>
            </div>

            <!-- System role notice -->
            <div v-if="isSystem" class="bg-amber-50 border border-amber-200/80 rounded-2xl px-5 py-4 mb-6 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-800">{{ $t('a_system_role_notice') }}</p>
                    <p class="text-xs text-amber-600 mt-0.5">{{ isRtl ? 'لا يمكن تعديل أو حذف أدوار النظام' : 'System roles cannot be modified or deleted' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Basic Info Card -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ $t('a_role_information') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_display_name_en') }}</label>
                                <input v-model="form.display_name_en" type="text" :disabled="isSystem"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none transition"
                                    :class="{ 'bg-gray-100 text-gray-500 cursor-not-allowed': isSystem }" />
                                <p v-if="form.errors.display_name_en" class="mt-1 text-xs text-red-500">{{ form.errors.display_name_en }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_display_name_ar') }}</label>
                                <input v-model="form.display_name_ar" type="text" dir="rtl" :disabled="isSystem"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50/50 hover:border-gray-300 focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] outline-none transition"
                                    :class="{ 'bg-gray-100 text-gray-500 cursor-not-allowed': isSystem }" />
                                <p v-if="form.errors.display_name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.display_name_ar }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">{{ $t('a_slug') }}</label>
                                <input :value="role.name" type="text" disabled
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono bg-gray-100/70 text-gray-500 cursor-not-allowed" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permission Summary Bar -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm px-6 py-4 mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-white text-sm" style="background: linear-gradient(135deg, #C4A265, #a8884f);">
                                {{ isSystem ? totalPerms : form.permissions.length }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-700">{{ isRtl ? 'صلاحية محددة' : 'Selected' }}</p>
                                <p class="text-xs text-gray-400">{{ isRtl ? `من ${totalPerms}` : `of ${totalPerms}` }}</p>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="hidden sm:block w-48 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 ease-out" style="background: linear-gradient(90deg, #C4A265, #a8884f);"
                                :style="{ width: isSystem ? '100%' : `${totalPerms ? (form.permissions.length / totalPerms * 100) : 0}%` }"></div>
                        </div>
                    </div>
                    <button v-if="!isSystem" type="button" @click="toggleAll"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200"
                        :class="isAllChecked ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'">
                        {{ isAllChecked ? ($t('a_deselect_all')) : ($t('a_select_all')) }}
                    </button>
                    <span v-else class="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-50 text-amber-600">
                        {{ isRtl ? 'جميع الصلاحيات' : 'All Permissions' }}
                    </span>
                </div>

                <p v-if="form.errors.permissions" class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3">{{ form.errors.permissions }}</p>

                <!-- Permission Groups -->
                <div class="space-y-4 mb-6">
                    <div v-for="(groupConfig, groupKey) in permissionGroups" :key="groupKey"
                        class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden transition-all duration-300">

                        <!-- Group Header -->
                        <div class="flex items-center justify-between px-5 py-3.5 cursor-pointer select-none hover:bg-gray-50/50 transition-colors"
                            @click="toggleGroup(groupKey)">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-transform duration-300"
                                    :style="{ backgroundColor: groupConfig.color + '15', color: groupConfig.color }"
                                    :class="{ 'scale-110': expandedGroups.includes(groupKey) }">
                                    <svg v-if="groupConfig.icon === 'hospital'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'banknotes'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'users'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'funnel'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'tooth'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'shield'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'box'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                    <svg v-else-if="groupConfig.icon === 'globe'" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? groupConfig.label_ar : groupConfig.label_en }}</h3>
                                    <p class="text-xs text-gray-400">
                                        {{ groupPermCount(groupKey) }} / {{ groupTotalPerms(groupKey) }}
                                        {{ isRtl ? 'صلاحية' : 'permissions' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Group progress mini bar -->
                                <div class="hidden sm:block w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" :style="{ width: `${groupTotalPerms(groupKey) ? (groupPermCount(groupKey) / groupTotalPerms(groupKey) * 100) : 0}%`, backgroundColor: groupConfig.color }"></div>
                                </div>
                                <!-- Group toggle all -->
                                <button v-if="!isSystem" type="button" @click.stop="toggleGroupAll(groupKey)"
                                    class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all duration-200"
                                    :class="isGroupAllChecked(groupKey) ? 'bg-red-50 text-red-500 hover:bg-red-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                    {{ isGroupAllChecked(groupKey) ? (isRtl ? 'إلغاء الكل' : 'None') : (isRtl ? 'تحديد الكل' : 'All') }}
                                </button>
                                <!-- Chevron -->
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                    :class="{ 'rotate-180': expandedGroups.includes(groupKey) }"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <!-- Group Content - Animated -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[2000px] opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-[2000px] opacity-100"
                            leave-to-class="max-h-0 opacity-0">
                            <div v-show="expandedGroups.includes(groupKey)" class="overflow-hidden">
                                <div class="border-t border-gray-100">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-gray-50/80">
                                                <th class="ltr:text-left rtl:text-right px-5 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الوحدة' : 'Module' }}</th>
                                                <th class="px-2 py-2.5 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider w-14">{{ isRtl ? 'الكل' : 'All' }}</th>
                                                <th v-for="action in getGroupActions(groupKey)" :key="action"
                                                    class="px-2 py-2.5 text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider w-16">
                                                    {{ getActionLabel(action) }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="mod in groupedModules[groupKey]" :key="mod.key"
                                                class="border-t border-gray-50 hover:bg-blue-50/30 transition-colors duration-150">
                                                <td class="px-5 py-3">
                                                    <p class="text-sm font-medium text-gray-700">{{ isRtl ? mod.label_ar : mod.label_en }}</p>
                                                    <p class="text-[10px] text-gray-400" :dir="isRtl ? 'ltr' : 'rtl'">{{ isRtl ? mod.label_en : mod.label_ar }}</p>
                                                </td>
                                                <td class="px-2 py-3 text-center">
                                                    <button type="button" @click="toggleModuleAll(mod.key, mod.actions)"
                                                        :disabled="isSystem"
                                                        class="w-6 h-6 rounded-md flex items-center justify-center transition-all duration-200"
                                                        :class="[
                                                            isModuleAllChecked(mod.key, mod.actions) ? 'text-white' : 'bg-gray-100 text-gray-400 hover:bg-gray-200',
                                                            isSystem ? 'opacity-60 cursor-not-allowed' : ''
                                                        ]"
                                                        :style="isModuleAllChecked(mod.key, mod.actions) ? { backgroundColor: groupConfig.color } : {}">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                    </button>
                                                </td>
                                                <td v-for="action in getGroupActions(groupKey)" :key="action" class="px-2 py-3 text-center">
                                                    <template v-if="mod.actions.includes(action)">
                                                        <label class="relative inline-flex" :class="isSystem ? 'cursor-not-allowed' : 'cursor-pointer'">
                                                            <input type="checkbox" :checked="isChecked(mod.key, action)" @change="togglePermission(mod.key, action)" :disabled="isSystem" class="sr-only peer" />
                                                            <div class="w-5 h-5 rounded-md border-2 transition-all duration-200 flex items-center justify-center
                                                                peer-checked:border-transparent peer-checked:shadow-sm"
                                                                :class="[
                                                                    isChecked(mod.key, action) ? '' : 'border-gray-300 hover:border-gray-400 bg-white',
                                                                    isSystem ? 'opacity-60' : ''
                                                                ]"
                                                                :style="isChecked(mod.key, action) ? { backgroundColor: groupConfig.color, borderColor: groupConfig.color } : {}">
                                                                <svg v-if="isChecked(mod.key, action)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                            </div>
                                                        </label>
                                                    </template>
                                                    <span v-else class="text-gray-200">-</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>

                <!-- Submit Bar -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm px-6 py-4 flex items-center justify-between sticky bottom-4">
                    <p class="text-xs text-gray-400">
                        {{ isSystem ? totalPerms : form.permissions.length }} {{ isRtl ? 'صلاحية محددة' : 'permissions selected' }}
                    </p>
                    <div class="flex items-center gap-3">
                        <Link href="/admin/roles" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                            {{ isSystem ? $t('a_back') : $t('a_cancel') }}
                        </Link>
                        <button v-if="!isSystem" type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-all duration-200 disabled:opacity-50 shadow-sm hover:shadow-md flex items-center gap-2"
                            style="background: linear-gradient(135deg, #C4A265, #a8884f);">
                            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            {{ form.processing ? $t('a_saving') : $t('a_update_role') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
