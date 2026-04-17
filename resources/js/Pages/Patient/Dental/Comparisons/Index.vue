<script setup>
import { ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();
defineOptions({ layout: PatientLayout });

const props = defineProps({
    comparisons: Object,
    categories: Array,
    activeCategory: String,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const categoryLabels = {
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' }, cosmetic: { ar: 'تجميلي', en: 'Cosmetic' },
    implant: { ar: 'زراعة', en: 'Implant' }, whitening: { ar: 'تبييض', en: 'Whitening' },
    restoration: { ar: 'ترميم', en: 'Restoration' }, surgical: { ar: 'جراحي', en: 'Surgical' },
    xray: { ar: 'أشعة', en: 'X-ray' }, other: { ar: 'أخرى', en: 'Other' },
};

const categoryColors = {
    orthodontic: 'from-[#1B365D] to-[#1B365D]', cosmetic: 'from-[#C4A265] to-[#C4A265]',
    implant: 'from-[#1B365D] to-teal-600', whitening: 'from-amber-400 to-amber-500',
    restoration: 'from-[#1B365D] to-[#1B365D]', surgical: 'from-red-500 to-[#C4A265]',
    xray: 'from-gray-500 to-gray-700', other: 'from-emerald-500 to-emerald-600',
};

function categoryLabel(cat) { const l = categoryLabels[cat]; return l ? (isRtl.value ? l.ar : l.en) : cat; }
function formatDate(date) { if (!date) return ''; return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

// Unique categories from data
const availableCategories = computed(() => {
    const cats = new Set();
    (props.comparisons?.data || []).forEach(c => cats.add(c.category));
    return Array.from(cats);
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'قبل وبعد العلاج' : 'Before & After' }}</h1>
                <p class="text-sm text-gray-400">{{ isRtl ? 'تتبع تقدم علاجك' : 'Track your treatment progress' }}</p>
            </div>
        </div>

        <!-- Category Filter Chips -->
        <div v-if="categories?.length > 1" class="flex flex-wrap gap-2 mb-6">
            <Link :href="lp('/dental/comparisons')"
                :class="!activeCategory ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition-all">
                {{ isRtl ? 'الكل' : 'All' }}
            </Link>
            <Link v-for="cat in categories" :key="cat" :href="lp('/dental/comparisons?category=' + cat)"
                :class="activeCategory === cat ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100'"
                class="px-4 py-2 rounded-full text-xs font-semibold transition-all">
                {{ categoryLabel(cat) }}
            </Link>
        </div>

        <!-- Comparison Cards -->
        <div v-if="comparisons?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <Link v-for="comp in comparisons.data" :key="comp.id" :href="lp('/dental/comparisons/' + comp.id)"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all duration-300 block group">

                <!-- Image Preview -->
                <div class="relative aspect-[16/9] overflow-hidden bg-gray-100">
                    <div class="absolute inset-0 flex">
                        <div class="w-1/2 h-full overflow-hidden">
                            <img :src="comp.before_image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                        <div class="w-1/2 h-full overflow-hidden border-s-2 border-white">
                            <img :src="comp.after_image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                    </div>

                    <!-- Before/After labels -->
                    <div class="absolute top-2 left-2 z-10"><span class="bg-black/50 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                    <div class="absolute top-2 right-2 z-10"><span class="bg-emerald-500/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>

                    <!-- Divider indicator -->
                    <div class="absolute top-0 bottom-0 left-1/2 w-0.5 bg-white/80 z-10">
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 bg-white rounded-full shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="absolute bottom-2 ltr:left-2 rtl:right-2 z-10">
                        <span :class="'bg-gradient-to-r ' + (categoryColors[comp.category] || 'from-gray-500 to-gray-600')"
                            class="text-white text-[10px] font-bold px-2.5 py-1 rounded-full">{{ categoryLabel(comp.category) }}</span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-800 truncate">
                        {{ isRtl ? (comp.title_ar || comp.title_en || categoryLabel(comp.category)) : (comp.title_en || comp.title_ar || categoryLabel(comp.category)) }}
                    </h3>
                    <div class="flex items-center justify-between mt-2">
                        <span v-if="comp.doctor" class="text-xs text-gray-500 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            {{ isRtl ? comp.doctor.name_ar : comp.doctor.name_en }}
                        </span>
                        <span v-if="comp.before_date && comp.after_date" class="text-[11px] text-gray-400">
                            {{ formatDate(comp.before_date) }} → {{ formatDate(comp.after_date) }}
                        </span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد مقارنات صور بعد' : 'No photo comparisons yet' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="comparisons?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in comparisons.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label" />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
