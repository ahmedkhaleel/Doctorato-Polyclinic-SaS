<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

// ─── Toast Notification ─────────────────────────────────
const showSuccess = ref(false);
const successMessage = ref('');
watch(() => page.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});

const props = defineProps({
    patient: Object,
    xrays: Array,
    xrayTypes: Array,
});

const showUploadForm = ref(false);
const processing = ref(false);
const errors = ref({});
const selectedXray = ref(null);

const form = ref({
    type: '',
    image: null,
    tooth_number: '',
    findings: '',
    notes: '',
    taken_date: new Date().toISOString().slice(0, 10),
});

function handleFileChange(event) {
    form.value.image = event.target.files[0];
}

function submitUpload() {
    processing.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('patient_id', props.patient.id);
    formData.append('type', form.value.type);
    if (form.value.image) formData.append('image', form.value.image);
    formData.append('tooth_number', form.value.tooth_number);
    formData.append('findings', form.value.findings);
    formData.append('notes', form.value.notes);
    formData.append('taken_date', form.value.taken_date);

    router.post('/doctor/dental/xrays', formData, {
        forceFormData: true,
        onError: (errs) => {
            errors.value = errs;
        },
        onSuccess: () => {
            showUploadForm.value = false;
            form.value = {
                type: '',
                image: null,
                tooth_number: '',
                findings: '',
                notes: '',
                taken_date: new Date().toISOString().slice(0, 10),
            };
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-6">
        <!-- Hero Header -->
        <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6 sm:p-7">
            <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-[#C4A265]/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-purple-500/10 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link href="/doctor/dental/xrays" class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center hover:bg-white/20 transition ring-1 ring-white/15">
                        <svg class="w-5 h-5 text-white rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <p class="text-[#C4A265]/80 text-xs font-semibold tracking-wider uppercase">{{ isRtl ? 'صور الأشعة' : 'X-Rays' }}</p>
                        <h1 class="text-xl sm:text-2xl font-bold text-white mt-0.5">{{ patient.full_name }}</h1>
                        <p class="text-gray-400 text-xs font-mono mt-0.5">{{ patient.file_number }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        @click="showUploadForm = !showUploadForm"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-900 bg-[#C4A265] rounded-xl hover:bg-[#B39255] transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        {{ isRtl ? 'رفع أشعة' : 'Upload X-Ray' }}
                    </button>
                    <Link :href="`/doctor/dental/chart/${patient.id}`"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white/90 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 border border-white/15 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ isRtl ? 'المخطط' : 'Chart' }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div v-if="showUploadForm" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4 sm:p-6 space-y-5" style="animation-delay:0.1s">
            <h2 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-3">{{ isRtl ? 'رفع أشعة جديدة' : 'Upload New X-Ray' }}</h2>

            <form @submit.prevent="submitUpload" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'نوع الأشعة' : 'X-Ray Type' }} *</label>
                        <select
                            v-model="form.type"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"
                        >
                            <option value="">{{ isRtl ? 'اختر النوع' : 'Select Type' }}</option>
                            <option v-for="xType in xrayTypes" :key="xType.value || xType" :value="xType.value || xType">
                                {{ isRtl ? {
                                    periapical: 'حول الذروة', panoramic: 'بانوراما', bitewing: 'جناح العضة',
                                    cephalometric: 'قياس الرأس', cbct: 'أشعة مقطعية مخروطية', occlusal: 'إطباقية'
                                }[xType.value || xType] || (xType.value || xType) : (xType.label || xType.value || xType) }}
                            </option>
                        </select>
                        <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'رقم السن' : 'Tooth Number' }}</label>
                        <input v-model="form.tooth_number" type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                        <p v-if="errors.tooth_number" class="mt-1 text-sm text-red-600">{{ errors.tooth_number }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'تاريخ التصوير' : 'Taken Date' }}</label>
                        <input v-model="form.taken_date" type="date"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]" />
                        <p v-if="errors.taken_date" class="mt-1 text-sm text-red-600">{{ errors.taken_date }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'صورة الأشعة' : 'X-Ray Image' }} *</label>
                    <input
                        type="file"
                        accept="image/*"
                        @change="handleFileChange"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-[#C4A265]/10 file:text-[#C4A265] hover:file:bg-[#C4A265]/20"
                    />
                    <p v-if="errors.image" class="mt-1 text-sm text-red-600">{{ errors.image }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'النتائج' : 'Findings' }}</label>
                    <textarea v-model="form.findings" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                    <p v-if="errors.findings" class="mt-1 text-sm text-red-600">{{ errors.findings }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ isRtl ? 'ملاحظات' : 'Notes' }}</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265]"></textarea>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-6 py-2.5 rounded-xl text-white font-medium text-sm transition bg-[#C4A265] hover:bg-[#B39255] disabled:opacity-50"
                    >
                        {{ processing ? (isRtl ? 'جاري الرفع...' : 'Uploading...') : (isRtl ? 'رفع الأشعة' : 'Upload X-Ray') }}
                    </button>
                    <button type="button" @click="showUploadForm = false"
                        class="px-4 py-2.5 rounded-xl bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Gallery Grid -->
        <div v-if="!xrays || xrays.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-12 text-center">
            <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100">
                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <p class="text-sm font-medium text-gray-500">{{ isRtl ? 'لا توجد صور أشعة لهذا المريض' : 'No x-rays found for this patient' }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ isRtl ? 'اضغط على زر "رفع أشعة" لإضافة صورة جديدة' : 'Click "Upload X-Ray" to add a new image' }}</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="xray in xrays" :key="xray.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-md transition group cursor-pointer"
                @click="selectedXray = xray"
            >
                <div class="aspect-video bg-gray-100 relative">
                    <img
                        v-if="xray.image_url"
                        :src="xray.image_url"
                        alt=""
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="absolute top-2 start-2">
                        <span class="px-2 py-1 bg-[#C4A265] text-white rounded-lg text-xs font-medium shadow-sm">
                            {{ isRtl ? {
                                periapical: 'حول الذروة', panoramic: 'بانوراما', bitewing: 'جناح العضة',
                                cephalometric: 'قياس الرأس', cbct: 'مقطعية', occlusal: 'إطباقية'
                            }[xray.type] || xray.type : xray.type }}
                        </span>
                    </div>
                </div>
                <div class="p-3 sm:p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">{{ isRtl ? 'سن' : 'Tooth' }} {{ xray.tooth_number || '-' }}</span>
                        <span class="text-xs text-gray-400">{{ formatDate(xray.taken_date || xray.created_at) }}</span>
                    </div>
                    <div v-if="xray.findings" class="text-xs text-gray-600 line-clamp-2">{{ xray.findings }}</div>
                    <div v-if="xray.notes" class="text-xs text-gray-400 line-clamp-1">{{ xray.notes }}</div>
                </div>
            </div>
        </div>

        <!-- Lightbox Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="selectedXray" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="selectedXray = null">
                    <div class="relative max-w-4xl w-full animate-[lightboxScale_0.3s_ease-out]">
                        <button @click="selectedXray = null" class="absolute -top-10 end-0 text-white hover:text-gray-300 transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <img
                            v-if="selectedXray.image_url"
                            :src="selectedXray.image_url"
                            alt=""
                            class="w-full rounded-xl shadow-2xl"
                        />
                        <div class="mt-4 bg-white rounded-xl p-3 sm:p-4 shadow-lg">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">{{ isRtl ? 'النوع' : 'Type' }}:</span>
                                    <span class="ms-1 font-medium">{{ selectedXray.type }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">{{ isRtl ? 'السن' : 'Tooth' }}:</span>
                                    <span class="ms-1 font-medium">{{ selectedXray.tooth_number || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">{{ isRtl ? 'التاريخ' : 'Date' }}:</span>
                                    <span class="ms-1 font-medium">{{ formatDate(selectedXray.taken_date || selectedXray.created_at) }}</span>
                                </div>
                                <div v-if="selectedXray.findings" class="col-span-2">
                                    <span class="text-gray-500">{{ isRtl ? 'النتائج' : 'Findings' }}:</span>
                                    <span class="ms-1">{{ selectedXray.findings }}</span>
                                </div>
                                <div v-if="selectedXray.notes" class="col-span-2">
                                    <span class="text-gray-500">{{ isRtl ? 'ملاحظات' : 'Notes' }}:</span>
                                    <span class="ms-1">{{ selectedXray.notes }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Success Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-4 opacity-0"
        >
            <div v-if="showSuccess" class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50 flex items-center gap-3 px-5 py-3 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-200/50">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium">{{ successMessage }}</span>
            </div>
        </Transition>
    </div>
</template>

<style>
@keyframes dentalHeroEnter {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes dentalCardEnter {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dental-hero-enter { animation: dentalHeroEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
.dental-card-enter { animation: dentalCardEnter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
@keyframes lightboxScale {
    from { opacity: 0; transform: scale(0.92); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
