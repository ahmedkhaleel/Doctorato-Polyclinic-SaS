<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import UiEmptyState from '@/Components/Ui/EmptyState.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    buckets: { type: Object, required: true },
});

const LABELS = {
    visit_in_progress: { ar: 'زيارة قيد التنفيذ بلا تشخيص', en: 'In-progress visit — no diagnosis' },
    visit_completed: { ar: 'زيارة مكتملة بلا تشخيص', en: 'Completed visit — no diagnosis' },
    scale_flagged: { ar: 'مقياس مُعلَّم يحتاج مراجعة', en: 'Flagged scale — needs review' },
    lab_abnormal: { ar: 'تحليل غير طبيعي', en: 'Abnormal lab result' },
    followup_due: { ar: 'متابعة مستحقّة', en: 'Follow-up due' },
    plan_open: { ar: 'خطة نشطة بلا جلسة قادمة', en: 'Open plan — no next session' },
};
const label = (k) => (LABELS[k] ? (isRtl.value ? LABELS[k].ar : LABELS[k].en) : k);

const SECTIONS = computed(() => [
    {
        key: 'incomplete_docs', accent: '#D97706',
        title: isRtl.value ? 'توثيق ناقص' : 'Incomplete documentation',
        hint: isRtl.value ? 'زيارات بلا تشخيص — أكمل التوثيق' : 'Visits missing a diagnosis — finish the note',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        data: props.buckets.incomplete_docs,
    },
    {
        key: 'results_review', accent: '#DC2626',
        title: isRtl.value ? 'نتائج تحتاج مراجعة' : 'Results to review',
        hint: isRtl.value ? 'مقاييس مُعلَّمة أو تحاليل غير طبيعية حديثة' : 'Recently flagged scales / abnormal labs',
        icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        data: props.buckets.results_review,
    },
    {
        key: 'due_followups', accent: '#4F46E5',
        title: isRtl.value ? 'متابعات مستحقّة' : 'Due follow-ups',
        hint: isRtl.value ? 'متابعات لم يُحجز لها موعد بعد' : 'Follow-ups not yet booked',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        data: props.buckets.due_followups,
        badge: props.buckets.due_followups?.overdue,
    },
    {
        key: 'open_plans', accent: '#8B5CF6',
        title: isRtl.value ? 'خطط علاج مفتوحة' : 'Open treatment plans',
        hint: isRtl.value ? 'خطط نشطة بلا جلسة قادمة مجدولة' : 'Active plans with no next session scheduled',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        data: props.buckets.open_plans,
    },
]);

const total = computed(() => props.buckets?.total || 0);
function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short' });
}
</script>

<template>
    <div class="space-y-6" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-8">
            <div class="absolute top-0 ltr:right-0 rtl:left-0 w-72 h-72 bg-[#C4A265]/10 rounded-full -translate-y-1/2 blur-3xl"></div>
            <div class="relative z-10 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white">{{ isRtl ? 'قائمة مهامي' : 'My Worklist' }}</h1>
                    <p class="text-sm text-gray-400 mt-1.5">{{ isRtl ? 'بنود تحتاج إجراءك — مجمّعة من حالة المرضى السريرية' : 'Items that need your action — across your patients’ clinical state' }}</p>
                </div>
                <div class="text-center bg-white/10 rounded-2xl px-5 py-3 border border-white/10">
                    <p class="text-3xl font-extrabold text-white tabular-nums">{{ total }}</p>
                    <p class="text-[11px] text-gray-300">{{ isRtl ? 'إجمالي المهام' : 'open items' }}</p>
                </div>
            </div>
        </div>

        <div v-if="total === 0" class="bg-white rounded-2xl border border-gray-100 shadow-sm py-4">
            <UiEmptyState icon="document" :title="isRtl ? 'لا مهام معلّقة — عمل رائع!' : 'Nothing pending — all caught up!'" />
        </div>

        <!-- Buckets -->
        <div class="grid md:grid-cols-2 gap-4 sm:gap-6">
            <section v-for="s in SECTIONS" :key="s.key"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="px-4 sm:px-5 py-4 border-b border-gray-100 flex items-center justify-between"
                    :style="`background: linear-gradient(115deg, ${s.accent}0D 0%, transparent 70%)`">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" :style="`background:${s.accent}1A`">
                            <svg class="w-4.5 h-4.5" :style="`color:${s.accent}`" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="s.icon" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                {{ s.title }}
                                <span v-if="s.badge" class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200">{{ isRtl ? `${s.badge} متأخرة` : `${s.badge} overdue` }}</span>
                            </h3>
                            <p class="text-[11px] text-gray-400">{{ s.hint }}</p>
                        </div>
                    </div>
                    <span class="text-lg font-extrabold tabular-nums" :style="`color:${s.accent}`">{{ s.data?.count || 0 }}</span>
                </div>

                <div class="p-3 sm:p-4 flex-1">
                    <UiEmptyState v-if="!s.data?.items?.length" icon="document" :title="isRtl ? 'لا شيء هنا' : 'Nothing here'" />
                    <ul v-else class="space-y-1.5">
                        <li v-for="it in s.data.items" :key="it.id">
                            <Link :href="it.href"
                                class="flex items-center gap-3 rounded-xl border border-gray-100 px-3 py-2.5 hover:bg-gray-50 hover:border-gray-200 transition-all">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="it.severity === 'high' ? 'bg-red-500' : 'bg-amber-400'"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ it.patient || '—' }}
                                        <span v-if="it.file_number" class="text-[10px] font-mono text-gray-400">#{{ it.file_number }}</span>
                                    </p>
                                    <p class="text-[11px] text-gray-500 truncate">{{ label(it.label) }}<span v-if="it.detail"> · {{ it.detail }}</span></p>
                                </div>
                                <span v-if="it.date" class="text-[10px] text-gray-400 shrink-0">{{ fmtDate(it.date) }}</span>
                                <svg class="w-4 h-4 text-gray-300 shrink-0 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</template>
