<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    templates: Array,
    leads: Array,
    filters: Object,
});

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

/* ---------- Filters ---------- */
const channelFilter = ref(props.filters?.channel || '');
const categoryFilter = ref(props.filters?.category || '');
const searchQuery = ref(props.filters?.search || '');

function applyFilters() {
    const params = {};
    if (channelFilter.value) params.channel = channelFilter.value;
    if (categoryFilter.value) params.category = categoryFilter.value;
    if (searchQuery.value) params.search = searchQuery.value;
    router.get('/secretary/crm/templates', params, { preserveState: true, replace: true });
}

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

const channels = [
    { value: '', label: { en: 'All Channels', ar: 'كل القنوات' } },
    { value: 'whatsapp', label: { en: 'WhatsApp', ar: 'واتساب' } },
    { value: 'sms', label: { en: 'SMS', ar: 'رسائل' } },
    { value: 'email', label: { en: 'Email', ar: 'بريد إلكتروني' } },
];

const categories = [
    { value: '', label: { en: 'All Categories', ar: 'كل الفئات' } },
    { value: 'welcome', label: { en: 'Welcome', ar: 'ترحيب' } },
    { value: 'follow_up', label: { en: 'Follow-up', ar: 'متابعة' } },
    { value: 'appointment_reminder', label: { en: 'Appointment', ar: 'موعد' } },
    { value: 'promotion', label: { en: 'Promotion', ar: 'عروض' } },
    { value: 're_engagement', label: { en: 'Re-engage', ar: 'إعادة تفاعل' } },
    { value: 'thank_you', label: { en: 'Thank You', ar: 'شكر' } },
    { value: 'custom', label: { en: 'Custom', ar: 'مخصص' } },
];

const channelConfig = {
    whatsapp: {
        label: { en: 'WhatsApp', ar: 'واتساب' },
        bg: '#dcfce7', text: '#16a34a', border: '#bbf7d0',
        icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z',
    },
    sms: {
        label: { en: 'SMS', ar: 'رسائل' },
        bg: '#ede9fe', text: '#7c3aed', border: '#ddd6fe',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
    email: {
        label: { en: 'Email', ar: 'بريد' },
        bg: '#dbeafe', text: '#2563eb', border: '#bfdbfe',
        icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    },
};

const categoryLabels = {
    welcome: { en: 'Welcome', ar: 'ترحيب', color: '#0d9488' },
    follow_up: { en: 'Follow-up', ar: 'متابعة', color: '#0ea5e9' },
    appointment_reminder: { en: 'Appointment', ar: 'موعد', color: '#8b5cf6' },
    promotion: { en: 'Promotion', ar: 'عروض', color: '#f59e0b' },
    re_engagement: { en: 'Re-engage', ar: 'إعادة تفاعل', color: '#ef4444' },
    thank_you: { en: 'Thank You', ar: 'شكر', color: '#10b981' },
    custom: { en: 'Custom', ar: 'مخصص', color: '#6b7280' },
};

function wrapVar(v) { return '\u007B\u007B' + v + '\u007D\u007D'; }

/* ---------- Send Modal ---------- */
const showSendModal = ref(false);
const selectedTemplate = ref(null);
const selectedLead = ref('');
const selectedLang = ref('ar');
const previewText = ref('');
const previewLoading = ref(false);
const sending = ref(false);

function openSendModal(template) {
    selectedTemplate.value = template;
    selectedLead.value = '';
    previewText.value = '';
    showSendModal.value = true;
}

function closeSendModal() {
    showSendModal.value = false;
    selectedTemplate.value = null;
}

async function loadPreview() {
    if (!selectedLead.value || !selectedTemplate.value) return;
    previewLoading.value = true;
    try {
        const res = await fetch('/secretary/crm/templates/preview', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                template_id: selectedTemplate.value.id,
                lead_id: selectedLead.value,
                language: selectedLang.value,
            }),
        });
        const data = await res.json();
        previewText.value = data.rendered || '';
    } catch (e) {
        previewText.value = isRtl.value ? 'خطأ في تحميل المعاينة' : 'Error loading preview';
    }
    previewLoading.value = false;
}

