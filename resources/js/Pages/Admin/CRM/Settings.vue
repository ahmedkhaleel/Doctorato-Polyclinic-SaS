<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({
    settings: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

// Tabs
const activeTab = ref('general');
const tabs = [
    { key: 'general',       label: 'General',          labelAr: 'عام' },
    { key: 'sla',           label: 'SLA & Response',   labelAr: 'اتفاقية الخدمة' },
    { key: 'notifications', label: 'Notifications',    labelAr: 'الاشعارات' },
    { key: 'pipeline',      label: 'Pipeline',         labelAr: 'مراحل العمل' },
];

// Form
const form = useForm({
    auto_assign_enabled:          !!props.settings.auto_assign_enabled,
    auto_assign_method:           props.settings.auto_assign_method || 'round_robin',
    default_lead_priority:        props.settings.default_lead_priority ?? 3,
    default_lead_module:          props.settings.default_lead_module || 'derma',
    sla_response_target_minutes:  props.settings.sla_response_target_minutes ?? 60,
    sla_followup_target_hours:    props.settings.sla_followup_target_hours ?? 24,
    stale_lead_days:              props.settings.stale_lead_days ?? 7,
    notify_on_new_lead:           !!props.settings.notify_on_new_lead,
    notify_on_status_change:      !!props.settings.notify_on_status_change,
    notify_on_overdue_followup:   !!props.settings.notify_on_overdue_followup,
});

const showSuccess = ref(false);

function saveSettings() {
    form.post('/admin/crm-settings', {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            setTimeout(() => { showSuccess.value = false; }, 3000);
        },
    });
}

// Pipeline stages (display only)
const pipelineStages = computed(() => {
    return props.settings.pipeline_stages || [];
});

// Priority options
const priorities = [
    { value: 1, label: 'Hot',  labelAr: 'ساخن',  color: 'from-red-500 to-orange-500',  ring: 'ring-red-400' },
    { value: 2, label: 'Warm', labelAr: 'دافئ', color: 'from-amber-400 to-yellow-500', ring: 'ring-amber-400' },
    { value: 3, label: 'Cold', labelAr: 'بارد', color: 'from-blue-400 to-cyan-500',    ring: 'ring-blue-400' },
];

// SLA gauge percentage
const slaGaugePercent = computed(() => {
    const val = form.sla_response_target_minutes;
    // 0-240 min mapped to 0-100%
    return Math.min(100, Math.max(0, (val / 240) * 100));
});
</script>

<template>
    <AdminLayout :title="isRtl ? 'اعدادات CRM' : 'CRM Settings'">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Success Toast -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-y-[-1rem]"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-[-1rem]"
            >
                <div v-if="showSuccess" class="fixed top-6 inset-x-0 z-50 flex justify-center pointer-events-none">
                    <div class="bg-emerald-600 text-white px-6 py-3 rounded-xl shadow-2xl flex items-center gap-3 pointer-events-auto">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium text-sm">{{ isRtl ? 'تم حفظ الاعدادات بنجاح' : 'Settings saved successfully' }}</span>
                    </div>
                </div>
            </Transition>

            <!-- Header -->
            <div
                :class="[
                    'transition-all duration-700',
                    mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                ]"
            >
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#C4A265] to-[#A88B4A] rounded-xl flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ isRtl ? 'اعدادات CRM' : 'CRM Settings' }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'تكوين وادارة اعدادات نظام ادارة العملاء' : 'Configure and manage your CRM system preferences' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div
                :class="[
                    'transition-all duration-700 delay-100',
                    mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                ]"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-1.5 mb-6 flex gap-1 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        :class="[
                            'flex-1 min-w-0 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 whitespace-nowrap',
                            activeTab === tab.key
                                ? 'bg-gradient-to-r from-[#C4A265] to-[#B8944F] text-white shadow-md shadow-[#C4A265]/25'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                        ]"
                    >
                        {{ isRtl ? tab.labelAr : tab.label }}
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <form @submit.prevent="saveSettings">

                <!-- ==================== GENERAL TAB ==================== -->
                <div v-show="activeTab === 'general'">
                    <div class="space-y-6">

                        <!-- Auto-Assign -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-150',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                            <path d="M16 3.13a4 4 0 010 7.75"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'التعيين التلقائي' : 'Auto-Assign Leads' }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'توزيع العملاء المحتملين تلقائيا على الموظفين' : 'Automatically distribute new leads among team members' }}</p>
                                    </div>
                                </div>
                                <!-- Toggle Switch -->
                                <button
                                    type="button"
                                    @click="form.auto_assign_enabled = !form.auto_assign_enabled"
                                    :class="[
                                        'relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#C4A265] focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                        form.auto_assign_enabled ? 'bg-[#C4A265]' : 'bg-gray-300 dark:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition-transform duration-300 ease-in-out',
                                            form.auto_assign_enabled ? (isRtl ? '-translate-x-5' : 'translate-x-5') : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Method selector -->
                            <Transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 max-h-0"
                                enter-to-class="opacity-100 max-h-40"
                                leave-active-class="transition-all duration-200 ease-in"
                                leave-from-class="opacity-100 max-h-40"
                                leave-to-class="opacity-0 max-h-0"
                            >
                                <div v-if="form.auto_assign_enabled" class="mt-5 overflow-hidden">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ isRtl ? 'طريقة التوزيع' : 'Assignment Method' }}
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button
                                            type="button"
                                            @click="form.auto_assign_method = 'round_robin'"
                                            :class="[
                                                'relative p-4 rounded-xl border-2 transition-all duration-300 text-start',
                                                form.auto_assign_method === 'round_robin'
                                                    ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-md shadow-[#C4A265]/10'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                            ]"
                                        >
                                            <svg class="w-6 h-6 text-[#C4A265] mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="23 4 23 10 17 10"/>
                                                <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
                                            </svg>
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">{{ isRtl ? 'دوري' : 'Round Robin' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'توزيع بالتناوب' : 'Rotate between agents' }}</div>
                                        </button>
                                        <button
                                            type="button"
                                            @click="form.auto_assign_method = 'load_based'"
                                            :class="[
                                                'relative p-4 rounded-xl border-2 transition-all duration-300 text-start',
                                                form.auto_assign_method === 'load_based'
                                                    ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-md shadow-[#C4A265]/10'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                            ]"
                                        >
                                            <svg class="w-6 h-6 text-[#C4A265] mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="18" y1="20" x2="18" y2="10"/>
                                                <line x1="12" y1="20" x2="12" y2="4"/>
                                                <line x1="6" y1="20" x2="6" y2="14"/>
                                            </svg>
                                            <div class="font-medium text-sm text-gray-900 dark:text-white">{{ isRtl ? 'حسب الحمولة' : 'Load Based' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'اقل عدد عملاء اولا' : 'Least busy agent first' }}</div>
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Default Lead Priority -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-200',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'اولوية العميل الافتراضية' : 'Default Lead Priority' }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'اولوية العملاء المحتملين الجدد' : 'Priority assigned to newly created leads' }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="p in priorities"
                                    :key="p.value"
                                    type="button"
                                    @click="form.default_lead_priority = p.value"
                                    :class="[
                                        'relative p-4 rounded-xl border-2 transition-all duration-300 text-center group',
                                        form.default_lead_priority === p.value
                                            ? `${p.ring} ring-2 border-transparent shadow-lg`
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'w-10 h-10 mx-auto rounded-full bg-gradient-to-br flex items-center justify-center mb-2 transition-transform duration-300 group-hover:scale-110',
                                            p.color
                                        ]"
                                    >
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    </div>
                                    <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ isRtl ? p.labelAr : p.label }}</div>
                                </button>
                            </div>
                        </div>

                        <!-- Default Module -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-[250ms]',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="7" height="7"/>
                                        <rect x="14" y="3" width="7" height="7"/>
                                        <rect x="14" y="14" width="7" height="7"/>
                                        <rect x="3" y="14" width="7" height="7"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'القسم الافتراضي' : 'Default Module' }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'القسم الافتراضي للعملاء الجدد' : 'Default department for new leads' }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="form.default_lead_module = 'derma'"
                                    :class="[
                                        'relative p-4 rounded-xl border-2 transition-all duration-300 text-center',
                                        form.default_lead_module === 'derma'
                                            ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-md shadow-[#C4A265]/10'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <div class="w-10 h-10 mx-auto rounded-full bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                                        </svg>
                                    </div>
                                    <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ isRtl ? 'الجلدية' : 'Derma' }}</div>
                                </button>
                                <button
                                    type="button"
                                    @click="form.default_lead_module = 'dental'"
                                    :class="[
                                        'relative p-4 rounded-xl border-2 transition-all duration-300 text-center',
                                        form.default_lead_module === 'dental'
                                            ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-md shadow-[#C4A265]/10'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                                    ]"
                                >
                                    <div class="w-10 h-10 mx-auto rounded-full bg-gradient-to-br from-sky-400 to-blue-500 flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 2C8 2 6 5 6 8c0 3 1 5 1 8 0 2 2 6 5 6s5-4 5-6c0-3 1-5 1-8 0-3-2-6-6-6z"/>
                                        </svg>
                                    </div>
                                    <div class="font-semibold text-sm text-gray-900 dark:text-white">{{ isRtl ? 'الاسنان' : 'Dental' }}</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== SLA TAB ==================== -->
                <div v-show="activeTab === 'sla'">
                    <div class="space-y-6">

                        <!-- SLA Response Target -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-150',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'هدف وقت الاستجابة' : 'SLA Response Target' }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'الوقت المستهدف للرد على العملاء المحتملين الجدد' : 'Target time to respond to new leads' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6">
                                <!-- Visual Gauge -->
                                <div class="flex-shrink-0">
                                    <div class="relative w-28 h-28">
                                        <svg class="w-28 h-28 -rotate-90" viewBox="0 0 120 120">
                                            <circle cx="60" cy="60" r="50" fill="none" stroke="currentColor" stroke-width="8" class="text-gray-200 dark:text-gray-700"/>
                                            <circle
                                                cx="60" cy="60" r="50"
                                                fill="none"
                                                stroke="#C4A265"
                                                stroke-width="8"
                                                stroke-linecap="round"
                                                :stroke-dasharray="314"
                                                :stroke-dashoffset="314 - (314 * slaGaugePercent / 100)"
                                                class="transition-all duration-700"
                                            />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ form.sla_response_target_minutes }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ isRtl ? 'دقيقة' : 'min' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <input
                                        type="range"
                                        v-model.number="form.sla_response_target_minutes"
                                        min="5"
                                        max="240"
                                        step="5"
                                        class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-[#C4A265]"
                                    />
                                    <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        <span>5 {{ isRtl ? 'دقائق' : 'min' }}</span>
                                        <span>240 {{ isRtl ? 'دقيقة' : 'min' }}</span>
                                    </div>
                                    <div class="mt-3">
                                        <input
                                            type="number"
                                            v-model.number="form.sla_response_target_minutes"
                                            min="1"
                                            max="1440"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#C4A265] focus:ring-[#C4A265] transition-colors"
                                            :placeholder="isRtl ? 'دقائق' : 'Minutes'"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Follow-Up Target -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-200',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'هدف المتابعة' : 'Follow-Up Target' }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'الحد الاقصى بين المتابعات' : 'Maximum time between follow-ups' }}</p>
                                </div>
                            </div>
                            <div class="relative">
                                <input
                                    type="number"
                                    v-model.number="form.sla_followup_target_hours"
                                    min="1"
                                    max="720"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#C4A265] focus:ring-[#C4A265] transition-colors pe-16"
                                    :placeholder="isRtl ? 'ساعات' : 'Hours'"
                                />
                                <span class="absolute top-1/2 -translate-y-1/2 text-sm text-gray-400 dark:text-gray-500" :class="isRtl ? 'start-4' : 'end-4'">
                                    {{ isRtl ? 'ساعة' : 'hours' }}
                                </span>
                            </div>
                        </div>

                        <!-- Stale Lead Threshold -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-[250ms]',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-start gap-3 mb-5">
                                <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        <line x1="12" y1="9" x2="12" y2="13"/>
                                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'حد العميل الخامل' : 'Stale Lead Threshold' }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'عدد الايام بدون نشاط قبل اعتبار العميل خاملا' : 'Days of inactivity before a lead is considered stale' }}</p>
                                </div>
                            </div>
                            <div class="relative">
                                <input
                                    type="number"
                                    v-model.number="form.stale_lead_days"
                                    min="1"
                                    max="365"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-[#C4A265] focus:ring-[#C4A265] transition-colors pe-16"
                                    :placeholder="isRtl ? 'ايام' : 'Days'"
                                />
                                <span class="absolute top-1/2 -translate-y-1/2 text-sm text-gray-400 dark:text-gray-500" :class="isRtl ? 'start-4' : 'end-4'">
                                    {{ isRtl ? 'يوم' : 'days' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== NOTIFICATIONS TAB ==================== -->
                <div v-show="activeTab === 'notifications'">
                    <div class="space-y-4">
                        <!-- New Lead -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-150',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                            <circle cx="8.5" cy="7" r="4"/>
                                            <line x1="20" y1="8" x2="20" y2="14"/>
                                            <line x1="23" y1="11" x2="17" y2="11"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'اشعار عميل جديد' : 'New Lead Notification' }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'ارسال اشعار عند اضافة عميل محتمل جديد' : 'Get notified when a new lead is created in the system' }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="form.notify_on_new_lead = !form.notify_on_new_lead"
                                    :class="[
                                        'relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#C4A265] focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                        form.notify_on_new_lead ? 'bg-[#C4A265]' : 'bg-gray-300 dark:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition-transform duration-300 ease-in-out',
                                            form.notify_on_new_lead ? (isRtl ? '-translate-x-5' : 'translate-x-5') : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Status Change -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-200',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                            <polyline points="17 6 23 6 23 12"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'اشعار تغيير الحالة' : 'Status Change Notification' }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'ارسال اشعار عند تغيير حالة العميل' : 'Get notified when a lead status is updated' }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="form.notify_on_status_change = !form.notify_on_status_change"
                                    :class="[
                                        'relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#C4A265] focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                        form.notify_on_status_change ? 'bg-[#C4A265]' : 'bg-gray-300 dark:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition-transform duration-300 ease-in-out',
                                            form.notify_on_status_change ? (isRtl ? '-translate-x-5' : 'translate-x-5') : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Overdue Follow-Up -->
                        <div
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-[250ms]',
                                mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                            ]"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                            <line x1="12" y1="2" x2="12" y2="4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'اشعار المتابعة المتاخرة' : 'Overdue Follow-Up Notification' }}</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'ارسال تنبيه عند تاخر متابعة عميل محتمل' : 'Get alerted when a scheduled follow-up becomes overdue' }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="form.notify_on_overdue_followup = !form.notify_on_overdue_followup"
                                    :class="[
                                        'relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#C4A265] focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                        form.notify_on_overdue_followup ? 'bg-[#C4A265]' : 'bg-gray-300 dark:bg-gray-600'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition-transform duration-300 ease-in-out',
                                            form.notify_on_overdue_followup ? (isRtl ? '-translate-x-5' : 'translate-x-5') : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== PIPELINE TAB ==================== -->
                <div v-show="activeTab === 'pipeline'">
                    <div
                        :class="[
                            'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-all duration-700 delay-150',
                            mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                        ]"
                    >
                        <div class="flex items-start gap-3 mb-6">
                            <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-[#C4A265]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="8" y1="6" x2="21" y2="6"/>
                                    <line x1="8" y1="12" x2="21" y2="12"/>
                                    <line x1="8" y1="18" x2="21" y2="18"/>
                                    <line x1="3" y1="6" x2="3.01" y2="6"/>
                                    <line x1="3" y1="12" x2="3.01" y2="12"/>
                                    <line x1="3" y1="18" x2="3.01" y2="18"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ isRtl ? 'مراحل خط الانابيب' : 'Pipeline Stages' }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ isRtl ? 'المراحل التي يمر بها العميل المحتمل' : 'The stages a lead progresses through in your pipeline' }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(stage, index) in pipelineStages"
                                :key="stage.key"
                                :class="[
                                    'flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/30 transition-all duration-500',
                                    mounted ? 'opacity-100 translate-x-0' : 'opacity-0 ' + (isRtl ? 'translate-x-4' : '-translate-x-4')
                                ]"
                                :style="{ transitionDelay: `${200 + index * 75}ms` }"
                            >
                                <!-- Order number -->
                                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300">{{ index + 1 }}</span>
                                </div>

                                <!-- Color dot -->
                                <div
                                    class="w-4 h-4 rounded-full flex-shrink-0 ring-2 ring-offset-2 ring-offset-gray-50 dark:ring-offset-gray-700"
                                    :style="{ backgroundColor: stage.color, ringColor: stage.color }"
                                />

                                <!-- Label -->
                                <div class="flex-1">
                                    <div class="font-medium text-sm text-gray-900 dark:text-white">{{ stage.label }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ stage.key }}</div>
                                </div>

                                <!-- Connector arrow (not on last) -->
                                <div v-if="index < pipelineStages.length - 1" class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" :class="isRtl ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                        <polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 rounded-lg bg-[#C4A265]/5 border border-[#C4A265]/20">
                            <p class="text-xs text-[#C4A265] dark:text-[#d4b87a]">
                                <svg class="w-4 h-4 inline-block align-middle" :class="isRtl ? 'ml-1' : 'mr-1'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="16" x2="12" y2="12"/>
                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                </svg>
                                {{ isRtl ? 'مراحل خط الانابيب قابلة للعرض فقط. لتعديل المراحل تواصل مع مسؤول النظام.' : 'Pipeline stages are read-only. Contact system administrator to modify stages.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div
                    v-if="activeTab !== 'pipeline'"
                    :class="[
                        'mt-8 flex justify-end transition-all duration-700 delay-300',
                        mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'
                    ]"
                >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        :class="[
                            'inline-flex items-center gap-2 px-8 py-3 rounded-xl text-sm font-semibold text-white transition-all duration-300',
                            'bg-gradient-to-r from-[#C4A265] to-[#B8944F] hover:from-[#B8944F] hover:to-[#A88B4A]',
                            'shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/30',
                            'focus:outline-none focus:ring-2 focus:ring-[#C4A265] focus:ring-offset-2 dark:focus:ring-offset-gray-900',
                            'disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-lg'
                        ]"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        {{ isRtl ? 'حفظ الاعدادات' : 'Save Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
