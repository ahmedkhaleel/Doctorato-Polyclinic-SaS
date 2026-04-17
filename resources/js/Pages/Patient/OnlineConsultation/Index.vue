<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    consultations: Object,
    joinWindowMinutes: { type: Number, default: 15 },
    cancellationHours: { type: Number, default: 24 },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

function lp(path) { return `/${locale.value}/patient${path}`; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || '';
}

const activeTab = ref('upcoming');
const tabs = computed(() => [
    { id: 'upcoming', label: isRtl.value ? 'القادمة' : 'Upcoming' },
    { id: 'past', label: isRtl.value ? 'السابقة' : 'Past' },
    { id: 'cancelled', label: isRtl.value ? 'الملغاة' : 'Cancelled' },
]);

const items = computed(() => props.consultations?.data || []);
const filteredItems = computed(() => {
    return items.value.filter(c => {
        const s = c.status;
        if (activeTab.value === 'upcoming') return ['scheduled', 'waiting', 'in_progress'].includes(s);
        if (activeTab.value === 'cancelled') return ['cancelled', 'refunded'].includes(s);
        // past
        return ['completed', 'missed_patient', 'missed_doctor', 'technical_issue'].includes(s);
    });
});

function statusBadge(status) {
    const map = {
        scheduled: { bg: 'bg-slate-50', text: 'text-[#1B365D]', label: isRtl.value ? 'مجدولة' : 'Scheduled' },
        waiting: { bg: 'bg-amber-50', text: 'text-amber-700', label: isRtl.value ? 'في الانتظار' : 'Waiting' },
        in_progress: { bg: 'bg-emerald-50', text: 'text-emerald-700', label: isRtl.value ? 'جارية' : 'In progress' },
        completed: { bg: 'bg-gray-100', text: 'text-gray-700', label: isRtl.value ? 'مكتملة' : 'Completed' },
        cancelled: { bg: 'bg-red-50', text: 'text-red-600', label: isRtl.value ? 'ملغاة' : 'Cancelled' },
        refunded: { bg: 'bg-red-50', text: 'text-red-600', label: isRtl.value ? 'مُستردة' : 'Refunded' },
        missed_patient: { bg: 'bg-amber-50', text: 'text-amber-600', label: isRtl.value ? 'فوّت المريض' : 'Missed' },
        missed_doctor: { bg: 'bg-amber-50', text: 'text-amber-600', label: isRtl.value ? 'فوّت الطبيب' : 'Missed' },
        technical_issue: { bg: 'bg-gray-50', text: 'text-gray-600', label: isRtl.value ? 'مشكلة تقنية' : 'Technical issue' },
    };
    return map[status] || map.scheduled;
}

function formatDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    return dt.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

function formatTime(t) {
    if (!t) return '';
    return String(t).substring(0, 5);
}

function canJoin(c) {
    if (!['scheduled', 'waiting', 'in_progress'].includes(c.status)) return false;
    if (c.payment_status !== 'paid') return false;
    const now = new Date();
    const start = new Date(`${c.scheduled_date?.substring(0, 10)}T${formatTime(c.start_time)}:00`);
    const end = new Date(`${c.scheduled_date?.substring(0, 10)}T${formatTime(c.end_time)}:00`);
    const openAt = new Date(start.getTime() - props.joinWindowMinutes * 60000);
    const closeAt = new Date(end.getTime() + 30 * 60000);
    return now >= openAt && now <= closeAt;
}

function canCancel(c) {
    if (!['scheduled', 'waiting'].includes(c.status)) return false;
    const start = new Date(`${c.scheduled_date?.substring(0, 10)}T${formatTime(c.start_time)}:00`);
    const cutoff = new Date(start.getTime() - props.cancellationHours * 3600000);
    return new Date() < cutoff;
}

const cancellingId = ref(null);
function cancelConsultation(c) {
    if (!confirm(isRtl.value ? 'هل أنت متأكد من إلغاء الاستشارة؟' : 'Are you sure you want to cancel?')) return;
    cancellingId.value = c.id;
    router.post(lp(`/online-consultations/${c.id}/cancel`), {}, {
        preserveScroll: true,
        onFinish: () => cancellingId.value = null,
    });
}
</script>

