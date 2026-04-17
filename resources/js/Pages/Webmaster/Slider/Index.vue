<script setup>
import { ref , computed } from 'vue';
import { Link, router , usePage } from '@inertiajs/vue3';
import WebmasterLayout from '@/Layouts/WebmasterLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({
    slides: Array,
});

const __page = usePage();
const locale = computed(() => __page.props.locale || 'ar');
const isRtl = computed(() => (__page.props.dir || 'rtl') === 'rtl');


const dragging = ref(null);
const dragOver = ref(null);

function deleteSlide(id) {
    if (window.confirm(isRtl ? 'هل أنت متأكد من حذف هذا السلايد؟' : 'Are you sure you want to delete this slide?')) {
        router.post(`/webmaster/slider/${id}/delete`);
    }
}

function toggleActive(slide) {
    router.post(`/webmaster/slider/${slide.id}/update`, {
        title_ar: slide.title_ar || '',
        title_en: slide.title_en || '',
        subtitle_ar: slide.subtitle_ar || '',
        subtitle_en: slide.subtitle_en || '',
        description_ar: slide.description_ar || '',
        description_en: slide.description_en || '',
        button_text_ar: slide.button_text_ar || '',
        button_text_en: slide.button_text_en || '',
        button_link: slide.button_link || '',
        display_order: slide.display_order,
        is_active: !slide.is_active,
    }, { preserveState: true });
}

function onDragStart(index) {
    dragging.value = index;
}

function onDragOver(e, index) {
    e.preventDefault();
    dragOver.value = index;
}

function onDrop(index) {
    if (dragging.value === null || dragging.value === index) return;

    const items = [...props.slides];
    const [moved] = items.splice(dragging.value, 1);
    items.splice(index, 0, moved);

    const ordered = items.map((item, i) => ({
        id: item.id,
        display_order: i,
    }));

    router.post('/webmaster/slider/update-order', { slides: ordered }, {
        preserveState: true,
    });

    dragging.value = null;
    dragOver.value = null;
}

function onDragEnd() {
    dragging.value = null;
    dragOver.value = null;
}
</script>

<template>
    <WebmasterLayout title="Hero Slider">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_hero_slider') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_manage_slider_desc') }}</p>
                </div>
                <Link
                    v-if="can('settings.update')"
                    href="/webmaster/slider/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition hover:opacity-90"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Slide
                </Link>
            </div>

            <!-- Slides Grid -->
            <div v-if="slides && slides.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover="(e) => onDragOver(e, index)"
                    @drop="onDrop(index)"
                    @dragend="onDragEnd"
                    class="bg-white rounded-xl shadow-sm overflow-hidden border-2 transition-all duration-200 cursor-grab active:cursor-grabbing"
                    :class="[
                        dragOver === index ? 'border-[#C4A265] scale-[1.02]' : 'border-transparent hover:border-gray-200',
                        !slide.is_active ? 'opacity-60' : ''
                    ]"
                >
                    <!-- Image -->
                    <div class="relative aspect-[16/9] bg-gray-100 overflow-hidden">
                        <img
                            :src="slide.image_url"
                            :alt="slide.title_en || 'Slide'"
                            class="w-full h-full object-cover"
                        />
                        <!-- Order badge -->
                        <div class="absolute top-3 left-3 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg"
                             style="background-color: #C4A265;">
                            {{ index + 1 }}
                        </div>
                        <!-- Status badge -->
                        <div class="absolute top-3 right-3">
                            <span
                                :class="slide.is_active ? 'bg-emerald-500' : 'bg-gray-400'"
                                class="px-2.5 py-1 rounded-full text-white text-xs font-medium shadow-lg"
                            >
                                {{ slide.is_active ? $t('a_active') : $t('a_inactive') }}
                            </span>
                        </div>
                        <!-- Overlay with text preview -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                        <div class="absolute bottom-3 left-3 right-3">
                            <p v-if="slide.title_en" class="text-white font-semibold text-sm truncate">{{ slide.title_en }}</p>
                            <p v-if="slide.subtitle_en" class="text-white/70 text-xs truncate mt-0.5">{{ slide.subtitle_en }}</p>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p v-if="slide.title_ar" class="text-gray-600 text-sm truncate" dir="rtl">{{ slide.title_ar }}</p>
                                <p v-if="slide.button_link" class="text-gray-400 text-xs mt-1 truncate">
                                    Link: {{ slide.button_link }}
                                </p>
                            </div>
                            <!-- Drag handle -->
                            <div class="flex-shrink-0 text-gray-300 mt-0.5">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="can('settings.update')" class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100">
                            <Link
                                :href="`/webmaster/slider/${slide.id}/edit`"
                                class="flex-1 text-center px-3 py-1.5 rounded-lg text-sm font-medium transition hover:opacity-90 text-white"
                                style="background-color: #C4A265;"
                            >
                                Edit
                            </Link>
                            <button
                                @click="toggleActive(slide)"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition"
                                :class="slide.is_active
                                    ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                            >
                                {{ slide.is_active ? $t('a_hide') : $t('a_show') }}
                            </button>
                            <button
                                @click="deleteSlide(slide.id)"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium bg-red-50 text-red-600 hover:bg-red-100 transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-50 flex items-center justify-center">
                    <svg class="w-8 h-8" style="color: #C4A265;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5zm14.25-11.25a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $t('a_no_slides_yet') }}</h3>
                <p class="text-gray-500 text-sm mb-6">{{ $t('a_add_slides_desc') }}</p>
                <Link
                    v-if="can('settings.update')"
                    href="/webmaster/slider/create"
                    class="inline-flex items-center px-5 py-2.5 rounded-lg text-white text-sm font-medium transition hover:opacity-90"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add First Slide
                </Link>
            </div>

            <!-- Drag hint -->
            <p v-if="slides && slides.length > 1" class="text-xs text-gray-400 text-center">
                Drag and drop slides to reorder them
            </p>
        </div>
    </WebmasterLayout>
</template>
