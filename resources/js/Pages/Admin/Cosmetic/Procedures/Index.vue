<script setup>
import { ref, computed, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import BoolIcon from '@/Components/Ui/BoolIcon.vue';
import FormErrors from '@/Components/Ui/FormErrors.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: AdminLayout });

const { confirm } = useConfirm();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ procedures: Object, filters: Object, categories: Array, supplies: { type: Array, default: () => [] } });

const search = ref(props.filters?.search || '');
const cat = ref(props.filters?.category || '');
const active = ref(props.filters?.active ?? '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/cosmetic/procedures', {
        search: search.value || undefined, category: cat.value || undefined, active: active.value === '' ? undefined : active.value,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, cat, active], apply);

const showModal = ref(false);
const editing = ref(null);
const form = useForm({
    name_ar: '', name_en: '', category: 'other', description: '',
    default_price: 0, supply_id: '', default_consumption_qty: 0,
    default_duration_minutes: 30, recovery_days: 0,
    is_active: true, display_order: 0,
});
function supplyName(s) { return isRtl?.value ? (s.name_ar || s.name_en) : (s.name_en || s.name_ar); }
function open(p = null) {
    editing.value = p;
    form.reset();
    if (p) Object.keys(form.data()).forEach(k => form[k] = p[k] ?? form[k]);
    showModal.value = true;
}
function submit() {
    const url = editing.value ? `/admin/cosmetic/procedures/${editing.value.id}` : '/admin/cosmetic/procedures';
    form.post(url, { preserveScroll: true, onSuccess: () => { showModal.value = false; router.reload({ only: ['procedures'] }); } });
}
function remove(p) {
    confirm(isRtl.value ? 'تأكيد الحذف؟' : 'Confirm delete?', () => {
        router.delete(`/admin/cosmetic/procedures/${p.id}`, { preserveScroll: true });
    });
}
function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex flex-col md:flex-row md:items-center gap-4 md:gap-5 justify-between">
                <div class="flex items-start gap-3 md:gap-4 min-w-0">
                    <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                        </div>
                        <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Procedures', 'الإجراءات') }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('Cosmetic procedure catalog', 'كتالوج إجراءات التجميل') }}</p>
                    </div>
                </div>
                <button @click="open()" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#8B7043] hover:from-[#8B7043] hover:to-[#C4A265] text-white font-bold px-4 md:px-5 py-2.5 shadow-md hover:shadow-lg transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ t('Add procedure', 'إضافة إجراء') }}
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-wrap gap-3">
            <input v-model="search" :placeholder="t('Search…', 'بحث…')" class="doctorato-input flex-1 min-w-[220px] px-4 py-2.5 border border-slate-200 rounded-xl text-sm" />
            <select v-model="cat" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All categories', 'كل التصنيفات') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select v-model="active" class="doctorato-input px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                <option value="">{{ t('All', 'الكل') }}</option>
                <option value="1">{{ t('Active', 'نشط') }}</option>
                <option value="0">{{ t('Inactive', 'معطل') }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Name', 'الاسم') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('Category', 'التصنيف') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Price', 'السعر') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Duration', 'المدة') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden lg:table-cell">{{ t('Active', 'نشط') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(p, i) in procedures.data" :key="p.id" class="lst-row hover:bg-[#C4A265]/5 transition" :style="{ '--row-i': i }">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ isRtl ? p.name_ar : (p.name_en || p.name_ar) }}</td>
                            <td class="px-5 py-3 hidden md:table-cell capitalize text-slate-600">{{ p.category }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ p.default_price }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell text-slate-600">{{ p.default_duration_minutes }} min</td>
                            <td class="px-5 py-3 hidden lg:table-cell">
                                <BoolIcon :ok="!!p.is_active" />
                            </td>
                            <td class="px-5 py-3 text-end space-x-2 rtl:space-x-reverse">
                                <button @click="open(p)" class="text-[#C4A265] hover:text-[#8B7043] text-xs font-bold">{{ t('Edit', 'تعديل') }}</button>
                                <button @click="remove(p)" class="text-red-600 hover:text-red-800 text-xs font-bold">{{ t('Delete', 'حذف') }}</button>
                            </td>
                        </tr>
                        <tr v-if="!procedures.data.length"><td colspan="6" class="text-center py-8 text-slate-400">{{ t('No procedures', 'لا توجد إجراءات') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showModal = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-auto p-6">
                <h2 class="text-lg font-bold mb-4">{{ editing ? t('Edit procedure', 'تعديل الإجراء') : t('Add procedure', 'إضافة إجراء') }}</h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <FormErrors :errors="form.errors" class="md:col-span-2" />
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Name (AR)', 'الاسم (عربي)') }} *</label>
                        <input v-model="form.name_ar" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Name (EN)', 'الاسم (إنجليزي)') }}</label>
                        <input v-model="form.name_en" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Category', 'التصنيف') }} *</label>
                        <select v-model="form.category" required class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Default Price', 'السعر الافتراضي') }}</label>
                        <input v-model.number="form.default_price" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Inventory item (consumed)', 'صنف المخزون (يُخصم)') }}</label>
                        <select v-model="form.supply_id" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="">{{ t('None', 'بدون') }}</option>
                            <option v-for="s in supplies" :key="s.id" :value="s.id">{{ supplyName(s) }}<span v-if="s.unit"> ({{ s.unit }})</span></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Qty consumed / session', 'الكمية المستهلكة / جلسة') }}</label>
                        <input v-model.number="form.default_consumption_qty" type="number" min="0" step="0.01" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Duration (min)', 'المدة (دقائق)') }}</label>
                        <input v-model.number="form.default_duration_minutes" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Recovery days', 'أيام التعافي') }}</label>
                        <input v-model.number="form.recovery_days" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">{{ t('Display order', 'الترتيب') }}</label>
                        <input v-model.number="form.display_order" type="number" min="0" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.is_active" type="checkbox" /> {{ t('Active', 'نشط') }}
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium mb-1">{{ t('Description', 'الوصف') }}</label>
                        <textarea v-model="form.description" rows="3" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"></textarea>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm">{{ t('Cancel', 'إلغاء') }}</button>
                        <button :disabled="form.processing" class="px-5 py-2 rounded-lg bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white text-sm font-bold">{{ t('Save', 'حفظ') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
