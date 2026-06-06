<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();
defineOptions({ layout: PatientLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ comparison: Object });
const comp = computed(() => props.comparison);

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

// Slider logic
const sliderPosition = ref(50);
const sliderRef = ref(null);
const isDragging = ref(false);
const viewMode = ref('slider');

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
</script>

<template>
    <div class="max-w-3xl mx-auto space-y-5">
        <!-- Header -->
        <div class="flex items-center gap-3 comp-hero-enter">
            <Link :href="lp('/dental/comparisons')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-800 truncate">
                    {{ isRtl ? (comp.title_ar || comp.title_en || categoryLabel(comp.category)) : (comp.title_en || comp.title_ar || categoryLabel(comp.category)) }}
                </h1>
                <div class="flex items-center gap-2 mt-0.5">
                    <span :class="'bg-gradient-to-r ' + (categoryColors[comp.category] || 'from-gray-500 to-gray-600')"
                        class="text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full">{{ categoryLabel(comp.category) }}</span>
                    <span v-if="comp.before_date && comp.after_date" class="text-xs text-gray-400">
                        {{ formatDate(comp.before_date) }} → {{ formatDate(comp.after_date) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- View Mode Toggle -->
        <div class="flex items-center justify-center gap-1 bg-white rounded-xl p-1 border border-gray-100 shadow-sm comp-card-enter" style="animation-delay:0.05s">
            <button @click="viewMode = 'slider'"
                :class="viewMode === 'slider' ? 'bg-[var(--brand-primary)] text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-medium transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                {{ isRtl ? 'شريط تمرير' : 'Slider' }}
            </button>
            <button @click="viewMode = 'side'"
                :class="viewMode === 'side' ? 'bg-[var(--brand-primary)] text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-medium transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7" /></svg>
                {{ isRtl ? 'جنباً لجنب' : 'Side by Side' }}
            </button>
            <button @click="viewMode = 'before'"
                :class="viewMode === 'before' ? 'bg-gray-800 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                class="px-4 py-2 rounded-lg text-xs font-medium transition-all">
                {{ isRtl ? 'قبل' : 'Before' }}
            </button>
            <button @click="viewMode = 'after'"
                :class="viewMode === 'after' ? 'bg-emerald-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                class="px-4 py-2 rounded-lg text-xs font-medium transition-all">
                {{ isRtl ? 'بعد' : 'After' }}
            </button>
        </div>

        <!-- Interactive Slider View -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden comp-card-enter" style="animation-delay:0.1s">
            <!-- Slider Mode -->
            <div v-if="viewMode === 'slider'" ref="sliderRef"
                class="relative aspect-[4/3] overflow-hidden cursor-col-resize select-none"
                @mousedown="startDrag" @touchstart.prevent="startDrag">
                <!-- After (background) -->
                <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.after_image_url})` }"></div>
                <!-- Before (clipped) -->
                <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.before_image_url})`, clipPath: `inset(0 ${100 - sliderPosition}% 0 0)` }"></div>
                <!-- Slider line -->
                <div class="absolute top-0 bottom-0 w-0.5 bg-white/90 shadow-lg z-10" :style="{ left: sliderPosition + '%' }">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 bg-white rounded-full shadow-xl flex items-center justify-center z-20 transition-transform"
                        :class="isDragging ? 'scale-110' : ''">
                        <svg class="w-5 h-5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                    </div>
                </div>
                <!-- Labels -->
                <div class="absolute bottom-3 left-3 z-10"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                <div class="absolute bottom-3 right-3 z-10"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
                <!-- Drag hint -->
                <div v-if="!isDragging" class="absolute top-3 left-1/2 -translate-x-1/2 z-10">
                    <span class="bg-black/40 text-white text-[10px] font-medium px-3 py-1 rounded-full backdrop-blur-sm">
                        {{ isRtl ? '← اسحب للمقارنة →' : '← Drag to compare →' }}
                    </span>
                </div>
            </div>

            <!-- Side by Side -->
            <div v-else-if="viewMode === 'side'" class="grid grid-cols-2 gap-0.5 bg-gray-200">
                <div class="relative">
                    <img :src="comp.before_image_url" class="w-full aspect-[4/3] object-cover" alt="" />
                    <div class="absolute bottom-3 left-3"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                </div>
                <div class="relative">
                    <img :src="comp.after_image_url" class="w-full aspect-[4/3] object-cover" alt="" />
                    <div class="absolute bottom-3 left-3"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
                </div>
            </div>

            <!-- Before Only -->
            <div v-else-if="viewMode === 'before'" class="relative">
                <img :src="comp.before_image_url" class="w-full aspect-[4/3] object-cover" alt="" />
                <div class="absolute bottom-3 left-3"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
            </div>

            <!-- After Only -->
            <div v-else class="relative">
                <img :src="comp.after_image_url" class="w-full aspect-[4/3] object-cover" alt="" />
                <div class="absolute bottom-3 left-3"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
            </div>
        </div>

        <!-- Details Cards -->
        <div class="grid grid-cols-2 gap-3 comp-card-enter" style="animation-delay:0.15s">
            <div v-if="comp.doctor" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">{{ isRtl ? 'الطبيب المعالج' : 'Doctor' }}</p>
                <div class="flex items-center gap-2 mt-1.5">
                    <div class="w-7 h-7 bg-[var(--brand-primary)]/10 rounded-full flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-[var(--brand-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">{{ isRtl ? comp.doctor.name_ar : comp.doctor.name_en }}</p>
                </div>
            </div>
            <div v-if="comp.before_date && comp.after_date" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">{{ isRtl ? 'المدة' : 'Duration' }}</p>
                <p class="text-sm font-semibold text-gray-800 mt-1.5">{{ formatDate(comp.before_date) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">→ {{ formatDate(comp.after_date) }}</p>
            </div>
            <div v-if="comp.tooth_numbers" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm col-span-2">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'الأسنان المعالجة' : 'Treated Teeth' }}</p>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="t in comp.tooth_numbers.split(',')" :key="t"
                        class="bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] text-xs font-mono font-bold px-2.5 py-1 rounded-lg">#{{ t.trim() }}</span>
                </div>
            </div>
        </div>

        <!-- Treatment Plan Link -->
        <div v-if="comp.treatment_plan" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm comp-card-enter" style="animation-delay:0.2s">
            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'خطة العلاج' : 'Treatment Plan' }}</p>
            <Link :href="lp('/dental/treatment-plans/' + comp.treatment_plan.id)"
                class="flex items-center gap-3 group">
                <div class="w-9 h-9 bg-slate-50 rounded-lg flex items-center justify-center group-hover:bg-slate-100 transition">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-[#1B365D] transition">
                        {{ isRtl ? comp.treatment_plan.title_ar : comp.treatment_plan.title_en }}
                    </p>
                    <p class="text-[11px] text-gray-400">{{ isRtl ? 'عرض خطة العلاج' : 'View treatment plan' }} →</p>
                </div>
            </Link>
        </div>

        <!-- Description -->
        <div v-if="comp.description" class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm comp-card-enter" style="animation-delay:0.25s">
            <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">{{ isRtl ? 'ملاحظات الطبيب' : 'Doctor Notes' }}</p>
            <p class="text-sm text-gray-700 leading-relaxed">{{ comp.description }}</p>
        </div>

        <!-- Back Button -->
        <div class="flex justify-center pt-2 comp-card-enter" style="animation-delay:0.3s">
            <Link :href="lp('/dental/comparisons')"
                class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-500 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-700 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                {{ isRtl ? 'العودة للقائمة' : 'Back to Gallery' }}
            </Link>
        </div>
    </div>
</template>

<style>
@keyframes compHeroEnter {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes compCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.comp-hero-enter { animation: compHeroEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
.comp-card-enter { animation: compCardEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
</style>
