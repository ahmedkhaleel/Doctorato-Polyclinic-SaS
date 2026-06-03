<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { t } = useLocale();
const { confirm } = useConfirm();

const props = defineProps({
    categories: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const search = ref(props.filters?.search || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/post-categories', { search: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

function deleteCategory(id) {
    confirm(t('a_confirm_delete_post_category'), () => {
        router.post(`/admin/post-categories/${id}/delete`);
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_post_categories')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_post_categories') }}</h1>
                <Link
                    v-if="can('post_categories.create')"
                    href="/admin/post-categories/create"
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
                    :placeholder="$t('a_search_categories')"
                    class="doctorato-input w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-transparent"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_id') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_en_header') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name_ar_header') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_posts_count') }}</th>
                                <th class="px-4 md:px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(category, i) in categories.data" :key="category.id" class="lst-row hover:bg-gray-50" :style="{ '--row-i': i }">
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ category.id }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm font-medium text-gray-900">{{ category.name_en }}</td>
                                <td class="px-4 md:px-6 py-4 text-sm text-gray-500" dir="rtl">{{ category.name_ar }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ category.posts_count }}</td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                                    <Link v-if="can('post_categories.update')" :href="`/admin/post-categories/${category.id}/edit`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('post_categories.delete')" @click="deleteCategory(category.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="5" class="px-4 md:px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_post_categories_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links && categories.links.length > 3" class="px-4 md:px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ categories.from }} {{ $t('a_to') }} {{ categories.to }} {{ $t('a_of') }} {{ categories.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in categories.links" :key="link.label">
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

<style scoped>
.lst-row {
    animation: lstRowIn 0.4s cubic-bezier(0.22, 0.61, 0.36, 1) both;
    animation-delay: calc(var(--row-i, 0) * 35ms);
}
@keyframes lstRowIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: none; }
}
@media (prefers-reduced-motion: reduce) {
    .lst-row { animation: none !important; }
}
</style>
