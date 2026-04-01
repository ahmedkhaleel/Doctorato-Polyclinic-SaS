<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Object,
    filters: Object,
});

const mounted = ref(false);
const search = ref(props.filters?.search || '');
let debounce = null;

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 50);
});

watch(search, (val) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get('/doctor/patients', { search: val || undefined }, { preserveState: true, replace: true });
    }, 300);
});

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
}

const avatarColors = [
    'from-[#C4A265] to-[#A68B52]',
    'from-blue-400 to-blue-600',
    'from-emerald-400 to-emerald-600',
    'from-purple-400 to-purple-600',
    'from-rose-400 to-rose-600',
    'from-cyan-400 to-cyan-600',
];

function getAvatarColor(id) {
    return avatarColors[id % avatarColors.length];
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-8"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1)"
        >
            <div class="absolute top-0 right-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-5">
                    <div>
                        <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ $t('a_patients') }}</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'مرضاي' : 'My Patients' }}</h1>
                        <p class="text-gray-400 text-sm mt-1">{{ isRtl ? 'المرضى الذين عالجتهم' : 'Patients you have treated' }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-white/5 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10 text-center">
                            <p class="text-2xl font-bold text-white">{{ patients.total || 0 }}</p>
                            <p class="text-xs text-gray-400">{{ isRtl ? 'إجمالي المرضى' : 'Total Patients' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Search in Hero -->
                <div class="relative max-w-lg"
                    :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                    style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.15s"
                >
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="isRtl ? 'بحث بالاسم أو الهاتف أو رقم الملف...' : 'Search by name, phone, or file number...'"
                        class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-sm border border-white/15 rounded-xl text-sm text-white placeholder-gray-400 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]/50 focus:bg-white/15 transition-all"
                    />
                </div>
            </div>
        </div>

        <!-- Patients Grid -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden"
            :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            style="transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 0.2s"
        >
            <div v-if="patients.data?.length > 0" class="divide-y divide-gray-100/80">
                <Link v-for="(patient, index) in patients.data" :key="patient.id"
                    :href="`/doctor/patients/${patient.id}`"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-gray-50/60 transition-all duration-200"
                    :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                    :style="{ transition: 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: `${0.25 + index * 0.03}s` }"
                >
                    <!-- Avatar -->
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-sm font-bold flex-shrink-0 transition-transform duration-200 group-hover:scale-105"
                        :class="getAvatarColor(patient.id)"
                    >
                        {{ getInitials(patient.full_name) }}
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-gray-800 truncate group-hover:text-gray-900 transition-colors">{{ patient.full_name }}</p>
                            <span v-if="patient.file_number" class="flex-shrink-0 font-mono text-[10px] text-[#C4A265] bg-[#C4A265]/5 px-1.5 py-0.5 rounded border border-[#C4A265]/10">{{ patient.file_number }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span v-if="patient.phone" class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                {{ patient.phone }}
                            </span>
                            <span v-else class="text-xs text-gray-300">{{ isRtl ? 'لا يوجد هاتف' : 'No phone' }}</span>
                        </div>
                    </div>

                    <!-- Visit Count + Arrow -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <div class="text-center">
                            <span class="inline-flex items-center justify-center min-w-[32px] h-7 px-2.5 rounded-full bg-[#C4A265]/10 text-[#C4A265] text-xs font-bold">
                                {{ patient.visits_count }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ isRtl ? 'زيارات' : 'visits' }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#C4A265] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="py-20 text-center">
                <div class="w-20 h-20 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا يوجد مرضى' : 'No patients found' }}</p>
                <p v-if="search" class="text-xs text-gray-400 mt-1.5">{{ isRtl ? 'جرب مصطلح بحث مختلف' : 'Try a different search term' }}</p>
                <p v-else class="text-xs text-gray-400 mt-1.5">{{ isRtl ? 'سيظهر المرضى هنا بعد أول زيارة لهم معك' : 'Patients will appear here after their first visit with you' }}</p>
            </div>

            <!-- Pagination -->
            <div v-if="patients.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <template v-for="link in patients.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'text-gray-500 hover:bg-gray-100'"
                        v-html="link.label" preserve-state
                    />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
