<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    patient: Object,
    groups: { type: Array, default: () => [] },
    patients: { type: Array, default: () => [] },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const selected = ref(props.patient?.id || '');
function load() {
    router.get('/admin/derma/comparisons', { patient_id: selected.value || undefined }, { preserveState: true, preserveScroll: true });
}

// Slider position per area (0–100), default 50.
const pos = reactive({});
function p(area) { return pos[area] ?? 50; }
function fmtDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="cmp-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="cmp-hero cmp-stagger" style="--i:0">
            <div class="cmp-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                    <div class="cmp-hero-badge">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                            <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'مقارنات قبل / بعد' : 'Before / After' }}</h1>
                    </div>
                </div>
                <select v-model="selected" @change="load" class="cmp-patient-select">
                    <option value="">{{ isRtl ? 'اختر مريضاً…' : 'Select a patient…' }}</option>
                    <option v-for="pt in patients" :key="pt.id" :value="pt.id">{{ pt.full_name }} — {{ pt.file_number || pt.phone }}</option>
                </select>
            </div>
        </div>

        <!-- No patient -->
        <div v-if="!patient" class="cmp-card cmp-stagger text-center py-20 mt-6" style="--i:1">
            <div class="text-5xl mb-3 opacity-40">📸</div>
            <p class="text-sm text-gray-400">{{ isRtl ? 'اختر مريضاً لعرض مقارنات الصور' : 'Select a patient to view photo comparisons' }}</p>
        </div>

        <!-- No photos -->
        <div v-else-if="!groups.length" class="cmp-card cmp-stagger text-center py-20 mt-6" style="--i:1">
            <div class="text-5xl mb-3 opacity-40">🖼️</div>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد صور لهذا المريض' : 'No photos for this patient' }}</p>
        </div>

        <!-- Comparisons by area -->
        <div v-else class="space-y-5 mt-6">
            <div v-for="(g, gi) in groups" :key="g.area" class="cmp-card cmp-stagger" :style="{ '--i': gi + 1 }">
                <h2 class="cmp-h2"><span class="cmp-dot"></span>{{ isRtl ? 'المنطقة' : 'Area' }}: {{ g.area }}</h2>

                <!-- Interactive slider (when both before + after exist) -->
                <div v-if="g.pair.before && g.pair.after" class="cmp-slider" :style="{ '--p': p(g.area) + '%' }">
                    <img :src="g.pair.before.url" class="cmp-img" :alt="'before'" />
                    <div class="cmp-after" :style="{ width: p(g.area) + '%' }">
                        <img :src="g.pair.after.url" class="cmp-img cmp-img-fixed" :alt="'after'" />
                    </div>
                    <div class="cmp-handle" :style="{ insetInlineStart: p(g.area) + '%' }"></div>
                    <span class="cmp-tag cmp-tag-before">{{ isRtl ? 'قبل' : 'BEFORE' }}</span>
                    <span class="cmp-tag cmp-tag-after">{{ isRtl ? 'بعد' : 'AFTER' }}</span>
                    <input type="range" min="0" max="100" :value="p(g.area)" @input="e => pos[g.area] = Number(e.target.value)" class="cmp-range" />
                </div>

                <!-- Otherwise: side-by-side grids -->
                <div v-else class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="cmp-col-label">{{ isRtl ? 'قبل' : 'Before' }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <figure v-for="ph in g.before" :key="ph.id"><img :src="ph.url" class="cmp-thumb" /><figcaption class="cmp-cap">{{ fmtDate(ph.date) }}</figcaption></figure>
                            <p v-if="!g.before.length" class="text-xs text-gray-300 col-span-2">—</p>
                        </div>
                    </div>
                    <div>
                        <p class="cmp-col-label">{{ isRtl ? 'بعد' : 'After' }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            <figure v-for="ph in g.after" :key="ph.id"><img :src="ph.url" class="cmp-thumb" /><figcaption class="cmp-cap">{{ fmtDate(ph.date) }}</figcaption></figure>
                            <p v-if="!g.after.length" class="text-xs text-gray-300 col-span-2">—</p>
                        </div>
                    </div>
                </div>

                <!-- Progress strip -->
                <div v-if="g.progress.length" class="mt-4 pt-3 border-t border-gray-50">
                    <p class="cmp-col-label">{{ isRtl ? 'مراحل التقدّم' : 'Progress' }}</p>
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <figure v-for="ph in g.progress" :key="ph.id" class="flex-shrink-0 w-24"><img :src="ph.url" class="cmp-thumb" /><figcaption class="cmp-cap">{{ fmtDate(ph.date) }}</figcaption></figure>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cmp-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.cmp-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.cmp-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }
.cmp-patient-select { background: rgba(255,255,255,0.92); border: 0; border-radius: 10px; padding: 9px 14px; font-size: 13px; min-width: 240px; }

.cmp-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; padding: 18px; box-shadow: 0 10px 30px -24px rgba(27,54,93,0.2); }
.cmp-h2 { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 800; color: #1B365D; margin-bottom: 14px; }
.cmp-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #C4A265, #8B7043); }

/* Slider compare */
.cmp-slider { position: relative; width: 100%; max-width: 560px; margin: 0 auto; aspect-ratio: 4/3; border-radius: 12px; overflow: hidden; border: 1px solid #eef0f3; user-select: none; }
.cmp-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.cmp-after { position: absolute; inset-block: 0; inset-inline-start: 0; overflow: hidden; }
.cmp-after .cmp-img-fixed { width: 560px; max-width: none; height: 100%; }
.cmp-handle { position: absolute; inset-block: 0; width: 2px; background: #C4A265; box-shadow: 0 0 0 1px rgba(255,255,255,0.6); pointer-events: none; }
.cmp-handle::after { content: '⇆'; position: absolute; top: 50%; inset-inline-start: 50%; transform: translate(-50%, -50%); width: 30px; height: 30px; border-radius: 50%; background: #C4A265; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.25); }
.cmp-range { position: absolute; inset-block-end: 0; inset-inline: 0; width: 100%; opacity: 0; height: 100%; cursor: ew-resize; margin: 0; }
.cmp-tag { position: absolute; top: 8px; font-size: 10px; font-weight: 800; letter-spacing: 0.08em; color: #fff; padding: 2px 8px; border-radius: 6px; pointer-events: none; }
.cmp-tag-before { inset-inline-start: 8px; background: rgba(27,54,93,0.8); }
.cmp-tag-after { inset-inline-end: 8px; background: rgba(196,162,101,0.9); }

.cmp-col-label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; }
.cmp-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 10px; border: 1px solid #eef0f3; }
.cmp-cap { font-size: 10px; color: #9ca3af; text-align: center; margin-top: 2px; }

.cmp-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 60ms + 80ms); }
.is-mounted .cmp-stagger { opacity: 1; transform: translateY(0); }
@media (prefers-reduced-motion: reduce) { .cmp-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
