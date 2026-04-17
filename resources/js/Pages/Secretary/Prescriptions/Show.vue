<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    prescription: Object,
});

const rx = props.prescription;

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/secretary/prescriptions" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0 bg-gradient-to-r from-teal-500 to-[#1B365D]">
                    {{ rx.patient?.full_name?.charAt(0) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'الوصفة الطبية' : 'Prescription' }}</h1>
                    <p class="text-sm text-gray-500">
                        <span class="text-teal-600 font-medium">{{ rx.patient?.full_name }}</span>
                        <span class="mx-1">&middot;</span>
                        <span>{{ formatDate(rx.created_at) }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    :href="`/secretary/prescriptions/${rx.id}/print`"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-medium transition bg-gradient-to-r from-teal-500 to-[#1B365D] hover:from-teal-600 hover:to-[#1B365D] shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    {{ isRtl ? 'طباعة' : 'Print' }}
                </Link>
                <a
                    :href="`/secretary/prescriptions/${rx.id}/pdf`"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    PDF
                </a>
            </div>
        </div>

        <!-- Prescription Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2">{{ isRtl ? 'معلومات المريض' : 'Patient Information' }}</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'الاسم' : 'Name' }}</dt>
                            <dd class="text-gray-900 font-medium">{{ rx.patient?.full_name }}</dd>
                        </div>
                        <div v-if="rx.patient?.file_number" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'رقم الملف' : 'File Number' }}</dt>
                            <dd class="font-mono text-teal-600">{{ rx.patient?.file_number }}</dd>
                        </div>
                        <div v-if="rx.patient?.phone" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'الهاتف' : 'Phone' }}</dt>
                            <dd class="text-gray-900">{{ rx.patient?.phone }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 border-b border-gray-100 pb-2">{{ isRtl ? 'تفاصيل الوصفة' : 'Prescription Details' }}</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'الطبيب' : 'Doctor' }}</dt>
                            <dd class="text-gray-900 font-medium">{{ isRtl ? 'د.' : 'Dr.' }} {{ rx.doctor?.name_en || rx.doctor?.name_ar || '-' }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'التاريخ' : 'Date' }}</dt>
                            <dd class="text-gray-900">{{ formatDate(rx.created_at) }}</dd>
                        </div>
                        <div v-if="rx.diagnosis" class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</dt>
                            <dd class="text-gray-900">{{ rx.diagnosis }}</dd>
                        </div>
                        <div v-if="rx.notes">
                            <dt class="text-sm text-gray-500 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</dt>
                            <dd class="text-sm text-gray-700 bg-teal-50/50 p-3 rounded-lg whitespace-pre-wrap">{{ rx.notes }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Medications Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6">
            <h2 class="text-sm font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">{{ isRtl ? 'الأدوية' : 'Medications' }}</h2>
            <div class="overflow-x-auto">
                <table v-if="rx.items?.length" class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الدواء' : 'Medication' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الجرعة' : 'Dosage' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التكرار' : 'Frequency' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'التعليمات' : 'Instructions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(item, index) in rx.items" :key="item.id" class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-gray-400">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ item.medication_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.dosage || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.frequency || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.duration || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.instructions || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-gray-400 text-center py-8">{{ isRtl ? 'لا توجد أدوية في هذه الوصفة' : 'No medications in this prescription' }}</p>
            </div>
        </div>
    </div>
</template>
