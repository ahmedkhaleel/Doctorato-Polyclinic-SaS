<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    galleryItem: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    category: props.galleryItem.category || '',
    image_path: null,
    video_url: props.galleryItem.video_url || '',
    caption_ar: props.galleryItem.caption_ar || '',
    caption_en: props.galleryItem.caption_en || '',
    is_before_after: props.galleryItem.is_before_after || false,
    before_image: null,
    after_image: null,
    display_order: props.galleryItem.display_order || 0,
    is_visible: props.galleryItem.is_visible !== undefined ? props.galleryItem.is_visible : true,
    _method: 'PUT',
});

const categories = [
    { value: 'clinic', label: 'Clinic' },
    { value: 'before-after', label: 'Before & After' },
    { value: 'team', label: 'Team' },
    { value: 'equipment', label: 'Equipment' },
    { value: 'services', label: 'Services' },
];

function submit() {
    form.post(`/admin/gallery/${props.galleryItem.id}`, {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_gallery_item')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_gallery_item') }}</h1>
                <Link href="/admin/gallery" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_gallery') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_caption_en') }}</label>
                                <input v-model="form.caption_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.caption_en" class="mt-1 text-sm text-red-600">{{ form.errors.caption_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_caption_ar') }}</label>
                                <input v-model="form.caption_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.caption_ar" class="mt-1 text-sm text-red-600">{{ form.errors.caption_ar }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_image') }}</label>
                            <div v-if="galleryItem.image" class="mb-3">
                                <img :src="galleryItem.image" alt="Current image" class="w-40 h-40 object-cover rounded-lg" />
                            </div>
                            <input
                                type="file"
                                accept="image/*"
                                @input="form.image_path = $event.target.files[0]"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                            />
                            <p class="text-xs text-gray-400 mt-1">{{ $t('a_leave_empty_keep_current') }}</p>
                            <p v-if="form.errors.image_path" class="mt-1 text-sm text-red-600">{{ form.errors.image_path }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_video_url') }}</label>
                            <input v-model="form.video_url" type="url" placeholder="https://youtube.com/..." class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.video_url" class="mt-1 text-sm text-red-600">{{ form.errors.video_url }}</p>
                        </div>

                        <div class="border-t border-gray-200 pt-5">
                            <div class="flex items-center mb-4">
                                <input v-model="form.is_before_after" type="checkbox" id="is_before_after" class="h-4 w-4 rounded border-gray-300" />
                                <label for="is_before_after" class="ltr:ml-2 rtl:mr-2 text-sm font-medium text-gray-700">{{ $t('a_before_after_comparison') }}</label>
                            </div>

                            <div v-if="form.is_before_after" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_before_image') }}</label>
                                    <div v-if="galleryItem.before_image_url" class="mb-2">
                                        <img :src="galleryItem.before_image_url" alt="Before" class="w-32 h-32 object-cover rounded-lg" />
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @input="form.before_image = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                                    />
                                    <p v-if="form.errors.before_image" class="mt-1 text-sm text-red-600">{{ form.errors.before_image }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_after_image') }}</label>
                                    <div v-if="galleryItem.after_image_url" class="mb-2">
                                        <img :src="galleryItem.after_image_url" alt="After" class="w-32 h-32 object-cover rounded-lg" />
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @input="form.after_image = $event.target.files[0]"
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                                    />
                                    <p v-if="form.errors.after_image" class="mt-1 text-sm text-red-600">{{ form.errors.after_image }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_details') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_category') }}</label>
                            <select v-model="form.category" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="">{{ $t('a_select_category') }}</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>

                        <div class="flex items-center">
                            <input v-model="form.is_visible" type="checkbox" id="is_visible" class="h-4 w-4 rounded border-gray-300" />
                            <label for="is_visible" class="ltr:ml-2 rtl:mr-2 text-sm text-gray-700">{{ $t('a_visible') }}</label>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_update_item') }}
                        </button>
                        <Link href="/admin/gallery" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
