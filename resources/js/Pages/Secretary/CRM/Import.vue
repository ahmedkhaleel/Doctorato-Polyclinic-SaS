<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

/* ── Steps ──────────────────────────────────────────────── */
const step = ref(1); // 1: Upload, 2: Map columns, 3: Confirm & import

/* ── Upload state ───────────────────────────────────────── */
const file = ref(null);
const fileName = ref('');
const fileSize = ref('');
const isDragging = ref(false);
const uploadError = ref('');
const isUploading = ref(false);

/* ── Preview state ──────────────────────────────────────── */
const headers = ref([]);
const preview = ref([]);
const totalRows = ref(0);

/* ── Column mapping ─────────────────────────────────────── */
const fields = [
    { key: 'full_name', labelEn: 'Full Name', labelAr: 'الاسم الكامل', required: true },
    { key: 'phone',     labelEn: 'Phone',     labelAr: 'الهاتف',       required: true },
    { key: 'phone2',    labelEn: 'Phone 2',   labelAr: 'هاتف 2',       required: false },
    { key: 'email',     labelEn: 'Email',     labelAr: 'البريد',       required: false },
    { key: 'gender',    labelEn: 'Gender',    labelAr: 'الجنس',        required: false },
    { key: 'date_of_birth', labelEn: 'Date of Birth', labelAr: 'تاريخ الميلاد', required: false },
    { key: 'city',      labelEn: 'City',      labelAr: 'المدينة',      required: false },
    { key: 'nationality', labelEn: 'Nationality', labelAr: 'الجنسية',  required: false },
    { key: 'priority',  labelEn: 'Priority',  labelAr: 'الأولوية',     required: false },
    { key: 'notes',     labelEn: 'Notes',     labelAr: 'ملاحظات',      required: false },
];

const columnMap = ref({});

// Auto-map columns based on similarity
function autoMap() {
    const map = {};
    const aliases = {
        full_name: ['full_name', 'fullname', 'name', 'الاسم', 'الاسمالكامل', 'اسم'],
        phone: ['phone', 'mobile', 'الهاتف', 'الجوال', 'رقمالهاتف', 'رقمالجوال', 'phonenumber'],
        phone2: ['phone2', 'mobile2', 'هاتف2', 'جوال2'],
        email: ['email', 'البريد', 'الايميل', 'emailaddress', 'البريدالالكتروني'],
        gender: ['gender', 'sex', 'الجنس'],
        date_of_birth: ['date_of_birth', 'dateofbirth', 'dob', 'birthday', 'تاريخالميلاد', 'birthdate'],
        city: ['city', 'المدينة'],
        nationality: ['nationality', 'الجنسية'],
        priority: ['priority', 'الأولوية', 'الاولوية'],
        notes: ['notes', 'note', 'ملاحظات', 'comment', 'comments'],
    };

    fields.forEach(field => {
        const fieldAliases = aliases[field.key] || [field.key];
        for (const header of headers.value) {
            const normalized = String(header).toLowerCase().replace(/[\s_\-]/g, '');
            if (fieldAliases.some(a => normalized.includes(a.toLowerCase().replace(/[\s_\-]/g, '')))) {
                map[field.key] = header;
                break;
            }
        }
    });

    columnMap.value = map;
}

/* ── File handling ──────────────────────────────────────── */
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function handleFileSelect(e) {
    const f = e.target?.files?.[0] || e.dataTransfer?.files?.[0];
    if (!f) return;

    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
    ];
    const allowedExtensions = ['xlsx', 'xls', 'csv'];
    const ext = f.name.split('.').pop().toLowerCase();

    if (!allowedTypes.includes(f.type) && !allowedExtensions.includes(ext)) {
        uploadError.value = isRtl.value ? 'نوع الملف غير مدعوم. يرجى استخدام xlsx أو csv' : 'Unsupported file type. Please use .xlsx or .csv';
        return;
    }

    if (f.size > 5 * 1024 * 1024) {
        uploadError.value = isRtl.value ? 'حجم الملف يتجاوز 5 ميغابايت' : 'File size exceeds 5MB limit';
        return;
    }

    file.value = f;
    fileName.value = f.name;
    fileSize.value = formatSize(f.size);
    uploadError.value = '';
}

function handleDragOver(e) {
    e.preventDefault();
    isDragging.value = true;
}

function handleDragLeave() {
    isDragging.value = false;
}

