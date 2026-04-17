<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    feedbacks: Object,
    stats: Object,
    improvementAreas: Object,
    doctorRankings: Array,
    doctors: Array,
    areaLabels: Object,
    filters: Object,
})

const page = usePage()
const locale = computed(() => page.props.locale || 'ar')
const isRtl = computed(() => locale.value === 'ar')

const doctorFilter = ref(props.filters?.doctor_id || '')
const ratingFilter = ref(props.filters?.rating || '')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

function applyFilters() {
    router.get('/admin/satisfaction', {
        doctor_id: doctorFilter.value || undefined,
        rating: ratingFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveState: true, replace: true })
}

function stars(count) {
    return '★'.repeat(count) + '☆'.repeat(5 - count)
}

function npsColor(score) {
    if (score >= 50) return 'text-emerald-600'
    if (score >= 0) return 'text-amber-600'
    return 'text-red-600'
}
</script>

<template>
    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ isRtl ? 'رضا المرضى' : 'Patient Satisfaction' }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ isRtl ? 'تحليل تقييمات وآراء المرضى' : 'Analyze patient ratings and feedback' }}</p>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي التقييمات' : 'Total Reviews' }}</p>
                <p class="text-xl md:text-2xl font-bold text-gray-800 mt-1">{{ stats.total }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'التقييم العام' : 'Overall Rating' }}</p>
                <p class="text-xl md:text-2xl font-bold text-amber-500 mt-1">{{ stats.avg_overall }} <span class="text-sm">/ 5</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'تقييم الطبيب' : 'Doctor Rating' }}</p>
                <p class="text-xl md:text-2xl font-bold text-[#1B365D] mt-1">{{ stats.avg_doctor }} <span class="text-sm">/ 5</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'تقييم الموظفين' : 'Staff Rating' }}</p>
                <p class="text-xl md:text-2xl font-bold text-[#1B365D] mt-1">{{ stats.avg_staff }} <span class="text-sm">/ 5</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'وقت الانتظار' : 'Waiting Rating' }}</p>
                <p class="text-xl md:text-2xl font-bold mt-1" :class="stats.avg_waiting >= 3.5 ? 'text-emerald-600' : 'text-red-600'">{{ stats.avg_waiting }} <span class="text-sm">/ 5</span></p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">{{ isRtl ? 'نسبة التوصية' : 'Recommend %' }}</p>
                <p class="text-xl md:text-2xl font-bold text-emerald-600 mt-1">{{ stats.recommend_rate }}%</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
                <p class="text-xs text-gray-400">NPS</p>
                <p class="text-xl md:text-2xl font-bold mt-1" :class="npsColor(stats.nps)">{{ stats.nps }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Rating Distribution -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="font-bold text-gray-700 mb-4">{{ isRtl ? 'توزيع التقييمات' : 'Rating Distribution' }}</h3>
                <div class="space-y-3">
                    <div v-for="i in [5,4,3,2,1]" :key="i" class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-600 w-6">{{ i }}★</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="i >= 4 ? 'bg-emerald-500' : i === 3 ? 'bg-amber-500' : 'bg-red-500'"
                                :style="{ width: stats.total > 0 ? ((stats.distribution[i-1] / stats.total) * 100) + '%' : '0%' }" />
                        </div>
                        <span class="text-xs text-gray-400 w-10 text-end">{{ stats.distribution[i-1] }}</span>
                    </div>
                </div>
            </div>

            <!-- Top Improvement Areas -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="font-bold text-gray-700 mb-4">{{ isRtl ? 'مجالات التحسين' : 'Top Improvement Areas' }}</h3>
                <div v-if="Object.keys(improvementAreas).length > 0" class="space-y-3">
                    <div v-for="(count, area) in improvementAreas" :key="area" class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ areaLabels[area] ? (isRtl ? areaLabels[area].ar : areaLabels[area].en) : area }}</span>
                        <span class="px-2 py-0.5 bg-red-50 text-red-600 rounded-full text-xs font-medium">{{ count }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-400 text-sm">{{ isRtl ? 'لا توجد بيانات' : 'No data yet' }}</div>
            </div>

            <!-- Doctor Rankings -->
            <div class="bg-white rounded-2xl border border-gray-100 p-5">
                <h3 class="font-bold text-gray-700 mb-4">{{ isRtl ? 'تقييم الأطباء' : 'Doctor Rankings' }}</h3>
                <div v-if="doctorRankings.length > 0" class="space-y-3">
                    <div v-for="(ranking, idx) in doctorRankings" :key="ranking.doctor_id" class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                            :class="idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-gray-100 text-gray-600' : idx === 2 ? 'bg-amber-100 text-[#C4A265]' : 'bg-gray-50 text-gray-400'">
                            {{ idx + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ isRtl ? ranking.doctor?.name_ar : ranking.doctor?.name_en }}</p>
                            <p class="text-xs text-gray-400">{{ ranking.review_count }} {{ isRtl ? 'تقييم' : 'reviews' }}</p>
                        </div>
                        <span class="text-amber-500 font-bold text-sm">{{ ranking.avg_rating }}★</span>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-gray-400 text-sm">{{ isRtl ? 'لا توجد بيانات كافية' : 'Not enough data' }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-4">
            <select v-model="doctorFilter" @change="applyFilters" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D]">
                <option value="">{{ isRtl ? 'كل الأطباء' : 'All Doctors' }}</option>
                <option v-for="d in doctors" :key="d.id" :value="d.id">{{ isRtl ? d.name_ar : d.name_en }}</option>
            </select>
            <select v-model="ratingFilter" @change="applyFilters" class="px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-[#1B365D] focus:border-[#1B365D]">
                <option value="">{{ isRtl ? 'كل التقييمات' : 'All Ratings' }}</option>
                <option v-for="r in [5,4,3,2,1]" :key="r" :value="r">{{ r }}★ {{ isRtl ? 'فأعلى' : '& up' }}</option>
            </select>
            <input v-model="dateFrom" @change="applyFilters" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm" />
            <input v-model="dateTo" @change="applyFilters" type="date" class="px-3 py-2 border border-gray-200 rounded-xl text-sm" />
        </div>

        <!-- Feedback List -->
        <div class="space-y-3">
            <div v-for="fb in feedbacks.data" :key="fb.id" class="bg-white rounded-2xl border border-gray-100 p-5 hover:shadow-sm transition">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-[#1B365D] font-bold text-sm">
                            {{ fb.patient?.full_name?.charAt(0) || '?' }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ fb.is_anonymous ? (isRtl ? 'مجهول' : 'Anonymous') : fb.patient?.full_name }}</p>
                            <p class="text-xs text-gray-400">{{ fb.created_at?.split('T')[0] }} &middot; {{ isRtl ? fb.doctor?.name_ar : fb.doctor?.name_en }}</p>
                        </div>
                    </div>
                    <div class="text-amber-500 text-lg tracking-wider">{{ stars(fb.overall_rating) }}</div>
                </div>

                <div v-if="fb.comments" class="text-sm text-gray-600 mb-3 bg-gray-50 rounded-xl p-3">{{ fb.comments }}</div>

                <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                    <span v-if="fb.doctor_rating">{{ isRtl ? 'الطبيب' : 'Doctor' }}: <strong class="text-amber-500">{{ fb.doctor_rating }}★</strong></span>
                    <span v-if="fb.staff_rating">{{ isRtl ? 'الموظفين' : 'Staff' }}: <strong class="text-amber-500">{{ fb.staff_rating }}★</strong></span>
                    <span v-if="fb.cleanliness_rating">{{ isRtl ? 'النظافة' : 'Clean' }}: <strong class="text-amber-500">{{ fb.cleanliness_rating }}★</strong></span>
                    <span v-if="fb.waiting_time_rating">{{ isRtl ? 'الانتظار' : 'Wait' }}: <strong class="text-amber-500">{{ fb.waiting_time_rating }}★</strong></span>
                    <span v-if="fb.nps_score !== null" class="px-2 py-0.5 rounded-full" :class="fb.nps_category?.color === 'green' ? 'bg-emerald-50 text-emerald-600' : fb.nps_category?.color === 'yellow' ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'">
                        NPS: {{ fb.nps_score }} ({{ isRtl ? fb.nps_category?.ar : fb.nps_category?.en }})
                    </span>
                    <span v-if="fb.would_recommend === true" class="text-emerald-600">{{ isRtl ? '✓ يوصي' : '✓ Recommends' }}</span>
                    <span v-else-if="fb.would_recommend === false" class="text-red-600">{{ isRtl ? '✗ لا يوصي' : '✗ Does not recommend' }}</span>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="feedbacks.data.length === 0" class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h3 class="font-medium">{{ isRtl ? 'لا توجد تقييمات بعد' : 'No feedback yet' }}</h3>
        </div>

        <!-- Pagination -->
        <div v-if="feedbacks.links && feedbacks.last_page > 1" class="flex justify-center gap-1 mt-6">
            <Link v-for="link in feedbacks.links" :key="link.label" :href="link.url || '#'"
                class="px-3 py-1.5 rounded-lg text-sm" :class="link.active ? 'bg-[#1B365D] text-white' : 'text-gray-500 hover:bg-gray-100'"
                v-html="link.label" preserve-state />
        </div>
    </div>
</template>
