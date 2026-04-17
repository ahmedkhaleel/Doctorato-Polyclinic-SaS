<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    doctors: { type: Array, default: () => [] },
});

const moduleColors = {
    derma:     { bg: 'bg-pink-50',    text: 'text-pink-700',    ring: 'ring-pink-200',    ar: 'جلدية',     en: 'Derma' },
    dental:    { bg: 'bg-sky-50',     text: 'text-sky-700',     ring: 'ring-sky-200',     ar: 'أسنان',     en: 'Dental' },
    pediatric: { bg: 'bg-indigo-50',  text: 'text-indigo-700',  ring: 'ring-indigo-200',  ar: 'أطفال',     en: 'Pediatric' },
};

function moduleInfo(m) { return moduleColors[m] || { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-200', ar: m, en: m }; }
function doctorName(d) { return isRtl.value ? (d.name_ar || d.name_en || '-') : (d.name_en || d.name_ar || '-'); }
function doctorSpecialty(d) { return isRtl.value ? (d.specialization_ar || '') : (d.specialization_en || ''); }
function moduleLabel(m) { const i = moduleInfo(m); return isRtl.value ? i.ar : i.en; }
function formatMoney(v) { return `${Number(v || 0).toLocaleString()} EGP`; }
function photoUrl(d) {
    if (!d.photo) return null;
    if (d.photo.startsWith('http')) return d.photo;
    return `/storage/${d.photo}`;
}
function initials(name) {
    if (!name) return '؟';
    return name.trim().split(/\s+/).slice(0, 2).map(s => s.charAt(0)).join('').toUpperCase();
}
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- Hero -->
        <div class="oc-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1B365D] via-[#24436F] to-[#1B365D] p-8 md:p-10">
            <div class="absolute -top-24 ltr:-right-24 rtl:-left-24 w-80 h-80 bg-[#C4A265]/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 ltr:-left-20 rtl:-right-20 w-64 h-64 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="oc-hero-up">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-[#C4A265]/20 ring-1 ring-[#C4A265]/40 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-white">
                                {{ isRtl ? 'الأطباء الأونلاين' : 'Online Doctors' }}
                            </h1>
                            <div class="h-0.5 w-20 bg-gradient-to-r from-[#C4A265] to-transparent mt-1.5"></div>
                            <p class="text-white/70 text-sm mt-2">
                                {{ isRtl ? 'الأطباء المفعلين لاستقبال الاستشارات عن بُعد' : 'Doctors enabled for remote consultations' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="oc-hero-up" style="animation-delay:.15s">
                    <Link
                        href="/admin/online-consultations"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 shadow-lg transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5m.75-9l3-3 2.148 2.148A12.061 12.061 0 0116.5 7.605" /></svg>
                        {{ isRtl ? 'كل الاستشارات' : 'All Consultations' }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Doctor cards -->
        <div v-if="doctors.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div
                v-for="(d, i) in doctors"
                :key="d.id"
                class="oc-card-enter bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden"
                :style="{ animationDelay: `${i * 0.06}s` }"
            >
                <!-- top bar accent -->
                <div class="h-1 bg-gradient-to-r from-[#1B365D] via-[#C4A265] to-[#1B365D]"></div>

                <div class="p-6">
                    <div class="flex items-center gap-4">
                        <!-- Avatar with gradient gold border -->
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 rounded-full p-[2px] bg-gradient-to-br from-[#C4A265] via-[#E6C88A] to-[#C4A265]">
                                <div class="w-full h-full rounded-full bg-white p-[2px]">
                                    <img
                                        v-if="photoUrl(d)"
                                        :src="photoUrl(d)"
                                        :alt="doctorName(d)"
                                        class="w-full h-full rounded-full object-cover"
                                    />
                                    <div v-else class="w-full h-full rounded-full bg-[#1B365D] text-[#C4A265] flex items-center justify-center font-bold text-lg">
                                        {{ initials(doctorName(d)) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-bold text-[#1B365D] truncate">{{ doctorName(d) }}</h3>
                            <p class="text-sm text-gray-500 truncate">{{ doctorSpecialty(d) }}</p>
                            <span
                                v-if="d.module"
                                :class="[
                                    'inline-flex items-center mt-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1',
                                    moduleInfo(d.module).bg,
                                    moduleInfo(d.module).text,
                                    moduleInfo(d.module).ring,
                                ]"
                            >
                                {{ moduleLabel(d.module) }}
                            </span>
                        </div>
                    </div>

                    <!-- Stats row -->
                    <div class="grid grid-cols-3 gap-2 mt-5">
                        <div class="rounded-xl bg-gray-50 p-3 text-center">
                            <p class="text-[10.5px] uppercase tracking-wide text-gray-500">{{ isRtl ? 'الجلسات' : 'Sessions' }}</p>
                            <p class="text-lg font-bold text-[#1B365D] tabular-nums">{{ d.total_sessions ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3 text-center">
                            <p class="text-[10.5px] uppercase tracking-wide text-emerald-600">{{ isRtl ? 'مكتمل' : 'Done' }}</p>
                            <p class="text-lg font-bold text-emerald-700 tabular-nums">{{ d.completed_sessions ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl bg-[#C4A265]/10 p-3 text-center">
                            <p class="text-[10.5px] uppercase tracking-wide text-[#8A6F3A]">{{ isRtl ? 'الإيرادات' : 'Revenue' }}</p>
                            <p class="text-sm font-bold text-[#8A6F3A] tabular-nums">{{ formatMoney(d.total_revenue) }}</p>
                        </div>
                    </div>

                    <!-- Fee + duration -->
                    <div class="flex items-center justify-between mt-5 py-3 px-4 rounded-xl bg-[#1B365D]/5">
                        <div>
                            <p class="text-[11px] text-gray-500">{{ isRtl ? 'الرسوم' : 'Fee' }}</p>
                            <p class="text-base font-bold text-[#1B365D] tabular-nums">{{ formatMoney(d.online_consultation_fee) }}</p>
                        </div>
                        <div class="w-px h-8 bg-gray-200"></div>
                        <div>
                            <p class="text-[11px] text-gray-500">{{ isRtl ? 'المدة' : 'Duration' }}</p>
                            <p class="text-base font-bold text-[#1B365D] tabular-nums">
                                {{ d.online_session_duration_minutes || 0 }} <span class="text-xs font-medium text-gray-500">{{ isRtl ? 'دقيقة' : 'min' }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-stretch gap-2 mt-5">
                        <Link
                            :href="`/admin/online-consultations?search=${encodeURIComponent(doctorName(d))}`"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-[#1B365D] hover:bg-[#24436F] transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                            {{ isRtl ? 'عرض الاستشارات' : 'View Consultations' }}
                        </Link>
                        <Link
                            :href="`/admin/doctors/${d.id}/edit`"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-[#1B365D] bg-[#C4A265]/15 hover:bg-[#C4A265]/25 border border-[#C4A265]/30 transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" /></svg>
                            {{ isRtl ? 'تعديل الإعدادات' : 'Edit Settings' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <div class="w-20 h-20 mx-auto rounded-full bg-[#C4A265]/10 flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <p class="text-lg font-semibold text-[#1B365D]">
                {{ isRtl ? 'لا يوجد أطباء مفعلين للأونلاين' : 'No online-enabled doctors yet' }}
            </p>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl ? 'فعّلها من ملف الطبيب.' : 'Enable it from the doctor profile.' }}
            </p>
            <Link
                href="/admin/doctors"
                class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl text-sm font-semibold text-[#1B365D] bg-[#C4A265] hover:bg-[#D4B275] transition"
            >
                {{ isRtl ? 'إدارة الأطباء' : 'Manage Doctors' }}
            </Link>
        </div>
    </div>
</template>

<style scoped>
.oc-hero-up { animation: ocHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both; }
@keyframes ocHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.oc-card-enter { animation: ocCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
@keyframes ocCardEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
