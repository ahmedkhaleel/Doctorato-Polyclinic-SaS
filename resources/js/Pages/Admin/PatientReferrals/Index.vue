<script setup>
import { computed, ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    referrals:    Object,
    stats:        Object,
    topReferrers: Array,
    filters:      Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');

let timer = null;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/patient-referrals', { search: v }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});
</script>

<template>
    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-[#1B365D]">
                {{ isRtl ? 'إحالات المرضى' : 'Patient Referrals' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl ? 'متابعة برنامج "ادعُ صديقاً" والمكافآت الممنوحة' : 'Monitor the refer-a-friend program and reward issuance' }}
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-[#C4A265]/20 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'إجمالي الإحالات' : 'Total referrals' }}</p>
                <p class="text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">{{ stats.total_referrals.toLocaleString() }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'تم الاستبدال' : 'Redeemed' }}</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">{{ stats.total_redeemed.toLocaleString() }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#C4A265]/30 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'إجمالي الخصم الممنوح' : 'Total discount issued' }}</p>
                <p class="text-2xl font-extrabold text-[#8B7043] mt-1 tabular-nums">{{ formatCurrency(stats.total_discount) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-blue-100 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'هذا الشهر' : 'This month' }}</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1 tabular-nums">{{ stats.this_month.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Top referrers -->
        <div v-if="topReferrers.length" class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
            <h2 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3h14M6 3v5a6 6 0 0012 0V3M9 21h6M12 17v4"/></svg>
                {{ isRtl ? 'أفضل المُحيلين' : 'Top referrers' }}
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div v-for="(r, i) in topReferrers" :key="r.patient_id"
                     class="bg-gradient-to-br from-[#FAF7F0] to-white border border-[#C4A265]/30 rounded-lg p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-base font-extrabold text-[#C4A265]">#{{ i + 1 }}</span>
                        <span class="text-xs font-semibold text-gray-700 truncate">{{ r.name }}</span>
                    </div>
                    <p class="text-lg font-bold text-[#1B365D] tabular-nums">{{ r.count }} <span class="text-[10px] font-normal text-gray-500">{{ isRtl ? 'إحالة' : 'referrals' }}</span></p>
                    <p class="text-[11px] text-[#8B7043] mt-0.5">{{ formatCurrency(r.total_discount) }}</p>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
            <input v-model="search" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم / رقم الملف / الكود...' : 'Search by name / file # / code...'"
                   class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]" />
        </div>

        <!-- Referrals table -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-[10px] uppercase tracking-wider text-gray-500">
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المُحيل' : 'Referrer' }}</th>
                            <th class="text-start px-4 py-3">{{ isRtl ? 'المُحال' : 'Referred' }}</th>
                            <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الكود' : 'Code' }}</th>
                            <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'الخصم' : 'Discount' }}</th>
                            <th class="text-center px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="(r, i) in referrals.data" :key="r.id" class="lst-row hover:bg-gray-50/50 transition" :style="{ '--row-i': i }">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ r.referrer_name }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ r.referrer_file || '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ r.referred_name }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ r.referred_file || '—' }}</p>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <code class="text-[11px] font-mono bg-gray-100 px-2 py-0.5 rounded">{{ r.code }}</code>
                            </td>
                            <td class="px-4 py-3 text-end font-bold text-[#8B7043] tabular-nums hidden sm:table-cell">
                                {{ r.discount_amount > 0 ? formatCurrency(r.discount_amount) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center hidden md:table-cell">
                                <span v-if="r.redeemed_at" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase">
                                    {{ isRtl ? 'استُبدل' : 'Redeemed' }}
                                </span>
                                <span v-else-if="r.first_booking_id" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">
                                    {{ isRtl ? 'حُجز' : 'Booked' }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold uppercase">
                                    {{ isRtl ? 'معلّق' : 'Pending' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end text-gray-400 text-xs tabular-nums hidden sm:table-cell">{{ r.created_at }}</td>
                        </tr>
                        <tr v-if="!referrals.data.length">
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">
                                {{ isRtl ? 'لا توجد إحالات بعد' : 'No referrals yet' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="referrals.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in referrals.links" :key="link.label"
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