function handleDrop(e) {
    e.preventDefault();
    isDragging.value = false;
    handleFileSelect(e);
}

function clearFile() {
    file.value = null;
    fileName.value = '';
    fileSize.value = '';
    headers.value = [];
    preview.value = [];
    totalRows.value = 0;
    step.value = 1;
    uploadError.value = '';
    columnMap.value = {};
}

/* ── Upload & Preview ───────────────────────────────────── */
async function uploadAndPreview() {
    if (!file.value) return;
    isUploading.value = true;
    uploadError.value = '';

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await fetch('/secretary/crm/import/preview', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            uploadError.value = data.error || (isRtl.value ? 'خطأ في معالجة الملف' : 'Error processing file');
            return;
        }

        headers.value = data.headers || [];
        preview.value = data.preview || [];
        totalRows.value = data.total_rows || 0;

        autoMap();
        step.value = 2;
    } catch (err) {
        uploadError.value = isRtl.value ? 'خطأ في الاتصال' : 'Connection error';
    } finally {
        isUploading.value = false;
    }
}

/* ── Import ─────────────────────────────────────────────── */
const isImporting = ref(false);

const canImport = computed(() => {
    return columnMap.value.full_name && columnMap.value.phone;
});

/* ── Mapping quality stats ─────────────────────────────── */
const mappingStats = computed(() => {
    const required = fields.filter(f => f.required);
    const optional = fields.filter(f => !f.required);
    const mappedRequired = required.filter(f => !!columnMap.value[f.key]).length;
    const mappedOptional = optional.filter(f => !!columnMap.value[f.key]).length;
    const totalMapped = mappedRequired + mappedOptional;
    const quality = totalMapped === 0 ? 0 : Math.round((totalMapped / fields.length) * 100);
    return { mappedRequired, totalRequired: required.length, mappedOptional, totalOptional: optional.length, totalMapped, quality };
});

/* ── Data quality preview ──────────────────────────────── */
const dataQualityIssues = computed(() => {
    if (!preview.value.length || !columnMap.value.phone) return [];
    const issues = [];
    const phoneCol = columnMap.value.phone;
    const nameCol = columnMap.value.full_name;
    const emailCol = columnMap.value.email;
    const phones = new Set();

    preview.value.forEach((row, idx) => {
        const phone = row[phoneCol];
        const name = nameCol ? row[nameCol] : '';
        const email = emailCol ? row[emailCol] : '';

        if (!phone || String(phone).trim().length < 5) {
            issues.push({ row: idx + 1, type: 'error', msg: isRtl.value ? `صف ${idx + 1}: رقم هاتف ناقص` : `Row ${idx + 1}: Missing/short phone` });
        }
        if (phone && phones.has(String(phone).trim())) {
            issues.push({ row: idx + 1, type: 'warning', msg: isRtl.value ? `صف ${idx + 1}: رقم مكرر "${phone}"` : `Row ${idx + 1}: Duplicate phone "${phone}"` });
        }
        if (phone) phones.add(String(phone).trim());
        if (!name || String(name).trim().length < 2) {
            issues.push({ row: idx + 1, type: 'error', msg: isRtl.value ? `صف ${idx + 1}: اسم ناقص` : `Row ${idx + 1}: Missing name` });
        }
        if (email && !String(email).includes('@')) {
            issues.push({ row: idx + 1, type: 'warning', msg: isRtl.value ? `صف ${idx + 1}: بريد غير صالح` : `Row ${idx + 1}: Invalid email format` });
        }
    });
    return issues;
});

function startImport() {
    if (!canImport.value || !file.value) return;
    isImporting.value = true;

    const formData = new FormData();
    formData.append('file', file.value);

    // Append column_map as individual fields
    Object.keys(columnMap.value).forEach(key => {
        if (columnMap.value[key]) {
            formData.append(`column_map[${key}]`, columnMap.value[key]);
        }
    });

    router.post('/secretary/crm/import/process', formData, {
        forceFormData: true,
        onFinish: () => { isImporting.value = false; },
    });
}
</script>

