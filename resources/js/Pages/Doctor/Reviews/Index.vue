<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const props = defineProps({
    stats:   Object,
    reviews: Object, // paginator
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const recommendRate = computed(() => {
    const total = (props.stats?.recommend_yes || 0) + (props.stats?.recommend_no || 0);
    if (!total) return null;
    return Math.round((props.stats.recommend_yes / total) * 100);
});

const distroBars = computed(() => {
    const total = props.stats?.total || 0;
    return [5, 4, 3, 2, 1].map(stars => {
        const cnt = props.stats?.distribution?.[stars] || 0;
        return {
            stars,
            count: cnt,
            pct: total ? Math.round((cnt / total) * 100) : 0,
        };
    });
});

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return d; }
}

const trendDelta = computed(() => {
    if (!props.stats?.last_30d_avg || !props.stats?.avg_overall) return null;
    return Math.round((props.stats.last_30d_avg - props.stats.avg_overall) * 100) / 100;
});
</script>

<template>
    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-[#1B365D]">
                {{ isRtl ? 'تقييمات المرضى' : 'Patient Reviews' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl
                    ? 'كل التقييمات التي تركها المرضى عن زياراتهم لديك'
                    : 'All ratings patients have left after their visits with you' }}
            </p>
        </div>

        <div v-if="!stats?.total" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="text-5xl mb-3">⭐</div>
            <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد تقييمات بعد' : 'No reviews yet' }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ isRtl ? 'ستظهر هنا عندما يقيّم المرضى زياراتهم' : 'Reviews will appear here once patients rate their visits' }}
            </p>
        </div>

        <div v-else class="space-y-5">
            <!-- Top summary -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Big avg + stars -->
                <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] rounded-2xl p-6 text-white relative overflow-hidden">
                    <div class="absolute -top-12 -end-12 w-44 h-44 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
                    <div class="relative">
                        <p class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase mb-2">
                            {{ isRtl ? 'المتوسط العام' : 'Overall rating' }}
                        </p>
                        <div class="flex items-end gap-3">
                            <p class="text-5xl font-extrabold tabular-nums">{{ stats.avg_overall?.toFixed(1) ?? '—' }}</p>
                            <p class="text-xl font-light text-white/60 mb-1">/ 5</p>
                        </div>
                        <div class="flex items-center gap-0.5 mt-2">
                            <svg v-for="i in 5" :key="i" class="w-5 h-5"
                                 :class="i <= Math.round(stats.avg_overall) ? 'text-[#C4A265]' : 'text-white/20'"
                                 fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-white/60 mt-2">
                            {{ isRtl ? 'من ' : 'Based on ' }}{{ stats.total.toLocaleString() }}{{ isRtl ? ' تقييم' : ' reviews' }}
                        </p>
                    </div>
                </div>

                <!-- Last 30 days trend -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6">
                    <p class="text-[10px] font-bold text-gray-500 tracking-[0.2em] uppercase mb-2">
                        {{ isRtl ? 'آخر 30 يوم' : 'Last 30 days' }}
                    </p>
                    <p class="text-3xl font-extrabold text-gray-800 tabular-nums">
                        {{ stats.last_30d_avg?.toFixed(1) ?? '—' }}
                        <span class="text-base font-light text-gray-400">/ 5</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ stats.last_30d_count }} {{ isRtl ? 'تقييم' : 'reviews' }}</p>
                    <div v-if="trendDelta !== null && stats.last_30d_count" class="mt-3 inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-bold"
                         :class="trendDelta >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
                        <span>{{ trendDelta >= 0 ? '↑' : '↓' }}</span>
                        <span>{{ Math.abs(trendDelta).toFixed(2) }} {{ isRtl ? 'مقابل المتوسط' : 'vs all-time' }}</span>
                    </div>
                </div>

                <!-- Recommendation rate -->
                <div class="bg-white rounded-2xl border border-emerald-100 p-6">
                    <p class="text-[10px] font-bold text-gray-500 tracking-[0.2em] uppercase mb-2">
                        {{ isRtl ? 'نسبة التوصية' : 'Would recommend' }}
                    </p>
                    <p v-if="recommendRate !== null" class="text-3xl font-extrabold text-emerald-600 tabular-nums">{{ recommendRate }}%</p>
                    <p v-else class="text-3xl font-extrabold text-gray-300">—</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ stats.recommend_yes }} {{ isRtl ? 'نعم' : 'yes' }}
                        · {{ stats.recommend_no }} {{ isRtl ? 'لا' : 'no' }}
                    </p>
                    <p v-if="stats.avg_nps !== null" class="text-[11px] text-gray-500 mt-3 pt-3 border-t border-gray-100">
                        <span class="font-semibold">NPS:</span> {{ stats.avg_nps?.toFixed(1) }} / 10
                    </p>
                </div>
            </div>

            <!-- Star distribution + sub-ratings -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Star distribution -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'توزيع التقييمات' : 'Rating distribution' }}</h2>
                    <div class="space-y-2">
                        <div v-for="row in distroBars" :key="row.stars" class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-gray-600 w-10 flex items-center gap-0.5">
                                {{ row.stars }}
                                <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </span>
                            <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full" :style="{ width: row.pct + '%' }"></div>
                            </div>
                            <span class="text-xs text-gray-500 tabular-nums w-12 text-end">{{ row.count }} ({{ row.pct }}%)</span>
                        </div>
                    </div>
                </div>

                <!-- Sub-ratings -->
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <h2 class="text-sm font-bold text-gray-800 mb-4">{{ isRtl ? 'متوسط التقييمات حسب الفئة' : 'Average by category' }}</h2>
                    <div class="space-y-3 text-sm">
                        <div v-for="row in [
                            { label: isRtl ? 'الطبيب' : 'Doctor',         val: stats.avg_doctor },
                            { label: isRtl ? 'التواصل' : 'Communication',  val: stats.avg_communication },
                            { label: isRtl ? 'وقت الانتظار' : 'Waiting',  val: stats.avg_waiting },
                            { label: isRtl ? 'الموظفون' : 'Staff',         val: stats.avg_staff },
                            { label: isRtl ? 'النظافة' : 'Cleanliness',    val: stats.avg_cleanliness },
                        ]" :key="row.label" class="flex items-center justify-between gap-3">
                            <span class="text-gray-700">{{ row.label }}</span>
                            <span v-if="row.val !== null" class="font-bold text-[#1B365D] tabular-nums">{{ row.val.toFixed(2) }} / 5</span>
                            <span v-else class="text-xs text-gray-300">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews list -->
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'كل التقييمات' : 'All reviews' }}</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    <div v-for="r in reviews.data" :key="r.id" class="p-5">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">
                                    {{ r.is_anonymous || !r.patient_name ? (isRtl ? 'مريض مجهول الهوية' : 'Anonymous patient') : r.patient_name }}
                                </p>
                                <p class="text-[11px] text-gray-500 mt-0.5">
                                    {{ isRtl ? 'الزيارة:' : 'Visit:' }} {{ fmtDate(r.visit_date) }}
                                    <span class="ms-2">· {{ isRtl ? 'قُدّم في' : 'Submitted' }} {{ fmtDate(r.created_at) }}</span>
                                    <span v-if="r.source === 'patient_portal'" class="ms-2 px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold uppercase">
                                        {{ isRtl ? 'البورتال' : 'Portal' }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex items-center gap-0.5 flex-shrink-0">
                                <svg v-for="i in 5" :key="i" class="w-4 h-4"
                                     :class="i <= r.overall_rating ? 'text-amber-400' : 'text-gray-200'"
                                     fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                        </div>
                        <p v-if="r.comments" class="text-sm text-gray-700 leading-relaxed bg-gray-50 rounded-lg p-3 mt-2">{{ r.comments }}</p>
                        <div v-if="r.would_recommend !== null || r.nps_score !== null" class="flex items-center gap-3 mt-3 text-[11px] text-gray-500">
                            <span v-if="r.would_recommend === true" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold">👍 {{ isRtl ? 'يوصي' : 'Recommends' }}</span>
                            <span v-if="r.would_recommend === false" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-semibold">👎 {{ isRtl ? 'لا يوصي' : "Doesn't recommend" }}</span>
                            <span v-if="r.nps_score !== null" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 font-semibold">NPS: {{ r.nps_score }}/10</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="reviews.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                    <Link v-for="link in reviews.links" :key="link.label"
                          :href="link.url || '#'"
                          v-html="link.label"
                          :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium border',
                            link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                        : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                                   : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                          ]" />
                </div>
            </div>
        </div>
    </div>
</template>
