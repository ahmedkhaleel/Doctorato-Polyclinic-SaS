<script setup>
import { computed } from 'vue';
import { Link , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({
    pages: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');

</script>

<template>
    <WebmasterLayout title="Pages">
        <div class="space-y-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_pages') }}</h1>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_title') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_slug') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="page in pages" :key="page.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ page.title_en }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ page.slug }}</td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm">
                                    <Link v-if="can('pages.update')" :href="`/webmaster/pages/${page.id}/edit`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</Link>
                                </td>
                            </tr>
                            <tr v-if="!pages || pages.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_pages_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </WebmasterLayout>
</template>
