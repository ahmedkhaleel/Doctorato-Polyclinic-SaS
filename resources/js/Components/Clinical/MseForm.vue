<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Structured Mental Status Examination (MSE) — shared by the psychiatry and
 * neurology modules. Two-way binds an object of the standard MSE domains.
 * Usage: <MseForm v-model="form.mse" />
 */
const model = defineModel({ type: Object, default: () => ({}) });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
function t(en, ar) { return isRtl.value ? ar : en; }

function set(key, value) {
    model.value = { ...(model.value || {}), [key]: value };
}

// Domain → [label_en, label_ar, options(en/ar pairs) | null=free text]
const fields = [
    ['appearance', 'Appearance', 'المظهر', null],
    ['behavior', 'Behavior', 'السلوك', null],
    ['speech', 'Speech', 'الكلام', [['normal', 'طبيعي'], ['pressured', 'مندفع'], ['slow', 'بطيء'], ['poverty', 'فقير']]],
    ['mood', 'Mood', 'المزاج', [['euthymic', 'سوي'], ['depressed', 'مكتئب'], ['anxious', 'قلق'], ['elevated', 'مرتفع'], ['irritable', 'متهيّج']]],
    ['affect', 'Affect', 'الوجدان', [['full', 'كامل'], ['constricted', 'مُقيّد'], ['blunted', 'بليد'], ['flat', 'مسطّح'], ['labile', 'متقلّب']]],
    ['thought_process', 'Thought process', 'سيرورة التفكير', [['linear', 'خطّي'], ['circumstantial', 'استطرادي'], ['tangential', 'متشعّب'], ['flight_of_ideas', 'تطاير أفكار'], ['disorganized', 'مفكّك']]],
    ['thought_content', 'Thought content', 'محتوى التفكير', null],
    ['perception', 'Perception', 'الإدراك', [['normal', 'طبيعي'], ['hallucinations', 'هلاوس'], ['illusions', 'أوهام حسّية']]],
    ['cognition', 'Cognition', 'المعرفة', [['intact', 'سليمة'], ['impaired', 'مُختلّة']]],
    ['insight', 'Insight', 'الاستبصار', [['good', 'جيّد'], ['fair', 'متوسّط'], ['poor', 'ضعيف']]],
    ['judgment', 'Judgment', 'الحكم', [['good', 'جيّد'], ['fair', 'متوسّط'], ['poor', 'ضعيف']]],
];
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="[key, en, ar, opts] in fields" :key="key">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t(en, ar) }}</label>
            <select v-if="opts" :value="model?.[key] || ''" @change="set(key, $event.target.value)"
                class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm">
                <option value="">{{ t('—', '—') }}</option>
                <option v-for="[ov, oar] in opts" :key="ov" :value="ov">{{ t(ov, oar) }}</option>
            </select>
            <input v-else :value="model?.[key] || ''" @input="set(key, $event.target.value)" type="text"
                class="doctorato-input w-full px-3 py-2 border rounded-lg text-sm" />
        </div>
    </div>
</template>
