<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    doctors: { type: Array, default: () => [] },
    availableModules: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref('');
const activeModule = ref(props.filters?.module || '');

function $localized(d, field) {
    if (!d) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return d[field + '_' + lang] || d[field + '_en'] || '';
}

const moduleLabel = (m) => {
    const labels = {
        ar: { derma: 'الجلدية والتجميل', dental: 'الأسنان', pediatric: 'الأطفال' },
        en: { derma: 'Dermatology', dental: 'Dental', pediatric: 'Pediatric' },
    };
    return labels[locale.value]?.[m] || m;
};

const filteredDoctors = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.doctors
        .filter(d => !activeModule.value || d.module === activeModule.value)
        .filter(d => {
            if (!q) return true;
            const blob = [
                d.name_en, d.name_ar,
                d.specialization_en, d.specialization_ar,
            ].filter(Boolean).join(' ').toLowerCase();
            return blob.includes(q);
        });
});

function applyModuleFilter(m) {
    activeModule.value = activeModule.value === m ? '' : m;
    router.get(lp('/doctors'),
        { module: activeModule.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] rounded-2xl p-6 md:p-8 mb-6 text-white relative overflow-hidden">
            <div class="absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                    <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">
                        {{ isRtl ? 'فريقنا الطبي' : 'Our Medical Team' }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">
                    {{ isRtl ? 'تصفح الأطباء واحجز' : 'Browse our doctors & book' }}
                </h1>
                <p class="text-sm text-white/70 max-w-xl">
                    {{ isRtl
                        ? 'تعرّف على أطبائنا، اقرأ تقييمات المرضى السابقين، واحجز مع الطبيب المناسب لك بضغطة واحدة.'
                        : 'Meet our team, read patient reviews, and book with the right doctor in one click.' }}
                </p>
            </div>
        </div>

        <!-- Filters bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex-1">
                    <input v-model="search" type="text"
                           :placeholder="isRtl ? 'بحث بالاسم أو التخصص...' : 'Search by name or specialty...'"
                           class="doctorato-input w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/30 focus:border-[var(--brand-primary)]/40 transition" />
                </div>
                <div v-if="availableModules.length > 1" class="flex flex-wrap gap-2">
                    <button v-for="m in availableModules" :key="m" @click="applyModuleFilter(m)"
                            :class="activeModule === m
                                ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)]'
                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                            class="px-3 py-2 rounded-lg border text-xs font-semibold transition whitespace-nowrap">
                        {{ moduleLabel(m) }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Doctors grid -->
        <div v-if="filteredDoctors.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="d in filteredDoctors" :key="d.id"
                 class="group bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-[#C4A265]/40 hover:shadow-lg transition overflow-hidden">
                <!-- Header: photo + name -->
                <div class="flex items-center gap-3 p-4 border-b border-gray-50">
                    <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-gradient-to-br from-[#1B365D] to-[#22406F] flex items-center justify-center text-white font-bold ring-2 ring-[#C4A265]/20">
                        <img v-if="d.photo_url" :src="d.photo_url" :alt="$localized(d, 'name')" class="w-full h-full object-cover" />
                        <span v-else>{{ ($localized(d, 'name') || '?').charAt(0).toUpperCase() }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-800 truncate">{{ $localized(d, 'name') }}</p>
                        <p class="text-[11px] text-gray-500 truncate">{{ $localized(d, 'specialization') }}</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-4 space-y-3">
                    <!-- Rating + module -->
                    <div class="flex items-center justify-between gap-2">
                        <div v-if="d.rating_count > 0" class="flex items-center gap-1.5">
                            <div class="flex items-center gap-0.5">
                                <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5"
                                     :class="i <= Math.round(d.rating_avg) ? 'text-amber-400' : 'text-gray-200'"
                                     fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-gray-700 tabular-nums">{{ d.rating_avg }}</span>
                            <span class="text-[10px] text-gray-400">({{ d.rating_count }})</span>
                        </div>
                        <span v-else class="text-[10px] text-gray-400 italic">{{ isRtl ? 'لا توجد تقييمات بعد' : 'No reviews yet' }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-50 text-[#1B365D] uppercase">
                            {{ moduleLabel(d.module) }}
                        </span>
                    </div>

                    <!-- Bio (truncated) -->
                    <p v-if="$localized(d, 'bio')" class="text-xs text-gray-600 leading-relaxed line-clamp-3">
                        {{ $localized(d, 'bio') }}
                    </p>

                    <!-- Experience -->
                    <p v-if="d.years_experience" class="text-[11px] text-gray-500 inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ d.years_experience }} {{ isRtl ? 'سنة خبرة' : 'years experience' }}
                    </p>

                    <!-- Book CTA -->
                    <Link :href="lp(`/bookings/create?module=${d.module}&doctor_id=${d.id}`)"
                          class="block w-full text-center px-4 py-2.5 rounded-xl bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:opacity-90 text-white text-sm font-bold shadow-md transition">
                        {{ isRtl ? 'احجز الآن' : 'Book Now' }}
                    </Link>
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="text-5xl mb-3">🔍</div>
            <p class="text-sm text-gray-500">
                {{ search ? (isRtl ? 'لا توجد نتائج مطابقة' : 'No matching results')
                          : (isRtl ? 'لا يوجد أطباء حالياً' : 'No doctors available') }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
