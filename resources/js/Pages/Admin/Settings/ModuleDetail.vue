<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
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

// Animation
const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

// Accordion state - first group open by default
const openGroups = ref(new Set());
onMounted(() => {
    const groups = Object.keys(settingsByGroup.value);
    if (groups.length > 0) openGroups.value.add(groups[0]);
});

function toggleGroup(key) {
    if (openGroups.value.has(key)) {
        openGroups.value.delete(key);
    } else {
        openGroups.value.add(key);
    }
}

// Settings come already grouped from backend
const settingsByGroup = computed(() => {
    const raw = props.module?.settings || {};
    if (!Array.isArray(raw) && typeof raw === 'object') {
        const filtered = {};
        for (const [group, settings] of Object.entries(raw)) {
            const items = (Array.isArray(settings) ? settings : []).filter(s => s.key !== 'enabled');
            if (items.length > 0) {
                filtered[group] = items;
            }
        }
        return filtered;
    }
    const groups = {};
    (Array.isArray(raw) ? raw : []).forEach(setting => {
        if (setting.key === 'enabled') return;
        const group = setting.group || 'general';
        if (!groups[group]) groups[group] = [];
        groups[group].push(setting);
    });
    return groups;
});

const totalSettings = computed(() => {
    return Object.values(settingsByGroup.value).reduce((sum, arr) => sum + arr.length, 0);
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

const groupDescriptions = {
    general: { en: 'Basic module configuration and display settings', ar: 'إعدادات العرض والتكوين الأساسية للمديول' },
    pricing: { en: 'Configure consultation and service fees', ar: 'تكوين رسوم الاستشارة والخدمات' },
    commission: { en: 'Doctor commission rates and percentages', ar: 'نسب ومعدلات عمولة الأطباء' },
    duration: { en: 'Appointment and session duration settings', ar: 'إعدادات مدة المواعيد والجلسات' },
    notifications: { en: 'Alert and notification preferences', ar: 'تفضيلات التنبيهات والإشعارات' },
    features: { en: 'Enable or disable module features', ar: 'تفعيل أو تعطيل ميزات المديول' },
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
const savedGroup = ref(null);
const savedAll = ref(false);

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
            savedGroup.value = groupKey;
            setTimeout(() => { savedGroup.value = null; }, 2000);
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
            savedAll.value = true;
            setTimeout(() => { savedAll.value = false; }, 2000);
        },
    });
}

function getInputType(setting) {
    const type = setting.type || 'text';
    if (type === 'boolean' || type === 'toggle') return 'toggle';
    if (type === 'number' || type === 'price' || type === 'percentage' || type === 'minutes') return 'number';
    if (type === 'color') return 'color';
    return 'text';
}

function getSuffix(setting) {
    const type = setting.type || '';
    if (type === 'percentage') return '%';
    if (type === 'minutes') return isRtl.value ? 'دقيقة' : 'min';
    if (type === 'price') return currencyCode.value;
    return '';
}

const moduleColor = computed(() => props.module?.color || '#C4A265');
</script>

