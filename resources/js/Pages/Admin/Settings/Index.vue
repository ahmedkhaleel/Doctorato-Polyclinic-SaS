<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    settings: Object,
});

const form = useForm({
    // General
    site_name_ar: props.settings?.site_name_ar || '',
    site_name_en: props.settings?.site_name_en || '',
    site_description_ar: props.settings?.site_description_ar || '',
    site_description_en: props.settings?.site_description_en || '',
    logo: props.settings?.logo || '',
    favicon: props.settings?.favicon || '',

    // Contact
    phone: props.settings?.phone || '',
    phone_secondary: props.settings?.phone_secondary || '',
    whatsapp: props.settings?.whatsapp || '',
    email: props.settings?.email || '',
    address_ar: props.settings?.address_ar || '',
    address_en: props.settings?.address_en || '',
    google_maps_url: props.settings?.google_maps_url || '',
    working_hours_ar: props.settings?.working_hours_ar || '',
    working_hours_en: props.settings?.working_hours_en || '',

    // Medical Pricing
    default_dermatology_fee: props.settings?.default_dermatology_fee || '',
    default_cosmetic_fee: props.settings?.default_cosmetic_fee || '',
    dermatology_consultant_fee: props.settings?.dermatology_consultant_fee || '',
    dermatology_specialist_fee: props.settings?.dermatology_specialist_fee || '',
    cosmetic_consultation_fee: props.settings?.cosmetic_consultation_fee || '',
    followup_fee: props.settings?.followup_fee || '',
    followup_window_days: props.settings?.followup_window_days || '15',
    // Dental Pricing
    dental_consultant_fee: props.settings?.dental_consultant_fee || '',
    dental_specialist_fee: props.settings?.dental_specialist_fee || '',

    // Currency
    currency_code: props.settings?.currency_code || 'EGP',
    currency_symbol: props.settings?.currency_symbol || 'E£',
    currency_name_en: props.settings?.currency_name_en || 'Egyptian Pound',
    currency_name_ar: props.settings?.currency_name_ar || 'جنيه مصري',
    currency_position: props.settings?.currency_position || 'after',
    currency_decimals: props.settings?.currency_decimals || '0',

    // Social Media
    facebook: props.settings?.facebook || '',
    instagram: props.settings?.instagram || '',
    twitter: props.settings?.twitter || '',
    tiktok: props.settings?.tiktok || '',
    youtube: props.settings?.youtube || '',
    snapchat: props.settings?.snapchat || '',

    // Statistics
    stat_patients: props.settings?.stat_patients || '',
    stat_years: props.settings?.stat_years || '',
    stat_doctors: props.settings?.stat_doctors || '',
    stat_services: props.settings?.stat_services || '',

    // Branding & Theme
    brand_primary: props.settings?.brand_primary || '#C4A265',
    brand_primary_hover: props.settings?.brand_primary_hover || '#A68B52',
    brand_secondary: props.settings?.brand_secondary || '#D4B87A',
    brand_accent: props.settings?.brand_accent || '#C4A265',
    brand_sidebar_bg: props.settings?.brand_sidebar_bg || '#0f172a',
    brand_sidebar_text: props.settings?.brand_sidebar_text || '#ffffff',
    brand_header_bg: props.settings?.brand_header_bg || '#3A3A3A',
    brand_footer_bg: props.settings?.brand_footer_bg || '#3A3A3A',
    brand_hero_overlay: props.settings?.brand_hero_overlay || '#1E1E1E',
    admin_primary: props.settings?.admin_primary || '#0891b2',
    admin_primary_hover: props.settings?.admin_primary_hover || '#0e7490',
    brand_font_ar: props.settings?.brand_font_ar || 'Tajawal',
    brand_font_en: props.settings?.brand_font_en || 'Poppins',
    brand_border_radius: props.settings?.brand_border_radius || '16',
    tagline_ar: props.settings?.tagline_ar || '',
    tagline_en: props.settings?.tagline_en || '',
    copyright_text_ar: props.settings?.copyright_text_ar || '',
    copyright_text_en: props.settings?.copyright_text_en || '',
    developer_name: props.settings?.developer_name || 'Markeza Group',
    developer_url: props.settings?.developer_url || 'https://markeza-group.com',
    logo_dark: props.settings?.logo_dark || '',

    // Automation
    automation_enabled: props.settings?.automation_enabled || '1',

    // SMS
    sms_enabled: props.settings?.sms_enabled || '0',
    sms_provider: props.settings?.sms_provider || 'none',
    sms_sender_name: props.settings?.sms_sender_name || 'AuraDerma',
    sms_unifonic_app_sid: props.settings?.sms_unifonic_app_sid || '',
    sms_twilio_account_sid: props.settings?.sms_twilio_account_sid || '',
    sms_twilio_auth_token: props.settings?.sms_twilio_auth_token || '',
    sms_twilio_from_number: props.settings?.sms_twilio_from_number || '',
    sms_gateway_url: props.settings?.sms_gateway_url || '',
    sms_gateway_method: props.settings?.sms_gateway_method || 'GET',
    sms_gateway_api_key: props.settings?.sms_gateway_api_key || '',
    sms_on_booking_confirmed: props.settings?.sms_on_booking_confirmed || '0',
    sms_on_booking_reminder: props.settings?.sms_on_booking_reminder || '0',
    sms_on_visit_completed: props.settings?.sms_on_visit_completed || '0',
    sms_on_lab_order_ready: props.settings?.sms_on_lab_order_ready || '0',
    // Scheduled reminders
    sms_reminder_day_before: props.settings?.sms_reminder_day_before || '1',
    sms_reminder_same_day: props.settings?.sms_reminder_same_day || '0',
    // Recall system
    sms_recall_enabled: props.settings?.sms_recall_enabled || '0',
    sms_recall_dental_months: props.settings?.sms_recall_dental_months || '6',
    sms_recall_derma_months: props.settings?.sms_recall_derma_months || '6',
    sms_recall_max_per_day: props.settings?.sms_recall_max_per_day || '50',
});

const testSmsPhone = ref('');
const testSmsLoading = ref(false);
const testSmsResult = ref(null);

