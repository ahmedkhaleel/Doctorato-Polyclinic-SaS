<script setup>
import { computed, ref, onMounted, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    consents: { type: Array, default: () => [] },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function fmtDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

// ─── Signature pad ─────────────────────────────────
const signing = ref(null); // the consent being signed
const canvasRef = ref(null);
const hasDrawn = ref(false);
const saving = ref(false);
let ctx = null, drawing = false;

async function openSign(c) {
    signing.value = c;
    hasDrawn.value = false;
    await nextTick();
    const canvas = canvasRef.value;
    if (!canvas) return;
    // Fit the backing store to the displayed size (sharp lines).
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#1B365D';
}
function pointerPos(e) {
    const r = canvasRef.value.getBoundingClientRect();
    const t = e.touches ? e.touches[0] : e;
    return { x: t.clientX - r.left, y: t.clientY - r.top };
}
function start(e) { e.preventDefault(); drawing = true; const p = pointerPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
function move(e) { if (!drawing) return; e.preventDefault(); const p = pointerPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasDrawn.value = true; }
function end() { drawing = false; }
function clearPad() { if (ctx && canvasRef.value) ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height); hasDrawn.value = false; }

function saveSignature() {
    if (!hasDrawn.value || !signing.value) return;
    saving.value = true;
    const dataUrl = canvasRef.value.toDataURL('image/png');
    router.post(`/${page.props.locale || 'ar'}/patient/derma/consents/${signing.value.id}/sign`, { signature: dataUrl }, {
        preserveScroll: true,
        onSuccess: () => { signing.value = null; },
        onFinish: () => { saving.value = false; },
    });
}

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="pc-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="pc-hero pc-stagger" style="--i:0">
            <div class="pc-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="pc-hero-badge">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'الموافقات والتوقيع' : 'Consents & Signature' }}</h1>
                </div>
            </div>
        </div>

        <div v-if="!consents.length" class="pc-card pc-stagger text-center py-20 mt-6" style="--i:1">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد موافقات حالياً' : 'No consents on file' }}</p>
        </div>

        <div v-else class="space-y-3 mt-6">
            <div v-for="(c, i) in consents" :key="c.id" class="pc-card pc-stagger" :style="{ '--i': i + 1 }">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-800">{{ c.title }}</p>
                        <p v-if="c.procedure" class="text-[11px] text-gray-400">{{ c.procedure }}</p>
                    </div>
                    <span v-if="c.signed" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        {{ isRtl ? 'موقّعة' : 'Signed' }}
                    </span>
                    <span v-else class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">{{ isRtl ? 'بانتظار التوقيع' : 'Pending' }}</span>
                </div>
                <p v-if="c.text" class="text-xs text-gray-600 leading-relaxed whitespace-pre-line max-h-32 overflow-y-auto bg-slate-50 rounded-lg p-3">{{ c.text }}</p>

                <div class="flex items-center justify-between mt-3">
                    <div v-if="c.signed" class="flex items-center gap-3">
                        <img v-if="c.signature" :src="c.signature" class="h-12 border border-gray-100 rounded bg-white" />
                        <span class="text-[11px] text-gray-400">{{ isRtl ? 'وُقّعت في' : 'Signed on' }} {{ fmtDate(c.signed_at) }}</span>
                    </div>
                    <button v-else @click="openSign(c)" class="pc-sign-btn">{{ isRtl ? '✍ التوقيع الآن' : '✍ Sign now' }}</button>
                </div>
            </div>
        </div>

        <!-- Signature modal -->
        <Teleport to="body">
            <Transition name="pc-modal">
                <div v-if="signing" v-focus-trap="() => (signing = false)" role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-[#0F2444]/50 backdrop-blur-sm" @click="signing = null"></div>
                    <div class="pc-dialog relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-br from-[#1B365D] to-[#0F2444]">
                            <h3 class="text-white font-bold">{{ isRtl ? 'التوقيع الإلكتروني' : 'E-Signature' }}</h3>
                            <p class="text-white/70 text-xs mt-0.5">{{ signing.title }}</p>
                        </div>
                        <div class="p-6">
                            <p class="text-xs text-gray-500 mb-2">{{ isRtl ? 'وقّع داخل الإطار أدناه:' : 'Sign inside the frame below:' }}</p>
                            <div class="pc-pad">
                                <canvas ref="canvasRef"
                                        @pointerdown="start" @pointermove="move" @pointerup="end" @pointerleave="end"
                                        @touchstart="start" @touchmove="move" @touchend="end"></canvas>
                                <span v-if="!hasDrawn" class="pc-pad-hint">{{ isRtl ? '✍ وقّع هنا' : '✍ Sign here' }}</span>
                            </div>
                            <div class="flex justify-between items-center mt-4">
                                <button @click="clearPad" class="text-xs font-semibold text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition">{{ isRtl ? 'مسح' : 'Clear' }}</button>
                                <div class="flex gap-2">
                                    <button @click="signing = null" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">{{ isRtl ? 'إلغاء' : 'Cancel' }}</button>
                                    <button @click="saveSignature" :disabled="!hasDrawn || saving" class="pc-save">{{ saving ? (isRtl ? 'جارٍ...' : '...') : (isRtl ? 'اعتماد التوقيع' : 'Confirm') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.pc-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.pc-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.pc-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }

.pc-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; padding: 16px; box-shadow: 0 10px 30px -24px rgba(27,54,93,0.2); }
.pc-sign-btn { padding: 8px 18px; border-radius: 10px; background: linear-gradient(135deg, #C4A265, #8B7043); color: #fff; font-size: 13px; font-weight: 700; box-shadow: 0 8px 20px -8px rgba(196,162,101,0.6); }

.pc-pad { position: relative; border: 2px dashed #cbd5e1; border-radius: 12px; background: #fff; height: 180px; touch-action: none; }
.pc-pad canvas { width: 100%; height: 100%; display: block; }
.pc-pad-hint { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 14px; pointer-events: none; }
.pc-save { padding: 8px 18px; border-radius: 10px; background: linear-gradient(135deg, #1B365D, #22406F); color: #fff; font-size: 13px; font-weight: 700; }
.pc-save:disabled { opacity: 0.5; cursor: not-allowed; }

.pc-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 60ms + 80ms); }
.is-mounted .pc-stagger { opacity: 1; transform: translateY(0); }
.pc-modal-enter-active, .pc-modal-leave-active { transition: opacity 0.25s ease; }
.pc-modal-enter-from, .pc-modal-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) { .pc-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