<template>
    <AdminLayout :title="isRtl ? module.name_ar : module.name_en">
        <div class="module-detail-page" :class="{ 'is-mounted': mounted }">
            <!-- Hero Header -->
            <div class="module-hero" :style="{ '--module-color': moduleColor }">
                <div class="hero-bg-pattern"></div>
                <div class="hero-content">
                    <div class="hero-left">
                        <Link href="/admin/settings/modules" class="hero-back-link">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7'" />
                            </svg>
                            {{ isRtl ? 'العودة للمديولات' : 'Back to Modules' }}
                        </Link>
                        <div class="hero-title-row">
                            <div class="hero-icon-wrapper">
                                <svg class="hero-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="module.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="module.icon" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="hero-title">{{ isRtl ? module.name_ar : module.name_en }}</h1>
                                <div class="hero-badges">
                                    <span class="status-badge" :class="module.enabled ? 'status-active' : 'status-inactive'">
                                        <span class="status-dot"></span>
                                        {{ module.enabled ? (isRtl ? 'مفعّل' : 'Enabled') : (isRtl ? 'معطّل' : 'Disabled') }}
                                    </span>
                                    <span v-if="module.is_core" class="core-badge">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        {{ isRtl ? 'أساسي' : 'Core' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-value">{{ Object.keys(settingsByGroup).length }}</span>
                            <span class="hero-stat-label">{{ isRtl ? 'مجموعات' : 'Groups' }}</span>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">{{ totalSettings }}</span>
                            <span class="hero-stat-label">{{ isRtl ? 'إعداد' : 'Settings' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="Object.keys(settingsByGroup).length === 0" class="empty-state">
                <div class="empty-icon-wrapper">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="empty-title">{{ isRtl ? 'لا توجد إعدادات قابلة للتعديل' : 'No configurable settings' }}</h3>
                <p class="empty-desc">{{ isRtl ? 'هذا المديول يعمل بالإعدادات الافتراضية' : 'This module works with default settings' }}</p>
            </div>

            <!-- Settings Groups -->
            <div class="groups-container">
                <div
                    v-for="(settings, groupKey, groupIndex) in settingsByGroup"
                    :key="groupKey"
                    class="settings-group"
                    :class="{ 'is-open': openGroups.has(groupKey) }"
                    :style="{ '--delay': (groupIndex * 0.08) + 's' }"
                >
                    <!-- Group Header (Clickable) -->
                    <button class="group-header" @click="toggleGroup(groupKey)" type="button">
                        <div class="group-header-left">
                            <div class="group-icon-wrapper" :style="{ '--module-color': moduleColor }">
                                <svg class="group-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="groupIcons[groupKey] || groupIcons.general" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="group-title">{{ groupLabels[groupKey]?.[locale] || groupKey }}</h2>
                                <p class="group-desc">{{ groupDescriptions[groupKey]?.[locale] || '' }}</p>
                            </div>
                        </div>
                        <div class="group-header-right">
                            <span class="group-count">{{ settings.length }}</span>
                            <!-- Save Group Button -->
                            <button
                                type="button"
                                @click.stop="saveGroup(groupKey)"
                                :disabled="savingGroup === groupKey"
                                class="group-save-btn"
                                :style="{ '--module-color': moduleColor }"
                                v-if="openGroups.has(groupKey)"
                            >
                                <svg v-if="savingGroup === groupKey" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                                <svg v-else-if="savedGroup === groupKey" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span v-else>{{ isRtl ? 'حفظ' : 'Save' }}</span>
                            </button>
                            <svg class="chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Group Body (Animated) -->
                    <Transition name="accordion">
                        <div v-if="openGroups.has(groupKey)" class="group-body">
                            <div class="settings-list">
                                <div
                                    v-for="(setting, settingIndex) in settings"
                                    :key="setting.key"
                                    class="setting-row"
                                    :style="{ '--row-delay': (settingIndex * 0.04) + 's' }"
                                >
                                    <div class="setting-info">
                                        <label class="setting-label">
                                            {{ isRtl ? (setting.label_ar || setting.label || setting.key) : (setting.label_en || setting.label || setting.key) }}
                                        </label>
                                        <p v-if="setting.hint || setting.hint_ar || setting.hint_en" class="setting-hint">
                                            {{ isRtl ? (setting.hint_ar || setting.hint || '') : (setting.hint_en || setting.hint || '') }}
                                        </p>
                                    </div>
                                    <div class="setting-input">
                                        <!-- Toggle -->
                                        <div v-if="getInputType(setting) === 'toggle'" class="toggle-wrapper">
                                            <button
                                                type="button"
                                                @click="formData[setting.key] = formData[setting.key] === '1' ? '0' : '1'"
                                                class="toggle-btn"
                                                :class="{ active: formData[setting.key] === '1' }"
                                                :style="formData[setting.key] === '1' ? { '--module-color': moduleColor } : {}"
                                            >
                                                <span class="toggle-thumb"></span>
                                            </button>
                                            <span class="toggle-label" :class="{ 'is-active': formData[setting.key] === '1' }">
                                                {{ formData[setting.key] === '1' ? (isRtl ? 'مفعّل' : 'Enabled') : (isRtl ? 'معطّل' : 'Disabled') }}
                                            </span>
                                        </div>

                                        <!-- Number Input -->
                                        <div v-else-if="getInputType(setting) === 'number'" class="number-input-wrapper">
                                            <input
                                                v-model="formData[setting.key]"
                                                type="number"
                                                :min="setting.min || 0"
                                                :max="setting.max || undefined"
                                                :step="setting.type === 'price' ? '0.01' : '1'"
                                                class="doctorato-input number-input"
                                                :style="{ '--module-color': moduleColor }"
                                            />
                                            <span v-if="getSuffix(setting)" class="input-suffix">{{ getSuffix(setting) }}</span>
                                        </div>

                                        <!-- Color Input -->
                                        <div v-else-if="getInputType(setting) === 'color'" class="color-input-wrapper">
                                            <div class="color-preview" :style="{ backgroundColor: formData[setting.key] || '#000' }"></div>
                                            <input
                                                v-model="formData[setting.key]"
                                                type="text"
                                                class="doctorato-input text-input"
                                                :style="{ '--module-color': moduleColor }"
                                            />
                                        </div>

                                        <!-- Text Input -->
                                        <div v-else class="text-input-wrapper">
                                            <input
                                                v-model="formData[setting.key]"
                                                type="text"
                                                class="doctorato-input text-input"
                                                :style="{ '--module-color': moduleColor }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Global Actions -->
            <div v-if="Object.keys(settingsByGroup).length > 0" class="global-actions">
                <Link href="/admin/settings/modules" class="cancel-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                </Link>
                <button
                    @click="saveAll"
                    :disabled="savingAll"
                    class="save-all-btn"
                    :class="{ 'is-saved': savedAll }"
                    :style="{ '--module-color': moduleColor }"
                >
                    <svg v-if="savingAll" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    <svg v-else-if="savedAll" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ savingAll ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : savedAll ? (isRtl ? 'تم الحفظ!' : 'Saved!') : (isRtl ? 'حفظ جميع الإعدادات' : 'Save All Settings') }}
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Base & Animation ── */
.module-detail-page {
    max-width: 900px;
    margin: 0 auto;
}

.module-detail-page > * {
    opacity: 0;
    transform: translateY(16px);
    transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.module-detail-page.is-mounted > * {
    opacity: 1;
    transform: translateY(0);
}

.module-detail-page.is-mounted > *:nth-child(2) { transition-delay: 0.08s; }
.module-detail-page.is-mounted > *:nth-child(3) { transition-delay: 0.16s; }

/* ── Hero Header ── */
.module-hero {
    position: relative;
    border-radius: 24px;
    padding: 32px;
    margin-bottom: 28px;
    background: linear-gradient(135deg, var(--module-color), color-mix(in srgb, var(--module-color) 70%, #000));
    overflow: hidden;
    box-shadow: 0 8px 32px color-mix(in srgb, var(--module-color) 25%, transparent);
}

.hero-bg-pattern {
    position: absolute;
    inset: 0;
    opacity: 0.08;
    background-image: radial-gradient(circle at 20% 50%, white 1px, transparent 1px),
                       radial-gradient(circle at 80% 20%, white 1px, transparent 1px),
                       radial-gradient(circle at 60% 80%, white 1.5px, transparent 1.5px);
    background-size: 40px 40px, 60px 60px, 80px 80px;
}

.hero-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
}

.hero-left {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.hero-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.2s;
    width: fit-content;
}

.hero-back-link:hover {
    color: white;
}

.hero-title-row {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.hero-icon {
    width: 28px;
    height: 28px;
    color: white;
}

.hero-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: white;
    line-height: 1.2;
}

.hero-badges {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: rgba(255, 255, 255, 0.2);
    color: #d1fae5;
}

.status-inactive {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.5);
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.core-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.6875rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.8);
}

.hero-stats {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.hero-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.hero-stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.hero-stat-label {
    font-size: 0.6875rem;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hero-stat-divider {
    width: 1px;
    height: 32px;
    background: rgba(255, 255, 255, 0.15);
}

/* ── Empty State ── */
.empty-state {
    text-align: center;
    padding: 60px 24px;
    background: white;
    border-radius: 20px;
    border: 2px dashed #e5e7eb;
}

.empty-icon-wrapper {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.empty-icon {
    width: 36px;
    height: 36px;
    color: #d1d5db;
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #374151;
}

.empty-desc {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-top: 4px;
}

/* ── Groups Container ── */
.groups-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

/* ── Settings Group ── */
.settings-group {
    background: white;
    border-radius: 20px;
    border: 1.5px solid #f0f1f3;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    animation: groupSlideIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: var(--delay);
}

@keyframes groupSlideIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.settings-group:hover {
    border-color: #e5e7eb;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.settings-group.is-open {
    border-color: color-mix(in srgb, var(--module-color, #C4A265) 20%, #e5e7eb);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

/* ── Group Header ── */
.group-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}

.group-header:hover {
    background: #fafbfc;
}

.group-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.group-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--module-color) 10%, white);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s;
}

.is-open .group-icon-wrapper {
    background: color-mix(in srgb, var(--module-color) 15%, white);
    transform: scale(1.05);
}

.group-icon {
    width: 20px;
    height: 20px;
    color: var(--module-color);
    transition: transform 0.3s;
}

.group-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1f2937;
    text-align: start;
}

.group-desc {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 2px;
    text-align: start;
}

.group-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.group-count {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: #6b7280;
}

.group-save-btn {
    padding: 6px 14px;
    border-radius: 8px;
    background: var(--module-color);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 52px;
    justify-content: center;
}

.group-save-btn:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.group-save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.chevron-icon {
    width: 20px;
    height: 20px;
    color: #9ca3af;
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    flex-shrink: 0;
}

.is-open .chevron-icon {
    transform: rotate(180deg);
}

/* ── Accordion Transition ── */
.accordion-enter-active {
    transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    overflow: hidden;
}

.accordion-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}

.accordion-enter-from {
    opacity: 0;
    max-height: 0;
}

.accordion-enter-to {
    opacity: 1;
    max-height: 800px;
}

.accordion-leave-from {
    opacity: 1;
    max-height: 800px;
}

.accordion-leave-to {
    opacity: 0;
    max-height: 0;
}

/* ── Group Body ── */
.group-body {
    border-top: 1px solid #f3f4f6;
}

.settings-list {
    padding: 8px 0;
}

/* ── Setting Row ── */
.setting-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 16px 24px;
    transition: background 0.15s;
    animation: rowFadeIn 0.3s ease both;
    animation-delay: var(--row-delay);
}

@keyframes rowFadeIn {
    from { opacity: 0; transform: translateX(8px); }
    to { opacity: 1; transform: translateX(0); }
}

.setting-row:hover {
    background: #fafbfc;
}

.setting-row + .setting-row {
    border-top: 1px solid #f8f9fa;
}

.setting-info {
    flex: 1;
    min-width: 0;
}

.setting-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.setting-hint {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 2px;
}

.setting-input {
    flex-shrink: 0;
    min-width: 200px;
}

/* ── Toggle ── */
.toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: flex-end;
}

