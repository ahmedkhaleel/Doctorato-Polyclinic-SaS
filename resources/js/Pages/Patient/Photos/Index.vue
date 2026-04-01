<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientStatus } from '@/Composables/usePatientStatus';

defineOptions({ layout: PatientLayout });

const { photoTypeLabel } = usePatientStatus();

const props = defineProps({
    visitPhotos: Array,
    patientPhotos: Array,
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

const allPhotos = computed(() => {
    const photos = [];
    if (props.visitPhotos?.length) photos.push(...props.visitPhotos);
    if (props.patientPhotos?.length) photos.push(...props.patientPhotos);
    return photos;
});

const typeColors = {
    before: 'bg-blue-100 text-blue-700',
    after: 'bg-green-100 text-green-700',
    progress: 'bg-yellow-100 text-yellow-700',
};

// Modal
const selectedPhoto = ref(null);
function openPhoto(photo) { selectedPhoto.value = photo; }
function closePhoto() { selectedPhoto.value = null; }
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">{{ t('p_my_photos') }}</h1>

        <!-- Photo Grid -->
        <div v-if="allPhotos.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
                v-for="photo in allPhotos"
                :key="photo.id"
                class="relative group cursor-pointer"
                @click="openPhoto(photo)"
            >
                <img
                    :src="photo.photo_url || photo.url || ('/storage/' + photo.photo_path)"
                    :alt="photo.type || 'Photo'"
                    class="w-full h-48 object-cover rounded-2xl border border-gray-100 shadow-sm group-hover:shadow-md group-hover:scale-[1.02] transition-all duration-200"
                />
                <!-- Type badge -->
                <span
                    v-if="photo.type"
                    :class="typeColors[photo.type] || 'bg-gray-100 text-gray-600'"
                    class="absolute top-2 ltr:left-2 rtl:right-2 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                >
                    {{ photoTypeLabel(photo.type) }}
                </span>
                <!-- Overlay info -->
                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent rounded-b-2xl p-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                    <p v-if="photo.visit_date || photo.created_at" class="text-white text-xs">{{ photo.visit_date || photo.created_at?.split('T')[0] }}</p>
                    <p v-if="photo.service_name || photo.service" class="text-white/70 text-[10px] mt-0.5">{{ $localized(photo.service, 'name') || photo.service_name }}</p>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <p class="text-gray-400 text-sm">{{ isRtl ? 'لا توجد صور' : 'No photos found' }}</p>
        </div>

        <!-- Photo Modal -->
        <Teleport to="body">
            <div v-if="selectedPhoto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" @click.self="closePhoto">
                <div class="relative max-w-4xl max-h-[90vh] mx-4">
                    <button @click="closePhoto" class="absolute -top-10 ltr:right-0 rtl:left-0 text-white/70 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <img
                        :src="selectedPhoto.photo_url || selectedPhoto.url || ('/storage/' + selectedPhoto.photo_path)"
                        :alt="selectedPhoto.type || 'Photo'"
                        class="max-w-full max-h-[85vh] object-contain rounded-xl"
                    />
                    <div class="mt-3 text-center">
                        <span v-if="selectedPhoto.type" :class="typeColors[selectedPhoto.type] || 'bg-white/20 text-white'" class="text-xs font-semibold px-3 py-1 rounded-full">{{ photoTypeLabel(selectedPhoto.type) }}</span>
                        <p v-if="selectedPhoto.visit_date" class="text-white/60 text-sm mt-2">{{ selectedPhoto.visit_date }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
