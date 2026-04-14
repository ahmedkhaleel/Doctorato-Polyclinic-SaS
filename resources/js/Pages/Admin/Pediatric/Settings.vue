<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    settings: Object,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const form = useForm({
    pediatric_consultation_fee: props.settings?.pediatric_consultation_fee ?? '0',
    pediatric_followup_fee: props.settings?.pediatric_followup_fee ?? '0',
    pediatric_vaccination_reminder_days: props.settings?.pediatric_vaccination_reminder_days ?? '7',
    pediatric_overdue_reminder_interval: props.settings?.pediatric_overdue_reminder_interval ?? '14',
    pediatric_sms_reminders_enabled: props.settings?.pediatric_sms_reminders_enabled ?? '1',
    pediatric_growth_alert_low_percentile: props.settings?.pediatric_growth_alert_low_percentile ?? '3',
    pediatric_growth_alert_high_percentile: props.settings?.pediatric_growth_alert_high_percentile ?? '97',
    pediatric_max_age_years: props.settings?.pediatric_max_age_years ?? '18',
    pediatric_default_vaccine_schedule: props.settings?.pediatric_default_vaccine_schedule ?? 'who_iraq',
});

const showSuccess = ref(false);

function submit() {
    form.post('/admin/pediatric/settings', {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess.value = true;
            setTimeout(() => { showSuccess.value = false; }, 3000);
        },
    });
}

