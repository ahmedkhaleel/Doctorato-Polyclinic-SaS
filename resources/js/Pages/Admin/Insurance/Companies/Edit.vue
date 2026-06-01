<script setup>
import { computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ company: Object });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

const form = useForm({
    name_ar: props.company.name_ar || '',
    name_en: props.company.name_en || '',
    code: props.company.code || '',
    phone: props.company.phone || '',
    email: props.company.email || '',
    contact_person: props.company.contact_person || '',
    address: props.company.address || '',
    notes: props.company.notes || '',
    is_active: !!props.company.is_active,
    logo: null,
});

function submit() {
    form.post(`/admin/insurance/companies/${props.company.id}/update`, { forceFormData: true });
}
</script>

<template>
    <AdminLayout :title="t('تعديل شركة تأمين', 'Edit Insurance Company')">
        <div class="max-w-3xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-[#1B365D]">{{ t('تعديل', 'Edit') }}: {{ isRtl ? company.name_ar : company.name_en }}</h1>
                <Link href="/admin/insurance/companies" class="text-sm text-gray-500 hover:underline">{{ t('رجوع', 'Back') }}</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('الاسم (عربي)', 'Name (Arabic)') }} *</span>
                        <input v-model="form.name_ar" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        <span v-if="form.errors.name_ar" class="text-xs text-red-500">{{ form.errors.name_ar }}</span></label>
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('الاسم (إنجليزي)', 'Name (English)') }} *</span>
                        <input v-model="form.name_en" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        <span v-if="form.errors.name_en" class="text-xs text-red-500">{{ form.errors.name_en }}</span></label>
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('الكود', 'Code') }}</span>
                        <input v-model="form.code" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        <span v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</span></label>
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('الهاتف', 'Phone') }}</span>
                        <input v-model="form.phone" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('البريد', 'Email') }}</span>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs font-medium text-gray-500">{{ t('مسؤول التواصل', 'Contact Person') }}</span>
                        <input v-model="form.contact_person" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block sm:col-span-2"><span class="text-xs font-medium text-gray-500">{{ t('العنوان', 'Address') }}</span>
                        <input v-model="form.address" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block sm:col-span-2"><span class="text-xs font-medium text-gray-500">{{ t('ملاحظات', 'Notes') }}</span>
                        <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-gray-200 text-sm"></textarea></label>
                    <label class="block sm:col-span-2"><span class="text-xs font-medium text-gray-500">{{ t('الشعار (اتركه فارغاً للإبقاء)', 'Logo (leave empty to keep)') }}</span>
                        <input type="file" accept="image/*" @input="form.logo = $event.target.files[0]" class="mt-1 w-full text-sm" /></label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded" /> {{ t('نشطة', 'Active') }}</label>
                <div class="flex justify-end gap-2 pt-2">
                    <Link href="/admin/insurance/companies" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</Link>
                    <button type="submit" :disabled="form.processing || !form.name_ar || !form.name_en" class="px-5 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('حفظ التعديلات', 'Save Changes') }}</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
