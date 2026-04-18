<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { currencyCode } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patient: Object,
    patients: Array,
    doctors: Array,
    itemTypes: Array,
    materials: Array,
});

const form = ref({
    patient_id: props.patient?.id || '',
    doctor_id: '',
    item_type: '',
    tooth_number: '',
    shade: '',
    material: '',
    cost: '',
    patient_charge: '',
    lab_name: '',
    order_number: '',
    order_date: new Date().toISOString().slice(0, 10),
    expected_date: '',
    notes: '',
    special_instructions: '',
});

const errors = ref({});
const processing = ref(false);
function submit() {
    processing.value = true;
    errors.value = {};
    router.post('/admin/dental/lab-orders', form.value, {
        onError: (errs) => { errors.value = errs; },
        onFinish: () => { processing.value = false; },
    });
}

/* ── Progress tracking ─────────────────── */
const progressPercent = computed(() => {
    const fields = ['patient_id', 'item_type', 'cost', 'order_date'];
    const filled = fields.filter(f => form.value[f] !== '' && form.value[f] !== null).length;
    return Math.round((filled / fields.length) * 100);
});

/* ── Searchable Dropdowns ──────────────── */
// Patient
const showPatientDropdown = ref(false);
const patientSearch = ref('');
const filteredPatients = computed(() => {
    if (!props.patients) return [];
    const q = patientSearch.value.toLowerCase();
    if (!q) return props.patients;
    return props.patients.filter(p =>
        p.full_name.toLowerCase().includes(q) ||
        (p.file_number && p.file_number.toLowerCase().includes(q))
    );
});
const selectedPatientLabel = computed(() => {
    if (!form.value.patient_id) return '';
    const p = props.patients?.find(x => x.id === form.value.patient_id);
    return p ? `${p.full_name} (${p.file_number})` : '';
});
function selectPatient(p) {
    form.value.patient_id = p.id;
    showPatientDropdown.value = false;
    patientSearch.value = '';
}

// Doctor
const showDoctorDropdown = ref(false);
const doctorSearch = ref('');
const filteredDoctors = computed(() => {
    if (!props.doctors) return [];
    const q = doctorSearch.value.toLowerCase();
    if (!q) return props.doctors;
    return props.doctors.filter(d => {
        const name = locale.value === 'ar' ? d.name_ar : d.name_en;
        return name?.toLowerCase().includes(q);
    });
});
const selectedDoctorLabel = computed(() => {
    if (!form.value.doctor_id) return '';
    const d = props.doctors?.find(x => x.id === form.value.doctor_id);
    return d ? (locale.value === 'ar' ? d.name_ar : d.name_en) : '';
});
function selectDoctor(d) {
    form.value.doctor_id = d.id;
    showDoctorDropdown.value = false;
    doctorSearch.value = '';
}

// Material
const showMaterialDropdown = ref(false);
const materialSearch = ref('');
const filteredMaterials = computed(() => {
    if (!props.materials) return [];
    const q = materialSearch.value.toLowerCase();
    if (!q) return props.materials;
    return props.materials.filter(m => {
        const label = m.label || m;
        return String(label).toLowerCase().includes(q);
    });
});
const selectedMaterialLabel = computed(() => {
    if (!form.value.material) return '';
    const m = props.materials?.find(x => (x.value || x) === form.value.material);
    return m ? (m.label || m) : form.value.material;
});
function selectMaterial(m) {
    form.value.material = m.value || m;
    showMaterialDropdown.value = false;
    materialSearch.value = '';
}

