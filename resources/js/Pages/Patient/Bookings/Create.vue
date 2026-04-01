<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    patient: Object,
    categories: Array,
    doctors: Array,
    modules: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

/* Module support */
const activeModules = computed(() => {
    if (!props.modules) return [];
    return Object.values(props.modules).filter(m => m.enabled);
});
const hasMultipleModules = computed(() => activeModules.value.length > 1);
const selectedModule = ref('');

// Auto-select if only one module
if (activeModules.value.length === 1) {
    selectedModule.value = activeModules.value[0].slug;
}

const form = useForm({
    booking_type: '',
    module: '',
    service_id: '',
    doctor_id: '',
    preferred_date: '',
    preferred_time: '',
    notes: '',
    promo_code: '',
});

/* Booking types per module */
const bookingTypes = computed(() => {
    if (selectedModule.value === 'dental') {
        return [
            { value: 'dental_consultation', label: isRtl.value ? 'كشف أسنان' : 'Dental Consultation', desc: isRtl.value ? 'فحص وتشخيص مشاكل الأسنان' : 'Dental examination and diagnosis' },
            { value: 'dental_service', label: isRtl.value ? 'خدمة أسنان' : 'Dental Service', desc: isRtl.value ? 'حجز علاج أو إجراء لطب الأسنان' : 'Book a dental treatment or procedure' },
        ];
    }
    return [
        { value: 'dermatology_consultation', label: isRtl.value ? 'استشارة جلدية' : 'Dermatology Consultation', desc: isRtl.value ? 'فحص وتشخيص مشاكل الجلد' : 'Skin examination and diagnosis' },
        { value: 'cosmetic_consultation', label: isRtl.value ? 'استشارة تجميلية' : 'Cosmetic Consultation', desc: isRtl.value ? 'استشارة عمليات التجميل والعناية' : 'Cosmetic procedure consultation' },
        { value: 'service', label: isRtl.value ? 'حجز خدمة' : 'Book a Service', desc: isRtl.value ? 'حجز خدمة علاجية أو تجميلية' : 'Book a treatment or cosmetic service' },
    ];
});

const isConsultation = computed(() => ['dermatology_consultation', 'cosmetic_consultation', 'dental_consultation'].includes(form.booking_type));
const isService = computed(() => ['service', 'dental_service'].includes(form.booking_type));

/* Filter services and doctors by module */
const filteredCategories = computed(() => {
    if (!selectedModule.value || !props.categories) return props.categories || [];
    return props.categories.map(cat => ({
        ...cat,
        services: (cat.services || []).filter(s => !s.module || s.module === selectedModule.value),
    })).filter(cat => cat.services.length > 0);
});

const filteredDoctors = computed(() => {
    if (!selectedModule.value || !props.doctors) return props.doctors || [];
    return props.doctors.filter(d => !d.module || d.module === selectedModule.value);
});

/* Reset form when switching module */
function selectDepartment(slug) {
    selectedModule.value = slug;
    form.booking_type = '';
    form.service_id = '';
    form.doctor_id = '';
    form.module = slug;
}

watch(() => form.booking_type, () => {
    if (isConsultation.value) {
        form.service_id = '';
    }
});