function sendTestSms() {
    if (!testSmsPhone.value || testSmsPhone.value.length < 8) return;
    testSmsLoading.value = true;
    testSmsResult.value = null;

    const formData = useForm({ phone: testSmsPhone.value });
    formData.post('/admin/settings/test-sms', {
        preserveScroll: true,
        onSuccess: () => {
            testSmsResult.value = { success: true, message: t('a_sms_test_sent') };
            testSmsLoading.value = false;
        },
        onError: () => {
            testSmsResult.value = { success: false, message: t('a_sms_test_failed') };
            testSmsLoading.value = false;
        },
    });
}

const activeTab = ref('general');
const mounted = ref(false);
const saveSuccess = ref(false);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

const tabs = computed(() => [
    { id: 'general',   label: t('a_general'),         icon: 'general' },
    { id: 'branding',  label: isRtl.value ? 'الهوية والألوان' : 'Branding & Theme', icon: 'branding' },
    { id: 'contact',   label: t('a_contact_info'),     icon: 'contact' },
    { id: 'currency',  label: t('a_currency'),         icon: 'currency' },
    { id: 'pricing',   label: t('a_medical_pricing'),  icon: 'pricing' },
    { id: 'social',    label: t('a_social_media'),     icon: 'social' },
    { id: 'stats',     label: t('a_statistics'),       icon: 'stats' },
    { id: 'automation', label: t('a_automation'),   icon: 'automation' },
    { id: 'sms',        label: t('a_sms_settings'),  icon: 'sms' },
]);

const currencyPreview = computed(() => {
    const num = Number(1250).toLocaleString('en-US', {
        minimumFractionDigits: parseInt(form.currency_decimals || 0),
        maximumFractionDigits: parseInt(form.currency_decimals || 0),
    });
    return form.currency_position === 'before'
        ? `${form.currency_code || 'EGP'} ${num}`
        : `${num} ${form.currency_code || 'EGP'}`;
});

