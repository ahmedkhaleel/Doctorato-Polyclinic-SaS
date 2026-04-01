<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    prescription: Object,
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
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('/prescriptions')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'تفاصيل الوصفة' : 'Prescription Details' }}</h1>
        </div>

        <!-- Prescription Header -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الطبيب' : 'Doctor' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ $localized(prescription?.doctor, 'name') || prescription?.doctor_name }}</p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 font-medium">{{ isRtl ? 'التاريخ' : 'Date' }}</span>
                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ prescription?.prescription_date || prescription?.created_at?.split('T')[0] }}</p>
                </div>
            </div>
        </div>

        <!-- Diagnosis -->
        <div v-if="prescription?.diagnosis" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">{{ isRtl ? 'التشخيص' : 'Diagnosis' }}</h2>
            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ prescription.diagnosis }}</p>
        </div>

        <!-- Medications Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">{{ isRtl ? 'الأدوية' : 'Medications' }}</h3>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الدواء' : 'Medication' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'الكمية' : 'Quantity' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'التكرار' : 'Frequency' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'المدة' : 'Duration' }}</th>
                            <th class="text-start px-6 py-3 font-medium text-gray-500 text-xs uppercase">{{ isRtl ? 'التعليمات' : 'Instructions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="med in prescription?.medications" :key="med.id" class="border-b border-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $localized(med, 'medication_name') || med.medication_name }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.quantity }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.frequency }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ med.duration }}</td>
                            <td class="px-6 py-3 text-gray-500 max-w-[200px]">{{ med.instructions || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden divide-y divide-gray-100">
                <div v-for="med in prescription?.medications" :key="med.id" class="p-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ $localized(med, 'medication_name') || med.medication_name }}</h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'الكمية' : 'Qty' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.quantity }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'التكرار' : 'Frequency' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.frequency }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400">{{ isRtl ? 'المدة' : 'Duration' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.duration }}</p>
                        </div>
                        <div v-if="med.instructions">
                            <span class="text-gray-400">{{ isRtl ? 'التعليمات' : 'Instructions' }}</span>
                            <p class="text-gray-700 font-medium">{{ med.instructions }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="!prescription?.medications?.length" class="text-center py-8 text-gray-400 text-sm">
                {{ isRtl ? 'لا توجد أدوية' : 'No medications listed' }}
            </div>
        </div>
    </div>
</template>
