<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    comparisons: Object,
    filters: Object,
    categories: Array,
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');

const categoryLabels = {
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' },
    cosmetic: { ar: 'تجميلي', en: 'Cosmetic' },
    implant: { ar: 'زراعة', en: 'Implant' },
    whitening: { ar: 'تبييض', en: 'Whitening' },
    restoration: { ar: 'ترميم', en: 'Restoration' },
    surgical: { ar: 'جراحي', en: 'Surgical' },
    xray: { ar: 'أشعة', en: 'X-ray' },
    other: { ar: 'أخرى', en: 'Other' },
};

const categoryColors = {
    orthodontic: 'from-violet-500 to-purple-600',
    cosmetic: 'from-pink-500 to-rose-600',
    implant: 'from-cyan-500 to-teal-600',
    whitening: 'from-amber-400 to-yellow-500',
    restoration: 'from-blue-500 to-indigo-600',
    surgical: 'from-red-500 to-rose-600',
    xray: 'from-gray-500 to-gray-700',
    other: 'from-emerald-500 to-green-600',
};

function categoryLabel(cat) {
    const l = categoryLabels[cat];
    return l ? (isRtl.value ? l.ar : l.en) : cat;
}

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

function applyFilters() {
    router.get('/doctor/dental/comparisons', {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'مقارنة الصور' : 'Photo Comparisons' }}</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ isRtl ? 'قبل / بعد' : 'Before & After' }}</h1>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4">
            <div class="flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="search" type="text" :placeholder="isRtl ? 'بحث...' : 'Search...'"
                        class="w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] transition" />
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button @click="selectedCategory = ''; applyFilters()"
                        :class="!selectedCategory ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition">{{ isRtl ? 'الكل' : 'All' }}</button>
                    <button v-for="cat in categories" :key="cat" @click="selectedCategory = cat; applyFilters()"
                        :class="selectedCategory === cat ? 'bg-[#C4A265] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition">{{ categoryLabel(cat) }}</button>
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div v-if="comparisons?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <Link v-for="comp in comparisons.data" :key="comp.id" :href="`/doctor/dental/comparisons/${comp.id}`"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all duration-300 block group">
                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                    <div class="absolute inset-0 flex">
                        <div class="w-1/2 h-full overflow-hidden">
                            <img :src="comp.before_image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                        <div class="w-1/2 h-full overflow-hidden border-s-2 border-white">
                            <img :src="comp.after_image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                    </div>
                    <div class="absolute top-2 left-2 z-10"><span class="bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                    <div class="absolute top-2 right-2 z-10"><span class="bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
                    <div class="absolute bottom-2 ltr:left-2 rtl:right-2 z-10">
                        <span :class="'bg-gradient-to-r ' + (categoryColors[comp.category] || 'from-gray-500 to-gray-600')"
                            class="text-white text-[10px] font-bold px-2.5 py-1 rounded-full">{{ categoryLabel(comp.category) }}</span>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? (comp.title_ar || comp.title_en || `#${comp.id}`) : (comp.title_en || comp.title_ar || `#${comp.id}`) }}</h3>
                    <p v-if="comp.patient" class="text-xs text-gray-500 mt-0.5">{{ comp.patient.full_name }}</p>
                    <div class="flex items-center justify-between mt-2 text-[11px] text-gray-400">
                        <span>{{ formatDate(comp.before_date) }} → {{ formatDate(comp.after_date) }}</span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-16 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-gray-500 text-sm">{{ isRtl ? 'لا توجد مقارنات بعد' : 'No comparisons yet' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="comparisons?.links?.length > 3" class="flex justify-center gap-1">
            <template v-for="link in comparisons.links" :key="link.label">
                <Link v-if="link.url" :href="link.url" class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[#C4A265] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label" />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
