<script setup>
/**
 * BodyMap — anterior/posterior body silhouette for plotting lesions (chart kit,
 * CV-3). Read-only display of markers, or `editable` capture mode (click the
 * body → emit add at normalized 0–100 coords). Front/back toggle. Brand-themed,
 * RTL-aware container, marker fade-in (reduced-motion aware).
 *
 * lesions: [{ id, view, x, y, lesion_type, size_mm, note }]
 * v-model:view  ('front' | 'back')
 * emits: add({x,y,view}) · select(lesion)
 */
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    lesions: { type: Array, default: () => [] },
    view: { type: String, default: 'front' },
    editable: { type: Boolean, default: false },
    accent: { type: String, default: '#8B5CF6' },
    isRtl: { type: Boolean, default: true },
    height: { type: Number, default: 320 },
    selectedId: { type: [Number, String], default: null },
});
const emit = defineEmits(['add', 'select', 'update:view']);

const VB_W = 100;
const VB_H = 200;
const svgRef = ref(null);

const shown = computed(() => props.lesions.filter(l => l.view === props.view));

function onClick(e) {
    if (!props.editable) return;
    const rect = svgRef.value.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    emit('add', { x: Math.round(x * 10) / 10, y: Math.round(y * 10) / 10, view: props.view });
}

const drawn = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { drawn.value = true; return; }
    requestAnimationFrame(() => requestAnimationFrame(() => { drawn.value = true; }));
});

function tip(l) {
    return [l.lesion_type, l.size_mm ? `${l.size_mm}mm` : null, l.note].filter(Boolean).join(' · ') || (props.isRtl ? 'آفة' : 'lesion');
}
</script>

<template>
    <div style="direction: ltr">
        <!-- view toggle -->
        <div class="flex items-center justify-center gap-1 mb-2" style="direction: ltr">
            <button v-for="v in ['front', 'back']" :key="v" type="button"
                @click="emit('update:view', v)"
                class="px-3 py-1 text-[11px] font-semibold rounded-lg transition-all"
                :class="view === v ? 'text-white' : 'text-gray-500 bg-gray-100 hover:bg-gray-200'"
                :style="view === v ? `background:${accent}` : ''">
                {{ v === 'front' ? (isRtl ? 'أمامي' : 'Front') : (isRtl ? 'خلفي' : 'Back') }}
            </button>
        </div>

        <svg ref="svgRef" :viewBox="`0 0 ${VB_W} ${VB_H}`" :height="height" class="mx-auto block"
            :class="editable ? 'cursor-crosshair' : ''" @click="onClick"
            role="img" :aria-label="isRtl ? 'خريطة الجسم' : 'Body map'">
            <!-- silhouette -->
            <g fill="#E8ECF3" stroke="#CBD5E1" stroke-width="0.8">
                <circle cx="50" cy="20" r="13" />
                <rect x="44" y="31" width="12" height="9" rx="3" />
                <rect x="34" y="39" width="32" height="74" rx="14" />
                <rect x="18" y="45" width="11" height="64" rx="5.5" />
                <rect x="71" y="45" width="11" height="64" rx="5.5" />
                <rect x="36" y="110" width="12" height="82" rx="6" />
                <rect x="52" y="110" width="12" height="82" rx="6" />
            </g>
            <!-- back-view spine hint -->
            <line v-if="view === 'back'" x1="50" y1="44" x2="50" y2="110" stroke="#CBD5E1" stroke-width="0.8" stroke-dasharray="2 2" />

            <!-- lesion markers -->
            <g v-for="l in shown" :key="l.id ?? (l.x + '-' + l.y)">
                <circle :cx="l.x" :cy="l.y * 2" :r="String(l.id) === String(selectedId) ? 4 : 3"
                    :fill="accent" fill-opacity="0.85" stroke="#fff" stroke-width="1"
                    :style="`cursor:pointer; opacity:${drawn ? 1 : 0}; transition: opacity .4s ease`"
                    @click.stop="emit('select', l)">
                    <title>{{ tip(l) }}</title>
                </circle>
            </g>
        </svg>

        <p v-if="editable" class="text-center text-[10px] text-gray-400 mt-1">{{ isRtl ? 'اضغط على الجسم لإضافة آفة' : 'Tap the body to add a lesion' }}</p>
    </div>
</template>
