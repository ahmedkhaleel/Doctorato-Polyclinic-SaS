<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({ comparison: Object });
const comp = computed(() => props.comparison);

const categoryLabels = {
    orthodontic: { ar: 'تقويم', en: 'Orthodontic' }, cosmetic: { ar: 'تجميلي', en: 'Cosmetic' },
    implant: { ar: 'زراعة', en: 'Implant' }, whitening: { ar: 'تبييض', en: 'Whitening' },
    restoration: { ar: 'ترميم', en: 'Restoration' }, surgical: { ar: 'جراحي', en: 'Surgical' },
    xray: { ar: 'أشعة', en: 'X-ray' }, other: { ar: 'أخرى', en: 'Other' },
};
function categoryLabel(cat) { const l = categoryLabels[cat]; return l ? (isRtl.value ? l.ar : l.en) : cat; }
function formatDate(date) { if (!date) return '-'; return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

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

onMounted(() => { document.addEventListener('mousemove', onDrag); document.addEventListener('mouseup', stopDrag); document.addEventListener('touchmove', onDrag, { passive: false }); document.addEventListener('touchend', stopDrag); });
onUnmounted(() => { document.removeEventListener('mousemove', onDrag); document.removeEventListener('mouseup', stopDrag); document.removeEventListener('touchmove', onDrag); document.removeEventListener('touchend', stopDrag); });
</script>

<template>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-7">
            <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-[#1B365D]/10 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link href="/doctor/dental/comparisons" class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center hover:bg-white/20 transition ring-1 ring-white/15">
                        <svg class="w-5 h-5 text-white rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <div>
                        <p class="text-[#C4A265]/80 text-xs font-semibold tracking-wider uppercase">{{ categoryLabel(comp.category) }}</p>
                        <h1 class="text-xl sm:text-2xl font-bold text-white mt-0.5">{{ isRtl ? (comp.title_ar || comp.title_en || `#${comp.id}`) : (comp.title_en || comp.title_ar || `#${comp.id}`) }}</h1>
                    </div>
                </div>
                <div v-if="comp.patient" class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/10">
                    <p class="text-xs text-gray-400">{{ isRtl ? 'المريض' : 'Patient' }}</p>
                    <p class="text-sm font-semibold text-white">{{ comp.patient.full_name }}</p>
                </div>
            </div>
        </div>

        <!-- View Modes -->
        <div class="dental-card-enter flex items-center justify-center gap-1 bg-white rounded-xl p-1 border border-gray-100 shadow-sm overflow-x-auto scrollbar-none" style="animation-delay:0.1s">
            <button v-for="mode in ['slider', 'side']" :key="mode" @click="viewMode = mode"
                :class="viewMode === mode ? 'bg-[#C4A265] text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'"
                class="px-4 py-2 rounded-lg text-xs font-medium transition-all">
                {{ mode === 'slider' ? (isRtl ? 'شريط تمرير' : 'Slider') : (isRtl ? 'جنباً لجنب' : 'Side by Side') }}
            </button>
        </div>

        <!-- Slider -->
        <div class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden" style="animation-delay:0.15s">
            <div v-if="viewMode === 'slider'" ref="sliderRef" class="relative aspect-[4/3] overflow-hidden cursor-col-resize select-none" @mousedown="startDrag" @touchstart.prevent="startDrag">
                <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.after_image_url})` }"></div>
                <div class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${comp.before_image_url})`, clipPath: `inset(0 ${100 - sliderPosition}% 0 0)` }"></div>
                <div class="absolute top-0 bottom-0 w-0.5 bg-white shadow-lg z-10" :style="{ left: sliderPosition + '%' }">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-12 h-12 bg-white rounded-full shadow-xl flex items-center justify-center z-20" :class="isDragging ? 'scale-110' : ''">
                        <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" /></svg>
                    </div>
                </div>
                <div class="absolute bottom-4 left-4 z-10"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div>
                <div class="absolute bottom-4 right-4 z-10"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-sm">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div>
            </div>
            <div v-else class="grid grid-cols-2 gap-0.5 bg-gray-200">
                <div class="relative"><img :src="comp.before_image_url" class="w-full aspect-[4/3] object-cover" /><div class="absolute bottom-3 left-3"><span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ isRtl ? 'قبل' : 'BEFORE' }}</span></div></div>
                <div class="relative"><img :src="comp.after_image_url" class="w-full aspect-[4/3] object-cover" /><div class="absolute bottom-3 left-3"><span class="bg-emerald-600/80 text-white text-xs font-bold px-3 py-1.5 rounded-full">{{ isRtl ? 'بعد' : 'AFTER' }}</span></div></div>
            </div>
        </div>

        <!-- Details -->
        <div class="dental-card-enter grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4" style="animation-delay:0.2s">
            <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400">{{ isRtl ? 'المريض' : 'Patient' }}</p>
                <p class="text-sm font-semibold text-gray-800 mt-1">{{ comp.patient?.full_name || '-' }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 sm:p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400">{{ isRtl ? 'التاريخ' : 'Period' }}</p>
                <p class="text-sm font-semibold text-gray-800 mt-1">{{ formatDate(comp.before_date) }} → {{ formatDate(comp.after_date) }}</p>
            </div>
            <div v-if="comp.tooth_numbers" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400">{{ isRtl ? 'الأسنان' : 'Teeth' }}</p>
                <div class="flex flex-wrap gap-1 mt-1">
                    <span v-for="t in comp.tooth_numbers.split(',')" :key="t" class="bg-[#C4A265]/10 text-[#C4A265] text-xs font-mono font-bold px-2 py-0.5 rounded">#{{ t.trim() }}</span>
                </div>
            </div>
            <div v-if="comp.treatment_plan" class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400">{{ isRtl ? 'خطة العلاج' : 'Treatment Plan' }}</p>
                <p class="text-sm font-semibold text-gray-800 mt-1">{{ isRtl ? comp.treatment_plan.title_ar : comp.treatment_plan.title_en }}</p>
            </div>
        </div>

        <div v-if="comp.description" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6">
            <p class="text-xs text-gray-400 mb-1">{{ isRtl ? 'الوصف' : 'Description' }}</p>
            <p class="text-sm text-gray-700 leading-relaxed">{{ comp.description }}</p>
        </div>
    </div>
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
