<script setup>
import { ref, reactive, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const props = defineProps({ branches: Array, staff: { type: Array, default: () => [] }, doctors: { type: Array, default: () => [] } });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const { confirm } = useConfirm();
const { can } = usePermissions();
const canEdit = can('settings.update');

// ── Member assignment ───────────────────────────────
const assignFor = ref(null);          // branch being edited
const selUsers = ref(new Set());
const selDoctors = ref(new Set());

function manage(b) {
    assignFor.value = b;
    selUsers.value = new Set(props.staff.filter(u => u.branch_ids.includes(b.id)).map(u => u.id));
    selDoctors.value = new Set(props.doctors.filter(d => d.branch_ids.includes(b.id)).map(d => d.id));
}
function toggle(set, id) {
    const s = new Set(set.value);
    s.has(id) ? s.delete(id) : s.add(id);
    set.value = s;
}
function saveMembers() {
    router.post(`/admin/branches/${assignFor.value.id}/members`, {
        user_ids: [...selUsers.value],
        doctor_ids: [...selDoctors.value],
    }, { preserveScroll: true, onSuccess: () => { assignFor.value = null; } });
}

const showForm = ref(false);
const form = reactive({ id: null, name_ar: '', name_en: '', code: '', phone: '', address: '', timezone: 'Africa/Cairo', is_active: true });

function openNew() {
    Object.assign(form, { id: null, name_ar: '', name_en: '', code: '', phone: '', address: '', timezone: 'Africa/Cairo', is_active: true });
    showForm.value = true;
}
function edit(b) {
    Object.assign(form, { id: b.id, name_ar: b.name_ar, name_en: b.name_en, code: b.code, phone: b.phone || '', address: b.address || '', timezone: b.timezone || 'Africa/Cairo', is_active: b.is_active });
    showForm.value = true;
}
function save() {
    const url = form.id ? `/admin/branches/${form.id}/update` : '/admin/branches';
    router.post(url, form, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
}
function deactivate(b) {
    confirm(t('إيقاف هذا الفرع؟', 'Deactivate this branch?'), () => router.post(`/admin/branches/${b.id}/delete`, {}, { preserveScroll: true }));
}
</script>

<template>
    <AdminLayout :title="t('الفروع', 'Branches')">
        <div class="max-w-4xl mx-auto p-4 md:p-6 space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h.01M11 7h.01M7 11h.01M11 11h.01M7 15h.01M11 15h.01" /></svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ t('إدارة الفروع', 'Branches') }}</h1>
                        <p class="text-xs text-gray-500">{{ t('أضف وأدِر فروع المنشأة', 'Add and manage your clinic branches') }}</p>
                    </div>
                </div>
                <button v-if="canEdit" @click="openNew" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">+ {{ t('فرع', 'Branch') }}</button>
            </div>

            <div v-if="showForm" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3">
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الاسم (عربي)', 'Name (Arabic)') }}</span>
                        <input v-model="form.name_ar" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الاسم (إنجليزي)', 'Name (English)') }}</span>
                        <input v-model="form.name_en" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الكود', 'Code') }}</span>
                        <input v-model="form.code" class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="MAADI" /></label>
                    <label class="block"><span class="text-xs text-gray-500">{{ t('الهاتف', 'Phone') }}</span>
                        <input v-model="form.phone" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                    <label class="block sm:col-span-2"><span class="text-xs text-gray-500">{{ t('العنوان', 'Address') }}</span>
                        <input v-model="form.address" class="mt-1 w-full rounded-lg border-gray-200 text-sm" /></label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" class="rounded" /> {{ t('نشط', 'Active') }}</label>
                <div class="flex justify-end gap-2">
                    <button @click="showForm = false" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="save" :disabled="!form.name_ar || !form.code" class="px-4 py-2 rounded-lg text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                        <th class="text-start px-4 py-3">{{ t('الفرع', 'Branch') }}</th>
                        <th class="px-4 py-3">{{ t('الكود', 'Code') }}</th>
                        <th class="px-4 py-3">{{ t('الموظفون', 'Staff') }}</th>
                        <th class="px-4 py-3">{{ t('الحالة', 'Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="b in branches" :key="b.id" class="border-b border-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ isRtl ? b.name_ar : b.name_en }}<span v-if="b.is_default" class="ms-2 text-[10px] bg-[#C4A265]/15 text-[#C4A265] px-1.5 py-0.5 rounded font-bold">{{ t('رئيسي', 'default') }}</span></td>
                            <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">{{ b.code }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ b.users_count }}</td>
                            <td class="px-4 py-3 text-center"><span class="text-xs font-bold px-2 py-0.5 rounded-full" :class="b.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'">{{ b.is_active ? t('نشط', 'Active') : t('متوقف', 'Off') }}</span></td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <button v-if="canEdit" @click="manage(b)" class="text-xs font-semibold text-[#C4A265] hover:underline me-2">{{ t('الأعضاء', 'Members') }}</button>
                                <button v-if="canEdit" @click="edit(b)" class="text-xs font-semibold text-[#1B365D] hover:underline me-2">{{ t('تعديل', 'Edit') }}</button>
                                <button v-if="canEdit && !b.is_default" @click="deactivate(b)" class="text-xs text-red-400 hover:underline">{{ t('إيقاف', 'Disable') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Member assignment modal -->
        <div v-if="assignFor" v-focus-trap="() => (assignFor = null)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="assignFor = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col" :dir="isRtl ? 'rtl' : 'ltr'">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between" style="background:linear-gradient(135deg,#1B365D,#2a4a7a)">
                    <h3 class="text-white font-bold">{{ t('أعضاء فرع', 'Members of') }} {{ isRtl ? assignFor.name_ar : assignFor.name_en }}</h3>
                    <button @click="assignFor = null" class="text-white/80 hover:text-white text-xl leading-none">×</button>
                </div>
                <div class="p-5 overflow-y-auto grid md:grid-cols-2 gap-5">
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">{{ t('الموظفون', 'Staff') }}</h4>
                        <div class="space-y-1 max-h-72 overflow-y-auto pe-1">
                            <label v-for="u in staff" :key="u.id" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" :checked="selUsers.has(u.id)" @change="toggle(selUsers, u.id)" class="rounded text-[#1B365D]" />
                                <span class="text-gray-800">{{ u.name }}</span>
                                <span v-if="u.role" class="ms-auto text-[10px] text-gray-400">{{ u.role }}</span>
                            </label>
                            <p v-if="!staff.length" class="text-xs text-gray-400 px-2 py-3">{{ t('لا يوجد', 'None') }}</p>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">{{ t('الأطباء', 'Doctors') }}</h4>
                        <div class="space-y-1 max-h-72 overflow-y-auto pe-1">
                            <label v-for="d in doctors" :key="d.id" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" :checked="selDoctors.has(d.id)" @change="toggle(selDoctors, d.id)" class="rounded text-[#C4A265]" />
                                <span class="text-gray-800">{{ d.name }}</span>
                            </label>
                            <p v-if="!doctors.length" class="text-xs text-gray-400 px-2 py-3">{{ t('لا يوجد', 'None') }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                    <button @click="assignFor = null" class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600 text-sm">{{ t('إلغاء', 'Cancel') }}</button>
                    <button @click="saveMembers" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background:#1B365D">{{ t('حفظ', 'Save') }}</button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
