<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    payments: Object,
    filters: Object,
    paymentMethods: Array,
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
let searchTimeout = null;

function buildParams() {
    return {
        search: search.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    };
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/secretary/payments', buildParams(), { preserveState: true, replace: true });
    }, 400);
});

watch([dateFrom, dateTo], () => {
    router.get('/secretary/payments', buildParams(), { preserveState: true, replace: true });
});

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return isRtl.value ? 'اليوم' : 'Today';
    if (d.toDateString() === yesterday.toDateString()) return isRtl.value ? 'أمس' : 'Yesterday';
    return d.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const { formatCurrency } = useCurrency();

// Stats
const totalAmount = computed(() => props.payments?.data?.reduce((s, p) => s + Number(p.amount || 0), 0) || 0);
const paymentCount = computed(() => props.payments?.data?.length || 0);

const methodLabels = {
    cash: { label: 'Cash', labelAr: 'نقدي' },
    card: { label: 'Card', labelAr: 'بطاقة' },
    bank_transfer: { label: 'Bank Transfer', labelAr: 'تحويل بنكي' },
    insurance: { label: 'Insurance', labelAr: 'تأمين' },
};

function getMethodLabel(method) {
    if (!method) return '-';
    const name = method.name_en || method.name || method;
    const config = methodLabels[name?.toLowerCase?.()];
    if (config) return isRtl.value ? config.labelAr : config.label;
    return isRtl.value ? (method.name_ar || method.name_en || method.name || '-') : (method.name_en || method.name || '-');
}

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
        <div class="relative -mx-4 sm:-mx-6 lg:-mx-8 -mt-4 sm:-mt-6 mb-8 px-4 sm:px-6 lg:px-8 pt-8 pb-10 bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] overflow-hidden transition-all duration-700" :class="headerLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'">
            <div class="absolute inset-0 opacity-10" style="background: radial-gradient(circle at 70% 50%, #0d9488 0%, transparent 60%)"></div>
            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm mb-3">
                            <span class="w-2 h-2 rounded-full bg-[#0d9488] animate-pulse"></span>
                            <span class="text-xs font-semibold text-gray-300">{{ isRtl ? 'المالية' : 'Finance' }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'المدفوعات' : 'Payments' }}</h1>
                        <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'عرض جميع المعاملات المالية' : 'View all payment transactions' }}</p>
                    </div>
                </div>

                <!-- Filters in Hero -->
                <div class="flex flex-wrap items-center gap-3 mt-6">
                    <div class="relative flex-1 min-w-[220px] max-w-sm">
                        <svg class="absolute left-3 rtl:left-auto rtl:right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث برقم المرجع، المريض، الفاتورة...' : 'Search by reference, patient, invoice...'"
                            class="doctorato-input w-full ltr:pl-10 ltr:pr-4 rtl:pr-10 rtl:pl-4 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] transition"
                        />
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input v-model="dateFrom" type="date" :max="dateTo || undefined" class="doctorato-input px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [color-scheme:dark]" />
                        <span class="text-gray-400 text-xs">{{ isRtl ? 'إلى' : 'to' }}</span>
                        <input v-model="dateTo" type="date" :min="dateFrom || undefined" class="doctorato-input px-3 py-2.5 bg-white/10 border border-white/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#0d9488]/50 focus:border-[#0d9488] [color-scheme:dark]" />
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-6">
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'عدد المدفوعات' : 'Total Payments' }}</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ payments?.total || 0 }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'إجمالي الصفحة' : 'Page Total' }}</p>
                        <p class="text-2xl font-bold text-emerald-400 mt-1">{{ formatCurrency(totalAmount) }}</p>
                    </div>
                    <div class="bg-white/5 backdrop-blur-sm rounded-xl p-3.5 border border-white/10 hidden sm:block">
                        <p class="text-xs text-gray-400 font-medium">{{ isRtl ? 'في هذه الصفحة' : 'On This Page' }}</p>
                        <p class="text-2xl font-bold text-[#0d9488] mt-1">{{ paymentCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ PAYMENT CARDS ═══ -->
        <div class="space-y-3 transition-all duration-500" :class="cardsLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div
                v-for="payment in payments.data"
                :key="payment.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 hover:shadow-md transition-all duration-300 overflow-hidden"
            >
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 sm:p-5">
                    <!-- Amount Icon -->
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900 text-lg">{{ formatCurrency(payment.amount) }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500">{{ formatDate(payment.payment_date) }}</span>
                                <span v-if="payment.reference_number" class="text-xs font-mono text-[#0d9488] font-semibold">{{ payment.reference_number }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="flex items-center gap-4 sm:gap-6 flex-wrap">
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5 max-w-[120px] truncate">{{ payment.invoice?.patient?.full_name || '-' }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</p>
                            <Link v-if="payment.invoice" :href="`/secretary/invoices/${payment.invoice.id}`" class="text-xs font-mono font-semibold text-[#0d9488] hover:text-[#0b8278] mt-0.5 block">
                                {{ payment.invoice.invoice_number }}
                            </Link>
                            <span v-else class="text-xs text-gray-400 mt-0.5 block">-</span>
                        </div>
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'الطريقة' : 'Method' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5 capitalize">{{ getMethodLabel(payment.payment_method) }}</p>
                        </div>
                        <div class="text-center hidden sm:block">
                            <p class="text-[10px] text-gray-400 font-semibold uppercase">{{ isRtl ? 'المستلم' : 'Received By' }}</p>
                            <p class="text-xs font-semibold text-gray-700 mt-0.5 max-w-[100px] truncate">{{ payment.receiver?.name || '-' }}</p>
                        </div>
                    </div>

                    <!-- Action -->
                    <Link
                        v-if="payment.invoice"
                        :href="`/secretary/invoices/${payment.invoice.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-[#0d9488] bg-[#0d9488]/5 hover:bg-[#0d9488]/10 rounded-xl transition-colors flex-shrink-0"
                    >
                        {{ isRtl ? 'عرض الفاتورة' : 'View Invoice' }}
                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!payments.data || payments.data.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <p class="text-sm font-semibold text-gray-500">{{ isRtl ? 'لا توجد مدفوعات' : 'No payments found' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'جرب تغيير البحث أو التاريخ' : 'Try adjusting your search or date range' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="payments.links?.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-6">
            <p class="text-xs text-gray-500">
                {{ isRtl ? 'عرض' : 'Showing' }} <span class="font-semibold">{{ payments.from }}</span> {{ isRtl ? 'إلى' : 'to' }} <span class="font-semibold">{{ payments.to }}</span> {{ isRtl ? 'من' : 'of' }} <span class="font-semibold">{{ payments.total }}</span> {{ isRtl ? 'نتيجة' : 'results' }}
            </p>
            <nav class="flex items-center gap-1">
                <template v-for="link in payments.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-[#0d9488] text-white shadow-sm' : 'text-gray-500 hover:bg-white hover:shadow-sm'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </nav>
        </div>
    </div>
</template>
