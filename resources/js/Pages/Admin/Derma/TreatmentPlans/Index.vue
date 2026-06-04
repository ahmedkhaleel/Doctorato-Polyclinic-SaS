<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    plans: Object,
    filters: Object,
    patients: Array,
    doctors: Array,
    types: Array,
    stats: Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
let timer = null;
watch([search, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/derma/treatment-plans',
            { search: search.value, status: status.value },
            { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

const typeLabels = {
    laser: { ar: 'ليزر', en: 'Laser' },
    peel: { ar: 'تقشير', en: 'Peel' },
    phototherapy: { ar: 'علاج ضوئي', en: 'Phototherapy' },
    injection: { ar: 'حقن', en: 'Injection' },
    cryotherapy: { ar: 'تبريد', en: 'Cryotherapy' },
    other: { ar: 'أخرى', en: 'Other' },
};
function typeLabel(t) { return isRtl.value ? (typeLabels[t]?.ar || t) : (typeLabels[t]?.en || t); }

const statusMeta = {
    planned:     { ar: 'مخطّطة', en: 'Planned', cls: 'bg-slate-100 text-[#1B365D]', dot: 'bg-[#1B365D]' },
    in_progress: { ar: 'قيد التنفيذ', en: 'In Progress', cls: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    completed:   { ar: 'مكتملة', en: 'Completed', cls: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    cancelled:   { ar: 'ملغاة', en: 'Cancelled', cls: 'bg-red-50 text-red-600', dot: 'bg-red-500' },
};
function sMeta(s) { return statusMeta[s] || statusMeta.planned; }
function planTitle(p) { return isRtl.value ? (p.title_ar || p.title_en) : (p.title_en || p.title_ar); }

// ─── Create / edit ─────────────────────────────────
const showForm = ref(false);
const editing = ref(null);
const form = useForm({
    patient_id: '', doctor_id: '', skin_condition_id: '',
    title_ar: '', title_en: '', session_type: 'laser',
    estimated_sessions: 6, interval_days: 14, estimated_cost: '',
    status: 'planned', start_date: '', notes: '',
});

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
}
function openEdit(p) {
    editing.value = p;
    form.clearErrors();
    form.patient_id = p.patient_id;
    form.doctor_id = p.doctor_id || '';
    form.skin_condition_id = p.skin_condition_id || '';
    form.title_ar = p.title_ar || '';
    form.title_en = p.title_en || '';
    form.session_type = p.session_type;
    form.estimated_sessions = p.estimated_sessions;
    form.interval_days = p.interval_days || '';
    form.estimated_cost = p.estimated_cost || '';
    form.status = p.status;
    form.start_date = p.start_date || '';
    form.notes = p.notes || '';
    showForm.value = true;
}
function submit() {
    const url = editing.value
        ? `/admin/derma/treatment-plans/${editing.value.id}`
        : '/admin/derma/treatment-plans';
    form.post(url, { preserveScroll: true, onSuccess: () => { showForm.value = false; form.reset(); } });
}

// ─── Delete ────────────────────────────────────────
const showDelete = ref(false);
const delTarget = ref(null);
function askDelete(p) { delTarget.value = p; showDelete.value = true; }
function doDelete() {
    if (!delTarget.value) return;
    router.delete(`/admin/derma/treatment-plans/${delTarget.value.id}`, { preserveScroll: true });
    showDelete.value = false; delTarget.value = null;
}

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="tp-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="tp-hero tp-stagger" style="--i:0">
            <div class="tp-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="tp-hero-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'خطط العلاج (الكورسات)' : 'Treatment Plans' }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ isRtl ? 'كورسات علاجية متعددة الجلسات مع تتبّع التقدّم' : 'Multi-session courses with progress tracking' }}</p>
                    </div>
                </div>
                <button @click="openCreate" class="tp-new-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'خطة جديدة' : 'New Plan' }}
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3 mt-6 mb-6">
            <div class="tp-stat tp-stagger" style="--i:1">
                <div class="tp-stat-bar bg-gradient-to-r from-amber-500 to-amber-600"></div>
                <p class="tp-stat-label">{{ isRtl ? 'خطط نشطة' : 'Active' }}</p>
                <p class="tp-stat-value text-amber-600">{{ stats.active }}</p>
            </div>
            <div class="tp-stat tp-stagger" style="--i:2">
                <div class="tp-stat-bar bg-gradient-to-r from-emerald-500 to-emerald-600"></div>
                <p class="tp-stat-label">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
                <p class="tp-stat-value text-emerald-600">{{ stats.completed }}</p>
            </div>
            <div class="tp-stat tp-stagger" style="--i:3">
                <div class="tp-stat-bar bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <p class="tp-stat-label">{{ isRtl ? 'قيمة الخطط النشطة' : 'Active Plan Value' }}</p>
                <p class="tp-stat-value text-[#8B7043] text-lg">{{ formatCurrency(stats.planned_value) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex gap-3 mb-4 tp-stagger flex-wrap" style="--i:4">
            <div class="tp-search flex-1 min-w-[200px]">
                <svg class="w-[18px] h-[18px] text-gray-400 ms-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالعنوان / المريض...' : 'Search title / patient...'" />
            </div>
            <select v-model="status" class="tp-select">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All statuses' }}</option>
                <option value="planned">{{ isRtl ? 'مخطّطة' : 'Planned' }}</option>
                <option value="in_progress">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                <option value="completed">{{ isRtl ? 'مكتملة' : 'Completed' }}</option>
                <option value="cancelled">{{ isRtl ? 'ملغاة' : 'Cancelled' }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="tp-table-card tp-stagger" style="--i:5">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'الخطة' : 'Plan' }}</th>
                            <th class="text-start px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'النوع' : 'Type' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'التقدّم' : 'Progress' }}</th>
                            <th class="text-end px-4 py-3 hidden lg:table-cell">{{ isRtl ? 'التكلفة' : 'Cost' }}</th>
                            <th class="text-center px-4 py-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in plans.data" :key="p.id" class="tp-row">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ planTitle(p) }}</p>
                                <p class="text-[11px] text-gray-400">{{ p.doctor ? (isRtl ? p.doctor.name_ar : p.doctor.name_en) : '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">
                                {{ p.patient?.full_name || '-' }}
                                <span class="block text-[11px] text-gray-400 font-mono">{{ p.patient?.file_number }}</span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#FAF7F0] text-[#8B7043] border border-[#C4A265]/30">{{ typeLabel(p.session_type) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-100 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-gradient-to-r from-[#1B365D] to-[#22406F]" :style="{ width: p.progress_percentage + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-bold text-[#1B365D] tabular-nums">{{ p.completed_sessions }}/{{ p.estimated_sessions }}</span>
                                </div>
                                <p class="text-[10px] mt-0.5 text-gray-400">{{ p.progress_percentage }}% · {{ p.sessions_remaining }} {{ isRtl ? 'متبقية' : 'left' }}</p>
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-gray-700 hidden lg:table-cell">{{ formatCurrency(p.estimated_cost) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="sMeta(p.status).cls">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="sMeta(p.status).dot"></span>
                                    {{ isRtl ? sMeta(p.status).ar : sMeta(p.status).en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <button @click="openEdit(p)" class="text-xs font-semibold text-[#1B365D] hover:text-[#22406F] px-2 py-1 rounded-lg hover:bg-slate-50 transition">{{ isRtl ? 'تعديل' : 'Edit' }}</button>
                                <button @click="askDelete(p)" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1 rounded-lg hover:bg-red-50 transition">{{ isRtl ? 'حذف' : 'Delete' }}</button>
                            </td>
                        </tr>
                        <tr v-if="!plans.data.length">
                            <td colspan="7" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد خطط علاج بعد' : 'No treatment plans yet' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="plans.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in plans.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                      :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/40'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed']" />
            </div>
        </div>

        <!-- Create / edit modal -->
        <Teleport to="body">
            <Transition name="tp-modal">
                <div v-if="showForm" v-focus-trap="() => (showForm = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="showForm = false"></div>
                    <div class="tp-dialog relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <h3 class="text-white font-bold">{{ editing ? (isRtl ? 'تعديل خطة العلاج' : 'Edit Treatment Plan') : (isRtl ? 'خطة علاج جديدة' : 'New Treatment Plan') }}</h3>
                        </div>
                        <form @submit.prevent="submit" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                                    <select v-model="form.patient_id" required class="tp-field">
                                        <option value="">{{ isRtl ? 'اختر المريض' : 'Select patient' }}</option>
                                        <option v-for="pt in patients" :key="pt.id" :value="pt.id">{{ pt.full_name }} — {{ pt.phone }}</option>
                                    </select>
                                    <p v-if="form.errors.patient_id" class="text-[11px] text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                                </div>
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                                    <select v-model="form.doctor_id" class="tp-field">
                                        <option value="">{{ isRtl ? 'بدون' : 'None' }}</option>
                                        <option v-for="d in doctors" :key="d.id" :value="d.id">{{ isRtl ? d.name_ar : d.name_en }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'العنوان (عربي)' : 'Title (Arabic)' }} *</label>
                                    <input v-model="form.title_ar" type="text" required class="tp-field" :placeholder="isRtl ? 'مثال: كورس ليزر إزالة الشعر' : '...'" />
                                    <p v-if="form.errors.title_ar" class="text-[11px] text-red-500 mt-1">{{ form.errors.title_ar }}</p>
                                </div>
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'العنوان (إنجليزي)' : 'Title (English)' }}</label>
                                    <input v-model="form.title_en" type="text" class="tp-field" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'النوع' : 'Type' }} *</label>
                                    <select v-model="form.session_type" required class="tp-field">
                                        <option v-for="t in types" :key="t" :value="t">{{ typeLabel(t) }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'عدد الجلسات' : 'Sessions' }} *</label>
                                    <input v-model.number="form.estimated_sessions" type="number" min="1" max="100" required class="tp-field" />
                                </div>
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'الفاصل (يوم)' : 'Interval (d)' }}</label>
                                    <input v-model.number="form.interval_days" type="number" min="0" max="365" class="tp-field" />
                                </div>
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'التكلفة' : 'Cost' }}</label>
                                    <input v-model="form.estimated_cost" type="number" step="0.01" min="0" class="tp-field" placeholder="0.00" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="tp-label">{{ isRtl ? 'تاريخ البدء' : 'Start date' }}</label>
                                    <input v-model="form.start_date" type="date" class="tp-field" />
                                </div>
                                <div v-if="editing">
                                    <label class="tp-label">{{ isRtl ? 'الحالة' : 'Status' }}</label>
                                    <select v-model="form.status" class="tp-field">
                                        <option value="planned">{{ isRtl ? 'مخطّطة' : 'Planned' }}</option>
                                        <option value="in_progress">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                                        <option value="completed">{{ isRtl ? 'مكتملة' : 'Completed' }}</option>
                                        <option value="cancelled">{{ isRtl ? 'ملغاة' : 'Cancelled' }}</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="tp-label">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <textarea v-model="form.notes" rows="2" class="tp-field resize-none"></textarea>
                            </div>

                            <p class="text-[11px] text-gray-500 bg-slate-50 rounded-lg px-3 py-2">
                                {{ isRtl ? 'اربط جلسات العلاج الجلدي بهذه الخطة ليُحدَّث التقدّم تلقائياً وتُكمَّل عند اكتمال الجلسات.' : 'Link derma sessions to this plan and progress updates automatically, completing when all sessions are done.' }}
                            </p>

                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showForm = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="tp-submit">{{ form.processing ? (isRtl ? 'جارٍ...' : 'Saving...') : (editing ? (isRtl ? 'حفظ' : 'Save') : (isRtl ? 'إنشاء' : 'Create')) }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showDelete"
            :title="isRtl ? 'حذف الخطة' : 'Delete Plan'"
            :message="isRtl ? 'سيتم حذف خطة العلاج. الجلسات المرتبطة لا تُحذف لكنها تُفصل عن الخطة.' : 'The plan will be deleted. Linked sessions are kept but detached.'"
            :confirmText="isRtl ? 'حذف' : 'Delete'"
            :cancelText="isRtl ? 'رجوع' : 'Back'"
            confirmColor="red"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />
    </div>
</template>

<style scoped>
.tp-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.tp-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.tp-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.tp-new-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px; background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 8px 20px -8px rgba(196,162,101,0.6); transition: transform 0.15s, box-shadow 0.3s; }
.tp-new-btn:hover { box-shadow: 0 12px 26px -8px rgba(196,162,101,0.75); }
.tp-new-btn:active { transform: translateY(1px); }

