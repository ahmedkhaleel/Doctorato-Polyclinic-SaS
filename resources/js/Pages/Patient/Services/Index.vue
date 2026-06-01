<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';
import { useCurrency } from '@/Composables/useCurrency';

const { lp } = usePatientLocale();
const { formatCurrency } = useCurrency();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    services: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    availableModules: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref('');
const activeModule = ref(props.filters?.module || '');
const activeCategory = ref(null);

function $localized(s, field) {
    if (!s) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return s[field + '_' + lang] || s[field + '_en'] || '';
}

const moduleLabel = (m) => {
    const labels = {
        ar: { derma: 'الجلدية والتجميل', dental: 'الأسنان', pediatric: 'الأطفال' },
        en: { derma: 'Dermatology', dental: 'Dental', pediatric: 'Pediatric' },
    };
    return labels[locale.value]?.[m] || m;
};

const filteredServices = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.services
        .filter(s => !activeModule.value || s.module === activeModule.value)
        .filter(s => !activeCategory.value || s.category_id === activeCategory.value)
        .filter(s => {
            if (!q) return true;
            const blob = [s.name_en, s.name_ar, s.short_desc_en, s.short_desc_ar]
                .filter(Boolean).join(' ').toLowerCase();
            return blob.includes(q);
        });
});

function applyModuleFilter(m) {
    activeModule.value = activeModule.value === m ? '' : m;
    router.get(lp('/services'),
        { module: activeModule.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true });
}

function pickCategory(id) {
    activeCategory.value = activeCategory.value === id ? null : id;
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
                        {{ isRtl ? 'الخدمات' : 'Services' }}
                    </span>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold mb-2">
                    {{ isRtl ? 'تصفح خدماتنا' : 'Browse our services' }}
                </h1>
                <p class="text-sm text-white/70 max-w-xl">
                    {{ isRtl
                        ? 'اطلع على الأسعار والمدة قبل الحجز. شفافية كاملة، لا مفاجآت.'
                        : 'Pricing and duration upfront. No surprises.' }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center gap-3 mb-3">
                <div class="flex-1">
                    <input v-model="search" type="text"
                           :placeholder="isRtl ? 'ابحث عن خدمة...' : 'Search services...'"
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
            <div v-if="categories.length" class="flex flex-wrap gap-2 pt-3 border-t border-gray-100">
                <button v-for="c in categories" :key="c.id" type="button" @click="pickCategory(c.id)"
                        :class="activeCategory === c.id
                            ? 'bg-[#C4A265] text-white border-[#C4A265]'
                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-full border text-xs font-medium transition">
                    {{ $localized(c, 'name') }}
                </button>
            </div>
        </div>

        <!-- Services grid -->
        <div v-if="filteredServices.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="s in filteredServices" :key="s.id"
                 class="group bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-[#C4A265]/40 hover:shadow-lg transition overflow-hidden flex flex-col">
                <!-- Image -->
                <div class="aspect-[16/10] bg-gradient-to-br from-slate-100 to-slate-50 overflow-hidden relative">
                    <img v-if="s.image" :src="s.image" :alt="$localized(s, 'name')"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                    <div v-else class="w-full h-full flex items-center justify-center text-[#C4A265]/40">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <span class="absolute top-2 end-2 text-[10px] font-bold px-2 py-1 rounded-full bg-white/90 backdrop-blur text-[#1B365D] uppercase">
                        {{ moduleLabel(s.module) }}
                    </span>
                </div>

                <!-- Body -->
                <div class="p-4 flex-1 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-800 line-clamp-2 mb-1">{{ $localized(s, 'name') }}</h3>
                    <p v-if="$localized(s, 'short_desc')" class="text-[11px] text-gray-500 line-clamp-2 mb-3">
                        {{ $localized(s, 'short_desc') }}
                    </p>

                    <!-- Meta row -->
                    <div class="flex items-center gap-3 text-[11px] text-gray-500 mt-auto mb-3">
                        <span v-if="s.session_duration_minutes" class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ s.session_duration_minutes }} {{ isRtl ? 'د' : 'min' }}
                        </span>
                        <span v-if="s.default_sessions > 1" class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#C4A265]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            {{ s.default_sessions }} {{ isRtl ? 'جلسات' : 'sessions' }}
                        </span>
                    </div>

                    <!-- Price + CTA -->
                    <div class="flex items-end justify-between gap-2 pt-3 border-t border-gray-50">
                        <div>
                            <p v-if="s.price > 0" class="flex items-baseline gap-2">
                                <span v-if="s.price_after_discount && s.price_after_discount < s.price"
                                      class="text-base font-extrabold text-[#1B365D] tabular-nums">{{ formatCurrency(s.price_after_discount) }}</span>
                                <span v-if="s.price_after_discount && s.price_after_discount < s.price"
                                      class="text-[10px] text-gray-400 line-through tabular-nums">{{ formatCurrency(s.price) }}</span>
                                <span v-else class="text-base font-extrabold text-[#1B365D] tabular-nums">{{ formatCurrency(s.price) }}</span>
                            </p>
                            <p v-else class="text-[11px] text-gray-400 italic">
                                {{ isRtl ? 'السعر عند الكشف' : 'Price on consultation' }}
                            </p>
                        </div>
                        <Link :href="lp(`/bookings/create?module=${s.module}&service_id=${s.id}`)"
                              class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:opacity-90 text-white text-xs font-bold shadow-md transition whitespace-nowrap">
                            {{ isRtl ? 'احجز' : 'Book' }}
                            <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-500">
                {{ search || activeCategory
                    ? (isRtl ? 'لا توجد نتائج مطابقة' : 'No matching results')
                    : (isRtl ? 'لا توجد خدمات حالياً' : 'No services available') }}
            </p>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
