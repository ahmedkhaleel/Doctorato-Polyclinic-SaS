<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    xrays: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const dir = computed(() => page.props.dir || 'rtl');
const isRtl = computed(() => dir.value === 'rtl');
const translations = computed(() => page.props.translations || {});
function t(key) { return translations.value[key] || key; }

function $localized(obj, field) {
    if (!obj) return '';
    const lang = locale.value === 'ar' ? 'ar' : 'en';
    return obj[field + '_' + lang] || obj[field + '_en'] || obj[field] || '';
}

const typeLabels = {
    panoramic: { ar: 'بانوراما', en: 'Panoramic' },
    periapical: { ar: 'ذروية', en: 'Periapical' },
    bitewing: { ar: 'جناحية', en: 'Bitewing' },
    cephalometric: { ar: 'سيفالومتري', en: 'Cephalometric' },
    cbct: { ar: 'CBCT', en: 'CBCT' },
    occlusal: { ar: 'إطباقية', en: 'Occlusal' },
};

function typeLabel(type) {
    const labels = typeLabels[type];
    if (!labels) return type;
    return isRtl.value ? labels.ar : labels.en;
}

/* Lightbox */
const lightboxOpen = ref(false);
const lightboxImage = ref('');
function openLightbox(path) {
    lightboxImage.value = '/storage/' + path;
    lightboxOpen.value = true;
}
function closeLightbox() {
    lightboxOpen.value = false;
    lightboxImage.value = '';
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link :href="lp('')" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </Link>
            <h1 class="text-2xl font-bold text-gray-800">{{ t('p_dental_xrays') }}</h1>
        </div>

        <!-- X-rays Grid -->
        <div v-if="xrays?.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="xray in xrays.data"
                :key="xray.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:border-gray-200 transition-all"
            >
                <!-- Image -->
                <div
                    class="aspect-[4/3] bg-gray-900 relative cursor-pointer group"
                    @click="openLightbox(xray.image_path)"
                >
                    <img
                        :src="'/storage/' + xray.image_path"
                        :alt="typeLabel(xray.type)"
                        class="w-full h-full object-contain"
                    />
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center">
                        <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                    </div>
                    <!-- Type Badge -->
                    <span class="absolute top-2 ltr:right-2 rtl:left-2 text-[10px] font-semibold bg-black/60 text-white px-2 py-0.5 rounded-full">
                        {{ typeLabel(xray.type) }}
                    </span>
                </div>

                <!-- Info -->
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span v-if="xray.tooth_number" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-mono">
                            {{ isRtl ? 'سن' : 'Tooth' }} #{{ xray.tooth_number }}
                        </span>
                        <span class="text-xs text-gray-400">{{ xray.taken_date }}</span>
                    </div>
                    <p v-if="xray.doctor" class="text-xs text-gray-500 mb-1">{{ $localized(xray.doctor, 'name') }}</p>
                    <p v-if="xray.findings" class="text-xs text-gray-400 line-clamp-2">{{ xray.findings }}</p>
                    <p v-if="xray.notes" class="text-xs text-gray-400 mt-1 italic">{{ xray.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد صور أشعة' : 'No dental x-rays found' }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="xrays?.links?.length > 3" class="flex justify-center gap-1 mt-6">
            <template v-for="link in xrays.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="link.active ? 'bg-[var(--brand-primary)] text-white' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                    v-html="link.label"
                />
                <span v-else class="px-3 py-2 text-sm text-gray-300" v-html="link.label" />
            </template>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div v-if="lightboxOpen" v-focus-trap="closeLightbox" role="dialog" aria-modal="true" class="fixed inset-0 z-[999] bg-black/90 flex items-center justify-center p-4" @click.self="closeLightbox">
                <button @click="closeLightbox" class="absolute top-4 ltr:right-4 rtl:left-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <img :src="lightboxImage" class="max-w-full max-h-[90vh] object-contain rounded-lg" />
            </div>
        </Teleport>
    </div>
</template>
