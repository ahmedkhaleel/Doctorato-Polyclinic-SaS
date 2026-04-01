<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    return Object.entries(modules.value)
        .filter(([, m]) => m.is_enabled !== false)
        .map(([slug, m]) => ({ slug, name: isRtl.value ? m.name_ar : m.name_en }));
});

const props = defineProps({
    visits: Object,
    filters: Object,
});

const mounted = ref(false);
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const moduleFilter = ref(props.filters?.module || '');
let debounce = null;

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 300);
});

watch(status, () => applyFilters());
watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get('/doctor/visits', {
        search: search.value || undefined,
        status: status.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const statusConfig = {
    waiting: { label: 'Waiting', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-400' },
    in_progress: { label: 'In Progress', bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
    completed: { label: 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { label: 'Cancelled', bg: 'bg-gray-50', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-400' },
};

const statusTabs = computed(() => [
    { key: '', label: 'All', active: status.value === '' },
    { key: 'waiting', label: 'Waiting', active: status.value === 'waiting' },
    { key: 'in_progress', label: 'In Progress', active: status.value === 'in_progress' },
    { key: 'completed', label: 'Completed', active: status.value === 'completed' },
    { key: 'cancelled', label: 'Cancelled', active: status.value === 'cancelled' },
]);

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    const today = new Date();
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'سجل الزيارات' : 'Visit History' }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ $t('a_visits') }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'جميع الزيارات المسندة إليك' : 'All visits assigned to you' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ visits.total || 0 }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search + Status Tabs in Hero -->
                <div class="space-y-3">
                    <div class="relative max-w-lg"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.1s"
                    >
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="isRtl ? 'بحث باسم المريض...' : 'Search patient name...'"
                            class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                        />
                    </div>
                    <select v-if="activeModules.length > 1" v-model="moduleFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                        <option value="">{{ isRtl ? 'كل الأقسام' : 'All Departments' }}</option>
                        <option v-for="mod in activeModules" :key="mod.slug" :value="mod.slug">{{ mod.name }}</option>
                    </select>

                    <!-- Status Tabs -->
                    <div class="flex items-center gap-1 overflow-x-auto scrollbar-none"
                        :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                    >
                        <button v-for="tab in statusTabs" :key="tab.key"
                            @click="status = tab.key"
                            class="px-4 py-1.5 text-xs font-medium rounded-lg transition-all duration-200 whitespace-nowrap"
                            :class="tab.active
                                ? 'bg-white/15 text-white border border-white/20'
                                : 'text-gray-400 hover:text-gray-300 hover:bg-white/5 border border-transparent'"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visits List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div v-if="visits.data?.length > 0" class="divide-y divide-gray-100/80">
                <Link v-for="(visit, index) in visits.data" :key="visit.id"
                    :href="`/doctor/visits/${visit.id}`"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-gray-50/60 transition-all duration-200"
                    :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                    :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.25 + index * 0.03}s` }"
                >
                    <!-- Status Icon -->
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center border flex-shrink-0 transition-transform duration-200 group-hover:scale-105"
                        :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.border]"
                    >
                        <svg v-if="visit.status === 'waiting'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <svg v-else-if="visit.status === 'in_progress'" class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        <svg v-else-if="visit.status === 'completed'" class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </div>

                    <!-- Visit Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ visit.patient?.full_name }}</p>
                            <span v-if="visit.patient?.file_number" class="flex-shrink-0 font-mono text-[10px] text-gray-400">{{ visit.patient.file_number }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-xs text-gray-500">{{ visit.service?.name_en || visit.visit_type }}</span>
                            <span class="text-gray-200">&middot;</span>
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                {{ formatDate(visit.visit_date) }}
                            </span>
                        </div>
                    </div>

                    <!-- Status + Arrow -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border"
                            :class="[statusConfig[visit.status]?.bg, statusConfig[visit.status]?.text, statusConfig[visit.status]?.border]"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusConfig[visit.status]?.dot"></span>
                            {{ statusConfig[visit.status]?.label }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="py-20 text-center">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد زيارات' : 'No visits found' }}</p>
                <p class="text-xs text-gray-400 mt-1.5">
                    <template v-if="search || status">{{ isRtl ? 'جرب تعديل البحث أو الفلاتر' : 'Try adjusting your search or filters' }}</template>
                    <template v-else>{{ isRtl ? 'ستظهر زياراتك هنا بمجرد جدولتها' : 'Your visits will appear here once scheduled' }}</template>
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="visits.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in visits.links" :key="link.label">
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
