<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PediatricGrowthChart from '@/Components/PediatricGrowthChart.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

const props = defineProps({
    records: Object,
    alerts: Object,
});

/* ── Alert cards config ────────────────────────────────── */
const alertCards = computed(() => [
    {
        key: 'underweight',
        labelEn: 'Underweight', labelAr: 'نقص الوزن',
        descEn: 'Below 5th percentile', descAr: 'أقل من المئين الخامس',
        patients: props.alerts?.underweight || [],
        borderColor: 'border-red-200',
        bgColor: 'bg-red-50',
        textColor: 'text-red-700',
        iconBg: 'bg-red-100',
        iconColor: 'text-red-500',
        icon: 'down',
    },
    {
        key: 'overweight',
        labelEn: 'Overweight', labelAr: 'زيادة الوزن',
        descEn: 'Above 95th percentile', descAr: 'أعلى من المئين 95',
        patients: props.alerts?.overweight || [],
        borderColor: 'border-amber-200',
        bgColor: 'bg-amber-50',
        textColor: 'text-amber-700',
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-500',
        icon: 'up',
    },
    {
        key: 'stunted',
        labelEn: 'Stunted Growth', labelAr: 'تأخر النمو',
        descEn: 'Height below 5th percentile', descAr: 'الطول أقل من المئين الخامس',
        patients: props.alerts?.stunted || [],
        borderColor: 'border-red-200',
        bgColor: 'bg-red-50',
        textColor: 'text-red-700',
        iconBg: 'bg-red-100',
        iconColor: 'text-red-500',
        icon: 'stunted',
    },
]);

/* ── Expanded alert cards ──────────────────────────────── */
const expandedAlerts = ref(new Set());
function toggleAlert(key) {
    const s = new Set(expandedAlerts.value);
    s.has(key) ? s.delete(key) : s.add(key);
    expandedAlerts.value = s;
}

/* ── Percentile color ──────────────────────────────────── */
function percentileColor(val) {
    if (val == null || val === '' || val === '-') return 'text-gray-400';
    const n = parseFloat(val);
    if (isNaN(n)) return 'text-gray-400';
    if (n < 5)  return 'text-red-600 font-bold';
    if (n < 15) return 'text-amber-600 font-semibold';
    if (n > 95) return 'text-red-600 font-bold';
    if (n > 85) return 'text-amber-600 font-semibold';
    return 'text-green-600 font-semibold';
}

function percentileBg(val) {
    if (val == null || val === '' || val === '-') return '';
    const n = parseFloat(val);
    if (isNaN(n)) return '';
    if (n < 5)  return 'bg-red-50';
    if (n < 15) return 'bg-amber-50';
    if (n > 95) return 'bg-red-50';
    if (n > 85) return 'bg-amber-50';
    return 'bg-green-50';
}

/* ── Helpers ────────────────────────────────────────────── */
function formatDate(date) {
    if (!date) return '-';
    const loc = isRtl.value ? 'ar-EG' : 'en-GB';
    return new Date(date).toLocaleDateString(loc, { day: '2-digit', month: 'short', year: 'numeric' });
}

function calcAge(dob) {
    if (!dob) return '-';
    const birth = new Date(dob);
    const now = new Date();
    const months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (months < 1) return isRtl.value ? 'حديث الولادة' : 'Newborn';
    if (months < 12) return `${months} ${isRtl.value ? 'شهر' : 'mo'}`;
    const years = Math.floor(months / 12);
    const rem = months % 12;
    return rem > 0 ? `${years}${isRtl.value ? 'س' : 'y'} ${rem}${isRtl.value ? 'ش' : 'm'}` : `${years} ${isRtl.value ? 'سنة' : 'y'}`;
}

/* ── Pagination ────────────────────────────────────────── */
const paginationLinks = computed(() => props.records?.links || []);
</script>

