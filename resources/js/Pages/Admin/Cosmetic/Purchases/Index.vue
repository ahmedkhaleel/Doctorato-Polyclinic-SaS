<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    purchases: Object,   // paginator
    filters: Object,
    packages: Array,
    patients: Array,
    stats: Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

let timer = null;
function refresh() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/cosmetic/package-purchases',
            { search: search.value, status: status.value },
            { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
}
watch([search, status], refresh);

// ─── Create purchase modal ─────────────────────────
const showCreate = ref(false);
const form = useForm({ patient_id: '', package_id: '', amount: '', notes: '' });

const selectedPackage = computed(() => props.packages.find(p => p.id === Number(form.package_id)) || null);

function openCreate() {
    form.reset();
    form.clearErrors();
    showCreate.value = true;
}
function submit() {
    form.post('/admin/cosmetic/package-purchases', {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; form.reset(); },
    });
}

// ─── Cancel ────────────────────────────────────────
const showCancel = ref(false);
const cancelTarget = ref(null);
function askCancel(p) { cancelTarget.value = p; showCancel.value = true; }
function doCancel() {
    if (!cancelTarget.value) return;
    router.post(`/admin/cosmetic/package-purchases/${cancelTarget.value.id}/cancel`, {}, { preserveScroll: true });
    showCancel.value = false; cancelTarget.value = null;
}

// ─── Helpers ───────────────────────────────────────
function pkgName(p) { return isRtl.value ? (p.name_ar || p.name_en) : (p.name_en || p.name_ar); }
function pct(p) { return p.total_sessions > 0 ? Math.min(100, Math.round((p.sessions_used / p.total_sessions) * 100)) : 0; }
function fmtDate(d) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

