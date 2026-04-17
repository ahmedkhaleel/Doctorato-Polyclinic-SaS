<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    visitsByDoctor: Object,
    doctors: Array,
    generatedAt: String,
});

const localVisits = ref(props.visitsByDoctor || {});
const localDoctors = ref(props.doctors || []);
const lastUpdated = ref(props.generatedAt || '');
const currentTime = ref(new Date());

let refreshInterval = null;
let clockInterval = null;

onMounted(() => {
    // Auto-refresh queue data every 15 seconds
    refreshInterval = setInterval(fetchData, 15000);
    // Update clock every second
    clockInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockInterval) clearInterval(clockInterval);
});

async function fetchData() {
    try {
        const response = await fetch('/queue-display/data');
        const data = await response.json();
        localVisits.value = data.visitsByDoctor || {};
        localDoctors.value = data.doctors || [];
        lastUpdated.value = data.generatedAt || '';
    } catch (e) {
        console.error('Queue refresh failed:', e);
    }
}

const formattedTime = computed(() => {
    return currentTime.value.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });
});

const formattedDate = computed(() => {
    return currentTime.value.toLocaleDateString('en-GB', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
});

const doctorIds = computed(() => Object.keys(localVisits.value || {}));

function getDoctorInfo(doctorId) {
    return (localDoctors.value || []).find(d => d.id == doctorId) || {};
}

const totalWaiting = computed(() => {
    let count = 0;
    for (const key of doctorIds.value) {
        count += (localVisits.value[key] || []).filter(v => v.status === 'waiting').length;
    }
    return count;
});

const totalInProgress = computed(() => {
    let count = 0;
    for (const key of doctorIds.value) {
        count += (localVisits.value[key] || []).filter(v => v.status === 'in_progress').length;
    }
    return count;
});

const doctorGradients = [
    'from-[#1B365D] to-[#1B365D]',
    'from-emerald-600 to-teal-500',
    'from-[#1B365D] to-[#1B365D]',
    'from-amber-500 to-amber-500',
    'from-[#C4A265] to-[#C4A265]',
    'from-[#1B365D] to-[#1B365D]',
];

function getDoctorGradient(index) {
    return doctorGradients[index % doctorGradients.length];
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden">
        <!-- Header -->
        <header class="px-8 py-5 flex items-center justify-between border-b border-white/10">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center shadow-lg shadow-[#C4A265]/20">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Doctorato Polyclinic</h1>
                    <p class="text-sm text-slate-400">Patient Queue Display</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold tabular-nums tracking-tight text-[#C4A265]">{{ formattedTime }}</div>
                <div class="text-sm text-slate-400 mt-0.5">{{ formattedDate }}</div>
            </div>
        </header>

        <!-- Summary Bar -->
        <div class="px-8 py-4 flex items-center gap-6 border-b border-white/5 bg-white/5">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-sm text-slate-300">In Progress: <strong class="text-emerald-400 text-lg">{{ totalInProgress }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                <span class="text-sm text-slate-300">Waiting: <strong class="text-amber-400 text-lg">{{ totalWaiting }}</strong></span>
            </div>
        </div>

        <!-- Main Content -->
        <main class="p-8">
            <!-- Doctor Columns -->
            <div v-if="doctorIds.length > 0" class="grid gap-6" :class="{
                'grid-cols-1': doctorIds.length === 1,
                'grid-cols-2': doctorIds.length === 2,
                'grid-cols-3': doctorIds.length === 3,
                'grid-cols-4': doctorIds.length >= 4,
            }">
                <div
                    v-for="(doctorId, dIdx) in doctorIds"
                    :key="doctorId"
                    class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden"
                >
                    <!-- Doctor Header -->
                    <div :class="'bg-gradient-to-r ' + getDoctorGradient(dIdx)" class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-full bg-white/20 flex items-center justify-center text-lg font-bold backdrop-blur">
                                {{ (getDoctorInfo(doctorId).name_en || 'D').charAt(0) }}
                            </div>
                            <div>
                                <h2 class="text-lg font-bold">Dr. {{ getDoctorInfo(doctorId).name_en || doctorId }}</h2>
                                <p v-if="getDoctorInfo(doctorId).specialization_en" class="text-xs text-white/70">{{ getDoctorInfo(doctorId).specialization_en }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Patients List -->
                    <div class="p-4 space-y-3">
                        <template v-for="(visit, vIdx) in localVisits[doctorId]" :key="visit.id">
                            <!-- Current Patient (In Progress) -->
                            <div
                                v-if="visit.status === 'in_progress'"
                                class="relative bg-emerald-500/15 border border-emerald-500/30 rounded-xl p-4 overflow-hidden"
                            >
                                <!-- Glow effect -->
                                <div class="absolute inset-0 bg-emerald-500/5 animate-pulse rounded-xl"></div>
                                <div class="relative">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Now Serving</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-xl font-bold text-white">{{ visit.patient?.full_name || '-' }}</p>
                                            <p class="text-sm text-slate-400 mt-0.5">{{ visit.service?.name_en || '-' }}</p>
                                        </div>
                                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Waiting Patient -->
                            <div
                                v-else
                                class="bg-white/5 border border-white/10 rounded-xl p-4 flex items-center gap-4"
                            >
                                <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-amber-400">{{ vIdx + 1 }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-base font-semibold text-white truncate">{{ visit.patient?.full_name || '-' }}</p>
                                    <p class="text-xs text-slate-400">{{ visit.service?.name_en || '-' }}</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                    Waiting
                                </span>
                            </div>
                        </template>

                        <!-- No patients for this doctor -->
                        <div v-if="!localVisits[doctorId] || localVisits[doctorId].length === 0" class="text-center py-8 text-slate-500">
                            <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm">No patients waiting</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State - No Queue -->
            <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center">
                <div class="w-24 h-24 rounded-full bg-[#C4A265]/10 flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white mb-2">Welcome to Doctorato Polyclinic</h2>
                <p class="text-lg text-slate-400">No patients in queue at the moment</p>
                <p class="text-sm text-slate-500 mt-2">The queue will update automatically</p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="fixed bottom-0 left-0 right-0 px-8 py-3 bg-slate-900/80 backdrop-blur border-t border-white/5 flex items-center justify-between">
            <div class="text-xs text-slate-500">
                Auto-refreshing every 15 seconds
            </div>
            <div class="text-xs text-slate-500">
                Last updated: <span class="text-slate-400">{{ lastUpdated }}</span>
            </div>
        </footer>
    </div>
</template>
