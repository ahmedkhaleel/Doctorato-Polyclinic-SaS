<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UiButton from '@/Components/Ui/Button.vue';
import UiEmptyState from '@/Components/Ui/EmptyState.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctor: { type: Object, required: true },
    services: { type: Array, default: () => [] },
});

const docName = computed(() => isRtl.value ? (props.doctor.name_ar || props.doctor.name_en) : (props.doctor.name_en || props.doctor.name_ar));

// Per-service rate form (keyed by service id; '' = use default).
const form = useForm({
    rates: Object.fromEntries(props.services.map((s) => [s.id, s.rate ?? ''])),
});
function save() {
    form.transform((d) => ({ rates: d.rates }))
        .post(route('admin.doctors.service-rates.update', props.doctor.id), { preserveScroll: true });
}

const bulkValue = ref('');
const bulk = useForm({ percentage: '' });
function applyBulk() {
    if (bulkValue.value === '' || isNaN(Number(bulkValue.value))) return;
    bulk.percentage = Number(bulkValue.value);
    bulk.post(route('admin.doctors.service-rates.bulk', props.doctor.id), {
        preserveScroll: true,
        onSuccess: () => { bulkValue.value = ''; },
    });
}
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <span class="w-2 h-8 rounded-full bg-[#C4A265]"></span>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'نسب عمولة الخدمات' : 'Service Commission Rates' }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ docName }} · {{ isRtl ? 'الافتراضي' : 'Default' }}: {{ doctor.default_commission_percentage }}%</p>
                </div>
            </div>
            <UiButton variant="outline" size="sm" :href="route('admin.doctors.show', doctor.id)">{{ isRtl ? 'رجوع للطبيب' : 'Back to doctor' }}</UiButton>
        </div>

        <!-- Bulk apply -->
        <div class="bg-amber-50/60 border border-amber-100 rounded-2xl p-4 flex items-end gap-2 flex-wrap">
            <label class="text-xs font-semibold text-gray-600">{{ isRtl ? 'تطبيق نسبة على كل الخدمات' : 'Apply one rate to all services' }}
                <div class="mt-1 flex items-center gap-1">
                    <input v-model="bulkValue" type="number" min="0" max="100" step="0.5" class="w-24 rounded-xl border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-200" />
                    <span class="text-gray-400 text-sm">%</span>
                </div>
            </label>
            <UiButton variant="secondary" size="sm" :loading="bulk.processing" @click="applyBulk">{{ isRtl ? 'تطبيق على الكل' : 'Apply to all' }}</UiButton>
        </div>

        <!-- Rates table -->
        <form @submit.prevent="save" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div v-if="services.length" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                            <th class="px-5 py-2.5 text-start font-semibold">{{ isRtl ? 'الموديول' : 'Module' }}</th>
                            <th class="px-5 py-2.5 text-end font-semibold">{{ isRtl ? 'نسبة العمولة %' : 'Commission %' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="s in services" :key="s.id" class="hover:bg-gray-50/60">
                            <td class="px-5 py-2.5 font-semibold text-gray-800">{{ isRtl ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar) }}</td>
                            <td class="px-5 py-2.5 text-gray-500">{{ s.module || '—' }}</td>
                            <td class="px-5 py-2.5 text-end">
                                <input v-model="form.rates[s.id]" type="number" min="0" max="100" step="0.5"
                                       :placeholder="String(doctor.default_commission_percentage)"
                                       class="w-24 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-end focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <UiEmptyState v-else icon="document"
                :title="isRtl ? 'لا خدمات لهذا الموديول' : 'No services for this module'"
                :description="isRtl ? 'أضف خدمات أولاً لتعيين نِسبها.' : 'Add services first to set their rates.'" />

            <div v-if="services.length" class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">{{ isRtl ? 'اترك الحقل فارغاً لاستخدام النسبة الافتراضية.' : 'Leave blank to use the default rate.' }}</p>
                <UiButton type="submit" variant="primary" :loading="form.processing">{{ isRtl ? 'حفظ' : 'Save' }}</UiButton>
            </div>
        </form>
    </div>
</template>
