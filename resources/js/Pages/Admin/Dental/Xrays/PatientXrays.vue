<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useLocale } from '@/Composables/useLocale.js';

const { t } = useLocale();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');

const props = defineProps({
    patient: Object,
    xrays: Array,
    xrayTypes: Array,
    doctors: Array,
});

const showUploadForm = ref(false);
const processing = ref(false);
const errors = ref({});

const form = ref({
    type: '',
    image: null,
    tooth_number: '',
    findings: '',
    notes: '',
    taken_date: new Date().toISOString().slice(0, 10),
    doctor_id: '',
});

function handleFileChange(event) {
    form.value.image = event.target.files[0];
}

function submitUpload() {
    processing.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('type', form.value.type);
    if (form.value.image) formData.append('image', form.value.image);
    formData.append('tooth_number', form.value.tooth_number);
    formData.append('findings', form.value.findings);
    formData.append('notes', form.value.notes);
    formData.append('taken_date', form.value.taken_date);
    formData.append('doctor_id', form.value.doctor_id);

    router.post(`/admin/dental/xrays/patient/${props.patient.id}`, formData, {
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
                doctor_id: '',
            };
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

function deleteXray(id) {
    if (window.confirm(t('a_confirm_delete'))) {
        router.post(`/admin/dental/xrays/${id}/delete`, {
            preserveScroll: true,
        });
    }
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const selectedXray = ref(null);
</script>

<template>
    <AdminLayout :title="$t('a_patient_xrays')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $t('a_patient_xrays') }}</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ patient.full_name }} ({{ patient.file_number }})
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="showUploadForm = !showUploadForm"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition bg-cyan-600 hover:bg-cyan-700"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        {{ $t('a_upload_xray') }}
                    </button>
                    <Link href="/admin/dental/xrays" class="text-sm text-gray-500 hover:text-gray-700">
                        {{ $t('a_back') }}
                    </Link>
                </div>
            </div>

            <!-- Upload Form -->
            <div v-if="showUploadForm" class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
                <h2 class="text-lg font-semibold text-gray-800 border-b pb-3">{{ $t('a_upload_new_xray') }}</h2>

                <form @submit.prevent="submitUpload" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_xray_type') }} *</label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent"
                            >
                                <option value="">{{ $t('a_select_type') }}</option>
                                <option v-for="xType in xrayTypes" :key="xType.value || xType" :value="xType.value || xType">
                                    {{ $t('a_xray_type_' + (xType.value || xType)) }}
                                </option>
                            </select>
                            <p v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_tooth_number') }}</label>
                            <input v-model="form.tooth_number" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            <p v-if="errors.tooth_number" class="mt-1 text-sm text-red-600">{{ errors.tooth_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_doctor') }}</label>
                            <select
                                v-model="form.doctor_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent"
                            >
                                <option value="">{{ $t('a_select_doctor') }}</option>
                                <option v-for="doc in doctors" :key="doc.id" :value="doc.id">
                                    {{ locale === 'ar' ? doc.name_ar : doc.name_en }}
                                </option>
                            </select>
                            <p v-if="errors.doctor_id" class="mt-1 text-sm text-red-600">{{ errors.doctor_id }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_xray_image') }} *</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleFileChange"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                            />
                            <p v-if="errors.image" class="mt-1 text-sm text-red-600">{{ errors.image }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_taken_date') }}</label>
                            <input v-model="form.taken_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent" />
                            <p v-if="errors.taken_date" class="mt-1 text-sm text-red-600">{{ errors.taken_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_findings') }}</label>
                        <textarea v-model="form.findings" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent"></textarea>
                        <p v-if="errors.findings" class="mt-1 text-sm text-red-600">{{ errors.findings }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-cyan-200 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50"
                        >
                            {{ processing ? $t('a_uploading') : $t('a_upload_xray') }}
                        </button>
                        <button type="button" @click="showUploadForm = false" class="px-4 py-2.5 rounded-lg bg-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-300 transition">
                            {{ $t('a_cancel') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Gallery Grid -->
            <div v-if="!xrays || xrays.length === 0" class="bg-white rounded-xl shadow-sm border p-8 text-center text-gray-400 text-sm">
                {{ $t('a_no_xrays_found') }}
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="xray in xrays" :key="xray.id" class="bg-white rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition group">
                    <div class="aspect-video bg-gray-100 relative cursor-pointer" @click="selectedXray = xray">
                        <img
                            v-if="xray.image_url"
                            :src="xray.image_url"
                            :alt="$t('a_xray')"
                            class="w-full h-full object-cover group-hover:opacity-90 transition"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="absolute top-2 start-2">
                            <span class="px-2 py-1 bg-cyan-600 text-white rounded text-xs font-medium">
                                {{ $t('a_xray_type_' + xray.type) }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">{{ $t('a_tooth') }} {{ xray.tooth_number || '-' }}</span>
                            <span class="text-xs text-gray-400">{{ formatDate(xray.taken_date || xray.created_at) }}</span>
                        </div>
                        <div v-if="xray.doctor" class="text-xs text-gray-500">
                            {{ locale === 'ar' ? xray.doctor.name_ar : xray.doctor.name_en }}
                        </div>
                        <div v-if="xray.findings" class="text-xs text-gray-600 line-clamp-2">
                            {{ xray.findings }}
                        </div>
                        <div v-if="xray.notes" class="text-xs text-gray-400 line-clamp-1">
                            {{ xray.notes }}
                        </div>
                        <div class="pt-2 border-t flex items-center justify-end">
                            <button @click="deleteXray(xray.id)" class="text-xs text-red-500 hover:text-red-700 transition">
                                {{ $t('a_delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox Modal -->
            <div v-if="selectedXray" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80" @click.self="selectedXray = null">
                <div class="relative max-w-4xl w-full mx-4">
                    <button @click="selectedXray = null" class="absolute -top-10 end-0 text-white hover:text-gray-300 transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img
                        v-if="selectedXray.image_url"
                        :src="selectedXray.image_url"
                        :alt="$t('a_xray')"
                        class="w-full rounded-lg"
                    />
                    <div class="mt-4 bg-white rounded-lg p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">{{ $t('a_type') }}:</span>
                                <span class="ms-1 font-medium">{{ $t('a_xray_type_' + selectedXray.type) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ $t('a_tooth') }}:</span>
                                <span class="ms-1 font-medium">{{ selectedXray.tooth_number || '-' }}</span>
                            </div>
                            <div v-if="selectedXray.findings" class="col-span-2">
                                <span class="text-gray-500">{{ $t('a_findings') }}:</span>
                                <span class="ms-1">{{ selectedXray.findings }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
