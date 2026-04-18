<script setup>
import { ref , computed } from 'vue';
import { Link, useForm , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';

const props = defineProps({
    slide: Object,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    title_ar: props.slide.title_ar || '',
    title_en: props.slide.title_en || '',
    subtitle_ar: props.slide.subtitle_ar || '',
    subtitle_en: props.slide.subtitle_en || '',
    description_ar: props.slide.description_ar || '',
    description_en: props.slide.description_en || '',
    button_text_ar: props.slide.button_text_ar || '',
    button_text_en: props.slide.button_text_en || '',
    button_link: props.slide.button_link || '',
    image: null,
    display_order: props.slide.display_order ?? 0,
    is_active: props.slide.is_active ?? true,
    _method: 'PUT',
});

const imagePreview = ref(props.slide.image_url || null);
const imageChanged = ref(false);

function onImageChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imageChanged.value = true;
        const reader = new FileReader();
        reader.onload = (ev) => {
            imagePreview.value = ev.target.result;
        };
        reader.readAsDataURL(file);
    }
}

function submit() {
    form.post(`/webmaster/slider/${props.slide.id}`, {
        forceFormData: true,
    });
}
</script>

<template>
    <WebmasterLayout title="Edit Slide">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_edit_slide') }}</h1>
                <Link href="/webmaster/slider" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_slider') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Titles -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_slide_text') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_en') }}</label>
                                <input v-model="form.title_en" type="text" placeholder="e.g. Doctorato"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.title_en" class="mt-1 text-sm text-red-600">{{ form.errors.title_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_title_ar') }}</label>
                                <input v-model="form.title_ar" type="text" dir="rtl" placeholder="مثال: دكتوراتو"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.title_ar" class="mt-1 text-sm text-red-600">{{ form.errors.title_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle (English)</label>
                                <input v-model="form.subtitle_en" type="text" placeholder="e.g. Where Advanced Technology Meets Natural Beauty"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.subtitle_en" class="mt-1 text-sm text-red-600">{{ form.errors.subtitle_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle (Arabic)</label>
                                <input v-model="form.subtitle_ar" type="text" dir="rtl" placeholder="مثال: حيث تلتقي التكنولوجيا المتقدمة بالجمال الطبيعي"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.subtitle_ar" class="mt-1 text-sm text-red-600">{{ form.errors.subtitle_ar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_en') }}</label>
                                <textarea v-model="form.description_en" rows="3" placeholder="Short description..."
                                          class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent resize-none"></textarea>
                                <p v-if="form.errors.description_en" class="mt-1 text-sm text-red-600">{{ form.errors.description_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description_ar') }}</label>
                                <textarea v-model="form.description_ar" rows="3" dir="rtl" placeholder="وصف قصير..."
                                          class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent resize-none"></textarea>
                                <p v-if="form.errors.description_ar" class="mt-1 text-sm text-red-600">{{ form.errors.description_ar }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Button -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_cta_button') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text (English)</label>
                                <input v-model="form.button_text_en" type="text" placeholder="e.g. Book Appointment"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.button_text_en" class="mt-1 text-sm text-red-600">{{ form.errors.button_text_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Button Text (Arabic)</label>
                                <input v-model="form.button_text_ar" type="text" dir="rtl" placeholder="مثال: احجزي موعدك"
                                       class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.button_text_ar" class="mt-1 text-sm text-red-600">{{ form.errors.button_text_ar }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Button Link</label>
                            <input v-model="form.button_link" type="text" placeholder="e.g. /booking or https://..."
                                   class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p class="mt-1 text-xs text-gray-400">Use relative path like /booking or full URL</p>
                            <p v-if="form.errors.button_link" class="mt-1 text-sm text-red-600">{{ form.errors.button_link }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Image Upload -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_slide_image') }}</h3>

                        <div
                            class="relative border-2 border-dashed rounded-lg overflow-hidden transition"
                            :class="imagePreview ? 'border-transparent' : 'border-gray-300 hover:border-gray-400'"
                        >
                            <div v-if="imagePreview" class="aspect-[16/9]">
                                <img :src="imagePreview" class="w-full h-full object-cover" />
                                <label class="absolute inset-0 bg-black/40 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                                    <span class="text-white text-sm font-medium">Change Image</span>
                                    <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImageChange" class="hidden" />
                                </label>
                            </div>
                            <label v-else class="flex flex-col items-center justify-center py-8 cursor-pointer">
                                <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z"/>
                                </svg>
                                <span class="text-sm text-gray-500">Click to upload image</span>
                                <span class="text-xs text-gray-400 mt-1">Recommended: 1920x1080px</span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImageChange" class="hidden" />
                            </label>
                        </div>
                        <p v-if="form.errors.image" class="text-sm text-red-600">{{ form.errors.image }}</p>
                    </div>

                    <!-- Settings -->
                    <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_settings') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0"
                                   class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-yellow-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C4A265]"></div>
                            </label>
                            <span class="text-sm text-gray-700">Active</span>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50 hover:opacity-90"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_update_slide') }}
                        </button>
                        <Link href="/webmaster/slider" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">{{ $t('a_cancel') }}</Link>
                    </div>
                </div>
            </form>
        </div>
    </WebmasterLayout>
</template>