function submit() {
    form.post('/admin/settings', {
        onSuccess: () => {
            saveSuccess.value = true;
            setTimeout(() => { saveSuccess.value = false; }, 2500);
        },
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_settings')">
        <div class="settings-page" :class="{ 'is-mounted': mounted }">
            <!-- Page Header -->
            <div class="settings-header">
                <div class="settings-header__content">
                    <div class="settings-header__text">
                        <h1 class="settings-header__title">{{ $t('a_settings') }}</h1>
                        <p class="settings-header__subtitle">{{ $t('a_settings_desc') }}</p>
                    </div>
                    <div class="settings-header__decoration">
                        <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                            <circle cx="60" cy="60" r="58" stroke="currentColor" stroke-width="0.5" stroke-dasharray="4 6" class="text-[#C4A265]/20"/>
                            <circle cx="60" cy="60" r="40" stroke="currentColor" stroke-width="0.5" stroke-dasharray="3 5" class="text-[#C4A265]/15"/>
                            <path d="M60 20V100M20 60H100" stroke="currentColor" stroke-width="0.3" class="text-[#C4A265]/10"/>
                            <circle cx="60" cy="60" r="4" fill="currentColor" class="text-[#C4A265]/30"/>
                        </svg>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="settings-layout">
                    <!-- Sidebar Navigation -->
                    <nav class="settings-nav">
                        <div class="settings-nav__inner">
                            <button
                                v-for="(tab, idx) in tabs"
                                :key="tab.id"
                                type="button"
                                @click="activeTab = tab.id"
                                class="settings-nav__item"
                                :class="{ 'is-active': activeTab === tab.id }"
                                :style="{ animationDelay: `${idx * 60 + 150}ms` }"
                            >
                                <span class="settings-nav__icon">
                                    <!-- General -->
                                    <svg v-if="tab.icon === 'general'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <!-- Branding -->
                                    <svg v-else-if="tab.icon === 'branding'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                                    <!-- Contact -->
                                    <svg v-else-if="tab.icon === 'contact'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    <!-- Currency -->
                                    <svg v-else-if="tab.icon === 'currency'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <!-- Pricing -->
                                    <svg v-else-if="tab.icon === 'pricing'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                                    <!-- Social -->
                                    <svg v-else-if="tab.icon === 'social'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    <!-- Stats -->
                                    <svg v-else-if="tab.icon === 'stats'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                    <svg v-else-if="tab.icon === 'automation'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    <svg v-else-if="tab.icon === 'sms'" class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                </span>
                                <span class="settings-nav__label">{{ tab.label }}</span>
                                <span class="settings-nav__arrow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </span>
                            </button>
                        </div>
                    </nav>

                    <!-- Content Panel -->
                    <div class="settings-content">
                        <!-- ═══ GENERAL ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'general'" key="general" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_general_settings') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_general_settings_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_site_identity') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_site_name_en') }}</label>
                                            <input v-model="form.site_name_en" type="text" class="field__input" />
                                            <p v-if="form.errors.site_name_en" class="field__error">{{ form.errors.site_name_en }}</p>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_site_name_ar') }}</label>
                                            <input v-model="form.site_name_ar" type="text" dir="rtl" class="field__input" />
                                            <p v-if="form.errors.site_name_ar" class="field__error">{{ form.errors.site_name_ar }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_description') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_site_desc_en') }}</label>
                                            <textarea v-model="form.site_description_en" rows="3" class="field__input field__textarea"></textarea>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_site_desc_ar') }}</label>
                                            <textarea v-model="form.site_description_ar" rows="3" dir="rtl" class="field__input field__textarea"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_branding') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_logo_url') }}</label>
                                            <div class="field__with-preview">
                                                <input v-model="form.logo" type="text" class="field__input" />
                                                <div v-if="form.logo" class="field__preview">
                                                    <img :src="form.logo" alt="Logo preview" class="h-8 w-auto object-contain" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_favicon_url') }}</label>
                                            <div class="field__with-preview">
                                                <input v-model="form.favicon" type="text" class="field__input" />
                                                <div v-if="form.favicon" class="field__preview">
                                                    <img :src="form.favicon" alt="Favicon preview" class="h-6 w-6 object-contain" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ BRANDING & THEME ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'branding'" key="branding" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ isRtl ? 'الهوية البصرية والألوان' : 'Branding & Theme' }}</h2>
                                    <p class="panel-header__desc">{{ isRtl ? 'تخصيص الألوان والشعارات والخطوط لتغيير مظهر النظام بالكامل — يمكنك إعادة استخدام النظام لعيادة أخرى بتغيير هذه الإعدادات فقط' : 'Customize colors, logos, and fonts to completely change the system appearance — reuse this system for another clinic by changing these settings only' }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <!-- Live Preview -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'معاينة مباشرة' : 'Live Preview' }}</h3>
                                    <div class="rounded-xl border border-gray-200 overflow-hidden mb-4">
                                        <!-- Mini preview header -->
                                        <div class="h-8 flex items-center justify-between px-4 text-[10px] text-white/70" :style="{ background: form.brand_header_bg }">
                                            <span>{{ form.site_name_en || 'Clinic Name' }}</span>
                                            <div class="flex gap-2">
                                                <span class="px-2 py-0.5 rounded-full border text-[9px]" :style="{ borderColor: form.brand_primary, color: form.brand_primary }">Portal</span>
                                                <span class="px-2 py-0.5 rounded-full text-white text-[9px]" :style="{ background: form.brand_primary }">Book</span>
                                            </div>
                                        </div>
                                        <!-- Mini preview body -->
                                        <div class="flex h-28">
                                            <!-- Sidebar preview -->
                                            <div class="w-20 flex flex-col items-center py-3 gap-2" :style="{ background: form.brand_sidebar_bg }">
                                                <div class="w-6 h-6 rounded-lg flex items-center justify-center text-white text-[9px] font-bold" :style="{ background: `linear-gradient(135deg, ${form.brand_primary}, ${form.brand_secondary || form.brand_primary})` }">A</div>
                                                <div class="w-12 h-1.5 rounded-full" :style="{ background: form.brand_primary + '30' }"></div>
                                                <div class="w-10 h-1.5 rounded-full bg-white/10"></div>
                                                <div class="w-10 h-1.5 rounded-full bg-white/10"></div>
                                            </div>
                                            <!-- Content preview -->
                                            <div class="flex-1 bg-gray-50 p-3">
                                                <div class="h-6 rounded-lg mb-2 text-white text-[9px] font-bold flex items-center px-3" :style="{ background: `linear-gradient(135deg, ${form.brand_sidebar_bg}, ${form.brand_sidebar_bg}dd)` }">
                                                    {{ isRtl ? 'مرحباً' : 'Welcome' }}
                                                </div>
                                                <div class="grid grid-cols-3 gap-1.5">
                                                    <div class="h-8 rounded-md bg-white border border-gray-100 flex items-center justify-center">
                                                        <div class="w-4 h-4 rounded" :style="{ background: form.brand_primary + '20' }"></div>
                                                    </div>
                                                    <div class="h-8 rounded-md bg-white border border-gray-100 flex items-center justify-center">
                                                        <div class="w-4 h-4 rounded" :style="{ background: form.admin_primary + '20' }"></div>
                                                    </div>
                                                    <div class="h-8 rounded-md bg-white border border-gray-100 flex items-center justify-center">
                                                        <div class="w-4 h-4 rounded" :style="{ background: form.brand_accent + '20' }"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Mini preview footer -->
                                        <div class="h-6 flex items-center justify-center text-[9px] text-white/50" :style="{ background: form.brand_footer_bg }">
                                            &copy; {{ form.site_name_en || 'Clinic' }} · {{ form.developer_name || 'Developer' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Brand Colors -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'الألوان الرئيسية (الموقع + بوابة المريض)' : 'Brand Colors (Website + Patient Portal)' }}</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="field" v-for="c in [
                                            { key: 'brand_primary', en: 'Primary Color', ar: 'اللون الأساسي' },
                                            { key: 'brand_primary_hover', en: 'Primary Hover', ar: 'اللون عند التمرير' },
                                            { key: 'brand_secondary', en: 'Secondary Color', ar: 'اللون الثانوي' },
                                            { key: 'brand_accent', en: 'Accent Color', ar: 'لون التمييز' },
                                        ]" :key="c.key">
                                            <label class="field__label">{{ isRtl ? c.ar : c.en }}</label>
                                            <div class="flex items-center gap-2">
                                                <input v-model="form[c.key]" type="color" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                                                <input v-model="form[c.key]" type="text" class="field__input flex-1 font-mono text-xs" maxlength="7" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Layout Colors -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'ألوان التخطيط' : 'Layout Colors' }}</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        <div class="field" v-for="c in [
                                            { key: 'brand_sidebar_bg', en: 'Sidebar Background', ar: 'خلفية الشريط الجانبي' },
                                            { key: 'brand_header_bg', en: 'Top Bar / Header', ar: 'الشريط العلوي' },
                                            { key: 'brand_footer_bg', en: 'Footer Background', ar: 'خلفية الفوتر' },
                                            { key: 'brand_hero_overlay', en: 'Hero Overlay', ar: 'طبقة الهيرو' },
                                            { key: 'brand_sidebar_text', en: 'Sidebar Text', ar: 'نص الشريط الجانبي' },
                                        ]" :key="c.key">
                                            <label class="field__label">{{ isRtl ? c.ar : c.en }}</label>
                                            <div class="flex items-center gap-2">
                                                <input v-model="form[c.key]" type="color" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                                                <input v-model="form[c.key]" type="text" class="field__input flex-1 font-mono text-xs" maxlength="7" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Admin Panel Colors -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'ألوان لوحة الإدارة' : 'Admin Panel Colors' }}</h3>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="field" v-for="c in [
                                            { key: 'admin_primary', en: 'Admin Accent', ar: 'لون الإدارة' },
                                            { key: 'admin_primary_hover', en: 'Admin Hover', ar: 'لون التمرير' },
                                        ]" :key="c.key">
                                            <label class="field__label">{{ isRtl ? c.ar : c.en }}</label>
                                            <div class="flex items-center gap-2">
                                                <input v-model="form[c.key]" type="color" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
                                                <input v-model="form[c.key]" type="text" class="field__input flex-1 font-mono text-xs" maxlength="7" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Logos -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'الشعارات' : 'Logos' }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الشعار الرئيسي (خلفية فاتحة)' : 'Main Logo (Light Background)' }}</label>
                                            <div class="field__with-preview">
                                                <input v-model="form.logo" type="text" class="field__input" :placeholder="isRtl ? 'رابط الصورة أو مسار الملف' : 'Image URL or file path'" />
                                                <div v-if="form.logo" class="field__preview"><img :src="form.logo" alt="" class="h-10 w-auto object-contain" /></div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الشعار الداكن (خلفية داكنة)' : 'Dark Logo (Dark Background)' }}</label>
                                            <div class="field__with-preview">
                                                <input v-model="form.logo_dark" type="text" class="field__input" :placeholder="isRtl ? 'رابط الصورة أو مسار الملف' : 'Image URL or file path'" />
                                                <div v-if="form.logo_dark" class="field__preview bg-gray-800"><img :src="form.logo_dark" alt="" class="h-10 w-auto object-contain" /></div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الأيقونة المفضلة (Favicon)' : 'Favicon' }}</label>
                                            <div class="field__with-preview">
                                                <input v-model="form.favicon" type="text" class="field__input" />
                                                <div v-if="form.favicon" class="field__preview"><img :src="form.favicon" alt="" class="h-6 w-auto object-contain" /></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Typography -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'الخطوط والشكل' : 'Typography & Shape' }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الخط العربي' : 'Arabic Font' }}</label>
                                            <select v-model="form.brand_font_ar" class="field__input">
                                                <option value="Tajawal">Tajawal</option>
                                                <option value="Cairo">Cairo</option>
                                                <option value="Almarai">Almarai</option>
                                                <option value="IBM Plex Sans Arabic">IBM Plex Sans Arabic</option>
                                                <option value="Noto Sans Arabic">Noto Sans Arabic</option>
                                                <option value="Readex Pro">Readex Pro</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الخط الإنجليزي' : 'English Font' }}</label>
                                            <select v-model="form.brand_font_en" class="field__input">
                                                <option value="Poppins">Poppins</option>
                                                <option value="Inter">Inter</option>
                                                <option value="DM Sans">DM Sans</option>
                                                <option value="Plus Jakarta Sans">Plus Jakarta Sans</option>
                                                <option value="Outfit">Outfit</option>
                                                <option value="Nunito">Nunito</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'استدارة الحواف' : 'Border Radius' }}</label>
                                            <div class="flex items-center gap-3">
                                                <input v-model="form.brand_border_radius" type="range" min="0" max="24" step="2" class="flex-1" />
                                                <span class="text-sm font-mono text-gray-500 w-10 text-center">{{ form.brand_border_radius }}px</span>
                                            </div>
                                            <div class="flex gap-2 mt-2">
                                                <div class="w-10 h-10 border-2 border-gray-300 bg-gray-50" :style="{ borderRadius: form.brand_border_radius + 'px' }"></div>
                                                <div class="flex-1 h-10 border-2 border-gray-300 bg-gray-50" :style="{ borderRadius: (form.brand_border_radius * 0.75) + 'px' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Branding Text -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ isRtl ? 'النصوص' : 'Branding Text' }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الشعار النصي (إنجليزي)' : 'Tagline (English)' }}</label>
                                            <input v-model="form.tagline_en" type="text" class="field__input" :placeholder="isRtl ? 'مثال: Your Beauty, Our Passion' : 'e.g. Your Beauty, Our Passion'" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'الشعار النصي (عربي)' : 'Tagline (Arabic)' }}</label>
                                            <input v-model="form.tagline_ar" type="text" dir="rtl" class="field__input" :placeholder="isRtl ? 'مثال: جمالك، شغفنا' : 'e.g. جمالك، شغفنا'" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'نص حقوق النشر (إنجليزي)' : 'Copyright Text (English)' }}</label>
                                            <input v-model="form.copyright_text_en" type="text" class="field__input" placeholder="AURA Derma Aesthetic Clinic" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'نص حقوق النشر (عربي)' : 'Copyright Text (Arabic)' }}</label>
                                            <input v-model="form.copyright_text_ar" type="text" dir="rtl" class="field__input" placeholder="عيادة أورا ديرما التجميلية" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'اسم المطور' : 'Developer Name' }}</label>
                                            <input v-model="form.developer_name" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ isRtl ? 'رابط المطور' : 'Developer URL' }}</label>
                                            <input v-model="form.developer_url" type="text" class="field__input" dir="ltr" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ CONTACT ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'contact'" key="contact" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--teal">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_contact_information') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_contact_info_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_phone_numbers') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_phone') }}</label>
                                            <input v-model="form.phone" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_secondary_phone') }}</label>
                                            <input v-model="form.phone_secondary" type="text" class="field__input" />
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_online') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_whatsapp') }}</label>
                                            <input v-model="form.whatsapp" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_email') }}</label>
                                            <input v-model="form.email" type="email" class="field__input" />
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_location') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_address_en') }}</label>
                                            <textarea v-model="form.address_en" rows="2" class="field__input field__textarea"></textarea>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_address_ar') }}</label>
                                            <textarea v-model="form.address_ar" rows="2" dir="rtl" class="field__input field__textarea"></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_google_maps_url') }}</label>
                                            <input v-model="form.google_maps_url" type="text" class="field__input" />
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_working_hours') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_working_hours_en') }}</label>
                                            <input v-model="form.working_hours_en" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_working_hours_ar') }}</label>
                                            <input v-model="form.working_hours_ar" type="text" dir="rtl" class="field__input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ CURRENCY ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'currency'" key="currency" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--amber">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_currency') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_currency_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <!-- Live Preview -->
                                <div class="currency-preview">
                                    <div class="currency-preview__label">{{ $t('a_live_preview') }}</div>
                                    <div class="currency-preview__value">{{ currencyPreview }}</div>
                                    <div class="currency-preview__sub">{{ form.currency_name_en || 'Egyptian Pound' }} / {{ form.currency_name_ar || 'جنيه مصري' }}</div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_configuration') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_currency_code') }}</label>
                                            <input v-model="form.currency_code" type="text" maxlength="5" placeholder="EGP" class="field__input uppercase" />
                                            <p class="field__hint">e.g. EGP, SAR, AED, USD, EUR</p>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_currency_symbol') }}</label>
                                            <input v-model="form.currency_symbol" type="text" maxlength="5" placeholder="E£" class="field__input" />
                                            <p class="field__hint">e.g. E£, &#xFDFC;, &#x62F;.&#x625;, $</p>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_decimal_places') }}</label>
                                            <select v-model="form.currency_decimals" class="field__input">
                                                <option value="0">0 (1,000)</option>
                                                <option value="1">1 (1,000.0)</option>
                                                <option value="2">2 (1,000.00)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_display') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_currency_name_en') }}</label>
                                            <input v-model="form.currency_name_en" type="text" placeholder="Egyptian Pound" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_currency_name_ar') }}</label>
                                            <input v-model="form.currency_name_ar" type="text" dir="rtl" placeholder="جنيه مصري" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_position') }}</label>
                                            <select v-model="form.currency_position" class="field__input">
                                                <option value="after">After number (1,000 EGP)</option>
                                                <option value="before">Before number (EGP 1,000)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ MEDICAL PRICING ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'pricing'" key="pricing" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--rose">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_medical_pricing') }}</h2>
                                    <p class="panel-header__desc">Unified pricing for consultations. Per-doctor overrides can be set in each doctor's profile.</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_derm_consultation') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">Consultant Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.dermatology_consultant_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                            <p class="field__hint">Applied when the doctor's type is "Consultant"</p>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">Specialist Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.dermatology_specialist_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                            <p class="field__hint">Applied when the doctor's type is "Specialist"</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_cosmetic_consultation') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">Consultation Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.cosmetic_consultation_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_derm_followup') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">Follow-up Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.followup_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">Follow-up Window (Days)</label>
                                            <input v-model="form.followup_window_days" type="number" min="1" max="90" placeholder="15" class="field__input" />
                                            <p class="field__hint">Patient can get a follow-up within this many days of their last dermatology consultation</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">
                                        <svg class="w-4 h-4 text-cyan-500 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                        {{ $t('a_dental_consultation') }}
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_consultant') }} ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.dental_consultant_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                            <p class="field__hint">{{ $t('a_dental_consultant_fee_hint') }}</p>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_specialist') }} ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.dental_specialist_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                            <p class="field__hint">{{ $t('a_dental_specialist_fee_hint') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="field-group field-group--muted">
                                    <h3 class="field-group__title">{{ $t('a_legacy_defaults') }}</h3>
                                    <p class="field__hint mb-3">Used as fallback when doctor has no per-doctor fee and no type-based fee applies.</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">Default Dermatology Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.default_dermatology_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">Default Cosmetic Fee ({{ form.currency_code || 'EGP' }})</label>
                                            <input v-model="form.default_cosmetic_fee" type="number" step="0.01" min="0" placeholder="0.00" class="field__input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ SOCIAL MEDIA ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'social'" key="social" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--indigo">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_social_media') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_social_media_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Facebook -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: #1877F2;">
                                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">Facebook</label>
                                            <input v-model="form.facebook" type="url" placeholder="https://facebook.com/..." class="field__input" />
                                        </div>
                                    </div>
                                    <!-- Instagram -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);">
                                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">Instagram</label>
                                            <input v-model="form.instagram" type="url" placeholder="https://instagram.com/..." class="field__input" />
                                        </div>
                                    </div>
                                    <!-- Twitter/X -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: #000;">
                                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">Twitter / X</label>
                                            <input v-model="form.twitter" type="url" placeholder="https://x.com/..." class="field__input" />
                                        </div>
                                    </div>
                                    <!-- TikTok -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: #000;">
                                            <svg class="w-3.5 h-3.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">TikTok</label>
                                            <input v-model="form.tiktok" type="url" placeholder="https://tiktok.com/..." class="field__input" />
                                        </div>
                                    </div>
                                    <!-- YouTube -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: #FF0000;">
                                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">YouTube</label>
                                            <input v-model="form.youtube" type="url" placeholder="https://youtube.com/..." class="field__input" />
                                        </div>
                                    </div>
                                    <!-- Snapchat -->
                                    <div class="social-field">
                                        <div class="social-field__icon" style="background: #FFFC00;">
                                            <svg class="w-4 h-4 text-black" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="field__label">Snapchat</label>
                                            <input v-model="form.snapchat" type="url" placeholder="https://snapchat.com/..." class="field__input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- ═══ STATISTICS ═══ -->
                        <Transition name="panel-slide" mode="out-in">
                        <div v-if="activeTab === 'stats'" key="stats" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--emerald">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_statistics') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_statistics_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <!-- Live Preview Cards -->
                                <div class="stats-preview">
                                    <div class="stats-preview__card">
                                        <div class="stats-preview__number">{{ form.stat_patients || '0' }}</div>
                                        <div class="stats-preview__label">{{ $t('a_happy_patients') }}</div>
                                    </div>
                                    <div class="stats-preview__card">
                                        <div class="stats-preview__number">{{ form.stat_years || '0' }}</div>
                                        <div class="stats-preview__label">{{ $t('a_years_experience') }}</div>
                                    </div>
                                    <div class="stats-preview__card">
                                        <div class="stats-preview__number">{{ form.stat_doctors || '0' }}</div>
                                        <div class="stats-preview__label">{{ $t('a_expert_doctors') }}</div>
                                    </div>
                                    <div class="stats-preview__card">
                                        <div class="stats-preview__number">{{ form.stat_services || '0' }}</div>
                                        <div class="stats-preview__label">{{ $t('a_services') }}</div>
                                    </div>
                                </div>

                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_edit_values') }}</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_happy_patients') }}</label>
                                            <input v-model="form.stat_patients" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_years_experience') }}</label>
                                            <input v-model="form.stat_years" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_expert_doctors') }}</label>
                                            <input v-model="form.stat_doctors" type="text" class="field__input" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_services_count') }}</label>
                                            <input v-model="form.stat_services" type="text" class="field__input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Automation Tab -->
                        <div v-if="activeTab === 'automation'" key="automation" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--emerald">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_automation') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_automation_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="field-group">
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                                        <div>
                                            <h3 class="text-sm font-semibold text-[#3A3A3A]">{{ $t('a_followup_automation') }}</h3>
                                            <p class="text-xs text-gray-500 mt-0.5">Enable or disable all automated follow-up sequences system-wide</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" :checked="form.automation_enabled === '1'" @change="form.automation_enabled = form.automation_enabled === '1' ? '0' : '1'" class="sr-only peer" />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                        </label>
                                    </div>
                                    <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100">
                                        <p class="text-xs text-blue-700">
                                            <strong>Manage sequences:</strong> Go to CRM → Automation Sequences to create, edit, and manage your automated workflows.
                                            The system processes pending steps every 5 minutes during working hours (8 AM - 10 PM).
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══ SMS ═══ -->
                        <div v-if="activeTab === 'sms'" key="sms" class="settings-panel">
                            <div class="panel-header">
                                <div class="panel-header__icon panel-header__icon--cyan">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                </div>
                                <div>
                                    <h2 class="panel-header__title">{{ $t('a_sms_settings') }}</h2>
                                    <p class="panel-header__desc">{{ $t('a_sms_settings_desc') }}</p>
                                </div>
                            </div>
                            <div class="panel-body">
                                <!-- Enable / Provider -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_sms_provider_config') }}</h3>
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 mb-5">
                                        <div>
                                            <h3 class="text-sm font-semibold text-[#3A3A3A]">{{ $t('a_enable_sms') }}</h3>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $t('a_enable_sms_desc') }}</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" :checked="form.sms_enabled === '1'" @change="form.sms_enabled = form.sms_enabled === '1' ? '0' : '1'" class="sr-only peer" />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_sms_provider') }}</label>
                                            <select v-model="form.sms_provider" class="field__input">
                                                <option value="none">{{ $t('a_none') }}</option>
                                                <option value="unifonic">Unifonic</option>
                                                <option value="twilio">Twilio</option>
                                                <option value="gateway">{{ $t('a_custom_gateway') }}</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_sender_name') }}</label>
                                            <input v-model="form.sms_sender_name" type="text" maxlength="11" placeholder="AuraDerma" class="field__input" />
                                            <p class="field__hint">{{ $t('a_sender_name_hint') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Unifonic Settings -->
                                <div v-if="form.sms_provider === 'unifonic'" class="field-group">
                                    <h3 class="field-group__title">Unifonic {{ $t('a_credentials') }}</h3>
                                    <div class="grid grid-cols-1 gap-5">
                                        <div class="field">
                                            <label class="field__label">App SID</label>
                                            <input v-model="form.sms_unifonic_app_sid" type="text" placeholder="Enter Unifonic App SID" class="field__input font-mono text-sm" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Twilio Settings -->
                                <div v-if="form.sms_provider === 'twilio'" class="field-group">
                                    <h3 class="field-group__title">Twilio {{ $t('a_credentials') }}</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="field">
                                            <label class="field__label">Account SID</label>
                                            <input v-model="form.sms_twilio_account_sid" type="text" placeholder="ACxxxxxxxx..." class="field__input font-mono text-sm" />
                                        </div>
                                        <div class="field">
                                            <label class="field__label">Auth Token</label>
                                            <input v-model="form.sms_twilio_auth_token" type="password" placeholder="Enter Auth Token" class="field__input font-mono text-sm" />
                                        </div>
                                    </div>
                                    <div class="mt-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_from_number') }}</label>
                                            <input v-model="form.sms_twilio_from_number" type="text" placeholder="+1234567890" class="field__input font-mono text-sm" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Gateway Settings -->
                                <div v-if="form.sms_provider === 'gateway'" class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_custom_gateway') }} {{ $t('a_credentials') }}</h3>
                                    <div class="grid grid-cols-1 gap-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_gateway_url') }}</label>
                                            <input v-model="form.sms_gateway_url" type="text" placeholder="https://api.example.com/sms?phone={phone}&message={message}&api_key={api_key}" class="field__input font-mono text-xs" />
                                            <p class="field__hint">{{ $t('a_gateway_url_hint') }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                                        <div class="field">
                                            <label class="field__label">{{ $t('a_http_method') }}</label>
                                            <select v-model="form.sms_gateway_method" class="field__input">
                                                <option value="GET">GET</option>
                                                <option value="POST">POST</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label class="field__label">API Key</label>
                                            <input v-model="form.sms_gateway_api_key" type="password" placeholder="Enter API Key" class="field__input font-mono text-sm" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Auto-SMS Events -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_auto_sms_events') }}</h3>
                                    <p class="field__hint mb-4">{{ $t('a_auto_sms_events_desc') }}</p>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_booking_confirmed') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_booking_confirmed_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_on_booking_confirmed === '1'" @change="form.sms_on_booking_confirmed = form.sms_on_booking_confirmed === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_booking_reminder') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_booking_reminder_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_on_booking_reminder === '1'" @change="form.sms_on_booking_reminder = form.sms_on_booking_reminder === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_visit_completed') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_visit_completed_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_on_visit_completed === '1'" @change="form.sms_on_visit_completed = form.sms_on_visit_completed === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_lab_order_ready') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_lab_order_ready_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_on_lab_order_ready === '1'" @change="form.sms_on_lab_order_ready = form.sms_on_lab_order_ready === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Scheduled Reminders -->
                                <div class="field-group">
                                    <h3 class="field-group__title">
                                        <svg class="w-4 h-4 text-cyan-500 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ $t('a_sms_scheduled_reminders') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-3">{{ $t('a_sms_scheduled_reminders_desc') }}</p>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_day_before') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_day_before_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_reminder_day_before === '1'" @change="form.sms_reminder_day_before = form.sms_reminder_day_before === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_same_day') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_same_day_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_reminder_same_day === '1'" @change="form.sms_reminder_same_day = form.sms_reminder_same_day === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recall System -->
                                <div class="field-group">
                                    <h3 class="field-group__title">
                                        <svg class="w-4 h-4 text-purple-500 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        {{ $t('a_sms_recall_system') }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-3">{{ $t('a_sms_recall_system_desc') }}</p>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <div>
                                                <span class="text-sm font-medium text-[#3A3A3A]">{{ $t('a_sms_recall_enabled') }}</span>
                                                <p class="text-xs text-gray-500">{{ $t('a_sms_recall_enabled_desc') }}</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" :checked="form.sms_recall_enabled === '1'" @change="form.sms_recall_enabled = form.sms_recall_enabled === '1' ? '0' : '1'" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#C4A265]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#C4A265]"></div>
                                            </label>
                                        </div>
                                        <Transition name="fade">
                                        <div v-if="form.sms_recall_enabled === '1'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="field">
                                                <label class="field__label">{{ $t('a_sms_recall_dental_months') }}</label>
                                                <select v-model="form.sms_recall_dental_months" class="field__input">
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="6">6</option>
                                                    <option value="9">9</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label class="field__label">{{ $t('a_sms_recall_derma_months') }}</label>
                                                <select v-model="form.sms_recall_derma_months" class="field__input">
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="6">6</option>
                                                    <option value="9">9</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label class="field__label">{{ $t('a_sms_recall_max_per_day') }}</label>
                                                <input v-model="form.sms_recall_max_per_day" type="number" min="5" max="500" class="field__input" />
                                            </div>
                                        </div>
                                        </Transition>
                                    </div>
                                </div>

                                <!-- Test SMS -->
                                <div class="field-group">
                                    <h3 class="field-group__title">{{ $t('a_test_sms') }}</h3>
                                    <div class="flex items-end gap-3">
                                        <div class="flex-1 field">
                                            <label class="field__label">{{ $t('a_phone') }}</label>
                                            <input v-model="testSmsPhone" type="text" placeholder="07xxxxxxxx" class="field__input" />
                                        </div>
                                        <button
                                            type="button"
                                            @click="sendTestSms"
                                            :disabled="testSmsLoading || !testSmsPhone || testSmsPhone.length < 8"
                                            class="px-5 py-2.5 bg-[#C4A265] text-white text-sm font-medium rounded-xl hover:bg-[#B08D4C] disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2 shrink-0"
                                        >
                                            <svg v-if="!testSmsLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            {{ $t('a_send_test') }}
                                        </button>
                                    </div>
                                    <div v-if="testSmsResult" class="mt-3 p-3 rounded-lg text-sm" :class="testSmsResult.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'">
                                        {{ testSmsResult.message }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>

                        <!-- Sticky Save Bar -->
                        <div class="save-bar" v-if="can('settings.update')">
                            <div class="save-bar__inner">
                                <Transition name="save-check" mode="out-in">
                                    <div v-if="saveSuccess" key="success" class="save-bar__success">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        <span>{{ $t('a_settings_saved') }}</span>
                                    </div>
                                    <div v-else key="btn" class="save-bar__actions">
                                        <span class="save-bar__hint">{{ $t('a_changes_saved_globally') }}</span>
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="save-bar__btn"
                                            :class="{ 'is-loading': form.processing }"
                                        >
                                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                            <span>{{ form.processing ? $t('a_saving') : $t('a_save_settings') }}</span>
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ═══════════════════════════════════════════════
   SETTINGS PAGE — Luxury Refined Design
   ═══════════════════════════════════════════════ */

.settings-page {
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.settings-page.is-mounted {
    opacity: 1;
    transform: translateY(0);
}

/* ── Header ─────────────────────────── */
.settings-header {
    margin-bottom: 2rem;
    position: relative;
}
.settings-header__content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.75rem 2rem;
    background: linear-gradient(135deg, #1E1E1E 0%, #2a2520 50%, #1E1E1E 100%);
    border-radius: 16px;
    position: relative;
    overflow: hidden;
}
.settings-header__content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, #C4A265 30%, #D4B87A 50%, #C4A265 70%, transparent 100%);
}
.settings-header__content::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 80% 50%, rgba(196, 162, 101, 0.06) 0%, transparent 70%);
    pointer-events: none;
}
.settings-header__title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.625rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.02em;
    margin-bottom: 0.25rem;
}
.settings-header__subtitle {
    font-size: 0.8125rem;
    color: rgba(255,255,255,0.4);
    font-weight: 400;
    letter-spacing: 0.01em;
}
.settings-header__decoration {
    color: #C4A265;
    opacity: 0.5;
    animation: spin-slow 60s linear infinite;
    flex-shrink: 0;
}
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* ── Layout: Sidebar + Content ─────── */
.settings-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 1.5rem;
    align-items: start;
}
@media (max-width: 1023px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
}

