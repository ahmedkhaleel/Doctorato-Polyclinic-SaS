<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescriptions: Object,
    filters: Object,
    doctors: Array,
    patients: Array,
});

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    };
}

function applyFilters() {
    router.get('/admin/prescriptions', buildParams(), {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch([dateFrom, dateTo], () => {
    applyFilters();
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function truncate(text, length = 40) {
    if (!text) return '-';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function deletePrescription(rxId) {
    if (window.confirm(t('a_confirm_delete_prescription'))) {
        router.post(`/admin/prescriptions/${rxId}/delete`, {
            preserveScroll: true,
        });
    }
}

// New Prescription
const showNewPrescription = ref(false);
const prescriptionForm = useForm({
    patient_id: '',
    doctor_id: '',
    diagnosis: '',
    notes: '',
    items: [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

const doctorOptions = computed(() => (props.doctors || []).map(d => ({ value: d.id, label: d.name_en })));

const patientSearch = ref('');
const filteredPatients = ref([]);
const showPatientDropdown = ref(false);

function searchPatients(query) {
    patientSearch.value = query;
    if (!query || query.length < 2) {
        filteredPatients.value = [];
        showPatientDropdown.value = false;
        return;
    }
    const q = query.toLowerCase();
    filteredPatients.value = (props.patients || [])
        .filter(p => p.full_name?.toLowerCase().includes(q) || p.phone?.includes(q) || p.file_number?.includes(q))
        .slice(0, 10);
    showPatientDropdown.value = filteredPatients.value.length > 0;
}

function selectPatient(patient) {
    prescriptionForm.patient_id = patient.id;
    patientSearch.value = patient.full_name;
    showPatientDropdown.value = false;
    filteredPatients.value = [];
}

const medicationSuggestions = ref([]);
const activeMedIdx = ref(-1);
let medSearchTimeout = null;

function searchMedications(query, index) {
    activeMedIdx.value = index;
    clearTimeout(medSearchTimeout);
    if (!query || query.length < 2) {
        medicationSuggestions.value = [];
        return;
    }
    medSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`/admin/api/medications?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            medicationSuggestions.value = data;
        } catch {
            medicationSuggestions.value = [];
        }
    }, 300);
}

function selectMedication(med, index) {
    prescriptionForm.items[index].medication_name = med.name;
    if (med.default_dosage) prescriptionForm.items[index].dosage = med.default_dosage;
    if (med.default_frequency) prescriptionForm.items[index].frequency = med.default_frequency;
    medicationSuggestions.value = [];
    activeMedIdx.value = -1;
}

function addPrescriptionItem() {
    prescriptionForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removePrescriptionItem(index) {
    if (prescriptionForm.items.length > 1) {
        prescriptionForm.items.splice(index, 1);
    }
}

function submitPrescription() {
    prescriptionForm
        .transform((data) => ({
            ...data,
            visit_id: null,
        }))
        .post('/admin/prescriptions', {
            preserveScroll: true,
            onSuccess: () => {
                prescriptionForm.reset();
                prescriptionForm.items = [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
                patientSearch.value = '';
                showNewPrescription.value = false;
            },
        });
}
</script>

<template>
    <AdminLayout :title="$t('a_prescriptions')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_prescriptions') }}</h1>
                <button
                    v-if="can('prescriptions.create')"
                    @click="showNewPrescription = !showNewPrescription"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_new_prescription') }}
                </button>
            </div>

            <!-- New Prescription Form -->
            <div v-if="showNewPrescription" class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('a_create_new_prescription') }}</h2>
                <form @submit.prevent="submitPrescription" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Patient Search -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_patient') }} <span class="text-red-500">*</span></label>
                            <input
                                v-model="patientSearch"
                                @input="searchPatients(patientSearch)"
                                @focus="searchPatients(patientSearch)"
                                @blur="setTimeout(() => showPatientDropdown = false, 200)"
                                type="text"
                                class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                :placeholder="$t('a_search_patient')"
                                autocomplete="off"
                            />
                            <div v-if="showPatientDropdown && filteredPatients.length" class="absolute z-20 mt-1 w-full bg-white rounded-lg shadow-lg border max-h-48 overflow-y-auto">
                                <button
                                    v-for="p in filteredPatients"
                                    :key="p.id"
                                    type="button"
                                    @mousedown.prevent="selectPatient(p)"
                                    class="w-full ltr:text-left rtl:text-right px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
                                >
                                    <span class="font-medium text-gray-900">{{ p.full_name }}</span>
                                    <span v-if="p.phone" class="text-gray-400 ml-2">{{ p.phone }}</span>
                                    <span v-if="p.file_number" class="text-gray-400 ml-1 font-mono text-xs">#{{ p.file_number }}</span>
                                </button>
                            </div>
                            <p v-if="prescriptionForm.errors.patient_id" class="mt-1 text-xs text-red-600">{{ prescriptionForm.errors.patient_id }}</p>
                        </div>

                        <!-- Doctor Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_doctor_label') }} <span class="text-red-500">*</span></label>
                            <SearchableSelect v-model="prescriptionForm.doctor_id" :options="doctorOptions" :placeholder="$t('a_select_doctor')" :searchPlaceholder="$t('a_search_doctors')" />
                            <p v-if="prescriptionForm.errors.doctor_id" class="mt-1 text-xs text-red-600">{{ prescriptionForm.errors.doctor_id }}</p>
                        </div>

                        <!-- Diagnosis -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_diagnosis') }}</label>
                            <input v-model="prescriptionForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_diagnosis')" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                            <input v-model="prescriptionForm.notes" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_optional_notes')" />
                        </div>
                    </div>

                    <!-- Medication Items -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('a_medications') }}</label>
                        <div v-for="(item, idx) in prescriptionForm.items" :key="idx" class="flex flex-wrap gap-2 mb-3 items-start p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex-1 min-w-[160px] relative">
                                <input
                                    v-model="item.medication_name"
                                    @input="searchMedications(item.medication_name, idx)"
                                    @blur="setTimeout(() => { if (activeMedIdx === idx) medicationSuggestions = []; }, 200)"
                                    type="text"
                                    class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm"
                                    :placeholder="$t('a_medication_name') + ' *'"
                                />
                                <div v-if="activeMedIdx === idx && medicationSuggestions.length" class="absolute z-10 mt-1 w-full bg-white rounded-lg shadow-lg border max-h-40 overflow-y-auto">
                                    <button
                                        v-for="med in medicationSuggestions"
                                        :key="med.id"
                                        type="button"
                                        @mousedown.prevent="selectMedication(med, idx)"
                                        class="w-full ltr:text-left rtl:text-right px-3 py-2 text-sm hover:bg-gray-50 border-b last:border-b-0"
                                    >
                                        <span class="font-medium text-gray-900">{{ med.name }}</span>
                                        <span v-if="med.default_dosage" class="text-gray-400 ml-2">{{ med.default_dosage }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="w-28">
                                <input v-model="item.dosage" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_dosage')" />
                            </div>
                            <div class="w-32">
                                <input v-model="item.frequency" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_frequency')" />
                            </div>
                            <div class="w-24">
                                <input v-model="item.duration" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_duration')" />
                            </div>
                            <div class="flex-1 min-w-[120px]">
                                <input v-model="item.instructions" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" :placeholder="$t('a_instructions')" />
                            </div>
                            <button
                                v-if="prescriptionForm.items.length > 1"
                                type="button"
                                @click="removePrescriptionItem(idx)"
                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="addPrescriptionItem"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-dashed border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400 transition"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ $t('a_add_medication') }}
                        </button>
                    </div>

                    <div class="flex space-x-3 pt-2 rtl:space-x-reverse">
                        <button
                            type="submit"
                            :disabled="prescriptionForm.processing"
                            class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ prescriptionForm.processing ? $t('a_saving') : $t('a_save_prescription') }}
                        </button>
                        <button
                            type="button"
                            @click="showNewPrescription = false"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50"
                        >
                            {{ $t('a_cancel') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_prescriptions')"
                    class="doctorato-input w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
                <select
                    v-if="activeModules.length> 1"
                    v-model="moduleFilter"
                    @change="applyFilters"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-200 focus:border-transparent"
                >
                    <option value="">{{ $t('a_all_departments') }}</option>
                    <option v-for="m in activeModules" :key="m.slug" :value="m.slug">{{ m.name }}</option>
                </select>
                <input
                    v-model="dateFrom"
                    type="date"
                    :max="dateTo || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    :placeholder="$t('a_from')"
                />
                <input
                    v-model="dateTo"
                    type="date"
                    :min="dateFrom || undefined"
                    class="doctorato-input px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                    :placeholder="$t('a_to_date')"
                />
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_date') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_doctor') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_diagnosis') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_medications') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_visit_date') }}</th>
                                <th class="px-4 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="rx in prescriptions.data" :key="rx.id" class="hover:bg-gray-50">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(rx.created_at) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full mr-3 flex-shrink-0 flex items-center justify-center text-white text-xs font-bold" style="background-color: #C4A265;">
                                            {{ rx.patient?.full_name?.charAt(0) }}
                                        </div>
                                        <div>
                                            <Link :href="`/admin/patients/${rx.patient_id}`" class="text-sm font-medium text-gray-900 hover:underline">{{ rx.patient?.full_name }}</Link>
                                            <div class="text-xs text-gray-400">{{ rx.patient?.phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ rx.doctor?.name_en || '-' }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500" :title="rx.diagnosis">{{ truncate(rx.diagnosis) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-[#1B365D]">
                                        {{ rx.items?.length || 0 }} {{ $t('a_items') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(rx.visit?.visit_date) }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-right text-sm space-x-3 rtl:space-x-reverse">
                                    <Link v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/print`" target="_blank" class="font-medium text-[#1B365D] hover:underline">{{ $t('a_print') }}</a>
                                    <a v-if="can('prescriptions.view')" :href="`/admin/prescriptions/${rx.id}/pdf`" class="font-medium text-emerald-600 hover:underline">{{ $t('a_pdf') }}</a>
                                    <button v-if="can('prescriptions.delete')" @click="deletePrescription(rx.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!prescriptions.data || prescriptions.data.length === 0">
                                <td colspan="7" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_prescriptions_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="prescriptions.links && prescriptions.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ prescriptions.from }} {{ $t('a_to') }} {{ prescriptions.to }} {{ $t('a_of') }} {{ prescriptions.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1 rtl:space-x-reverse">
                        <template v-for="link in prescriptions.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
