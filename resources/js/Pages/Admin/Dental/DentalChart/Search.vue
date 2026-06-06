<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: { type: Array, default: () => [] },
    recentCharts: { type: Array, default: () => [] },
    search: { type: String, default: '' },
});

const searchQuery = ref(props.search || '');
const searching = ref(false);
let debounceTimer = null;

watch(searchQuery, (val) => {
    clearTimeout(debounceTimer);
    if (val.length >= 2) {
        searching.value = true;
        debounceTimer = setTimeout(() => {
            router.get('/admin/dental/chart-search', { search: val }, {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => { searching.value = false; },
            });
        }, 400);
    }
});

function openChart(patientId) {
    router.visit(`/admin/dental/chart/${patientId}`);
}
</script>

<template>
    <div class="dental-search max-w-4xl mx-auto py-6 px-4 sm:px-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative mb-8 rounded-2xl overflow-hidden bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-8 text-white shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 w-56 h-56 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-12 start-1/3 w-40 h-40 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <!-- Floating tooth decoration -->
            <div class="pointer-events-none absolute ltr:right-6 rtl:left-6 top-1/2 -translate-y-1/2 opacity-[0.07]">
                <svg class="w-28 h-28 text-white dental-float" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
            </div>
            <div class="relative z-10 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg shadow-black/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'طب الأسنان' : 'DENTAL' }}</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h1>
                    <p class="text-white/70 text-sm mt-1">{{ isRtl ? 'ابحث عن مريض لفتح مخطط أسنانه' : 'Search for a patient to open their dental chart' }}</p>
                </div>
            </div>
        </div>

        <!-- Search Box -->
        <div class="dental-card-enter relative mb-8" style="animation-delay: 0.15s">
            <div class="relative group">
                <svg class="absolute top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-[#C4A265] transition-colors duration-300" :class="isRtl ? 'right-5' : 'left-5'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="searchQuery" type="text"
                    :placeholder="isRtl ? 'ابحث بالاسم أو رقم الهاتف أو رقم الملف...' : 'Search by name, phone, or file number...'"
                    class="doctorato-input w-full border border-gray-200 rounded-2xl py-4 text-base bg-white shadow-sm focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/20 focus:shadow-lg transition-all duration-300"
                    :class="isRtl ? 'pr-13 pl-4' : 'pl-13 pr-4'"
                />
                <div v-if="searching" class="absolute top-1/2 -translate-y-1/2" :class="isRtl ? 'left-5' : 'right-5'">
                    <svg class="w-5 h-5 animate-spin text-[#1B365D]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div v-if="patients.length > 0" class="mb-8">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ isRtl ? 'نتائج البحث' : 'Search Results' }}</h3>
            <div class="space-y-2">
                <button v-for="(patient, index) in patients" :key="patient.id"
                    @click="openChart(patient.id)"
                    class="dental-stagger dental-result w-full flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/50 hover:shadow-md hover:bg-slate-50/30 transition-all group text-start"
                    :style="{ '--i': index }"
                >
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-100 to-slate-50 flex items-center justify-center flex-shrink-0 group-hover:from-slate-200 transition-all">
                        <span v-if="patient.photo" class="w-full h-full rounded-xl overflow-hidden">
                            <img :src="patient.photo_url" class="w-full h-full object-cover" />
                        </span>
                        <span v-else class="text-lg font-bold text-[#1B365D]">{{ patient.full_name?.charAt(0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#1B365D] transition-colors">{{ patient.full_name }}</p>
                        <div class="flex items-center gap-3 mt-0.5">
                            <span class="text-xs text-gray-400">{{ patient.file_number }}</span>
                            <span class="text-xs text-gray-400">{{ patient.phone }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-50 text-[#1B365D] group-hover:bg-[#1B365D] group-hover:text-white transition-all">
                            {{ isRtl ? 'فتح المخطط' : 'Open Chart' }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#1B365D] transition-colors" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- No Results -->
        <div v-else-if="searchQuery.length >= 2 && !searching" class="mb-8 text-center py-12">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <p class="text-gray-400">{{ isRtl ? 'لا توجد نتائج' : 'No patients found' }}</p>
        </div>

        <!-- Recent Charts -->
        <div v-if="recentCharts.length > 0">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ isRtl ? 'آخر المخططات المفتوحة' : 'Recent Charts' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button v-for="(rc, index) in recentCharts" :key="rc.id"
                    @click="openChart(rc.id)"
                    class="dental-stagger flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/50 hover:bg-slate-50/30 hover:-translate-y-0.5 hover:shadow-md transition-all group text-start"
                    :style="{ '--i': index }"
                >
                    <div class="w-10 h-10 rounded-lg bg-slate-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#1B365D] truncate">{{ rc.full_name }}</p>
                        <p class="text-[10px] text-gray-400">{{ rc.file_number }} · {{ rc.teeth_count || 0 }} {{ isRtl ? 'سن مسجل' : 'teeth' }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-[#1B365D]" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!searchQuery && recentCharts.length === 0" class="dental-card-enter text-center py-16" style="animation-delay: 0.25s">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 flex items-center justify-center">
                <svg class="w-10 h-10 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h3>
            <p class="text-sm text-gray-400 max-w-sm mx-auto">{{ isRtl ? 'ابحث عن اسم المريض أو رقم هاتفه أو رقم ملفه للوصول إلى مخطط أسنانه' : 'Search for a patient by name, phone, or file number to access their dental chart' }}</p>
        </div>
    </div>
</template>

<style scoped>
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

/* ── Floating hero tooth ───────────────────────────────── */
@keyframes dentalFloat {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50%      { transform: translateY(-10px) rotate(3deg); }
}
.dental-float { animation: dentalFloat 6s ease-in-out infinite; }

/* ── Staggered list entrance ───────────────────────────── */
.dental-stagger {
    animation: dentalCardEnter 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: calc(var(--i, 0) * 60ms + 100ms);
}

/* ── Brand atmosphere (navy + gold radial mesh) ────────── */
.dental-search { position: relative; }
.dental-search::before {
    content: '';
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background:
        radial-gradient(55% 45% at 90% 0%, rgba(196, 162, 101, 0.07), transparent 60%),
        radial-gradient(45% 40% at 0% 12%, rgba(27, 54, 93, 0.05), transparent 55%);
}
.dental-search > * { position: relative; z-index: 1; }

/* ── Accessibility: honor reduced-motion ───────────────── */
@media (prefers-reduced-motion: reduce) {
    .dental-hero-enter,
    .dental-card-enter,
    .dental-stagger {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    .dental-float { animation: none !important; }
}
</style>