<template>
<SecretaryLayout :title="isRtl ? 'استيراد العملاء' : 'Import Leads'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/20 to-slate-50 p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-6 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'استيراد العملاء المحتملين' : 'Import Leads' }}</h1>
                    <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'استيراد قائمة عملاء من ملف Excel أو CSV' : 'Import leads from an Excel or CSV file' }}</p>
                </div>
            </div>
            <!-- Download template -->
            <a href="/secretary/crm/import/template" class="flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur rounded-xl text-white text-sm font-medium transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ isRtl ? 'تحميل نموذج' : 'Download Template' }}
            </a>
        </div>
    </div>

    <!-- Progress Steps -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 transition-all duration-700 delay-100', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">
        <div class="flex items-center justify-center gap-0">
            <!-- Step 1 -->
            <div class="flex items-center gap-2">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                    step >= 1 ? 'bg-teal-500 text-white shadow-md shadow-teal-200' : 'bg-gray-100 text-gray-400']">1</div>
                <span :class="['text-sm font-medium transition-colors', step >= 1 ? 'text-teal-700' : 'text-gray-400']">
                    {{ isRtl ? 'رفع الملف' : 'Upload' }}
                </span>
            </div>
            <div :class="['w-16 h-0.5 mx-2 transition-colors duration-300', step >= 2 ? 'bg-teal-400' : 'bg-gray-200']"></div>
            <!-- Step 2 -->
            <div class="flex items-center gap-2">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                    step >= 2 ? 'bg-teal-500 text-white shadow-md shadow-teal-200' : 'bg-gray-100 text-gray-400']">2</div>
                <span :class="['text-sm font-medium transition-colors', step >= 2 ? 'text-teal-700' : 'text-gray-400']">
                    {{ isRtl ? 'ربط الأعمدة' : 'Map Columns' }}
                </span>
            </div>
            <div :class="['w-16 h-0.5 mx-2 transition-colors duration-300', step >= 3 ? 'bg-teal-400' : 'bg-gray-200']"></div>
            <!-- Step 3 -->
            <div class="flex items-center gap-2">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300',
                    step >= 3 ? 'bg-teal-500 text-white shadow-md shadow-teal-200' : 'bg-gray-100 text-gray-400']">3</div>
                <span :class="['text-sm font-medium transition-colors', step >= 3 ? 'text-teal-700' : 'text-gray-400']">
                    {{ isRtl ? 'استيراد' : 'Import' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Step 1: Upload -->
    <div v-if="step === 1"
         :class="['bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 transition-all duration-700 delay-200', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }">

        <!-- Drop zone -->
        <div @dragover="handleDragOver" @dragleave="handleDragLeave" @drop="handleDrop"
             @click="$refs.fileInput.click()"
             :class="['relative border-2 border-dashed rounded-2xl p-12 text-center cursor-pointer transition-all duration-300',
                isDragging ? 'border-teal-400 bg-teal-50 scale-[1.01]' :
                file ? 'border-teal-300 bg-teal-50/50' :
                'border-gray-200 hover:border-teal-300 hover:bg-teal-50/30']">

            <input ref="fileInput" type="file" class="hidden" accept=".xlsx,.xls,.csv" @change="handleFileSelect">

            <div v-if="!file">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-700 mb-1">
                    {{ isRtl ? 'اسحب الملف هنا أو اضغط للاختيار' : 'Drag file here or click to browse' }}
                </h3>
                <p class="text-sm text-gray-400">
                    {{ isRtl ? 'يدعم ملفات Excel (.xlsx) و CSV بحد أقصى 5 ميغابايت' : 'Supports .xlsx and .csv files up to 5MB' }}
                </p>
            </div>

            <div v-else class="flex items-center justify-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="text-start">
                    <p class="text-sm font-semibold text-gray-800">{{ fileName }}</p>
                    <p class="text-xs text-gray-400">{{ fileSize }}</p>
                </div>
                <button @click.stop="clearFile" class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Error -->
        <div v-if="uploadError" class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-600 flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ uploadError }}
        </div>

        <!-- Upload button -->
        <div class="mt-6 flex justify-end">
            <button @click="uploadAndPreview" :disabled="!file || isUploading"
                :class="['px-6 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-2',
                    file && !isUploading
                        ? 'bg-teal-500 text-white hover:bg-teal-600 shadow-md shadow-teal-200'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed']">
                <svg v-if="isUploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                {{ isUploading ? (isRtl ? 'جاري التحليل...' : 'Analyzing...') : (isRtl ? 'التالي' : 'Next') }}
            </button>
        </div>

        <!-- Tips -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-700">{{ isRtl ? 'الحقول المطلوبة' : 'Required Fields' }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ isRtl ? 'الاسم الكامل والهاتف مطلوبان' : 'Full name and phone are required' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-700">{{ isRtl ? 'كشف التكرار' : 'Duplicate Detection' }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ isRtl ? 'يتم تخطي الأرقام المكررة تلقائيا' : 'Duplicate phones are auto-skipped' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-700">{{ isRtl ? 'تعيين تلقائي' : 'Auto-Assigned' }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ isRtl ? 'العملاء يتم تعيينهم لك تلقائيا' : 'Leads are auto-assigned to you' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Map Columns -->
    <div v-if="step === 2"
         class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 transition-all duration-500">

        <!-- Summary -->
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ fileName }}</p>
                    <p class="text-xs text-gray-400">{{ totalRows }} {{ isRtl ? 'صف' : 'rows' }} - {{ headers.length }} {{ isRtl ? 'عمود' : 'columns' }}</p>
                </div>
            </div>
            <button @click="clearFile" class="text-xs text-gray-400 hover:text-red-500 transition-colors">
                {{ isRtl ? 'تغيير الملف' : 'Change file' }}
            </button>
        </div>

        <!-- Column mapping -->
        <h3 class="text-sm font-semibold text-gray-700 mb-3">
            {{ isRtl ? 'ربط أعمدة الملف بحقول النظام' : 'Map file columns to system fields' }}
        </h3>

        <!-- Mapping quality bar -->
        <div class="mb-4 p-3 rounded-xl border transition-all duration-300"
             :class="mappingStats.quality >= 80 ? 'border-emerald-200 bg-emerald-50/40' :
                      mappingStats.quality >= 50 ? 'border-amber-200 bg-amber-50/40' :
                      'border-red-200 bg-red-50/40'">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <svg v-if="mappingStats.quality >= 80" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else-if="mappingStats.quality >= 50" class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <svg v-else class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <span class="text-xs font-semibold"
                          :class="mappingStats.quality >= 80 ? 'text-emerald-700' : mappingStats.quality >= 50 ? 'text-amber-700' : 'text-red-700'">
                        {{ isRtl ? 'جودة الربط' : 'Mapping Quality' }}: {{ mappingStats.quality }}%
                    </span>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-gray-500">
                    <span>{{ isRtl ? 'مطلوب' : 'Required' }}: {{ mappingStats.mappedRequired }}/{{ mappingStats.totalRequired }}</span>
                    <span>{{ isRtl ? 'اختياري' : 'Optional' }}: {{ mappingStats.mappedOptional }}/{{ mappingStats.totalOptional }}</span>
                </div>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     :class="mappingStats.quality >= 80 ? 'bg-emerald-500' : mappingStats.quality >= 50 ? 'bg-amber-500' : 'bg-red-500'"
                     :style="{ width: mappingStats.quality + '%' }"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div v-for="field in fields" :key="field.key"
                 :class="['rounded-xl border p-3 transition-all duration-200',
                    columnMap[field.key] ? 'border-teal-200 bg-teal-50/30' : 'border-gray-100 bg-gray-50/50']">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-gray-700 flex items-center gap-1">
                        {{ isRtl ? field.labelAr : field.labelEn }}
                        <span v-if="field.required" class="text-red-400">*</span>
                    </label>
                    <span v-if="columnMap[field.key]" class="text-[10px] text-teal-600 bg-teal-100 rounded-full px-2 py-0.5">
                        {{ isRtl ? 'مربوط' : 'Mapped' }}
                    </span>
                </div>
                <select v-model="columnMap[field.key]"
                    :class="['w-full text-sm rounded-lg border bg-white px-3 py-2 outline-none transition-all duration-200 focus:ring-2 focus:ring-teal-200',
                        columnMap[field.key] ? 'border-teal-300 text-gray-800' : 'border-gray-200 text-gray-400']">
                    <option value="">{{ isRtl ? '-- لا يوجد --' : '-- None --' }}</option>
                    <option v-for="h in headers" :key="h" :value="h">{{ h }}</option>
                </select>
            </div>
        </div>

        <!-- Preview table -->
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                {{ isRtl ? 'معاينة البيانات (أول 5 صفوف)' : 'Data Preview (first 5 rows)' }}
            </h3>
            <div class="overflow-x-auto rounded-xl border border-gray-100">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold text-gray-500">#</th>
                            <th v-for="h in headers" :key="h" class="px-3 py-2 text-start font-semibold text-gray-500 whitespace-nowrap">
                                {{ h }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, idx) in preview" :key="idx" class="border-t border-gray-50 hover:bg-gray-50/50">
                            <td class="px-3 py-2 text-gray-400">{{ idx + 1 }}</td>
                            <td v-for="h in headers" :key="h" class="px-3 py-2 text-gray-700 whitespace-nowrap max-w-[200px] truncate">
                                {{ row[h] || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Data quality issues -->
        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="dataQualityIssues.length > 0" class="mt-4 rounded-xl border border-amber-200 bg-amber-50/50 p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <span class="text-xs font-semibold text-amber-700">
                    {{ isRtl ? `تم اكتشاف ${dataQualityIssues.length} مشكلة في البيانات` : `${dataQualityIssues.length} data quality issue(s) detected` }}
                </span>
            </div>
            <div class="max-h-32 overflow-y-auto space-y-1.5 custom-scrollbar">
                <div v-for="(issue, idx) in dataQualityIssues" :key="idx"
                     class="flex items-center gap-2 text-[11px] px-2 py-1 rounded-lg"
                     :class="issue.type === 'error' ? 'bg-red-100/60 text-red-700' : 'bg-amber-100/60 text-amber-700'">
                    <svg v-if="issue.type === 'error'" class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    <svg v-else class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    {{ issue.msg }}
                </div>
            </div>
            <p class="mt-2 text-[10px] text-amber-500">
                {{ isRtl ? 'هذه المشاكل في المعاينة فقط. الصفوف المعيبة سيتم تخطيها أثناء الاستيراد.' : 'These issues are from the preview only. Problematic rows will be skipped during import.' }}
            </p>
        </div>
        </Transition>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-between">
            <button @click="step = 1" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all duration-200">
                {{ isRtl ? 'رجوع' : 'Back' }}
            </button>
            <button @click="step = 3" :disabled="!canImport"
                :class="['px-6 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-2',
                    canImport
                        ? 'bg-teal-500 text-white hover:bg-teal-600 shadow-md shadow-teal-200'
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed']">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                {{ isRtl ? 'التالي' : 'Next' }}
            </button>
        </div>
    </div>

    <!-- Step 3: Confirm -->
    <div v-if="step === 3"
         class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 transition-all duration-500">

        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-teal-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">{{ isRtl ? 'تأكيد الاستيراد' : 'Confirm Import' }}</h2>
            <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'راجع التفاصيل قبل البدء' : 'Review details before starting' }}</p>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-3xl font-bold text-teal-600">{{ totalRows }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'إجمالي الصفوف' : 'Total Rows' }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-3xl font-bold text-blue-600">{{ Object.values(columnMap).filter(v => v).length }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'أعمدة مربوطة' : 'Mapped Columns' }}</div>
            </div>
            <div class="rounded-xl border border-gray-100 p-4 text-center">
                <div class="text-3xl font-bold text-emerald-600">{{ fileName.split('.').pop()?.toUpperCase() }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ isRtl ? 'نوع الملف' : 'File Type' }}</div>
            </div>
        </div>

        <!-- Mapping summary -->
        <div class="rounded-xl border border-gray-100 p-4 mb-8">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                {{ isRtl ? 'ملخص الربط' : 'Mapping Summary' }}
            </h4>
            <div class="space-y-2">
                <div v-for="field in fields.filter(f => columnMap[f.key])" :key="field.key"
                     class="flex items-center gap-3 text-sm">
                    <span class="text-gray-500 w-32">{{ isRtl ? field.labelAr : field.labelEn }}</span>
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <span class="text-gray-800 font-medium">{{ columnMap[field.key] }}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button @click="step = 2" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all duration-200">
                {{ isRtl ? 'رجوع' : 'Back' }}
            </button>
            <button @click="startImport" :disabled="isImporting"
                :class="['px-8 py-3 rounded-xl text-sm font-bold transition-all duration-200 flex items-center gap-2',
                    !isImporting
                        ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white hover:from-teal-600 hover:to-emerald-600 shadow-lg shadow-teal-200'
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed']">
                <svg v-if="isImporting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                {{ isImporting ? (isRtl ? 'جاري الاستيراد...' : 'Importing...') : (isRtl ? 'بدء الاستيراد' : 'Start Import') }}
            </button>
        </div>
    </div>

</div>
</SecretaryLayout>
</template>
