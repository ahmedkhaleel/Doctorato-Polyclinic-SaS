<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency } = useCurrency();

const props = defineProps({
    slips: Object,
});

const headerLoaded = ref(false);
const cardsLoaded = ref(false);

onMounted(() => {
    setTimeout(() => headerLoaded.value = true, 50);
    setTimeout(() => cardsLoaded.value = true, 200);
});

const monthNamesEn = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const monthNamesAr = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function periodLabel(slip) {
    const names = isRtl.value ? monthNamesAr : monthNamesEn;
    return `${names[slip.month] || slip.month} ${slip.year}`;
}

const slipsList = computed(() => props.slips?.data || []);

const totalEarnings = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.total_earnings || 0), 0));
const totalDeductions = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.total_deductions || 0), 0));
const totalNet = computed(() => slipsList.value.reduce((s, sl) => s + Number(sl.net_salary || 0), 0));
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8 transition-all duration-700"
            :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
        >
            <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-radial from-indigo-500/10 to-transparent rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-gradient-radial from-[#C4A265]/5 to-transparent rounded-full translate-y-1/2 -translate-x-1/4"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ $t('a_my_salary_slips') }}</h1>
                        <p class="text-sm text-gray-400 mt-0.5">{{ isRtl ? 'عرض وتحميل كشوف الرواتب' : 'View and download your salary slips' }}</p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي المكاسب' : 'Total Earnings' }}</p>
                        <p class="text-lg font-bold text-emerald-400 mt-0.5">{{ formatCurrency(totalEarnings) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الخصومات' : 'Total Deductions' }}</p>
                        <p class="text-lg font-bold text-red-400 mt-0.5">{{ formatCurrency(totalDeductions) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                        <p class="text-xs text-gray-400">{{ isRtl ? 'صافي الراتب' : 'Net Total' }}</p>
                        <p class="text-lg font-bold text-white mt-0.5">{{ formatCurrency(totalNet) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary Slips Cards -->
        <div
            class="transition-all duration-700"
            :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
        >
            <div v-if="slipsList.length" class="space-y-3">
                <div
                    v-for="slip in slipsList"
                    :key="slip.id"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                        <!-- Period -->
                        <div class="flex items-center gap-3 sm:w-48">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex flex-col items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 leading-none">{{ slip.month }}</span>
                                <span class="text-[9px] text-indigo-400 font-semibold">{{ slip.year }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ periodLabel(slip) }}</p>
                                <p v-if="slip.slip_number" class="text-xs text-gray-400 font-mono">#{{ slip.slip_number }}</p>
                            </div>
                        </div>

                        <!-- Financial Info -->
                        <div class="flex-1 grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'المكاسب' : 'Earnings' }}</p>
                                <p class="text-sm font-bold text-emerald-600 tabular-nums mt-0.5">{{ formatCurrency(slip.total_earnings) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الخصومات' : 'Deductions' }}</p>
                                <p class="text-sm font-bold text-red-500 tabular-nums mt-0.5">{{ formatCurrency(slip.total_deductions) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase">{{ isRtl ? 'الصافي' : 'Net' }}</p>
                                <p class="text-sm font-bold text-indigo-600 tabular-nums mt-0.5">{{ formatCurrency(slip.net_salary) }}</p>
                            </div>
                        </div>

                        <!-- Paid Date & Action -->
                        <div class="flex items-center gap-3">
                            <div v-if="slip.paid_at" class="text-xs text-gray-400">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-lg font-medium">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ formatDate(slip.paid_at) }}
                                </span>
                            </div>
                            <Link
                                :href="`/doctor/my-salary-slips/${slip.id}`"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                {{ isRtl ? 'عرض' : 'View' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-xl border border-gray-100">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد كشوف رواتب' : 'No salary slips found' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="slips.links?.length > 3" class="flex items-center justify-center gap-1 mt-4">
                <template v-for="link in slips.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-500 hover:bg-gray-100 bg-white border border-gray-200'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
