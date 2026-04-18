<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import SearchableSelect from '@/Components/Doctor/SearchableSelect.vue';
import PatientSearchSelect from '@/Components/Doctor/PatientSearchSelect.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescriptions: Object,
    filters: Object,
    presets: Object,
});

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

// ─── Search & Filters ────────────────────────────
const search = ref(props.filters?.search || '');
let debounce = null;
watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get('/doctor/pediatric/prescriptions', { search: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

// ─── Dose Calculator ─────────────────────────────
const calcWeight = ref('');
const calcDosePerKg = ref('');
const calcFrequency = ref(3);
const calcConcentration = ref('');
const calcMedName = ref('');
const calcMaxDose = ref('');
const calcAnimating = ref(false);

const totalDosePerAdmin = computed(() => {
    const w = parseFloat(calcWeight.value);
    const d = parseFloat(calcDosePerKg.value);
    if (!w || !d) return null;
    return Math.round(w * d * 10) / 10;
});

const dailyDose = computed(() => {
    if (!totalDosePerAdmin.value) return null;
    return Math.round(totalDosePerAdmin.value * calcFrequency.value * 10) / 10;
});

const volumePerDose = computed(() => {
    const c = parseFloat(calcConcentration.value);
    if (!totalDosePerAdmin.value || !c) return null;
    return Math.round((totalDosePerAdmin.value / (c / 5)) * 10) / 10;
});

const isDoseExceeded = computed(() => {
    if (!totalDosePerAdmin.value || !calcMaxDose.value) return false;
    return totalDosePerAdmin.value > parseFloat(calcMaxDose.value);
});

const pediatricMeds = [
    { name: 'Paracetamol', nameAr: '\u0628\u0627\u0631\u0627\u0633\u064A\u062A\u0627\u0645\u0648\u0644', dosePerKg: 15, concentration: 120, frequency: 4, maxDose: 60 },
    { name: 'Ibuprofen', nameAr: '\u0627\u064A\u0628\u0648\u0628\u0631\u0648\u0641\u064A\u0646', dosePerKg: 10, concentration: 100, frequency: 3, maxDose: 40 },
    { name: 'Amoxicillin', nameAr: '\u0623\u0645\u0648\u0643\u0633\u064A\u0633\u064A\u0644\u064A\u0646', dosePerKg: 25, concentration: 250, frequency: 3, maxDose: 500 },
    { name: 'Augmentin', nameAr: '\u0623\u0648\u062C\u0645\u0646\u062A\u064A\u0646', dosePerKg: 25, concentration: 228.5, frequency: 2, maxDose: 500 },
    { name: 'Azithromycin', nameAr: '\u0623\u0632\u064A\u062B\u0631\u0648\u0645\u064A\u0633\u064A\u0646', dosePerKg: 10, concentration: 200, frequency: 1, maxDose: 500 },
    { name: 'Cetirizine', nameAr: '\u0633\u064A\u062A\u0631\u064A\u0632\u064A\u0646', dosePerKg: 0.25, concentration: 5, frequency: 1, maxDose: 10 },
    { name: 'Cefixime', nameAr: '\u0633\u064A\u0641\u064A\u0643\u0633\u064A\u0645', dosePerKg: 8, concentration: 100, frequency: 1, maxDose: 400 },
    { name: 'Domperidone', nameAr: '\u062F\u0648\u0645\u0628\u064A\u0631\u064A\u062F\u0648\u0646', dosePerKg: 0.25, concentration: 5, frequency: 3, maxDose: 10 },
];

const activeMed = ref(null);

function selectPediatricMed(med) {
    activeMed.value = med.name;
    calcDosePerKg.value = med.dosePerKg;
    calcConcentration.value = med.concentration;
    calcFrequency.value = med.frequency;
    calcMedName.value = isRtl.value ? med.nameAr : med.name;
    calcMaxDose.value = med.maxDose;
    triggerCalcAnimation();
}

function triggerCalcAnimation() {
    calcAnimating.value = false;
    setTimeout(() => { calcAnimating.value = true; }, 50);
}

function resetCalculator() {
    calcWeight.value = '';
    calcDosePerKg.value = '';
    calcFrequency.value = 3;
    calcConcentration.value = '';
    calcMedName.value = '';
    calcMaxDose.value = '';
    activeMed.value = null;
    calcAnimating.value = false;
}

// ─── Copy Calculator to Prescription Form ────────
function copyToForm() {
    if (!totalDosePerAdmin.value) return;
    const dosageStr = volumePerDose.value
        ? `${totalDosePerAdmin.value}mg (${volumePerDose.value}ml)`
        : `${totalDosePerAdmin.value}mg`;
    const freqLabel = `${calcFrequency.value}x/${isRtl.value ? '\u064A\u0648\u0645' : 'day'}`;

    // Find the first empty item or add a new one
    let targetIndex = form.items.findIndex(item => !item.medication_name);
    if (targetIndex === -1) {
        addItem();
        targetIndex = form.items.length - 1;
    }

    form.items[targetIndex].medication_name = calcMedName.value || '';
    form.items[targetIndex].dosage = dosageStr;
    form.items[targetIndex].frequency = freqLabel;
    form.items[targetIndex].instructions = isDoseExceeded.value
        ? (isRtl.value ? '\u062A\u062D\u0630\u064A\u0631: \u0627\u0644\u062C\u0631\u0639\u0629 \u062A\u062A\u062C\u0627\u0648\u0632 \u0627\u0644\u062D\u062F \u0627\u0644\u0623\u0642\u0635\u0649!' : 'WARNING: Dose exceeds max!')
        : '';

    showCreate.value = true;
    copiedToForm.value = true;
    setTimeout(() => { copiedToForm.value = false; }, 2000);
}

const copiedToForm = ref(false);

// ─── Selected Patient Info ───────────────────────
const selectedPatientData = ref(null);
const selectedPatient = computed(() => selectedPatientData.value);

function onPatientSelected(patient) {
    selectedPatientData.value = patient;
}

const selectedPatientAge = computed(() => {
    if (!selectedPatient.value?.date_of_birth) return null;
    const dob = new Date(selectedPatient.value.date_of_birth);
    const now = new Date();
    let years = now.getFullYear() - dob.getFullYear();
    let months = now.getMonth() - dob.getMonth();
    if (months < 0) { years--; months += 12; }
    if (now.getDate() < dob.getDate()) months--;
    if (months < 0) { years--; months += 12; }
    if (years < 1) return isRtl.value ? `${months} \u0634\u0647\u0631` : `${months}mo`;
    if (years < 3) return isRtl.value ? `${years} \u0633\u0646\u0629 \u0648 ${months} \u0634\u0647\u0631` : `${years}y ${months}m`;
    return isRtl.value ? `${years} \u0633\u0646\u0629` : `${years}y`;
});

// ─── Create Form ─────────────────────────────────
const showCreate = ref(false);

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
            selectedPatientData.value = null;
        },
    });
}

