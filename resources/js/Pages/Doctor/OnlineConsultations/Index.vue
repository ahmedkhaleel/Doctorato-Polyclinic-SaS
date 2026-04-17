<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const props = defineProps({
    consultations: Object,
    joinWindowMinutes: { type: Number, default: 15 },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const activeFilter = ref('all');
const filters = computed(() => [
    { id: 'today', label: isRtl.value ? 'اليوم' : 'Today' },
    { id: 'week', label: isRtl.value ? 'هذا الأسبوع' : 'This week' },
    { id: 'all', label: isRtl.value ? 'الكل' : 'All' },
]);

const items = computed(() => props.consultations?.data || []);

const filteredItems = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const weekFromNow = new Date(today);
    weekFromNow.setDate(weekFromNow.getDate() + 7);

    return items.value.filter(c => {
        if (activeFilter.value === 'all') return true;
        const d = new Date(c.scheduled_date?.substring(0, 10));
        if (activeFilter.value === 'today') {
            return d.getTime() === today.getTime();
        }
        if (activeFilter.value === 'week') {
            return d >= today && d <= weekFromNow;
        }
        return true;
    });
});

function statusBadge(status) {
    const map = {
        scheduled: { bg: 'bg-blue-50', text: 'text-blue-700', label: isRtl.value ? 'مجدولة' : 'Scheduled' },
        waiting: { bg: 'bg-amber-50', text: 'text-amber-700', label: isRtl.value ? 'في الانتظار' : 'Waiting' },
        in_progress: { bg: 'bg-emerald-50', text: 'text-emerald-700', label: isRtl.value ? 'جارية' : 'In progress' },
        completed: { bg: 'bg-gray-100', text: 'text-gray-700', label: isRtl.value ? 'مكتملة' : 'Completed' },
        cancelled: { bg: 'bg-red-50', text: 'text-red-600', label: isRtl.value ? 'ملغاة' : 'Cancelled' },
        refunded: { bg: 'bg-red-50', text: 'text-red-600', label: isRtl.value ? 'مُستردة' : 'Refunded' },
        missed_patient: { bg: 'bg-orange-50', text: 'text-orange-600', label: isRtl.value ? 'فوّت المريض' : 'Missed (patient)' },
        missed_doctor: { bg: 'bg-orange-50', text: 'text-orange-600', label: isRtl.value ? 'فوّت الطبيب' : 'Missed (doctor)' },
        technical_issue: { bg: 'bg-gray-50', text: 'text-gray-600', label: isRtl.value ? 'مشكلة تقنية' : 'Technical issue' },
    };
    return map[status] || map.scheduled;
}

function formatDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

function formatTime(t) {
    if (!t) return '';
    return String(t).substring(0, 5);
}

function canJoin(c) {
    if (!['scheduled', 'waiting', 'in_progress'].includes(c.status)) return false;
    if (c.payment_status !== 'paid') return false;
    const now = new Date();
    const dateStr = c.scheduled_date?.substring(0, 10);
    const start = new Date(`${dateStr}T${formatTime(c.start_time)}:00`);
    const end = new Date(`${dateStr}T${formatTime(c.end_time)}:00`);
    const openAt = new Date(start.getTime() - props.joinWindowMinutes * 60000);
    const closeAt = new Date(end.getTime() + 30 * 60000);
    return now >= openAt && now <= closeAt;
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#0f172a] via-[#1B365D] to-[#0f172a] p-6 lg:p-8 mb-6 shadow-lg">
            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[#C4A265] text-xs font-semibold uppercase tracking-widest mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        <span>{{ isRtl ? 'خدمات عن بُعد' : 'Telemedicine' }}</span>
                    </div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white">{{ isRtl ? 'الاستشارات الأونلاين' : 'Online Consultations' }}</h1>
                    <p class="text-white/70 text-sm mt-1 max-w-md">
                        {{ isRtl ? 'جلساتك مع المرضى عن بُعد.' : 'Your remote patient consultations.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Filters + List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex border-b border-gray-100 overflow-x-auto">
                <button
                    v-for="f in filters"
                    :key="f.id"
                    @click="activeFilter = f.id"
                    class="px-5 py-3 text-sm font-semibold border-b-2 transition whitespace-nowrap"
                    :class="activeFilter === f.id
                        ? 'border-[#C4A265] text-[#1B365D]'
                        : 'border-transparent text-gray-400 hover:text-gray-600'"
                >
                    {{ f.label }}
                </button>
            </div>

            <!-- Empty -->
            <div v-if="filteredItems.length === 0" class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-[#1B365D]/10 to-[#C4A265]/10 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-[#1B365D]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-gray-600 font-medium">
                    {{ isRtl ? 'لا توجد استشارات في هذه الفترة' : 'No consultations in this period' }}
                </p>
            </div>

            <!-- List -->
            <div v-else class="divide-y divide-gray-100">
                <div v-for="c in filteredItems" :key="c.id" class="p-4 lg:p-5 flex flex-col md:flex-row md:items-center gap-4 hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#C4A265] flex items-center justify-center text-white font-bold shadow-md overflow-hidden flex-shrink-0">
                            <img v-if="c.patient?.photo" :src="`/storage/${c.patient.photo}`" class="w-full h-full object-cover" :alt="c.patient.full_name" />
                            <span v-else>{{ (c.patient?.full_name || 'P').charAt(0) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 truncate">{{ c.patient?.full_name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ c.patient?.phone }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(c.scheduled_date) }} · {{ formatTime(c.start_time) }} – {{ formatTime(c.end_time) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center flex-wrap gap-2 md:justify-end">
                        <span :class="[statusBadge(c.status).bg, statusBadge(c.status).text]" class="text-[11px] font-semibold px-2.5 py-1 rounded-full">
                            {{ statusBadge(c.status).label }}
                        </span>
                        <span class="text-xs text-gray-500">{{ Number(c.fee || 0).toFixed(0) }} {{ isRtl ? 'ج' : 'EGP' }}</span>

                        <template v-if="canJoin(c)">
                            <a :href="`/doctor/online-consultations/${c.id}/room`"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                {{ isRtl ? 'انضم الآن' : 'Join Now' }}
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="consultations?.links && consultations.last_page > 1" class="flex items-center justify-center gap-1 p-4 border-t border-gray-100">
                <Link
                    v-for="link in consultations.links"
                    :key="link.label"
                    :href="link.url || ''"
                    v-html="link.label"
                    preserve-scroll
                    class="min-w-[32px] h-8 px-2 inline-flex items-center justify-center rounded-md text-xs transition"
                    :class="link.active
                        ? 'bg-[#1B365D] text-white'
                        : link.url
                            ? 'text-gray-600 hover:bg-gray-100'
                            : 'text-gray-300 pointer-events-none'"
                />
            </div>
        </div>
    </div>
</template>
