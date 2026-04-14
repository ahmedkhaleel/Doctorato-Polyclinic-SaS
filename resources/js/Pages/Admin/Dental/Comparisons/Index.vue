<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

// ─── Toast Notification ─────────────────────────────────
const showSuccess = ref(false);
const successMessage = ref('');
watch(() => page.props.flash?.success, (msg) => {
    if (msg) {
        successMessage.value = msg;
        showSuccess.value = true;
        setTimeout(() => { showSuccess.value = false; }, 4000);
    }
});

// ─── Confirm Modal State ────────────────────────────────
const showDeleteModal = ref(false);
const pendingDeleteId = ref(null);

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

const isFiltering = ref(false);

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

function applyFilters() {
    isFiltering.value = true;
    router.get('/admin/dental/comparisons', {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { isFiltering.value = false; },
    });
}

function clearFilters() {
    search.value = '';
    selectedCategory.value = '';
    router.get('/admin/dental/comparisons');
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function confirmDelete(id) {
    pendingDeleteId.value = id;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!pendingDeleteId.value) return;
    router.post(`/admin/dental/comparisons/${pendingDeleteId.value}/delete`);
    showDeleteModal.value = false;
    pendingDeleteId.value = null;
}

function toggleVisibility(id) {
    router.post(`/admin/dental/comparisons/${id}/toggle-visibility`, {}, { preserveScroll: true });
}

