<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';

const props = defineProps({
    doctors: { type: Array, default: () => [] },
    availableModules: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

function $localized(d, field) {
    return locale.value === 'ar'
        ? (d[field + '_ar'] || d[field + '_en'])
        : (d[field + '_en'] || d[field + '_ar']);
}

function moduleLabel(m) {
    const labels = {
        ar: { derma: 'الجلدية والتجميل', dental: 'الأسنان', pediatric: 'الأطفال' },
        en: { derma: 'Dermatology', dental: 'Dental', pediatric: 'Pediatric' },
    };
    return labels[locale.value]?.[m] || m;
}

const search = ref('');
const activeModule = ref(props.filters?.module || '');

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.doctors
        .filter(d => !activeModule.value || d.module === activeModule.value)
        .filter(d => {
            if (!q) return true;
            const blob = [d.name_en, d.name_ar, d.specialization_en, d.specialization_ar]
                .filter(Boolean).join(' ').toLowerCase();
            return blob.includes(q);
        });
});

function applyModuleFilter(m) {
    activeModule.value = activeModule.value === m ? '' : m;
    router.get('/doctors',
        { module: activeModule.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <FrontendLayout :title="isRtl ? 'الأطباء' : 'Our Doctors'">
        <!-- Hero -->
        <section class="relative bg-gradient-to-br from-[#1B365D] via-[#22406F] to-[#0F2444] py-16 overflow-hidden">
            <div class="absolute -top-20 -end-20 w-96 h-96 rounded-full bg-[#C4A265]/15 blur-3xl"></div>
            <div class="container mx-auto px-4 relative">
                <div class="text-center text-white">
                    <span class="inline-block text-[10px] font-bold text-[#C4A265] tracking-[0.3em] uppercase mb-3">
                        {{ isRtl ? 'فريقنا الطبي' : 'Our Medical Team' }}
                    </span>
                    <h1 class="text-3xl md:text-5xl font-extrabold mb-4">
                        {{ isRtl ? 'تعرّف على أطبائنا' : 'Meet our doctors' }}
                    </h1>
                    <p class="text-base md:text-lg text-white/70 max-w-2xl mx-auto">
                        {{ isRtl
                            ? 'فريق من الأطباء المعتمدين بخبرات متنوعة، جاهزون لخدمتك في كافة التخصصات'
                            : 'Board-certified specialists ready to serve you across every specialty' }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Filters + grid -->
        <section class="py-12 bg-gray-50">
            <div class="container mx-auto px-4">
                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col md:flex-row md:items-center gap-3">
                    <input v-model="search" type="text"
                           :placeholder="isRtl ? 'ابحث عن طبيب أو تخصص...' : 'Search doctor or specialty...'"
                           class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#1B365D]/30 focus:border-[#1B365D]/40" />
                    <div v-if="availableModules.length > 1" class="flex flex-wrap gap-2">
                        <button v-for="m in availableModules" :key="m" @click="applyModuleFilter(m)"
                                :class="activeModule === m
                                    ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                class="px-3 py-2 rounded-lg border text-xs font-semibold transition whitespace-nowrap">
                            {{ moduleLabel(m) }}
                        </button>
                    </div>
                </div>

                <!-- Doctor cards -->
                <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Link v-for="d in filtered" :key="d.id"
                          :href="`/doctors/${d.id}`"
                          class="group bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-[#C4A265]/40 hover:shadow-xl transition overflow-hidden flex flex-col">
                        <!-- Photo -->
                        <div class="aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden relative">
                            <img v-if="d.photo" :src="d.photo" :alt="$localized(d, 'name')"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center text-[#1B365D]/20">
                                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </div>
                            <span class="absolute top-3 end-3 text-[10px] font-bold px-2 py-1 rounded-full bg-white/95 backdrop-blur text-[#1B365D] uppercase">
                                {{ moduleLabel(d.module) }}
                            </span>
                            <span v-if="d.online" class="absolute bottom-3 start-3 text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-500 text-white uppercase shadow">
                                 {{ isRtl ? 'أونلاين' : 'Online' }}
                            </span>
                        </div>

                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="text-base font-bold text-[#1B365D] mb-1">{{ $localized(d, 'name') }}</h3>
                            <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $localized(d, 'specialization') }}</p>

                            <!-- Rating -->
                            <div v-if="d.rating_avg" class="flex items-center gap-2 mb-3">
                                <div class="flex items-center gap-0.5">
                                    <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5"
                                         :class="i <= Math.round(d.rating_avg) ? 'text-amber-400' : 'text-gray-200'"
                                         fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-[#1B365D]">{{ d.rating_avg.toFixed(1) }}</span>
                                <span class="text-[10px] text-gray-400">({{ d.rating_count }})</span>
                            </div>
                            <div v-else class="text-[10px] text-gray-400 italic mb-3">
                                {{ isRtl ? 'لا توجد تقييمات بعد' : 'No reviews yet' }}
                            </div>

                            <!-- CTA -->
                            <div class="mt-auto pt-3 border-t border-gray-50 flex items-center justify-between">
                                <span v-if="d.consultation_fee" class="text-xs text-gray-600">
                                    <span class="font-bold text-[#1B365D]">{{ d.consultation_fee }}</span>
                                    {{ isRtl ? 'كشف' : 'consultation' }}
                                </span>
                                <span v-else class="text-[10px] text-gray-400">&nbsp;</span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-[#C4A265] group-hover:gap-2 transition-all">
                                    {{ isRtl ? 'عرض الملف' : 'View profile' }}
                                    <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-sm text-gray-500">
                        {{ search ? (isRtl ? 'لا يوجد طبيب يطابق البحث' : 'No matching doctors') : (isRtl ? 'لا يوجد أطباء حالياً' : 'No doctors available') }}
                    </p>
                </div>
            </div>
        </section>
    </FrontendLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
