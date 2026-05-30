<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const form = useForm({
    consultation_fee: props.settings.consultation_fee ?? '',
    anc_fee: props.settings.anc_fee ?? '',
    ultrasound_fee: props.settings.ultrasound_fee ?? '',
    delivery_fee: props.settings.delivery_fee ?? '',
    lab_fee: props.settings.lab_fee ?? '',
    pap_smear_fee: props.settings.pap_smear_fee ?? '',
    anc_reminder_days: props.settings.anc_reminder_days ?? 2,
    edd_alert_days: props.settings.edd_alert_days ?? 14,
    pap_recall_months: props.settings.pap_recall_months ?? 36,
});

function save() {
    form.post(route('admin.obgyn.settings.update'), { preserveScroll: true });
}

const fees = computed(() => [
    { key: 'consultation_fee', label: isRtl.value ? 'رسوم الكشف' : 'Consultation Fee' },
    { key: 'anc_fee', label: isRtl.value ? 'رسوم متابعة الحمل' : 'Antenatal Visit Fee' },
    { key: 'ultrasound_fee', label: isRtl.value ? 'رسوم السونار' : 'Ultrasound Fee' },
    { key: 'delivery_fee', label: isRtl.value ? 'رسوم الولادة' : 'Delivery Fee' },
    { key: 'lab_fee', label: isRtl.value ? 'رسوم التحاليل' : 'Lab Test Fee' },
    { key: 'pap_smear_fee', label: isRtl.value ? 'رسوم مسحة عنق الرحم' : 'Pap Smear Fee' },
]);
const reminders = computed(() => [
    { key: 'anc_reminder_days', label: isRtl.value ? 'تذكير الزيارة القادمة (أيام قبل)' : 'ANC reminder (days before)' },
    { key: 'edd_alert_days', label: isRtl.value ? 'تنبيه قرب الولادة (أيام قبل)' : 'EDD alert (days before)' },
    { key: 'pap_recall_months', label: isRtl.value ? 'تجديد المسحة (شهور)' : 'Pap recall (months)' },
]);
</script>

<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">{{ isRtl ? 'إعدادات النساء والتوليد' : 'OB/GYN Settings' }}</h2>
        </template>

        <div class="max-w-3xl space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <div v-if="$page.props.flash?.success" class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm">{{ $page.props.flash.success }}</div>

            <form @submit.prevent="save" class="space-y-6">
                <!-- Pricing -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <span class="w-2 h-6 rounded-full" style="background:#DB2777"></span>
                        <h3 class="font-bold text-gray-800">{{ isRtl ? 'التسعير' : 'Pricing' }}</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="f in fees" :key="f.key">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ f.label }}</label>
                            <input v-model="form[f.key]" type="number" step="0.01" min="0"
                                   class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                        </div>
                    </div>
                </div>

                <!-- Reminders -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <span class="w-2 h-6 rounded-full bg-[#1B365D]"></span>
                        <h3 class="font-bold text-gray-800">{{ isRtl ? 'التذكيرات' : 'Reminders' }}</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div v-for="r in reminders" :key="r.key">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ r.label }}</label>
                            <input v-model="form[r.key]" type="number" min="0"
                                   class="w-full rounded-xl border-gray-200 text-sm focus:border-rose-400 focus:ring-rose-400" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl text-white font-semibold disabled:opacity-50" style="background:#DB2777">
                        {{ isRtl ? 'حفظ الإعدادات' : 'Save Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
