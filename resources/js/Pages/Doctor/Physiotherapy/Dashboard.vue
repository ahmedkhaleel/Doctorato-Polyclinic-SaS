<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCountUp } from '@/Composables/useCountUp';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentSessions: { type: Array, default: () => [] },
    activePlans: { type: Array, default: () => [] },
});

const ACCENT = '#0D9488'; // physiotherapy teal

const { values: counters } = useCountUp({
    active_plans: props.stats.active_plans || 0,
    sessions_this_month: props.stats.sessions_this_month || 0,
    assessments_this_month: props.stats.assessments_this_month || 0,
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

const cards = computed(() => [
    { key: 'active_plans', label: isRtl.value ? 'خطط علاجية نشطة' : 'Active Plans', color: ACCENT, icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 4.5a48.424 48.424 0 00-7.5 0M12 2.25h.008v.008H12V2.25z' },
    { key: 'sessions_this_month', label: isRtl.value ? 'جلسات هذا الشهر' : 'Sessions This Month', color: '#1B365D', icon: 'M3 12h4l3 8 4-16 3 8h4' },
    { key: 'assessments_this_month', label: isRtl.value ? 'تقييمات هذا الشهر' : 'Assessments This Month', color: '#C4A265', icon: 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-1.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.75' },
]);

function painDelta(s) {
    if (s.pain_before == null || s.pain_after == null) return null;
    return Number(s.pain_before) - Number(s.pain_after);
}
function dateLabel(d) {
    return d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short' }) : '';
}
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg"
             style="background: linear-gradient(120deg,#1B365D 0%,#155e63 55%,#0D9488 160%)">
            <div class="absolute -top-10 end-0 w-48 h-48 rounded-full opacity-20" style="background:#C4A265"></div>
            <div class="absolute -bottom-12 end-24 w-32 h-32 rounded-full opacity-10 bg-white"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }}</h1>
                    <p class="text-white/80 mt-2">{{ isRtl ? 'قسم العلاج الطبيعي والتأهيل' : 'Physiotherapy & Rehabilitation' }}</p>
                </div>
                <Link :href="route('doctor.physiotherapy.patients.index')"
                      class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    {{ isRtl ? 'قائمة المرضى' : 'Patient List' }}
                </Link>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="c in cards" :key="c.key" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-xl" :style="{ backgroundColor: c.color + '1A', color: c.color }">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" :d="c.icon"/></svg>
                </span>
                <div>
                    <p class="text-2xl font-bold text-gray-800 tabular-nums">{{ counters[c.key] }}</p>
                    <p class="text-sm text-gray-500">{{ c.label }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Active plans -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ isRtl ? 'الخطط العلاجية النشطة' : 'Active Treatment Plans' }}</h2>
                <p v-if="!activePlans.length" class="text-sm text-gray-400 py-8 text-center">{{ isRtl ? 'لا توجد خطط نشطة' : 'No active plans' }}</p>
                <ul v-else class="space-y-3">
                    <li v-for="p in activePlans" :key="p.id" class="flex items-center gap-3">
                        <Link :href="route('doctor.physiotherapy.patients.show', p.patient_id)" class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ p.patient?.full_name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ isRtl ? (p.title_ar || p.title_en) : (p.title_en || p.title_ar) }}</p>
                        </Link>
                        <div class="w-28">
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full transition-all" :style="{ width: p.progress_percentage + '%', backgroundColor: ACCENT }"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1 text-center">{{ p.completed_sessions }}/{{ p.estimated_sessions }}</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Recent sessions -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-4">{{ isRtl ? 'أحدث الجلسات' : 'Recent Sessions' }}</h2>
                <p v-if="!recentSessions.length" class="text-sm text-gray-400 py-8 text-center">{{ isRtl ? 'لا توجد جلسات' : 'No sessions yet' }}</p>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="s in recentSessions" :key="s.id" class="border-b border-gray-50 last:border-0">
                            <td class="py-2">
                                <Link :href="route('doctor.physiotherapy.patients.show', s.patient_id)" class="font-medium text-gray-700 hover:text-teal-600">{{ s.patient?.full_name }}</Link>
                            </td>
                            <td class="py-2 text-gray-400 text-xs">{{ dateLabel(s.session_date) }}</td>
                            <td class="py-2 text-end">
                                <span v-if="painDelta(s) != null" class="inline-flex items-center gap-1 text-xs font-medium"
                                      :class="painDelta(s) > 0 ? 'text-emerald-600' : painDelta(s) < 0 ? 'text-red-500' : 'text-gray-400'">
                                    {{ isRtl ? 'الألم' : 'Pain' }} {{ s.pain_before }}→{{ s.pain_after }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
