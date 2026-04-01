<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const props = defineProps({
    galleryItems: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');


const search = ref('');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/gallery', { search: val || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

function deleteItem(id) {
    if (window.confirm(t('a_confirm_delete_gallery'))) {
        router.delete(`/admin/gallery/${id}`);
    }
}
</script>

<template>
    <AdminLayout :title="$t('a_gallery')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_gallery') }}</h1>
                <Link
                    v-if="can('gallery.create')"
                    href="/admin/gallery/create"
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
                    :placeholder="$t('a_search_gallery')"
                    class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                />
            </div>

            <!-- Grid View -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div
                    v-for="item in galleryItems.data"
                    :key="item.id"
                    class="bg-white rounded-lg shadow-sm overflow-hidden group"
                >
                    <div class="aspect-square bg-gray-100 relative">
                        <img
                            v-if="item.image"
                            :src="item.image"
                            :alt="item.caption_en"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <!-- Before/After badge -->
                        <span
                            v-if="item.is_before_after"
                            class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                        >
                            Before/After
                        </span>
                        <!-- Overlay with actions -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-3">
                            <Link
                                v-if="can('gallery.update')"
                                :href="`/admin/gallery/${item.id}/edit`"
                                class="px-3 py-1.5 bg-white rounded-lg text-sm font-medium text-gray-800 hover:bg-gray-100 transition"
                            >
                                Edit
                            </Link>
                            <button
                                v-if="can('gallery.delete')"
                                @click="deleteItem(item.id)"
                                class="px-3 py-1.5 bg-red-500 rounded-lg text-sm font-medium text-white hover:bg-red-600 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $localized(item, 'caption') || $t('a_untitled') }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 capitalize">{{ item.category || '-' }}</p>
                    </div>
                </div>
            </div>

            <div v-if="!galleryItems.data || galleryItems.data.length === 0" class="bg-white rounded-lg shadow-sm p-8 text-center text-sm text-gray-500">
                {{ $t('a_no_gallery_found') }}
            </div>

            <!-- Pagination -->
            <div v-if="galleryItems.links && galleryItems.links.length > 3" class="flex items-center justify-between">
                <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ galleryItems.from }} {{ $t('a_to') }} {{ galleryItems.to }} {{ $t('a_of') }} {{ galleryItems.total }} {{ $t('a_results') }}</p>
                <nav class="flex space-x-1">
                    <template v-for="link in galleryItems.links" :key="link.label">
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
    </AdminLayout>
</template>
