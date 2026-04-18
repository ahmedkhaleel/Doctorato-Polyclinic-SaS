<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescription: Object,
});

const rx = props.prescription;

const editing = ref(false);
const editForm = useForm({
    diagnosis: rx.diagnosis || '',
    notes: rx.notes || '',
    items: rx.items?.length
        ? rx.items.map(i => ({
            medication_name: i.medication_name || '',
            dosage: i.dosage || '',
            frequency: i.frequency || '',
            duration: i.duration || '',
            instructions: i.instructions || '',
        }))
        : [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
});

function addItem() {
    editForm.items.push({ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' });
}

function removeItem(index) {
    if (editForm.items.length > 1) {
        editForm.items.splice(index, 1);
    }
}

function submitEdit() {
    editForm.put(`/admin/prescriptions/${rx.id}`, {
        preserveScroll: true,
        onSuccess: () => { editing.value = false; },
    });
}

function cancelEdit() {
    editForm.diagnosis = rx.diagnosis || '';
    editForm.notes = rx.notes || '';
    editForm.items = rx.items?.length
        ? rx.items.map(i => ({
            medication_name: i.medication_name || '',
            dosage: i.dosage || '',
            frequency: i.frequency || '',
            duration: i.duration || '',
            instructions: i.instructions || '',
        }))
        : [{ medication_name: '', dosage: '', frequency: '', duration: '', instructions: '' }];
    editing.value = false;
}

function deletePrescription() {
    if (window.confirm('Are you sure you want to delete this prescription?')) {
        router.post(`/admin/prescriptions/${rx.id}/delete`, {
            onSuccess: () => {
                window.history.back();
            },
        });
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}
</script>

<template>
    <AdminLayout :title="`Prescription - ${rx.patient?.full_name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0" style="background-color: #C4A265;">
                        {{ rx.patient?.full_name?.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-800">Prescription</h1>
                        <p class="text-sm text-gray-500">
                            <Link v-if="can('patients.view')" :href="`/admin/patients/${rx.patient?.id}`" class="hover:underline" style="color: #C4A265;">{{ rx.patient?.full_name }}</Link>
                            <span v-else>{{ rx.patient?.full_name }}</span>
                            <span class="mx-1">&middot;</span>
                            <span>{{ formatDate(rx.created_at) }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex space-x-3 rtl:space-x-reverse">
                    <Link
                        :href="`/admin/prescriptions/${rx.id}/print`"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                        style="background-color: #C4A265;"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                    </Link>
                    <a
                        :href="`/admin/prescriptions/${rx.id}/pdf`"
                        class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition border-emerald-400 text-emerald-700 hover:bg-emerald-50"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                    </a>
                    <button
                        v-if="can('prescriptions.update') && !editing"
                        @click="editing = true"
                        class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition"
                        style="border-color: #C4A265; color: #C4A265;"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                    <button
                        v-if="can('prescriptions.delete')"
                        @click="deletePrescription"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-red-300 text-red-600 text-sm font-medium transition hover:bg-red-50"
                    >
                        Delete
                    </button>
                    <Link
                        v-if="rx.visit"
                        :href="`/admin/visits/${rx.visit.id}`"
                        class="inline-flex items-center px-4 py-2 rounded-lg border text-sm font-medium transition border-gray-300 text-gray-600 hover:bg-gray-50"
                    >
                        View Visit
                    </Link>
                </div>
            </div>

            <!-- View Mode -->
            <div v-if="!editing">
                <!-- Prescription Info -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">Patient Information</h3>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Name</dt>
                                    <dd class="text-gray-900">{{ rx.patient?.full_name }}</dd>
                                </div>
                                <div v-if="rx.patient?.file_number" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">File Number</dt>
                                    <dd class="font-mono" style="color: #C4A265;">{{ rx.patient?.file_number }}</dd>
                                </div>
                                <div v-if="rx.patient?.phone" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="text-gray-900">{{ rx.patient?.phone }}</dd>
                                </div>
                                <div v-if="rx.patient?.age" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Age</dt>
                                    <dd class="text-gray-900">{{ rx.patient?.age }} years</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">Prescription Details</h3>
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Doctor</dt>
                                    <dd>
                                        <Link v-if="rx.doctor && can('doctors.view')" :href="`/admin/doctors/${rx.doctor.id}`" class="hover:underline" style="color: #C4A265;">Dr. {{ rx.doctor.name_en }}</Link>
                                        <span v-else class="text-gray-900">Dr. {{ rx.doctor?.name_en || 'Unknown' }}</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Date</dt>
                                    <dd class="text-gray-900">{{ formatDate(rx.created_at) }}</dd>
                                </div>
                                <div v-if="rx.diagnosis" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Diagnosis</dt>
                                    <dd class="text-gray-900">{{ rx.diagnosis }}</dd>
                                </div>
                                <div v-if="rx.notes">
                                    <dt class="text-sm text-gray-500 mb-1">Notes</dt>
                                    <dd class="text-sm text-gray-700 bg-amber-50 p-3 rounded-lg whitespace-pre-wrap">{{ rx.notes }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Medications Table -->
                <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 mt-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('a_medications') }}</h2>
                    <div class="overflow-x-auto">
                        <table v-if="rx.items?.length" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_medication') }}</th>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_dosage') }}</th>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_frequency') }}</th>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_duration') }}</th>
                                    <th class="px-4 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase">{{ $t('a_instructions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="(item, index) in rx.items" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-400">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.medication_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ item.dosage || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ item.frequency || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ item.duration || '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ item.instructions || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-gray-500 text-center py-8">No medications in this prescription.</p>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div v-else class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit Prescription</h2>
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                            <input v-model="editForm.diagnosis" type="text" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Diagnosis" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea v-model="editForm.notes" rows="2" class="doctorato-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Optional notes"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Medications</label>
                        <div v-for="(item, idx) in editForm.items" :key="idx" class="flex flex-wrap gap-2 mb-3 items-start p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex-1 min-w-[160px]">
                                <input v-model="item.medication_name" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Medication name *" />
                            </div>
                            <div class="w-28">
                                <input v-model="item.dosage" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Dosage" />
                            </div>
                            <div class="w-32">
                                <input v-model="item.frequency" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Frequency" />
                            </div>
                            <div class="w-24">
                                <input v-model="item.duration" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Duration" />
                            </div>
                            <div class="flex-1 min-w-[120px]">
                                <input v-model="item.instructions" type="text" class="doctorato-input w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm" placeholder="Instructions" />
                            </div>
                            <button v-if="editForm.items.length > 1" type="button" @click="removeItem(idx)" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                        <button type="button" @click="addItem" class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg border border-dashed border-gray-300 text-gray-500 hover:text-gray-700 hover:border-gray-400 transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Add Medication
                        </button>
                    </div>

                    <div class="flex space-x-3 pt-2 rtl:space-x-reverse">
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition disabled:opacity-50" style="background-color: #C4A265;">
                            {{ editForm.processing ? 'Saving...' : 'Update Prescription' }}
                        </button>
                        <button type="button" @click="cancelEdit" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-medium transition hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
