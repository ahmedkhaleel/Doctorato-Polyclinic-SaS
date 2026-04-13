<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';

const props = defineProps({
    page: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    title_ar: props.page.title_ar || '',
    title_en: props.page.title_en || '',
    content_ar: props.page.content_ar || '',
    content_en: props.page.content_en || '',
    seo_title_ar: props.page.seo_title_ar || '',
    seo_title_en: props.page.seo_title_en || '',
    seo_desc_ar: props.page.seo_desc_ar || '',
    seo_desc_en: props.page.seo_desc_en || '',
    seo_keywords: props.page.seo_keywords || '',
});

function submit() {
    form.post(`/admin/pages/${props.page.id}/update`);
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_page')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_page') }}: {{ $localized(page, 'title') }}</h1>
                <Link href="/admin/pages" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_pages') }}</Link>
            </div>

            <form @submit.prevent="submit" class="max-w-5xl space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_en') }}</label>
                            <input v-model="form.title_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.title_en" class="mt-1 text-sm text-red-600">{{ form.errors.title_en }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_ar') }}</label>
                            <input v-model="form.title_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            <p v-if="form.errors.title_ar" class="mt-1 text-sm text-red-600">{{ form.errors.title_ar }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_content_en') }}</label>
                        <RichTextEditor v-model="form.content_en" dir="ltr" placeholder="Write content..." />
                        <p v-if="form.errors.content_en" class="mt-1 text-sm text-red-600">{{ form.errors.content_en }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_content_ar') }}</label>
                        <RichTextEditor v-model="form.content_ar" dir="rtl" placeholder="اكتب المحتوى..." />
                        <p v-if="form.errors.content_ar" class="mt-1 text-sm text-red-600">{{ form.errors.content_ar }}</p>
                    </div>
                </div>

                <!-- SEO Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_seo_settings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_title_en') }}</label>
                            <input v-model="form.seo_title_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_title_ar') }}</label>
                            <input v-model="form.seo_title_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_desc_en') }}</label>
                            <textarea v-model="form.seo_desc_en" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_desc_ar') }}</label>
                            <textarea v-model="form.seo_desc_ar" rows="2" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_seo_keywords') }}</label>
                        <input v-model="form.seo_keywords" type="text" placeholder="comma, separated, keywords" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                    </div>
                </div>

                <div class="flex space-x-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="py-2.5 px-8 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                        style="background-color: #C4A265;"
                    >
                        {{ form.processing ? $t('a_saving') : $t('a_update_page') }}
                    </button>
                    <Link href="/admin/pages" class="py-2.5 px-6 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                        {{ $t('a_cancel') }}
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
