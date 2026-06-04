<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Renders a measurement-based-care scale (items + option set) and collects the
 * patient's/clinician's answers. Presentation only — scoring happens server-
 * side via ScaleEngine. Shared by the patient portal and the doctor panel.
 * v-model is an object { itemIndex: chosenValue }.
 */
const props = defineProps({
    scale: { type: Object, required: true }, // { name_ar, name_en, items:[{en,ar}], options:[{v,en,ar}] }
});
const model = defineModel({ type: Object, default: () => ({}) });

const page = usePage();
const isRtl = computed(() => (page.props.dir || page.props.locale === 'ar') ? true : false);
function t(en, ar) { return isRtl.value ? ar : en; }

const answeredCount = computed(() => Object.keys(model.value || {}).length);

function pick(i, v) {
    model.value = { ...(model.value || {}), [i]: v };
}
</script>

<template>
    <div class="space-y-3">
        <p class="text-xs text-gray-500">{{ answeredCount }} / {{ scale.items.length }} {{ t('answered', 'مُجاب') }}</p>
        <div v-for="(item, i) in scale.items" :key="i" class="rounded-xl border border-gray-200 p-3">
            <p class="text-sm font-medium text-gray-800 mb-2">{{ i + 1 }}. {{ t(item.en, item.ar) }}</p>
            <div class="flex flex-wrap gap-2">
                <button v-for="opt in scale.options" :key="opt.v" type="button"
                    @click="pick(i, opt.v)"
                    :aria-pressed="model?.[i] === opt.v"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition"
                    :class="model?.[i] === opt.v
                        ? 'bg-[#1B365D] text-white border-[#1B365D]'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                    {{ t(opt.en, opt.ar) }}
                </button>
            </div>
        </div>
    </div>
</template>
