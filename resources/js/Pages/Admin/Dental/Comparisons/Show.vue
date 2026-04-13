<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';
import { useCurrency } from '@/Composables/useCurrency.js';

const { t } = useLocale();
const { formatCurrency } = useCurrency();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    comparison: Object,
});

const comp = computed(() => props.comparison);

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

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ─── Interactive Slider ─────────────────────────────────
const sliderPosition = ref(50);
const sliderRef = ref(null);
const isDragging = ref(false);
const viewMode = ref('slider'); // 'slider', 'side', 'before', 'after'

function getPos(e) {
    if (!sliderRef.value) return 50;
    const rect = sliderRef.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    return Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
}

function startDrag(e) { isDragging.value = true; sliderPosition.value = getPos(e); }
function onDrag(e) { if (!isDragging.value) return; e.preventDefault(); sliderPosition.value = getPos(e); }
function stopDrag() { isDragging.value = false; }

onMounted(() => {
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', stopDrag);
});
onUnmounted(() => {
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', stopDrag);
});

function deleteComparison() {
    if (window.confirm(isRtl.value ? 'حذف هذه المقارنة؟' : 'Delete this comparison?')) {
        router.post(`/admin/dental/comparisons/${comp.value.id}/delete`);
    }
}
</script>

<template>
    <AdminLayout :title="isRtl ? 'مقارنة قبل / بعد' : 'Before & After'">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between dental-hero-enter">
                <div class="flex items-center gap-3">
                    <Link href="/admin/dental/comparisons" class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            {{ isRtl ? (comp.title_ar || comp.title_en || `مقارنة #${comp.id}`) : (comp.title_en || comp.title_ar || `Comparison #${comp.id}`) }}
                        </h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span :class="'bg-gradient-to-r ' + (categoryColors[comp.category] || 'from-gray-500 to-gray-600')"
                                class="text-white text-xs font-bold px-3 py-0.5 rounded-full">
                                {{ categoryLabel(comp.category) }}
                            </span>
                            <span v-if="comp.is_featured" class="bg-amber-100 text-amber-700 text-xs font-medium px-2 py-0.5 rounded-full flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                {{ isRtl ? 'مميز' : 'Featured' }}
                            </span>
                        </div>
                    </div>
                </div>
                <button @click="deleteComparison" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>

            <!-- View Mode Toggle -->
            <div class="flex items-center justify-center gap-1 bg-white rounded-xl p-1 border border-gray-100 shadow-sm dental-card-enter" style="animation-delay:0.1s">
                <button v-for="mode in [
                    { key: 'slider', icon: 'M8 9l4-4 4 4m0 6l-4 4-4-4', label: isRtl ? 'شريط تمرير' : 'Slider' },
                    { key: 'side', icon: 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7', label: isRtl ? 'جنباً لجنب' : 'Side by Side' },
                    { key: 'before', icon: 'M15 19l-7-7 7-7', label: isRtl ? 'قبل فقط' : 'Before Only' },
                    { key: 'after', icon: 'M9 5l7 7-7 7', label: isRtl ? 'بعد فقط' : 'After Only' },
                ]" :key="mode.key"
                    @click="viewMode = mode.key"
                    :class="viewMode === mode.key ? 'bg-cyan-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-medium transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mode.icon" /></svg>
                    {{ mode.label }}
                </button>
            </div>

            <!-- Image Comparison -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden dental-card-enter" style="animation-delay:0.15s">
                <!-- Slider Mode -->
                <div v-if="viewMode === 'slider'"
                    ref="sliderRef"
                    class="relative aspect-[4/3] md:aspect-[16/9] overflow-hidden cursor-col-resize select-none"
                    @mousedown="startDrag"
                    @touchstart.prevent="startDrag">

                    <!-- After (background) -->
                    <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.after_image_url})` }"></div>
                    <!-- Before (clipped) -->
                    <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.before_image_url})`, clipPath: `inset(0 ${100 - sliderPosition}% 0 0)` }"></div>

                    <!-- Divider -->
                    <div class="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg z-10" :style="{ left: sliderPosition + '%' }">
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center z-20 transition-transform"
                            :class="isDragging ? 'scale-110' : 'hover:scale-110'">
                            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                            </svg>
                        </div>
                    </div>

                    <!-- Labels -->
                    <div class="absolute bottom-4 left-4 z-10"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                    <div class="absolute bottom-4 right-4 z-10"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
                </div>

                <!-- Side by Side -->
                <div v-else-if="viewMode === 'side'" class="grid grid-cols-2 gap-0.5 bg-gray-200">
                    <div class="relative">
                        <img :src="comp.before_image_url" class="w-full aspect-[4/3] object-cover" />
                        <div class="absolute bottom-3 ltr:left-3 rtl:right-3"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                    </div>
                    <div class="relative">
                        <img :src="comp.after_image_url" class="w-full aspect-[4/3] object-cover" />
                        <div class="absolute bottom-3 ltr:left-3 rtl:right-3"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
                    </div>
                </div>

                <!-- Before Only -->
                <div v-else-if="viewMode === 'before'" class="relative">
                    <img :src="comp.before_image_url" class="w-full aspect-[4/3] md:aspect-[16/9] object-cover" />
                    <div class="absolute bottom-4 ltr:left-4 rtl:right-4"><span class="bg-black/60 text-white text-sm font-bold px-4 py-2 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل العلاج' : 'BEFORE TREATMENT' }}</span></div>
                </div>

                <!-- After Only -->
                <div v-else class="relative">
                    <img :src="comp.after_image_url" class="w-full aspect-[4/3] md:aspect-[16/9] object-cover" />
                    <div class="absolute bottom-4 ltr:left-4 rtl:right-4"><span class="bg-emerald-600/80 text-white text-sm font-bold px-4 py-2 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد العلاج' : 'AFTER TREATMENT' }}</span></div>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 dental-card-enter" style="animation-delay:0.2s">
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400">{{ isRtl ? 'المريض' : 'Patient' }}</p>
                    <p v-if="comp.patient" class="text-sm font-semibold text-gray-800 mt-1">{{ comp.patient.full_name }}</p>
                    <p v-if="comp.patient" class="text-xs text-gray-400 font-mono">{{ comp.patient.file_number }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400">{{ isRtl ? 'الطبيب' : 'Doctor' }}</p>
                    <p v-if="comp.doctor" class="text-sm font-semibold text-gray-800 mt-1">{{ isRtl ? comp.doctor.name_ar : comp.doctor.name_en }}</p>
                    <p v-else class="text-sm text-gray-400 mt-1">-</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400">{{ isRtl ? 'الفترة' : 'Duration' }}</p>
                    <p class="text-sm font-semibold text-gray-800 mt-1">{{ formatDate(comp.before_date) }}</p>
                    <p class="text-xs text-cyan-600">→ {{ formatDate(comp.after_date) }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                    <p class="text-xs text-gray-400">{{ isRtl ? 'الأسنان' : 'Teeth' }}</p>
                    <div v-if="comp.tooth_numbers" class="flex flex-wrap gap-1 mt-1">
                        <span v-for="tooth in comp.tooth_numbers.split(',')" :key="tooth"
                            class="bg-cyan-50 text-cyan-700 text-xs font-mono font-bold px-2 py-0.5 rounded">
                            #{{ tooth.trim() }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-gray-400 mt-1">-</p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="comp.description || comp.before_notes || comp.after_notes" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 dental-card-enter" style="animation-delay:0.25s">
                <div v-if="comp.description" class="mb-4">
                    <p class="text-xs text-gray-400 mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</p>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ comp.description }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="comp.before_notes" class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-medium mb-1">{{ isRtl ? 'ملاحظات قبل' : 'Before Notes' }}</p>
                        <p class="text-sm text-gray-700">{{ comp.before_notes }}</p>
                    </div>
                    <div v-if="comp.after_notes" class="bg-emerald-50 rounded-xl p-4">
                        <p class="text-xs text-emerald-600 font-medium mb-1">{{ isRtl ? 'ملاحظات بعد' : 'After Notes' }}</p>
                        <p class="text-sm text-gray-700">{{ comp.after_notes }}</p>
                    </div>
                </div>
            </div>
        </div>
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
