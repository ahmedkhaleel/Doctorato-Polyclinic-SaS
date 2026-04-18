<script setup>
import { watch, computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';

const props = defineProps({
    categories: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    title_ar: '',
    title_en: '',
    slug: '',
    category_id: '',
    excerpt_ar: '',
    excerpt_en: '',
    content_ar: '',
    content_en: '',
    featured_image: null,
    status: 'draft',
    is_featured: false,
    published_at: '',
});

// Auto-generate slug from English title
watch(() => form.title_en, (val) => {
    form.slug = val
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
});

function submit() {
    form.post('/admin/posts', {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_create_post')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_create_post') }}</h1>
                <Link href="/admin/posts" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_posts') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content (Left) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <!-- Title EN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_en') }}</label>
                            <input v-model="form.title_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.title_en" class="mt-1 text-sm text-red-600">{{ form.errors.title_en }}</p>
                        </div>

                        <!-- Title AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_ar') }}</label>
                            <input v-model="form.title_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.title_ar" class="mt-1 text-sm text-red-600">{{ form.errors.title_ar }}</p>
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_slug') }}</label>
                            <input v-model="form.slug" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                        </div>

                        <!-- Excerpt EN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_excerpt_en') }}</label>
                            <textarea v-model="form.excerpt_en" rows="2" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.excerpt_en" class="mt-1 text-sm text-red-600">{{ form.errors.excerpt_en }}</p>
                        </div>

                        <!-- Excerpt AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_excerpt_ar') }}</label>
                            <textarea v-model="form.excerpt_ar" rows="2" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.excerpt_ar" class="mt-1 text-sm text-red-600">{{ form.errors.excerpt_ar }}</p>
                        </div>

                        <!-- Content EN -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_content_en') }}</label>
                            <RichTextEditor v-model="form.content_en" dir="ltr" placeholder="Write content..." />
                            <p v-if="form.errors.content_en" class="mt-1 text-sm text-red-600">{{ form.errors.content_en }}</p>
                        </div>

                        <!-- Content AR -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_content_ar') }}</label>
                            <RichTextEditor v-model="form.content_ar" dir="rtl" placeholder="اكتب المحتوى..." />
                            <p v-if="form.errors.content_ar" class="mt-1 text-sm text-red-600">{{ form.errors.content_ar }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Right) -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_publish') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                            <select v-model="form.status" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="draft">{{ $t('a_draft') }}</option>
                                <option value="published">{{ $t('a_published') }}</option>
                                <option value="scheduled">{{ $t('a_scheduled') }}</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_published_at_label') }}</label>
                            <input v-model="form.published_at" type="datetime-local" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.published_at" class="mt-1 text-sm text-red-600">{{ form.errors.published_at }}</p>
                        </div>

                        <div class="flex items-center">
                            <input v-model="form.is_featured" type="checkbox" id="is_featured" class="h-4 w-4 rounded border-gray-300" />
                            <label for="is_featured" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">{{ $t('a_featured_post') }}</label>
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_category') }}</h3>
                        <div>
                            <select v-model="form.category_id" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="">{{ $t('a_select_category') }}</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_en }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_featured_image') }}</h3>
                        <div>
                            <input
                                type="file"
                                accept="image/*"
                                @input="form.featured_image = $event.target.files[0]"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                            />
                            <p v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">{{ form.errors.featured_image }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_post') }}
                        </button>
                        <Link href="/admin/posts" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
