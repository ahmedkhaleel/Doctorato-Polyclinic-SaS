<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';

const props = defineProps({
    faq: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    category: props.faq.category || '',
    question_ar: props.faq.question_ar || '',
    question_en: props.faq.question_en || '',
    answer_ar: props.faq.answer_ar || '',
    answer_en: props.faq.answer_en || '',
    display_order: props.faq.display_order || 0,
});

const categories = [
    { value: 'general', label: 'General' },
    { value: 'services', label: 'Services' },
    { value: 'booking', label: 'Booking' },
    { value: 'pricing', label: 'Pricing' },
    { value: 'aftercare', label: 'Aftercare' },
];

function submit() {
    form.put(`/admin/faqs/${props.faq.id}`);
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_faq')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_faq') }}</h1>
                <Link href="/admin/faqs" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_faqs') }}</Link>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_category') }}</label>
                            <select v-model="form.category" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                <option value="">{{ $t('a_select_category') }}</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_question_en') }}</label>
                        <input v-model="form.question_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.question_en" class="mt-1 text-sm text-red-600">{{ form.errors.question_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_question_ar') }}</label>
                        <input v-model="form.question_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        <p v-if="form.errors.question_ar" class="mt-1 text-sm text-red-600">{{ form.errors.question_ar }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_answer_en') }}</label>
                        <RichTextEditor v-model="form.answer_en" dir="ltr" placeholder="Write answer..." />
                        <p v-if="form.errors.answer_en" class="mt-1 text-sm text-red-600">{{ form.errors.answer_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_answer_ar') }}</label>
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
                            {{ form.processing ? $t('a_saving') : $t('a_update_faq') }}
                        </button>
                        <Link href="/admin/faqs" class="py-2.5 px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
