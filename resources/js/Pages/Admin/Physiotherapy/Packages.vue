<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({ packages: { type: Array, default: () => [] } });

const showForm = ref(false);
const editing = ref(null);
const form = useForm({ name_ar: '', name_en: '', total_sessions: 10, price: 0, validity_days: 90 });

function startNew() {
    editing.value = null;
    form.reset();
    showForm.value = true;
}
function startEdit(p) {
    editing.value = p.id;
    form.name_ar = p.name_ar; form.name_en = p.name_en; form.total_sessions = p.total_sessions;
    form.price = p.price; form.validity_days = p.validity_days;
    showForm.value = true;
}
function submit() {
    const url = editing.value ? `/admin/physiotherapy/packages/${editing.value}` : '/admin/physiotherapy/packages';
    form.post(url, { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset(); editing.value = null; } });
}
function toggle(p) {
    router.post(`/admin/physiotherapy/packages/${p.id}/toggle`, {}, { preserveScroll: true });
}
const pName = (p) => (isRtl.value ? p.name_ar : p.name_en);
</script>

<template>
    <AdminLayout>
        <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h1 class="text-xl font-bold text-gray-800">{{ t('Session Packages', 'باقات الجلسات') }}</h1>
                <button @click="startNew" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">+ {{ t('New Package', 'باقة جديدة') }}</button>
            </div>

            <form v-if="showForm" @submit.prevent="submit" class="bg-white rounded-2xl p-5 shadow-sm border border-teal-100 space-y-3">
                <h3 class="font-semibold text-gray-800">{{ editing ? t('Edit Package', 'تعديل الباقة') : t('New Package', 'باقة جديدة') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input v-model="form.name_en" :placeholder="t('Name (EN)', 'الاسم (EN)')" class="form-in" required />
                    <input v-model="form.name_ar" :placeholder="t('Name (AR)', 'الاسم (AR)')" class="form-in" required />
                    <input v-model.number="form.total_sessions" type="number" min="1" :placeholder="t('Total sessions', 'عدد الجلسات')" class="form-in" required />
                    <input v-model.number="form.price" type="number" min="0" step="0.01" :placeholder="t('Price', 'السعر')" class="form-in" required />
                    <input v-model.number="form.validity_days" type="number" min="0" :placeholder="t('Validity (days)', 'الصلاحية (أيام)')" class="form-in" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showForm = false" class="px-4 py-2 text-sm text-gray-500">{{ t('Cancel', 'إلغاء') }}</button>
                    <button type="submit" :disabled="form.processing" class="px-5 py-2 rounded-xl text-sm font-semibold text-white" :style="{ backgroundColor: ACCENT }">{{ t('Save', 'حفظ') }}</button>
                </div>
            </form>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-start font-medium px-5 py-3">{{ t('Package', 'الباقة') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Sessions', 'الجلسات') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Price', 'السعر') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Validity', 'الصلاحية') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Active enrollments', 'اشتراكات نشطة') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Active', 'مُفعّل') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in packages" :key="p.id" class="border-t border-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ pName(p) }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ p.total_sessions }}</td>
                            <td class="px-5 py-3 text-center text-gray-600 tabular-nums">{{ Number(p.price).toLocaleString() }}</td>
                            <td class="px-5 py-3 text-center text-gray-500 text-xs">{{ p.validity_days ? p.validity_days + ' ' + t('d', 'يوم') : '—' }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ p.active_purchases }}</td>
                            <td class="px-5 py-3 text-center">
                                <button @click="toggle(p)" :aria-label="p.is_active ? t('Deactivate', 'تعطيل') : t('Activate', 'تفعيل')"
                                    class="w-10 h-6 rounded-full transition relative" :class="p.is_active ? 'bg-teal-500' : 'bg-gray-200'">
                                    <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all" :class="p.is_active ? 'start-4' : 'start-0.5'"></span>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-end">
                                <button @click="startEdit(p)" class="text-teal-600 hover:text-teal-700 text-sm font-medium">{{ t('Edit', 'تعديل') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!packages.length"><td colspan="7" class="px-5 py-12 text-center text-gray-400">{{ t('No packages', 'لا توجد باقات') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
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
