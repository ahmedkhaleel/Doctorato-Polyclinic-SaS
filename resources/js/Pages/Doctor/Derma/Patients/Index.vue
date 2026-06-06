<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
function apply() {
    router.get(route('doctor.derma.patients.index'), { search: search.value }, { preserveState: true, replace: true });
}
function clearSearch() {
    search.value = '';
    apply();
}

const total = computed(() => props.patients.total ?? props.patients.data.length);
function initials(name) {
    return (name || '?').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('') || '?';
}
function fmtDate(d) {
    if (!d) return null;
    try { return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }); }
    catch { return d; }
}
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Header band -->
        <header class="relative overflow-hidden rounded-3xl px-6 py-7 md:px-8 md:py-8 text-white shadow-lg shadow-violet-900/10"
                style="background: linear-gradient(115deg,#1B365D 0%,#3b2f6b 55%,#8B5CF6 130%)">
            <div class="absolute -top-16 ltr:-right-10 rtl:-left-10 w-56 h-56 rounded-full blur-2xl opacity-30" style="background:#C4A265"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:22px 22px"></div>
            <div class="relative z-10 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p class="text-white/60 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'Dermatology & Cosmetic' }}</p>
                    <h1 class="text-2xl md:text-3xl font-extrabold mt-1.5 flex items-center gap-3">
                        {{ isRtl ? 'سجل المرضى' : 'Patient Registry' }}
                        <span class="text-sm font-bold bg-white/15 backdrop-blur px-3 py-1 rounded-full tabular-nums">{{ total }}</span>
                    </h1>
                    <p class="text-white/70 mt-2 text-sm max-w-md">{{ isRtl ? 'كل من زرت أو سجّلت له جلسة جلدية أو تجميلية.' : 'Everyone you have seen or logged a derma / cosmetic session for.' }}</p>
                </div>
                <Link :href="route('doctor.derma.dashboard')"
                      class="inline-flex items-center gap-2 bg-white/12 hover:bg-white/22 backdrop-blur px-4 py-2.5 rounded-xl text-sm font-semibold transition">
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ isRtl ? 'اللوحة' : 'Dashboard' }}
                </Link>
            </div>
        </header>

        <!-- Search -->
        <div class="relative max-w-md">
            <input v-model="search" @keyup.enter="apply" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم أو الهاتف…' : 'Search by name or phone…'"
                   class="w-full rounded-2xl border border-gray-200 bg-white ps-11 pe-10 py-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-400 transition" />
            <svg class="w-5 h-5 text-gray-400 absolute top-3 ltr:left-3.5 rtl:right-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <button v-if="search" @click="clearSearch" :aria-label="isRtl ? 'مسح' : 'Clear'" :title="isRtl ? 'مسح' : 'Clear'"
                    class="absolute top-3 ltr:right-3.5 rtl:left-3.5 text-gray-300 hover:text-gray-500 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Cards -->
        <div v-if="patients.data.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link v-for="(p, i) in patients.data" :key="p.id" :href="route('doctor.derma.patients.show', p.id)"
                  class="group relative bg-white rounded-2xl border border-gray-100 p-5 shadow-sm hover:shadow-xl hover:shadow-violet-900/5 hover:-translate-y-1 transition-all duration-300 derma-reveal"
                  :style="{ animationDelay: `${Math.min(i, 12) * 45}ms` }">
                <span class="absolute inset-x-0 top-0 h-1 rounded-t-2xl bg-gradient-to-r from-violet-400 to-[#1B365D] opacity-0 group-hover:opacity-100 transition-opacity"></span>
                <div class="flex items-center gap-3.5">
                    <div class="relative shrink-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-[#1B365D] text-white flex items-center justify-center font-bold text-sm shadow-inner">{{ initials(p.full_name) }}</div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-gray-800 group-hover:text-violet-700 transition truncate">{{ p.full_name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ p.phone || '—' }}<span v-if="p.file_number"> · {{ p.file_number }}</span></p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-violet-500 rtl:rotate-180 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div class="mt-4 flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-violet-50 text-violet-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ p.sessions || 0 }} {{ isRtl ? 'جلسة' : 'sessions' }}
                    </span>
                    <span v-if="p.last_visit" class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-lg bg-gray-50 text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ fmtDate(p.last_visit) }}
                    </span>
                </div>
            </Link>
        </div>

        <!-- Empty state (premium) -->
        <div v-else class="relative overflow-hidden bg-white rounded-3xl border border-gray-100 shadow-sm py-16 px-6 text-center">
            <div class="absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle at 1px 1px,#8B5CF6 1px,transparent 0);background-size:20px 20px"></div>
            <div class="relative">
                <div class="mx-auto w-20 h-20 rounded-3xl bg-gradient-to-br from-violet-100 to-violet-50 flex items-center justify-center text-violet-500 shadow-inner">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-gray-800">{{ search ? (isRtl ? 'لا نتائج للبحث' : 'No matching patients') : (isRtl ? 'لا مرضى بعد' : 'No patients yet') }}</h3>
                <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto">{{ search ? (isRtl ? 'جرّب اسماً أو رقم هاتف آخر.' : 'Try a different name or phone number.') : (isRtl ? 'سيظهر المرضى هنا تلقائياً بعد أول زيارة أو جلسة جلدية تسجّلها.' : 'Patients appear here automatically after the first derma visit or session you log.') }}</p>
                <Link :href="route('doctor.derma.dashboard')" class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90 transition" style="background:linear-gradient(120deg,#1B365D,#8B5CF6 200%)">
                    {{ isRtl ? 'الذهاب للوحة' : 'Go to dashboard' }}
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="patients.links && patients.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <template v-for="(l, i) in patients.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label" preserve-scroll
                      class="px-3.5 py-1.5 rounded-lg text-sm transition" :class="l.active ? 'bg-[#1B365D] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" />
                <span v-else v-html="l.label" class="px-3.5 py-1.5 rounded-lg text-sm text-gray-300"></span>
            </template>
        </div>
    </div>
</template>

<style scoped>
@keyframes dermaReveal { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.derma-reveal { animation: dermaReveal .5s cubic-bezier(.22,1,.36,1) both; }
@media (prefers-reduced-motion: reduce) { .derma-reveal { animation: none; } }
</style>
