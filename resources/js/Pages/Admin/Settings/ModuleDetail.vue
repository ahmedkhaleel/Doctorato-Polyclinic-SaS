<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { currencyCode } = useCurrency();

const props = defineProps({
    module: Object,
});

// Settings come already grouped from backend: { general: [...], pricing: [...], ... }
const settingsByGroup = computed(() => {
    const raw = props.module?.settings || {};
    // If it's already grouped (object with arrays), use directly
    if (!Array.isArray(raw) && typeof raw === 'object') {
        // Filter out 'enabled' from general group (handled by toggle on main page)
        const filtered = {};
        for (const [group, settings] of Object.entries(raw)) {
            const items = (Array.isArray(settings) ? settings : []).filter(s => s.key !== 'enabled');
            if (items.length > 0) {
                filtered[group] = items;
            }
        }
        return filtered;
    }
    // Fallback: if it's a flat array, group it
    const groups = {};
    (Array.isArray(raw) ? raw : []).forEach(setting => {
        if (setting.key === 'enabled') return;
        const group = setting.group || 'general';
        if (!groups[group]) groups[group] = [];
        groups[group].push(setting);
    });
    return groups;
});

const groupLabels = {
    general: { en: 'General', ar: 'عام' },
    pricing: { en: 'Pricing', ar: 'التسعير' },
    commission: { en: 'Commission', ar: 'العمولة' },
    duration: { en: 'Duration & Scheduling', ar: 'المدة والجدولة' },
    notifications: { en: 'Notifications', ar: 'الإشعارات' },
    features: { en: 'Features', ar: 'الميزات' },
};

const groupIcons = {
    general: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    pricing: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    commission: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    duration: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    notifications: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    features: 'M13 10V3L4 14h7v7l9-11h-7z',
};

// Build form data from all settings
const formData = ref({});

function initFormData() {
    const data = {};
    const raw = props.module?.settings || {};
    if (!Array.isArray(raw) && typeof raw === 'object') {
        for (const settings of Object.values(raw)) {
            (Array.isArray(settings) ? settings : []).forEach(s => {
                data[s.key] = s.value;
            });
        }
    } else if (Array.isArray(raw)) {
        raw.forEach(s => {
            data[s.key] = s.value;
        });
    }
    formData.value = data;
}
initFormData();

const savingGroup = ref(null);
const savingAll = ref(false);

function saveGroup(groupKey) {
    savingGroup.value = groupKey;
    const groupSettings = settingsByGroup.value[groupKey] || [];
    const payload = {};
    groupSettings.forEach(s => {
        payload[s.key] = formData.value[s.key] ?? s.value;
    });

    router.post(`/admin/settings/modules/${props.module.slug}`, {
        settings: payload,
    }, {
        preserveScroll: true,
        onFinish: () => {
            savingGroup.value = null;
        },
    });
}

function saveAll() {
    savingAll.value = true;
    router.post(`/admin/settings/modules/${props.module.slug}`, {
        settings: formData.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            savingAll.value = false;
        },
    });
}

function getInputType(setting) {
    const type = setting.type || 'text';
    if (type === 'boolean' || type === 'toggle') return 'toggle';
    if (type === 'number' || type === 'price' || type === 'percentage' || type === 'minutes') return 'number';
    return 'text';
}

function getSuffix(setting) {
    const type = setting.type || '';
    if (type === 'percentage') return '%';
    if (type === 'minutes') return isRtl.value ? 'دقيقة' : 'min';
    if (type === 'price') return currencyCode.value;
    return '';
}
</script>

