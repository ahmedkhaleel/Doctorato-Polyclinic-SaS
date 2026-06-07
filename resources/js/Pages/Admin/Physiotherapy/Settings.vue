<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({ settings: { type: Object, default: () => ({}) } });

const form = useForm({
    consultation_fee: props.settings.consultation_fee ?? '',
    followup_fee: props.settings.followup_fee ?? '',
    session_fee: props.settings.session_fee ?? '',
    home_visit_surcharge: props.settings.home_visit_surcharge ?? '',
    default_commission: props.settings.default_commission ?? '',
    consultation_commission: props.settings.consultation_commission ?? '',
    session_commission: props.settings.session_commission ?? '',
    consultation_duration: props.settings.consultation_duration ?? 45,
    session_duration: props.settings.session_duration ?? 45,
});

function submit() {
    form.post(route('admin.physiotherapy.settings.update'), { preserveScroll: true });
}

const pricing = computed(() => [
    { key: 'consultation_fee', label: t('Assessment fee', 'رسوم التقييم') },
    { key: 'followup_fee', label: t('Re-assessment fee', 'رسوم إعادة التقييم') },
    { key: 'session_fee', label: t('Session fee', 'رسوم الجلسة') },
    { key: 'home_visit_surcharge', label: t('Home-visit surcharge', 'رسوم الزيارة المنزلية') },
]);
const commission = computed(() => [
    { key: 'default_commission', label: t('Default commission %', 'العمولة الافتراضية %') },
    { key: 'consultation_commission', label: t('Assessment commission %', 'عمولة التقييم %') },
    { key: 'session_commission', label: t('Session commission %', 'عمولة الجلسة %') },
]);
const duration = computed(() => [
    { key: 'consultation_duration', label: t('Assessment duration (min)', 'مدة التقييم (دقيقة)') },
    { key: 'session_duration', label: t('Session duration (min)', 'مدة الجلسة (دقيقة)') },
]);
</script>

<template>
    <AdminLayout>
        <form @submit.prevent="submit" class="space-y-6 max-w-3xl" :dir="isRtl ? 'rtl' : 'ltr'">
            <h1 class="text-xl font-bold text-gray-800">{{ t('Physiotherapy Settings', 'إعدادات العلاج الطبيعي') }}</h1>
            <FormErrors :errors="form.errors" />

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Pricing', 'الأسعار') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label v-for="f in pricing" :key="f.key" class="block">
                        <span class="text-sm text-gray-600">{{ f.label }}</span>
                        <input v-model="form[f.key]" type="number" min="0" step="0.01" class="form-in mt-1 w-full" />
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Commission', 'العمولة') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label v-for="f in commission" :key="f.key" class="block">
                        <span class="text-sm text-gray-600">{{ f.label }}</span>
                        <input v-model="form[f.key]" type="number" min="0" max="100" step="0.1" class="form-in mt-1 w-full" />
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ t('Duration', 'المدة') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label v-for="f in duration" :key="f.key" class="block">
                        <span class="text-sm text-gray-600">{{ f.label }}</span>
                        <input v-model="form[f.key]" type="number" min="0" class="form-in mt-1 w-full" />
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save Settings', 'حفظ الإعدادات') }}</button>
            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
.form-in {
    padding: 0.5rem 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    font-size: 0.875rem;
    background: #fff;
    outline: none;
}
.form-in:focus { border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.25); }
</style>
