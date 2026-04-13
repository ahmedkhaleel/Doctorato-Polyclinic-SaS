<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plans: Object,
    filters: Object,
});

const mounted = ref(false);
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let searchTimeout = null;

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/doctor/dental/treatment-plans', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch([search, statusFilter, dateFrom, dateTo], applyFilters);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-[#C4A265]/10 text-[#C4A265]',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const priorityColors = {
    low: 'bg-gray-100 text-gray-600',
    normal: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
};
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'طب الأسنان' : 'Dental' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'إدارة خطط علاج مرضاك' : 'Manage your patients treatment plans' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ plans.total || 0 }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الخطط' : 'Total Plans' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters in Hero -->
                <div class="flex flex-col sm:flex-row gap-3 max-w-2xl"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <div class="relative flex-1">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث بالاسم، العنوان، الملاحظات...' : 'Search name, title, notes...'"
                            class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                    >
                        <option value="" class="text-gray-900">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                        <option value="draft" class="text-gray-900">{{ isRtl ? 'مسودة' : 'Draft' }}</option>
                        <option value="pending" class="text-gray-900">{{ isRtl ? 'قيد الانتظار' : 'Pending' }}</option>
                        <option value="approved" class="text-gray-900">{{ isRtl ? 'معتمد' : 'Approved' }}</option>
                        <option value="in_progress" class="text-gray-900">{{ isRtl ? 'قيد التنفيذ' : 'In Progress' }}</option>
                        <option value="completed" class="text-gray-900">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                        <option value="cancelled" class="text-gray-900">{{ isRtl ? 'ملغي' : 'Cancelled' }}</option>
                    </select>
                </div>
                <!-- Date Range Filters -->
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 max-w-2xl mt-3"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ isRtl ? 'من' : 'From' }}</span>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ isRtl ? 'إلى' : 'To' }}</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="px-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 transition-all"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'العنوان' : 'Title' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'الأولوية' : 'Priority' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">{{ isRtl ? 'التكلفة المتوقعة' : 'Est. Cost' }}</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">{{ isRtl ? 'العلاجات' : 'Treatments' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">{{ isRtl ? 'تاريخ البدء' : 'Start Date' }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">{{ isRtl ? 'تاريخ الانتهاء' : 'End Date' }}</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-semibold text-gray-800">{{ plan.patient?.full_name || '-' }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ plan.patient?.file_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <Link :href="`/doctor/dental/treatment-plans/${plan.id}`" class="hover:text-[#C4A265] transition-colors">
                                    {{ locale === 'ar' ? (plan.title_ar || plan.title_en) : (plan.title_en || plan.title_ar) }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[statusColors[plan.status] || 'bg-gray-100 text-gray-800', 'px-2.5 py-1 rounded-full text-xs font-medium']">
                                    {{ isRtl ? {
                                        draft: 'مسودة', pending: 'قيد الانتظار', approved: 'معتمد',
                                        in_progress: 'قيد التنفيذ', completed: 'مكتمل', cancelled: 'ملغي'
                                    }[plan.status] || plan.status : {
                                        draft: 'Draft', pending: 'Pending', approved: 'Approved',
                                        in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled'
                                    }[plan.status] || plan.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center hidden sm:table-cell">
                                <span :class="[priorityColors[plan.priority] || 'bg-gray-100 text-gray-600', 'px-2.5 py-1 rounded-full text-xs font-medium']">
                                    {{ isRtl ? {
                                        low: 'منخفض', normal: 'عادي', high: 'مرتفع', urgent: 'عاجل'
                                    }[plan.priority] || 'عادي' : {
                                        low: 'Low', normal: 'Normal', high: 'High', urgent: 'Urgent'
                                    }[plan.priority] || 'Normal' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 font-medium hidden sm:table-cell">{{ formatCurrency(plan.estimated_cost) }}</td>
                            <td class="px-4 py-3 text-center hidden md:table-cell">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold">
                                    {{ plan.treatments_count ?? (plan.treatments?.length || 0) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ formatDate(plan.start_date) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 hidden lg:table-cell">{{ formatDate(plan.expected_end_date) }}</td>
                            <td class="px-4 py-3 text-end">
                                <Link :href="`/doctor/dental/treatment-plans/${plan.id}`"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-[#C4A265] bg-[#C4A265]/5 hover:bg-[#C4A265]/10 border border-[#C4A265]/10 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ isRtl ? 'عرض' : 'View' }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!plans.data || plans.data.length === 0">
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد خطط علاج' : 'No treatment plans found' }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="plans.links && plans.links.length > 3" class="flex items-center justify-center gap-1 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex-wrap">
                <template v-for="link in plans.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