// ─── Medication Autocomplete ─────────────────────
const medicationSuggestions = ref({});
let medSearchTimeout = null;

function searchMedication(index, query) {
    const key = `create-${index}`;
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

function selectMedication(index, med) {
    const key = `create-${index}`;
    form.items[index].medication_name = med.name;
    if (med.default_dosage) form.items[index].dosage = med.default_dosage;
    if (med.default_frequency) form.items[index].frequency = med.default_frequency;
    if (med.default_duration) form.items[index].duration = med.default_duration;
    delete medicationSuggestions.value[key];
}

// ─── Preset Dropdowns ────────────────────────────
const showPreset = ref({});
function togglePreset(key) { showPreset.value[key] = !showPreset.value[key]; }
function selectPreset(index, field, value) {
    form.items[index][field] = value;
    showPreset.value = {};
}

// ─── View Prescription ──────────────────────────
const viewingPrescription = ref(null);
function openView(rx) { viewingPrescription.value = rx; }
function printPdf(id) { window.open(`/doctor/prescriptions/${id}/print`, '_blank'); }
function downloadPdf(id) { window.open(`/doctor/prescriptions/${id}/pdf`, '_blank'); }

// ─── Helpers ─────────────────────────────────────
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? '\u0627\u0644\u064A\u0648\u0645' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? '\u0623\u0645\u0633' : 'Yesterday';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return d.toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}

function hasMedicalNotes(patient) {
    return patient?.medical_notes && patient.medical_notes.trim().length > 0;
}
</script>

