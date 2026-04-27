<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import PatientLayout from '@/Layouts/PatientLayout.vue';
import { usePatientLocale } from '@/Composables/usePatientLocale';

const { lp } = usePatientLocale();

defineOptions({ layout: PatientLayout });

const props = defineProps({
    documents:    Object, // paginator
    allowedTypes: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl  = computed(() => (page.props.dir || 'rtl') === 'rtl');

const showUpload = ref(false);
const fileInput = ref(null);

const form = useForm({
    document_type: 'medical_report',
    title: '',
    file: null,
    document_date: '',
    notes: '',
});

const typeOptions = computed(() =>
    Object.entries(props.allowedTypes).map(([key, labels]) => ({
        value: key,
        label: locale.value === 'ar' ? labels.ar : labels.en,
    }))
);

function onFileSelect(e) {
    const f = e.target.files[0];
    if (f) {
        form.file = f;
        if (!form.title) {
            // Default title to the filename without extension.
            form.title = f.name.replace(/\.[^/.]+$/, '');
        }
    }
}

function submit() {
    form.post(lp('/documents'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showUpload.value = false;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function deleteDoc(id) {
    if (!confirm(isRtl.value ? 'هل تريد حذف هذا الملف؟' : 'Delete this document?')) return;
    router.delete(lp(`/documents/${id}`), { preserveScroll: true });
}

function fmtSize(bytes) {
    if (!bytes) return '—';
    const kb = bytes / 1024;
    if (kb < 1024) return kb.toFixed(0) + ' KB';
    return (kb / 1024).toFixed(1) + ' MB';
}

function fileIcon(mime) {
    if (!mime) return '📄';
    if (mime.includes('pdf')) return '📕';
    if (mime.startsWith('image/')) return '🖼';
    if (mime.includes('word')) return '📝';
    return '📄';
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ isRtl ? 'مستنداتي الطبية' : 'My medical documents' }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ isRtl
                        ? 'ارفع تقاريرك الطبية ونتائج المختبرات السابقة ليتمكن طبيبك من الاطلاع عليها'
                        : 'Upload your previous lab reports and medical records so your doctor can review them' }}
                </p>
            </div>
            <button @click="showUpload = !showUpload"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[var(--brand-primary)] to-[var(--brand-secondary)] hover:opacity-90 text-white text-sm font-semibold shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ showUpload ? (isRtl ? 'إلغاء' : 'Cancel') : (isRtl ? 'رفع ملف جديد' : 'Upload new file') }}
            </button>
        </div>

        <!-- Upload form -->
        <div v-if="showUpload" class="bg-white rounded-2xl shadow-sm border-2 border-[var(--brand-primary)]/30 p-5 mb-6">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'نوع الملف' : 'Document type' }} *
                        </label>
                        <select v-model="form.document_type" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-[var(--brand-primary)]/30">
                            <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'العنوان' : 'Title' }} *
                        </label>
                        <input v-model="form.title" type="text" required maxlength="255"
                               :placeholder="isRtl ? 'مثال: تحليل دم 2026' : 'e.g. Blood test 2026'"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-[var(--brand-primary)]/30" />
                        <p v-if="form.errors.title" class="text-xs text-red-600 mt-1">{{ form.errors.title }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        {{ isRtl ? 'الملف (PDF / صورة / Word — حتى 10MB)' : 'File (PDF / image / Word — up to 10MB)' }} *
                    </label>
                    <input ref="fileInput" type="file" required @change="onFileSelect"
                           accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[var(--brand-primary)]/10 file:text-[var(--brand-primary)] hover:file:bg-[var(--brand-primary)]/20" />
                    <p v-if="form.errors.file" class="text-xs text-red-600 mt-1">{{ form.errors.file }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'تاريخ الملف (اختياري)' : 'Document date (optional)' }}
                        </label>
                        <input v-model="form.document_date" type="date" :max="new Date().toISOString().split('T')[0]"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'ملاحظات (اختياري)' : 'Notes (optional)' }}
                        </label>
                        <input v-model="form.notes" type="text" maxlength="1000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="submit" :disabled="form.processing || !form.file"
                            class="px-5 py-2 rounded-lg bg-[var(--brand-primary)] text-white text-sm font-semibold disabled:opacity-50">
                        {{ form.processing ? (isRtl ? 'جارٍ الرفع...' : 'Uploading...') : (isRtl ? 'رفع الملف' : 'Upload') }}
                    </button>
                    <p v-if="form.progress" class="text-xs text-gray-500">{{ form.progress.percentage }}%</p>
                </div>
            </form>
        </div>

        <!-- Document list -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div v-if="documents.data.length" class="divide-y divide-gray-50">
                <div v-for="doc in documents.data" :key="doc.id"
                     class="flex items-center gap-4 p-4 hover:bg-gray-50/50 transition">
                    <div class="text-3xl flex-shrink-0">{{ fileIcon(doc.mime_type) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ doc.title }}</p>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 text-[#1B365D]">
                                {{ locale === 'ar' ? doc.type_label?.ar : doc.type_label?.en }}
                            </span>
                            <span v-if="doc.uploaded_by_me" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                {{ isRtl ? 'رفعتُه' : 'My upload' }}
                            </span>
                            <span v-else class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                {{ isRtl ? 'العيادة' : 'Clinic' }}
                            </span>
                            <span v-if="doc.is_expired" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                {{ isRtl ? 'منتهي' : 'Expired' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ doc.original_name }} · {{ fmtSize(doc.file_size) }}
                            <span v-if="doc.document_date" class="ms-2">
                                · {{ isRtl ? 'تاريخ:' : 'Date:' }} {{ doc.document_date }}
                            </span>
                        </p>
                        <p v-if="doc.notes" class="text-[11px] text-gray-400 mt-0.5 truncate">{{ doc.notes }}</p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <a :href="lp(`/documents/${doc.id}/download`)"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-[#1B365D] text-white text-xs font-semibold hover:bg-[#22406F]">
                            <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ isRtl ? 'تنزيل' : 'Download' }}
                        </a>
                        <button v-if="doc.uploaded_by_me" @click="deleteDoc(doc.id)" type="button"
                                :title="isRtl ? 'حذف' : 'Delete'"
                                class="inline-flex items-center px-2 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="p-12 text-center">
                <div class="text-5xl mb-3">📁</div>
                <p class="text-sm text-gray-500">
                    {{ isRtl ? 'لا توجد مستندات بعد' : 'No documents yet' }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ isRtl ? 'ارفع أول ملف بالضغط على الزر أعلاه' : 'Upload your first file using the button above' }}
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="documents.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in documents.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border',
                        link.active ? 'bg-[var(--brand-primary)] text-white border-[var(--brand-primary)]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>
