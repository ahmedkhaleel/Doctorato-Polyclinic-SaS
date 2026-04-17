<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    seoPages: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


function truncate(text, length = 60) {
    if (!text) return '—';
    return text.length > length ? text.substring(0, length) + '...' : text;
}
</script>

<template>
    <AdminLayout :title="$t('a_seo_pages')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_seo_pages') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_manage_seo') }}</p>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-xs font-medium text-amber-700">{{ seoPages?.length || 0 }} {{ $t('a_pages_configured') }}</span>
                </div>
            </div>

            <!-- Pages Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_page') }}</th>
                            <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_title_en') }}</th>
                            <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_description_en') }}</th>
                            <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_indexable') }}</th>
                            <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="page in seoPages" :key="page.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold" style="background-color: #C4A265;">
                                        {{ page.page_identifier?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ page.page_name_en }}</p>
                                        <p class="text-xs text-gray-400 font-mono">/{{ page.page_identifier }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                                <p class="text-gray-700">{{ truncate(page.title_en, 45) }}</p>
                                <p class="text-xs mt-0.5" :class="(page.title_en?.length || 0) > 60 ? 'text-red-500' : 'text-gray-400'">
                                    {{ page.title_en?.length || 0 }} / 60 chars
                                </p>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                                <p class="text-gray-600 text-xs leading-relaxed">{{ truncate(page.description_en, 80) }}</p>
                                <p class="text-xs mt-0.5" :class="(page.description_en?.length || 0) > 160 ? 'text-red-500' : 'text-gray-400'">
                                    {{ page.description_en?.length || 0 }} / 160 chars
                                </p>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                                <span v-if="page.is_indexable" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ $t('a_yes') }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    {{ $t('a_no') }}
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                                <Link
                                    :href="`/admin/seo-pages/${page.id}/edit`"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all hover:opacity-80"
                                    style="color: #C4A265; border-color: rgba(196, 162, 101, 0.4);"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    {{ $t('a_edit_seo_btn') }}
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="!seoPages?.length" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">{{ $t('a_no_seo_pages_found') }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
