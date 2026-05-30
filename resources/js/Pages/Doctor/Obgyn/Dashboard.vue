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
    upcomingDue: { type: Array, default: () => [] },
    todayVisits: { type: Array, default: () => [] },
});

const ACCENT = '#DB2777'; // OB/GYN rose

const { values: counters, mounted } = useCountUp({
    active_pregnancies: props.stats.active_pregnancies || 0,
    high_risk: props.stats.high_risk || 0,
    anc_this_month: props.stats.anc_this_month || 0,
    deliveries_this_month: props.stats.deliveries_this_month || 0,
});

const greeting = computed(() => {
    const h = new Date().getHours();
    if (isRtl.value) return h < 12 ? 'صباح الخير' : 'مساء الخير';
    return h < 12 ? 'Good Morning' : (h < 17 ? 'Good Afternoon' : 'Good Evening');
});
const today = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));

const cards = computed(() => [
    { key: 'active_pregnancies', label: isRtl.value ? 'حمل نشط' : 'Active Pregnancies', color: ACCENT, icon: 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z' },
    { key: 'high_risk', label: isRtl.value ? 'حمل عالي الخطورة' : 'High-Risk', color: '#EF4444', icon: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z' },
    { key: 'anc_this_month', label: isRtl.value ? 'متابعات هذا الشهر' : 'ANC This Month', color: '#1B365D', icon: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z' },
    { key: 'deliveries_this_month', label: isRtl.value ? 'ولادات هذا الشهر' : 'Deliveries This Month', color: '#C4A265', icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
]);

function trimesterLabel(t) {
    if (isRtl.value) return t === 1 ? 'الثلث الأول' : t === 2 ? 'الثلث الثاني' : 'الثلث الثالث';
    return t === 1 ? '1st trimester' : t === 2 ? '2nd trimester' : '3rd trimester';
}
function dueLabel(days) {
    if (days === null || days === undefined) return '';
    if (days < 0) return isRtl.value ? `متأخّر ${Math.abs(days)} يوم` : `${Math.abs(days)}d overdue`;
    if (days === 0) return isRtl.value ? 'اليوم' : 'Today';
    return isRtl.value ? `خلال ${days} يوم` : `in ${days}d`;
}
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl p-6 md:p-8 text-white shadow-lg"
             style="background: linear-gradient(120deg,#1B365D 0%,#24456f 55%,#DB2777 160%)">
            <div class="absolute -top-10 end-0 w-48 h-48 rounded-full opacity-20" style="background:#C4A265"></div>
            <div class="absolute -bottom-12 end-24 w-32 h-32 rounded-full opacity-10 bg-white"></div>
            <div class="relative z-10 flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/70 text-sm">{{ today }}</p>
                    <h1 class="text-2xl md:text-3xl font-bold mt-1">{{ greeting }} 👋</h1>
                    <p class="text-white/80 mt-2">{{ isRtl ? 'قسم النساء والتوليد' : 'Obstetrics & Gynecology' }}</p>
                </div>
                <Link :href="route('doctor.obgyn.pregnancies.index')"
                      class="inline-flex items-center gap-2 bg-white/15 hover:bg-white/25 backdrop-blur px-5 py-3 rounded-xl font-semibold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ isRtl ? 'ملفات الحمل' : 'Pregnancy Files' }}
                </Link>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="(c, i) in cards" :key="c.key"
                 class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-5 overflow-hidden transition-all duration-500"
                 :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                 :style="{ transitionDelay: `${i * 80}ms` }">
                <span class="absolute top-0 inset-x-0 h-1" :style="{ background: c.color }"></span>
                <div class="flex items-center justify-between">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" :style="{ background: c.color + '1A' }">
                        <svg class="w-6 h-6" :style="{ color: c.color }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="c.icon"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 mt-3 tabular-nums">{{ counters[c.key] }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ c.label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Upcoming due -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-6 rounded-full" :style="{ background: ACCENT }"></span>
                    <h2 class="font-bold text-gray-800">{{ isRtl ? 'قرب موعد الولادة (30 يوم)' : 'Due Within 30 Days' }}</h2>
                </div>
                <div v-if="upcomingDue.length === 0" class="text-center text-gray-400 py-10 text-sm">{{ isRtl ? 'لا توجد حالات قريبة' : 'No upcoming due dates' }}</div>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="p in upcomingDue" :key="p.id">
                        <Link :href="route('doctor.obgyn.pregnancies.show', p.id)" class="flex items-center justify-between py-3 hover:bg-rose-50/40 rounded-lg px-2 -mx-2 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-bold text-sm">{{ (p.patient?.full_name || '?').charAt(0) }}</div>
                                <div>
                                    <p class="font-medium text-gray-800 text-sm">{{ p.patient?.full_name }}</p>
                                    <p class="text-xs text-gray-400">{{ p.ga_label }} · {{ trimesterLabel(p.trimester) }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                                  :class="p.days_until_edd < 0 ? 'bg-red-100 text-red-700' : 'bg-rose-100 text-rose-700'">{{ dueLabel(p.days_until_edd) }}</span>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Today's visits -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-6 rounded-full bg-[#1B365D]"></span>
                    <h2 class="font-bold text-gray-800">{{ isRtl ? 'زيارات اليوم' : "Today's Visits" }}</h2>
                </div>
                <div v-if="todayVisits.length === 0" class="text-center text-gray-400 py-10 text-sm">{{ isRtl ? 'لا توجد زيارات اليوم' : 'No visits today' }}</div>
                <ul v-else class="space-y-2">
                    <li v-for="v in todayVisits" :key="v.id" class="flex items-center gap-3 py-2 px-2 rounded-lg hover:bg-gray-50 transition">
                        <div class="w-9 h-9 rounded-full bg-[#1B365D]/10 flex items-center justify-center text-[#1B365D] font-bold text-sm">{{ (v.patient?.full_name || '?').charAt(0) }}</div>
                        <p class="font-medium text-gray-800 text-sm flex-1">{{ v.patient?.full_name }}</p>
                        <span class="text-xs text-gray-400">{{ v.scheduled_time }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
