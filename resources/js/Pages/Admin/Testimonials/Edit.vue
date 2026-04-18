<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    testimonial: Object,
    services: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const form = useForm({
    client_name_ar: props.testimonial.client_name_ar || '',
    client_name_en: props.testimonial.client_name_en || '',
    service_id: props.testimonial.service_id || '',
    rating: props.testimonial.rating || 5,
    review_ar: props.testimonial.review_ar || '',
    review_en: props.testimonial.review_en || '',
    photo: null,
    video_url: props.testimonial.video_url || '',
    status: props.testimonial.status || 'pending',
    display_order: props.testimonial.display_order || 0,
    _method: 'PUT',
});

function submit() {
    form.post(`/admin/testimonials/${props.testimonial.id}`, {
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_edit_testimonial')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_testimonial') }}</h1>
                <Link href="/admin/testimonials" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('a_back_to_testimonials') }}</Link>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_client_name_en') }}</label>
                                <input v-model="form.client_name_en" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.client_name_en" class="mt-1 text-sm text-red-600">{{ form.errors.client_name_en }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_client_name_ar') }}</label>
                                <input v-model="form.client_name_ar" type="text" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                                <p v-if="form.errors.client_name_ar" class="mt-1 text-sm text-red-600">{{ form.errors.client_name_ar }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_review_en') }}</label>
                            <textarea v-model="form.review_en" rows="5" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.review_en" class="mt-1 text-sm text-red-600">{{ form.errors.review_en }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_review_ar') }}</label>
                            <textarea v-model="form.review_ar" rows="5" dir="rtl" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"></textarea>
                            <p v-if="form.errors.review_ar" class="mt-1 text-sm text-red-600">{{ form.errors.review_ar }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_video_url') }}</label>
                            <input v-model="form.video_url" type="url" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                            <p v-if="form.errors.video_url" class="mt-1 text-sm text-red-600">{{ form.errors.video_url }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_details') }}</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_service_name') }}</label>
                            <select v-model="form.service_id" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="">{{ $t('a_select_service') }}</option>
                                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_rating') }}</label>
                            <select v-model="form.rating" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option :value="5">5 Stars</option>
                                <option :value="4">4 Stars</option>
                                <option :value="3">3 Stars</option>
                                <option :value="2">2 Stars</option>
                                <option :value="1">1 Star</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_status') }}</label>
                            <select v-model="form.status" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent">
                                <option value="pending">{{ $t('a_pending') }}</option>
                                <option value="approved">{{ $t('a_approved') }}</option>
                                <option value="rejected">{{ $t('a_rejected') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_display_order') }}</label>
                            <input v-model="form.display_order" type="number" min="0" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent" />
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6 space-y-5">
                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ $t('a_client_photo') }}</h3>
                        <div v-if="testimonial.photo" class="mb-3">
                            <img :src="`/storage/${testimonial.photo}`" alt="Client photo" class="w-20 h-20 object-cover rounded-full" />
                        </div>
                        <input
                            type="file"
                            accept="image/*"
                            @input="form.photo = $event.target.files[0]"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        />
                        <p class="text-xs text-gray-400">{{ $t('a_leave_empty_keep_photo') }}</p>
                        <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">{{ form.errors.photo }}</p>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 py-2.5 px-4 rounded-lg text-white font-medium text-sm transition disabled:opacity-50"
                            style="background-color: #C4A265;"
                        >
                            {{ form.processing ? $t('a_saving') : $t('a_update_testimonial') }}
                        </button>
                        <Link href="/admin/testimonials" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
