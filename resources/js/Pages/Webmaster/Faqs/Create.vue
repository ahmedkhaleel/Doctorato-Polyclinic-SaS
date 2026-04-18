<script setup>
import { computed } from 'vue';
import { Link, useForm , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    category: '',
    question_ar: '',
    question_en: '',
    answer_ar: '',
    answer_en: '',
    display_order: 0,
});

const categories = [
    { value: 'general', label: 'General' },
    { value: 'services', label: 'Services' },
    { value: 'booking', label: 'Booking' },
    { value: 'pricing', label: 'Pricing' },
    { value: 'aftercare', label: 'Aftercare' },
];

function submit() {
    form.post('/webmaster/faqs');
}
</script>

<template>
    <WebmasterLayout title="Create FAQ">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_create_faq') }}</h1>
                <Link href="/webmaster/faqs" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_faqs') }}</Link>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select v-model="form.category" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="">Select Category</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Question (English)</label>
                        <input v-model="form.question_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        <p v-if="form.errors.question_en" class="mt-1 text-sm text-red-600">{{ form.errors.question_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Question (Arabic)</label>
                        <input v-model="form.question_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        <p v-if="form.errors.question_ar" class="mt-1 text-sm text-red-600">{{ form.errors.question_ar }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Answer (English)</label>
                        <RichTextEditor v-model="form.answer_en" dir="ltr" placeholder="Write answer..." />
                        <p v-if="form.errors.answer_en" class="mt-1 text-sm text-red-600">{{ form.errors.answer_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Answer (Arabic)</label>
                        <RichTextEditor v-model="form.answer_ar" dir="rtl" placeholder="اكتب الإجابة..." />
                        <p v-if="form.errors.answer_ar" class="mt-1 text-sm text-red-600">{{ form.errors.answer_ar }}</p>
                    </div>

                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2.5 px-6 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_faq') }}
                        </button>
                        <Link href="/webmaster/faqs" class="py-2.5 px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">{{ $t('a_cancel') }}</Link>
                    </div>
                </div>
            </form>
        </div>
    </WebmasterLayout>
</template>