// Close dropdowns on outside click
function handleClickOutside(e) {
    if (!e.target.closest('.loc-patient-dd')) showPatientDropdown.value = false;
    if (!e.target.closest('.loc-doctor-dd')) showDoctorDropdown.value = false;
    if (!e.target.closest('.loc-material-dd')) showMaterialDropdown.value = false;
}
onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <AdminLayout :title="$t('a_create_lab_order')">
        <div class="loc-page">

            <!-- ═══ HERO ═══ -->
            <div class="loc-animate loc-animate-1 loc-hero">
                <div class="loc-hero-orb loc-hero-orb-1"></div>
                <div class="loc-hero-orb loc-hero-orb-2"></div>
                <div class="loc-hero-dots"></div>
                <div class="loc-hero-float">
                    <svg class="w-20 h-20 text-white/[0.04]" fill="currentColor" viewBox="0 0 24 24"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="loc-hero-badge">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">{{ $t('a_create_lab_order') }}</h1>
                            <p class="text-slate-200/70 text-sm mt-1 font-medium">{{ $t('a_create_lab_order_desc') }}</p>
                        </div>
                    </div>
                    <Link href="/admin/dental/lab-orders" class="loc-hero-back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                        {{ isRtl ? 'العودة للقائمة' : 'Back to List' }}
                    </Link>
                </div>
                <!-- Progress Bar -->
                <div class="relative z-10 mt-5">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-semibold text-slate-200/70">{{ isRtl ? 'اكتمال النموذج' : 'Form Completion' }}</span>
                        <span class="text-xs font-bold text-white">{{ progressPercent }}%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#2C4E7A] to-emerald-300 transition-all duration-500 ease-out" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">

                <!-- ═══ ORDER DETAILS ═══ -->
                <div class="loc-animate loc-animate-2 loc-glass-card">
                    <div class="loc-card-header">
                        <div class="loc-section-badge bg-gradient-to-br from-[#1B365D] to-emerald-600">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ $t('a_order_details') }}</h2>
                    </div>

                    <div class="loc-card-body">
                        <!-- Patient & Doctor -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Patient Searchable -->
                            <div>
                                <label class="loc-label">{{ $t('a_patient') }} <span class="text-[#D4B57E]">*</span></label>
                                <div class="loc-patient-dd relative">
                                    <div class="loc-input-wrapper" :class="{ 'loc-input-focus': showPatientDropdown, 'loc-input-error': errors.patient_id, 'loc-input-disabled': !!patient }" @click="!patient && (showPatientDropdown = !showPatientDropdown)">
                                        <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                        <span v-if="selectedPatientLabel" class="loc-input-field truncate">{{ selectedPatientLabel }}</span>
                                        <span v-else class="loc-input-field text-gray-400">{{ $t('a_select_patient') }}</span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="{ 'rotate-180': showPatientDropdown }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                    <Transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                                        enter-to-class="opacity-100 scale-100 translate-y-0"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div v-if="showPatientDropdown && !patient" class="loc-dropdown">
                                            <div class="p-2">
                                                <input v-model="patientSearch" type="text" :placeholder="isRtl ? 'بحث بالاسم أو رقم الملف...' : 'Search by name or file number...'" class="doctorato-input loc-dropdown-search" @click.stop />
                                            </div>
                                            <div class="loc-dropdown-list">
                                                <button v-for="p in filteredPatients" :key="p.id" type="button" @click="selectPatient(p)" class="loc-dropdown-item">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-sm font-semibold text-gray-800 truncate">{{ p.full_name }}</div>
                                                        <div class="text-xs text-gray-400 font-mono">{{ p.file_number }}</div>
                                                    </div>
                                                    <svg v-if="form.patient_id === p.id" class="w-4.5 h-4.5 text-[#1B365D] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                                </button>
                                                <div v-if="filteredPatients.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                <p v-if="errors.patient_id" class="loc-error">{{ errors.patient_id }}</p>
                            </div>

                            <!-- Doctor Searchable -->
                            <div>
                                <label class="loc-label">{{ $t('a_doctor') }}</label>
                                <div class="loc-doctor-dd relative">
                                    <div class="loc-input-wrapper" :class="{ 'loc-input-focus': showDoctorDropdown, 'loc-input-error': errors.doctor_id }" @click="showDoctorDropdown = !showDoctorDropdown">
                                        <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.1-5.1m0 0L12 4.37m-5.68 5.7h11.8M4.26 19.72a9.75 9.75 0 1115.48 0" /></svg>
                                        <span v-if="selectedDoctorLabel" class="loc-input-field truncate">{{ selectedDoctorLabel }}</span>
                                        <span v-else class="loc-input-field text-gray-400">{{ $t('a_select_doctor') }}</span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="{ 'rotate-180': showDoctorDropdown }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                    <Transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                                        enter-to-class="opacity-100 scale-100 translate-y-0"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div v-if="showDoctorDropdown" class="loc-dropdown">
                                            <div class="p-2">
                                                <input v-model="doctorSearch" type="text" :placeholder="isRtl ? 'بحث عن طبيب...' : 'Search doctor...'" class="doctorato-input loc-dropdown-search" @click.stop />
                                            </div>
                                            <div class="loc-dropdown-list">
                                                <button v-for="d in filteredDoctors" :key="d.id" type="button" @click="selectDoctor(d)" class="loc-dropdown-item">
                                                    <span class="text-sm font-semibold text-gray-800">{{ locale === 'ar' ? d.name_ar : d.name_en }}</span>
                                                    <svg v-if="form.doctor_id === d.id" class="w-4.5 h-4.5 text-[#1B365D] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                                </button>
                                                <div v-if="filteredDoctors.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                <p v-if="errors.doctor_id" class="loc-error">{{ errors.doctor_id }}</p>
                            </div>
                        </div>

                        <!-- Item Type / Tooth / Shade -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="loc-label">{{ $t('a_item_type') }} <span class="text-[#D4B57E]">*</span></label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.item_type }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                                    <select v-model="form.item_type" class="doctorato-input loc-input-field">
                                        <option value="">{{ $t('a_select_type') }}</option>
                                        <option v-for="it in itemTypes" :key="it.value || it" :value="it.value || it">{{ $t('a_lab_' + (it.value || it)) }}</option>
                                    </select>
                                </div>
                                <p v-if="errors.item_type" class="loc-error">{{ errors.item_type }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_tooth_number') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.tooth_number }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" /></svg>
                                    <input v-model="form.tooth_number" type="text" :placeholder="isRtl ? 'مثال: 11, 21' : 'e.g. 11, 21'" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.tooth_number" class="loc-error">{{ errors.tooth_number }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_shade') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.shade }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 005.304 0l6.401-6.402M6.75 21A3.75 3.75 0 013 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125V6m3 0h3.375c.621 0 1.125.504 1.125 1.125v3.375M3 12h7.5" /></svg>
                                    <input v-model="form.shade" type="text" placeholder="A1, A2, B1..." class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.shade" class="loc-error">{{ errors.shade }}</p>
                            </div>
                        </div>

                        <!-- Material & Lab Name -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Material Searchable -->
                            <div>
                                <label class="loc-label">{{ $t('a_material') }}</label>
                                <div class="loc-material-dd relative">
                                    <div class="loc-input-wrapper" :class="{ 'loc-input-focus': showMaterialDropdown, 'loc-input-error': errors.material }" @click="showMaterialDropdown = !showMaterialDropdown">
                                        <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" /></svg>
                                        <span v-if="selectedMaterialLabel" class="loc-input-field truncate">{{ selectedMaterialLabel }}</span>
                                        <span v-else class="loc-input-field text-gray-400">{{ $t('a_select_material') }}</span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform" :class="{ 'rotate-180': showMaterialDropdown }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                    </div>
                                    <Transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                                        enter-to-class="opacity-100 scale-100 translate-y-0"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 scale-100"
                                        leave-to-class="opacity-0 scale-95"
                                    >
                                        <div v-if="showMaterialDropdown" class="loc-dropdown">
                                            <div class="p-2">
                                                <input v-model="materialSearch" type="text" :placeholder="isRtl ? 'بحث عن المادة...' : 'Search material...'" class="doctorato-input loc-dropdown-search" @click.stop />
                                            </div>
                                            <div class="loc-dropdown-list">
                                                <button v-for="m in filteredMaterials" :key="m.value || m" type="button" @click="selectMaterial(m)" class="loc-dropdown-item">
                                                    <span class="text-sm font-semibold text-gray-800">{{ m.label || m }}</span>
                                                    <svg v-if="form.material === (m.value || m)" class="w-4.5 h-4.5 text-[#1B365D] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" /></svg>
                                                </button>
                                                <div v-if="filteredMaterials.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">{{ isRtl ? 'لا توجد نتائج' : 'No results' }}</div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                <p v-if="errors.material" class="loc-error">{{ errors.material }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_lab_name') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.lab_name }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                                    <input v-model="form.lab_name" type="text" :placeholder="isRtl ? 'اسم المعمل' : 'Lab name'" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.lab_name" class="loc-error">{{ errors.lab_name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ PRICING & DATES ═══ -->
                <div class="loc-animate loc-animate-3 loc-glass-card">
                    <div class="loc-card-header">
                        <div class="loc-section-badge bg-gradient-to-br from-amber-500 to-[#C4A265]">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                        </div>
                        <h2 class="text-base font-bold text-gray-800">{{ $t('a_pricing_and_dates') }}</h2>
                    </div>

                    <div class="loc-card-body">
                        <!-- Cost / Charge / Order Number -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="loc-label">{{ $t('a_lab_cost') }} ({{ currencyCode }})</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.cost }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <input v-model="form.cost" type="number" min="0" step="0.01" placeholder="0.00" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.cost" class="loc-error">{{ errors.cost }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_patient_charge') }} ({{ currencyCode }})</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.patient_charge }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                                    <input v-model="form.patient_charge" type="number" min="0" step="0.01" placeholder="0.00" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.patient_charge" class="loc-error">{{ errors.patient_charge }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_order_number') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.order_number }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 8.25h15m-16.5 7.5h15m-1.8-13.5l-3.9 19.5m-2.1-19.5l-3.9 19.5" /></svg>
                                    <input v-model="form.order_number" type="text" :placeholder="isRtl ? 'رقم الطلب' : 'Order #'" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.order_number" class="loc-error">{{ errors.order_number }}</p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="loc-label">{{ $t('a_order_date') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.order_date }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    <input v-model="form.order_date" type="date" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.order_date" class="loc-error">{{ errors.order_date }}</p>
                            </div>
                            <div>
                                <label class="loc-label">{{ $t('a_expected_date') }}</label>
                                <div class="loc-input-wrapper" :class="{ 'loc-input-error': errors.expected_date }">
                                    <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <input v-model="form.expected_date" type="date" class="doctorato-input loc-input-field" />
                                </div>
                                <p v-if="errors.expected_date" class="loc-error">{{ errors.expected_date }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="loc-label">{{ $t('a_notes') }}</label>
                            <div class="loc-textarea-wrapper">
                                <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                <textarea v-model="form.notes" rows="2" :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'" class="doctorato-input loc-textarea-field"></textarea>
                            </div>
                        </div>

                        <!-- Special Instructions -->
                        <div>
                            <label class="loc-label">{{ $t('a_special_instructions') }}</label>
                            <div class="loc-textarea-wrapper">
                                <svg class="w-4.5 h-4.5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                <textarea v-model="form.special_instructions" rows="2" :placeholder="isRtl ? 'تعليمات خاصة...' : 'Special instructions...'" class="doctorato-input loc-textarea-field"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ SUBMIT ═══ -->
                <div class="loc-animate loc-animate-4 flex items-center justify-end gap-3">
                    <Link href="/admin/dental/lab-orders" class="loc-cancel-btn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        {{ $t('a_cancel') }}
                    </Link>
                    <button type="submit" :disabled="processing" class="loc-submit-btn">
                        <div class="loc-shimmer"></div>
                        <svg v-if="!processing" class="w-5 h-5 relative" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else class="w-5 h-5 relative animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" /></svg>
                        <span class="relative">{{ processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : $t('a_create_order') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ─── Page ──────────────────────────────── */
.loc-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* ─── Staggered Animations ──────────────── */
.loc-animate {
    opacity: 0;
    transform: translateY(24px);
    animation: locReveal 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.loc-animate-1 { animation-delay: 0.05s; }
.loc-animate-2 { animation-delay: 0.15s; }
.loc-animate-3 { animation-delay: 0.25s; }
.loc-animate-4 { animation-delay: 0.35s; }

@keyframes locReveal {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ─── Hero ──────────────────────────────── */
.loc-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.75rem;
    background: linear-gradient(135deg, #0F2444 0%, #1B365D 50%, #134e4a 100%);
    padding: 2rem;
}
.loc-hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    animation: locOrb 8s ease-in-out infinite;
}
.loc-hero-orb-1 {
    width: 220px; height: 220px;
    top: -70px; right: -30px;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.3), transparent);
}
[dir="rtl"] .loc-hero-orb-1 { right: auto; left: -30px; }
.loc-hero-orb-2 {
    width: 160px; height: 160px;
    bottom: -50px; left: -20px;
    background: radial-gradient(circle, rgba(20, 184, 166, 0.2), transparent);
    animation-delay: 3s;
}
[dir="rtl"] .loc-hero-orb-2 { left: auto; right: -20px; }
@keyframes locOrb {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(12px, -12px) scale(1.08); }
}
.loc-hero-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 20px 20px;
}
.loc-hero-float {
    position: absolute; bottom: -10px; right: 20px;
    animation: locFloat 6s ease-in-out infinite;
}
[dir="rtl"] .loc-hero-float { right: auto; left: 20px; }
@keyframes locFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.loc-hero-badge {
    width: 3rem; height: 3rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, rgba(6, 182, 212, 0.4), rgba(20, 184, 166, 0.4));
    backdrop-filter: blur(12px);
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 16px rgba(6, 182, 212, 0.25);
}
.loc-hero-back {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 1rem; border-radius: 0.75rem;
    font-size: 0.875rem; font-weight: 600;
    color: rgba(255,255,255,0.7);
    transition: all 0.2s;
}
.loc-hero-back:hover { color: #fff; background: rgba(255,255,255,0.1); }

/* ─── Glass Card ────────────────────────── */
.loc-glass-card {
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 24px -4px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}
.loc-glass-card:hover {
    box-shadow: 0 8px 32px -4px rgba(0, 0, 0, 0.1);
}
.loc-card-header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.loc-card-body {
    padding: 1.75rem;
    display: flex; flex-direction: column; gap: 1.25rem;
}
.loc-section-badge {
    width: 2rem; height: 2rem;
    border-radius: 0.625rem;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* ─── Label ─────────────────────────────── */
.loc-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #374151;
    margin-bottom: 0.5rem;
}

/* ─── Input Wrapper ─────────────────────── */
.loc-input-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    background-color: rgba(249, 250, 251, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}
.loc-input-wrapper:focus-within,
.loc-input-focus {
    border-color: #C4A265;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12);
}
.loc-input-error {
    border-color: #fca5a5 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}
.loc-input-disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.loc-input-field {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font-size: 0.875rem;
    color: #1f2937;
    min-width: 0;
    cursor: inherit;
}
.loc-input-field::placeholder { color: #9ca3af; }
.loc-input-field::-webkit-inner-spin-button { -webkit-appearance: none; }

/* ─── Textarea Wrapper ──────────────────── */
.loc-textarea-wrapper {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 2px solid #f3f4f6;
    border-radius: 1rem;
    font-size: 0.875rem;
    background-color: rgba(249, 250, 251, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.loc-textarea-wrapper:focus-within {
    border-color: #C4A265;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.12);
}
.loc-textarea-field {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    font-size: 0.875rem;
    color: #1f2937;
    resize: vertical;
    min-height: 2.5rem;
}
.loc-textarea-field::placeholder { color: #9ca3af; }

/* ─── Dropdown ──────────────────────────── */
.loc-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0; right: 0;
    z-index: 40;
    background: rgba(255, 255, 255, 0.97);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 1rem;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}
.loc-dropdown-search {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 2px solid #f3f4f6;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    background: rgba(249, 250, 251, 0.6);
    outline: none;
    transition: all 0.2s;
}
.loc-dropdown-search:focus {
    border-color: #C4A265;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.1);
}
.loc-dropdown-list {
    max-height: 200px;
    overflow-y: auto;
    padding: 0.25rem;
}
.loc-dropdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 0.625rem 0.875rem;
    border-radius: 0.625rem;
    text-align: start;
    transition: background 0.15s;
}
.loc-dropdown-item:hover {
    background: rgba(6, 182, 212, 0.06);
}

/* ─── Error ─────────────────────────────── */
.loc-error {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #ef4444;
}

/* ─── Buttons ───────────────────────────── */
.loc-cancel-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 1.25rem;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.875rem; font-weight: 700;
    transition: all 0.3s;
}
.loc-cancel-btn:hover {
    background: #e5e7eb;
    color: #374151;
}
.loc-submit-btn {
    position: relative;
    display: inline-flex; align-items: center; gap: 0.75rem;
    padding: 0.875rem 2rem;
    border-radius: 1.25rem;
    color: #fff;
    font-size: 0.875rem; font-weight: 800;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.3);
}
.loc-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 32px rgba(6, 182, 212, 0.4);
}
.loc-submit-btn:active { transform: scale(0.97); }
.loc-submit-btn:disabled { opacity: 0.5; transform: none; }

.loc-shimmer {
    position: absolute; inset: 0;
    background: linear-gradient(90deg, #1B365D, #059669, #1B365D);
    background-size: 200% 100%;
    animation: locShimmer 3s ease-in-out infinite;
}
@keyframes locShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
