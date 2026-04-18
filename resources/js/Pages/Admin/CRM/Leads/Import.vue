<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    sources: Array,
    campaigns: Array,
    assignees: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
});

const form = useForm({
    file: null,
    lead_source_id: '',
    campaign_id: '',
    assigned_to: '',
    skip_duplicates: true,
});

const fileName = ref('');
const fileSize = ref('');
const dragOver = ref(false);

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function handleFile(e) {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        fileName.value = file.name;
        fileSize.value = formatSize(file.size);
    }
}

function handleDrop(e) {
    e.preventDefault();
    dragOver.value = false;
    const file = e.dataTransfer.files[0];
    if (file && (file.name.endsWith('.csv') || file.name.endsWith('.txt'))) {
        form.file = file;
        fileName.value = file.name;
        fileSize.value = formatSize(file.size);
    }
}

function removeFile() {
    form.file = null;
    fileName.value = '';
    fileSize.value = '';
}

function submit() {
    form.post('/admin/leads-import', {
        forceFormData: true,
    });
}

function downloadTemplate() {
    const headers = ['full_name', 'phone', 'email', 'gender', 'city', 'nationality', 'priority', 'notes'];
    const sampleRow = ['Ahmed Mohamed', '+201234567890', 'ahmed@example.com', 'male', 'Cairo', 'Egyptian', 'warm', 'Interested in laser treatment'];
    const csv = headers.join(',') + '\n' + sampleRow.join(',') + '\n';
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'leads-import-template.csv';
    a.click();
    URL.revokeObjectURL(url);
}

const expectedColumns = [
    { name: 'full_name', required: true, desc: 'Lead full name' },
    { name: 'phone', required: false, desc: 'Phone number' },
    { name: 'email', required: false, desc: 'Email address' },
    { name: 'gender', required: false, desc: 'male or female' },
    { name: 'city', required: false, desc: 'City name' },
    { name: 'nationality', required: false, desc: 'Nationality' },
    { name: 'priority', required: false, desc: 'hot, warm, or cold' },
    { name: 'notes', required: false, desc: 'Additional notes' },
];
</script>

