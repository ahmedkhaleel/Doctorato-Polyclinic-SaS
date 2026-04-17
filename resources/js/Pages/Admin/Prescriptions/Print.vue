<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescription: Object,
});

const rx = props.prescription;

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

function printPage() {
    window.print();
}
</script>

<template>
    <div class="print-page">
        <!-- Screen-only toolbar -->
        <div class="no-print fixed top-0 left-0 right-0 bg-gray-800 text-white px-4 md:px-6 py-3 flex items-center justify-between z-50">
            <span class="text-sm">Prescription Preview</span>
            <div class="flex space-x-3 rtl:space-x-reverse">
                <button
                    @click="printPage"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print
                </button>
                <a
                    :href="`/admin/prescriptions/${rx.id}/pdf`"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-emerald-600 hover:bg-emerald-500 transition"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
                <button
                    @click="$inertia ? window.history.back() : window.close()"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-600 hover:bg-gray-500 transition"
                >
                    Close
                </button>
            </div>
        </div>

        <!-- Printable content -->
        <div class="print-content mx-auto" style="max-width: 800px; padding: 80px 40px 40px 40px;">

            <!-- Clinic Header -->
            <div class="text-center border-b-2 pb-4 mb-6" style="border-color: #C4A265;">
                <div class="flex items-center justify-center mb-2">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl md:text-2xl font-bold mr-4" style="background-color: #C4A265;">
                        A
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold tracking-wide" style="color: #C4A265;">Doctorato Polyclinic</h1>
                        <p class="text-sm text-gray-500">Doctorato Polyclinic</p>
                    </div>
                </div>
            </div>

            <!-- Prescription Title -->
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">Medical Prescription</h2>
                <p class="text-sm text-gray-400 mt-1">Date: {{ formatDate(rx.created_at) }}</p>
            </div>

            <!-- Patient & Doctor Info -->
            <div class="grid grid-cols-2 gap-6 mb-6 border rounded-lg p-4" style="border-color: #e5e7eb;">
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Patient Information</h3>
                    <table class="text-sm">
                        <tr>
                            <td class="text-gray-500 pr-3 py-1">Name:</td>
                            <td class="text-gray-900 font-medium py-1">{{ rx.patient?.full_name }}</td>
                        </tr>
                        <tr v-if="rx.patient?.file_number">
                            <td class="text-gray-500 pr-3 py-1">File #:</td>
                            <td class="font-mono py-1" style="color: #C4A265;">{{ rx.patient?.file_number }}</td>
                        </tr>
                        <tr v-if="rx.patient?.age">
                            <td class="text-gray-500 pr-3 py-1">Age:</td>
                            <td class="text-gray-900 py-1">{{ rx.patient?.age }} years</td>
                        </tr>
                        <tr v-if="rx.patient?.phone">
                            <td class="text-gray-500 pr-3 py-1">Phone:</td>
                            <td class="text-gray-900 py-1">{{ rx.patient?.phone }}</td>
                        </tr>
                    </table>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Doctor Information</h3>
                    <table class="text-sm">
                        <tr>
                            <td class="text-gray-500 pr-3 py-1">Doctor:</td>
                            <td class="text-gray-900 font-medium py-1">Dr. {{ rx.doctor?.name_en || 'Unknown' }}</td>
                        </tr>
                        <tr v-if="rx.doctor?.specialization_en">
                            <td class="text-gray-500 pr-3 py-1">Specialization:</td>
                            <td class="text-gray-900 py-1">{{ rx.doctor?.specialization_en }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Diagnosis -->
            <div v-if="rx.diagnosis" class="mb-6 p-4 rounded-lg" style="background-color: rgba(196, 162, 101, 0.08);">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Diagnosis</h3>
                <p class="text-sm text-gray-800 font-medium">{{ rx.diagnosis }}</p>
            </div>

            <!-- Medications Table -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Prescribed Medications</h3>
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr style="background-color: rgba(196, 162, 101, 0.1);">
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">#</th>
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">{{ $t('a_medication') }}</th>
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">{{ $t('a_dosage') }}</th>
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">{{ $t('a_frequency') }}</th>
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">{{ $t('a_duration') }}</th>
                            <th class="border px-3 py-2 ltr:text-left rtl:text-right text-xs font-semibold text-gray-600 uppercase">{{ $t('a_instructions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in rx.items" :key="item.id">
                            <td class="border px-3 py-2 text-gray-400">{{ index + 1 }}</td>
                            <td class="border px-3 py-2 text-gray-900 font-medium">{{ item.medication_name }}</td>
                            <td class="border px-3 py-2 text-gray-600">{{ item.dosage || '-' }}</td>
                            <td class="border px-3 py-2 text-gray-600">{{ item.frequency || '-' }}</td>
                            <td class="border px-3 py-2 text-gray-600">{{ item.duration || '-' }}</td>
                            <td class="border px-3 py-2 text-gray-600">{{ item.instructions || '-' }}</td>
                        </tr>
                        <tr v-if="!rx.items?.length">
                            <td colspan="6" class="border px-3 py-4 text-center text-gray-400">No medications listed.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Doctor Signature -->
            <div class="mt-16 flex justify-end">
                <div class="text-center" style="min-width: 250px;">
                    <div class="border-b border-gray-400 mb-2" style="height: 60px;"></div>
                    <p class="text-sm font-medium text-gray-700">Dr. {{ rx.doctor?.name_en || 'Unknown' }}</p>
                    <p v-if="rx.doctor?.specialization_en" class="text-xs text-gray-500">{{ rx.doctor?.specialization_en }}</p>
                    <p class="text-xs text-gray-400 mt-1">Signature & Stamp</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-12 pt-4 border-t text-center text-xs text-gray-400">
                <p>Doctorato Polyclinic &middot; This prescription is valid for one month from the date of issue.</p>
            </div>
        </div>
    </div>
</template>

<style>
/* Screen styles */
.print-page {
    background-color: #f3f4f6;
    min-height: 100vh;
}

.print-content {
    background-color: #ffffff;
    min-height: 100vh;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }

    .print-page {
        background-color: #ffffff;
    }

    .print-content {
        padding: 20px !important;
        max-width: 100% !important;
        box-shadow: none;
    }

    body {
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page {
        margin: 15mm;
        size: A4;
    }

    table {
        page-break-inside: avoid;
    }
}
</style>
