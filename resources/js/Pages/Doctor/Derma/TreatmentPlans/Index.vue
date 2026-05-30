<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#8B5CF6';

defineProps({
    plans: { type: Object, default: () => ({ data: [], links: [] }) },
});

function pct(p) {
    const est = Number(p.estimated_sessions) || 0;
    if (!est) return 0;
    return Math.min(Math.round(((Number(p.completed_sessions) || 0) / est) * 100), 100);
}
</script>

<template>
    <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center gap-3">
            <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
            <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'خطط العلاج' : 'Treatment Plans' }}</h1>
        </div>

        <div v-if="plans.data.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="pl in plans.data" :key="pl.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-800">{{ isRtl ? pl.title_ar : pl.title_en }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ pl.patient?.full_name }} · {{ pl.patient?.phone }}</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">{{ pl.session_type }}</span>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ isRtl ? 'التقدّم' : 'Progress' }}</span>
                        <span>{{ pl.completed_sessions ?? 0 }}/{{ pl.estimated_sessions ?? '—' }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700" :style="{ width: pct(pl) + '%', background: ACCENT }"></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 py-16 text-center text-gray-400">
            {{ isRtl ? 'لا توجد خطط علاج' : 'No treatment plans' }}
        </div>

        <div v-if="plans.links && plans.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <template v-for="(l, i) in plans.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label" preserve-scroll
                      class="px-3 py-1.5 rounded-lg text-sm" :class="l.active ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" />
                <span v-else v-html="l.label" class="px-3 py-1.5 rounded-lg text-sm text-gray-300"></span>
            </template>
        </div>
    </div>
</template>