<template>
    <AdminLayout :title="$t('a_import_leads')">
        <div class="space-y-6">
            <!-- Header Card -->
            <div
                :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
            >
                <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                <div class="px-4 md:px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_import_leads_from_csv') }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_import_leads_description') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="downloadTemplate"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>{{ $t('a_download_template') }}</button>
                        <Link href="/admin/leads" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>{{ $t('a_back_to_leads') }}</Link>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- File Upload Dropzone -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                    style="transition-delay: 100ms;"
                >
                    <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_csv_file_upload') }}</h3>
                            <p class="text-xs text-gray-400">{{ $t('a_drag_drop_description') }}</p>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Dropzone when no file selected -->
                        <div
                            v-if="!fileName"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop="handleDrop"
                            :class="[
                                dragOver ? 'border-[#C4A265] bg-[#C4A265]/5 scale-[1.01]' : 'border-gray-200 bg-gray-50/30',
                            ]"
                            class="border-2 border-dashed rounded-2xl p-12 text-center transition-all duration-300 cursor-pointer hover:border-[#C4A265]/50 hover:bg-[#C4A265]/5 group"
                            @click="$refs.fileInput.click()"
                        >
                            <input ref="fileInput" type="file" accept=".csv,.txt" class="hidden" @change="handleFile" />
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 group-hover:bg-[#C4A265]/10 flex items-center justify-center mb-4 transition-all duration-300">
                                <svg class="w-8 h-8 text-gray-300 group-hover:text-[#C4A265] transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-600 group-hover:text-gray-800 transition-colors">{{ $t('a_drop_csv_or_browse') }}</p>
                            <p class="text-xs text-gray-400 mt-1.5">Supports .csv and .txt files -- Maximum size: 2MB</p>
                            <div class="flex items-center justify-center gap-4 mt-4">
                                <span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">.CSV</span>
                                <span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-3 py-1 rounded-full">.TXT</span>
                            </div>
                        </div>

                        <!-- File selected state -->
                        <div v-else class="bg-emerald-50/50 border border-emerald-200/60 rounded-2xl p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center shrink-0">
                                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate">{{ fileName }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ fileSize }} -- Ready to import</p>
                                    <div class="w-full h-1.5 bg-emerald-100 rounded-full mt-2 overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: 100%;"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" @click="$refs.fileInput2.click()" class="px-3 py-2 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-all duration-200">{{ $t('a_change') }}</button>
                                    <button type="button" @click="removeFile" class="px-3 py-2 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-all duration-200">{{ $t('a_remove') }}</button>
                                </div>
                                <input ref="fileInput2" type="file" accept=".csv,.txt" class="hidden" @change="handleFile" />
                            </div>
                        </div>

                        <p v-if="form.errors.file" class="text-xs text-red-500 mt-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ form.errors.file }}
                        </p>
                    </div>
                </div>

                <!-- Import Settings -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                    style="transition-delay: 200ms;"
                >
                    <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_import_settings') }}</h3>
                            <p class="text-xs text-gray-400">{{ $t('a_configure_defaults_hint') }}</p>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    </div>{{ $t('a_default_lead_source') }}</label>
                                <select v-model="form.lead_source_id" class="doctorato-input w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] bg-gray-50/50 transition-all duration-200">
                                    <option value="">{{ $t('a_none') }}</option>
                                    <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1.5">{{ $t('a_applied_to_all_imports') }}</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                                    <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                    </div>{{ $t('a_default_campaign') }}</label>
                                <select v-model="form.campaign_id" class="doctorato-input w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] bg-gray-50/50 transition-all duration-200">
                                    <option value="">{{ $t('a_none') }}</option>
                                    <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                                    <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                    </div>{{ $t('a_assign_to') }}</label>
                                <select v-model="form.assigned_to" class="doctorato-input w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] bg-gray-50/50 transition-all duration-200">
                                    <option value="">Auto-assign (use rules)</option>
                                    <option v-for="u in assignees" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                                <p class="text-[10px] text-gray-400 mt-1.5">{{ $t('a_leave_empty_assignment') }}</p>
                            </div>
                        </div>

                        <!-- Skip Duplicates Toggle -->
                        <div class="mt-6 pt-5 border-t border-gray-100">
                            <label class="flex items-center gap-3 cursor-pointer group" @click.prevent="form.skip_duplicates = !form.skip_duplicates">
                                <div class="relative shrink-0">
                                    <div :class="form.skip_duplicates ? 'bg-[#C4A265]' : 'bg-gray-200'"
                                        class="w-11 h-6 rounded-full transition-all duration-300">
                                    </div>
                                    <div :class="form.skip_duplicates ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0.5'"
                                        class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-all duration-300">
                                    </div>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">{{ $t('a_skip_duplicates') }}</span>
                                    <p class="text-xs text-gray-400">Prevents re-importing leads that already exist in the system</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Field Mapping Reference -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                    style="transition-delay: 300ms;"
                >
                    <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">{{ $t('a_expected_csv_columns') }}</h3>
                            <p class="text-xs text-gray-400">{{ $t('a_csv_headers_hint') }}</p>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div v-for="col in expectedColumns" :key="col.name"
                                class="bg-gray-50/80 rounded-xl p-3.5 border border-gray-100/80 hover:-translate-y-0.5 hover:shadow-sm transition-all duration-300">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <code class="text-xs font-bold text-gray-800 bg-white px-2 py-0.5 rounded-md border border-gray-100">{{ col.name }}</code>
                                    <span v-if="col.required" class="text-[9px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded-full">{{ $t('a_required') }}</span>
                                    <span v-else class="text-[9px] font-medium text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-full">{{ $t('a_optional') }}</span>
                                </div>
                                <p class="text-[10px] text-gray-400 ltr:pl-0.5 rtl:pr-0.5">{{ col.desc }}</p>
                            </div>
                        </div>

                        <div class="mt-5 bg-slate-50/50 rounded-xl p-4 border border-slate-100/60">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1B365D] mb-1.5">{{ $t('a_additional_notes') }}</p>
                                    <ul class="text-[11px] text-[#1B365D] space-y-1">
                                        <li>-- Alternative column names accepted: <code class="bg-slate-100 px-1 py-0.5 rounded text-[#1B365D] font-semibold">name</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-[#1B365D] font-semibold">mobile</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-[#1B365D] font-semibold">e-mail</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-[#1B365D] font-semibold">phone2</code></li>
                                        <li>-- Duplicate detection matches by phone number or email address</li>
                                        <li>-- Lead scoring rules will be automatically applied to each imported lead</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Section -->
                <div
                    :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 ease-out"
                    style="transition-delay: 400ms;"
                >
                    <div class="px-4 md:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center" :class="form.file ? 'bg-emerald-50' : 'bg-gray-50'">
                                <svg v-if="form.file" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-sm text-gray-500">
                                <span v-if="form.file" class="text-emerald-600 font-medium">{{ $t('a_file_ready') }}</span>
                                <span v-else class="text-gray-400">{{ $t('a_select_csv') }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Link href="/admin/leads" class="inline-flex items-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 hover:-translate-y-0.5 transition-all duration-300">{{ $t('a_cancel') }}</Link>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.file"
                                class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);"
                            >
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                {{ form.processing ? $t('a_importing') : $t('a_import_leads') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
