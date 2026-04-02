<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import PatientSearchSelect from '@/Components/Doctor/PatientSearchSelect.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescriptions: Object,
    presets: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showCreate = ref(false);
const editingPrescription = ref(null);
const viewingPrescription = ref(null);
const showDeleteConfirm = ref(null);
let debounce = null;

watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get('/doctor/prescriptions', { search: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// ─── Selected Patient Info ───────────────────────
const selectedPatientData = ref(null);
const selectedPatient = computed(() => selectedPatientData.value);

function onPatientSelected(patient) {
    selectedPatientData.value = patient;
}

const selectedPatientAge = computed(() => {
    if (!selectedPatient.value?.date_of_birth) return null;
    const dob = new Date(selectedPatient.value.date_of_birth);
    return Math.floor((Date.now() - dob.getTime()) / (365.25 * 24 * 60 * 60 * 1000));
});

// ─── Create Form ─────────────────────────────────
const form = useForm({
    patient_id: '',
    diagnosis: '',
    notes: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

function addItem() {
    form.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}
function removeItem(index) {
    if (form.items.length > 1) form.items.splice(index, 1);
}
function submitPrescription() {
    form.post('/doctor/prescriptions', {
        onSuccess: () => {
            showCreate.value = false;
            form.reset();
            form.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
        },
    });
}

// ─── Edit Form ───────────────────────────────────
const editForm = useForm({ diagnosis: '', notes: '', items: [] });

function openEdit(rx) {
    editingPrescription.value = rx;
    editForm.diagnosis = rx.diagnosis || '';
    editForm.notes = rx.notes || '';
    editForm.items = (rx.items || []).map(i => ({
        medication_name: i.medication_name, dosage: i.dosage || '',
        frequency: i.frequency || '', duration: i.duration || '', instructions: i.instructions || '',
    }));
    if (editForm.items.length === 0) editForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}
function addEditItem() { editForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }); }
function removeEditItem(index) { if (editForm.items.length > 1) editForm.items.splice(index, 1); }
function submitEdit() {
    editForm.put(`/doctor/prescriptions/${editingPrescription.value.id}`, {
        onSuccess: () => { editingPrescription.value = null; },
    });
}

// ─── View / Delete / Duplicate / PDF ─────────────
function openView(rx) { viewingPrescription.value = rx; }
function deletePrescription(id) { router.delete(`/doctor/prescriptions/${id}`, { onSuccess: () => { showDeleteConfirm.value = null; } }); }
function duplicatePrescription(id) { router.post(`/doctor/prescriptions/${id}/duplicate`); }
function downloadPdf(id) { window.open(`/doctor/prescriptions/${id}/pdf`, '_blank'); }
function printPdf(id) { window.open(`/doctor/prescriptions/${id}/print`, '_blank'); }

// ─── Medication Autocomplete ─────────────────────
const medicationSuggestions = ref({});
let medSearchTimeout = null;

function searchMedication(formType, index, query) {
    const key = `${formType}-${index}`;
    clearTimeout(medSearchTimeout);
    if (!query || query.length < 2) { delete medicationSuggestions.value[key]; return; }
    medSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/doctor/api/medications?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) medicationSuggestions.value[key] = await res.json();
        } catch (e) {}
    }, 300);
}

function selectMedication(formType, index, med) {
    const key = `${formType}-${index}`;
    const targetForm = formType === 'create' ? form : editForm;
    targetForm.items[index].medication_name = med.name;
    if (med.default_dosage) targetForm.items[index].dosage = med.default_dosage;
    if (med.default_frequency) targetForm.items[index].frequency = med.default_frequency;
    if (med.default_duration) targetForm.items[index].duration = med.default_duration;
    delete medicationSuggestions.value[key];
}

// ─── Preset Dropdowns ────────────────────────────
const showPreset = ref({});
function togglePreset(key) { showPreset.value[key] = !showPreset.value[key]; }
function selectPreset(formType, index, field, value) {
    const targetForm = formType === 'create' ? form : editForm;
    targetForm.items[index][field] = value;
    showPreset.value = {};
}

