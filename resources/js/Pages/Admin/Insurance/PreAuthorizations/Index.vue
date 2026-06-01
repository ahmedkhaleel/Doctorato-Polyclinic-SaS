<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    preAuths: Object,
    filters: Object,
    stats: Object,
    patients: Array,
    insurances: Array,
    doctors: Array,
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
        router.get('/admin/insurance/pre-authorizations', { search: search.value, status: status.value }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

const statusMeta = {
    pending:            { ar: 'قيد الانتظار', en: 'Pending', cls: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    approved:           { ar: 'موافَق', en: 'Approved', cls: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    partially_approved: { ar: 'موافقة جزئية', en: 'Partial', cls: 'bg-teal-50 text-teal-700', dot: 'bg-teal-500' },
    rejected:           { ar: 'مرفوض', en: 'Rejected', cls: 'bg-red-50 text-red-600', dot: 'bg-red-500' },
    expired:            { ar: 'منتهٍ', en: 'Expired', cls: 'bg-gray-100 text-gray-500', dot: 'bg-gray-400' },
};
function sMeta(s) { return statusMeta[s] || statusMeta.pending; }
function fmtDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }
function coName(c) { return c ? (isRtl.value ? (c.name_ar || c.name_en) : (c.name_en || c.name_ar)) : '—'; }

// ─── Create ────────────────────────────────────────
const showCreate = ref(false);
const form = useForm({
    patient_id: '', patient_insurance_id: '', doctor_id: '',
    procedure_description: '', icd_code: '', cpt_code: '', estimated_cost: '',
});
const patientInsurances = computed(() => props.insurances.filter(i => i.patient_id === Number(form.patient_id)));
function openCreate() { form.reset(); form.clearErrors(); showCreate.value = true; }
function submitCreate() {
    form.post('/admin/insurance/pre-authorizations', { preserveScroll: true, onSuccess: () => { showCreate.value = false; form.reset(); } });
}

// ─── Status update ─────────────────────────────────
const showStatus = ref(false);
const target = ref(null);
const statusForm = useForm({ status: 'approved', approved_amount: '', valid_from: '', valid_until: '', conditions: '', rejection_reason: '' });
function openStatus(p) {
    target.value = p;
    statusForm.reset();
    statusForm.status = 'approved';
    statusForm.approved_amount = p.estimated_cost;
    statusForm.valid_from = new Date().toISOString().split('T')[0];
    showStatus.value = true;
}
function submitStatus() {
    statusForm.post(`/admin/insurance/pre-authorizations/${target.value.id}/status`, { preserveScroll: true, onSuccess: () => { showStatus.value = false; } });
}

// ─── Delete ────────────────────────────────────────
const showDelete = ref(false);
const delTarget = ref(null);
function askDelete(p) { delTarget.value = p; showDelete.value = true; }
function doDelete() {
    if (!delTarget.value) return;
    router.post(`/admin/insurance/pre-authorizations/${delTarget.value.id}/delete`, {}, { preserveScroll: true });
    showDelete.value = false; delTarget.value = null;
}

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="pa-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="pa-hero pa-stagger" style="--i:0">
            <div class="pa-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="pa-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'التأمين' : 'Insurance' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'الموافقات المسبقة' : 'Pre-Authorizations' }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ isRtl ? 'موافقة شركة التأمين على الإجراء قبل تنفيذه' : 'Insurer approval for a procedure before it is performed' }}</p>
                    </div>
                </div>
                <button @click="openCreate" class="pa-new-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'طلب موافقة' : 'New Request' }}
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6 mb-6">
            <div class="pa-stat pa-stagger" style="--i:1"><div class="pa-bar bg-gradient-to-r from-amber-500 to-amber-600"></div><p class="pa-label">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</p><p class="pa-val text-amber-600">{{ stats.pending }}</p></div>
            <div class="pa-stat pa-stagger" style="--i:2"><div class="pa-bar bg-gradient-to-r from-emerald-500 to-emerald-600"></div><p class="pa-label">{{ isRtl ? 'موافَق عليها' : 'Approved' }}</p><p class="pa-val text-emerald-600">{{ stats.approved }}</p></div>
            <div class="pa-stat pa-stagger" style="--i:3"><div class="pa-bar bg-gradient-to-r from-gray-400 to-gray-500"></div><p class="pa-label">{{ isRtl ? 'منتهية' : 'Expired' }}</p><p class="pa-val text-gray-500">{{ stats.expired }}</p></div>
            <div class="pa-stat pa-stagger" style="--i:4"><div class="pa-bar bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div><p class="pa-label">{{ isRtl ? 'قيمة الموافقات' : 'Approved Value' }}</p><p class="pa-val text-[#8B7043] text-lg">{{ formatCurrency(stats.approved_amount) }}</p></div>
        </div>

        <!-- Filters -->
        <div class="flex gap-3 mb-4 pa-stagger flex-wrap" style="--i:5">
            <div class="pa-search flex-1 min-w-[200px]">
                <svg class="w-[18px] h-[18px] text-gray-400 ms-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث برقم الموافقة / المريض / الإجراء...' : 'Search auth # / patient / procedure...'" />
            </div>
            <select v-model="status" class="pa-select">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All statuses' }}</option>
                <option value="pending">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</option>
                <option value="approved">{{ isRtl ? 'موافَق' : 'Approved' }}</option>
                <option value="partially_approved">{{ isRtl ? 'جزئية' : 'Partial' }}</option>
                <option value="rejected">{{ isRtl ? 'مرفوض' : 'Rejected' }}</option>
                <option value="expired">{{ isRtl ? 'منتهٍ' : 'Expired' }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="pa-table-card pa-stagger" style="--i:6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'رقم الموافقة' : 'Auth #' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الإجراء' : 'Procedure' }}</th>
                            <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'المقدّر' : 'Estimated' }}</th>
                            <th class="text-end px-4 py-3 hidden lg:table-cell">{{ isRtl ? 'المعتمد' : 'Approved' }}</th>
                            <th class="text-start px-4 py-3 hidden lg:table-cell">{{ isRtl ? 'الصلاحية' : 'Valid until' }}</th>
                            <th class="text-center px-4 py-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in preAuths.data" :key="p.id" class="pa-row">
                            <td class="px-4 py-3 font-mono text-xs text-[#1B365D]">{{ p.auth_number }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ p.patient?.full_name || '-' }}</p>
                                <p class="text-[11px] text-gray-400">{{ coName(p.patient_insurance?.company) }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 hidden md:table-cell max-w-[220px] truncate">
                                {{ p.procedure_description }}
                                <span v-if="p.cpt_code" class="text-[10px] text-gray-400 font-mono">· CPT {{ p.cpt_code }}</span>
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-gray-600 hidden sm:table-cell">{{ formatCurrency(p.estimated_cost) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums font-semibold text-[#1B365D] hidden lg:table-cell">{{ p.approved_amount ? formatCurrency(p.approved_amount) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">{{ fmtDate(p.valid_until) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="sMeta(p.status).cls">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="sMeta(p.status).dot"></span>
                                    {{ isRtl ? sMeta(p.status).ar : sMeta(p.status).en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end whitespace-nowrap">
                                <button v-if="p.status === 'pending'" @click="openStatus(p)" class="text-xs font-semibold text-[#1B365D] hover:text-[#22406F] px-2 py-1 rounded-lg hover:bg-slate-50 transition">{{ isRtl ? 'مراجعة' : 'Review' }}</button>
                                <button @click="askDelete(p)" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1 rounded-lg hover:bg-red-50 transition">{{ isRtl ? 'حذف' : 'Delete' }}</button>
                            </td>
                        </tr>
                        <tr v-if="!preAuths.data.length">
                            <td colspan="8" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد طلبات موافقة مسبقة' : 'No pre-authorization requests' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="preAuths.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in preAuths.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                      :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/40'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed']" />
            </div>
        </div>

        <!-- Create modal -->
        <Teleport to="body">
            <Transition name="pa-modal">
                <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="showCreate = false"></div>
                    <div class="pa-dialog relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444]">
                            <h3 class="text-white font-bold">{{ isRtl ? 'طلب موافقة مسبقة' : 'New Pre-Authorization Request' }}</h3>
                        </div>
                        <form @submit.prevent="submitCreate" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="pa-flabel">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                                <select v-model="form.patient_id" required class="pa-field">
                                    <option value="">{{ isRtl ? 'اختر المريض' : 'Select patient' }}</option>
                                    <option v-for="pt in patients" :key="pt.id" :value="pt.id">{{ pt.full_name }} — {{ pt.phone }}</option>
                                </select>
                                <p v-if="form.errors.patient_id" class="text-[11px] text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                            </div>
                            <div>
                                <label class="pa-flabel">{{ isRtl ? 'وثيقة التأمين' : 'Insurance policy' }} *</label>
                                <select v-model="form.patient_insurance_id" required class="pa-field">
                                    <option value="">{{ isRtl ? 'اختر الوثيقة' : 'Select policy' }}</option>
                                    <option v-for="ins in patientInsurances" :key="ins.id" :value="ins.id">{{ coName(ins.company) }} — {{ ins.policy_number }}</option>
                                </select>
                                <p v-if="form.patient_id && !patientInsurances.length" class="text-[10px] text-amber-600 mt-1">{{ isRtl ? 'لا توجد وثيقة تأمين نشطة لهذا المريض' : 'No active insurance policy for this patient' }}</p>
                            </div>
                            <div>
                                <label class="pa-flabel">{{ isRtl ? 'الإجراء المطلوب' : 'Procedure' }} *</label>
                                <textarea v-model="form.procedure_description" rows="2" required class="pa-field resize-none" :placeholder="isRtl ? 'وصف الإجراء المراد الموافقة عليه' : 'Describe the planned procedure'"></textarea>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="pa-flabel">ICD</label>
                                    <input v-model="form.icd_code" class="pa-field" placeholder="e.g. K02.9" />
                                </div>
                                <div>
                                    <label class="pa-flabel">CPT</label>
                                    <input v-model="form.cpt_code" class="pa-field" placeholder="e.g. 99213" />
                                </div>
                                <div>
                                    <label class="pa-flabel">{{ isRtl ? 'التكلفة المقدّرة' : 'Est. cost' }} *</label>
                                    <input v-model="form.estimated_cost" type="number" step="0.01" min="0" required class="pa-field" />
                                </div>
                            </div>
                            <div>
                                <label class="pa-flabel">{{ isRtl ? 'الطبيب' : 'Doctor' }}</label>
                                <select v-model="form.doctor_id" class="pa-field">
                                    <option value="">{{ isRtl ? 'بدون' : 'None' }}</option>
                                    <option v-for="d in doctors" :key="d.id" :value="d.id">{{ isRtl ? d.name_ar : d.name_en }}</option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showCreate = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="pa-submit">{{ form.processing ? '...' : (isRtl ? 'إرسال الطلب' : 'Submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Review / status modal -->
        <Teleport to="body">
            <Transition name="pa-modal">
                <div v-if="showStatus" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="showStatus = false"></div>
                    <div class="pa-dialog relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444]">
                            <h3 class="text-white font-bold">{{ isRtl ? 'مراجعة الموافقة المسبقة' : 'Review Pre-Authorization' }}</h3>
                            <p class="text-white/70 text-xs mt-0.5">{{ target?.auth_number }} · {{ formatCurrency(target?.estimated_cost) }}</p>
                        </div>
                        <form @submit.prevent="submitStatus" class="p-6 space-y-4">
                            <div>
                                <label class="pa-flabel">{{ isRtl ? 'القرار' : 'Decision' }}</label>
                                <select v-model="statusForm.status" class="pa-field">
                                    <option value="approved">{{ isRtl ? 'موافقة كاملة' : 'Approve' }}</option>
                                    <option value="partially_approved">{{ isRtl ? 'موافقة جزئية' : 'Partially approve' }}</option>
                                    <option value="rejected">{{ isRtl ? 'رفض' : 'Reject' }}</option>
                                </select>
                            </div>
                            <template v-if="statusForm.status !== 'rejected'">
                                <div>
                                    <label class="pa-flabel">{{ isRtl ? 'المبلغ المعتمد' : 'Approved amount' }}</label>
                                    <input v-model="statusForm.approved_amount" type="number" step="0.01" min="0" class="pa-field" />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="pa-flabel">{{ isRtl ? 'صالح من' : 'Valid from' }}</label><input v-model="statusForm.valid_from" type="date" class="pa-field" /></div>
                                    <div><label class="pa-flabel">{{ isRtl ? 'صالح حتى' : 'Valid until' }}</label><input v-model="statusForm.valid_until" type="date" class="pa-field" /></div>
                                </div>
                                <div><label class="pa-flabel">{{ isRtl ? 'الشروط' : 'Conditions' }}</label><input v-model="statusForm.conditions" class="pa-field" /></div>
                            </template>
                            <div v-else>
                                <label class="pa-flabel">{{ isRtl ? 'سبب الرفض' : 'Rejection reason' }}</label>
                                <textarea v-model="statusForm.rejection_reason" rows="2" class="pa-field resize-none"></textarea>
                            </div>
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showStatus = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="statusForm.processing" class="pa-submit">{{ isRtl ? 'حفظ القرار' : 'Save Decision' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showDelete"
            :title="isRtl ? 'حذف الموافقة المسبقة' : 'Delete Pre-Authorization'"
            :message="isRtl ? 'سيتم حذف طلب الموافقة المسبقة.' : 'This pre-authorization request will be deleted.'"
            :confirmText="isRtl ? 'حذف' : 'Delete'"
            :cancelText="isRtl ? 'رجوع' : 'Back'"
            confirmColor="red"
            @confirm="doDelete"
            @cancel="showDelete = false"
        />
    </div>
</template>

<style scoped>
.pa-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.pa-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.22), transparent 70%); filter: blur(20px); pointer-events: none; }
.pa-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.pa-new-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px; background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 8px 20px -8px rgba(196,162,101,0.6); transition: transform 0.15s, box-shadow 0.3s; }
.pa-new-btn:hover { box-shadow: 0 12px 26px -8px rgba(196,162,101,0.75); }
.pa-new-btn:active { transform: translateY(1px); }
.pa-stat { position: relative; background: #fff; border: 1px solid rgba(196,162,101,0.16); border-radius: 14px; padding: 16px; overflow: hidden; }
.pa-bar { position: absolute; top: 0; inset-inline: 0; height: 3px; }
.pa-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
.pa-val { font-size: 26px; font-weight: 800; margin-top: 4px; font-variant-numeric: tabular-nums; line-height: 1.1; }
.pa-search { display: flex; align-items: center; background: #fff; border: 1px solid #eef0f3; border-radius: 12px; transition: border-color 0.2s, box-shadow 0.2s; }
.pa-search:focus-within { border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }
.pa-search input { flex: 1; border: 0; outline: 0; background: transparent; padding: 10px 12px; font-size: 14px; }
.pa-select { background: #fff; border: 1px solid #eef0f3; border-radius: 12px; padding: 0 14px; font-size: 13px; color: #374151; }
.pa-select:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }
.pa-table-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px -22px rgba(27,54,93,0.2); }
.pa-row { transition: background 0.18s; }
.pa-row:hover { background: linear-gradient(90deg, rgba(196,162,101,0.05), transparent); }
.pa-flabel { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; }
.pa-field { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 14px; transition: all 0.2s; background: #fff; }
.pa-field:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.15); }
.pa-submit { padding: 9px 20px; border-radius: 9px; background: linear-gradient(135deg, #1B365D, #22406F); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 6px 18px -8px rgba(27,54,93,0.6); transition: box-shadow 0.3s; }
.pa-submit:hover:not(:disabled) { box-shadow: 0 10px 24px -8px rgba(27,54,93,0.7); }
.pa-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.pa-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0)*70ms + 80ms); }
.is-mounted .pa-stagger { opacity: 1; transform: translateY(0); }
.pa-modal-enter-active, .pa-modal-leave-active { transition: opacity 0.25s ease; }
.pa-modal-enter-from, .pa-modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) { .pa-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
