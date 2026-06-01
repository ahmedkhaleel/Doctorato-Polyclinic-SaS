<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useCountUp } from '@/Composables/useCountUp';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const { formatCurrency } = useCurrency();
const ACCENT = '#8B5CF6'; // derma violet

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    todayVisits: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
});

const { values: counters, mounted } = useCountUp({
    visits_today: props.stats.visits_today || 0,
    sessions_this_month: props.stats.sessions_this_month || 0,
    active_plans: props.stats.active_plans || 0,
}, 1000);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

const cards = computed(() => [
    { key: 'visits_today', v: counters.value.visits_today, label: isRtl.value ? 'زيارات اليوم' : "Today's Visits", color: ACCENT },
    { key: 'sessions_this_month', v: counters.value.sessions_this_month, label: isRtl.value ? 'جلسات هذا الشهر' : 'Sessions (Month)', color: '#1B365D' },
    { key: 'active_plans', v: counters.value.active_plans, label: isRtl.value ? 'خطط علاج نشطة' : 'Active Plans', color: '#C4A265' },
]);
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg"
             style="background: linear-gradient(120deg,#1B365D 0%,#24456f 55%,#8B5CF6 160%)">
            <div class="absolute -top-10 end-0 w-48 h-48 rounded-full opacity-20" style="background:#C4A265"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }} </h1>
                    <p class="text-white/80 mt-2">{{ isRtl ? 'قسم الجلدية والتجميل' : 'Dermatology & Cosmetic' }}</p>
                </div>
                <Link :href="route('doctor.derma.patients.index')"
                      class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    {{ isRtl ? 'مرضى الجلدية' : 'Derma Patients' }}
                </Link>
            </div>
        </div>

        <!-- Stat cards + revenue -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
            <div v-for="(c, i) in cards" :key="c.key"
                 class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-500"
                 :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" :style="{ transitionDelay: `${i*80}ms` }">
                <span class="absolute top-0 inset-x-0 h-1" :style="{ background: c.color }"></span>
                <p class="text-3xl font-bold text-gray-900 mt-2 tabular-nums">{{ c.v }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
            </div>
            <div class="rounded-2xl p-5 text-white shadow-sm" style="background: linear-gradient(120deg,#1B365D,#8B5CF6 200%)">
                <p class="text-white/70 text-sm">{{ isRtl ? 'إيراد هذا الشهر' : 'Revenue (Month)' }}</p>
                <p class="text-2xl font-bold mt-2">{{ formatCurrency(stats.revenue_this_month || 0) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4"><span class="w-2 h-6 rounded-full" :style="{ background: ACCENT }"></span><h2 class="font-bold text-gray-800">{{ isRtl ? 'زيارات اليوم' : "Today's Visits" }}</h2></div>
                <div v-if="todayVisits.length === 0" class="text-center text-gray-400 py-10 text-sm">{{ isRtl ? 'لا زيارات اليوم' : 'No visits today' }}</div>
                <ul v-else class="space-y-2">
                    <li v-for="v in todayVisits" :key="v.id" class="flex items-center gap-3 py-2 px-2 rounded-lg hover:bg-violet-50/40 transition">
                        <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold text-sm">{{ (v.patient?.full_name || '?').charAt(0) }}</div>
                        <p class="font-medium text-gray-800 text-sm flex-1">{{ v.patient?.full_name }}</p>
                        <span class="text-xs text-gray-400">{{ v.scheduled_time }}</span>
                    </li>
                </ul>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4"><span class="w-2 h-6 rounded-full bg-[#1B365D]"></span><h2 class="font-bold text-gray-800">{{ isRtl ? 'أحدث الجلسات' : 'Recent Sessions' }}</h2></div>
                <div v-if="recentSessions.length === 0" class="text-center text-gray-400 py-10 text-sm">{{ isRtl ? 'لا جلسات بعد' : 'No sessions yet' }}</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="s in recentSessions" :key="s.id">
                        <Link :href="route('doctor.derma.patients.show', s.patient_id)" class="flex items-center justify-between py-2.5 px-2 -mx-2 rounded-lg hover:bg-gray-50 transition">
                            <span class="text-sm font-medium text-gray-800">{{ s.patient?.full_name }}</span>
                            <span class="text-xs text-gray-400">{{ s.session_type }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