function toggleFeatured(id) {
    router.post(`/admin/dental/comparisons/${id}/toggle-featured`, {}, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'مقارنة قبل / بعد' : 'Before & After'">
        <div class="space-y-6">
            <!-- Hero Header -->
            <div class="relative bg-gradient-to-br from-cyan-600 via-teal-600 to-emerald-700 rounded-2xl px-6 py-7 text-white overflow-hidden dental-hero-enter">
                <div class="absolute -top-10 ltr:-right-10 rtl:-left-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 ltr:right-20 rtl:left-20 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
                <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-10 h-10 bg-white/10 rounded-xl backdrop-blur">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                            {{ isRtl ? 'مقارنة قبل / بعد' : 'Before & After' }}
                        </h1>
                        <p class="text-cyan-100 text-sm mt-1">
                            {{ isRtl ? 'عرض صور قبل وبعد العلاج بمقارنة تفاعلية' : 'Interactive before & after treatment comparisons' }}
                        </p>
                    </div>
                    <Link href="/admin/dental/comparisons/create"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/15 hover:bg-white/25 backdrop-blur border border-white/20 rounded-xl text-sm font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        {{ isRtl ? 'إضافة مقارنة' : 'Add Comparison' }}
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 dental-card-enter" style="animation-delay:0.1s">
                <div class="flex flex-wrap gap-3 items-center">
                    <div class="relative flex-1 min-w-[200px]">
                        <svg v-if="!isFiltering" class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <svg v-else class="absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 w-4 h-4 text-cyan-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالمريض أو العنوان...' : 'Search patient or title...'"
                            class="w-full ltr:pl-10 rtl:pr-10 pr-4 py-2.5 bg-gray-50/50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-cyan-200 focus:border-cyan-300 transition" />
                    </div>

                    <!-- Category pills -->
                    <div class="flex flex-wrap gap-1.5">
                        <button @click="selectedCategory = ''; applyFilters()"
                            :class="!selectedCategory ? 'bg-cyan-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">
                            {{ isRtl ? 'الكل' : 'All' }}
                        </button>
                        <button v-for="cat in categories" :key="cat"
                            @click="selectedCategory = cat; applyFilters()"
                            :class="selectedCategory === cat ? 'bg-cyan-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition">
                            {{ categoryLabel(cat) }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="comparisons?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <div v-for="(comp, i) in comparisons.data" :key="comp.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all duration-300 group dental-card-enter"
                    :style="{ animationDelay: (0.15 + i * 0.05) + 's' }">

                    <!-- Image Comparison Preview -->
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                        <!-- Split view: left = before, right = after -->
                        <div class="absolute inset-0 flex">
                            <div class="w-1/2 h-full overflow-hidden">
                                <img :src="comp.before_image_url" :alt="isRtl ? 'قبل' : 'Before'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            </div>
                            <div class="w-1/2 h-full overflow-hidden border-s-2 border-white">
                                <img :src="comp.after_image_url" :alt="isRtl ? 'بعد' : 'After'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            </div>
                        </div>

                        <!-- Labels -->
                        <div class="absolute top-2 left-2 z-10">
                            <span class="bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span>
                        </div>
                        <div class="absolute top-2 right-2 z-10">
                            <span class="bg-black/60 text-white text-[10px] font-bold px-2 py-0.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span>
                        </div>

                        <!-- Category badge -->
                        <div class="absolute bottom-2 ltr:left-2 rtl:right-2 z-10">
                            <span :class="'bg-gradient-to-r ' + (categoryColors[comp.category] || 'from-gray-500 to-gray-600')"
                                class="text-white text-[10px] font-bold px-2.5 py-1 rounded-full">
                                {{ categoryLabel(comp.category) }}
                            </span>
                        </div>

                        <!-- Featured Star -->
                        <div v-if="comp.is_featured" class="absolute bottom-2 ltr:right-2 rtl:left-2 z-10">
                            <span class="bg-amber-400 text-white text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-0.5">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            </span>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-800 truncate">
                            {{ isRtl ? (comp.title_ar || comp.title_en || `مقارنة #${comp.id}`) : (comp.title_en || comp.title_ar || `Comparison #${comp.id}`) }}
                        </h3>
                        <p v-if="comp.patient" class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            {{ comp.patient.full_name }}
                        </p>

                        <div v-if="comp.tooth_numbers" class="flex flex-wrap gap-1 mt-2">
                            <span v-for="tooth in comp.tooth_numbers.split(',')" :key="tooth"
                                class="bg-cyan-50 text-cyan-700 text-[10px] font-mono font-bold px-1.5 py-0.5 rounded">
                                #{{ tooth.trim() }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between mt-3 text-[11px] text-gray-400">
                            <span>{{ formatDate(comp.before_date) }} → {{ formatDate(comp.after_date) }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 mt-3 pt-3 border-t border-gray-100">
                            <Link :href="`/admin/dental/comparisons/${comp.id}`"
                                class="flex-1 px-3 py-1.5 text-xs font-medium text-center text-cyan-600 bg-cyan-50 rounded-lg hover:bg-cyan-100 transition">
                                {{ isRtl ? 'عرض' : 'View' }}
                            </Link>
                            <button @click="toggleVisibility(comp.id)" :title="comp.is_visible_to_patient ? 'Hide from patient' : 'Show to patient'"
                                class="p-1.5 rounded-lg transition"
                                :class="comp.is_visible_to_patient ? 'text-green-500 bg-green-50 hover:bg-green-100' : 'text-gray-400 bg-gray-50 hover:bg-gray-100'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="comp.is_visible_to_patient" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                </svg>
                            </button>
                            <button @click="toggleFeatured(comp.id)"
                                class="p-1.5 rounded-lg transition"
                                :class="comp.is_featured ? 'text-amber-500 bg-amber-50 hover:bg-amber-100' : 'text-gray-400 bg-gray-50 hover:bg-gray-100'">
                                <svg class="w-4 h-4" :fill="comp.is_featured ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </button>
                            <button @click="confirmDelete(comp.id)" class="p-1.5 text-gray-400 bg-gray-50 rounded-lg hover:bg-red-50 hover:text-red-500 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-16 text-center dental-card-enter" style="animation-delay:0.2s">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500 text-sm mb-3">{{ isRtl ? 'لا توجد مقارنات بعد' : 'No comparisons yet' }}</p>
                <Link href="/admin/dental/comparisons/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600 text-white text-sm font-medium rounded-xl hover:bg-cyan-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'إضافة أول مقارنة' : 'Add First Comparison' }}
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="comparisons?.links?.length > 3" class="flex justify-center gap-1">
                <template v-for="link in comparisons.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-2 rounded-lg text-sm transition-colors"
                        :class="link.active ? 'bg-cyan-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                        v-html="link.label" />
                    <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="isRtl ? 'حذف المقارنة' : 'Delete Comparison'"
            :message="isRtl ? 'هل أنت متأكد من حذف هذه المقارنة؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this comparison? This action cannot be undone.'"
            :confirmText="isRtl ? 'حذف' : 'Delete'"
            :cancelText="isRtl ? 'إلغاء' : 'Cancel'"
            confirmColor="red"
            @confirm="executeDelete"
            @cancel="showDeleteModal = false"
        />

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showSuccess" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ successMessage }}</span>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