/* ── Sidebar Navigation ─────────────── */
.settings-nav {
    position: sticky;
    top: 100px;
}
.settings-nav__inner {
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 16px rgba(0,0,0,0.02);
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
@media (max-width: 1023px) {
    .settings-nav__inner {
        flex-direction: row;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        gap: 4px;
    }
    .settings-nav__inner::-webkit-scrollbar { display: none; }
}

.settings-nav__item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.625rem 0.75rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    animation: nav-item-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}
@keyframes nav-item-in {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}
@media (max-width: 1023px) {
    .settings-nav__item {
        white-space: nowrap;
        width: auto;
        flex-shrink: 0;
    }
}
.settings-nav__item:hover:not(.is-active) {
    background: #f8f7f4;
    color: #334155;
}
.settings-nav__item.is-active {
    background: linear-gradient(135deg, #C4A265 0%, #D4B87A 100%);
    color: #fff;
    box-shadow: 0 2px 8px rgba(196, 162, 101, 0.3), 0 1px 2px rgba(196, 162, 101, 0.2);
}
.settings-nav__icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    flex-shrink: 0;
}
.settings-nav__item.is-active .settings-nav__icon {
    background: rgba(255,255,255,0.2);
}
.settings-nav__label {
    flex: 1;
}
.settings-nav__arrow {
    opacity: 0;
    transform: translateX(-4px);
    transition: all 0.25s ease;
}
.settings-nav__item.is-active .settings-nav__arrow {
    opacity: 0.7;
    transform: translateX(0);
}
@media (max-width: 1023px) {
    .settings-nav__arrow { display: none; }
}

