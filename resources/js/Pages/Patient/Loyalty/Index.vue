<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
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
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

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
                    <div v-if="balance >= min_redeem"
                         class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-200 text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ isRtl ? 'يمكنك الاستبدال' : 'Eligible to redeem' }}
                    </div>
                    <p v-else class="text-xs text-white/60">
                        {{ isRtl
                            ? `تحتاج ${(min_redeem - balance).toLocaleString()} نقطة أخرى للاستبدال`
                            : `${(min_redeem - balance).toLocaleString()} more points to redeem` }}
                    </p>
                </div>
            </div>
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
