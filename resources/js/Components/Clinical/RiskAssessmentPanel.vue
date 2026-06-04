<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * C-SSRS suicide-risk screener (6 yes/no items) + safety plan. Shared clinical
 * component. Two-way binds `answers` ({q1..q6: bool}) and `safetyPlan` (string).
 * Scoring/stratification happens server-side (RiskAssessmentService); this
 * panel surfaces a live hint and reminds the clinician a plan is required.
 */
const answers = defineModel('answers', { type: Object, default: () => ({}) });
const safetyPlan = defineModel('safetyPlan', { type: String, default: '' });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

const items = [
    ['q1', 'Wish to be dead', 'الرغبة في الموت'],
    ['q2', 'Non-specific active suicidal thoughts', 'أفكار انتحارية نشطة غير محدّدة'],
    ['q3', 'Suicidal thoughts with method (no plan/intent)', 'أفكار انتحارية مع طريقة (بلا خطة/نية)'],
    ['q4', 'Some intent to act', 'بعض النية للتنفيذ'],
    ['q5', 'Specific plan and intent', 'خطة ونية محدّدتان'],
    ['q6', 'Suicidal behavior (recent or lifetime)', 'سلوك انتحاري (حديث أو سابق)'],
];

function setAns(q, v) { answers.value = { ...(answers.value || {}), [q]: v }; }

// Live client-side hint mirroring the server stratification.
const liveLevel = computed(() => {
    const a = answers.value || {};
    if (a.q5 || a.q6) return 'high';
    if (a.q3 || a.q4) return 'moderate';
    if (a.q1 || a.q2) return 'low';
    return 'none';
});
const needsPlan = computed(() => liveLevel.value === 'high' || liveLevel.value === 'moderate');
const levelLabel = computed(() => ({
    high: t('HIGH', 'مرتفع'), moderate: t('MODERATE', 'متوسط'), low: t('LOW', 'منخفض'), none: t('None', 'لا يوجد'),
}[liveLevel.value]));
const levelClass = computed(() => ({
    high: 'bg-red-100 text-red-700', moderate: 'bg-amber-100 text-amber-700',
    low: 'bg-yellow-50 text-yellow-700', none: 'bg-slate-100 text-slate-500',
}[liveLevel.value]));
</script>

<template>
    <div class="space-y-3">
        <p class="text-xs text-gray-500">{{ t('Columbia Suicide Severity Rating Scale (C-SSRS) — screener', 'مقياس كولومبيا لشدة الانتحار (C-SSRS) — فرز') }}</p>

        <div v-for="[q, en, ar] in items" :key="q" class="flex items-center justify-between rounded-xl border border-gray-200 px-3 py-2">
            <span class="text-sm text-gray-800 me-3">{{ t(en, ar) }}</span>
            <div class="flex gap-1 shrink-0">
                <button type="button" @click="setAns(q, true)"
                    class="px-3 py-1 rounded-lg text-xs font-medium border transition"
                    :class="answers?.[q] === true ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-600 border-gray-200'">{{ t('Yes', 'نعم') }}</button>
                <button type="button" @click="setAns(q, false)"
                    class="px-3 py-1 rounded-lg text-xs font-medium border transition"
                    :class="answers?.[q] === false ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200'">{{ t('No', 'لا') }}</button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-600">{{ t('Risk level', 'مستوى الخطر') }}:</span>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full" :class="levelClass">{{ levelLabel }}</span>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                {{ t('Safety plan', 'خطة السلامة') }}<span v-if="needsPlan" class="text-red-600"> *</span>
            </label>
            <textarea v-model="safetyPlan" rows="3" class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm"
                :placeholder="t('Means restriction, coping strategies, crisis contacts, follow-up…', 'تقييد الوسائل، استراتيجيات التكيّف، جهات الأزمات، المتابعة…')"></textarea>
            <p v-if="needsPlan" class="text-[11px] text-red-600 mt-1">{{ t('A safety plan is required at this risk level.', 'خطة السلامة إلزامية عند هذا المستوى.') }}</p>
        </div>
    </div>
</template>
