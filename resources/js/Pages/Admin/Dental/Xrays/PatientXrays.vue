<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';
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

// ─── Confirm Modal + Toast ──────────────────────────────
const showDeleteModal = ref(false);
const pendingDeleteId = ref(null);
const showSuccess = ref(false);
const successMessage = ref('');
const locale_val = computed(() => page.props.locale || 'ar');

watch(() => page.props.flash?.success, (msg) => {
    if (msg) { successMessage.value = msg; showSuccess.value = true; setTimeout(() => { showSuccess.value = false; }, 4000); }
});

function confirmDeleteXray(id) {
    pendingDeleteId.value = id;
    showDeleteModal.value = true;
}

function executeDeleteXray() {
    if (!pendingDeleteId.value) return;
    router.post(`/admin/dental/xrays/${pendingDeleteId.value}/delete`, {}, {
        preserveScroll: true,
    });
    showDeleteModal.value = false;
    pendingDeleteId.value = null;
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
            <!-- Hero Header -->
            <div class="dental-hero-enter relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] p-6 sm:p-7">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-8 ltr:left-20 rtl:right-20 w-32 h-32 bg-emerald-400/10 rounded-full blur-2xl"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link href="/admin/dental/xrays" class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center hover:bg-white/20 transition ring-1 ring-white/15">
                            <svg class="w-5 h-5 text-white rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <div>
                            <p class="text-slate-200/80 text-xs font-semibold tracking-wider uppercase">{{ $t('a_patient_xrays') }}</p>
                            <h1 class="text-xl sm:text-2xl font-bold text-white mt-0.5">{{ patient.full_name }}</h1>
                            <p class="text-slate-100/60 text-xs mt-0.5">{{ patient.file_number }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="showUploadForm = !showUploadForm"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 bg-white text-[#1B365D] hover:bg-slate-50 shadow-lg shadow-black/10"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            {{ $t('a_upload_xray') }}
                        </button>
                        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-2.5 border border-white/10 text-center">
                            <p class="text-xl font-bold text-white">{{ xrays?.length || 0 }}</p>
                            <p class="text-[10px] text-slate-200/60">{{ locale === 'ar' ? 'صور' : 'Images' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div v-if="showUploadForm" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 p-6 space-y-5" style="animation-delay:0.1s">
                <h2 class="text-lg font-semibold text-gray-800 border-b pb-3">{{ $t('a_upload_new_xray') }}</h2>

                <form @submit.prevent="submitUpload" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_xray_type') }} *</label>
                            <select
                                v-model="form.type"
                                class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent"
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
                            <input v-model="form.tooth_number" type="text" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent" />
                            <p v-if="errors.tooth_number" class="mt-1 text-sm text-red-600">{{ errors.tooth_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_doctor') }}</label>
                            <select
                                v-model="form.doctor_id"
                                class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent"
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
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-50 file:text-[#1B365D] hover:file:bg-slate-100"
                            />
                            <p v-if="errors.image" class="mt-1 text-sm text-red-600">{{ errors.image }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_taken_date') }}</label>
                            <input v-model="form.taken_date" type="date" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent" />
                            <p v-if="errors.taken_date" class="mt-1 text-sm text-red-600">{{ errors.taken_date }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_findings') }}</label>
                        <textarea v-model="form.findings" rows="2" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent"></textarea>
                        <p v-if="errors.findings" class="mt-1 text-sm text-red-600">{{ errors.findings }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_notes') }}</label>
                        <textarea v-model="form.notes" rows="2" class="doctorato-input w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-slate-200 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition bg-[#1B365D] hover:bg-[#1B365D] disabled:opacity-50"
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
            <div v-if="!xrays || xrays.length === 0" class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 p-12 text-center" style="animation-delay:0.15s">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-400">{{ $t('a_no_xrays_found') }}</p>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="(xray, i) in xrays" :key="xray.id"
                    class="dental-card-enter bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden hover:shadow-lg hover:border-gray-200 transition-all duration-300 group"
                    :style="{ animationDelay: (0.15 + i * 0.05) + 's' }">
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
                            <span class="px-2 py-1 bg-[#1B365D] text-white rounded text-xs font-medium">
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
                            <button @click="confirmDeleteXray(xray.id)" class="text-xs text-red-500 hover:text-red-700 transition">
                                {{ $t('a_delete') }}
                            </button>
                        </div>
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
                                :alt="$t('a_xray')"
                                class="w-full rounded-xl shadow-2xl"
                            />
                            <div class="mt-4 bg-white rounded-xl p-4 shadow-lg">
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
                </Transition>
            </Teleport>
        </div>

        <!-- Confirm Delete Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="locale === 'ar' ? 'حذف الأشعة' : 'Delete X-ray'"
            :message="locale === 'ar' ? 'هل أنت متأكد من حذف هذه الأشعة؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this X-ray? This action cannot be undone.'"
            :confirmText="locale === 'ar' ? 'حذف' : 'Delete'"
            :cancelText="locale === 'ar' ? 'إلغاء' : 'Cancel'"
            confirmColor="red"
            @confirm="executeDeleteXray"
            @cancel="showDeleteModal = false"
        />

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
    </AdminLayout>
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
