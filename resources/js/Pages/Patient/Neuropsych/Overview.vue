<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

defineOptions({ layout: PatientLayout });

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentScales: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
});

const { lp } = usePatientLocale();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

const accent = '#7C3AED';

function moduleLabel(m) {
    return m === 'neurology' ? t('Neurology', 'الأعصاب') : t('Psychiatry', 'النفسية');
}
function statusLabel(s) {
    const map = isRtl.value ? { unconfirmed: 'بانتظار التأكيد', confirmed: 'مؤكّد' } : { unconfirmed: 'Pending', confirmed: 'Confirmed' };
    return map[s] || s;
}

const cards = computed(() => [
    { label: t('Questionnaires taken', 'استبيانات مُسجّلة'), value: props.stats.scales_total ?? 0, color: accent },
    { label: t('Seizure logs (30d)', 'تسجيلات نوبات (30 يوم)'), value: props.stats.seizure_30d ?? 0, color: '#0EA5E9' },
    { label: t('Headache logs (30d)', 'تسجيلات صداع (30 يوم)'), value: props.stats.headache_30d ?? 0, color: '#DB2777' },
]);
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="relative overflow-hidden rounded-2xl p-6 text-white shadow-lg" :style="{ background: `linear-gradient(120deg,#1B365D 0%, ${accent} 160%)` }">
            <h1 class="text-2xl font-bold">{{ t('Mind & Nerves', 'النفسية والعصبية') }}</h1>
            <p class="text-white/70 text-sm mt-1">{{ t('Your questionnaires, diaries and appointments at a glance', 'استبياناتك ومفكّراتك ومواعيدك في مكان واحد') }}</p>
        </div>

        <!-- Stat tiles -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div v-for="(c, i) in cards" :key="i" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="text-3xl font-extrabold" :style="{ color: c.color }">{{ c.value }}</div>
                <div class="text-xs text-slate-500 mt-1">{{ c.label }}</div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link :href="lp('/neuropsych/scales')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('My Questionnaires', 'استبياناتي') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('Fill measurement-based questionnaires before your visit', 'املأ الاستبيانات قبل زيارتك') }}</p>
            </Link>
            <Link :href="lp('/neuropsych/diaries')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('My Diaries', 'مفكّراتي') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('Log seizures and headaches to share with your doctor', 'سجّل النوبات والصداع لمشاركتها مع طبيبك') }}</p>
            </Link>
            <Link :href="lp('/visits')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('My Visits', 'زياراتي') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('Review your past consultations', 'راجع زياراتك السابقة') }}</p>
            </Link>
            <Link :href="lp('/prescriptions')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('My Prescriptions', 'وصفاتي') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('See prescribed medications', 'اطّلع على الأدوية الموصوفة') }}</p>
            </Link>
            <Link :href="lp('/invoices')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('My Invoices', 'فواتيري') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('View invoices and payments', 'اطّلع على الفواتير والمدفوعات') }}</p>
            </Link>
            <Link :href="lp('/bookings')" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition-all block">
                <div class="font-bold text-gray-800">{{ t('Book Appointment', 'حجز موعد') }}</div>
                <p class="text-sm text-gray-500 mt-1">{{ t('Book a psychiatry or neurology visit', 'احجز موعد نفسية أو أعصاب') }}</p>
            </Link>
        </div>

        <!-- Upcoming appointments -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-bold text-gray-800 mb-3">{{ t('Upcoming appointments', 'المواعيد القادمة') }}</h3>
            <div v-if="upcoming.length === 0" class="text-sm text-gray-400 py-4 text-center">{{ t('No upcoming appointments', 'لا توجد مواعيد قادمة') }}</div>
            <ul v-else class="divide-y divide-gray-50">
                <li v-for="a in upcoming" :key="a.id" class="py-2.5 flex items-center justify-between text-sm">
                    <div>
                        <span class="font-medium text-gray-800">{{ moduleLabel(a.module) }}</span>
                        <span v-if="a.doctor" class="text-gray-400"> — {{ isRtl ? a.doctor.name_ar : a.doctor.name_en }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-600" dir="ltr">{{ a.date }} {{ a.time }}</span>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="a.status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ statusLabel(a.status) }}</span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Recent scores -->
        <div v-if="recentScales.length" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <h3 class="font-bold text-gray-800 mb-3">{{ t('Recent questionnaire scores', 'أحدث درجات الاستبيانات') }}</h3>
            <ul class="divide-y divide-gray-50">
                <li v-for="(r, i) in recentScales" :key="i" class="py-2.5 flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700" dir="ltr">{{ r.scale_key }}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-800 font-semibold" dir="ltr">{{ r.score }}</span>
                        <span class="text-gray-500">{{ r.severity || '—' }}</span>
                        <span class="text-gray-400" dir="ltr">{{ r.taken_at }}</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