// ─── Helpers ─────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
function getPatientAge(dob) {
    if (!dob) return null;
    return Math.floor((Date.now() - new Date(dob).getTime()) / (365.25 * 24 * 60 * 60 * 1000));
}
function hasMedicalNotes(patient) {
    return patient?.medical_notes && patient.medical_notes.trim().length > 0;
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_prescriptions') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ isRtl ? 'إنشاء وإدارة وطباعة الوصفات الطبية لمرضاك' : 'Create, manage and print prescriptions for your patients' }}</p>
            </div>
            <button @click="showCreate = !showCreate" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#C4A265] to-[#D4B87A] hover:from-[#A68B52] hover:to-[#C4A265] rounded-xl transition-all duration-200 shadow-lg shadow-[#C4A265]/20">
                <svg v-if="!showCreate" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                {{ showCreate ? (isRtl ? 'إلغاء' : 'Cancel') : (isRtl ? 'وصفة جديدة' : 'New Prescription') }}
            </button>
        </div>

        <!-- ══════ CREATE FORM ══════ -->
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-4">
            <div v-if="showCreate" class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-[#C4A265]/5 to-transparent px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ isRtl ? 'وصفة طبية جديدة' : 'New Prescription' }}
                    </h3>
                </div>
                <form @submit.prevent="submitPrescription" class="p-6 space-y-5">
                    <div class="grid lg:grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? 'المريض *' : 'Patient *' }}</label>
                            <PatientSearchSelect v-model="form.patient_id" :required="true" @patient-selected="onPatientSelected" />
                            <p v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                            <input v-model="form.diagnosis" type="text" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'مثال: حب الشباب، إكزيما...' : 'e.g. Acne vulgaris, Eczema...'" />
                        </div>
                    </div>

                    <!-- Patient Alert -->
                    <div v-if="selectedPatient && hasMedicalNotes(selectedPatient)" class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" /></svg>
                        <div>
                            <p class="text-xs font-bold text-red-700 uppercase tracking-wide">{{ isRtl ? 'ملاحظات طبية / حساسية' : 'Medical Notes / Allergies' }}</p>
                            <p class="text-sm text-red-600 mt-0.5">{{ selectedPatient.medical_notes }}</p>
                        </div>
                    </div>

                    <!-- Patient Quick Info -->
                    <div v-if="selectedPatient" class="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
                        <span v-if="selectedPatient.file_number" class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 rounded-lg font-mono font-semibold text-[#C4A265]">{{ selectedPatient.file_number }}</span>
                        <span v-if="selectedPatientAge">{{ isRtl ? 'العمر' : 'Age' }}: <b class="text-gray-700">{{ selectedPatientAge }} {{ isRtl ? 'سنة' : 'yrs' }}</b></span>
                        <span v-if="selectedPatient.gender">{{ isRtl ? 'الجنس' : 'Gender' }}: <b class="text-gray-700">{{ selectedPatient.gender }}</b></span>
                        <span v-if="selectedPatient.phone">{{ isRtl ? 'الهاتف' : 'Phone' }}: <b class="text-gray-700">{{ selectedPatient.phone }}</b></span>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? 'ملاحظات للمريض' : 'Notes for Patient' }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'تعليمات إضافية، نصائح متابعة...' : 'Additional instructions, follow-up advice...'"></textarea>
                    </div>

                    <!-- Medications -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                {{ isRtl ? 'الأدوية' : 'Medications' }}
                            </label>
                            <button type="button" @click="addItem" class="text-xs font-semibold text-[#C4A265] hover:text-[#A68B52] flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ isRtl ? 'إضافة دواء' : 'Add Medication' }}
                            </button>
                        </div>
                        <div class="hidden lg:grid grid-cols-12 gap-2 mb-2 px-1">
                            <span class="col-span-3 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'الدواء *' : 'Medication *' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'الجرعة' : 'Dosage' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'التكرار' : 'Frequency' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'تعليمات' : 'Instructions' }}</span>
                            <span class="col-span-1"></span>
                        </div>
                        <div v-for="(item, index) in form.items" :key="index" class="mb-2">
                            <div class="grid grid-cols-12 gap-2 items-start">
                                <div class="col-span-12 lg:col-span-3 relative">
                                        <input v-model="item.medication_name" @input="searchMedication('create', index, item.medication_name)" :placeholder="isRtl ? 'اكتب اسم الدواء...' : 'Type medication...'" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" required />
                                    <div v-if="medicationSuggestions[`create-${index}`]?.length" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="med in medicationSuggestions[`create-${index}`]" :key="med.id" type="button" @click="selectMedication('create', index, med)" class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0">
                                            <span class="font-semibold text-gray-800">{{ med.name }}</span>
                                            <span v-if="med.generic_name" class="text-gray-400 ml-1 text-xs">({{ med.generic_name }})</span>
                                            <span v-if="med.category" class="block text-[10px] text-[#C4A265] mt-0.5">{{ med.category }}</span>
                                        </button>
                                    </div>
                                </div>
                                <input v-model="item.dosage" :placeholder="isRtl ? 'مثال: 500مج' : 'e.g. 500mg'" class="col-span-6 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                <div class="col-span-6 lg:col-span-2 relative">
                                    <div class="relative">
                                        <input v-model="item.frequency" :placeholder="isRtl ? 'التكرار' : 'Frequency'" class="w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                        <button type="button" @click="togglePreset(`freq-c-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C4A265]"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button>
                                    </div>
                                    <div v-if="showPreset[`freq-c-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="opt in presets?.frequencies" :key="opt" type="button" @click="selectPreset('create', index, 'frequency', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0">{{ opt }}</button>
                                    </div>
                                </div>
                                <div class="col-span-6 lg:col-span-2 relative">
                                    <div class="relative">
                                        <input v-model="item.duration" :placeholder="isRtl ? 'المدة' : 'Duration'" class="w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                        <button type="button" @click="togglePreset(`dur-c-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C4A265]"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button>
                                    </div>
                                    <div v-if="showPreset[`dur-c-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="opt in presets?.durations" :key="opt" type="button" @click="selectPreset('create', index, 'duration', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0">{{ opt }}</button>
                                    </div>
                                </div>
                                <input v-model="item.instructions" :placeholder="isRtl ? 'تعليمات خاصة...' : 'Special instructions...'" class="col-span-5 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                <button type="button" @click="removeItem(index)" class="col-span-1 flex items-center justify-center h-10 text-gray-300 hover:text-red-500 transition-colors" :class="form.items.length <= 1 ? 'invisible' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#C4A265] to-[#D4B87A] hover:from-[#A68B52] hover:to-[#C4A265] rounded-xl transition-all disabled:opacity-50 shadow-lg shadow-[#C4A265]/20">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            {{ form.processing ? (isRtl ? 'جاري الإنشاء...' : 'Creating...') : (isRtl ? 'إنشاء وصفة' : 'Create Prescription') }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ══════ SEARCH ══════ -->
        <div class="mb-4">
            <div class="relative max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالاسم، الهاتف، رقم الملف...' : 'Search by patient name, phone, file #...'" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
            </div>
        </div>

        <!-- ══════ TABLE ══════ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-50/50">
                            <th class="ltr:text-left rtl:text-right px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</th>
                            <th class="text-center px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'أدوية' : 'Meds' }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="ltr:text-right rtl:text-left px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الإجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="rx in prescriptions.data" :key="rx.id" class="hover:bg-[#FDF8F0]/50 transition-colors group">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#C4A265]/15 to-[#C4A265]/5 flex items-center justify-center text-[#C4A265] font-bold text-xs flex-shrink-0">{{ rx.patient?.full_name?.charAt(0) || '?' }}</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-[13px]">{{ rx.patient?.full_name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] font-mono text-[#C4A265] font-semibold">{{ rx.patient?.file_number }}</span>
                                            <span v-if="hasMedicalNotes(rx.patient)" class="inline-flex items-center gap-0.5 text-[9px] font-bold text-red-500 bg-red-50 px-1.5 py-0.5 rounded-full">
                                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                {{ isRtl ? 'تنبيه' : 'Alert' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 max-w-[200px]"><p class="truncate text-[13px]">{{ rx.diagnosis || '-' }}</p></td>
                            <td class="px-5 py-3.5 text-center"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold">{{ rx.items?.length || 0 }}</span></td>
                            <td class="px-5 py-3.5">
                                <span class="text-[13px] text-gray-500">{{ formatDate(rx.created_at) }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">Rx #{{ rx.id }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <button @click="openView(rx)" class="p-1.5 text-gray-500 hover:text-[#C4A265] hover:bg-[#C4A265]/5 rounded-lg transition-colors" :title="isRtl ? 'عرض' : 'View'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg></button>
                                    <button @click="openEdit(rx)" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" :title="isRtl ? 'تعديل' : 'Edit'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
                                    <button @click="printPdf(rx.id)" class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" :title="isRtl ? 'طباعة' : 'Print'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg></button>
                                    <button @click="downloadPdf(rx.id)" class="p-1.5 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" :title="isRtl ? 'تحميل PDF' : 'Download PDF'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg></button>
                                    <button @click="duplicatePrescription(rx.id)" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" :title="isRtl ? 'نسخ' : 'Duplicate'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg></button>
                                    <button @click="showDeleteConfirm = rx.id" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" :title="isRtl ? 'حذف' : 'Delete'"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="prescriptions.data?.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center"><svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>
                <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد وصفات' : 'No prescriptions found' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'أنشئ أول وصفة باستخدام الزر أعلاه' : 'Create your first prescription using the button above' }}</p>
            </div>
            <div v-if="prescriptions.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in prescriptions.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#C4A265] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- ══════ VIEW MODAL ══════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="viewingPrescription" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="viewingPrescription = null">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تفاصيل الوصفة' : 'Prescription Details' }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Rx #{{ viewingPrescription.id }} · {{ formatDate(viewingPrescription.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="printPdf(viewingPrescription.id)" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    {{ isRtl ? 'طباعة' : 'Print' }}
                                </button>
                                <button @click="downloadPdf(viewingPrescription.id)" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    {{ isRtl ? 'تحميل' : 'Download' }}
                                </button>
                                <button @click="viewingPrescription = null" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center text-white font-bold text-lg flex-shrink-0">{{ viewingPrescription.patient?.full_name?.charAt(0) || '?' }}</div>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-900">{{ viewingPrescription.patient?.full_name }}</p>
                                    <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-500">
                                        <span v-if="viewingPrescription.patient?.file_number" class="font-mono text-[#C4A265] font-semibold">{{ viewingPrescription.patient.file_number }}</span>
                                        <span v-if="getPatientAge(viewingPrescription.patient?.date_of_birth)">{{ getPatientAge(viewingPrescription.patient.date_of_birth) }} {{ isRtl ? 'سنة' : 'yrs' }}</span>
                                        <span v-if="viewingPrescription.patient?.gender">{{ viewingPrescription.patient.gender }}</span>
                                        <span v-if="viewingPrescription.patient?.phone">{{ viewingPrescription.patient.phone }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="hasMedicalNotes(viewingPrescription.patient)" class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" /></svg>
                                <div><p class="text-xs font-bold text-red-700 uppercase">{{ isRtl ? 'ملاحظات طبية / حساسية' : 'Medical Notes / Allergies' }}</p><p class="text-sm text-red-600 mt-0.5">{{ viewingPrescription.patient.medical_notes }}</p></div>
                            </div>
                            <div v-if="viewingPrescription.diagnosis" class="p-4 bg-amber-50/50 border border-amber-100 rounded-xl">
                                <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</p>
                                <p class="text-sm text-gray-800 font-medium">{{ viewingPrescription.diagnosis }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                    {{ isRtl ? 'الأدوية الموصوفة' : 'Prescribed Medications' }} ({{ viewingPrescription.items?.length || 0 }})
                                </p>
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead><tr class="bg-gray-50">
                                            <th class="ltr:text-left rtl:text-right px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase">#</th>
                                            <th class="ltr:text-left rtl:text-right px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'الدواء' : 'Medication' }}</th>
                                            <th class="ltr:text-left rtl:text-right px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'الجرعة' : 'Dosage' }}</th>
                                            <th class="ltr:text-left rtl:text-right px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'التكرار' : 'Frequency' }}</th>
                                            <th class="ltr:text-left rtl:text-right px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</th>
                                        </tr></thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(item, idx) in viewingPrescription.items" :key="idx" class="hover:bg-gray-50/50">
                                                <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ idx + 1 }}</td>
                                                <td class="px-4 py-3"><p class="font-semibold text-gray-800">{{ item.medication_name }}</p><p v-if="item.instructions" class="text-[11px] text-gray-400 mt-0.5 italic">{{ item.instructions }}</p></td>
                                                <td class="px-4 py-3 text-gray-600">{{ item.dosage || '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ item.frequency || '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600">{{ item.duration || '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-if="viewingPrescription.notes" class="p-4 bg-blue-50/50 border border-blue-100 rounded-xl">
                                <p class="text-[10px] font-bold text-blue-700 uppercase tracking-wider mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</p>
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ viewingPrescription.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════ EDIT MODAL ══════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="editingPrescription" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="editingPrescription = null">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                        <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'تعديل الوصفة' : 'Edit Prescription' }}</h3>
                                <p class="text-xs text-gray-400 mt-0.5">{{ editingPrescription.patient?.full_name }} · Rx #{{ editingPrescription.id }}</p>
                            </div>
                            <button @click="editingPrescription = null" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                        <form @submit.prevent="submitEdit" class="p-6 space-y-5">
                            <div v-if="hasMedicalNotes(editingPrescription.patient)" class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" /></svg>
                                <div><p class="text-xs font-bold text-red-700 uppercase">{{ isRtl ? 'ملاحظات طبية / حساسية' : 'Medical Notes / Allergies' }}</p><p class="text-sm text-red-600 mt-0.5">{{ editingPrescription.patient.medical_notes }}</p></div>
                            </div>
                            <div class="grid lg:grid-cols-2 gap-4">
                                <div><label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label><input v-model="editForm.diagnosis" type="text" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" /></div>
                                <div><label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label><input v-model="editForm.notes" type="text" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" /></div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-xs font-bold text-gray-700 uppercase tracking-wide">{{ isRtl ? 'الأدوية' : 'Medications' }}</label>
                                    <button type="button" @click="addEditItem" class="text-xs font-semibold text-[#C4A265] hover:text-[#A68B52] flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ isRtl ? 'إضافة' : 'Add' }}</button>
                                </div>
                                <div v-for="(item, index) in editForm.items" :key="index" class="mb-2">
                                    <div class="grid grid-cols-12 gap-2 items-start">
                                        <div class="col-span-12 lg:col-span-3 relative">
                                            <input v-model="item.medication_name" @input="searchMedication('edit', index, item.medication_name)" :placeholder="isRtl ? 'الدواء *' : 'Medication *'" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" required />
                                            <div v-if="medicationSuggestions[`edit-${index}`]?.length" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-40 overflow-y-auto">
                                                <button v-for="med in medicationSuggestions[`edit-${index}`]" :key="med.id" type="button" @click="selectMedication('edit', index, med)" class="w-full text-left px-3 py-2 text-sm hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0"><span class="font-semibold text-gray-800">{{ med.name }}</span><span v-if="med.generic_name" class="text-gray-400 ml-1 text-xs">({{ med.generic_name }})</span></button>
                                            </div>
                                        </div>
                                        <input v-model="item.dosage" :placeholder="isRtl ? 'الجرعة' : 'Dosage'" class="col-span-6 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                        <div class="col-span-6 lg:col-span-2 relative">
                                            <div class="relative"><input v-model="item.frequency" :placeholder="isRtl ? 'التكرار' : 'Frequency'" class="w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" /><button type="button" @click="togglePreset(`freq-e-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C4A265]"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button></div>
                                            <div v-if="showPreset[`freq-e-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto"><button v-for="opt in presets?.frequencies" :key="opt" type="button" @click="selectPreset('edit', index, 'frequency', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0">{{ opt }}</button></div>
                                        </div>
                                        <div class="col-span-6 lg:col-span-2 relative">
                                            <div class="relative"><input v-model="item.duration" :placeholder="isRtl ? 'المدة' : 'Duration'" class="w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" /><button type="button" @click="togglePreset(`dur-e-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C4A265]"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg></button></div>
                                            <div v-if="showPreset[`dur-e-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto"><button v-for="opt in presets?.durations" :key="opt" type="button" @click="selectPreset('edit', index, 'duration', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-[#FDF8F0] border-b border-gray-50 last:border-0">{{ opt }}</button></div>
                                        </div>
                                        <input v-model="item.instructions" :placeholder="isRtl ? 'تعليمات' : 'Instructions'" class="col-span-5 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30" />
                                        <button type="button" @click="removeEditItem(index)" class="col-span-1 flex items-center justify-center h-10 text-gray-300 hover:text-red-500 transition-colors" :class="editForm.items.length <= 1 ? 'invisible' : ''"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="editingPrescription = null" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 text-sm font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-xl transition-colors disabled:opacity-50">{{ editForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'تحديث الوصفة' : 'Update Prescription') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ══════ DELETE CONFIRM ══════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showDeleteConfirm = null">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></div>
                            <div><h3 class="text-lg font-bold text-gray-900">{{ isRtl ? 'حذف الوصفة؟' : 'Delete Prescription?' }}</h3><p class="text-sm text-gray-500">{{ isRtl ? 'لا يمكن التراجع عن هذا الإجراء.' : 'This action cannot be undone.' }}</p></div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showDeleteConfirm = null" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            <button @click="deletePrescription(showDeleteConfirm)" class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">{{ isRtl ? 'حذف' : 'Delete' }}</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