.tp-stat { position: relative; background: #fff; border: 1px solid rgba(196,162,101,0.16); border-radius: 14px; padding: 16px; overflow: hidden; }
.tp-stat-bar { position: absolute; top: 0; inset-inline: 0; height: 3px; }
.tp-stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
.tp-stat-value { font-size: 26px; font-weight: 800; margin-top: 4px; font-variant-numeric: tabular-nums; line-height: 1.1; }

.tp-search { display: flex; align-items: center; background: #fff; border: 1px solid #eef0f3; border-radius: 12px; transition: border-color 0.2s, box-shadow 0.2s; }
.tp-search:focus-within { border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }
.tp-search input { flex: 1; border: 0; outline: 0; background: transparent; padding: 10px 12px; font-size: 14px; }
.tp-select { background: #fff; border: 1px solid #eef0f3; border-radius: 12px; padding: 0 14px; font-size: 13px; color: #374151; }
.tp-select:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }

.tp-table-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px -22px rgba(27,54,93,0.2); }
.tp-row { transition: background 0.18s; }
.tp-row:hover { background: linear-gradient(90deg, rgba(196,162,101,0.05), transparent); }

.tp-label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; }
.tp-field { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 14px; transition: all 0.2s; background: #fff; }
.tp-field:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.15); }
.tp-submit { padding: 9px 20px; border-radius: 9px; background: linear-gradient(135deg, #1B365D, #22406F); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 6px 18px -8px rgba(27,54,93,0.6); transition: box-shadow 0.3s; }
.tp-submit:hover:not(:disabled) { box-shadow: 0 10px 24px -8px rgba(27,54,93,0.7); }
.tp-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.tp-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 70ms + 80ms); }
.is-mounted .tp-stagger { opacity: 1; transform: translateY(0); }

.tp-modal-enter-active, .tp-modal-leave-active { transition: opacity 0.25s ease; }
.tp-modal-enter-from, .tp-modal-leave-to { opacity: 0; }
.tp-modal-enter-active .tp-dialog, .tp-modal-leave-active .tp-dialog { transition: transform 0.3s cubic-bezier(0.16,1,0.3,1); }
.tp-modal-enter-from .tp-dialog { transform: translateY(20px) scale(0.97); }

@media (prefers-reduced-motion: reduce) {
    .tp-stagger { transition-duration: 0.01s; transition-delay: 0s; }
    .tp-modal-enter-active .tp-dialog { transition: none; }
}
</style>
