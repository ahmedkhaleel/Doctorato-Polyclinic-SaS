<script setup>
import { computed } from 'vue';
import { Link, useForm , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import RichTextEditor from '@/Components/Admin/RichTextEditor.vue';
import SearchableSelect from '@/Components/Admin/SearchableSelect.vue';

const props = defineProps({
    categories: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    name_ar: '',
    name_en: '',
    category_id: '',
    short_desc_ar: '',
    short_desc_en: '',
    full_desc_ar: '',
    full_desc_en: '',
    featured_image: null,
    icon: '',
    benefits_ar: '',
    benefits_en: '',
    sessions_count: '',
    results_ar: '',
    results_en: '',
    status: 'active',
    show_on_home: false,
    show_on_website: true,
    bookable: true,
    seo_title_ar: '',
    seo_title_en: '',
    seo_desc_ar: '',
    seo_desc_en: '',
    seo_keywords: '',
});

const categoryOptions = computed(() => (props.categories || []).map(c => ({ value: c.id, label: c.name_en })));

function submit() {
    form.post('/webmaster/services', {
        forceFormData: true,
    });
}
</script>

<template>
    <WebmasterLayout title="Create Service">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_create_service') }}</h1>
                <Link href="/webmaster/services" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_services') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }}</label>
                                <input v-model="form.name_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-600">{{ form.errors.name_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }}</label>
                                <input v-model="form.name_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                                <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.name_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Short Description (English)</label>
                                <textarea v-model="form.short_desc_en" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.short_desc_en" class="mt-1 text-sm text-red-600">{{ form.errors.short_desc_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Short Description (Arabic)</label>
                                <textarea v-model="form.short_desc_ar" rows="3" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.short_desc_ar" class="mt-1 text-sm text-red-600">{{ form.errors.short_desc_ar }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Description (English)</label>
                            <RichTextEditor v-model="form.full_desc_en" dir="ltr" placeholder="Write full description..." />
                            <p v-if="form.errors.full_desc_en" class="mt-1 text-sm text-red-600">{{ form.errors.full_desc_en }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Description (Arabic)</label>
                            <RichTextEditor v-model="form.full_desc_ar" dir="rtl" placeholder="اكتب الوصف الكامل..." />
                            <p v-if="form.errors.full_desc_ar" class="mt-1 text-sm text-red-600">{{ form.errors.full_desc_ar }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Benefits (English)</label>
                                <textarea v-model="form.benefits_en" rows="4" placeholder="One benefit per line" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.benefits_en" class="mt-1 text-sm text-red-600">{{ form.errors.benefits_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Benefits (Arabic)</label>
                                <textarea v-model="form.benefits_ar" rows="4" dir="rtl" placeholder="One benefit per line" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.benefits_ar" class="mt-1 text-sm text-red-600">{{ form.errors.benefits_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Results (English)</label>
                                <textarea v-model="form.results_en" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.results_en" class="mt-1 text-sm text-red-600">{{ form.errors.results_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Results (Arabic)</label>
                                <textarea v-model="form.results_ar" rows="3" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                                <p v-if="form.errors.results_ar" class="mt-1 text-sm text-red-600">{{ form.errors.results_ar }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Section -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_seo_settings') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Title (English)</label>
                                <input v-model="form.seo_title_en" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Title (Arabic)</label>
                                <input v-model="form.seo_title_ar" type="text" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Description (English)</label>
                                <textarea v-model="form.seo_desc_en" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO Description (Arabic)</label>
                                <textarea v-model="form.seo_desc_ar" rows="2" dir="rtl" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"></textarea>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SEO Keywords</label>
                            <input v-model="form.seo_keywords" type="text" placeholder="comma, separated, keywords" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_details') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <SearchableSelect v-model="form.category_id" :options="categoryOptions" placeholder="Select Category" searchPlaceholder="Search categories..." :error="form.errors.category_id" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                            <select v-model="form.status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent">
                                <option value="active">{{ $t('a_active') }}</option>
                                <option value="inactive">{{ $t('a_inactive') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sessions Count</label>
                            <input v-model="form.sessions_count" type="number" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Icon (CSS class or SVG name)</label>
                            <input v-model="form.icon" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent" />
                        </div>

                        <!-- Visibility Settings -->
                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_visibility') }}</p>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input v-model="form.show_on_website" type="checkbox" id="show_on_website" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="show_on_website" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">Show on Website</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ml-6 -mt-1">Display on the public services page</p>

                                <div class="flex items-center">
                                    <input v-model="form.show_on_home" type="checkbox" id="show_on_home" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="show_on_home" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">Show on Homepage</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ml-6 -mt-1">Feature on the homepage</p>

                                <div class="flex items-center">
                                    <input v-model="form.bookable" type="checkbox" id="bookable" class="h-4 w-4 rounded border-gray-300 text-[#C4A265] focus:ring-[#C4A265]" />
                                    <label for="bookable" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">Available for Booking</label>
                                </div>
                                <p class="text-[10px] text-gray-400 ml-6 -mt-1">Show in booking forms for patients & staff</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_featured_image') }}</h3>
                        <input
                            type="file"
                            accept="image/*"
                            @input="form.featured_image = $event.target.files[0]"
                            class="w-full text-sm text-gray-500 ltr:file:mr-4 rtl:file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <p v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">{{ form.errors.featured_image }}</p>
                    </div>

                    <div class="flex ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_create_service') }}
                        </button>
                        <Link href="/webmaster/services" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">{{ $t('a_cancel') }}</Link>
                    </div>
                </div>
            </form>
        </div>
    </WebmasterLayout>
</template>
