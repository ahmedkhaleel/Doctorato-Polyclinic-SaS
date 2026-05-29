<script setup>
import { computed, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    packages: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    sessions: { type: Array, default: () => [] },
    gallery: { type: Array, default: () => [] },
    conditions: { type: Array, default: () => [] },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const typeLabels = {
    laser: { ar: 'ليزر', en: 'Laser' }, peel: { ar: 'تقشير', en: 'Peel' },
    phototherapy: { ar: 'علاج ضوئي', en: 'Phototherapy' }, injection: { ar: 'حقن', en: 'Injection' },
    cryotherapy: { ar: 'تبريد', en: 'Cryotherapy' }, other: { ar: 'أخرى', en: 'Other' },
};
function typeLabel(t) { return typeLabels[t] ? (isRtl.value ? typeLabels[t].ar : typeLabels[t].en) : t; }

const statusMeta = {
    planned: { ar: 'مخطّطة', en: 'Planned', cls: 'bg-slate-100 text-[#1B365D]' },
    in_progress: { ar: 'قيد التنفيذ', en: 'In Progress', cls: 'bg-amber-50 text-amber-700' },
    completed: { ar: 'مكتملة', en: 'Completed', cls: 'bg-emerald-50 text-emerald-700' },
    cancelled: { ar: 'ملغاة', en: 'Cancelled', cls: 'bg-red-50 text-red-600' },
    active: { ar: 'نشطة', en: 'Active', cls: 'bg-emerald-50 text-emerald-700' },
    expired: { ar: 'منتهية', en: 'Expired', cls: 'bg-amber-50 text-amber-700' },
};
function sLabel(s) { return statusMeta[s] ? (isRtl.value ? statusMeta[s].ar : statusMeta[s].en) : s; }
function sCls(s) { return statusMeta[s]?.cls || 'bg-slate-100 text-gray-600'; }
function fmtDate(d) { if (!d) return '—'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }

const severityMeta = {
    mild: { ar: 'خفيفة', en: 'Mild', cls: 'text-emerald-600' },
    moderate: { ar: 'متوسطة', en: 'Moderate', cls: 'text-amber-600' },
    severe: { ar: 'شديدة', en: 'Severe', cls: 'text-red-600' },
};

const beforeAfter = computed(() => ({
    before: props.gallery.filter(g => g.category === 'before'),
    after: props.gallery.filter(g => g.category === 'after'),
    progress: props.gallery.filter(g => g.category === 'progress'),
}));

const mounted = ref(false);
onMounted(() => { requestAnimationFrame(() => { mounted.value = true; }); });
</script>

<template>
    <div class="pd-root p-4 lg:p-6" :class="{ 'is-mounted': mounted }">
        <!-- Hero -->
        <div class="pd-hero pd-stagger" style="--i:0">
            <div class="pd-hero-orb"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="pd-hero-badge">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Derma & Cosmetic' }}</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">{{ isRtl ? 'علاجاتي الجلدية والتجميلية' : 'My Treatments' }}</h1>
                    <p class="text-xs md:text-sm text-white/70 mt-1">{{ isRtl ? 'باقاتك وكورساتك وجلساتك وصور قبل/بعد' : 'Your packages, courses, sessions and before/after photos' }}</p>
                </div>
            </div>
        </div>

        <!-- My Packages -->
        <section v-if="packages.length" class="pd-card pd-stagger" style="--i:1">
            <h2 class="pd-h2"><span class="pd-dot"></span>{{ isRtl ? 'باقاتي المدفوعة' : 'My Packages' }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div v-for="p in packages" :key="p.id" class="pd-pkg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-bold text-gray-800 text-sm">{{ p.name }}</p>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="sCls(p.status)">{{ sLabel(p.status) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full bg-gradient-to-r from-[#C4A265] to-[#8B7043]" :style="{ width: (p.total ? Math.round(p.used / p.total * 100) : 0) + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-[#1B365D] tabular-nums">{{ p.used }}/{{ p.total }}</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-semibold" :class="p.remaining > 0 ? 'text-emerald-600' : 'text-gray-400'">{{ p.remaining }} {{ isRtl ? 'جلسة متبقية' : 'sessions left' }}</span>
                        <span class="text-gray-400">{{ isRtl ? 'تنتهي' : 'Expires' }}: {{ fmtDate(p.expires_at) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Treatment plans -->
        <section v-if="plans.length" class="pd-card pd-stagger" style="--i:2">
            <h2 class="pd-h2"><span class="pd-dot"></span>{{ isRtl ? 'كورسات العلاج' : 'Treatment Courses' }}</h2>
            <div class="space-y-3">
                <div v-for="p in plans" :key="p.id" class="pd-row">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 text-sm">{{ p.title }}</p>
                            <p class="text-[11px] text-gray-400">{{ typeLabel(p.type) }} · {{ isRtl ? 'بدأ' : 'started' }} {{ fmtDate(p.start_date) }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold" :class="sCls(p.status)">{{ sLabel(p.status) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full bg-gradient-to-r from-[#1B365D] to-[#22406F]" :style="{ width: p.progress + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-[#1B365D] tabular-nums">{{ p.completed }}/{{ p.total }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Before / After gallery -->
        <section v-if="gallery.length" class="pd-card pd-stagger" style="--i:3">
            <h2 class="pd-h2"><span class="pd-dot"></span>{{ isRtl ? 'صور قبل / بعد' : 'Before / After' }}</h2>
            <div v-if="beforeAfter.before.length || beforeAfter.after.length" class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'قبل' : 'Before' }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        <img v-for="(g, i) in beforeAfter.before" :key="'b'+i" :src="g.url" class="pd-photo" loading="lazy" />
                        <p v-if="!beforeAfter.before.length" class="text-xs text-gray-300 col-span-2">—</p>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'بعد' : 'After' }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        <img v-for="(g, i) in beforeAfter.after" :key="'a'+i" :src="g.url" class="pd-photo" loading="lazy" />
                        <p v-if="!beforeAfter.after.length" class="text-xs text-gray-300 col-span-2">—</p>
                    </div>
                </div>
            </div>
            <div v-if="beforeAfter.progress.length">
                <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'مراحل التقدّم' : 'Progress' }}</p>
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <img v-for="(g, i) in beforeAfter.progress" :key="'p'+i" :src="g.url" class="pd-photo" loading="lazy" />
                </div>
            </div>
        </section>

        <!-- Session history -->
        <section v-if="sessions.length" class="pd-card pd-stagger" style="--i:4">
            <h2 class="pd-h2"><span class="pd-dot"></span>{{ isRtl ? 'سجل الجلسات' : 'Session History' }}</h2>
            <div class="space-y-2">
                <div v-for="(s, i) in sessions" :key="i" class="pd-session">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" :class="s.completed ? 'bg-emerald-500' : 'bg-amber-400'"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">
                            {{ s.kind === 'derma' ? typeLabel(s.type) : s.type }}
                            <span v-if="s.area" class="text-gray-400 font-normal">— {{ s.area }}</span>
                        </p>
                        <p class="text-[11px] text-gray-400">
                            <span v-if="s.doctor">{{ s.doctor }} · </span>{{ fmtDate(s.date) }}
                        </p>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold"
                          :class="s.kind === 'derma' ? 'bg-[#1B365D]/8 text-[#1B365D]' : 'bg-[#C4A265]/15 text-[#8B7043]'">
                        {{ s.kind === 'derma' ? (isRtl ? 'جلدية' : 'Derma') : (isRtl ? 'تجميل' : 'Cosmetic') }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Active conditions -->
        <section v-if="conditions.length" class="pd-card pd-stagger" style="--i:5">
            <h2 class="pd-h2"><span class="pd-dot"></span>{{ isRtl ? 'الحالات النشطة' : 'Active Conditions' }}</h2>
            <div class="flex flex-wrap gap-2">
                <span v-for="(c, i) in conditions" :key="i" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-gray-100 text-sm">
                    <span class="font-medium text-gray-700">{{ c.name }}</span>
                    <span v-if="c.area" class="text-[11px] text-gray-400">{{ c.area }}</span>
                    <span class="text-[11px] font-semibold" :class="severityMeta[c.severity]?.cls">{{ severityMeta[c.severity] ? (isRtl ? severityMeta[c.severity].ar : severityMeta[c.severity].en) : c.severity }}</span>
                </span>
            </div>
        </section>

        <!-- Empty -->
        <div v-if="!packages.length && !plans.length && !sessions.length && !gallery.length && !conditions.length"
             class="pd-card pd-stagger text-center py-16" style="--i:1">
            <div class="text-5xl mb-3 opacity-40">🧖</div>
            <p class="text-sm text-gray-400">{{ isRtl ? 'لا توجد سجلات جلدية أو تجميلية بعد' : 'No dermatology or cosmetic records yet' }}</p>
        </div>
    </div>
</template>

<style scoped>
.pd-hero { position: relative; overflow: hidden; border-radius: 1rem; padding: 22px 24px; margin-bottom: 20px; background: linear-gradient(135deg, #1B365D 0%, #1B365D 45%, #0F2444 100%); box-shadow: 0 18px 40px -20px rgba(27,54,93,0.5); }
.pd-hero-orb { position: absolute; top: -80px; inset-inline-end: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(196,162,101,0.25), transparent 70%); filter: blur(20px); pointer-events: none; }
.pd-hero-badge { width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0; background: linear-gradient(135deg, #C4A265, #8B7043); display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(196,162,101,0.35); }

.pd-card { background: #fff; border: 1px solid #eef0f3; border-radius: 14px; padding: 18px; margin-bottom: 16px; box-shadow: 0 10px 30px -24px rgba(27,54,93,0.2); }
.pd-h2 { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 800; color: #1B365D; margin-bottom: 14px; }
.pd-dot { width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #C4A265, #8B7043); }

.pd-pkg { border: 1px solid rgba(196,162,101,0.25); border-radius: 12px; padding: 14px; background: linear-gradient(120deg, #FAF7F0, #fff); }
.pd-row { border: 1px solid #f1f2f4; border-radius: 12px; padding: 12px 14px; }
.pd-session { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; transition: background 0.15s; }
.pd-session:hover { background: #f8fafc; }
.pd-photo { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 10px; border: 1px solid #eef0f3; }

.pd-stagger { opacity: 0; transform: translateY(14px); transition: opacity 0.6s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94); transition-delay: calc(var(--i,0) * 80ms + 80ms); }
.is-mounted .pd-stagger { opacity: 1; transform: translateY(0); }

@media (prefers-reduced-motion: reduce) { .pd-stagger { transition-duration: 0.01s; transition-delay: 0s; } }
</style>
