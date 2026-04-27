<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    pendingVisits: { type: Array, default: () => [] },
    recentReviews: { type: Array, default: () => [] },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return d; }
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] rounded-2xl p-6 md:p-8 mb-6 text-white relative overflow-hidden">
            <div class="absolute -top-12 -end-12 w-44 h-44 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                    <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                        {{ isRtl ? 'تقييماتي' : 'My Feedback' }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">
                    {{ isRtl ? 'رأيك يهمنا' : 'Your feedback matters' }}
                </h1>
                <p class="text-sm text-white/70 max-w-xl">
                    {{ isRtl
                        ? 'شارك تجربتك بعد كل زيارة لمساعدتنا على تحسين خدماتنا. تقييماتك تساعد المرضى الآخرين أيضاً.'
                        : 'Share your experience after each visit to help us improve. Your reviews also help other patients.' }}
                </p>
            </div>
        </div>

        <!-- Pending visits to review -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    ⭐ {{ isRtl ? 'زيارات بانتظار تقييمك' : 'Visits awaiting your review' }}
                </h2>
                <span v-if="pendingVisits.length" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
                    {{ pendingVisits.length }}
                </span>
            </div>

            <div v-if="pendingVisits.length" class="divide-y divide-gray-50">
                <div v-for="v in pendingVisits" :key="v.id"
                     class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#22406F] flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {{ ($localized(v.doctor, 'name') || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ $localized(v.doctor, 'name') || (isRtl ? 'الطبيب' : 'Doctor') }}
                            </p>
                            <p class="text-[11px] text-gray-500 mt-0.5">
                                {{ $localized(v.service, 'name') || '—' }}
                                <span class="ms-2">· {{ fmtDate(v.visit_date) }}</span>
                            </p>
                        </div>
                    </div>
                    <Link :href="lp(`/feedback/${v.id}`)"
                          class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-[#C4A265] hover:bg-[#8B7043] text-white text-xs font-bold transition">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        {{ isRtl ? 'قيّم الآن' : 'Rate now' }}
                    </Link>
                </div>
            </div>
            <div v-else class="p-10 text-center">
                <div class="text-5xl mb-3">✨</div>
                <p class="text-sm text-gray-500">{{ isRtl ? 'كل زياراتك تم تقييمها — شكراً!' : 'All your visits have been reviewed — thank you!' }}</p>
            </div>
        </div>

        <!-- Recent reviews submitted -->
        <div v-if="recentReviews.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">{{ isRtl ? 'تقييماتك السابقة' : 'Your past reviews' }}</h2>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="r in recentReviews" :key="r.id" class="p-4">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">
                                {{ $localized(r.doctor, 'name') || (isRtl ? 'الطبيب' : 'Doctor') }}
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ fmtDate(r.visit?.visit_date) }} · {{ isRtl ? 'قُدّم في' : 'Submitted' }} {{ fmtDate(r.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-0.5 flex-shrink-0">
                            <svg v-for="i in 5" :key="i"
                                 class="w-4 h-4"
                                 :class="i <= r.overall_rating ? 'text-amber-400' : 'text-gray-200'"
                                 fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <p v-if="r.comments" class="text-xs text-gray-600 leading-relaxed bg-gray-50 rounded-lg p-3">{{ r.comments }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
