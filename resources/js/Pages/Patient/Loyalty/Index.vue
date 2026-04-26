<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, useForm } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    balance: Number,
    egp_value: Number,
    currency: String,
    min_redeem: Number,
    rules: Object,
    stats: Object,
    transactions: Object, // paginator
    activeCodes: { type: Array, default: () => [] },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

// ── Redemption flow ─────────────────────────
const showRedeem = ref(false);
const form = useForm({ points: props.min_redeem });

const redeemPreview = computed(() => {
    const p = parseInt(form.points) || 0;
    return (p * (props.rules?.redeem_rate || 0.10));
});

const redemption = computed(() => page.props.flash?.redemption || null);

const codeCopied = ref(null);
function copyCode(code) {
    if (!code) return;
    navigator.clipboard.writeText(code).then(() => {
        codeCopied.value = code;
        setTimeout(() => { codeCopied.value = null; }, 2000);
    });
}

function submitRedeem() {
    form.post(lp('/loyalty/redeem'), {
        preserveScroll: true,
        onSuccess: () => {
            showRedeem.value = false;
            form.reset('points');
            form.points = props.min_redeem;
        },
    });
}

function typeLabel(t) {
    const ar = { earn: 'كسب', redeem: 'استبدال', expire: 'انتهاء', adjust: 'تسوية' };
    const en = { earn: 'Earned', redeem: 'Redeemed', expire: 'Expired', adjust: 'Adjusted' };
    return (locale.value === 'ar' ? ar[t] : en[t]) || t;
}
function typeColor(t) {
    return {
        earn:   'bg-emerald-100 text-emerald-700 border-emerald-200',
        redeem: 'bg-amber-100   text-amber-700   border-amber-200',
        expire: 'bg-gray-100    text-gray-600    border-gray-200',
        adjust: 'bg-slate-100   text-slate-700   border-slate-200',
    }[t] || 'bg-gray-100 text-gray-700';
}
function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return d; }
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#22406F] to-[#0F2444] shadow-xl mb-6 p-6 md:p-8 text-white">
            <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative grid md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                            {{ isRtl ? 'نقاط الولاء' : 'Loyalty Points' }}
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-extrabold mb-2">
                        {{ balance.toLocaleString() }}
                        <span class="text-base font-normal text-white/60 ms-2">
                            {{ isRtl ? 'نقطة' : 'points' }}
                        </span>
                    </h1>
                    <p class="text-sm text-white/70">
                        {{ isRtl ? 'تساوي تقريباً' : 'Approx. value:' }}
                        <span class="font-bold text-[#C4A265]">{{ formatCurrency(egp_value) }}</span>
                    </p>
                </div>
                <div class="md:text-end">
                    <button v-if="balance >= min_redeem" @click="showRedeem = !showRedeem"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#C4A265] hover:bg-[#8B7043] text-white text-sm font-bold shadow-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ isRtl ? 'استبدال النقاط' : 'Redeem points' }}
                    </button>
                    <p v-else class="text-xs text-white/60">
                        {{ isRtl
                            ? `تحتاج ${(min_redeem - balance).toLocaleString()} نقطة أخرى للاستبدال`
                            : `${(min_redeem - balance).toLocaleString()} more points to redeem` }}
                    </p>
                </div>
            </div>
        </div>

        <!-- ── Redemption success banner ─────────────────────── -->
        <div v-if="redemption" class="bg-gradient-to-r from-emerald-50 to-white border-2 border-emerald-200 rounded-2xl p-5 mb-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-emerald-900 mb-1">
                        {{ isRtl ? '🎉 تم الاستبدال بنجاح!' : '🎉 Redemption successful!' }}
                    </h3>
                    <p class="text-xs text-emerald-700 mb-3">
                        {{ isRtl
                            ? `استخدم هذا الكود في حجزك القادم — صالح حتى ${redemption.expires_at}`
                            : `Use this code on your next booking — valid until ${redemption.expires_at}` }}
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <code class="px-4 py-2 bg-white border-2 border-dashed border-emerald-400 rounded-lg text-emerald-800 font-mono font-extrabold text-lg tracking-wider">
                            {{ redemption.code }}
                        </code>
                        <span class="text-sm font-bold text-emerald-700">= {{ formatCurrency(redemption.amount) }}</span>
                        <button @click="copyCode(redemption.code)"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold transition">
                            <svg v-if="codeCopied !== redemption.code" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ codeCopied === redemption.code ? (isRtl ? 'نُسخ' : 'Copied') : (isRtl ? 'نسخ' : 'Copy') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Redeem form ─────────────────────────── -->
        <div v-if="showRedeem" class="bg-[#FAF7F0] border-2 border-[#C4A265]/40 rounded-2xl p-5 mb-6">
            <h3 class="text-sm font-bold text-[#1B365D] mb-3">
                {{ isRtl ? 'كم نقطة تريد استبدالها؟' : 'How many points to redeem?' }}
            </h3>
            <form @submit.prevent="submitRedeem" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <input v-model="form.points" type="number" :min="min_redeem" :max="balance" step="1" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                        <p class="text-[11px] text-gray-500 mt-1">
                            {{ isRtl
                                ? `الحد الأدنى ${min_redeem} نقطة. رصيدك ${balance.toLocaleString()}.`
                                : `Min ${min_redeem} pts. Your balance: ${balance.toLocaleString()}.` }}
                        </p>
                        <p v-if="form.errors.points" class="text-xs text-red-600 mt-1">{{ form.errors.points }}</p>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-[#C4A265]/30 text-center">
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider">{{ isRtl ? 'تحصل على' : 'You get' }}</p>
                        <p class="text-2xl font-extrabold text-[#8B7043] tabular-nums">{{ formatCurrency(redeemPreview) }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" :disabled="form.processing"
                            class="px-5 py-2.5 rounded-lg bg-[#1B365D] text-white text-sm font-bold disabled:opacity-50">
                        {{ form.processing ? (isRtl ? 'جارٍ...' : 'Processing...') : (isRtl ? 'تأكيد الاستبدال' : 'Confirm redemption') }}
                    </button>
                    <button type="button" @click="showRedeem = false"
                            class="px-5 py-2.5 rounded-lg bg-white border border-gray-300 text-gray-600 text-sm">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                </div>
                <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2">
                    💡 {{ isRtl
                        ? 'بعد الاستبدال ستحصل على كود خصم مفرد الاستخدام صالح لـ 30 يوماً. يمكنك استخدامه عند الحجز.'
                        : 'After redeeming, you receive a single-use discount code valid for 30 days. Use it when booking.' }}
                </p>
            </form>
        </div>

        <!-- Stat trio -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-5 text-center">
                <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ (stats?.total_earned || 0).toLocaleString() }}</p>
                <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-1">{{ isRtl ? 'إجمالي المكتسب' : 'Total Earned' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-5 text-center">
                <p class="text-2xl font-extrabold text-amber-600 tabular-nums">{{ (stats?.total_redeemed || 0).toLocaleString() }}</p>
                <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-1">{{ isRtl ? 'إجمالي المستبدل' : 'Total Redeemed' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
                <p class="text-2xl font-extrabold text-gray-500 tabular-nums">{{ (stats?.total_expired || 0).toLocaleString() }}</p>
                <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-1">{{ isRtl ? 'منتهي الصلاحية' : 'Expired' }}</p>
            </div>
        </div>

        <!-- ── Active Discount Codes (unused/unexpired LOYAL codes) ── -->
        <div v-if="activeCodes.length" class="bg-white rounded-2xl shadow-sm border-2 border-emerald-200 p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                    🎟 {{ isRtl ? 'أكواد خصم متاحة' : 'Active discount codes' }}
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px]">{{ activeCodes.length }}</span>
                </h2>
                <span class="text-[10px] text-gray-500">{{ isRtl ? 'استخدمها قبل انتهائها' : 'Use before they expire' }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div v-for="c in activeCodes" :key="c.code"
                     class="flex items-center justify-between gap-3 p-3 rounded-lg bg-gradient-to-r from-emerald-50/70 to-white border border-emerald-200">
                    <div class="min-w-0">
                        <code class="font-mono font-extrabold text-emerald-800 text-sm tracking-wider truncate block">{{ c.code }}</code>
                        <p class="text-[11px] text-gray-500 mt-0.5">
                            {{ formatCurrency(c.amount) }}
                            <span v-if="c.expires_at" class="ms-1 text-amber-600">
                                · {{ isRtl ? 'صالح حتى' : 'until' }} {{ c.expires_at }}
                            </span>
                        </p>
                    </div>
                    <button @click="copyCode(c.code)"
                            :title="isRtl ? 'نسخ' : 'Copy'"
                            class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold transition">
                        <svg v-if="codeCopied !== c.code" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ codeCopied === c.code ? (isRtl ? 'نُسخ' : 'Copied') : (isRtl ? 'نسخ' : 'Copy') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Rules -->
        <div class="bg-white rounded-2xl shadow-sm border border-[#C4A265]/20 p-5 mb-6">
            <h2 class="text-sm font-bold text-[#1B365D] mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ isRtl ? 'كيف تعمل النقاط؟' : 'How points work' }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-gray-600">
                <p class="flex items-start gap-2"><span class="text-[#C4A265] font-bold">•</span>
                    {{ isRtl
                        ? `كل زيارة مكتملة = ${rules.points_per_visit} نقطة + ${rules.points_per_egp} نقطة لكل ${currency} في الفاتورة`
                        : `${rules.points_per_visit} pts per completed visit + ${rules.points_per_egp} pt per ${currency} billed` }}
                </p>
                <p class="flex items-start gap-2"><span class="text-[#C4A265] font-bold">•</span>
                    {{ isRtl
                        ? `كل ${(1 / rules.redeem_rate).toFixed(0)} نقطة = ${currency} 1 خصم على الحجز`
                        : `${(1 / rules.redeem_rate).toFixed(0)} pts = ${currency} 1 discount` }}
                </p>
                <p class="flex items-start gap-2"><span class="text-[#C4A265] font-bold">•</span>
                    {{ isRtl
                        ? `الحد الأدنى للاستبدال: ${min_redeem} نقطة`
                        : `Minimum redemption: ${min_redeem} points` }}
                </p>
                <p v-if="rules.expiry_months > 0" class="flex items-start gap-2"><span class="text-[#C4A265] font-bold">•</span>
                    {{ isRtl
                        ? `النقاط تنتهي بعد ${rules.expiry_months} شهر من تاريخ الكسب`
                        : `Points expire ${rules.expiry_months} months after earning` }}
                </p>
            </div>
        </div>

        <!-- Ledger -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'سجل المعاملات' : 'Transaction history' }}</h2>
            </div>
            <div v-if="transactions.data.length" class="divide-y divide-gray-50">
                <div v-for="row in transactions.data" :key="row.id" class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <span :class="typeColor(row.type)" class="text-[10px] font-bold px-2 py-1 rounded-full uppercase whitespace-nowrap border">
                            {{ typeLabel(row.type) }}
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-700 truncate">
                                {{ row.description || (isRtl ? 'بدون وصف' : 'No description') }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                {{ fmtDate(row.created_at) }}
                                <span v-if="row.expires_at" class="ms-2">
                                    · {{ isRtl ? 'تنتهي:' : 'Expires:' }} {{ fmtDate(row.expires_at) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <span :class="row.points >= 0 ? 'text-emerald-600' : 'text-amber-600'"
                          class="text-base font-extrabold tabular-nums whitespace-nowrap">
                        {{ row.points >= 0 ? '+' : '' }}{{ row.points.toLocaleString() }}
                    </span>
                </div>
            </div>
            <div v-else class="p-12 text-center text-gray-400 text-sm">
                {{ isRtl ? 'لا توجد معاملات بعد. زرنا لتربح أولى نقاطك!' : 'No transactions yet. Visit us to earn your first points!' }}
            </div>

            <!-- Pagination -->
            <div v-if="transactions.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-between gap-2">
                <Link v-for="link in transactions.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>