<template>
    <AdminLayout :title="isRtl ? module.name_ar : module.name_en">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-xl flex items-center justify-center"
                        :style="{ backgroundColor: (module.color || '#6B7280') + '20' }"
                    >
                        <svg
                            class="w-7 h-7"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            :style="{ color: module.color || '#6B7280' }"
                        >
                            <path v-if="module.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="module.icon" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ isRtl ? module.name_ar : module.name_en }}
                        </h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                :class="module.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ module.enabled ? (isRtl ? 'مفعّل' : 'Enabled') : (isRtl ? 'معطّل' : 'Disabled') }}
                            </span>
                            <span v-if="module.is_core" class="text-xs text-gray-400">({{ isRtl ? 'أساسي' : 'Core' }})</span>
                        </div>
                    </div>
                </div>
                <Link href="/admin/settings/modules" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'العودة للمديولات' : 'Back to Modules' }}
                </Link>
            </div>

            <!-- Empty State (no configurable settings) -->
            <div v-if="Object.keys(settingsByGroup).length === 0" class="bg-white rounded-2xl shadow-sm border p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <h3 class="text-lg font-semibold text-gray-700">{{ isRtl ? 'لا توجد إعدادات قابلة للتعديل' : 'No configurable settings' }}</h3>
                <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'هذا المديول يعمل بالإعدادات الافتراضية' : 'This module works with default settings' }}</p>
            </div>

            <!-- Settings Groups -->
            <div v-for="(settings, groupKey) in settingsByGroup" :key="groupKey" class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="groupIcons[groupKey] || groupIcons.general" />
                        </svg>
                        <h2 class="text-sm font-semibold text-gray-700">
                            {{ groupLabels[groupKey]?.[locale] || groupKey }}
                        </h2>
                        <span class="text-xs text-gray-400">({{ settings.length }})</span>
                    </div>
                    <button
                        @click="saveGroup(groupKey)"
                        :disabled="savingGroup === groupKey"
                        class="px-4 py-1.5 text-xs font-semibold text-white rounded-lg transition disabled:opacity-50 hover:opacity-90"
                        :style="{ backgroundColor: module.color || '#0891b2' }"
                    >
                        <span v-if="savingGroup === groupKey" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            {{ isRtl ? 'حفظ...' : 'Saving...' }}
                        </span>
                        <span v-else>{{ isRtl ? 'حفظ' : 'Save' }}</span>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div v-for="setting in settings" :key="setting.key" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ isRtl ? (setting.label_ar || setting.label || setting.key) : (setting.label_en || setting.label || setting.key) }}
                            </label>
                            <p v-if="setting.hint || setting.hint_ar || setting.hint_en" class="text-xs text-gray-400 mt-0.5">
                                {{ isRtl ? (setting.hint_ar || setting.hint || '') : (setting.hint_en || setting.hint || '') }}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <!-- Toggle / Boolean -->
                            <div v-if="getInputType(setting) === 'toggle'" class="flex items-center">
                                <button
                                    type="button"
                                    @click="formData[setting.key] = formData[setting.key] === '1' ? '0' : '1'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                    :class="formData[setting.key] === '1' ? 'bg-cyan-600' : 'bg-gray-200'"
                                    :style="formData[setting.key] === '1' && module.color ? { backgroundColor: module.color } : {}"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="formData[setting.key] === '1' ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                                    ></span>
                                </button>
                                <span class="ltr:ml-3 rtl:mr-3 text-sm text-gray-600">
                                    {{ formData[setting.key] === '1' ? (isRtl ? 'نعم' : 'Yes') : (isRtl ? 'لا' : 'No') }}
                                </span>
                            </div>

                            <!-- Number Input -->
                            <div v-else-if="getInputType(setting) === 'number'" class="relative max-w-xs">
                                <input
                                    v-model="formData[setting.key]"
                                    type="number"
                                    :min="setting.min || 0"
                                    :max="setting.max || undefined"
                                    :step="setting.type === 'price' ? '0.01' : '1'"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent transition"
                                    :class="{ 'pe-14': getSuffix(setting) }"
                                    :style="{ '--tw-ring-color': (module.color || '#0891b2') + '40' }"
                                />
                                <span v-if="getSuffix(setting)" class="absolute end-4 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">
                                    {{ getSuffix(setting) }}
                                </span>
                            </div>

                            <!-- Text Input -->
                            <div v-else class="max-w-md">
                                <input
                                    v-model="formData[setting.key]"
                                    type="text"
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:border-transparent transition"
                                    :style="{ '--tw-ring-color': (module.color || '#0891b2') + '40' }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Save -->
            <div v-if="Object.keys(settingsByGroup).length > 0" class="flex items-center justify-end gap-3">
                <Link href="/admin/settings/modules" class="px-5 py-2.5 rounded-xl text-gray-600 font-medium text-sm border border-gray-200 hover:bg-gray-50 transition">
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <button
                    @click="saveAll"
                    :disabled="savingAll"
                    class="px-6 py-2.5 rounded-xl text-white font-semibold text-sm transition disabled:opacity-50 hover:opacity-90 shadow-sm"
                    :style="{ backgroundColor: module.color || '#0891b2' }"
                >
                    <span v-if="savingAll" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        {{ isRtl ? 'جاري الحفظ...' : 'Saving...' }}
                    </span>
                    <span v-else>{{ isRtl ? 'حفظ جميع الإعدادات' : 'Save All Settings' }}</span>
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
