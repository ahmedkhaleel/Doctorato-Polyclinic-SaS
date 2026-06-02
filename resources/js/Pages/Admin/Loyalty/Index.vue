<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    patients: Object,  // paginator
    stats:    Object,
    rules:    Object,
    filters:  Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');

let timer = null;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/loyalty', { search: v }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

// ─── Rules edit form ───────────────────────────────
const editRules = ref(false);
const rulesForm = useForm({
    loyalty_points_per_egp:    props.rules.points_per_egp,
    loyalty_points_per_visit:  props.rules.points_per_visit,
    loyalty_redeem_rate:       props.rules.redeem_rate,
    loyalty_min_redeem_points: props.rules.min_redeem,
    loyalty_expiry_months:     props.rules.expiry_months,
});

function saveRules() {
    rulesForm.post('/admin/loyalty/settings', {
        preserveScroll: true,
        onSuccess: () => { editRules.value = false; },
    });
}

// ─── Page-load orchestration + count-up ─────────────
const mounted = ref(false);
const prefersReducedMotion = typeof window !== 'undefined'
    && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

function animateCount(el, target) {
    const final = Number(target) || 0;
    if (prefersReducedMotion) { el.textContent = final.toLocaleString(); return; }
    const duration = 1000;
    const start = performance.now();
    function frame(now) {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(final * eased).toLocaleString();
        if (t < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
}
const vCountup = {
    mounted(el, binding) { animateCount(el, binding.value); },
    updated(el, binding) { if (binding.value !== binding.oldValue) animateCount(el, binding.value); },
};

onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });

// First-letter avatar helper
function initial(name) {
    return (name || '?').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <div class="lyl-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">

        <!-- ═══════════ Navy Hero Header ═══════════ -->
        <div class="lyl-hero lyl-stagger" style="--i:0">
            <div class="lyl-hero-orb"></div>
            <div class="lyl-hero-accent"></div>
            <svg class="lyl-hero-ecg" viewBox="0 0 520 40" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 20 L150 20 L165 7 L180 33 L195 4 L210 36 L225 20 L360 20 L375 11 L390 29 L405 7 L420 33 L435 20 L520 20"
                      fill="none" stroke="#C4A265" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="lyl-hero-ecg-path" />
            </svg>

            <div class="relative z-10 flex items-center gap-4">
                <div class="lyl-hero-badge">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'برنامج المكافآت' : 'Rewards Program' }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">
                        {{ isRtl ? 'نظام نقاط الولاء' : 'Loyalty Points' }}
                    </h1>
                    <p class="text-xs md:text-sm text-white/70 mt-1">
                        {{ isRtl ? 'متابعة وضبط أرصدة المرضى من النقاط' : 'Monitor and adjust patient point balances' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ═══════════ Stat row ═══════════ -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-6 mb-6">
            <div class="lyl-stat lyl-stagger" style="--i:1">
                <div class="lyl-stat-bar lyl-bar-navy"></div>
                <p class="lyl-stat-label">{{ isRtl ? 'الرصيد القائم' : 'Outstanding Balance' }}</p>
                <p class="lyl-stat-value text-[#1B365D]" v-countup="stats.total_outstanding">{{ stats.total_outstanding.toLocaleString() }}</p>
                <p class="lyl-stat-sub">{{ isRtl ? 'نقطة' : 'pts' }}</p>
            </div>
            <div class="lyl-stat lyl-stagger" style="--i:2">
                <div class="lyl-stat-bar lyl-bar-gold"></div>
                <p class="lyl-stat-label">{{ isRtl ? 'مرضى لديهم نقاط' : 'Patients w/ points' }}</p>
                <p class="lyl-stat-value text-[#8B7043]" v-countup="stats.patients_with_pts">{{ stats.patients_with_pts.toLocaleString() }}</p>
                <p class="lyl-stat-sub">{{ isRtl ? 'مريض' : 'patients' }}</p>
            </div>
            <div class="lyl-stat lyl-stagger" style="--i:3">
                <div class="lyl-stat-bar lyl-bar-emerald"></div>
                <p class="lyl-stat-label">{{ isRtl ? 'مكتسب (30 يوم)' : 'Earned (30d)' }}</p>
                <p class="lyl-stat-value text-emerald-600">+<span v-countup="stats.awarded_30d">{{ stats.awarded_30d.toLocaleString() }}</span></p>
                <p class="lyl-stat-sub">{{ isRtl ? 'نقطة' : 'pts' }}</p>
            </div>
            <div class="lyl-stat lyl-stagger" style="--i:4">
                <div class="lyl-stat-bar lyl-bar-amber"></div>
                <p class="lyl-stat-label">{{ isRtl ? 'مستبدل (30 يوم)' : 'Redeemed (30d)' }}</p>
                <p class="lyl-stat-value text-amber-600">-<span v-countup="stats.redeemed_30d">{{ stats.redeemed_30d.toLocaleString() }}</span></p>
                <p class="lyl-stat-sub">{{ isRtl ? 'نقطة' : 'pts' }}</p>
            </div>
        </div>

        <!-- ═══════════ Rules summary / editor ═══════════ -->
        <div class="lyl-rules lyl-stagger" style="--i:5">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-bold text-[#8B7043] tracking-wider uppercase flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ isRtl ? 'القواعد الحالية' : 'Active rules' }}
                </p>
                <button @click="editRules = !editRules" class="lyl-edit-btn inline-flex items-center gap-1.5">
                    <svg v-if="editRules" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7m-1.5-9.5a2.12 2.12 0 013 3L12 16l-4 1 1-4 9.5-9.5z" /></svg>
                    {{ editRules ? (isRtl ? 'إلغاء' : 'Cancel') : (isRtl ? 'تعديل' : 'Edit') }}
                </button>
            </div>

            <Transition name="lyl-swap" mode="out-in">
                <div v-if="!editRules" key="view" class="grid grid-cols-2 lg:grid-cols-5 gap-2.5">
                    <div class="lyl-rule-chip">
                        <span class="lyl-rule-k">{{ isRtl ? 'لكل زيارة' : 'Per visit' }}</span>
                        <span class="lyl-rule-v">{{ rules.points_per_visit }} <small>{{ isRtl ? 'نقطة' : 'pts' }}</small></span>
                    </div>
                    <div class="lyl-rule-chip">
                        <span class="lyl-rule-k">{{ isRtl ? 'لكل عملة' : 'Per currency' }}</span>
                        <span class="lyl-rule-v">{{ rules.points_per_egp }} <small>{{ isRtl ? `نقطة/${rules.currency}` : `pt/${rules.currency}` }}</small></span>
                    </div>
                    <div class="lyl-rule-chip">
                        <span class="lyl-rule-k">{{ isRtl ? 'سعر الاستبدال' : 'Redeem rate' }}</span>
                        <span class="lyl-rule-v">{{ rules.redeem_rate }} <small>{{ rules.currency }}/{{ isRtl ? 'نقطة' : 'pt' }}</small></span>
                    </div>
                    <div class="lyl-rule-chip">
                        <span class="lyl-rule-k">{{ isRtl ? 'حد الاستبدال' : 'Min redeem' }}</span>
                        <span class="lyl-rule-v">{{ rules.min_redeem }} <small>{{ isRtl ? 'نقطة' : 'pts' }}</small></span>
                    </div>
                    <div class="lyl-rule-chip">
                        <span class="lyl-rule-k">{{ isRtl ? 'انتهاء الصلاحية' : 'Expiry' }}</span>
                        <span class="lyl-rule-v">{{ rules.expiry_months > 0 ? rules.expiry_months + (isRtl ? ' شهر' : ' mo') : (isRtl ? 'بدون' : 'never') }}</span>
                    </div>
                </div>

                <form v-else key="edit" @submit.prevent="saveRules" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div>
                            <label class="lyl-field-label">{{ isRtl ? 'لكل زيارة (نقطة)' : 'Per visit (pts)' }}</label>
                            <input v-model.number="rulesForm.loyalty_points_per_visit" type="number" min="0" max="10000" required class="lyl-input" />
                        </div>
                        <div>
                            <label class="lyl-field-label">{{ isRtl ? `نقطة/${rules.currency}` : `pts/${rules.currency}` }}</label>
                            <input v-model.number="rulesForm.loyalty_points_per_egp" type="number" min="0" max="100" step="0.1" required class="lyl-input" />
                        </div>
                        <div>
                            <label class="lyl-field-label">{{ isRtl ? `${rules.currency}/نقطة` : `${rules.currency}/pt` }}</label>
                            <input v-model.number="rulesForm.loyalty_redeem_rate" type="number" min="0" max="10" step="0.01" required class="lyl-input" />
                        </div>
                        <div>
                            <label class="lyl-field-label">{{ isRtl ? 'حد الاستبدال' : 'Min redeem' }}</label>
                            <input v-model.number="rulesForm.loyalty_min_redeem_points" type="number" min="1" max="100000" required class="lyl-input" />
                        </div>
                        <div>
                            <label class="lyl-field-label">{{ isRtl ? 'الانتهاء (شهر)' : 'Expiry (months)' }}</label>
                            <input v-model.number="rulesForm.loyalty_expiry_months" type="number" min="0" max="120" required class="lyl-input" />
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <button type="submit" :disabled="rulesForm.processing" class="lyl-save-btn">
                            <span class="lyl-save-shine"></span>
                            {{ rulesForm.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'حفظ القواعد' : 'Save rules') }}
                        </button>
                        <p class="text-[10px] text-gray-500">
                            {{ isRtl
                                ? '0 شهر = بدون انتهاء. التغييرات تنطبق على النقاط الجديدة فقط.'
                                : '0 months = never expire. Changes apply to new points only.' }}
                        </p>
                    </div>
                </form>
            </Transition>
        </div>

        <!-- ═══════════ Search ═══════════ -->
        <div class="lyl-search lyl-stagger" style="--i:6">
            <svg class="lyl-search-icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم / رقم الملف / الهاتف...' : 'Search by name / file # / phone...'" />
        </div>

        <!-- ═══════════ Patients table ═══════════ -->
        <div class="lyl-table-card lyl-stagger" style="--i:7">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                            <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                            <th class="text-end px-4 py-3">{{ isRtl ? 'الرصيد' : 'Balance' }}</th>
                            <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'القيمة' : 'Value' }}</th>
                            <th class="text-end px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(p, idx) in patients.data" :key="p.id" class="lyl-row" :style="{ '--ri': idx }">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="lyl-avatar">{{ initial(p.full_name) }}</span>
                                    <span class="font-medium text-gray-800">{{ p.full_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell font-mono text-xs">{{ p.file_number || '-' }}</td>
                            <td class="px-4 py-3 text-gray-500 hidden md:table-cell" dir="ltr">{{ p.phone || '-' }}</td>
                            <td class="px-4 py-3 text-end">
                                <span class="lyl-balance" :class="p.balance > 0 ? 'lyl-balance-on' : 'lyl-balance-off'">
                                    {{ p.balance.toLocaleString() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end text-gray-500 tabular-nums hidden sm:table-cell">
                                {{ formatCurrency(p.egp_value) }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                <Link :href="`/admin/loyalty/${p.id}`" class="lyl-manage-btn">
                                    {{ isRtl ? 'إدارة' : 'Manage' }}
                                    <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!patients.data.length">
                            <td colspan="6" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <p class="text-sm text-gray-400">{{ isRtl ? 'لا يوجد مرضى مطابقين' : 'No matching patients' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="patients.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in patients.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-[#C4A265]/40'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════
   LOYALTY ADMIN — brand navy + gold, animated
   ═══════════════════════════════════════════════════════════ */

/* ─── Hero ─── */
.lyl-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1rem;
    padding: 22px 24px;
    background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%);
    box-shadow: 0 18px 40px -20px rgba(27, 54, 93, 0.5);
}
.lyl-hero-orb {
    position: absolute;
    top: -80px;
    inset-inline-end: -60px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%);
    filter: blur(20px);
    pointer-events: none;
    animation: lylFloat 14s ease-in-out infinite;
}
.lyl-hero-accent {
    position: absolute;
    inset-inline: 0;
    top: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #C4A265, transparent);
}
.lyl-hero-ecg {
    position: absolute;
    bottom: 6px;
    inset-inline-start: 0;
    width: 100%;
    height: 36px;
    opacity: 0.4;
    pointer-events: none;
}
.lyl-hero-ecg-path {
    stroke-dasharray: 1400;
    stroke-dashoffset: 1400;
    animation: lylEcg 5s cubic-bezier(0.4, 0, 0.2, 1) 0.3s forwards infinite;
}
.lyl-hero-badge {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #C4A265, #8B7043);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(196, 162, 101, 0.35);
    flex-shrink: 0;
}

/* ─── Stat cards ─── */
.lyl-stat {
    position: relative;
    background: #fff;
    border: 1px solid rgba(196, 162, 101, 0.16);
    border-radius: 14px;
    padding: 16px;
    overflow: hidden;
    transition: transform 0.3s cubic-bezier(0.25,0.46,0.45,0.94), box-shadow 0.3s ease;
}
.lyl-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 30px -16px rgba(27, 54, 93, 0.25);
}
.lyl-stat-bar {
    position: absolute;
    top: 0; inset-inline: 0;
    height: 3px;
}
.lyl-bar-navy    { background: linear-gradient(90deg, #1B365D, #22406F); }
.lyl-bar-gold    { background: linear-gradient(90deg, #C4A265, #8B7043); }
.lyl-bar-emerald { background: linear-gradient(90deg, #10b981, #059669); }
.lyl-bar-amber   { background: linear-gradient(90deg, #f59e0b, #d97706); }
.lyl-stat-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
}
.lyl-stat-value {
    font-size: 26px;
    font-weight: 800;
    margin-top: 4px;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
}
.lyl-stat-sub {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}

/* ─── Rules card ─── */
.lyl-rules {
    background: linear-gradient(120deg, #FAF7F0, #fff);
    border: 1px solid rgba(196, 162, 101, 0.3);
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 16px;
}
.lyl-edit-btn {
    font-size: 12px;
    font-weight: 700;
    color: #1B365D;
    padding: 4px 12px;
    border-radius: 8px;
    transition: background 0.2s;
}
.lyl-edit-btn:hover { background: rgba(27, 54, 93, 0.06); }

.lyl-rule-chip {
    display: flex;
    flex-direction: column;
    gap: 3px;
    background: #fff;
    border: 1px solid rgba(196, 162, 101, 0.18);
    border-radius: 10px;
    padding: 10px 12px;
}
.lyl-rule-k {
    font-size: 10px;
    color: #8B7043;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.lyl-rule-v {
    font-size: 15px;
    font-weight: 800;
    color: #1B365D;
    font-variant-numeric: tabular-nums;
}
.lyl-rule-v small { font-size: 10px; font-weight: 500; color: #9ca3af; }

.lyl-field-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.lyl-input {
    width: 100%;
    padding: 7px 10px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-variant-numeric: tabular-nums;
    transition: all 0.2s;
}
.lyl-input:focus {
    outline: 0;
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.15);
}

.lyl-save-btn {
    position: relative;
    overflow: hidden;
    padding: 8px 20px;
    border-radius: 9px;
    background: linear-gradient(135deg, #1B365D, #22406F);
    color: #fff;
    font-size: 12.5px;
    font-weight: 700;
    transition: box-shadow 0.3s, transform 0.15s;
    box-shadow: 0 6px 18px -8px rgba(27, 54, 93, 0.6);
}
.lyl-save-btn:hover:not(:disabled) { box-shadow: 0 10px 24px -8px rgba(27, 54, 93, 0.7); }
.lyl-save-btn:active:not(:disabled) { transform: translateY(1px); }
.lyl-save-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.lyl-save-shine {
    position: absolute; top: 0; inset-inline-start: -150%;
    width: 60%; height: 100%;
    background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
    transform: skewX(-20deg);
    transition: inset-inline-start 0.7s ease;
}
.lyl-save-btn:hover:not(:disabled) .lyl-save-shine { inset-inline-start: 150%; }

/* ─── Search ─── */
.lyl-search {
    position: relative;
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.lyl-search:focus-within {
    border-color: #C4A265;
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.12);
}
.lyl-search-icon {
    width: 18px; height: 18px;
    margin-inline-start: 12px;
    color: #9ca3af;
    flex-shrink: 0;
}
.lyl-search input {
    flex: 1;
    border: 0;
    outline: 0;
    background: transparent;
    padding: 9px 12px;
    font-size: 14px;
    color: #1f2937;
}

/* ─── Table ─── */
.lyl-table-card {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 30px -22px rgba(27, 54, 93, 0.2);
}
.lyl-row {
    transition: background 0.18s;
}
.lyl-row:hover { background: linear-gradient(90deg, rgba(196,162,101,0.05), transparent); }
.lyl-avatar {
    width: 32px; height: 32px;
    border-radius: 9px;
    background: linear-gradient(135deg, #1B365D, #22406F);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lyl-balance {
    display: inline-block;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    padding: 3px 10px;
    border-radius: 999px;
}
.lyl-balance-on  { color: #1B365D; background: rgba(196, 162, 101, 0.12); }
.lyl-balance-off { color: #cbd5e1; }
.lyl-manage-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 9px;
    background: #1B365D;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.25s;
}
.lyl-manage-btn:hover {
    background: linear-gradient(135deg, #C4A265, #8B7043);
    gap: 8px;
}

/* ─── Staggered entrance ─── */
.lyl-stagger {
    opacity: 0;
    transform: translateY(14px);
    transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94);
    transition-delay: calc(var(--i, 0) * 70ms + 80ms);
}
.is-mounted .lyl-stagger { opacity: 1; transform: translateY(0); }

/* ─── Rules view/edit swap transition ─── */
.lyl-swap-enter-active, .lyl-swap-leave-active { transition: all 0.25s cubic-bezier(0.25,0.46,0.45,0.94); }
.lyl-swap-enter-from { opacity: 0; transform: translateY(-6px); }
.lyl-swap-leave-to   { opacity: 0; transform: translateY(6px); }

/* ─── Keyframes ─── */
@keyframes lylFloat {
    0%, 100% { transform: translate(0,0); }
    50%      { transform: translate(-14px, 18px); }
}
@keyframes lylEcg {
    0%   { stroke-dashoffset: 1400; opacity: 0; }
    10%  { opacity: 0.4; }
    65%  { stroke-dashoffset: 0; opacity: 0.5; }
    100% { stroke-dashoffset: 0; opacity: 0.18; }
}

/* ─── Accessibility ─── */
@media (prefers-reduced-motion: reduce) {
    .lyl-stagger { transition-duration: 0.01s; transition-delay: 0s; }
    .lyl-hero-orb, .lyl-hero-ecg-path { animation: none; }
    .lyl-stat:hover { transform: none; }
}
</style>
