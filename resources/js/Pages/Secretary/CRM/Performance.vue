<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    monthlyStats: Object,
    weeklyStats: Object,
    dailyActivity: Array,
    statusDistribution: Object,
    conversionRate: Number,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const maxDailyCount = computed(() => {
    return Math.max(...(props.dailyActivity || []).map(d => d.count), 1);
});

const statusLabels = {
    new: { en: 'New', ar: 'جديد', color: 'bg-blue-500' },
    contacted: { en: 'Contacted', ar: 'تم التواصل', color: 'bg-cyan-500' },
    qualified: { en: 'Qualified', ar: 'مؤهل', color: 'bg-teal-500' },
    appointment_booked: { en: 'Booked', ar: 'تم الحجز', color: 'bg-emerald-500' },
    consultation_done: { en: 'Consulted', ar: 'تم الاستشارة', color: 'bg-green-500' },
    negotiation: { en: 'Negotiation', ar: 'تفاوض', color: 'bg-amber-500' },
};

const totalLeads = computed(() => {
    return Object.values(props.statusDistribution || {}).reduce((a, b) => a + b, 0);
});

function animatedCounter(val) {
    return val || 0;
}
</script>

<template>
<SecretaryLayout :title="isRtl ? 'تقارير الأداء' : 'Performance'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'تقارير الأداء' : 'My Performance' }}</h1>
                <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'متابعة إنتاجيتك ونتائج عملك' : 'Track your productivity and results' }}</p>
            </div>
        </div>
    </div>

    <!-- Conversion Rate + Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Conversion Rate -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
            <div class="relative w-20 h-20 mx-auto mb-3">
                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#0d9488" stroke-width="8" stroke-linecap="round"
                            :stroke-dasharray="((conversionRate / 100) * 264) + ' 264'"
                            class="transition-all duration-1000 ease-out"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-teal-700">{{ conversionRate }}%</span>
            </div>
            <p class="text-xs font-medium text-slate-500">{{ isRtl ? 'نسبة التحويل' : 'Conversion Rate' }}</p>
        </div>

        <!-- Converted -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '150ms' }">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.leads_converted || 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ isRtl ? 'تم تحويلهم هذا الشهر' : 'Converted this month' }}</p>
        </div>

        <!-- Total Activities -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
            <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.total_activities || 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ isRtl ? 'نشاط هذا الشهر' : 'Activities this month' }}</p>
        </div>

        <!-- Leads Created -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '250ms' }">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ monthlyStats?.leads_created || 0 }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ isRtl ? 'عملاء جدد هذا الشهر' : 'Leads added this month' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Activity Chart (7 days) -->
        <div :class="['lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                {{ isRtl ? 'النشاط اليومي (آخر 7 أيام)' : 'Daily Activity (Last 7 Days)' }}
            </h3>
            <div class="flex items-end gap-3 h-40">
                <div v-for="day in dailyActivity" :key="day.date" class="flex-1 flex flex-col items-center gap-2">
                    <span class="text-xs font-semibold text-teal-700">{{ day.count }}</span>
                    <div class="w-full bg-slate-100 rounded-t-lg relative overflow-hidden" style="min-height: 4px"
                         :style="{ height: Math.max((day.count / maxDailyCount) * 100, 4) + '%' }">
                        <div class="absolute inset-0 bg-gradient-to-t from-teal-600 to-teal-400 rounded-t-lg transition-all duration-1000"
                             :style="{ height: mounted ? '100%' : '0%' }"></div>
                    </div>
                    <span class="text-xs text-slate-500">{{ isRtl ? day.label_ar : day.label }}</span>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '350ms' }">
            <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                {{ isRtl ? 'توزيع الحالات' : 'Status Distribution' }}
            </h3>
            <div class="space-y-3">
                <div v-for="(count, status) in statusDistribution" :key="status" class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full" :class="statusLabels[status]?.color || 'bg-gray-400'"></div>
                    <span class="text-sm text-slate-600 flex-1">{{ isRtl ? (statusLabels[status]?.ar || status) : (statusLabels[status]?.en || status) }}</span>
                    <span class="text-sm font-semibold text-slate-800">{{ count }}</span>
                    <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-1000" :class="statusLabels[status]?.color || 'bg-gray-400'"
                             :style="{ width: mounted ? ((count / (totalLeads || 1)) * 100) + '%' : '0%' }"></div>
                    </div>
                </div>
                <div v-if="Object.keys(statusDistribution || {}).length === 0" class="text-center py-4 text-sm text-slate-400">
                    {{ isRtl ? 'لا توجد بيانات' : 'No data available' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Communication Breakdown -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
        <h3 class="text-sm font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            {{ isRtl ? 'ملخص التواصل هذا الشهر' : 'Communication Summary This Month' }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div v-for="item in [
                { key: 'calls', label: { en: 'Calls', ar: 'مكالمات' }, color: 'text-green-600 bg-green-100', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
                { key: 'whatsapp', label: { en: 'WhatsApp', ar: 'واتساب' }, color: 'text-emerald-600 bg-emerald-100', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
                { key: 'emails', label: { en: 'Emails', ar: 'بريد' }, color: 'text-blue-600 bg-blue-100', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
                { key: 'meetings', label: { en: 'Meetings', ar: 'اجتماعات' }, color: 'text-amber-600 bg-amber-100', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
                { key: 'follow_ups_completed', label: { en: 'Follow-ups Done', ar: 'متابعات مكتملة' }, color: 'text-teal-600 bg-teal-100', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            ]" :key="item.key"
                class="bg-slate-50 rounded-xl p-4 text-center border border-slate-100">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-2" :class="item.color">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon"/></svg>
                </div>
                <p class="text-xl font-bold text-slate-800">{{ monthlyStats?.[item.key] || 0 }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ isRtl ? item.label.ar : item.label.en }}</p>
            </div>
        </div>
    </div>

    <!-- Follow-up Performance -->
    <div :class="['grid grid-cols-1 md:grid-cols-3 gap-4 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '450ms' }">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-3xl font-bold text-slate-800">{{ monthlyStats?.follow_ups_completed || 0 }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ isRtl ? 'متابعات مكتملة' : 'Follow-ups Completed' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <p class="text-3xl font-bold text-slate-800">{{ monthlyStats?.follow_ups_missed || 0 }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ isRtl ? 'متابعات فائتة' : 'Follow-ups Missed' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <p class="text-3xl font-bold text-slate-800">{{ monthlyStats?.leads_lost || 0 }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ isRtl ? 'عملاء خسارة' : 'Leads Lost' }}</p>
        </div>
    </div>

</div>
</SecretaryLayout>
</template>