<template>
    <div>
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#22406F] to-[#1B365D] p-6 lg:p-8 mb-6 shadow-lg">
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <svg class="w-full h-full" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-width="0.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            </div>
            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-[#C4A265] text-xs font-semibold uppercase tracking-widest mb-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        <span>{{ isRtl ? 'خدمات عن بُعد' : 'Telemedicine' }}</span>
                    </div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white">{{ isRtl ? 'الاستشارات الأونلاين' : 'Online Consultations' }}</h1>
                    <p class="text-white/70 text-sm mt-1 max-w-md">
                        {{ isRtl ? 'تواصل مع أفضل أطبائنا من أي مكان عبر مكالمة فيديو آمنة.' : 'Connect with our top doctors from anywhere via secure video call.' }}
                    </p>
                </div>
                <Link :href="lp('/online-consultations/doctors')" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold text-[#1B365D] bg-gradient-to-r from-[#C4A265] to-[#D9B985] hover:shadow-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'احجز استشارة جديدة' : 'Book new consultation' }}
                </Link>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex border-b border-gray-100 overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="px-5 py-3 text-sm font-semibold border-b-2 transition whitespace-nowrap"
                    :class="activeTab === tab.id
                        ? 'border-[#C4A265] text-[#1B365D]'
                        : 'border-transparent text-gray-400 hover:text-gray-600'"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Empty -->
            <div v-if="filteredItems.length === 0" class="p-12 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-[#1B365D]/10 to-[#C4A265]/10 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-[#1B365D]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                </div>
                <p class="text-gray-600 font-medium">
                    {{ isRtl ? 'لم تحجز أي استشارة أونلاين بعد' : 'No online consultations yet' }}
                </p>
                <p class="text-xs text-gray-400 mt-1 mb-4">
                    {{ isRtl ? 'ابدأ بحجز استشارتك الأولى مع أحد أطبائنا.' : 'Start by booking your first consultation with one of our doctors.' }}
                </p>
                <Link :href="lp('/online-consultations/doctors')" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#C4A265] to-[#D9B985]">
                    {{ isRtl ? 'احجز الآن' : 'Book Now' }}
                </Link>
            </div>

            <!-- List -->
            <div v-else class="divide-y divide-gray-100">
                <div v-for="c in filteredItems" :key="c.id" class="p-4 lg:p-5 flex flex-col md:flex-row md:items-center gap-4 hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1B365D] to-[#C4A265] flex items-center justify-center text-white font-bold shadow-md overflow-hidden flex-shrink-0">
                            <img v-if="c.doctor?.photo" :src="`/storage/${c.doctor.photo}`" class="w-full h-full object-cover" :alt="$localized(c.doctor, 'name')" />
                            <span v-else>{{ ($localized(c.doctor, 'name') || 'D').charAt(0) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-800 truncate">{{ $localized(c.doctor, 'name') }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $localized(c.doctor, 'specialization') }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ formatDate(c.scheduled_date) }} · {{ formatTime(c.start_time) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center flex-wrap gap-2 md:justify-end">
                        <span :class="[statusBadge(c.status).bg, statusBadge(c.status).text]" class="text-[11px] font-semibold px-2.5 py-1 rounded-full">
                            {{ statusBadge(c.status).label }}
                        </span>
                        <span class="text-xs text-gray-500">{{ Number(c.fee || 0).toFixed(0) }} {{ isRtl ? 'ج' : 'EGP' }}</span>

                        <template v-if="canJoin(c)">
                            <a :href="lp(`/online-consultations/${c.id}/room`)"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-500 hover:bg-emerald-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                {{ isRtl ? 'انضم الآن' : 'Join Now' }}
                            </a>
                        </template>
                        <button
                            v-if="canCancel(c)"
                            @click="cancelConsultation(c)"
                            :disabled="cancellingId === c.id"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition disabled:opacity-50"
                        >
                            {{ isRtl ? 'إلغاء' : 'Cancel' }}
                        </button>
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
