<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slips: Object,
});

const months = isRtl.value
    ? ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
    : ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const { formatCurrency } = useCurrency();

const statusConfig = {
    draft:    { label: 'Draft',    labelAr: 'مسودة',   bg: 'bg-gray-100',   text: 'text-gray-600', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
    approved: { label: 'Approved', labelAr: 'معتمد',   bg: 'bg-blue-50',    text: 'text-blue-700', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    paid:     { label: 'Paid',     labelAr: 'مدفوع',   bg: 'bg-emerald-50', text: 'text-emerald-700', icon: 'M5 13l4 4L19 7' },
};

function getStatus(status) {
    return statusConfig[status] || { label: status, labelAr: status, bg: 'bg-gray-100', text: 'text-gray-600' };
}

// Stats
const totalEarnings = computed(() => props.slips?.data?.reduce((s, sl) => s + Number(sl.total_earnings || 0), 0) || 0);
const totalNet = computed(() => props.slips?.data?.reduce((s, sl) => s + Number(sl.net_salary || 0), 0) || 0);
const paidCount = computed(() => props.slips?.data?.filter(s => s.status === 'paid').length || 0);

// Animations
const headerLoaded = ref(false);
const cardsLoaded = ref(false);
onMounted(() => {
    setTimeout(() => { headerLoaded.value = true; }, 50);
    setTimeout(() => { cardsLoaded.value = true; }, 200);
});
</script>

<template>
    <div>
        <!-- ═══ HERO HEADER ═══ -->
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 30% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'الموارد البشرية' : 'HR Module' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'كشوف مرتباتي' : 'My Salary Slips' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'عرض كشوف المرتبات الشهرية وسجل المدفوعات' : 'View your monthly salary slips and payment history' }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'الكشوف' : 'Total Slips' }}</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ slips?.data?.length || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'مدفوع' : 'Paid' }}</p>
                        <p class="text-2xl font-bold text-[#0d9488] mt-1">{{ paidCount }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'صافي الراتب' : 'Net Total' }}</p>
                        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ formatCurrency(totalNet) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ SALARY SLIP CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="slip in slips.data"
                :key="slip.id"
                class="group bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-lg hover:border-gray-200/80 transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                    <!-- Month/Year -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#0d9488]/15 to-[#0d9488]/5 flex flex-col items-center justify-center flex-shrink-0">
                            <span class="text-[10px] font-bold text-[#0d9488] uppercase leading-none">{{ months[slip.month]?.substring(0, 3) }}</span>
                            <span class="text-lg font-black text-gray-800 leading-none mt-0.5">{{ slip.year }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 text-[15px]">{{ months[slip.month] }} {{ slip.year }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs font-mono text-[#0d9488] font-semibold">{{ slip.slip_number }}</span>
                                <span :class="[getStatus(slip.status).bg, getStatus(slip.status).text]" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ isRtl ? getStatus(slip.status).labelAr : getStatus(slip.status).label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="flex items-center gap-6 sm:gap-8">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الإجمالي' : 'Earnings' }}</p>
                            <p class="text-sm font-bold text-gray-700 mt-0.5">{{ formatCurrency(slip.total_earnings) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الخصومات' : 'Deductions' }}</p>
                            <p class="text-sm font-bold text-red-500 mt-0.5">{{ formatCurrency(slip.total_deductions) }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-[#0d9488] font-bold uppercase">{{ isRtl ? 'صافي' : 'Net' }}</p>
                            <p class="text-base font-black text-gray-900 mt-0.5">{{ formatCurrency(slip.net_salary) }}</p>
                        </div>
                    </div>

                    <!-- Action -->
                    <Link
                        :href="`/secretary/my-salary-slips/${slip.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors flex-shrink-0"
                    >
                        {{ isRtl ? 'عرض التفاصيل' : 'View Details' }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!slips.data || slips.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد كشوف مرتبات' : 'No salary slips found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'ستظهر كشوف مرتباتك هنا بمجرد إنشائها' : 'Your salary slips will appear here once generated' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="slips.links?.length > 3" class="flex items-center justify-center gap-1 mt-6">
            <template v-for="link in slips.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
