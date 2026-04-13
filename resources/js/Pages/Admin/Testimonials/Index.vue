<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const props = defineProps({
    testimonials: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const search = ref('');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/testimonials', { search: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

function deleteTestimonial(id) {
    if (window.confirm(t('a_confirm_delete_testimonial'))) {
        router.post(`/admin/testimonials/${id}/delete`);
    }
}

const statusColors = {
    approved: 'bg-green-100 text-green-800',
    pending: 'bg-yellow-100 text-yellow-800',
    rejected: 'bg-red-100 text-red-800',
};

function renderStars(rating) {
    return Array.from({ length: 5 }, (_, i) => i < rating ? 'text-yellow-400' : 'text-gray-300');
}
</script>

<template>
    <AdminLayout :title="$t('a_testimonials')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_testimonials') }}</h1>
                <Link
                    v-if="can('testimonials.create')"
                    href="/admin/testimonials/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_testimonials')"
                    class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_client') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_rating') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="t in testimonials.data" :key="t.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $localized(t, 'client_name') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex space-x-0.5">
                                        <svg
                                            v-for="(cls, i) in renderStars(t.rating)"
                                            :key="i"
                                            :class="cls"
                                            class="w-4 h-4"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[t.status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ t.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                                    <Link v-if="can('testimonials.update')" :href="`/admin/testimonials/${t.id}/edit`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('testimonials.delete')" @click="deleteTestimonial(t.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!testimonials.data || testimonials.data.length === 0">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_testimonials_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="testimonials.links && testimonials.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ testimonials.from }} {{ $t('a_to') }} {{ testimonials.to }} {{ $t('a_of') }} {{ testimonials.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in testimonials.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