<template>
    <div class="space-y-6">
        <!-- ══════ HERO HEADER ══════ -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-300/20 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>
            <div class="absolute top-1/2 right-1/4 w-32 h-32 bg-emerald-400/10 rounded-full blur-xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/15 backdrop-blur-sm rounded-full mb-3">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span class="text-white/90 text-xs font-medium">{{ isRtl ? '\u0637\u0628 \u0627\u0644\u0623\u0637\u0641\u0627\u0644' : 'Pediatrics' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? '\u0648\u0635\u0641\u0627\u062A \u0627\u0644\u0623\u0637\u0641\u0627\u0644' : 'Pediatric Prescriptions' }}</h1>
                        <p class="text-emerald-100 text-sm mt-1">{{ isRtl ? '\u0648\u0635\u0641\u0627\u062A \u0637\u0628\u064A\u0629 \u0645\u0639 \u062D\u0627\u0633\u0628\u0629 \u0627\u0644\u062C\u0631\u0639\u0627\u062A \u062D\u0633\u0628 \u0627\u0644\u0648\u0632\u0646' : 'Prescriptions with weight-based dose calculator' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/15 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/20 text-center">
                            <p class="text-2xl font-bold text-white">{{ prescriptions?.total || 0 }}</p>
                            <p class="text-xs text-emerald-100">{{ isRtl ? '\u0625\u062C\u0645\u0627\u0644\u064A \u0627\u0644\u0648\u0635\u0641\u0627\u062A' : 'Total Rx' }}</p>
                        </div>
                        <button @click="showCreate = !showCreate" class="inline-flex items-center gap-2 px-5 py-3 text-sm font-semibold bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl border border-white/25 transition-all duration-200">
                            <svg v-if="!showCreate" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            {{ showCreate ? (isRtl ? '\u0625\u0644\u063A\u0627\u0621' : 'Cancel') : (isRtl ? '\u0648\u0635\u0641\u0629 \u062C\u062F\u064A\u062F\u0629' : 'New Prescription') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════ WEIGHT-BASED DOSE CALCULATOR ══════ -->
        <div class="relative overflow-hidden rounded-2xl border-2 border-emerald-200 bg-gradient-to-br from-emerald-50/80 via-white to-emerald-50/50 shadow-lg shadow-emerald-100/50"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
        >
            <!-- Calculator Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 px-5 sm:px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">{{ isRtl ? '\u062D\u0627\u0633\u0628\u0629 \u062C\u0631\u0639\u0627\u062A \u0627\u0644\u0623\u0637\u0641\u0627\u0644' : 'Pediatric Dose Calculator' }}</h2>
                            <p class="text-emerald-100 text-xs">{{ isRtl ? '\u062D\u0633\u0627\u0628 \u0627\u0644\u062C\u0631\u0639\u0629 \u062D\u0633\u0628 \u0648\u0632\u0646 \u0627\u0644\u0637\u0641\u0644' : 'Calculate dose based on child weight' }}</p>
                        </div>
                    </div>
                    <button v-if="calcWeight || calcDosePerKg" @click="resetCalculator" class="text-xs text-white/70 hover:text-white flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        {{ isRtl ? '\u0625\u0639\u0627\u062F\u0629 \u062A\u0639\u064A\u064A\u0646' : 'Reset' }}
                    </button>
                </div>
            </div>

            <div class="p-5 sm:p-6 space-y-5">
                <!-- Quick-Select Medications -->
                <div>
                    <label class="text-xs font-bold text-emerald-700 uppercase tracking-wide mb-2.5 block flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        {{ isRtl ? '\u0627\u062E\u062A\u064A\u0627\u0631 \u0633\u0631\u064A\u0639 \u0644\u0644\u062F\u0648\u0627\u0621' : 'Quick Select Medication' }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="med in pediatricMeds"
                            :key="med.name"
                            type="button"
                            @click="selectPediatricMed(med)"
                            class="group relative px-3.5 py-2 rounded-xl text-xs font-semibold border-2 transition-all duration-300"
                            :class="activeMed === med.name
                                ? 'bg-emerald-500 text-white border-emerald-500 shadow-lg shadow-emerald-200/50 scale-105'
                                : 'bg-white text-emerald-700 border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50 hover:shadow-md'"
                        >
                            <span class="block">{{ isRtl ? med.nameAr : med.name }}</span>
                            <span class="block text-[10px] mt-0.5" :class="activeMed === med.name ? 'text-emerald-100' : 'text-emerald-400'">{{ med.dosePerKg }}mg/kg</span>
                        </button>
                    </div>
                </div>

                <!-- Calculator Inputs -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Weight -->
                    <div>
                        <label class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ isRtl ? '\u0648\u0632\u0646 \u0627\u0644\u0637\u0641\u0644 (kg)' : "Child's Weight (kg)" }}</label>
                        <div class="relative">
                            <input
                                v-model="calcWeight"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: 12' : 'e.g. 12'"
                                class="doctorato-input w-full px-3.5 py-2.5 border-2 border-emerald-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] bg-white transition-all"
                                @input="triggerCalcAnimation"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-emerald-400 font-bold">kg</span>
                        </div>
                    </div>

                    <!-- Dose per kg -->
                    <div>
                        <label class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ isRtl ? '\u0627\u0644\u062C\u0631\u0639\u0629 (mg/kg)' : 'Dose per kg (mg/kg)' }}</label>
                        <div class="relative">
                            <input
                                v-model="calcDosePerKg"
                                type="number"
                                step="0.1"
                                min="0"
                                :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: 15' : 'e.g. 15'"
                                class="doctorato-input w-full px-3.5 py-2.5 border-2 border-emerald-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] bg-white transition-all"
                                @input="triggerCalcAnimation"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-emerald-400 font-bold">mg/kg</span>
                        </div>
                    </div>

                    <!-- Frequency -->
                    <div>
                        <label class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ isRtl ? '\u0639\u062F\u062F \u0627\u0644\u0645\u0631\u0627\u062A / \u064A\u0648\u0645' : 'Frequency (times/day)' }}</label>
                        <div class="relative">
                            <select
                                v-model.number="calcFrequency"
                                class="doctorato-input w-full px-3.5 py-2.5 border-2 border-emerald-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] bg-white appearance-none transition-all"
                            >
                                <option :value="1">1x / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</option>
                                <option :value="2">2x / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</option>
                                <option :value="3">3x / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</option>
                                <option :value="4">4x / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</option>
                                <option :value="6">6x / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</option>
                            </select>
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>

                    <!-- Concentration -->
                    <div>
                        <label class="text-xs font-semibold text-gray-600 mb-1.5 block">{{ isRtl ? '\u062A\u0631\u0643\u064A\u0632 \u0627\u0644\u0634\u0631\u0627\u0628 (mg/5ml)' : 'Syrup conc. (mg/5ml)' }}</label>
                        <div class="relative">
                            <input
                                v-model="calcConcentration"
                                type="number"
                                step="0.1"
                                min="0"
                                :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: 120' : 'e.g. 120'"
                                class="doctorato-input w-full px-3.5 py-2.5 border-2 border-emerald-200 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] bg-white transition-all"
                            />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-emerald-400 font-bold">mg/5ml</span>
                        </div>
                    </div>
                </div>

                <!-- ── Results Panel ── -->
                <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 scale-95 translate-y-2" enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="totalDosePerAdmin" class="relative">
                        <!-- Max Dose Warning -->
                        <div v-if="isDoseExceeded" class="mb-3 flex items-start gap-3 px-4 py-3 bg-red-50 border-2 border-red-200 rounded-xl">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" /></svg>
                            <div>
                                <p class="text-xs font-bold text-red-700 uppercase tracking-wide">{{ isRtl ? '\u062A\u062D\u0630\u064A\u0631: \u062A\u062C\u0627\u0648\u0632 \u0627\u0644\u062C\u0631\u0639\u0629 \u0627\u0644\u0642\u0635\u0648\u0649' : 'WARNING: Exceeds Maximum Dose' }}</p>
                                <p class="text-sm text-red-600 mt-0.5">
                                    {{ isRtl ? '\u0627\u0644\u062C\u0631\u0639\u0629 \u0627\u0644\u0645\u062D\u0633\u0648\u0628\u0629' : 'Calculated' }}: <b>{{ totalDosePerAdmin }}mg</b>
                                    {{ isRtl ? '| \u0627\u0644\u062D\u062F \u0627\u0644\u0623\u0642\u0635\u0649' : '| Max' }}: <b>{{ calcMaxDose }}mg</b>
                                </p>
                            </div>
                        </div>

                        <!-- Results Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Dose per administration -->
                            <div class="relative overflow-hidden rounded-xl border-2 p-4 text-center transition-all duration-500"
                                :class="[
                                    isDoseExceeded ? 'border-red-300 bg-red-50' : 'border-emerald-300 bg-gradient-to-br from-emerald-50 to-emerald-50',
                                    calcAnimating ? 'scale-100 opacity-100' : 'scale-95 opacity-70'
                                ]"
                            >
                                <div class="absolute top-0 right-0 w-20 h-20 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"
                                    :class="isDoseExceeded ? 'bg-red-200/50' : 'bg-emerald-200/50'"></div>
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" :class="isDoseExceeded ? 'text-red-500' : 'text-emerald-500'">
                                    {{ isRtl ? '\u0627\u0644\u062C\u0631\u0639\u0629 / \u0645\u0631\u0629' : 'Dose / Admin' }}
                                </p>
                                <p class="text-3xl sm:text-4xl font-black transition-all duration-700" :class="isDoseExceeded ? 'text-red-600' : 'text-emerald-700'">
                                    {{ totalDosePerAdmin }}
                                </p>
                                <p class="text-xs font-semibold" :class="isDoseExceeded ? 'text-red-400' : 'text-emerald-400'">mg</p>
                            </div>

                            <!-- Daily dose -->
                            <div class="relative overflow-hidden rounded-xl border-2 border-emerald-300 bg-gradient-to-br from-emerald-50 to-emerald-50 p-4 text-center transition-all duration-500"
                                :class="calcAnimating ? 'scale-100 opacity-100' : 'scale-95 opacity-70'"
                                style="transition-delay: 0.1s"
                            >
                                <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-200/50 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
                                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider mb-1">{{ isRtl ? '\u0627\u0644\u062C\u0631\u0639\u0629 \u0627\u0644\u064A\u0648\u0645\u064A\u0629' : 'Daily Dose' }}</p>
                                <p class="text-3xl sm:text-4xl font-black text-emerald-700 transition-all duration-700">{{ dailyDose }}</p>
                                <p class="text-xs font-semibold text-emerald-400">mg / {{ isRtl ? '\u064A\u0648\u0645' : 'day' }}</p>
                            </div>

                            <!-- Volume per dose -->
                            <div class="relative overflow-hidden rounded-xl border-2 p-4 text-center transition-all duration-500"
                                :class="[
                                    volumePerDose ? 'border-emerald-300 bg-gradient-to-br from-teal-50 to-emerald-50' : 'border-gray-200 bg-gray-50',
                                    calcAnimating ? 'scale-100 opacity-100' : 'scale-95 opacity-70'
                                ]"
                                style="transition-delay: 0.2s"
                            >
                                <div class="absolute top-0 right-0 w-20 h-20 bg-teal-200/50 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
                                <p class="text-[10px] font-bold uppercase tracking-wider mb-1" :class="volumePerDose ? 'text-teal-500' : 'text-gray-400'">
                                    {{ isRtl ? '\u0627\u0644\u062D\u062C\u0645 / \u0645\u0631\u0629' : 'Volume / Dose' }}
                                </p>
                                <template v-if="volumePerDose">
                                    <p class="text-3xl sm:text-4xl font-black text-teal-700 transition-all duration-700">{{ volumePerDose }}</p>
                                    <p class="text-xs font-semibold text-teal-400">ml</p>
                                </template>
                                <template v-else>
                                    <p class="text-lg font-semibold text-gray-400 mt-2">--</p>
                                    <p class="text-[10px] text-gray-400">{{ isRtl ? '\u0623\u062F\u062E\u0644 \u062A\u0631\u0643\u064A\u0632 \u0627\u0644\u0634\u0631\u0627\u0628' : 'Enter syrup concentration' }}</p>
                                </template>
                            </div>
                        </div>

                        <!-- Copy to Form Button -->
                        <div class="flex justify-center mt-4">
                            <button
                                type="button"
                                @click="copyToForm"
                                class="inline-flex items-center gap-2.5 px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-500 hover:from-emerald-600 hover:to-emerald-600 rounded-xl transition-all duration-300 shadow-lg shadow-emerald-200/50 hover:shadow-emerald-300/50 hover:scale-105 active:scale-95"
                            >
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                                {{ isRtl ? '\u0646\u0633\u062E \u0625\u0644\u0649 \u0627\u0644\u0648\u0635\u0641\u0629' : 'Copy to Prescription' }}
                                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-0" enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-0">
                                    <svg v-if="copiedToForm" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </Transition>
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Empty State for Calculator -->
                <div v-if="!totalDosePerAdmin" class="text-center py-6">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-emerald-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500">{{ isRtl ? '\u0623\u062F\u062E\u0644 \u0627\u0644\u0648\u0632\u0646 \u0648\u0627\u0644\u062C\u0631\u0639\u0629 \u0644\u0628\u062F\u0621 \u0627\u0644\u062D\u0633\u0627\u0628' : 'Enter weight and dose to start calculating' }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ isRtl ? '\u0623\u0648 \u0627\u062E\u062A\u0631 \u062F\u0648\u0627\u0621 \u0645\u0646 \u0627\u0644\u0623\u0632\u0631\u0627\u0631 \u0623\u0639\u0644\u0627\u0647' : 'Or select a medication from the quick buttons above' }}</p>
                </div>
            </div>
        </div>

        <!-- ══════ CREATE PRESCRIPTION FORM ══════ -->
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-4">
            <div v-if="showCreate" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                style="transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
            >
                <div class="bg-gradient-to-r from-emerald-500/10 to-transparent px-4 sm:px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        {{ isRtl ? '\u0648\u0635\u0641\u0629 \u0637\u0628\u064A\u0629 \u062C\u062F\u064A\u062F\u0629' : 'New Prescription' }}
                    </h3>
                </div>

                <form @submit.prevent="submitPrescription" class="p-4 sm:p-6 space-y-5">
                    <!-- Patient + Diagnosis -->
                    <div class="grid md:grid-cols-2 gap-4 sm:gap-5">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? '\u0627\u0644\u0645\u0631\u064A\u0636 *' : 'Patient *' }}</label>
                            <PatientSearchSelect v-model="form.patient_id" :required="true" @patient-selected="onPatientSelected" />
                            <p v-if="form.errors.patient_id" class="text-xs text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? '\u0627\u0644\u062A\u0634\u062E\u064A\u0635' : 'Diagnosis' }}</label>
                            <textarea v-model="form.diagnosis" rows="2" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: \u0627\u0644\u062A\u0647\u0627\u0628 \u0627\u0644\u0623\u0630\u0646 \u0627\u0644\u0648\u0633\u0637\u0649...' : 'e.g. Acute otitis media...'"></textarea>
                        </div>
                    </div>

                    <!-- Patient Alert -->
                    <div v-if="selectedPatient && hasMedicalNotes(selectedPatient)" class="flex items-start gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-xl">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18 9 9 0 010-18z" /></svg>
                        <div>
                            <p class="text-xs font-bold text-red-700 uppercase tracking-wide">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0627\u062A \u0637\u0628\u064A\u0629 / \u062D\u0633\u0627\u0633\u064A\u0629' : 'Medical Notes / Allergies' }}</p>
                            <p class="text-sm text-red-600 mt-0.5">{{ selectedPatient.medical_notes }}</p>
                        </div>
                    </div>

                    <!-- Patient Quick Info -->
                    <div v-if="selectedPatient" class="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
                        <span v-if="selectedPatient.file_number" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 rounded-lg font-mono font-semibold text-emerald-600">{{ selectedPatient.file_number }}</span>
                        <span v-if="selectedPatientAge">{{ isRtl ? '\u0627\u0644\u0639\u0645\u0631' : 'Age' }}: <b class="text-gray-700">{{ selectedPatientAge }}</b></span>
                        <span v-if="selectedPatient.gender">{{ isRtl ? '\u0627\u0644\u062C\u0646\u0633' : 'Gender' }}: <b class="text-gray-700">{{ selectedPatient.gender }}</b></span>
                        <span v-if="selectedPatient.phone">{{ isRtl ? '\u0627\u0644\u0647\u0627\u062A\u0641' : 'Phone' }}: <b class="text-gray-700">{{ selectedPatient.phone }}</b></span>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1.5 block uppercase tracking-wide">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0627\u062A \u0644\u0644\u0645\u0631\u064A\u0636' : 'Notes for Patient' }}</label>
                        <textarea v-model="form.notes" rows="2" class="doctorato-input w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" :placeholder="isRtl ? '\u062A\u0639\u0644\u064A\u0645\u0627\u062A \u0625\u0636\u0627\u0641\u064A\u0629...' : 'Additional instructions...'"></textarea>
                    </div>

                    <!-- Medications -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                                {{ isRtl ? '\u0627\u0644\u0623\u062F\u0648\u064A\u0629' : 'Medications' }}
                            </label>
                            <button type="button" @click="addItem" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ isRtl ? '\u0625\u0636\u0627\u0641\u0629 \u062F\u0648\u0627\u0621' : 'Add Medication' }}
                            </button>
                        </div>

                        <!-- Desktop Headers -->
                        <div class="hidden lg:grid grid-cols-12 gap-2 mb-2 px-1">
                            <span class="col-span-3 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? '\u0627\u0644\u062F\u0648\u0627\u0621 *' : 'Medication *' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? '\u0627\u0644\u062C\u0631\u0639\u0629' : 'Dosage' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? '\u0627\u0644\u062A\u0643\u0631\u0627\u0631' : 'Frequency' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? '\u0627\u0644\u0645\u062F\u0629' : 'Duration' }}</span>
                            <span class="col-span-2 text-[10px] font-bold text-gray-400 uppercase">{{ isRtl ? '\u062A\u0639\u0644\u064A\u0645\u0627\u062A' : 'Instructions' }}</span>
                            <span class="col-span-1"></span>
                        </div>

                        <!-- Medication Items -->
                        <div v-for="(item, index) in form.items" :key="index" class="mb-2">
                            <div class="grid grid-cols-12 gap-2 items-start">
                                <!-- Medication Name -->
                                <div class="col-span-12 lg:col-span-3 relative">
                                    <input
                                        v-model="item.medication_name"
                                        @input="searchMedication(index, item.medication_name)"
                                        :placeholder="isRtl ? '\u0627\u0643\u062A\u0628 \u0627\u0633\u0645 \u0627\u0644\u062F\u0648\u0627\u0621...' : 'Type medication...'"
                                        class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all"
                                        required
                                    />
                                    <div v-if="medicationSuggestions[`create-${index}`]?.length" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="med in medicationSuggestions[`create-${index}`]" :key="med.id" type="button" @click="selectMedication(index, med)" class="w-full text-left px-3.5 py-2.5 text-sm hover:bg-emerald-50 border-b border-gray-50 last:border-0 transition-colors">
                                            <span class="font-semibold text-gray-800">{{ med.name }}</span>
                                            <span v-if="med.generic_name" class="text-gray-400 ml-1 text-xs">({{ med.generic_name }})</span>
                                            <span v-if="med.category" class="block text-[10px] text-emerald-500 mt-0.5">{{ med.category }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Dosage -->
                                <input v-model="item.dosage" :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: 180mg (7.5ml)' : 'e.g. 180mg (7.5ml)'" class="doctorato-input col-span-6 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" />

                                <!-- Frequency with presets -->
                                <div class="col-span-6 lg:col-span-2 relative">
                                    <div class="relative">
                                        <input v-model="item.frequency" :placeholder="isRtl ? '\u0627\u0644\u062A\u0643\u0631\u0627\u0631' : 'Frequency'" class="doctorato-input w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" />
                                        <button type="button" @click="togglePreset(`freq-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                    </div>
                                    <div v-if="showPreset[`freq-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="opt in presets?.frequencies" :key="opt" type="button" @click="selectPreset(index, 'frequency', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-emerald-50 border-b border-gray-50 last:border-0 transition-colors">{{ opt }}</button>
                                    </div>
                                </div>

                                <!-- Duration with presets -->
                                <div class="col-span-6 lg:col-span-2 relative">
                                    <div class="relative">
                                        <input v-model="item.duration" :placeholder="isRtl ? '\u0627\u0644\u0645\u062F\u0629' : 'Duration'" class="doctorato-input w-full px-3 py-2.5 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" />
                                        <button type="button" @click="togglePreset(`dur-${index}`)" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                    </div>
                                    <div v-if="showPreset[`dur-${index}`]" class="absolute z-30 top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-44 overflow-y-auto">
                                        <button v-for="opt in presets?.durations" :key="opt" type="button" @click="selectPreset(index, 'duration', opt)" class="w-full text-left px-3 py-2 text-xs hover:bg-emerald-50 border-b border-gray-50 last:border-0 transition-colors">{{ opt }}</button>
                                    </div>
                                </div>

                                <!-- Instructions -->
                                <input v-model="item.instructions" :placeholder="isRtl ? '\u062A\u0639\u0644\u064A\u0645\u0627\u062A...' : 'Instructions...'" class="doctorato-input col-span-5 lg:col-span-2 px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" />

                                <!-- Remove -->
                                <button type="button" @click="removeItem(index)" class="col-span-1 flex items-center justify-center h-10 text-gray-300 hover:text-red-500 transition-colors" :class="form.items.length <= 1 ? 'invisible' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-emerald-500 to-emerald-500 hover:from-emerald-600 hover:to-emerald-600 rounded-xl transition-all disabled:opacity-50 shadow-lg shadow-emerald-200/50">
                            <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            {{ form.processing ? (isRtl ? '\u062C\u0627\u0631\u064A \u0627\u0644\u0625\u0646\u0634\u0627\u0621...' : 'Creating...') : (isRtl ? '\u0625\u0646\u0634\u0627\u0621 \u0648\u0635\u0641\u0629' : 'Create Prescription') }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- ══════ SEARCH BAR ══════ -->
        <div class="mb-0"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
        >
            <div class="relative max-w-md">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? '\u0628\u062D\u062B \u0628\u0627\u0644\u0627\u0633\u0645\u060C \u0627\u0644\u0647\u0627\u062A\u0641\u060C \u0631\u0642\u0645 \u0627\u0644\u0645\u0644\u0641...' : 'Search by patient name, phone, file #...'" class="doctorato-input w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/50 focus:border-[#1B365D] transition-all" />
            </div>
        </div>

        <!-- ══════ RECENT PRESCRIPTIONS ══════ -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50/50 to-transparent">
                <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ isRtl ? '\u0627\u0644\u0648\u0635\u0641\u0627\u062A \u0627\u0644\u0623\u062E\u064A\u0631\u0629' : 'Recent Prescriptions' }}
                </h3>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-50/50">
                            <th class="ltr:text-left rtl:text-right px-3 sm:px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? '\u0627\u0644\u0645\u0631\u064A\u0636' : 'Patient' }}</th>
                            <th class="ltr:text-left rtl:text-right px-3 sm:px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? '\u0627\u0644\u062A\u0634\u062E\u064A\u0635' : 'Diagnosis' }}</th>
                            <th class="text-center px-3 sm:px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? '\u0623\u062F\u0648\u064A\u0629' : 'Meds' }}</th>
                            <th class="ltr:text-left rtl:text-right px-3 sm:px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">{{ isRtl ? '\u0627\u0644\u062A\u0627\u0631\u064A\u062E' : 'Date' }}</th>
                            <th class="ltr:text-right rtl:text-left px-3 sm:px-5 py-3.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ isRtl ? '\u0627\u0644\u0625\u062C\u0631\u0627\u0621\u0627\u062A' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="rx in prescriptions.data" :key="rx.id" class="hover:bg-emerald-50/30 transition-colors group">
                            <td class="px-3 sm:px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400/15 to-emerald-400/10 flex items-center justify-center text-emerald-600 font-bold text-xs flex-shrink-0">{{ rx.patient?.full_name?.charAt(0) || '?' }}</div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-[13px]">{{ rx.patient?.full_name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] font-mono text-emerald-600 font-semibold">{{ rx.patient?.file_number }}</span>
                                            <span v-if="hasMedicalNotes(rx.patient)" class="inline-flex items-center gap-0.5 text-[9px] font-bold text-red-500 bg-red-50 px-1.5 py-0.5 rounded-full">
                                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                {{ isRtl ? '\u062A\u0646\u0628\u064A\u0647' : 'Alert' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 sm:px-5 py-3.5 text-gray-600 max-w-[200px]"><p class="truncate text-[13px]">{{ rx.diagnosis || '-' }}</p></td>
                            <td class="px-3 sm:px-5 py-3.5 text-center"><span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 text-xs font-bold">{{ rx.items?.length || 0 }}</span></td>
                            <td class="px-3 sm:px-5 py-3.5 hidden md:table-cell">
                                <span class="text-[13px] text-gray-500">{{ formatDate(rx.created_at) }}</span>
                                <p class="text-[10px] text-gray-400 mt-0.5">Rx #{{ rx.id }}</p>
                            </td>
                            <td class="px-3 sm:px-5 py-3.5 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <button @click="openView(rx)" class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" :title="isRtl ? '\u0639\u0631\u0636' : 'View'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </button>
                                    <button @click="printPdf(rx.id)" class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" :title="isRtl ? '\u0637\u0628\u0627\u0639\u0629' : 'Print'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    </button>
                                    <button @click="downloadPdf(rx.id)" class="p-1.5 text-gray-500 hover:text-[#1B365D] hover:bg-slate-50 rounded-lg transition-colors" :title="isRtl ? '\u062A\u062D\u0645\u064A\u0644 PDF' : 'Download PDF'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="sm:hidden divide-y divide-gray-50">
                <div v-for="rx in prescriptions.data" :key="'m-'+rx.id" class="p-4 hover:bg-emerald-50/30 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400/15 to-emerald-400/10 flex items-center justify-center text-emerald-600 font-bold text-sm flex-shrink-0">{{ rx.patient?.full_name?.charAt(0) || '?' }}</div>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 text-sm truncate">{{ rx.patient?.full_name }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] font-mono text-emerald-600 font-semibold">{{ rx.patient?.file_number }}</span>
                                    <span class="text-[10px] text-gray-400">{{ formatDate(rx.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 text-xs font-bold flex-shrink-0">{{ rx.items?.length || 0 }}</span>
                    </div>
                    <p v-if="rx.diagnosis" class="text-xs text-gray-500 mt-2 truncate">{{ rx.diagnosis }}</p>
                    <div class="flex items-center gap-2 mt-3">
                        <button @click="openView(rx)" class="flex-1 text-center py-1.5 text-xs font-semibold text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors">{{ isRtl ? '\u0639\u0631\u0636' : 'View' }}</button>
                        <button @click="printPdf(rx.id)" class="flex-1 text-center py-1.5 text-xs font-semibold text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">{{ isRtl ? '\u0637\u0628\u0627\u0639\u0629' : 'Print' }}</button>
                        <button @click="downloadPdf(rx.id)" class="flex-1 text-center py-1.5 text-xs font-semibold text-[#1B365D] bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">PDF</button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="prescriptions.data?.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm font-semibold text-gray-500">{{ isRtl ? '\u0644\u0627 \u062A\u0648\u062C\u062F \u0648\u0635\u0641\u0627\u062A' : 'No prescriptions found' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ isRtl ? '\u0623\u0646\u0634\u0626 \u0623\u0648\u0644 \u0648\u0635\u0641\u0629 \u0628\u0627\u0633\u062A\u062E\u062F\u0627\u0645 \u0627\u0644\u0632\u0631 \u0623\u0639\u0644\u0627\u0647' : 'Create your first prescription using the button above' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="prescriptions.links?.length > 3" class="flex flex-wrap items-center justify-center gap-1 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in prescriptions.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-emerald-500 text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- ══════ VIEW MODAL ══════ -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="viewingPrescription" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="viewingPrescription = null">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                        <!-- Modal Header -->
                        <div class="sticky top-0 z-10 bg-gradient-to-r from-emerald-500 to-emerald-500 px-5 py-4 rounded-t-2xl flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-white">{{ isRtl ? '\u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0648\u0635\u0641\u0629' : 'Prescription Details' }}</h3>
                                <p class="text-emerald-100 text-xs">Rx #{{ viewingPrescription.id }}</p>
                            </div>
                            <button @click="viewingPrescription = null" class="p-1.5 text-white/70 hover:text-white hover:bg-white/20 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-5 space-y-4">
                            <!-- Patient info -->
                            <div class="flex items-center gap-3 pb-3 border-b border-gray-100">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400/15 to-emerald-400/10 flex items-center justify-center text-emerald-600 font-bold text-sm">{{ viewingPrescription.patient?.full_name?.charAt(0) || '?' }}</div>
                                <div>
                                    <p class="font-bold text-gray-800">{{ viewingPrescription.patient?.full_name }}</p>
                                    <p class="text-xs text-gray-400">{{ viewingPrescription.patient?.file_number }} &middot; {{ formatDate(viewingPrescription.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Diagnosis -->
                            <div v-if="viewingPrescription.diagnosis">
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">{{ isRtl ? '\u0627\u0644\u062A\u0634\u062E\u064A\u0635' : 'Diagnosis' }}</p>
                                <p class="text-sm text-gray-700">{{ viewingPrescription.diagnosis }}</p>
                            </div>

                            <!-- Medications -->
                            <div>
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-2">{{ isRtl ? '\u0627\u0644\u0623\u062F\u0648\u064A\u0629' : 'Medications' }}</p>
                                <div v-for="(item, i) in viewingPrescription.items" :key="i" class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/50 mb-2">
                                    <span class="w-6 h-6 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ i + 1 }}</span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-800 text-sm">{{ item.medication_name }}</p>
                                        <div class="flex flex-wrap gap-2 mt-1 text-[11px] text-gray-500">
                                            <span v-if="item.dosage" class="px-2 py-0.5 bg-white rounded-md">{{ item.dosage }}</span>
                                            <span v-if="item.frequency" class="px-2 py-0.5 bg-white rounded-md">{{ item.frequency }}</span>
                                            <span v-if="item.duration" class="px-2 py-0.5 bg-white rounded-md">{{ item.duration }}</span>
                                        </div>
                                        <p v-if="item.instructions" class="text-xs text-gray-400 mt-1">{{ item.instructions }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div v-if="viewingPrescription.notes">
                                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0627\u062A' : 'Notes' }}</p>
                                <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl">{{ viewingPrescription.notes }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 pt-2">
                                <button @click="printPdf(viewingPrescription.id)" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    {{ isRtl ? '\u0637\u0628\u0627\u0639\u0629' : 'Print' }}
                                </button>
                                <button @click="downloadPdf(viewingPrescription.id)" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-[#1B365D] bg-slate-50 hover:bg-slate-100 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