const statusMeta = computed(() => ({
    active:    { ar: 'نشطة', en: 'Active', cls: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    completed: { ar: 'مكتملة', en: 'Completed', cls: 'bg-slate-100 text-[#1B365D]', dot: 'bg-[#1B365D]' },
    expired:   { ar: 'منتهية', en: 'Expired', cls: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    cancelled: { ar: 'ملغاة', en: 'Cancelled', cls: 'bg-red-50 text-red-600', dot: 'bg-red-500' },
}));
function sMeta(s) { return statusMeta.value[s] || statusMeta.value.active; }

const invStatus = computed(() => ({
    paid: { ar: 'مدفوعة', en: 'Paid', cls: 'text-emerald-600' },
    partial: { ar: 'جزئية', en: 'Partial', cls: 'text-amber-600' },
    unpaid: { ar: 'غير مدفوعة', en: 'Unpaid', cls: 'text-red-500' },
    cancelled: { ar: 'ملغاة', en: 'Cancelled', cls: 'text-gray-400' },
}));

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="pkg-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">

        <!-- ═══ Hero ═══ -->
        <div class="pkg-hero pkg-stagger" style="--i:0">
            <div class="pkg-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="pkg-hero-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'اشتراكات الباقات' : 'Package Purchases' }}</h1>
                        <p class="text-xs md:text-sm text-white/70 mt-1">{{ isRtl ? 'الباقات المدفوعة مقدّماً ورصيد جلسات كل مريض' : 'Prepaid packages and each patient\'s session balance' }}</p>
                    </div>
                </div>
                <button @click="openCreate" class="pkg-new-btn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'بيع باقة' : 'Sell Package' }}
                </button>
            </div>
        </div>

        <!-- ═══ Stats ═══ -->
        <div class="grid grid-cols-3 gap-3 mt-6 mb-6">
            <div class="pkg-stat pkg-stagger" style="--i:1">
                <div class="pkg-stat-bar bg-gradient-to-r from-emerald-500 to-emerald-600"></div>
                <p class="pkg-stat-label">{{ isRtl ? 'باقات نشطة' : 'Active' }}</p>
                <p class="pkg-stat-value text-emerald-600">{{ stats.active }}</p>
            </div>
            <div class="pkg-stat pkg-stagger" style="--i:2">
                <div class="pkg-stat-bar bg-gradient-to-r from-[#1B365D] to-[#22406F]"></div>
                <p class="pkg-stat-label">{{ isRtl ? 'مكتملة' : 'Completed' }}</p>
                <p class="pkg-stat-value text-[#1B365D]">{{ stats.completed }}</p>
            </div>
            <div class="pkg-stat pkg-stagger" style="--i:3">
                <div class="pkg-stat-bar bg-gradient-to-r from-[#C4A265] to-[#8B7043]"></div>
                <p class="pkg-stat-label">{{ isRtl ? 'إجمالي مبيعات الباقات' : 'Package Revenue' }}</p>
                <p class="pkg-stat-value text-[#8B7043] text-lg">{{ formatCurrency(stats.revenue) }}</p>
            </div>
        </div>

        <!-- ═══ Filters ═══ -->
        <div class="flex gap-3 mb-4 pkg-stagger flex-wrap" style="--i:4">
            <div class="pkg-search flex-1 min-w-[200px]">
                <svg class="w-[18px] h-[18px] text-gray-400 ms-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالمريض / الهاتف / رقم الملف...' : 'Search patient / phone / file #...'" />
            </div>
            <select v-model="status" class="pkg-select">
                <option value="">{{ isRtl ? 'كل الحالات' : 'All statuses' }}</option>
                <option value="active">{{ isRtl ? 'نشطة' : 'Active' }}</option>
                <option value="completed">{{ isRtl ? 'مكتملة' : 'Completed' }}</option>
                <option value="expired">{{ isRtl ? 'منتهية' : 'Expired' }}</option>
                <option value="cancelled">{{ isRtl ? 'ملغاة' : 'Cancelled' }}</option>
            </select>
        </div>

        <!-- ═══ Table ═══ -->
        <div class="pkg-table-card pkg-stagger" style="--i:5">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'الباقة' : 'Package' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'الجلسات' : 'Sessions' }}</th>
                            <th class="text-end px-4 py-3 hidden md:table-cell">{{ isRtl ? 'القيمة' : 'Amount' }}</th>
                            <th class="text-center px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</th>
                            <th class="text-start px-4 py-3 hidden lg:table-cell">{{ isRtl ? 'الصلاحية' : 'Expires' }}</th>
                            <th class="text-center px-4 py-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="p in purchases.data" :key="p.id" class="pkg-row">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ p.patient?.full_name || '-' }}</p>
                                <p class="text-[11px] text-gray-400 font-mono">{{ p.patient?.file_number }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ p.package ? pkgName(p.package) : '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 bg-gray-100 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-gradient-to-r from-[#C4A265] to-[#8B7043]" :style="{ width: pct(p) + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-bold text-[#1B365D] tabular-nums">{{ p.sessions_used }}/{{ p.total_sessions }}</span>
                                </div>
                                <p class="text-[10px] mt-0.5" :class="p.sessions_remaining > 0 ? 'text-emerald-600' : 'text-gray-400'">
                                    {{ p.sessions_remaining }} {{ isRtl ? 'متبقية' : 'left' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-end tabular-nums text-gray-700 hidden md:table-cell">{{ formatCurrency(p.amount) }}</td>
                            <td class="px-4 py-3 text-center hidden md:table-cell">
                                <Link v-if="p.invoice" :href="`/admin/invoices/${p.invoice.id}`" class="text-xs font-semibold hover:underline" :class="invStatus[p.invoice.status]?.cls">
                                    {{ isRtl ? invStatus[p.invoice.status]?.ar : invStatus[p.invoice.status]?.en }}
                                </Link>
                                <span v-else class="text-xs text-gray-300">-</span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">{{ fmtDate(p.expires_at) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="sMeta(p.status).cls">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="sMeta(p.status).dot"></span>
                                    {{ isRtl ? sMeta(p.status).ar : sMeta(p.status).en }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button v-if="p.status === 'active'" @click="askCancel(p)" class="text-xs font-semibold text-red-500 hover:text-red-700 px-2 py-1 rounded-lg hover:bg-red-50 transition">
                                    {{ isRtl ? 'إلغاء' : 'Cancel' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!purchases.data.length">
                            <td colspan="8" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد اشتراكات باقات بعد' : 'No package purchases yet' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="purchases.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in purchases.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                      :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/40'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed']" />
            </div>
        </div>

        <!-- ═══ Create modal ═══ -->
        <Teleport to="body">
            <Transition name="pkg-modal">
                <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="showCreate = false"></div>
                    <div class="pkg-dialog relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444] flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" /></svg>
                            </div>
                            <h3 class="text-white font-bold">{{ isRtl ? 'بيع باقة لمريض' : 'Sell Package to Patient' }}</h3>
                        </div>
                        <form @submit.prevent="submit" class="p-6 space-y-4">
                            <div>
                                <label class="pkg-label">{{ isRtl ? 'المريض' : 'Patient' }} *</label>
                                <select v-model="form.patient_id" required class="pkg-field">
                                    <option value="">{{ isRtl ? 'اختر المريض' : 'Select patient' }}</option>
                                    <option v-for="pt in patients" :key="pt.id" :value="pt.id">{{ pt.full_name }} — {{ pt.phone }}</option>
                                </select>
                                <p v-if="form.errors.patient_id" class="text-[11px] text-red-500 mt-1">{{ form.errors.patient_id }}</p>
                            </div>
                            <div>
                                <label class="pkg-label">{{ isRtl ? 'الباقة' : 'Package' }} *</label>
                                <select v-model="form.package_id" required class="pkg-field">
                                    <option value="">{{ isRtl ? 'اختر الباقة' : 'Select package' }}</option>
                                    <option v-for="pk in packages" :key="pk.id" :value="pk.id">{{ pkgName(pk) }} — {{ pk.total_sessions }} {{ isRtl ? 'جلسة' : 'sessions' }}</option>
                                </select>
                            </div>

                            <!-- Package preview -->
                            <div v-if="selectedPackage" class="rounded-xl bg-[#FAF7F0] border border-[#C4A265]/30 p-3 grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-[10px] text-[#8B7043] uppercase">{{ isRtl ? 'الجلسات' : 'Sessions' }}</p>
                                    <p class="text-base font-extrabold text-[#1B365D]">{{ selectedPackage.total_sessions }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-[#8B7043] uppercase">{{ isRtl ? 'السعر' : 'Price' }}</p>
                                    <p class="text-base font-extrabold text-[#1B365D]">{{ formatCurrency(selectedPackage.total_price) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-[#8B7043] uppercase">{{ isRtl ? 'الصلاحية' : 'Validity' }}</p>
                                    <p class="text-base font-extrabold text-[#1B365D]">{{ selectedPackage.validity_days }} {{ isRtl ? 'يوم' : 'd' }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="pkg-label">{{ isRtl ? 'القيمة (اتركها فارغة لسعر الباقة)' : 'Amount (blank = package price)' }}</label>
                                <input v-model="form.amount" type="number" step="0.01" min="0" class="pkg-field" :placeholder="selectedPackage ? String(selectedPackage.total_price) : '0.00'" />
                            </div>
                            <div>
                                <label class="pkg-label">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                                <input v-model="form.notes" type="text" class="pkg-field" />
                            </div>

                            <p class="text-[11px] text-gray-500 bg-slate-50 rounded-lg px-3 py-2 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>{{ isRtl ? 'سيتم إنشاء فاتورة دفع مقدّم بقيمة الباقة. الجلسات اللاحقة المرتبطة بالباقة تُخصم من الرصيد دون فوترة إضافية.' : 'A prepayment invoice for the package price will be created. Sessions linked to this package draw down the balance with no extra charge.' }}</span>
                            </p>

                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showCreate = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                <button type="submit" :disabled="form.processing" class="pkg-submit">{{ form.processing ? (isRtl ? 'جارٍ...' : 'Saving...') : (isRtl ? 'إنشاء الباقة' : 'Create Purchase') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmModal
            :show="showCancel"
            :title="isRtl ? 'إلغاء الباقة' : 'Cancel Package'"
            :message="isRtl ? 'سيتم إلغاء هذه الباقة وإيقاف خصم الجلسات منها. الفاتورة لا تُحذف.' : 'This package will be cancelled and stop drawing sessions. The invoice is not deleted.'"
            :confirmText="isRtl ? 'إلغاء الباقة' : 'Cancel Package'"
            :cancelText="isRtl ? 'رجوع' : 'Back'"
            confirmColor="red"
            @confirm="doCancel"
            @cancel="showCancel = false"
        />
    </div>
</template>

<style scoped>
.pkg-hero {
    position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px;
    background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%);
    box-shadow: 0 18px 40px -20px rgba(27, 54, 93, 0.5);
}
.pkg-hero-orb {
    position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none;
}
.pkg-hero-badge {
    width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 20px rgba(196, 162, 101, 0.35);
}
.pkg-new-btn {
    display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px;
    background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 13px; font-weight: 700;
    box-shadow: 0 8px 20px -8px rgba(196,162,101,0.6); transition: transform 0.15s, box-shadow 0.3s;
}
.pkg-new-btn:hover { box-shadow: 0 12px 26px -8px rgba(196,162,101,0.75); }
.pkg-new-btn:active { transform: translateY(1px); }

.pkg-stat { position: relative; background: #fff; border: 1px solid rgba(196,162,101,0.16); border-radius: 14px; padding: 16px; overflow: hidden; }
.pkg-stat-bar { position: absolute; top: 0; inset-inline: 0; height: 3px; }
.pkg-stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
.pkg-stat-value { font-size: 26px; font-weight: 800; margin-top: 4px; font-variant-numeric: tabular-nums; line-height: 1.1; }

.pkg-search { display: flex; align-items: center; background: #fff; border: 1px solid #eef0f3; border-radius: 12px; transition: border-color 0.2s, box-shadow 0.2s; }
.pkg-search:focus-within { border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }
.pkg-search input { flex: 1; border: 0; outline: 0; background: transparent; padding: 10px 12px; font-size: 14px; }
.pkg-select { background: #fff; border: 1px solid #eef0f3; border-radius: 12px; padding: 0 14px; font-size: 13px; color: #374151; }
.pkg-select:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.12); }

.pkg-table-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px -22px rgba(27,54,93,0.2); }
.pkg-row { transition: background 0.18s; }
.pkg-row:hover { background: linear-gradient(90deg, rgba(196,162,101,0.05), transparent); }

.pkg-label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; }
.pkg-field { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 9px; font-size: 14px; transition: all 0.2s; background: #fff; }
.pkg-field:focus { outline: 0; border-color: #C4A265; box-shadow: 0 0 0 3px rgba(196,162,101,0.15); }
.pkg-submit { padding: 9px 20px; border-radius: 9px; background: linear-gradient(135deg, #1B365D, #22406F); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 6px 18px -8px rgba(27,54,93,0.6); transition: box-shadow 0.3s; }
.pkg-submit:hover:not(:disabled) { box-shadow: 0 10px 24px -8px rgba(27,54,93,0.7); }
.pkg-submit:disabled { opacity: 0.6; cursor: not-allowed; }

/* entrance */
.pkg-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 70ms + 80ms); }
.is-mounted .pkg-stagger { opacity: 1; transform: translateY(0); }

/* modal transition */
.pkg-modal-enter-active, .pkg-modal-leave-active { transition: opacity 0.25s ease; }
.pkg-modal-enter-from, .pkg-modal-leave-to { opacity: 0; }
.pkg-modal-enter-active .pkg-dialog, .pkg-modal-leave-active .pkg-dialog { transition: transform 0.3s cubic-bezier(0.16,1,0.3,1); }
.pkg-modal-enter-from .pkg-dialog { transform: translateY(20px) scale(0.97); }

@media (prefers-reduced-motion: reduce) {
    .pkg-stagger { transition-duration: 0.01s; transition-delay: 0s; }
    .pkg-modal-enter-active .pkg-dialog { transition: none; }
}
</style>