function submit() {
    form.module = selectedModule.value;
    form.post(lp('/bookings'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/bookings')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'حجز موعد' : 'Book Appointment' }}</h1>
        </div>

        <!-- Department Selection (when multiple modules active) -->
        <div v-if="hasMultipleModules && !selectedModule" class="mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">{{ isRtl ? 'اختر القسم' : 'Select Department' }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                    v-for="mod in activeModules"
                    :key="mod.slug"
                    @click="selectDepartment(mod.slug)"
                    class="group relative bg-white rounded-2xl border-2 border-gray-100 p-6 text-center hover:shadow-lg transition-all duration-300"
                    :class="{ 'ring-2': false }"
                >
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl"
                        :style="{ backgroundColor: mod.color + '15' }">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-1">{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</h3>
                    <p class="text-sm text-gray-500">
                        {{ mod.slug === 'derma' ? (isRtl ? 'العناية بالبشرة والتجميل وعلاج الأمراض الجلدية' : 'Skin care, cosmetics & dermatology treatments') : (isRtl ? 'العناية بالأسنان والعلاجات وصحة الفم' : 'Dental care, treatments & oral health') }}
                    </p>
                    <div class="absolute inset-0 rounded-2xl border-2 border-transparent group-hover:border-current opacity-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none"
                        :style="{ borderColor: mod.color }"></div>
                </button>
            </div>
        </div>

        <!-- Module chip (when selected, allow changing) -->
        <div v-if="hasMultipleModules && selectedModule" class="mb-6 flex items-center gap-2">
            <span v-for="mod in activeModules" :key="mod.slug"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium cursor-pointer transition-all duration-200"
                :class="selectedModule === mod.slug ? 'text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                :style="selectedModule === mod.slug ? { backgroundColor: mod.color } : {}"
                @click="selectDepartment(mod.slug)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
            </span>
        </div>

        <!-- Booking Form (shown after department selection) -->
        <div v-if="selectedModule" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Booking Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ isRtl ? 'نوع الحجز' : 'Booking Type' }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    v-for="bt in bookingTypes"
                                    :key="bt.value"
                                    type="button"
                                    @click="form.booking_type = bt.value"
                                    class="p-4 rounded-xl border-2 text-start transition-all duration-200"
                                    :class="form.booking_type === bt.value ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)]/5' : 'border-gray-100 hover:border-gray-200 bg-gray-50'"
                                >
                                    <span class="block text-sm font-semibold" :class="form.booking_type === bt.value ? 'text-[var(--brand-primary)]' : 'text-gray-800'">{{ bt.label }}</span>
                                    <span class="block text-xs text-gray-400 mt-0.5">{{ bt.desc }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.booking_type" class="mt-1.5 text-sm text-red-500">{{ form.errors.booking_type }}</p>
                        </div>

                        <!-- Service (grouped by category) — only for service types -->
                        <div v-if="isService">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الخدمة' : 'Service' }}</label>
                            <select
                                v-model="form.service_id"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                :class="form.errors.service_id ? 'border-red-300' : ''"
                            >
                                <option value="">{{ isRtl ? 'اختر الخدمة' : 'Select service' }}</option>
                                <optgroup v-for="cat in filteredCategories" :key="cat.id" :label="$localized(cat, 'name')">
                                    <option v-for="svc in cat.services" :key="svc.id" :value="svc.id">{{ $localized(svc, 'name') }}</option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.service_id" class="mt-1.5 text-sm text-red-500">{{ form.errors.service_id }}</p>
                        </div>

                        <!-- Doctor -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                            <select
                                v-model="form.doctor_id"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                :class="form.errors.doctor_id ? 'border-red-300' : ''"
                            >
                                <option value="">{{ isRtl ? 'اختر الطبيب' : 'Select doctor' }}</option>
                                <option v-for="doc in filteredDoctors" :key="doc.id" :value="doc.id">
                                    {{ $localized(doc, 'name') }} — {{ $localized(doc, 'specialization') }}
                                </option>
                            </select>
                            <p v-if="form.errors.doctor_id" class="mt-1.5 text-sm text-red-500">{{ form.errors.doctor_id }}</p>
                        </div>

                        <!-- Date & Time -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'التاريخ المفضل' : 'Preferred Date' }}</label>
                                <input
                                    v-model="form.preferred_date"
                                    type="date"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                    :class="form.errors.preferred_date ? 'border-red-300' : ''"
                                />
                                <p v-if="form.errors.preferred_date" class="mt-1.5 text-sm text-red-500">{{ form.errors.preferred_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'الوقت المفضل' : 'Preferred Time' }}</label>
                                <input
                                    v-model="form.preferred_time"
                                    type="time"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                    :class="form.errors.preferred_time ? 'border-red-300' : ''"
                                />
                                <p v-if="form.errors.preferred_time" class="mt-1.5 text-sm text-red-500">{{ form.errors.preferred_time }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all resize-none"
                                :placeholder="isRtl ? 'أي ملاحظات إضافية...' : 'Any additional notes...'"
                            ></textarea>
                        </div>

                        <!-- Promo Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ isRtl ? 'رمز الخصم' : 'Promo Code' }}</label>
                            <input
                                v-model="form.promo_code"
                                type="text"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/50 focus:border-[var(--brand-primary)]/50 transition-all"
                                :placeholder="isRtl ? 'أدخل رمز الخصم (اختياري)' : 'Enter promo code (optional)'"
                            />
                            <p v-if="form.errors.promo_code" class="mt-1.5 text-sm text-red-500">{{ form.errors.promo_code }}</p>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:from-[var(--brand-primary-hover)] hover:to-[var(--brand-primary)] transition-all duration-300 disabled:opacity-50 shadow-lg shadow-[var(--brand-primary)]/20"
                        >
                            <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ isRtl ? 'جاري الحجز...' : 'Booking...' }}
                            </span>
                            <span v-else>{{ isRtl ? 'تأكيد الحجز' : 'Confirm Booking' }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Patient Info Sidebar -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">{{ isRtl ? 'معلومات المريض' : 'Patient Info' }}</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'الاسم' : 'Name' }}</span>
                            <p class="font-medium text-gray-800">{{ patient?.full_name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'رقم الملف' : 'File Number' }}</span>
                            <p class="font-medium text-gray-800">{{ patient?.file_number || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'الهاتف' : 'Phone' }}</span>
                            <p class="font-medium text-gray-800">{{ patient?.phone || '—' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'البريد الإلكتروني' : 'Email' }}</span>
                            <p class="font-medium text-gray-800">{{ patient?.email || '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