watch([selectedLead, selectedLang], () => {
    if (selectedLead.value) loadPreview();
});

function sendTemplate() {
    if (!selectedLead.value || !selectedTemplate.value) return;
    sending.value = true;
    router.post(`/secretary/crm/leads/${selectedLead.value}/quick-send`, {
        template_id: selectedTemplate.value.id,
        channel: selectedTemplate.value.channel,
        language: selectedLang.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            sending.value = false;
            closeSendModal();
        },
    });
}

/* ---------- Copy to clipboard ---------- */
const copiedId = ref(null);
function copyBody(template) {
    const text = isRtl.value ? template.body_ar : template.body_en;
    navigator.clipboard.writeText(text).then(() => {
        copiedId.value = template.id;
        setTimeout(() => { copiedId.value = null; }, 2000);
    });
}
</script>

<template>
<SecretaryLayout :title="isRtl ? 'قوالب التواصل' : 'Communication Templates'">
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
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h10"/></svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ isRtl ? 'قوالب التواصل' : 'Communication Templates' }}</h1>
                    <p class="text-teal-100 mt-1 text-sm">{{ isRtl ? 'قوالب جاهزة للتواصل السريع مع العملاء' : 'Ready-made templates for quick client communication' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-white/80 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                {{ templates?.length || 0 }} {{ isRtl ? 'قالب' : 'templates' }}
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
        <div class="flex flex-col md:flex-row gap-3">
            <!-- Search -->
            <div class="flex-1 relative">
                <svg class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                <input v-model="searchQuery" type="text"
                       :placeholder="isRtl ? 'بحث في القوالب...' : 'Search templates...'"
                       class="w-full border border-slate-200 rounded-xl py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                       :class="isRtl ? 'pr-10 pl-3' : 'pl-10 pr-3'"/>
            </div>
            <!-- Channel -->
            <select v-model="channelFilter" @change="applyFilters" class="border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option v-for="ch in channels" :key="ch.value" :value="ch.value">{{ isRtl ? ch.label.ar : ch.label.en }}</option>
            </select>
            <!-- Category -->
            <select v-model="categoryFilter" @change="applyFilters" class="border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ isRtl ? cat.label.ar : cat.label.en }}</option>
            </select>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="(template, idx) in templates" :key="template.id"
             :class="['bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition-all duration-700 group', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: (150 + idx * 50) + 'ms' }">

            <!-- Card Header -->
            <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Channel icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         :style="{ background: channelConfig[template.channel]?.bg, color: channelConfig[template.channel]?.text }">
                        <svg class="w-5 h-5" :fill="template.channel === 'whatsapp' ? 'currentColor' : 'none'" :stroke="template.channel === 'whatsapp' ? 'none' : 'currentColor'" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig[template.channel]?.icon"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-800 text-sm truncate">{{ template.name }}</h3>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :style="{ background: channelConfig[template.channel]?.bg, color: channelConfig[template.channel]?.text }">
                                {{ isRtl ? channelConfig[template.channel]?.label?.ar : channelConfig[template.channel]?.label?.en }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :style="{ background: categoryLabels[template.category]?.color + '15', color: categoryLabels[template.category]?.color }">
                                {{ isRtl ? categoryLabels[template.category]?.ar : categoryLabels[template.category]?.en }}
                            </span>
                        </div>
                    </div>
                </div>
                <span class="text-xs text-slate-400 flex items-center gap-1 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    {{ template.usage_count }}
                </span>
            </div>

            <!-- Body Preview -->
            <div class="px-5 pb-3">
                <div class="bg-slate-50 rounded-xl p-3 text-sm text-slate-600 leading-relaxed max-h-24 overflow-hidden relative">
                    {{ isRtl ? template.body_ar : template.body_en }}
                    <div class="absolute bottom-0 inset-x-0 h-6 bg-gradient-to-t from-slate-50 to-transparent"></div>
                </div>
                <!-- Variables -->
                <div v-if="template.variables && template.variables.length" class="flex flex-wrap gap-1.5 mt-2">
                    <span v-for="v in template.variables" :key="v" class="text-xs bg-teal-50 text-teal-600 px-2 py-0.5 rounded-md font-mono" v-text="wrapVar(v)"></span>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="px-5 pb-4 flex items-center gap-2">
                <button @click="openSendModal(template)"
                        class="flex-1 flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium py-2.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    {{ isRtl ? 'إرسال' : 'Send' }}
                </button>
                <button @click="copyBody(template)"
                        class="flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium py-2.5 px-4 rounded-xl transition-colors">
                    <svg v-if="copiedId !== template.id" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg v-else class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ copiedId === template.id ? (isRtl ? 'تم النسخ' : 'Copied') : (isRtl ? 'نسخ' : 'Copy') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div v-if="!templates || templates.length === 0"
         :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-700 mb-2">{{ isRtl ? 'لا توجد قوالب' : 'No Templates Found' }}</h3>
        <p class="text-sm text-slate-500">{{ isRtl ? 'لم يتم إنشاء قوالب بعد. تواصل مع المدير لإضافة قوالب.' : 'No templates created yet. Contact admin to add templates.' }}</p>
    </div>

</div>

<!-- ─── Send Modal ─── -->
<Teleport to="body">
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showSendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeSendModal"></div>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-4"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showSendModal" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between rounded-t-2xl z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 :style="{ background: channelConfig[selectedTemplate?.channel]?.bg, color: channelConfig[selectedTemplate?.channel]?.text }">
                                <svg class="w-5 h-5" :fill="selectedTemplate?.channel === 'whatsapp' ? 'currentColor' : 'none'" :stroke="selectedTemplate?.channel === 'whatsapp' ? 'none' : 'currentColor'" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig[selectedTemplate?.channel]?.icon"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800">{{ isRtl ? 'إرسال قالب' : 'Send Template' }}</h3>
                                <p class="text-xs text-slate-500">{{ selectedTemplate?.name }}</p>
                            </div>
                        </div>
                        <button @click="closeSendModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Select Lead -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ isRtl ? 'اختر العميل' : 'Select Lead' }}</label>
                            <select v-model="selectedLead" class="w-full border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                <option value="">{{ isRtl ? '-- اختر --' : '-- Choose --' }}</option>
                                <option v-for="lead in leads" :key="lead.id" :value="lead.id">
                                    {{ lead.full_name }} - {{ lead.phone }}
                                </option>
                            </select>
                        </div>

                        <!-- Language Toggle -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ isRtl ? 'اللغة' : 'Language' }}</label>
                            <div class="flex gap-2">
                                <button @click="selectedLang = 'ar'"
                                        :class="['flex-1 py-2.5 rounded-xl text-sm font-medium transition-all border', selectedLang === 'ar' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300']">
                                    العربية
                                </button>
                                <button @click="selectedLang = 'en'"
                                        :class="['flex-1 py-2.5 rounded-xl text-sm font-medium transition-all border', selectedLang === 'en' ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300']">
                                    English
                                </button>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ isRtl ? 'معاينة الرسالة' : 'Message Preview' }}</label>
                            <div class="bg-slate-50 rounded-xl p-4 min-h-[80px] text-sm text-slate-600 leading-relaxed border border-slate-100">
                                <div v-if="previewLoading" class="flex items-center gap-2 text-slate-400">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ isRtl ? 'جاري التحميل...' : 'Loading...' }}
                                </div>
                                <div v-else-if="previewText" :dir="selectedLang === 'ar' ? 'rtl' : 'ltr'" class="whitespace-pre-wrap">{{ previewText }}</div>
                                <div v-else class="text-slate-400 italic">{{ isRtl ? 'اختر عميل لعرض المعاينة' : 'Select a lead to see preview' }}</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-2">
                            <button @click="sendTemplate" :disabled="!selectedLead || sending"
                                    :class="['flex-1 flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all',
                                              !selectedLead || sending ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-200']">
                                <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                {{ sending ? (isRtl ? 'جاري الإرسال...' : 'Sending...') : (isRtl ? 'إرسال' : 'Send') }}
                            </button>
                            <button @click="closeSendModal"
                                    class="px-6 py-3 rounded-xl text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                                {{ isRtl ? 'إلغاء' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</Teleport>

</SecretaryLayout>
</template>
