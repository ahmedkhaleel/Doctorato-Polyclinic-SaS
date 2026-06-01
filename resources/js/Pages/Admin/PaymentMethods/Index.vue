<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const props = defineProps({ paymentMethods: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { can } = usePermissions();
const canEdit = can('payments.create');

const showForm = ref(false);
const form = reactive({ id: null, name_ar: '', name_en: '', sort_order: 0, is_active: true });

function openNew() {
    Object.assign(form, { id: null, name_ar: '', name_en: '', sort_order: 0, is_active: true });
    showForm.value = true;
}
function edit(m) {
    Object.assign(form, { id: m.id, name_ar: m.name_ar, name_en: m.name_en, sort_order: m.sort_order ?? 0, is_active: !!m.is_active });
    showForm.value = true;
}
function save() {
    const url = form.id ? `/admin/payment-methods/${form.id}/update` : '/admin/payment-methods';
    router.post(url, { name_ar: form.name_ar, name_en: form.name_en, sort_order: form.sort_order, is_active: form.is_active },
        { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
}
function toggle(m) {
    router.post(`/admin/payment-methods/${m.id}/update`, { name_ar: m.name_ar, name_en: m.name_en, sort_order: m.sort_order ?? 0, is_active: !m.is_active }, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout :title="t('طرق الدفع', 'Payment Methods')">
        <div class="max-w-3xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#16A34A,#0E7A38)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ t('طرق الدفع', 'Payment Methods') }}</h1>
                        <p class="text-xs text-gray-500">{{ t('طرق الدفع المتاحة في الفواتير', 'Methods available on invoices') }}</p>
                    </div>
                </div>
                <button v-if="canEdit" @click="openNew" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">+ {{ t('طريقة', 'Method') }}</button>
            </div>

            <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الاسم (عربي)', 'Name (Arabic)') }}</span>
                        <input v-model="form.name_ar" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الاسم (إنجليزي)', 'Name (English)') }}</span>
                        <input v-model="form.name_en" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الترتيب', 'Sort Order') }}</span>
                        <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded" /> {{ t('نشطة', 'Active') }}</label>
                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="save" :disabled="!form.name_ar || !form.name_en" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                        <th class="text-start px-4 py-3">{{ t('الاسم', 'Name') }}</th>
                        <th class="px-4 py-3">{{ t('الترتيب', 'Order') }}</th>
                        <th class="px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="m in paymentMethods" :key="m.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ isRtl ? m.name_ar : m.name_en }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ m.sort_order ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <button :disabled="!canEdit" @click="toggle(m)" class="text-xs font-bold px-2 py-0.5 rounded-full" :class="m.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'">
                                    {{ m.is_active ? t('نشطة', 'Active') : t('متوقفة', 'Off') }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button v-if="canEdit" @click="edit(m)" class="text-xs font-semibold text-[#1B365D] hover:underline">{{ t('تعديل', 'Edit') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!paymentMethods.length"><td colspan="4" class="text-center text-gray-400 py-10">{{ t('لا توجد طرق دفع', 'No payment methods') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