/* ── Content Panel ──────────────────── */
.settings-content {
    min-height: 500px;
}
.settings-panel {
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.02);
    overflow: hidden;
}

/* Panel Transitions */
.panel-slide-enter-active {
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.panel-slide-leave-active {
    transition: all 0.2s cubic-bezier(0.4, 0, 1, 1);
}
.panel-slide-enter-from {
    opacity: 0;
    transform: translateY(12px);
}
.panel-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* Panel Header */
.panel-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: linear-gradient(180deg, #faf9f7 0%, #fff 100%);
}
.panel-header__icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #C4A265 0%, #D4B87A 100%);
    color: #fff;
    box-shadow: 0 2px 6px rgba(196, 162, 101, 0.25);
    flex-shrink: 0;
}
.panel-header__icon--teal {
    background: linear-gradient(135deg, #0d9488, #14b8a6);
    box-shadow: 0 2px 6px rgba(13,148,136,0.25);
}
.panel-header__icon--amber {
    background: linear-gradient(135deg, #d97706, #f59e0b);
    box-shadow: 0 2px 6px rgba(217,119,6,0.25);
}
.panel-header__icon--rose {
    background: linear-gradient(135deg, #e11d48, #f43f5e);
    box-shadow: 0 2px 6px rgba(225,29,72,0.25);
}
.panel-header__icon--indigo {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    box-shadow: 0 2px 6px rgba(79,70,229,0.25);
}
.panel-header__icon--emerald {
    background: linear-gradient(135deg, #059669, #10b981);
    box-shadow: 0 2px 6px rgba(5,150,105,0.25);
}
.panel-header__icon--cyan {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
    box-shadow: 0 2px 6px rgba(8,145,178,0.25);
}
.panel-header__title {
    font-family: 'Poppins', sans-serif;
    font-size: 1.0625rem;
    font-weight: 600;
    color: #1e293b;
    letter-spacing: -0.01em;
}
.panel-header__desc {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 1px;
}

/* Panel Body */
.panel-body {
    padding: 1.75rem;
}

/* ── Field Groups ─────────────────── */
.field-group {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #f1f0ed;
}
.field-group:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.field-group--muted {
    background: #faf9f7;
    margin-left: -1.75rem;
    margin-right: -1.75rem;
    padding-left: 1.75rem;
    padding-right: 1.75rem;
    padding-top: 1.5rem;
    border-top: 1px dashed #e2e0db;
    border-bottom: none;
    border-radius: 0 0 16px 16px;
}
.field-group__title {
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #C4A265;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.field-group__title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, rgba(196,162,101,0.2), transparent);
}

/* ── Fields ──────────────────────── */
.field {
    position: relative;
}
.field__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.375rem;
    letter-spacing: 0.01em;
}
.field__input {
    width: 100%;
    padding: 0.5625rem 0.875rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.8125rem;
    color: #1e293b;
    background: #fff;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    font-family: 'Poppins', sans-serif;
}
.field__input:hover {
    border-color: #cbd5e1;
}
.field__input:focus {
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196,162,101,0.1), 0 1px 2px rgba(0,0,0,0.04);
}
.field__input::placeholder {
    color: #cbd5e1;
}
.field__textarea {
    resize: vertical;
    min-height: 44px;
}
.field__hint {
    font-size: 0.6875rem;
    color: #94a3b8;
    margin-top: 0.375rem;
    line-height: 1.4;
}
.field__error {
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 0.25rem;
    font-weight: 500;
}
.field__with-preview {
    position: relative;
}
.field__preview {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    padding: 4px;
    background: #f8fafc;
    border-radius: 6px;
}

/* ── Social Media Fields ────────────── */
.social-field {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 12px;
    background: #faf9f7;
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.25s ease;
}
.social-field:hover {
    background: #f5f3ef;
    border-color: rgba(0,0,0,0.08);
}
.social-field__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 1.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* ── Currency Preview ────────────────── */
.currency-preview {
    text-align: center;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 14px;
    background: linear-gradient(135deg, #1E1E1E 0%, #2a2520 50%, #1E1E1E 100%);
    position: relative;
    overflow: hidden;
}
.currency-preview::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 50%, rgba(196,162,101,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.currency-preview__label {
    font-size: 0.625rem;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: rgba(196,162,101,0.6);
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.currency-preview__value {
    font-family: 'Poppins', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #C4A265;
    letter-spacing: -0.02em;
    transition: all 0.3s ease;
}
.currency-preview__sub {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.3);
    margin-top: 0.375rem;
}

/* ── Stats Preview ───────────────────── */
.stats-preview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.75rem;
    margin-bottom: 2rem;
}
@media (max-width: 640px) {
    .stats-preview { grid-template-columns: repeat(2, 1fr); }
}
.stats-preview__card {
    text-align: center;
    padding: 1.25rem 0.75rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #faf9f7 0%, #f5f3ef 100%);
    border: 1px solid rgba(196,162,101,0.12);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.stats-preview__card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(196,162,101,0.12);
    border-color: rgba(196,162,101,0.25);
}
.stats-preview__number {
    font-family: 'Poppins', sans-serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.02em;
    line-height: 1;
    margin-bottom: 0.375rem;
    transition: color 0.3s ease;
}
.stats-preview__card:hover .stats-preview__number {
    color: #C4A265;
}
.stats-preview__label {
    font-size: 0.6875rem;
    color: #94a3b8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* ── Save Bar ──────────────────────── */
.save-bar {
    margin-top: 1.5rem;
    position: sticky;
    bottom: 1rem;
    z-index: 10;
}
.save-bar__inner {
    background: #fff;
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 16px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.06);
    padding: 0.875rem 1.25rem;
}
.save-bar__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}
.save-bar__hint {
    font-size: 0.75rem;
    color: #94a3b8;
}
.save-bar__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.5rem;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #C4A265 0%, #b8953a 100%);
    border: none;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 8px rgba(196,162,101,0.3);
    white-space: nowrap;
}
.save-bar__btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(196,162,101,0.4);
}
.save-bar__btn:active:not(:disabled) {
    transform: translateY(0);
}
.save-bar__btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.save-bar__success {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #059669;
    padding: 0.5rem 0;
}

/* Save check transition */
.save-check-enter-active {
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.save-check-leave-active {
    transition: all 0.2s ease;
}
.save-check-enter-from {
    opacity: 0;
    transform: scale(0.95);
}
.save-check-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

/* ── Responsive ───────────────────── */
@media (max-width: 640px) {
    .settings-header__content { padding: 1.25rem 1.25rem; }
    .settings-header__title { font-size: 1.25rem; }
    .settings-header__decoration { display: none; }
    .panel-body { padding: 1.25rem; }
    .panel-header { padding: 1rem 1.25rem; }
    .save-bar__hint { display: none; }
    .field-group--muted {
        margin-left: -1.25rem;
        margin-right: -1.25rem;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
    }
}
</style>
