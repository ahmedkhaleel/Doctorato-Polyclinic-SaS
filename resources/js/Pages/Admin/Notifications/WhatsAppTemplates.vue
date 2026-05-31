<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const props = defineProps({ templates: Array, events: Array });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { can } = usePermissions();
const canEdit = can('notifications.update');

const showForm = ref(false);
const form = reactive({ id: null, name: '', language: 'ar', event_key: '', variablesCsv: '', body_preview: '', is_active: true });

function openNew() {
    Object.assign(form, { id: null, name: '', language: 'ar', event_key: '', variablesCsv: '', body_preview: '', is_active: true });
    showForm.value = true;
}
function edit(tpl) {
    Object.assign(form, {
        id: tpl.id, name: tpl.name, language: tpl.language, event_key: tpl.event_key || '',
        variablesCsv: (tpl.variables || []).join(', '), body_preview: tpl.body_preview || '', is_active: tpl.is_active,
    });
    showForm.value = true;
}
function save() {
    const payload = {
        name: form.name, language: form.language, event_key: form.event_key || null,
        variables: form.variablesCsv.split(',').map((s) => s.trim()).filter(Boolean),
        body_preview: form.body_preview, is_active: form.is_active,
    };
    const url = form.id ? `/admin/notifications-hub/whatsapp-templates/${form.id}/update` : '/admin/notifications-hub/whatsapp-templates';
    router.post(url, payload, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
}
function remove(tpl) {
    if (confirm(t('حذف القالب؟', 'Delete template?'))) router.post(`/admin/notifications-hub/whatsapp-templates/${tpl.id}/delete`, {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout :title="t('قوالب واتساب', 'WhatsApp Templates')">
        <div class="max-w-5xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:#25D366">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a10 10 0 00-8.7 15l-1.3 4 4.1-1.3A10 10 0 1012 2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ t('قوالب واتساب المعتمدة', 'Approved WhatsApp Templates') }}</h1>
                        <p class="text-xs text-gray-500">{{ t('مطلوبة للرسائل خارج نافذة 24 ساعة', 'Required for messages outside the 24h window') }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/notifications-hub" class="text-sm font-semibold px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">{{ t('المركز', 'Hub') }}</Link>
                    <button v-if="can('notifications.create')" @click="openNew" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">+ {{ t('قالب', 'Template') }}</button>
                </div>
            </div>

            <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs text-gray-500">{{ t('اسم القالب في Meta', 'Meta template name') }}</span>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="appointment_reminder" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('اللغة', 'Language') }}</span>
                        <input v-model="form.language" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="ar" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الحدث المرتبط', 'Linked event') }}</span>
                        <select v-model="form.event_key" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                            <option value="">{{ t('— بدون —', '— none —') }}</option>
                            <option v-for="e in events" :key="e.key" :value="e.key">{{ t(e.label_ar, e.label_en) }}</option>
                        </select></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('المتغيرات (بالترتيب، مفصولة بفاصلة)', 'Variables (ordered, comma-separated)') }}</span>
                        <input v-model="form.variablesCsv" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="name, date, time" /></label>
                </div>
                <label class="block"><span class="text-xs text-gray-500">{{ t('نص القالب المعتمد (للمرجعية)', 'Approved body (reference)') }}</span>
                    <textarea v-model="form.body_preview" rows="2" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="مرحبا {{1}}، موعدك {{2}} الساعة {{3}}"></textarea></label>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded" /> {{ t('مُفعّل', 'Active') }}</label>
                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="save" :disabled="!form.name" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                        <th class="text-start px-4 py-3">{{ t('الاسم', 'Name') }}</th>
                        <th class="px-4 py-3">{{ t('اللغة', 'Lang') }}</th>
                        <th class="text-start px-4 py-3">{{ t('الحدث', 'Event') }}</th>
                        <th class="text-start px-4 py-3">{{ t('المتغيرات', 'Variables') }}</th>
                        <th class="px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="tpl in templates" :key="tpl.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ tpl.name }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ tpl.language }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ tpl.event_key || '—' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ (tpl.variables || []).join(', ') || '—' }}</td>
                            <td class="px-4 py-3 text-center"><span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="tpl.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'">{{ tpl.is_active ? t('مُفعّل', 'Active') : t('متوقف', 'Off') }}</span></td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <button v-if="canEdit" @click="edit(tpl)" class="text-xs font-semibold text-[#1B365D] hover:underline me-2">{{ t('تعديل', 'Edit') }}</button>
                                <button v-if="canEdit" @click="remove(tpl)" class="text-xs text-red-400 hover:underline">{{ t('حذف', 'Delete') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!templates.length"><td colspan="6" class="text-center text-gray-400 py-10">{{ t('لا توجد قوالب معتمدة بعد', 'No approved templates yet') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
