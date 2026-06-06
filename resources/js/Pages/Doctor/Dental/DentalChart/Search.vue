<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

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
            router.get('/doctor/dental/chart-search', { search: val }, {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => { searching.value = false; },
            });
        }, 400);
    }
});

function openChart(patientId) {
    router.visit(`/doctor/dental/chart/${patientId}`);
}
</script>

<template>
    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative mb-8 rounded-2xl overflow-hidden bg-gradient-to-br from-[#1a1a2e] via-[#16213e] to-[#0f3460] p-5 sm:p-8 text-white shadow-xl">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 400 200"><path d="M200,20 C180,20 170,40 165,60 C160,80 150,90 140,100 C130,110 120,130 120,150 C120,170 135,190 155,190 C170,190 180,180 185,170 C190,160 195,155 200,155 C205,155 210,160 215,170 C220,180 230,190 245,190 C265,190 280,170 280,150 C280,130 270,110 260,100 C250,90 240,80 235,60 C230,40 220,20 200,20Z" fill="currentColor" /></svg>
            </div>
            <div class="relative z-10">
                <h1 class="text-2xl font-bold mb-1">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h1>
                <p class="text-white/60 text-sm">{{ isRtl ? 'ابحث عن مريض لفتح مخطط أسنانه' : 'Search for a patient to open their dental chart' }}</p>
            </div>
        </div>

        <!-- Search Box -->
        <div class="dental-card-enter relative mb-8" style="animation-delay:0.1s">
            <div class="relative">
                <svg class="absolute top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" :class="isRtl ? 'right-4' : 'left-4'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="searchQuery" type="text"
                    :placeholder="isRtl ? 'ابحث بالاسم أو رقم الهاتف أو رقم الملف...' : 'Search by name, phone, or file number...'"
                    class="doctorato-input w-full border-2 border-gray-200 rounded-2xl py-4 text-base focus:border-[#C4A265] focus:ring-[#C4A265]/20 focus:ring-4 transition-all shadow-sm"
                    :class="isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4'"
                />
                <div v-if="searching" class="absolute top-1/2 -translate-y-1/2" :class="isRtl ? 'left-4' : 'right-4'">
                    <svg class="w-5 h-5 animate-spin text-[#C4A265]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div v-if="patients.length > 0" class="dental-card-enter mb-8" style="animation-delay:0.15s">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ isRtl ? 'نتائج البحث' : 'Search Results' }}</h3>
            <div class="space-y-2">
                <button v-for="patient in patients" :key="patient.id"
                    @click="openChart(patient.id)"
                    class="w-full flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-200 hover:border-[#C4A265]/40 hover:shadow-md hover:bg-[#C4A265]/5 transition-all group text-start"
                >
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 flex items-center justify-center flex-shrink-0 group-hover:from-[#C4A265]/30 transition-all">
                        <span v-if="patient.photo" class="w-full h-full rounded-xl overflow-hidden">
                            <img :src="patient.photo_url" class="w-full h-full object-cover" alt="" />
                        </span>
                        <span v-else class="text-lg font-bold text-[#C4A265]">{{ patient.full_name?.charAt(0) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-[#C4A265] transition-colors">{{ patient.full_name }}</p>
                        <div class="flex items-center gap-3 mt-0.5">
                            <span class="text-xs text-gray-400">{{ patient.file_number }}</span>
                            <span class="text-xs text-gray-400">{{ patient.phone }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#C4A265]/10 text-[#C4A265] group-hover:bg-[#C4A265] group-hover:text-white transition-all">
                            {{ isRtl ? 'فتح المخطط' : 'Open Chart' }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] transition-colors" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- No Results -->
        <div v-else-if="searchQuery.length >= 2 && !searching" class="mb-8 text-center py-12">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <p class="text-gray-400">{{ isRtl ? 'لا توجد نتائج' : 'No patients found' }}</p>
        </div>

        <!-- Recent Charts / Quick Access -->
        <div v-if="recentCharts.length > 0" class="dental-card-enter" style="animation-delay:0.2s">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ isRtl ? 'آخر المخططات المفتوحة' : 'Recent Charts' }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button v-for="rc in recentCharts" :key="rc.id"
                    @click="openChart(rc.id)"
                    class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-slate-200 hover:bg-slate-50/30 transition-all group text-start"
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

        <!-- Empty State (no search, no recent) -->
        <div v-if="!searchQuery && recentCharts.length === 0" class="dental-card-enter text-center py-16" style="animation-delay:0.15s">
            <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-[#C4A265]/10 to-[#C4A265]/5 flex items-center justify-center">
                <svg class="w-10 h-10 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3C10.5 3 9 4.5 8.5 6.5C8 8.5 7 9.5 6 10.5C5 11.5 4 13 4 15C4 17 5.5 19 7.5 19C9 19 10 18 10.5 17C11 16 11.5 15.5 12 15.5C12.5 15.5 13 16 13.5 17C14 18 15 19 16.5 19C18.5 19 20 17 20 15C20 13 19 11.5 18 10.5C17 9.5 16 8.5 15.5 6.5C15 4.5 13.5 3 12 3Z" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">{{ isRtl ? 'مخطط الأسنان' : 'Dental Chart' }}</h3>
            <p class="text-sm text-gray-400 max-w-sm mx-auto">{{ isRtl ? 'ابحث عن اسم المريض أو رقم هاتفه أو رقم ملفه للوصول إلى مخطط أسنانه' : 'Search for a patient by name, phone, or file number to access their dental chart' }}</p>
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