function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="px-4 sm:px-6 pb-10">
        <!-- Success toast -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="showSuccess" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 px-5 py-3.5 rounded-xl bg-emerald-600 text-white shadow-2xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <p class="text-sm font-medium">{{ t('Settings saved successfully', 'تم حفظ الإعدادات بنجاح') }}</p>
            </div>
        </Transition>

        <!-- Hero Header -->
        <div
            class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-green-500 p-5 sm:p-7 shadow-xl"
            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-white/10 blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="relative z-10 flex items-center gap-3">
                <Link href="/admin/pediatric" class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center hover:bg-white/30 transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ t('Pediatric Settings', 'إعدادات طب الأطفال') }}</h1>
                    <p class="text-sm text-emerald-100/80 mt-0.5">{{ t('Configure module fees, alerts, and notification settings', 'إعداد الرسوم والتنبيهات وإعدادات الإشعارات') }}</p>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6 max-w-3xl">
            <!-- Fees Section -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.1s"
            >
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">{{ t('Consultation Fees', 'رسوم الكشف') }}</h2>
                        <p class="text-[11px] text-gray-400">{{ t('Set default consultation and follow-up fees', 'تعيين رسوم الكشف والمتابعة الافتراضية') }}</p>
                    </div>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Consultation Fee (IQD)', 'رسوم الكشف (د.ع)') }}</label>
                        <input v-model="form.pediatric_consultation_fee" type="number" step="500" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all" />
                        <p v-if="form.errors.pediatric_consultation_fee" class="text-xs text-red-500 mt-1">{{ form.errors.pediatric_consultation_fee }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Follow-up Fee (IQD)', 'رسوم المتابعة (د.ع)') }}</label>
                        <input v-model="form.pediatric_followup_fee" type="number" step="500" min="0" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-green-500/20 focus:border-green-500 transition-all" />
                        <p v-if="form.errors.pediatric_followup_fee" class="text-xs text-red-500 mt-1">{{ form.errors.pediatric_followup_fee }}</p>
                    </div>
                </div>
            </div>

            <!-- SMS & Reminders Section -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.2s"
            >
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">{{ t('SMS & Reminders', 'الرسائل والتذكيرات') }}</h2>
                        <p class="text-[11px] text-gray-400">{{ t('Configure vaccination reminder settings', 'إعداد تذكيرات التطعيمات') }}</p>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ t('Enable SMS Reminders', 'تفعيل التذكيرات') }}</p>
                            <p class="text-xs text-gray-400">{{ t('Send vaccination reminders via SMS', 'إرسال تذكيرات التطعيمات عبر SMS') }}</p>
                        </div>
                        <button type="button" @click="form.pediatric_sms_reminders_enabled = form.pediatric_sms_reminders_enabled === '1' ? '0' : '1'" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200" :class="form.pediatric_sms_reminders_enabled === '1' ? 'bg-green-500' : 'bg-gray-300'">
                            <span class="inline-block h-4 w-4 rounded-full bg-white shadow-sm transform transition-transform duration-200" :class="form.pediatric_sms_reminders_enabled === '1' ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Upcoming Reminder (days before)', 'تذكير مسبق (أيام)') }}</label>
                            <input v-model="form.pediatric_vaccination_reminder_days" type="number" min="1" max="30" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Overdue Reminder Interval (days)', 'فترة تذكير التأخير (أيام)') }}</label>
                            <input v-model="form.pediatric_overdue_reminder_interval" type="number" min="7" max="60" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Growth Alerts Section -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.3s"
            >
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">{{ t('Growth & Clinical', 'النمو والسريري') }}</h2>
                        <p class="text-[11px] text-gray-400">{{ t('Growth alert thresholds and age limits', 'حدود التنبيهات والأعمار') }}</p>
                    </div>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Low Percentile Alert', 'تنبيه النسبة المنخفضة') }}</label>
                        <input v-model="form.pediatric_growth_alert_low_percentile" type="number" min="1" max="25" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                        <p class="text-[10px] text-gray-400 mt-1">{{ t('Below this = alert (default: 3)', 'أقل من هذا = تنبيه') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('High Percentile Alert', 'تنبيه النسبة المرتفعة') }}</label>
                        <input v-model="form.pediatric_growth_alert_high_percentile" type="number" min="75" max="99" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                        <p class="text-[10px] text-gray-400 mt-1">{{ t('Above this = alert (default: 97)', 'أعلى من هذا = تنبيه') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">{{ t('Max Patient Age (years)', 'الحد الأقصى للعمر (سنوات)') }}</label>
                        <input v-model="form.pediatric_max_age_years" type="number" min="12" max="21" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all" />
                    </div>
                </div>
            </div>

            <!-- Vaccine Schedule Section -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.4s"
            >
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">{{ t('Vaccine Schedule', 'جدول التطعيمات') }}</h2>
                        <p class="text-[11px] text-gray-400">{{ t('Default vaccination schedule template', 'قالب جدول التطعيمات الافتراضي') }}</p>
                    </div>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label
                            v-for="opt in [
                                { value: 'who_iraq', en: 'WHO + Iraq MOH', ar: 'منظمة الصحة + وزارة الصحة العراقية' },
                                { value: 'who_standard', en: 'WHO Standard', ar: 'منظمة الصحة العالمية' },
                                { value: 'custom', en: 'Custom', ar: 'مخصص' },
                            ]" :key="opt.value"
                            class="relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                            :class="form.pediatric_default_vaccine_schedule === opt.value ? 'border-green-500 bg-green-50 ring-2 ring-green-500/20' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input type="radio" v-model="form.pediatric_default_vaccine_schedule" :value="opt.value" class="sr-only" />
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors" :class="form.pediatric_default_vaccine_schedule === opt.value ? 'border-green-500' : 'border-gray-300'">
                                <div v-if="form.pediatric_default_vaccine_schedule === opt.value" class="w-2 h-2 rounded-full bg-green-500"></div>
                            </div>
                            <span class="text-sm font-medium" :class="form.pediatric_default_vaccine_schedule === opt.value ? 'text-green-700' : 'text-gray-600'">
                                {{ isRtl ? opt.ar : opt.en }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div
                class="flex justify-end"
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.5s"
            >
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-3 rounded-xl text-sm font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-all flex items-center gap-2 shadow-lg shadow-green-500/25"
                >
                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ t('Save Settings', 'حفظ الإعدادات') }}
                </button>
            </div>
        </form>
    </div>
</template>
