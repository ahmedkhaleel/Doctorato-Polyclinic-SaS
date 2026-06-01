<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();

const props = defineProps({
    slips: Object,
    employee: Object,
});

const monthNames = {
    ar: ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
    en: ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
};
function monthName(m) { return (isRtl.value ? monthNames.ar : monthNames.en)[m] || m; }

const statusCfg = {
    draft:    { ar: 'مسودة',  en: 'Draft',    cls: 'bg-gray-100 text-gray-600' },
    approved: { ar: 'معتمد',  en: 'Approved', cls: 'bg-slate-100 text-[#1B365D]' },
    paid:     { ar: 'مدفوع', en: 'Paid',     cls: 'bg-emerald-50 text-emerald-700' },
};
function sCls(s) { return statusCfg[s]?.cls || 'bg-gray-100 text-gray-600'; }
function sLabel(s) { return isRtl.value ? (statusCfg[s]?.ar || s) : (statusCfg[s]?.en || s); }

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="mps-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">

        <!-- Hero -->
        <div class="mps-hero mps-stagger" style="--i:0">
            <div class="mps-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="mps-badge">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الموارد البشرية' : 'HR' }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'قسائم راتبي' : 'My Payslips' }}</h1>
                    <p v-if="employee" class="text-xs text-white/70 mt-1">{{ isRtl ? 'الراتب الأساسي' : 'Basic salary' }}: {{ formatCurrency(employee.basic_salary) }}</p>
                </div>
            </div>
        </div>

        <!-- No employee record -->
        <div v-if="!employee" class="mps-card mps-stagger mt-6 text-center py-16" style="--i:1">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا يوجد ملف موظف مرتبط بحسابك.' : 'No employee record is linked to your account.' }}</p>
            <p class="text-xs text-gray-300 mt-1">{{ isRtl ? 'تواصل مع مدير الموارد البشرية.' : 'Contact your HR manager.' }}</p>
        </div>

        <div v-else-if="!slips.data?.length" class="mps-card mps-stagger mt-6 text-center py-16" style="--i:1">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد قسائم راتب بعد.' : 'No payslips yet.' }}</p>
        </div>

        <div v-else class="mps-card mps-stagger mt-6 overflow-hidden" style="--i:1">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                        <th class="text-start px-5 py-3">{{ isRtl ? 'الشهر' : 'Month' }}</th>
                        <th class="text-end px-5 py-3 hidden sm:table-cell">{{ isRtl ? 'الراتب الإجمالي' : 'Gross' }}</th>
                        <th class="text-end px-5 py-3 hidden md:table-cell">{{ isRtl ? 'الخصومات' : 'Deductions' }}</th>
                        <th class="text-end px-5 py-3">{{ isRtl ? 'الصافي' : 'Net' }}</th>
                        <th class="text-center px-5 py-3">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="slip in slips.data" :key="slip.id" class="mps-row hover:bg-slate-50/50 transition-colors">
                        <td class="px-5 py-4 font-semibold text-gray-800">{{ monthName(slip.month) }} {{ slip.year }}</td>
                        <td class="px-5 py-4 text-end tabular-nums text-gray-600 hidden sm:table-cell">{{ formatCurrency(slip.total_earnings) }}</td>
                        <td class="px-5 py-4 text-end tabular-nums text-red-500 hidden md:table-cell">-{{ formatCurrency(slip.total_deductions) }}</td>
                        <td class="px-5 py-4 text-end tabular-nums font-bold text-[#1B365D]">{{ formatCurrency(slip.net_salary) }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold" :class="sCls(slip.status)">{{ sLabel(slip.status) }}</span>
                        </td>
                        <td class="px-5 py-4 text-end">
                            <Link :href="`/admin/my-payslips/${slip.id}`" class="text-xs font-semibold text-[#1B365D] hover:text-[#22406F] px-2 py-1 rounded-lg hover:bg-slate-100 transition">
                                {{ isRtl ? 'عرض' : 'View' }}
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="slips.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center gap-2 flex-wrap">
                <Link v-for="link in slips.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                      :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:border-[#C4A265]/40'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed']" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.mps-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.mps-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.22), transparent 70%); filter: blur(20px); pointer-events: none; }
.mps-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.mps-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; box-shadow: 0 10px 30px -22px rgba(27,54,93,0.2); }
.mps-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0)*70ms + 80ms); }
.is-mounted .mps-stagger { opacity: 1; transform: translateY(0); }
@media (prefers-reduced-motion: reduce) { .mps-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
