<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

const props = defineProps({
    doctor: Object,
    rating: Object,
    reviews: { type: Array, default: () => [] },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(obj, field) {
    return locale.value === 'ar'
        ? (obj[field + '_ar'] || obj[field + '_en'])
        : (obj[field + '_en'] || obj[field + '_ar']);
}

function fmtDate(d) {
    if (!d) return '';
    try {
        return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US',
            { year: 'numeric', month: 'short', day: 'numeric' });
    } catch { return d; }
}

const recommendRate = computed(() => {
    const total = (props.rating?.recommend_yes || 0) + (props.rating?.recommend_no || 0);
    if (!total) return null;
    return Math.round((props.rating.recommend_yes / total) * 100);
});
</script>

<template>
    <FrontendLayout :title="$localized(doctor, 'name')">
        <!-- Hero -->
        <section class="relative bg-gradient-to-br from-[#1B365D] via-[#22406F] to-[#0F2444] py-16 overflow-hidden">
            <div class="absolute -top-20 -end-20 w-96 h-96 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="container mx-auto px-4 relative">
                <Link href="/doctors" class="inline-flex items-center gap-2 text-[#C4A265] hover:text-[#D9B985] mb-6 text-sm font-semibold">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    {{ isRtl ? 'كل الأطباء' : 'All doctors' }}
                </Link>

                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Photo -->
                    <div class="w-40 h-40 md:w-56 md:h-56 rounded-2xl overflow-hidden bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center flex-shrink-0 ring-4 ring-white/10 shadow-2xl">
                        <img v-if="doctor.photo" :src="doctor.photo" :alt="$localized(doctor, 'name')"
                             class="w-full h-full object-cover" />
                        <span v-else class="text-7xl text-white font-extrabold">{{ ($localized(doctor, 'name') || '?').charAt(0) }}</span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 text-white">
                        <span class="inline-block text-[10px] font-bold text-[#C4A265] tracking-[0.3em] uppercase mb-2">
                            {{ isRtl ? 'الطبيب' : 'Doctor' }}
                        </span>
                        <h1 class="text-3xl md:text-4xl font-extrabold mb-2">{{ $localized(doctor, 'name') }}</h1>
                        <p v-if="$localized(doctor, 'specialization')" class="text-base text-white/70 mb-4">
                            {{ $localized(doctor, 'specialization') }}
                        </p>

                        <!-- Rating + recommend -->
                        <div v-if="rating?.total" class="flex items-center gap-4 flex-wrap mb-5">
                            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur border border-white/20 rounded-xl px-4 py-2">
                                <div class="flex items-center gap-0.5">
                                    <svg v-for="i in 5" :key="i" class="w-4 h-4"
                                         :class="i <= Math.round(rating.avg_overall) ? 'text-[#C4A265]' : 'text-white/20'"
                                         fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                                <span class="text-base font-bold tabular-nums">{{ rating.avg_overall?.toFixed(1) }}</span>
                                <span class="text-xs text-white/60">/ 5</span>
                                <span class="text-[10px] text-white/50 ms-1">({{ rating.total }})</span>
                            </div>
                            <span v-if="recommendRate !== null" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-300">
                                👍 {{ recommendRate }}% {{ isRtl ? 'يوصي بنا' : 'recommend' }}
                            </span>
                        </div>

                        <!-- CTAs -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <Link href="/booking"
                                  class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#C4A265] to-[#D9B985] hover:from-[#D9B985] hover:to-[#C4A265] text-[#1B365D] font-bold text-sm shadow-lg transition">
                                {{ isRtl ? '📅 احجز الآن' : '📅 Book Now' }}
                            </Link>
                            <Link v-if="doctor.online_consultation_enabled" href="/booking"
                                  class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm shadow-lg transition">
                                {{ isRtl ? '💻 استشارة أونلاين' : '💻 Online Consultation' }}
                                <span v-if="doctor.online_consultation_fee" class="text-[11px] font-normal opacity-90">
                                    ({{ doctor.online_consultation_fee }})
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <section class="py-12 bg-gray-50">
            <div class="container mx-auto px-4 max-w-5xl">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Bio + qualifications -->
                    <div class="lg:col-span-2 space-y-5">
                        <div v-if="$localized(doctor, 'bio')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-base font-bold text-[#1B365D] mb-3 flex items-center gap-2">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                {{ isRtl ? 'نبذة' : 'About' }}
                            </h2>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $localized(doctor, 'bio') }}</p>
                        </div>

                        <div v-if="$localized(doctor, 'qualifications')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-base font-bold text-[#1B365D] mb-3 flex items-center gap-2">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                {{ isRtl ? 'المؤهلات' : 'Qualifications' }}
                            </h2>
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $localized(doctor, 'qualifications') }}</p>
                        </div>

                        <!-- Reviews -->
                        <div v-if="reviews.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-base font-bold text-[#1B365D] mb-4 flex items-center gap-2">
                                <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                                {{ isRtl ? 'آراء المرضى' : 'Patient reviews' }}
                            </h2>
                            <div class="space-y-4">
                                <div v-for="r in reviews" :key="r.id" class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-7 h-7 rounded-full bg-[#1B365D] text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                                                {{ r.reviewer || '?' }}
                                            </div>
                                            <p class="text-xs font-semibold text-gray-700">
                                                {{ r.reviewer ? r.reviewer : (isRtl ? 'مريض مجهول الهوية' : 'Anonymous patient') }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-0.5 flex-shrink-0">
                                            <svg v-for="i in 5" :key="i" class="w-3 h-3"
                                                 :class="i <= r.overall_rating ? 'text-amber-400' : 'text-gray-200'"
                                                 fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ r.comments }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1">{{ fmtDate(r.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar: at-a-glance -->
                    <div class="space-y-5">
                        <div class="bg-gradient-to-br from-[#FAF7F0] to-white rounded-2xl border border-[#C4A265]/30 p-5">
                            <h3 class="text-[10px] font-bold text-[#8B7043] tracking-[0.2em] uppercase mb-3">
                                {{ isRtl ? 'لمحة سريعة' : 'At a glance' }}
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div v-if="doctor.consultation_fee">
                                    <p class="text-xs text-gray-500">{{ isRtl ? 'سعر الكشف' : 'Consultation fee' }}</p>
                                    <p class="font-bold text-[#1B365D] text-lg tabular-nums">{{ doctor.consultation_fee }}</p>
                                </div>
                                <div v-if="doctor.online_consultation_enabled && doctor.online_consultation_fee" class="pt-3 border-t border-[#C4A265]/20">
                                    <p class="text-xs text-gray-500">{{ isRtl ? 'سعر الاستشارة الأونلاين' : 'Online consultation fee' }}</p>
                                    <p class="font-bold text-emerald-600 text-lg tabular-nums">{{ doctor.online_consultation_fee }}</p>
                                </div>
                                <div v-if="rating?.total" class="pt-3 border-t border-[#C4A265]/20 grid grid-cols-2 gap-2 text-xs">
                                    <div v-if="rating.avg_doctor">
                                        <p class="text-gray-500">{{ isRtl ? 'الطبيب' : 'Doctor' }}</p>
                                        <p class="font-bold text-[#1B365D] tabular-nums">{{ rating.avg_doctor.toFixed(1) }}/5</p>
                                    </div>
                                    <div v-if="rating.avg_communication">
                                        <p class="text-gray-500">{{ isRtl ? 'التواصل' : 'Communication' }}</p>
                                        <p class="font-bold text-[#1B365D] tabular-nums">{{ rating.avg_communication.toFixed(1) }}/5</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Link href="/booking"
                              class="block w-full text-center px-6 py-3 rounded-xl bg-[#1B365D] hover:bg-[#22406F] text-white font-bold text-sm shadow-md transition">
                            {{ isRtl ? '📅 احجز موعداً الآن' : '📅 Book an appointment' }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </FrontendLayout>
</template>
