<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    prescriptions: { type: Array, default: () => [] },
    patient: { type: Object, required: true },
    role: { type: String, default: 'admin' },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function prescriptionHref(rx) {
    if (props.role === 'admin') return `/admin/prescriptions/${rx.id}`;
    if (props.role === 'doctor') return `/doctor/prescriptions/${rx.id}`;
    if (props.role === 'secretary') return `/secretary/prescriptions/${rx.id}`;
    return null;
}

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch { return d; }
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="prescriptions.length" class="space-y-3">
            <div v-for="rx in prescriptions" :key="rx.id" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ isRtl ? 'وصفة طبية' : 'Prescription' }} #{{ rx.id }}</p>
                            <p class="text-[10px] text-gray-500">
                                {{ formatDate(rx.created_at) }}
                                <span v-if="rx.doctor"> · {{ isRtl ? (rx.doctor.name_ar || rx.doctor.name_en) : (rx.doctor.name_en || rx.doctor.name_ar) }}</span>
                            </p>
                        </div>
                    </div>
                    <Link v-if="prescriptionHref(rx)" :href="prescriptionHref(rx)"
                        class="text-xs font-semibold text-[#C4A265] hover:text-[#8B6F3F] transition">
                        {{ isRtl ? 'عرض' : 'View' }}
                    </Link>
                </div>
                <div class="px-5 py-3 space-y-2">
                    <div v-if="rx.diagnosis" class="text-xs text-gray-600">
                        <strong class="text-gray-800">{{ isRtl ? 'التشخيص:' : 'Diagnosis:' }}</strong> {{ rx.diagnosis }}
                    </div>
                    <div v-if="rx.items?.length" class="space-y-1.5">
                        <p class="text-[10px] font-bold text-gray-500 uppercase">{{ isRtl ? 'الأدوية' : 'Medications' }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="item in rx.items" :key="item.id"
                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-medium">
                                {{ item.medication_name || item.name || item.drug_name || (isRtl ? 'دواء' : 'Medication') }}
                                <span v-if="item.dosage" class="text-purple-500 ltr:ml-1 rtl:mr-1">· {{ item.dosage }}</span>
                            </span>
                        </div>
                    </div>
                    <div v-if="rx.notes" class="text-xs text-gray-600 bg-gray-50 rounded-lg p-2 mt-2">
                        {{ rx.notes }}
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-gray-50 rounded-xl p-8 text-center">
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد وصفات طبية' : 'No prescriptions' }}</p>
        </div>
    </div>
</template>
