<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    plans: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const ACCENT = '#0D9488';
const tabs = computed(() => [
    { key: 'active', label: isRtl.value ? 'نشطة' : 'Active' },
    { key: 'completed', label: isRtl.value ? 'مكتملة' : 'Completed' },
    { key: 'on_hold', label: isRtl.value ? 'معلّقة' : 'On Hold' },
    { key: 'all', label: isRtl.value ? 'الكل' : 'All' },
]);
function setTab(k) {
    router.get(route('doctor.physiotherapy.treatment-plans.index'), { status: k }, { preserveState: true, replace: true });
}
const statusColor = (s) => ({ in_progress: '#0D9488', planned: '#6366F1', on_hold: '#D97706', completed: '#059669', cancelled: '#9CA3AF' }[s] || '#6B7280');
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'الخطط العلاجية' : 'Treatment Plans' }}</h1>

        <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
            <button v-for="t in tabs" :key="t.key" @click="setTab(t.key)"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition"
                    :class="(filters.status || 'active') === t.key ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'">
                {{ t.label }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="p in plans.data" :key="p.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <Link :href="route('doctor.physiotherapy.patients.show', p.patient_id)" class="min-w-0">
                        <p class="font-semibold text-gray-800 truncate">{{ p.patient?.full_name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ isRtl ? (p.title_ar || p.title_en) : (p.title_en || p.title_ar) }}</p>
                    </Link>
                    <span class="text-[11px] font-semibold px-2 py-1 rounded-full whitespace-nowrap"
                          :style="{ backgroundColor: statusColor(p.status) + '1A', color: statusColor(p.status) }">{{ p.status }}</span>
                </div>
                <div class="mt-4">
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all" :style="{ width: p.progress_percentage + '%', backgroundColor: ACCENT }"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-1.5">
                        <span>{{ p.completed_sessions }}/{{ p.estimated_sessions }} {{ isRtl ? 'جلسة' : 'sessions' }}</span>
                        <span>{{ p.progress_percentage }}%</span>
                    </div>
                </div>
            </div>
            <div v-if="!plans.data.length" class="col-span-full bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-100">
                {{ isRtl ? 'لا توجد خطط' : 'No plans found' }}
            </div>
        </div>

        <div v-if="plans.links && plans.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <Link v-for="(l, i) in plans.links" :key="i" :href="l.url || ''" v-html="l.label"
                  class="px-3 py-1.5 rounded-lg text-sm" :class="[l.active ? 'text-white' : 'text-gray-600 hover:bg-gray-100', !l.url && 'opacity-40 pointer-events-none']"
                  :style="l.active ? { backgroundColor: ACCENT } : {}" />
        </div>
    </div>
</template>
