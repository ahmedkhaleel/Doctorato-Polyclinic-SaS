<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    data: { type: Object, default: null },
    patient: { type: Object, required: true },
    role: { type: String, default: 'admin' },
    readonly: { type: Boolean, default: false },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const visits = computed(() => props.data?.visits || []);
const photos = computed(() => props.data?.photos || []);
const prescriptions = computed(() => props.data?.prescriptions || []);
const diagnoses = computed(() => props.data?.diagnoses || []);
const stats = computed(() => props.data?.stats || {});

const beforePhotos = computed(() => photos.value.filter(p => p.photo_type === 'before'));
const afterPhotos = computed(() => photos.value.filter(p => p.photo_type === 'after'));
const otherPhotos = computed(() => photos.value.filter(p => !['before', 'after'].includes(p.photo_type)));

function formatDate(d) {
    if (!d) return '-';
    try {
        return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return d;
    }
}

const photoPreview = ref(null);
function openPhoto(photo) {
    photoPreview.value = photo;
}
function closePhoto() {
    photoPreview.value = null;
}
</script>

<template>
    <div v-if="data" class="space-y-5">
        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-gradient-to-br from-[#FBF7EE] to-[#F5EDD8] rounded-xl border border-[#C4A265]/30 p-4">
                <p class="text-2xl font-bold text-[#C4A265]">{{ stats.total_visits || 0 }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ isRtl ? 'إجمالي الزيارات' : 'Total Visits' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-2xl font-bold text-emerald-600">{{ stats.completed_visits || 0 }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ isRtl ? 'مكتمل' : 'Completed' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 p-4">
                <p class="text-2xl font-bold text-[#1B365D]">{{ stats.total_photos || 0 }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ isRtl ? 'الصور' : 'Photos' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-100 p-4">
                <p class="text-2xl font-bold text-[#1B365D]">{{ prescriptions.length }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ isRtl ? 'الوصفات' : 'Prescriptions' }}</p>
            </div>
        </div>

        <!-- Diagnoses summary -->
        <div v-if="diagnoses.length" class="bg-white rounded-xl border border-gray-100 p-5">
            <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                {{ isRtl ? 'التشخيصات' : 'Diagnoses' }}
            </h3>
            <div class="flex flex-wrap gap-2">
                <span v-for="(dx, i) in diagnoses" :key="i"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#C4A265]/10 text-[#8B6F3F] text-xs font-semibold">
                    {{ dx }}
                </span>
            </div>
        </div>

        <!-- Before/After Photos -->
        <div v-if="photos.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    {{ isRtl ? 'الصور' : 'Photos' }} ({{ photos.length }})
                </h3>
            </div>
            <div class="p-5 space-y-4">
                <div v-if="beforePhotos.length">
                    <p class="text-xs font-bold text-amber-600 uppercase mb-2">{{ isRtl ? 'قبل' : 'Before' }} ({{ beforePhotos.length }})</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <button v-for="photo in beforePhotos" :key="photo.id" type="button" @click="openPhoto(photo)"
                            class="aspect-square rounded-lg overflow-hidden bg-gray-100 relative group">
                            <img :src="photo.url || ('/storage/' + photo.photo_path)" class="w-full h-full object-cover" alt="" />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-1.5">
                                <p class="text-white text-[9px]">{{ formatDate(photo.created_at) }}</p>
                            </div>
                        </button>
                    </div>
                </div>
                <div v-if="afterPhotos.length">
                    <p class="text-xs font-bold text-emerald-600 uppercase mb-2">{{ isRtl ? 'بعد' : 'After' }} ({{ afterPhotos.length }})</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <button v-for="photo in afterPhotos" :key="photo.id" type="button" @click="openPhoto(photo)"
                            class="aspect-square rounded-lg overflow-hidden bg-gray-100 relative group">
                            <img :src="photo.url || ('/storage/' + photo.photo_path)" class="w-full h-full object-cover" alt="" />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-1.5">
                                <p class="text-white text-[9px]">{{ formatDate(photo.created_at) }}</p>
                            </div>
                        </button>
                    </div>
                </div>
                <div v-if="otherPhotos.length">
                    <p class="text-xs font-bold text-gray-500 uppercase mb-2">{{ isRtl ? 'صور أخرى' : 'Other' }} ({{ otherPhotos.length }})</p>
                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <button v-for="photo in otherPhotos" :key="photo.id" type="button" @click="openPhoto(photo)"
                            class="aspect-square rounded-lg overflow-hidden bg-gray-100 relative group">
                            <img :src="photo.url || ('/storage/' + photo.photo_path)" class="w-full h-full object-cover" alt="" />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-1.5">
                                <p class="text-white text-[9px]">{{ formatDate(photo.created_at) }}</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Derma Visits -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    {{ isRtl ? 'زيارات الجلدية' : 'Dermatology Visits' }}
                </h3>
            </div>
            <div v-if="visits.length" class="divide-y divide-gray-50">
                <div v-for="visit in visits" :key="visit.id" class="px-5 py-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#C4A265]/10 text-[#C4A265] flex flex-col items-center justify-center flex-shrink-0">
                        <span class="text-[9px] font-bold uppercase">{{ visit.visit_date ? new Date(visit.visit_date).toLocaleDateString('en', { month: 'short' }) : '' }}</span>
                        <span class="text-xs font-black leading-none">{{ visit.visit_date ? new Date(visit.visit_date).getDate() : '-' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ isRtl ? (visit.service?.name_ar || visit.service?.name_en) : (visit.service?.name_en || visit.service?.name_ar) || visit.visit_type || '-' }}</p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ isRtl ? (visit.doctor?.name_ar || visit.doctor?.name_en) : (visit.doctor?.name_en || visit.doctor?.name_ar) || '-' }}
                            <span v-if="visit.diagnosis"> · {{ visit.diagnosis }}</span>
                        </p>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                        :class="visit.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : visit.status === 'cancelled' ? 'bg-gray-50 text-gray-500' : 'bg-amber-50 text-amber-600'">
                        {{ isRtl ? ({ completed: 'مكتمل', waiting: 'انتظار', in_progress: 'جاري', cancelled: 'ملغي' }[visit.status] || visit.status) : visit.status }}
                    </span>
                </div>
            </div>
            <p v-else class="text-sm text-gray-400 text-center py-6">{{ isRtl ? 'لا توجد زيارات جلدية' : 'No dermatology visits' }}</p>
        </div>

        <!-- Recent Derma Prescriptions -->
        <div v-if="prescriptions.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    {{ isRtl ? 'وصفات الجلدية' : 'Dermatology Prescriptions' }}
                </h3>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="rx in prescriptions" :key="rx.id" class="px-5 py-3">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-gray-700">{{ isRtl ? (rx.doctor?.name_ar || rx.doctor?.name_en) : (rx.doctor?.name_en || rx.doctor?.name_ar) || '-' }}</p>
                        <p class="text-[10px] text-gray-400">{{ formatDate(rx.created_at) }}</p>
                    </div>
                    <p v-if="rx.diagnosis" class="text-xs text-gray-600 mb-1">{{ rx.diagnosis }}</p>
                    <div v-if="rx.items?.length" class="flex flex-wrap gap-1.5 mt-1.5">
                        <span v-for="item in rx.items" :key="item.id"
                            class="inline-flex items-center px-2 py-0.5 rounded bg-slate-50 text-[#1B365D] text-[10px] font-medium">
                            {{ item.medication_name || item.name || item.drug_name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <div v-if="photoPreview" @click.self="closePhoto" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
            <div class="relative max-w-4xl max-h-[90vh]">
                <button @click="closePhoto" class="absolute -top-10 ltr:right-0 rtl:left-0 text-white/80 hover:text-white text-xl">
                    &times; {{ isRtl ? 'إغلاق' : 'Close' }}
                </button>
                <img :src="photoPreview.url || ('/storage/' + photoPreview.photo_path)" class="max-w-full max-h-[90vh] rounded-xl" alt="" />
            </div>
        </div>
    </div>

    <div v-else class="bg-gray-50 rounded-2xl p-8 text-center">
        <p class="text-sm text-gray-500">{{ isRtl ? 'لا توجد بيانات جلدية' : 'No dermatology data' }}</p>
    </div>
</template>
