<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },
    isRtl: {
        type: Boolean,
        default: false,
    },
    isEditable: {
        type: Boolean,
        default: false,
    },
    mounted: {
        type: Boolean,
        default: false,
    },
});

// ─── Prescription Create Form ────────────────────
const showPrescriptionForm = ref(false);
const prescriptionForm = useForm({
    patient_id: props.visit.patient_id,
    visit_id: props.visit.id,
    diagnosis: props.visit.diagnosis || '',
    notes: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

function addMedItem() {
    prescriptionForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removeMedItem(index) {
    if (prescriptionForm.items.length > 1) prescriptionForm.items.splice(index, 1);
}

// Medication autocomplete
const medicationSuggestions = ref({});
let medSearchTimeout = null;

function searchMedication(index, query) {
    clearTimeout(medSearchTimeout);
    if (!query || query.length < 2) {
        delete medicationSuggestions.value[index];
        return;
    }
    medSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/doctor/api/medications?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.ok) {
                const data = await res.json();
                medicationSuggestions.value[index] = data;
            }
        } catch (e) { console.error('Medication search failed:', e); }
    }, 300);
}

function selectMedication(index, med) {
    prescriptionForm.items[index].medication_name = med.name;
    if (med.default_dosage) prescriptionForm.items[index].dosage = med.default_dosage;
    if (med.default_frequency) prescriptionForm.items[index].frequency = med.default_frequency;
    delete medicationSuggestions.value[index];
}

function submitPrescription() {
    prescriptionForm.post('/doctor/prescriptions', {
        onSuccess: () => {
            showPrescriptionForm.value = false;
            prescriptionForm.reset();
            prescriptionForm.patient_id = props.visit.patient_id;
            prescriptionForm.visit_id = props.visit.id;
            prescriptionForm.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
        },
    });
}
</script>

