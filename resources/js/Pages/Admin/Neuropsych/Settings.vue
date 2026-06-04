<script setup>
import { computed } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ module: String, settings: Object, enabled: Boolean });
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }
const title = computed(() => props.module === 'neurology' ? t('Neurology', 'طب الأعصاب') : t('Psychiatry', 'الطب النفسي'));

const form = useForm({ enabled: props.enabled, name_ar: '', name_en: '' });
function submit() { form.post(route(`admin.${props.module}.settings.update`), { preserveScroll: true }); }
</script>

<template>
    <div class="space-y-6 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800">{{ title }} — {{ t('Settings', 'الإعدادات') }}</h1>
        <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <FormErrors :errors="form.errors" />
            <label class="inline-flex items-center gap-2 text-sm font-medium">
                <input v-model="form.enabled" type="checkbox" /> {{ t('Module enabled', 'الوحدة مفعّلة') }}
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Name (AR)', 'الاسم (عربي)') }}</label><input v-model="form.name_ar" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
                <div><label class="block text-xs font-medium text-gray-600 mb-1">{{ t('Name (EN)', 'الاسم (إنجليزي)') }}</label><input v-model="form.name_en" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" /></div>
            </div>
            <div class="flex justify-end">
                <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">{{ t('Save', 'حفظ') }}</button>
            </div>
        </form>
    </div>
</template>
