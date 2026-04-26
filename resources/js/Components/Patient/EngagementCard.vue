<script setup>
/**
 * Compact engagement summary card shown on staff-facing patient pages
 * (Admin / Secretary / Doctor). Surfaces:
 *   - loyalty balance + EGP value + redeem-eligibility chip
 *   - patient's active discount codes (LOYAL/FRIEND/THANKS) — staff
 *     can copy any code with one click to apply on the next invoice
 *   - referral count + total reward earned
 *
 * Read-only for staff: the actual loyalty management is on /admin/loyalty.
 */
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency';

const props = defineProps({
    engagement: { type: Object, default: () => null },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();

const copied = ref(null);
function copyCode(code) {
    if (!code) return;
    navigator.clipboard.writeText(code).then(() => {
        copied.value = code;
        setTimeout(() => { copied.value = null; }, 2000);
    });
}

const hasAnything = computed(() =>
    props.engagement && (
        props.engagement.loyalty?.balance > 0
        || props.engagement.loyalty?.active_codes?.length
        || props.engagement.referral?.count > 0
    )
);
</script>

<template>
    <div v-if="engagement" class="bg-white rounded-xl border border-[#C4A265]/30 overflow-hidden">
        <div class="px-4 py-3 bg-gradient-to-r from-[#FAF7F0] to-white border-b border-[#C4A265]/20 flex items-center gap-2">
            <svg class="w-4 h-4 text-[#C4A265]" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <h3 class="text-sm font-bold text-[#1B365D]">
                {{ isRtl ? 'الولاء والمكافآت' : 'Loyalty & Rewards' }}
            </h3>
            <span v-if="engagement.loyalty?.eligible_to_redeem"
                  class="ms-auto inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase">
                {{ isRtl ? 'يمكنه الاستبدال' : 'Eligible to redeem' }}
            </span>
        </div>

        <div v-if="!hasAnything" class="p-5 text-center">
            <p class="text-xs text-gray-400">
                {{ isRtl ? 'لا توجد مكافآت أو نقاط بعد' : 'No rewards or points yet' }}
            </p>
        </div>

        <div v-else class="p-4 space-y-4">
            <!-- Top row: Balance + Referrals -->
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center bg-gradient-to-br from-[#FAF7F0] to-white border border-[#C4A265]/20 rounded-lg p-3">
                    <p class="text-2xl font-extrabold text-[#1B365D] tabular-nums">{{ engagement.loyalty.balance.toLocaleString() }}</p>
                    <p class="text-[9px] text-gray-500 uppercase tracking-wider mt-0.5">{{ isRtl ? 'نقاط' : 'Points' }}</p>
                    <p class="text-[10px] text-[#8B7043] font-bold mt-1">≈ {{ formatCurrency(engagement.loyalty.egp_value) }}</p>
                </div>
                <div class="text-center bg-gradient-to-br from-emerald-50 to-white border border-emerald-100 rounded-lg p-3">
                    <p class="text-2xl font-extrabold text-emerald-600 tabular-nums">{{ engagement.referral.count.toLocaleString() }}</p>
                    <p class="text-[9px] text-gray-500 uppercase tracking-wider mt-0.5">{{ isRtl ? 'إحالات' : 'Referrals' }}</p>
                    <p v-if="engagement.referral.total_discount > 0" class="text-[10px] text-emerald-700 font-bold mt-1">
                        {{ formatCurrency(engagement.referral.total_discount) }}
                    </p>
                </div>
            </div>

            <!-- Active codes (staff copy → invoice) -->
            <div v-if="engagement.loyalty.active_codes && engagement.loyalty.active_codes.length">
                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
                        🎟 {{ isRtl ? 'أكواد المريض النشطة' : 'Active codes' }}
                    </p>
                    <span class="text-[10px] text-gray-400">{{ engagement.loyalty.active_codes.length }}</span>
                </div>
                <div class="space-y-1.5">
                    <div v-for="c in engagement.loyalty.active_codes.slice(0, 5)" :key="c.code"
                         class="flex items-center justify-between gap-2 p-2 rounded-md bg-emerald-50/50 border border-emerald-100">
                        <div class="min-w-0 flex-1">
                            <code class="font-mono font-bold text-emerald-800 text-xs truncate block">{{ c.code }}</code>
                            <p class="text-[10px] text-gray-500 mt-0.5">
                                {{ c.type === 'percentage' ? `${c.amount}%` : formatCurrency(c.amount) }}
                                <span v-if="c.expires_at" class="ms-1 text-amber-600">
                                    · {{ isRtl ? 'حتى' : 'until' }} {{ c.expires_at }}
                                </span>
                            </p>
                        </div>
                        <button @click="copyCode(c.code)" type="button"
                                :title="isRtl ? 'نسخ للفاتورة التالية' : 'Copy for next invoice'"
                                class="flex-shrink-0 inline-flex items-center px-2 py-1 rounded-md bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] font-semibold transition">
                            <svg v-if="copied !== c.code" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