<template>
    <!-- Prescriptions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
    >
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
                <h3 class="text-sm font-bold text-gray-800">{{ isRtl ? 'الوصفات الطبية' : 'Prescriptions' }}</h3>
                <span v-if="visit.prescriptions?.length" class="text-[10px] font-bold text-[#C4A265] bg-amber-50 px-1.5 py-0.5 rounded-full border border-amber-100">{{ visit.prescriptions.length }}</span>
            </div>
            <button v-if="isEditable && !showPrescriptionForm" @click="showPrescriptionForm = true"
                class="inline-flex items-center gap-1 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] px-3 py-1.5 rounded-lg transition-all shadow-sm hover:shadow"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                {{ isRtl ? 'إضافة' : 'Add' }}
            </button>
        </div>
        <div class="p-6">
            <!-- Inline Prescription Form -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-3" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-3">
                <div v-if="showPrescriptionForm" class="bg-gray-50 rounded-xl p-5 mb-5 border border-gray-200">
                    <form @submit.prevent="submitPrescription" class="space-y-4">
                        <div class="grid lg:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs text-gray-500 font-medium mb-1 block">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</label>
                                <input v-model="prescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'التشخيص...' : 'Diagnosis...'" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 font-medium mb-1 block">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="prescriptionForm.notes" type="text" class="doctorato-input w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" :placeholder="isRtl ? 'ملاحظات...' : 'Notes...'" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs font-bold text-gray-500 uppercase">{{ isRtl ? 'الأدوية' : 'Medications' }}</label>
                                <button type="button" @click="addMedItem" class="text-xs text-[#C4A265] hover:text-[#A68B52] font-semibold transition-colors">{{ isRtl ? '+ إضافة دواء' : '+ Add Medication' }}</button>
                            </div>
                            <div v-for="(item, idx) in prescriptionForm.items" :key="idx" class="relative mb-3">
                                <div class="grid grid-cols-12 gap-1.5">
                                    <div class="col-span-3 relative">
                                        <input v-model="item.medication_name" @input="searchMedication(idx, item.medication_name)" :placeholder="isRtl ? 'الدواء *' : 'Medication *'" class="doctorato-input w-full px-2.5 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30" required />
                                        <div v-if="medicationSuggestions[idx]?.length" class="absolute z-20 top-full left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                            <button v-for="med in medicationSuggestions[idx]" :key="med.id" type="button" @click="selectMedication(idx, med)" class="w-full text-left px-3 py-2 text-xs hover:bg-[#C4A265]/5 border-b border-gray-50 last:border-0">
                                                <span class="font-medium text-gray-800">{{ med.name }}</span>
                                                <span v-if="med.generic_name" class="text-gray-400 ml-1">({{ med.generic_name }})</span>
                                            </button>
                                        </div>
                                    </div>
                                    <input v-model="item.dosage" :placeholder="isRtl ? 'الجرعة' : 'Dosage'" class="doctorato-input col-span-2 px-2.5 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30" />
                                    <input v-model="item.frequency" :placeholder="isRtl ? 'التكرار' : 'Frequency'" class="doctorato-input col-span-2 px-2.5 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30" />
                                    <input v-model="item.duration" :placeholder="isRtl ? 'المدة' : 'Duration'" class="doctorato-input col-span-2 px-2.5 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30" />
                                    <input v-model="item.instructions" :placeholder="isRtl ? 'تعليمات' : 'Instructions'" class="doctorato-input col-span-2 px-2.5 py-2 border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-[#C4A265]/30" />
                                    <button type="button" @click="removeMedItem(idx)" class="col-span-1 flex items-center justify-center text-gray-300 hover:text-red-500 rounded-lg hover:bg-red-50 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showPrescriptionForm = false" class="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                            <button type="submit" :disabled="prescriptionForm.processing" class="px-5 py-2 text-xs font-semibold text-white bg-[#C4A265] hover:bg-[#A68B52] rounded-lg transition-colors shadow-sm disabled:opacity-50">
                                {{ prescriptionForm.processing ? (isRtl ? 'جاري الحفظ...' : 'Saving...') : (isRtl ? 'إنشاء وصفة' : 'Create Prescription') }}
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>

            <!-- Existing Prescriptions -->
            <div v-if="visit.prescriptions?.length > 0" class="space-y-3">
                <div v-for="prescription in visit.prescriptions" :key="prescription.id" class="border border-gray-100 rounded-xl p-4 hover:border-gray-200 transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-amber-50 flex items-center justify-center">
                                <svg class="w-3 h-3 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <p class="text-xs text-gray-400">{{ prescription.created_at }} <span v-if="prescription.diagnosis" class="text-gray-600 font-medium"> &middot; {{ prescription.diagnosis }}</span></p>
                        </div>
                        <button @click="window.open(`/doctor/prescriptions/${prescription.id}/pdf`, '_blank')" class="inline-flex items-center gap-1 text-xs font-medium text-[#C4A265] hover:text-[#A68B52] hover:bg-[#C4A265]/5 px-2 py-1 rounded-lg transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            PDF
                        </button>
                    </div>
                    <div v-for="item in prescription.items" :key="item.id" class="flex items-center gap-2 text-sm py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C4A265] flex-shrink-0"></span>
                        <span class="font-medium text-gray-800">{{ item.medication_name }}</span>
                        <span class="text-gray-400">{{ [item.dosage, item.frequency, item.duration].filter(Boolean).join(' · ') }}</span>
                    </div>
                    <p v-if="prescription.notes" class="text-xs text-gray-400 mt-2 italic pl-3.5">{{ prescription.notes }}</p>
                </div>
            </div>
            <div v-else-if="!showPrescriptionForm" class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-50 rounded-xl flex items-center justify-center mb-3 border border-gray-100">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                </div>
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد وصفات بعد' : 'No prescriptions yet' }}</p>
            </div>
        </div>
    </div>
</template>