<template>
    <div class="space-y-8 pb-12">
        <!-- ── Hero ──────────────────────────────────────── -->
        <div class="ped-hero relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-green-600 to-green-500 p-8 md:p-10">
            <div class="absolute -top-20 ltr:-right-20 rtl:-left-20 w-72 h-72 bg-green-400/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-16 ltr:-left-16 rtl:-right-16 w-56 h-56 bg-emerald-300/15 rounded-full blur-3xl"></div>

            <div class="absolute ltr:right-8 rtl:left-8 top-8 opacity-10">
                <svg class="w-28 h-28 text-white ped-float" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="ped-hero-up">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center ring-1 ring-white/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">
                                    {{ isRtl ? 'متابعة النمو' : 'Growth Monitoring' }}
                                </h1>
                                <p class="text-green-100/80 text-sm mt-0.5">
                                    {{ isRtl ? 'مراقبة نمو الأطفال والتنبيهات' : 'Track child growth and alerts' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 ped-hero-up" style="animation-delay: 0.15s">
                        <Link
                            href="/admin/pediatric"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-white/15 hover:bg-white/25 ring-1 ring-white/30 hover:ring-white/50 shadow-lg transition-all duration-300 backdrop-blur-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                            {{ isRtl ? 'لوحة التحكم' : 'Dashboard' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Growth Alerts ─────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div
                v-for="card in alertCards"
                :key="card.key"
                :class="`bg-white rounded-2xl shadow-sm border ${card.borderColor} overflow-hidden transition-all duration-300 hover:shadow-md`"
            >
                <!-- Alert Header -->
                <div :class="`${card.bgColor} p-5 cursor-pointer`" @click="toggleAlert(card.key)">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div :class="`w-10 h-10 rounded-xl ${card.iconBg} flex items-center justify-center`">
                                <svg v-if="card.icon === 'down'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 5.25l-7.5 7.5-7.5-7.5m15 6l-7.5 7.5-7.5-7.5" /></svg>
                                <svg v-else-if="card.icon === 'up'" :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                                <svg v-else :class="`w-5 h-5 ${card.iconColor}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4.5h14.25M3 9h9.75M3 13.5h5.25m5.25-.75L17.25 9m0 0L21 12.75M17.25 9v12" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">{{ isRtl ? card.labelAr : card.labelEn }}</p>
                                <p :class="`text-2xl font-bold ${card.textColor}`">{{ card.patients.length }}</p>
                            </div>
                        </div>
                        <svg
                            class="w-5 h-5 text-gray-400 transition-transform duration-300"
                            :class="{ 'rotate-180': expandedAlerts.has(card.key) }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ isRtl ? card.descAr : card.descEn }}</p>
                </div>

                <!-- Expanded Patient List -->
                <transition name="ped-expand">
                    <div v-if="expandedAlerts.has(card.key) && card.patients.length" class="border-t border-gray-100">
                        <div class="max-h-52 overflow-y-auto">
                            <div
                                v-for="p in card.patients"
                                :key="p.id"
                                class="flex items-center justify-between px-5 py-2.5 hover:bg-gray-50 transition text-sm border-b border-gray-50 last:border-0"
                            >
                                <div>
                                    <Link
                                        :href="`/admin/patients/${p.id || p.patient_id}`"
                                        class="font-medium text-gray-800 hover:text-green-600 transition"
                                    >
                                        {{ p.full_name || p.patient_name || '-' }}
                                    </Link>
                                    <p class="text-xs text-gray-400">{{ p.file_number || '' }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ calcAge(p.date_of_birth) }}</span>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Empty state for expanded -->
                <transition name="ped-expand">
                    <div v-if="expandedAlerts.has(card.key) && !card.patients.length" class="p-4 text-center text-sm text-gray-400 border-t border-gray-100">
                        {{ isRtl ? 'لا توجد تنبيهات' : 'No alerts' }}
                    </div>
                </transition>
            </div>
        </div>

        <!-- ── System-wide Growth Analysis Chart ────────── -->
        <section v-if="records?.data?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-2">{{ isRtl ? 'تحليل نمو المرضى' : 'Patient Growth Analysis' }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ isRtl ? 'يظهر الرسم البياني جميع القياسات المسجلة في النظام' : 'Chart shows all measurements recorded in the system' }}</p>
            <PediatricGrowthChart :records="records.data" gender="male" />
        </section>

        <!-- ── Growth Records Table ──────────────────────── -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">
                    {{ isRtl ? 'سجلات النمو' : 'Growth Records' }}
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80">
                        <tr>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="text-start px-5 py-3.5 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'العمر' : 'Age' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'الوزن (كجم)' : 'Weight (kg)' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden sm:table-cell">{{ isRtl ? 'الطول (سم)' : 'Height (cm)' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden lg:table-cell">{{ isRtl ? 'محيط الرأس' : 'Head Circ.' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500 hidden md:table-cell">{{ isRtl ? 'BMI' : 'BMI' }}</th>
                            <th class="text-center px-5 py-3.5 font-semibold text-gray-500">{{ isRtl ? 'مئين الوزن' : 'Wt %ile' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="r in (records?.data || [])"
                            :key="r.id"
                            class="border-b border-gray-50 hover:bg-green-50/30 transition-colors"
                        >
                            <td class="px-5 py-3.5">
                                <div>
                                    <Link
                                        :href="`/admin/patients/${r.patient_id || r.patient?.id}`"
                                        class="font-semibold text-gray-800 hover:text-green-600 transition"
                                    >
                                        {{ r.patient?.full_name || r.patient_name || '-' }}
                                    </Link>
                                    <p class="text-xs text-gray-400 sm:hidden">{{ formatDate(r.recorded_at || r.created_at) }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 hidden sm:table-cell">{{ formatDate(r.recorded_at || r.created_at) }}</td>
                            <td class="px-5 py-3.5 text-center text-gray-500 hidden md:table-cell">{{ r.age_display || calcAge(r.patient?.date_of_birth) }}</td>
                            <td class="px-5 py-3.5 text-center font-medium text-gray-800 tabular-nums">{{ r.weight ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-center text-gray-600 tabular-nums hidden sm:table-cell">{{ r.height ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-center text-gray-600 tabular-nums hidden lg:table-cell">{{ r.head_circumference ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-center text-gray-600 tabular-nums hidden md:table-cell">{{ r.bmi ? parseFloat(r.bmi).toFixed(1) : '-' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span
                                    v-if="r.weight_percentile != null && r.weight_percentile !== ''"
                                    :class="`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold tabular-nums ${percentileBg(r.weight_percentile)} ${percentileColor(r.weight_percentile)}`"
                                >
                                    {{ r.weight_percentile }}{{ typeof r.weight_percentile === 'number' ? '%' : '' }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </td>
                        </tr>
                        <tr v-if="!records?.data?.length">
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                                {{ isRtl ? 'لا توجد سجلات نمو' : 'No growth records found' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="flex items-center justify-center gap-1 px-5 py-4 border-t border-gray-100">
                <template v-for="(link, i) in paginationLinks" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm transition"
                        :class="link.active ? 'bg-green-500 text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                        v-html="link.label"
                        preserve-state
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-gray-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ped-hero-up {
    animation: pedHeroUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes pedHeroUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ped-float {
    animation: pedFloat 6s ease-in-out infinite;
}
@keyframes pedFloat {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-12px); }
}

/* ── Expand transition ────────────────────────────────── */
.ped-expand-enter-active { transition: all 0.3s ease-out; max-height: 300px; }
.ped-expand-leave-active { transition: all 0.2s ease-in; }
.ped-expand-enter-from   { opacity: 0; max-height: 0; }
.ped-expand-enter-to     { opacity: 1; max-height: 300px; }
.ped-expand-leave-from   { opacity: 1; max-height: 300px; }
.ped-expand-leave-to     { opacity: 0; max-height: 0; }
</style>
