<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#1B365D';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    templates: { type: Array, default: () => [] },
    treatmentTypes: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

function filterType(type) {
    router.get('/doctor/dental/prescription-templates', { treatment_type: type || undefined }, { preserveState: true, replace: true });
}
const tName = (tpl) => (isRtl.value ? tpl.name_ar : tpl.name_en) || tpl.name_en || tpl.name_ar;
const diagnosis = (tpl) => (isRtl.value ? tpl.diagnosis_ar : tpl.diagnosis_en);
const notes = (tpl) => (isRtl.value ? tpl.notes_ar : tpl.notes_en);
const itemInstr = (it) => (isRtl.value ? it.instructions_ar : it.instructions_en);
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ t('Prescription Templates', 'قوالب الوصفات') }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ t('Reference templates managed by the clinic admin.', 'قوالب مرجعية تُدار من قِبل إدارة العيادة.') }}</p>
            </div>
            <div v-if="treatmentTypes.length" class="flex flex-wrap gap-1">
                <button @click="filterType(null)" class="px-3 py-1 rounded-lg text-xs font-medium" :class="!filters.treatment_type ? 'text-white' : 'bg-gray-100 text-gray-600'" :style="!filters.treatment_type ? { backgroundColor: ACCENT } : {}">{{ t('All', 'الكل') }}</button>
                <button v-for="ty in treatmentTypes" :key="ty" @click="filterType(ty)" class="px-3 py-1 rounded-lg text-xs font-medium" :class="filters.treatment_type === ty ? 'text-white' : 'bg-gray-100 text-gray-600'" :style="filters.treatment_type === ty ? { backgroundColor: ACCENT } : {}">{{ ty }}</button>
            </div>
        </div>

        <p v-if="!templates.length" class="bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-100">{{ t('No templates found.', 'لا توجد قوالب.') }}</p>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="tpl in templates" :key="tpl.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold text-gray-800">{{ tName(tpl) }}</h2>
                    <span v-if="tpl.treatment_type" class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 whitespace-nowrap">{{ tpl.treatment_type }}</span>
                </div>
                <p v-if="diagnosis(tpl)" class="text-xs text-gray-500 mt-1">{{ t('Diagnosis', 'التشخيص') }}: {{ diagnosis(tpl) }}</p>

                <ul v-if="tpl.items.length" class="mt-3 space-y-1.5">
                    <li v-for="(it, i) in tpl.items" :key="i" class="text-sm border-b border-gray-50 last:border-0 pb-1.5">
                        <span class="font-medium text-gray-800">{{ it.medication_name }}</span>
                        <span class="text-gray-500 text-xs">
                            <template v-if="it.dosage"> · {{ it.dosage }}</template>
                            <template v-if="it.frequency"> · {{ it.frequency }}</template>
                            <template v-if="it.duration"> · {{ it.duration }}</template>
                        </span>
                        <p v-if="itemInstr(it)" class="text-[11px] text-gray-400">{{ itemInstr(it) }}</p>
                    </li>
                </ul>
                <p v-else class="text-xs text-gray-400 mt-2">{{ t('No medications in this template.', 'لا أدوية في هذا القالب.') }}</p>

                <p v-if="notes(tpl)" class="text-xs text-gray-500 mt-3 pt-2 border-t border-gray-50">{{ t('Notes', 'ملاحظات') }}: {{ notes(tpl) }}</p>
            </div>
        </div>
    </div>
</template>
