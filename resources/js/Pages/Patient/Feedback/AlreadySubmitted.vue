<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();
defineOptions({ layout: PatientLayout });

defineProps({ visit: Object });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
</script>

<template>
    <div class="max-w-md mx-auto text-center py-12">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            {{ isRtl ? 'لقد قيّمت هذه الزيارة من قبل' : 'You already reviewed this visit' }}
        </h1>
        <p class="text-sm text-gray-500 mb-6">
            {{ isRtl
                ? 'شكراً لمشاركتك! يمكنك مراجعة تقييماتك أو تقييم زيارة أخرى.'
                : 'Thanks for your feedback! Review your past ratings or rate another visit.' }}
        </p>
        <Link :href="lp('/feedback')"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[var(--brand-primary)] text-white text-sm font-semibold hover:opacity-90">
            {{ isRtl ? '← عودة لكل التقييمات' : '← Back to feedback' }}
        </Link>
    </div>
</template>
