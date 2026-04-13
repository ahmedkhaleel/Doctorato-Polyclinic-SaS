<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    seoPage: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    title_ar: props.seoPage.title_ar || '',
    title_en: props.seoPage.title_en || '',
    description_ar: props.seoPage.description_ar || '',
    description_en: props.seoPage.description_en || '',
    keywords: props.seoPage.keywords || '',
    og_image: props.seoPage.og_image || '',
    canonical_url: props.seoPage.canonical_url || '',
    is_indexable: props.seoPage.is_indexable ?? true,
    structured_data: props.seoPage.structured_data ? JSON.stringify(props.seoPage.structured_data, null, 2) : '',
});

function submit() {
    form.post(`/admin/seo-pages/${props.seoPage.id}/update`);
}

// Preview helpers
const previewTitle = computed(() => {
    const t = form.title_en || form.title_ar || 'Page Title';
    return t.length > 60 ? t.substring(0, 57) + '...' : t;
});

const previewDescription = computed(() => {
    const d = form.description_en || form.description_ar || 'Page description will appear here.';
    return d.length > 160 ? d.substring(0, 157) + '...' : d;
});

const previewUrl = computed(() => {
    return form.canonical_url || `https://auraderma.com/${props.seoPage.page_identifier}`;
});

const titleLengthClass = computed(() => {
    const len = form.title_en?.length || 0;
    if (len > 60) return 'text-red-500';
    if (len > 50) return 'text-amber-500';
    return 'text-gray-400';
});

const descLengthClass = computed(() => {
    const len = form.description_en?.length || 0;
    if (len > 160) return 'text-red-500';
    if (len > 140) return 'text-amber-500';
    return 'text-gray-400';
});
</script>

<template>
    <AdminLayout :title="$t('a_edit_seo')">
        <div class="space-y-6 max-w-5xl">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/admin/seo-pages" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    {{ $t('a_back') }}
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_seo') }} — {{ seoPage.page_name_en }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Page identifier: <code class="text-xs font-mono px-1.5 py-0.5 bg-gray-100 rounded">{{ seoPage.page_identifier }}</code></p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Google Search Preview -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">{{ $t('a_google_preview') }}</h2>
                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                        <p class="text-xs text-emerald-700 font-medium truncate">{{ previewUrl }}</p>
                        <h3 class="text-lg font-medium text-blue-700 hover:underline cursor-default mt-1 leading-snug">{{ previewTitle }}</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ previewDescription }}</p>
                    </div>
                </div>

                <!-- Title -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3">{{ $t('a_page_title_section') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_en') }}</label>
                            <input v-model="form.title_en" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="Page title for search engines" />
                            <p class="mt-1 text-xs" :class="titleLengthClass">{{ form.title_en?.length || 0 }} / 60 characters</p>
                            <p v-if="form.errors.title_en" class="mt-1 text-sm text-red-600">{{ form.errors.title_en }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_ar') }}</label>
                            <input v-model="form.title_ar" type="text" dir="rtl" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="عنوان الصفحة لمحركات البحث" />
                            <p class="mt-1 text-xs text-gray-400">{{ form.title_ar?.length || 0 }} / 60 characters</p>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3">{{ $t('a_meta_desc_section') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_en') }}</label>
                            <textarea v-model="form.description_en" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="Brief description shown in search results"></textarea>
                            <p class="mt-1 text-xs" :class="descLengthClass">{{ form.description_en?.length || 0 }} / 160 characters</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_ar') }}</label>
                            <textarea v-model="form.description_ar" rows="3" dir="rtl" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="وصف مختصر يظهر في نتائج البحث"></textarea>
                            <p class="mt-1 text-xs text-gray-400">{{ form.description_ar?.length || 0 }} / 160 characters</p>
                        </div>
                    </div>
                </div>

                <!-- Keywords & Image -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3">{{ $t('a_keywords_og') }}</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_keywords') }}</label>
                        <input v-model="form.keywords" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="keyword1, keyword2, keyword3" />
                        <p class="mt-1 text-xs text-gray-400">Comma-separated keywords for this page</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_og_image_url') }}</label>
                            <input v-model="form.og_image" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="/images/og-home.jpg" />
                            <p class="mt-1 text-xs text-gray-400">Image shown when shared on social media (1200x630px recommended)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_canonical_url') }}</label>
                            <input v-model="form.canonical_url" type="text" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" placeholder="https://auraderma.com/page" />
                            <p class="mt-1 text-xs text-gray-400">Leave empty for automatic canonical URL</p>
                        </div>
                    </div>

                    <!-- OG Image Preview -->
                    <div v-if="form.og_image" class="mt-3">
                        <p class="text-xs text-gray-500 mb-2">{{ $t('a_image_preview') }}</p>
                        <img :src="form.og_image" alt="OG Image Preview" class="max-h-32 rounded-lg border border-gray-200 object-cover" @error="$event.target.style.display='none'" />
                    </div>
                </div>

                <!-- Advanced -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3">{{ $t('a_advanced_settings') }}</h2>

                    <!-- Indexable Toggle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ $t('a_allow_indexing') }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $t('a_noindex_desc') }}</p>
                        </div>
                        <button
                            type="button"
                            @click="form.is_indexable = !form.is_indexable"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-yellow-300 focus:ring-offset-2"
                            :class="form.is_indexable ? 'bg-emerald-500' : 'bg-gray-300'"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="form.is_indexable ? 'translate-x-5' : 'translate-x-0'"
                            />
                        </button>
                    </div>

                    <!-- Structured Data -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_structured_data') }}</label>
                        <textarea
                            v-model="form.structured_data"
                            rows="6"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-xs font-mono focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                            placeholder='{ "@context": "https://schema.org", "@type": "MedicalClinic", "name": "AURA Derma Clinic" }'
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-400">Optional JSON-LD structured data for rich search results</p>
                        <p v-if="form.errors.structured_data" class="mt-1 text-sm text-red-600">{{ form.errors.structured_data }}</p>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="py-2.5 px-8 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                        style="background-color: #C4A265;"
                    >
                        {{ form.processing ? $t('a_saving') : $t('a_save_seo_settings') }}
                    </button>
                    <Link href="/admin/seo-pages" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_cancel') }}</Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
