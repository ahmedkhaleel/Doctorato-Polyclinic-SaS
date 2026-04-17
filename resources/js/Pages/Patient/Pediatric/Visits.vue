<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    visits: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusColors = {
    completed: 'bg-emerald-50 text-emerald-700',
    waiting: 'bg-amber-50 text-amber-700',
    in_progress: 'bg-slate-50 text-[#1B365D]',
    cancelled: 'bg-red-50 text-red-700',
};

const statusLabels = {
    completed: { ar: 'مكتمل', en: 'Completed' },
    waiting: { ar: 'انتظار', en: 'Waiting' },
    in_progress: { ar: 'جاري', en: 'In Progress' },
    cancelled: { ar: 'ملغي', en: 'Cancelled' },
};
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link href="/patient/pediatric/overview" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ isRtl ? 'زيارات طب الأطفال' : 'Pediatric Visits' }}</h1>
                <p class="text-sm text-gray-500">{{ isRtl ? 'سجل الزيارات الخاص بطفلك' : 'Your child\'s visit history' }}</p>
            </div>
        </div>

        <!-- Visits List -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-50">
                <div v-for="v in visits?.data" :key="v.id" class="px-5 py-4 flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">{{ isRtl ? v.doctor?.name_ar : v.doctor?.name_en }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ formatDate(v.visit_date) }}
                            <span v-if="v.service" class="mx-1">&middot;</span>
                            <span v-if="v.service">{{ isRtl ? v.service.name_ar : v.service.name_en }}</span>
                        </p>
                        <p v-if="v.diagnosis" class="text-xs text-gray-500 mt-1 line-clamp-1">{{ v.diagnosis }}</p>
                    </div>
                    <span class="text-[11px] font-medium px-2.5 py-1 rounded-full" :class="statusColors[v.status] || 'bg-gray-50 text-gray-500'">
                        {{ isRtl ? statusLabels[v.status]?.ar : statusLabels[v.status]?.en }}
                    </span>
                </div>
            </div>
            <div v-if="!visits?.data?.length" class="py-16 text-center">
                <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد زيارات مسجلة' : 'No visits recorded' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="visits?.links?.length > 3" class="flex justify-center gap-1">
            <template v-for="link in visits.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1.5 text-xs rounded-lg transition-colors"
                    :class="link.active ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
