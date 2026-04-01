<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

defineOptions({ layout: DoctorLayout });

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

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
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <p class="text-[#C4A265] text-xs font-semibold tracking-wider uppercase mb-1">{{ isRtl ? 'صور الأشعة' : 'X-Rays' }}</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ isRtl ? 'صور أشعة المريض' : 'Patient X-Rays' }}</h1>
                <p class="text-gray-500 text-sm mt-1">
                    {{ patient.full_name }} <span class="font-mono text-xs text-gray-400">({{ patient.file_number }})</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="showUploadForm = !showUploadForm"
                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-white text-sm font-medium transition bg-[#C4A265] hover:bg-[#B39255]"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ isRtl ? 'رفع أشعة' : 'Upload X-Ray' }}
                </button>
                <Link :href="`/doctor/dental/chart/${patient.id}`"
                    class="inline-flex items-center px-3 py-2.5 text-sm font-medium text-[#C4A265] bg-[#C4A265]/5 rounded-xl hover:bg-[#C4A265]/10 border border-[#C4A265]/10 transition-colors">
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    {{ isRtl ? 'المخطط' : 'Chart' }}
                </Link>
                <Link href="/doctor/dental/xrays"
                    class="inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-500 hover:text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                    <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    {{ isRtl ? 'رجوع' : 'Back' }}
                </Link>
            </div>
        </div>

        <!-- Upload Form -->
        <div v-if="showUploadForm" class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 space-y-5">
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

                <div class="flex items-center gap-3">
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

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                <div class="p-4 space-y-2">
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
            <div v-if="selectedXray" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" @click.self="selectedXray = null">
                <div class="relative max-w-4xl w-full">
                    <button @click="selectedXray = null" class="absolute -top-10 end-0 text-white hover:text-gray-300 transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img
                        v-if="selectedXray.image_url"
                        :src="selectedXray.image_url"
                        alt=""
                        class="w-full rounded-xl"
                    />
                    <div class="mt-4 bg-white rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
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
        </Teleport>
    </div>
</template>