.toggle-btn {
    position: relative;
    width: 48px;
    height: 26px;
    border-radius: 13px;
    background: #d1d5db;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
    flex-shrink: 0;
}

.toggle-btn.active {
    background: var(--module-color, #C4A265);
}

.toggle-thumb {
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: white;
    top: 2px;
    inset-inline-start: 2px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    transition: inset-inline-start 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

.toggle-btn.active .toggle-thumb {
    inset-inline-start: 24px;
}

.toggle-label {
    font-size: 0.8125rem;
    color: #9ca3af;
    font-weight: 500;
    min-width: 52px;
}

.toggle-label.is-active {
    color: #374151;
}

/* ── Number Input ── */
.number-input-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
}

.number-input {
    width: 120px;
    padding: 9px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    text-align: center;
    background: white;
    transition: all 0.2s;
}

.number-input:focus {
    outline: none;
    border-color: var(--module-color, #C4A265);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--module-color, #C4A265) 12%, transparent);
}

.input-suffix {
    font-size: 0.75rem;
    font-weight: 600;
    color: #9ca3af;
    min-width: 32px;
}

/* ── Text Input ── */
.text-input-wrapper,
.color-input-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.text-input {
    width: 100%;
    max-width: 280px;
    padding: 9px 14px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.875rem;
    color: #374151;
    background: white;
    transition: all 0.2s;
    margin-inline-start: auto;
}

.text-input:focus {
    outline: none;
    border-color: var(--module-color, #C4A265);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--module-color, #C4A265) 12%, transparent);
}

.color-preview {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    flex-shrink: 0;
}

/* ── Global Actions ── */
.global-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 8px;
}

.cancel-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 24px;
    border-radius: 14px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    background: white;
    border: 1.5px solid #e5e7eb;
    text-decoration: none;
    transition: all 0.2s;
}

.cancel-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.save-all-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: 14px;
    font-size: 0.9375rem;
    font-weight: 700;
    color: white;
    background: linear-gradient(135deg, var(--module-color), color-mix(in srgb, var(--module-color) 80%, #000));
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    box-shadow: 0 4px 16px color-mix(in srgb, var(--module-color) 30%, transparent);
}

.save-all-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--module-color) 40%, transparent);
}

.save-all-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.save-all-btn.is-saved {
    background: linear-gradient(135deg, #10b981, #059669);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

/* ── Utilities ── */
.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .hero-stats {
        align-self: stretch;
        justify-content: center;
    }

    .setting-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .setting-input {
        width: 100%;
        min-width: 0;
    }

    .text-input {
        max-width: 100%;
        margin-inline-start: 0;
    }

    .number-input-wrapper,
    .toggle-wrapper {
        justify-content: flex-start;
    }

    .global-actions {
        flex-direction: column;
    }

    .cancel-btn,
    .save-all-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
