<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditorLazy.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';

const props = defineProps({
    doctor: Object,
    users: Array,
    services: Array,
    pricingSettings: Object,
    modules: Object,
});

const activeSection = ref('basic');

// Quick-create user account form
const showCreateUser = ref(false);
const createUserForm = useForm({
    name: props.doctor.name_en || '',
    email: props.doctor.email || '',
    password: '',
});

function createUserAccount() {
    createUserForm.post(`/admin/doctors/${props.doctor.id}/create-user`, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateUser.value = false;
            createUserForm.reset('password');
        },
    });
}

const sections = [
    { id: 'basic', label: 'Basic Info', key: 'a_basic_info', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'clinic', label: 'Clinic Settings', key: 'a_clinic_settings', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z' },
    { id: 'online', label: 'Online Consultations', key: 'a_online_consultations', icon: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z' },
    { id: 'schedule', label: 'Schedule', key: 'a_schedule', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'vacations', label: 'Vacations', key: 'a_vacations', icon: 'M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
    { id: 'rates', label: 'Service Rates', key: 'a_service_rates', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const dayNames = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Build initial schedules from doctor.schedules (fill missing days)
function buildSchedules() {
    const existing = props.doctor?.schedules || [];
    return dayNames.map((_, i) => {
        const found = existing.find(s => Number(s.day_of_week) === i);
        if (found) {
            // Strip seconds from time (HH:MM:SS → HH:MM)
            const st = (found.start_time || '09:00').substring(0, 5);
            const et = (found.end_time || '17:00').substring(0, 5);
            return {
                day_of_week: i,
                start_time: st,
                end_time: et,
                is_active: !!found.is_active,
                mode: found.mode || 'in_person',
                slot_duration_minutes: found.slot_duration_minutes || 30,
                buffer_minutes: found.buffer_minutes ?? 5,
            };
        }
        return {
            day_of_week: i,
            start_time: '09:00',
            end_time: '17:00',
            is_active: false,
            mode: 'in_person',
            slot_duration_minutes: 30,
            buffer_minutes: 5,
        };
    });
}

// Build initial vacations
function buildVacations() {
    return (props.doctor?.vacations || []).map(v => ({
        id: v.id,
        start_date: v.start_date ? v.start_date.split('T')[0] : '',
        end_date: v.end_date ? v.end_date.split('T')[0] : '',
        reason: v.reason || '',
    }));
}

// Build initial service rates
function buildServiceRates() {
    return (props.doctor?.service_rates || []).map(r => ({
        service_id: r.service_id,
        commission_percentage: r.commission_percentage,
    }));
}

const form = useForm({
    module: props.doctor.module || 'derma',
    // Basic Info
    name_ar: props.doctor.name_ar || '',
    name_en: props.doctor.name_en || '',
    photo: null,
    specialization_ar: props.doctor.specialization_ar || '',
    specialization_en: props.doctor.specialization_en || '',
    bio_ar: props.doctor.bio_ar || '',
    bio_en: props.doctor.bio_en || '',
    qualifications_ar: props.doctor.qualifications_ar || '',
    qualifications_en: props.doctor.qualifications_en || '',
    display_order: props.doctor.display_order || 0,
    status: props.doctor.status || 'active',
    doctor_type: props.doctor.doctor_type || '',
    // Clinic Settings
    user_id: props.doctor.user_id || '',
    phone: props.doctor.phone || '',
    email: props.doctor.email || '',
    consultation_fee: props.doctor.consultation_fee || '',
    dermatology_fee: props.doctor.dermatology_fee || '',
    cosmetic_fee: props.doctor.cosmetic_fee || '',
    default_commission_percentage: props.doctor.default_commission_percentage || '',
    payment_mode: props.doctor.payment_mode || 'payout',
    dermatology_commission: props.doctor.dermatology_commission || '',
    cosmetic_commission: props.doctor.cosmetic_commission || '',
    followup_commission: props.doctor.followup_commission || '',
    dental_consultation_fee: props.doctor.dental_consultation_fee || '',
    dental_service_fee: props.doctor.dental_service_fee || '',
    dental_consultation_commission: props.doctor.dental_consultation_commission || '',
    dental_service_commission: props.doctor.dental_service_commission || '',
    pediatric_consultation_commission: props.doctor.pediatric_consultation_commission || '',
    pediatric_followup_commission: props.doctor.pediatric_followup_commission || '',
    clinic_notes: props.doctor.clinic_notes || '',
    // Online consultations
    online_consultation_enabled: !!props.doctor.online_consultation_enabled,
    online_consultation_fee: props.doctor.online_consultation_fee || '',
    online_session_duration_minutes: props.doctor.online_session_duration_minutes || 30,
    online_consultation_bio_ar: props.doctor.online_consultation_bio_ar || '',
    online_consultation_bio_en: props.doctor.online_consultation_bio_en || '',
    // Nested
    schedules: buildSchedules(),
    vacations: buildVacations(),
    service_rates: buildServiceRates(),
});

function submit() {
    // Helper: convert is_active booleans to 1/0 integers for safe serialization
    const safeSchedules = (schedules) => schedules.map(s => ({
        ...s,
        is_active: s.is_active ? 1 : 0,
    }));

    // Filter out empty/incomplete service rates before submitting
    const cleanServiceRates = (rates) => rates.filter(r => r.service_id && r.commission_percentage !== '' && r.commission_percentage !== null);

    const options = {
        preserveScroll: true,
        onError: (errors) => {
            // Auto-switch to the tab that has errors
            const errorKeys = Object.keys(errors);
            if (errorKeys.some(k => k.startsWith('service_rates'))) {
                activeSection.value = 'rates';
            } else if (errorKeys.some(k => k.startsWith('vacations'))) {
                activeSection.value = 'vacations';
            } else if (errorKeys.some(k => k.startsWith('schedules'))) {
                activeSection.value = 'schedule';
            } else if (errorKeys.some(k => k.startsWith('online_'))) {
                activeSection.value = 'online';
            } else if (errorKeys.some(k => ['user_id', 'phone', 'email', 'doctor_type', 'consultation_fee', 'dermatology_fee', 'cosmetic_fee', 'default_commission_percentage', 'dermatology_commission', 'cosmetic_commission', 'followup_commission', 'clinic_notes'].includes(k))) {
                activeSection.value = 'clinic';
            } else {
                activeSection.value = 'basic';
            }
        },
    };

    // Use direct POST to avoid 403 Forbidden on servers blocking PUT
    form.transform((data) => {
        const payload = { ...data };
        // Remove photo if no new file selected
        if (!(payload.photo instanceof File)) {
            delete payload.photo;
        }
        return {
            ...payload,
            online_consultation_enabled: payload.online_consultation_enabled ? 1 : 0,
            schedules: safeSchedules(payload.schedules),
            service_rates: cleanServiceRates(payload.service_rates),
        };
    }).post(`/admin/doctors/${props.doctor.id}`, {
        forceFormData: true,
        ...options,
    });
}

// Vacations management
function addVacation() {
    form.vacations.push({ id: null, start_date: '', end_date: '', reason: '' });
}

function removeVacation(index) {
    form.vacations.splice(index, 1);
}

// Service rates management
function addServiceRate() {
    form.service_rates.push({ service_id: '', commission_percentage: '' });
}

function removeServiceRate(index) {
    form.service_rates.splice(index, 1);
}

// Filter out services already assigned
const availableServices = computed(() => {
    const usedIds = form.service_rates.map(r => Number(r.service_id));
    return (props.services || []).filter(s => !usedIds.includes(s.id));
});

function getServiceName(serviceId) {
    const svc = (props.services || []).find(s => s.id === Number(serviceId));
    return svc ? svc.name_en : '-';
}

const dermatologyFeeForType = computed(() => {
    if (form.doctor_type === 'consultant') return props.pricingSettings?.dermatology_consultant_fee || 0;
    if (form.doctor_type === 'specialist') return props.pricingSettings?.dermatology_specialist_fee || 0;
    return 0;
});

const dentalFeeForType = computed(() => {
    if (form.doctor_type === 'consultant') return props.pricingSettings?.dental_consultant_fee || 0;
    if (form.doctor_type === 'specialist') return props.pricingSettings?.dental_specialist_fee || 0;
    return 0;
});

const pediatricFeeForType = computed(() => {
    if (form.doctor_type === 'consultant') return props.pricingSettings?.pediatric_consultant_fee || 0;
    if (form.doctor_type === 'specialist') return props.pricingSettings?.pediatric_specialist_fee || 0;
    return 0;
});

const cosmeticServices = computed(() => (props.services || []).filter(s => s.category?.name_en?.toLowerCase().includes('cosmetic') || s.category?.name_en?.toLowerCase().includes('تجميل')));

const userOptions = computed(() => (props.users || []).map(u => ({ value: u.id, label: u.name + ' (' + u.email + ')' })));

function serviceRateOptions(currentIndex) {
    return (props.services || []).filter(svc => svc.module === form.module).map(svc => ({
        value: svc.id,
        label: svc.name_en,
    })).filter(opt => !form.service_rates.some((r, ri) => ri !== currentIndex && Number(r.service_id) === opt.value));
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_doctor')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center ltr:space-x-4 rtl:space-x-reverse rtl:space-x-4">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_doctor') }}</h1>
                    <Link v-if="doctor.id" :href="`/admin/doctors/${doctor.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view_profile') }}</Link>
                </div>
                <Link href="/admin/doctors" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_doctors') }}</Link>
            </div>

            <form @submit.prevent="submit">
                <!-- Section Navigation -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <nav class="flex border-b border-gray-200 overflow-x-auto">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            type="button"
                            @click="activeSection = section.id"
                            class="flex items-center px-5 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap"
                            :class="activeSection === section.id
                                ? 'border-current text-[#C4A265]'
                                : 'border-transparent text-gray-500 hover:text-gray-700'"
                            :style="activeSection === section.id ? 'color: #C4A265;' : ''"
                        >
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" />
                            </svg>
                            {{ section.key ? $t(section.key) : section.label }}
                        </button>
                    </nav>
                </div>

                <!-- ==================== BASIC INFO ==================== -->
                <div v-show="activeSection === 'basic'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <!-- Module Selector -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_module') }}</label>
                                <div class="flex gap-2">
                                    <button
                                        v-for="(mod, slug) in modules"
                                        :key="slug"
                                        type="button"
                                        @click="form.module = slug"
                                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-all duration-200"
                                        :class="form.module === slug ? 'border-transparent text-white shadow-sm' : 'border-gray-200 text-gray-600 hover:border-gray-300'"
                                        :style="form.module === slug ? { backgroundColor: mod.color } : {}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                                        <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                                    </button>
                                </div>
                                <p v-if="form.errors.module" class="mt-1 text-sm text-red-600">{{ form.errors.module }}</p>
                            </div>

                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_names_specialization') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_specialization_en') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_en" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_specialization_ar') }} <span class="text-red-500">*</span></label>
                                    <input v-model="form.specialization_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    <p v-if="form.errors.specialization_ar" class="mt-1 text-sm text-red-600">{{ form.errors.specialization_ar }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_bio_qualifications') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_bio_en') }}</label>
                                <RichTextEditor v-model="form.bio_en" dir="ltr" :placeholder="$t('a_write_bio')" />
                                <p v-if="form.errors.bio_en" class="mt-1 text-sm text-red-600">{{ form.errors.bio_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_bio_ar') }}</label>
                                <RichTextEditor v-model="form.bio_ar" dir="rtl" placeholder="اكتب السيرة الذاتية..." />
                                <p v-if="form.errors.bio_ar" class="mt-1 text-sm text-red-600">{{ form.errors.bio_ar }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_qualifications_en') }}</label>
                                    <textarea v-model="form.qualifications_en" rows="4" :placeholder="$t('a_one_qualification_per_line')" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_qualifications_ar') }}</label>
                                    <textarea v-model="form.qualifications_ar" rows="4" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_status_order') }}</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                                <select v-model="form.status" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                    <option value="active">{{ $t('a_active') }}</option>
                                    <option value="inactive">{{ $t('a_inactive') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                                <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_photo') }}</h3>
                            <div v-if="doctor.photo" class="mb-3">
                                <img :src="doctor.photo.startsWith('http') ? doctor.photo : `/storage/${doctor.photo}`" alt="Doctor photo" class="w-24 h-24 object-cover rounded-lg" />
                            </div>
                            <input
                                type="file"
                                accept="image/*"
                                @input="form.photo = $event.target.files[0]"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                            />
                            <p class="text-xs text-gray-400">{{ $t('a_keep_current_photo') }}</p>
                            <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">{{ form.errors.photo }}</p>
                        </div>
                    </div>
                </div>

                <!-- ==================== CLINIC SETTINGS ==================== -->
                <div v-show="activeSection === 'clinic'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_contact_account') }}</h3>

                        <!-- Linked User Info or Create Button -->
                        <div v-if="doctor.user_id">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_link_to_user') }}</label>
                            <SearchableSelect v-model="form.user_id" :options="userOptions" placeholder="-- No user linked --" searchPlaceholder="Search users..." />
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_link_user_edit_hint') }}</p>
                            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
                            <div class="mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-sm font-medium text-emerald-700">{{ $t('a_user_account_linked') }} <code class="bg-emerald-100 px-1 rounded">/doctor/login</code></span>
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_link_to_user') }}</label>
                            <SearchableSelect v-model="form.user_id" :options="userOptions" placeholder="-- No user linked --" searchPlaceholder="Search users..." />
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_link_user_or_create') }}</p>
                            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>

                            <!-- Quick Create User Account -->
                            <div class="mt-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                        <span class="text-sm font-medium text-amber-700">{{ $t('a_no_user_linked') }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="showCreateUser = !showCreateUser"
                                        class="text-xs font-semibold px-3 py-1 rounded-lg transition"
                                        :class="showCreateUser ? 'bg-gray-200 text-gray-600' : 'bg-[#C4A265] text-white hover:bg-[#A68B52]'"
                                    >
                                        {{ showCreateUser ? $t('a_cancel') : '+ ' + $t('a_quick_create_account') }}
                                    </button>
                                </div>

                                <div v-if="showCreateUser" class="mt-3 pt-3 border-t border-amber-200 space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_account_name') }}</label>
                                        <input v-model="createUserForm.name" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                        <p v-if="createUserForm.errors.name" class="mt-1 text-xs text-red-600">{{ createUserForm.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_login_email') }}</label>
                                        <input v-model="createUserForm.email" type="email" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                        <p v-if="createUserForm.errors.email" class="mt-1 text-xs text-red-600">{{ createUserForm.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_password') }}</label>
                                        <input v-model="createUserForm.password" type="text" :placeholder="$t('a_enter_password_for_doctor')" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                        <p v-if="createUserForm.errors.password" class="mt-1 text-xs text-red-600">{{ createUserForm.errors.password }}</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="createUserAccount"
                                        :disabled="createUserForm.processing"
                                        class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg transition disabled:opacity-50"
                                        style="background-color: #C4A265;"
                                    >
                                        {{ createUserForm.processing ? $t('a_creating_account_dots') : $t('a_create_account_link') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_phone') }}</label>
                                <input v-model="form.phone" type="text" placeholder="01xxxxxxxxx" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_email') }}</label>
                                <input v-model="form.email" type="email" placeholder="doctor@clinic.com" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <!-- Doctor Type & Applicable Fees -->
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_doctor_type_fees') }}</h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_doctor_type') }} <span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label
                                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="form.doctor_type === 'consultant'
                                            ? 'border-[#C4A265] bg-[#C4A265]/5 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <input type="radio" v-model="form.doctor_type" value="consultant" class="sr-only" />
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="form.doctor_type === 'consultant' ? 'bg-[#C4A265]/15' : 'bg-gray-100'">
                                            <svg class="w-5 h-5" :class="form.doctor_type === 'consultant' ? 'text-[#C4A265]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold" :class="form.doctor_type === 'consultant' ? 'text-[#C4A265]' : 'text-gray-700'">{{ $t('a_consultant') }}</span>
                                            <span class="block text-xs text-gray-400">{{ locale === 'ar' ? 'Consultant' : 'استشاري' }}</span>
                                        </div>
                                        <div v-if="form.doctor_type === 'consultant'" class="absolute top-2 right-2">
                                            <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </label>
                                    <label
                                        class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="form.doctor_type === 'specialist'
                                            ? 'border-[#1B365D] bg-slate-50/50 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <input type="radio" v-model="form.doctor_type" value="specialist" class="sr-only" />
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" :class="form.doctor_type === 'specialist' ? 'bg-slate-100' : 'bg-gray-100'">
                                            <svg class="w-5 h-5" :class="form.doctor_type === 'specialist' ? 'text-[#1B365D]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold" :class="form.doctor_type === 'specialist' ? 'text-[#1B365D]' : 'text-gray-700'">{{ $t('a_specialist') }}</span>
                                            <span class="block text-xs text-gray-400">{{ locale === 'ar' ? 'Specialist' : 'اخصائي' }}</span>
                                        </div>
                                        <div v-if="form.doctor_type === 'specialist'" class="absolute top-2 right-2">
                                            <svg class="w-4 h-4 text-[#1B365D]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="form.errors.doctor_type" class="mt-1 text-sm text-red-600">{{ form.errors.doctor_type }}</p>
                            </div>

                            <!-- Applicable Fees from Settings (read-only info) -->
                            <div v-if="form.doctor_type && form.module === 'derma'" class="p-4 rounded-xl border border-dashed space-y-2"
                                 :class="form.doctor_type === 'consultant' ? 'border-[#C4A265]/40 bg-[#C4A265]/5' : 'border-slate-300 bg-slate-50/30'">
                                <h4 class="text-xs font-semibold uppercase tracking-wider" :class="form.doctor_type === 'consultant' ? 'text-[#C4A265]' : 'text-[#1B365D]'">
                                    Applicable Consultation Fees (from Settings)
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_derm_consultation') }}</p>
                                        <p class="text-xs text-gray-400">كشف جلدية</p>
                                        <p class="text-lg font-bold" :class="form.doctor_type === 'consultant' ? 'text-[#C4A265]' : 'text-[#1B365D]'">{{ dermatologyFeeForType }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_cosmetic_consult') }}</p>
                                        <p class="text-xs text-gray-400">استشارة تجميل</p>
                                        <p class="text-lg font-bold text-[#1B365D]">{{ pricingSettings?.cosmetic_consultation_fee || 0 }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ $t('a_follow_up_type') }}</p>
                                        <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'Follow-up' : 'متابعة' }}</p>
                                        <p class="text-lg font-bold text-emerald-600">{{ pricingSettings?.followup_fee || 0 }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 text-center">{{ $t('a_fees_managed_in_settings') }}</p>
                            </div>
                            <!-- Dental Fees -->
                            <div v-if="form.module === 'dental' && form.doctor_type" class="p-4 rounded-xl border border-dashed border-slate-300 bg-slate-50/30 space-y-3">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-[#1B365D]">
                                    {{ locale === 'ar' ? 'رسوم استشارات الأسنان المطبقة (من الإعدادات)' : 'Applicable Dental Consultation Fees (from Settings)' }}
                                </h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ locale === 'ar' ? 'كشف أسنان' : 'Dental Consultation' }}</p>
                                        <p class="text-xs text-gray-400">{{ form.doctor_type === 'consultant' ? (locale === 'ar' ? 'استشاري' : 'Consultant') : (locale === 'ar' ? 'أخصائي' : 'Specialist') }}</p>
                                        <p class="text-lg font-bold text-[#1B365D]">{{ dentalFeeForType }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ locale === 'ar' ? 'رسم خدمة الأسنان' : 'Dental Service Fee' }}</p>
                                        <p class="text-xs text-gray-400">&nbsp;</p>
                                        <div class="relative">
                                            <input v-model="form.dental_service_fee" type="number" step="0.01" min="0" placeholder="0" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-center focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">{{ currencyCode }}</span>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 text-center">{{ locale === 'ar' ? 'رسوم الكشف تُدار من الإعدادات العامة' : 'Consultation fees are managed in general settings' }}</p>
                            </div>
                            <!-- Pediatric Fees -->
                            <div v-if="form.module === 'pediatric' && form.doctor_type" class="p-4 rounded-xl border border-dashed border-emerald-300 bg-emerald-50/30 space-y-3">
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
                                    {{ locale === 'ar' ? 'رسوم استشارات الأطفال المطبقة (من الإعدادات)' : 'Applicable Pediatric Consultation Fees (from Settings)' }}
                                </h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ locale === 'ar' ? 'كشف أطفال' : 'Pediatric Consultation' }}</p>
                                        <p class="text-xs text-gray-400">{{ form.doctor_type === 'consultant' ? (locale === 'ar' ? 'استشاري' : 'Consultant') : (locale === 'ar' ? 'أخصائي' : 'Specialist') }}</p>
                                        <p class="text-lg font-bold text-emerald-600">{{ pediatricFeeForType }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                    <div class="text-center p-2 bg-white/80 rounded-lg">
                                        <p class="text-xs text-gray-400 mb-0.5">{{ locale === 'ar' ? 'رسوم المتابعة' : 'Follow-up Fee' }}</p>
                                        <p class="text-xs text-gray-400">&nbsp;</p>
                                        <p class="text-lg font-bold text-emerald-600">{{ pricingSettings?.pediatric_followup_fee || 0 }} <span class="text-xs font-normal text-gray-400">{{ currencyCode }}</span></p>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 text-center">{{ locale === 'ar' ? 'رسوم الكشف تُدار من الإعدادات العامة' : 'Consultation fees are managed in general settings' }}</p>
                            </div>
                            <div v-if="!form.doctor_type" class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                <p class="text-xs text-amber-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                                    Please select a doctor type to see applicable consultation fees and configure commission rates.
                                </p>
                            </div>
                        </div>

                        <!-- Commission Rates -->
                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_commission_rates') }}</h3>
                            <p class="text-xs text-gray-400 -mt-3">{{ $t('a_commission_desc') }}</p>

                            <div class="space-y-3">
                                <!-- Derma Commission Rates -->
                                <template v-if="form.module === 'derma'">
                                <!-- Dermatology Consultation Commission -->
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-gray-700">{{ $t('a_dermatology_consultation') }}</label>
                                        <p class="text-xs text-gray-400">كشف جلدية</p>
                                    </div>
                                    <div class="w-28">
                                        <div class="relative">
                                            <input v-model="form.dermatology_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cosmetic Consultation Commission -->
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-gray-700">{{ $t('a_cosmetic_consultation') }}</label>
                                        <p class="text-xs text-gray-400">استشارة تجميل</p>
                                    </div>
                                    <div class="w-28">
                                        <div class="relative">
                                            <input v-model="form.cosmetic_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Follow-up Commission -->
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-gray-700">{{ $t('a_follow_up_type') }}</label>
                                        <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'Follow-up' : 'متابعة جلدية' }}</p>
                                    </div>
                                    <div class="w-28">
                                        <div class="relative">
                                            <input v-model="form.followup_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                        </div>
                                    </div>
                                </div>
                                </template>

                                <!-- Dental Commission Rates -->
                                <template v-if="form.module === 'dental'">
                                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-semibold text-gray-700">{{ $t('a_dental_consultation') }}</label>
                                            <p class="text-xs text-gray-400">كشف اسنان</p>
                                        </div>
                                        <div class="w-28">
                                            <div class="relative">
                                                <input v-model="form.dental_consultation_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                        <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.658-5.66A4.022 4.022 0 013 6.476V5a1 1 0 011-1h2.476c1.064 0 2.084.423 2.836 1.176l5.658 5.66M11.42 15.17l2.496-2.496M11.42 15.17l4.243 4.243a2 2 0 002.828 0l.586-.586a2 2 0 000-2.828L14.83 11.758M14.83 11.758L18 8.586a2 2 0 000-2.828l-.586-.586a2 2 0 00-2.828 0L11.42 8.414" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-semibold text-gray-700">{{ $t('a_dental_service_comm') }}</label>
                                            <p class="text-xs text-gray-400">خدمات وعلاجات اسنان</p>
                                        </div>
                                        <div class="w-28">
                                            <div class="relative">
                                                <input v-model="form.dental_service_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-slate-300 focus:border-slate-400" />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Pediatric Commission Rates -->
                                <template v-if="form.module === 'pediatric'">
                                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8.25a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM6.75 12a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75H6.75zm10.5 0a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75h-.008zM12 10.5c-3.315 0-6 2.685-6 6v3a.75.75 0 00.75.75h10.5a.75.75 0 00.75-.75v-3c0-3.315-2.685-6-6-6z" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-semibold text-gray-700">{{ locale === 'ar' ? 'عمولة استشارة الأطفال' : 'Pediatric Consultation Commission' }}</label>
                                            <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'Consultation' : 'كشف أطفال' }}</p>
                                        </div>
                                        <div class="w-28">
                                            <div class="relative">
                                                <input v-model="form.pediatric_consultation_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]" />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50/50">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-semibold text-gray-700">{{ locale === 'ar' ? 'عمولة المتابعة' : 'Follow-up Commission' }}</label>
                                            <p class="text-xs text-gray-400">{{ locale === 'ar' ? 'Follow-up' : 'متابعة أطفال' }}</p>
                                        </div>
                                        <div class="w-28">
                                            <div class="relative">
                                                <input v-model="form.pediatric_followup_commission" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]" />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Default / Fallback Commission -->
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-dashed border-gray-300 bg-gray-50/30">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-gray-700">{{ $t('a_default_rate') }}</label>
                                        <p class="text-xs text-gray-400">{{ $t('a_fallback_any_service') }}</p>
                                    </div>
                                    <div class="w-28">
                                        <div class="relative">
                                            <input v-model="form.default_commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0" class="doctorato-input w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm ltr:text-right rtl:text-left font-semibold focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 font-medium">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment mode (hybrid: payout vs salary) -->
                                <div class="flex items-center justify-between gap-4 pt-3 mt-3 border-t border-gray-100">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">{{ isRtl ? 'طريقة دفع العمولة' : 'Commission Payment' }}</label>
                                        <p class="text-xs text-gray-400">
                                            {{ form.payment_mode === 'salary'
                                                ? (isRtl ? 'تُضاف لقسيمة الراتب الشهرية (موظّف)' : 'Added to the monthly salary slip (employee)')
                                                : (isRtl ? 'تُصرف عبر مستحقات الأطباء (متعاقد)' : 'Disbursed via Doctor Payouts (contractor)') }}
                                        </p>
                                    </div>
                                    <select v-model="form.payment_mode" class="doctorato-input w-44 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                        <option value="payout">{{ isRtl ? 'مستحقات (متعاقد)' : 'Payout (contractor)' }}</option>
                                        <option value="salary">{{ isRtl ? 'راتب (موظّف)' : 'Salary (employee)' }}</option>
                                    </select>
                                </div>
                            </div>

                            <p v-if="form.errors.dermatology_commission" class="text-sm text-red-600">{{ form.errors.dermatology_commission }}</p>
                            <p v-if="form.errors.cosmetic_commission" class="text-sm text-red-600">{{ form.errors.cosmetic_commission }}</p>
                            <p v-if="form.errors.followup_commission" class="text-sm text-red-600">{{ form.errors.followup_commission }}</p>
                            <p v-if="form.errors.default_commission_percentage" class="text-sm text-red-600">{{ form.errors.default_commission_percentage }}</p>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_clinic_notes') }}</h3>
                            <textarea v-model="form.clinic_notes" rows="5" :placeholder="$t('a_internal_notes_hint')" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.clinic_notes" class="mt-1 text-sm text-red-600">{{ form.errors.clinic_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- ==================== ONLINE CONSULTATIONS ==================== -->
                <div v-show="activeSection === 'online'">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-6">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1">
                                {{ locale === 'ar' ? 'الاستشارات الأونلاين' : 'Online Consultations' }}
                            </h3>
                            <p class="text-xs text-gray-400">
                                {{ locale === 'ar'
                                    ? 'فعّل هذه الخدمة ليستقبل هذا الطبيب استشارات فيديو أونلاين.'
                                    : 'Enable this service so the doctor can accept online video consultations.' }}
                            </p>
                        </div>

                        <!-- Toggle -->
                        <label class="flex items-center gap-3 p-4 rounded-xl border transition cursor-pointer"
                            :class="form.online_consultation_enabled ? 'border-emerald-300 bg-emerald-50/60' : 'border-gray-200 bg-gray-50'">
                            <input type="checkbox" v-model="form.online_consultation_enabled"
                                class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-[#C4A265]/30" />
                            <div class="flex-1">
                                <span class="block text-sm font-semibold"
                                    :class="form.online_consultation_enabled ? 'text-emerald-700' : 'text-gray-600'">
                                    {{ locale === 'ar' ? 'تفعيل الاستشارات الأونلاين' : 'Enable online consultations for this doctor' }}
                                </span>
                                <span class="block text-xs text-gray-400 mt-0.5">
                                    {{ locale === 'ar'
                                        ? 'سيظهر الطبيب للمرضى في صفحة الحجز الأونلاين.'
                                        : 'Doctor will be listed for patients on the online booking page.' }}
                                </span>
                            </div>
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4"
                             :class="!form.online_consultation_enabled && 'opacity-50 pointer-events-none'">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    {{ locale === 'ar' ? 'رسوم الاستشارة الأونلاين' : 'Online consultation fee' }}
                                    <span class="text-gray-400 font-normal">({{ currencyCode }})</span>
                                </label>
                                <input v-model="form.online_consultation_fee" type="number" step="0.01" min="0" placeholder="0"
                                    class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                <p v-if="form.errors.online_consultation_fee" class="mt-1 text-xs text-red-600">{{ form.errors.online_consultation_fee }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    {{ locale === 'ar' ? 'مدة الجلسة' : 'Session duration' }}
                                </label>
                                <select v-model.number="form.online_session_duration_minutes"
                                    class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                    <option :value="15">15 {{ locale === 'ar' ? 'دقيقة' : 'minutes' }}</option>
                                    <option :value="20">20 {{ locale === 'ar' ? 'دقيقة' : 'minutes' }}</option>
                                    <option :value="30">30 {{ locale === 'ar' ? 'دقيقة' : 'minutes' }}</option>
                                    <option :value="45">45 {{ locale === 'ar' ? 'دقيقة' : 'minutes' }}</option>
                                    <option :value="60">60 {{ locale === 'ar' ? 'دقيقة' : 'minutes' }}</option>
                                </select>
                                <p v-if="form.errors.online_session_duration_minutes" class="mt-1 text-xs text-red-600">{{ form.errors.online_session_duration_minutes }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4"
                             :class="!form.online_consultation_enabled && 'opacity-50 pointer-events-none'">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    {{ locale === 'ar' ? 'نبذة الاستشارة (عربي)' : 'Online consultation bio (Arabic)' }}
                                </label>
                                <textarea v-model="form.online_consultation_bio_ar" rows="4" dir="rtl"
                                    :placeholder="locale === 'ar' ? 'نبذة مختصرة عن الطبيب لعرضها في صفحة الحجز الأونلاين...' : 'Short doctor bio shown on the online booking page'"
                                    class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                                <p v-if="form.errors.online_consultation_bio_ar" class="mt-1 text-xs text-red-600">{{ form.errors.online_consultation_bio_ar }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">
                                    {{ locale === 'ar' ? 'نبذة الاستشارة (إنجليزي)' : 'Online consultation bio (English)' }}
                                </label>
                                <textarea v-model="form.online_consultation_bio_en" rows="4" dir="ltr"
                                    placeholder="Short bio shown on the online booking page"
                                    class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                                <p v-if="form.errors.online_consultation_bio_en" class="mt-1 text-xs text-red-600">{{ form.errors.online_consultation_bio_en }}</p>
                            </div>
                        </div>

                        <div v-if="form.online_consultation_enabled" class="p-3 rounded-lg bg-slate-50/70 border border-slate-200 text-xs text-[#1B365D] flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                {{ locale === 'ar'
                                    ? 'للسماح بالحجز، تأكد أيضاً من تحديد وضع "أونلاين" أو "كلاهما" لأحد أيام الجدول الأسبوعي.'
                                    : 'To accept online bookings, make sure at least one weekly schedule day is set to "Online" or "Both".' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ==================== SCHEDULE ==================== -->
                <div v-show="activeSection === 'schedule'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">{{ $t('a_weekly_schedule') }}</h3>
                        <p class="text-xs text-gray-400 mb-4">{{ $t('a_toggle_schedule_hint') }}</p>
                        <div class="space-y-3">
                            <div
                                v-for="(schedule, i) in form.schedules"
                                :key="i"
                                class="p-3 rounded-lg border transition"
                                :class="schedule.is_active ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-gray-50'"
                            >
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="flex items-center cursor-pointer min-w-[140px]">
                                        <input
                                            type="checkbox"
                                            v-model="schedule.is_active"
                                            class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-[#C4A265]/30 ltr:mr-3 rtl:ml-3"
                                        />
                                        <span class="text-sm font-medium" :class="schedule.is_active ? 'text-gray-900' : 'text-gray-400'">
                                            {{ dayNames[i] }}
                                        </span>
                                    </label>
                                    <div class="flex items-center gap-2" :class="!schedule.is_active && 'opacity-40 pointer-events-none'">
                                        <input
                                            v-model="schedule.start_time"
                                            type="time"
                                            class="doctorato-input px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                                        />
                                        <span class="text-gray-400">{{ $t('a_to') }}</span>
                                        <input
                                            v-model="schedule.end_time"
                                            type="time"
                                            class="doctorato-input px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                                <div
                                    class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3"
                                    :class="!schedule.is_active && 'opacity-40 pointer-events-none'"
                                >
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                                            {{ locale === 'ar' ? 'وضع العمل' : 'Mode' }}
                                        </label>
                                        <select v-model="schedule.mode"
                                            class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]">
                                            <option value="in_person">{{ locale === 'ar' ? 'عيادة فقط' : 'Clinic Only' }}</option>
                                            <option value="online">{{ locale === 'ar' ? 'أونلاين فقط' : 'Online Only' }}</option>
                                            <option value="both">{{ locale === 'ar' ? 'كلاهما' : 'Both' }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                                            {{ locale === 'ar' ? 'مدة الموعد (دقيقة)' : 'Slot duration (min)' }}
                                        </label>
                                        <input v-model.number="schedule.slot_duration_minutes" type="number" min="5" max="240" placeholder="30"
                                            class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-1">
                                            {{ locale === 'ar' ? 'الفاصل بين المواعيد (دقيقة)' : 'Buffer (min)' }}
                                        </label>
                                        <input v-model.number="schedule.buffer_minutes" type="number" min="0" max="120" placeholder="5"
                                            class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== VACATIONS ==================== -->
                <div v-show="activeSection === 'vacations'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_vacations_time_off') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">{{ $t('a_vacations_time_off') }}</p>
                            </div>
                            <button
                                type="button"
                                @click="addVacation"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-white text-xs font-medium transition"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('a_add_vacation') }}
                            </button>
                        </div>

                        <div v-if="form.vacations.length > 0" class="space-y-3">
                            <div
                                v-for="(vacation, i) in form.vacations"
                                :key="i"
                                class="flex items-start gap-4 p-4 rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_start_date') }}</label>
                                        <input v-model="vacation.start_date" type="date" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_end_date') }}</label>
                                        <input v-model="vacation.end_date" type="date" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_reason') }}</label>
                                        <input v-model="vacation.reason" type="text" placeholder="Optional" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeVacation(i)"
                                    class="mt-5 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Remove"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_vacations') }}</p>
                    </div>
                </div>

                <!-- ==================== SERVICE RATES ==================== -->
                <div v-show="activeSection === 'rates'">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_per_service_commission_rates') }}</h3>
                                <p class="text-xs text-gray-400 mt-1">Set custom commission rates for individual cosmetic services. If not set here, the default rate ({{ form.default_commission_percentage || 0 }}%) applies.</p>
                            </div>
                            <button
                                type="button"
                                @click="addServiceRate"
                                :disabled="availableServices.length === 0"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-white text-xs font-medium transition disabled:opacity-50"
                                style="background-color: #C4A265;"
                            >
                                <svg class="w-3.5 h-3.5 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('a_add_rate') }}
                            </button>
                        </div>

                        <div v-if="form.service_rates.length > 0" class="space-y-3">
                            <div
                                v-for="(rate, i) in form.service_rates"
                                :key="i"
                                class="flex items-center gap-4 p-4 rounded-lg border border-gray-200 bg-gray-50"
                            >
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_service') }}</label>
                                        <SearchableSelect v-model="rate.service_id" :options="serviceRateOptions(i)" placeholder="-- Select Service --" searchPlaceholder="Search services..." />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_commission_rate') }} (%)</label>
                                        <input v-model="rate.commission_percentage" type="number" step="0.01" min="0" max="100" placeholder="0.00" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeServiceRate(i)"
                                    class="mt-5 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                    title="Remove"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-400 text-center py-8">{{ $t('a_no_custom_rates_default_edit') }}</p>
                    </div>
                </div>

                <!-- ==================== SAVE BUTTON (always visible) ==================== -->
                <div class="mt-6 flex items-center justify-between bg-white rounded-lg shadow-sm p-4">
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600 font-medium">{{ $t('a_saved_successfully') }}</p>
                    <p v-else-if="Object.keys(form.errors).length > 0" class="text-sm text-red-600 font-medium">
                        {{ $t('a_fix_errors_before_saving') }} ({{ Object.keys(form.errors).length }})
                    </p>
                    <span v-else></span>
                    <div class="flex ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                        <Link href="/admin/doctors" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 md:px-6 py-2.5 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving_dots') : $t('a_save_all_changes') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
