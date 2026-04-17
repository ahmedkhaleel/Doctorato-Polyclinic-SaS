<script setup>
import { ref, watch , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({
    services: Object,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const search = ref('');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/webmaster/services', { search: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

function deleteService(id) {
    if (window.confirm(isRtl ? 'هل أنت متأكد من حذف هذه الخدمة؟' : 'Are you sure you want to delete this service?')) {
        router.post(`/webmaster/services/${id}/delete`);
    }
}

const statusColors = {
    active: 'bg-emerald-100 text-emerald-800',
    inactive: 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <WebmasterLayout title="Services">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_services') }}</h1>
                <Link
                    v-if="can('services.create')"
                    href="/webmaster/services/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_new') }}
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_services')"
                    class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_category') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_order') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="service in services.data" :key="service.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ service.name_en }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.category?.name_en ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[service.status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ service.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ service.display_order ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                                    <Link v-if="can('services.update')" :href="`/webmaster/services/${service.id}/edit`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('services.delete')" @click="deleteService(service.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!services.data || services.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_services_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="services.links && services.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ services.from }} {{ $t('a_to') }} {{ services.to }} {{ $t('a_of') }} {{ services.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in services.links" :key="link.label">
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
    </WebmasterLayout>
</template>
