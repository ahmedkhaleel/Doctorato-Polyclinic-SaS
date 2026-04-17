<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
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

/* ---------- Animated Counters ---------- */
const animatedTotal = ref(0);
const animatedWhatsapp = ref(0);
const animatedSms = ref(0);
const animatedEmail = ref(0);

function animateCounter(target, endValue, duration = 1200) {
    const start = 0;
    const startTime = performance.now();
    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        target.value = Math.round(start + (endValue - start) * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

onMounted(() => {
    const all = props.templates || [];
    const byChannel = {};
    all.forEach(t => { byChannel[t.channel] = (byChannel[t.channel] || 0) + 1; });
    setTimeout(() => {
        animateCounter(animatedTotal, all.length);
        animateCounter(animatedWhatsapp, byChannel.whatsapp || 0);
        animateCounter(animatedSms, byChannel.sms || 0);
        animateCounter(animatedEmail, byChannel.email || 0);
    }, 400);
});

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
    { value: '', label: { en: 'All Channels', ar: 'كل القنوات' }, icon: null },
    { value: 'whatsapp', label: { en: 'WhatsApp', ar: 'واتساب' }, icon: 'whatsapp' },
    { value: 'sms', label: { en: 'SMS', ar: 'رسائل' }, icon: 'sms' },
    { value: 'email', label: { en: 'Email', ar: 'بريد إلكتروني' }, icon: 'email' },
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
        bg: '#dcfce7', text: '#16a34a', border: '#bbf7d0', strip: '#22c55e',
        bubbleBg: '#dcfce7', bubbleText: '#166534',
        icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z',
    },
    sms: {
        label: { en: 'SMS', ar: 'رسائل' },
        bg: '#dbeafe', text: '#2563eb', border: '#bfdbfe', strip: '#3b82f6',
        bubbleBg: '#dbeafe', bubbleText: '#1e40af',
        icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    },
    email: {
        label: { en: 'Email', ar: 'بريد' },
        bg: '#f3e8ff', text: '#7c3aed', border: '#e9d5ff', strip: '#8b5cf6',
        bubbleBg: '#f3e8ff', bubbleText: '#5b21b6',
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

/* ---------- Send / Preview Modal ---------- */
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

function copyPreview() {
    if (!previewText.value) return;
    navigator.clipboard.writeText(previewText.value).then(() => {
        copiedPreview.value = true;
        setTimeout(() => { copiedPreview.value = false; }, 2000);
    });
}
const copiedPreview = ref(false);

/* ---------- Template effectiveness ---------- */
function getEffectiveness(template) {
    const usage = template.usage_count || 0;
    if (usage === 0) return { score: 0, label: { en: 'Unused', ar: 'غير مستخدم' }, color: 'text-gray-400', bg: 'bg-gray-50' };
    if (usage < 5) return { score: 1, label: { en: 'Low', ar: 'منخفض' }, color: 'text-amber-600', bg: 'bg-amber-50' };
    if (usage < 20) return { score: 2, label: { en: 'Good', ar: 'جيد' }, color: 'text-[#1B365D]', bg: 'bg-slate-50' };
    return { score: 3, label: { en: 'Excellent', ar: 'ممتاز' }, color: 'text-emerald-600', bg: 'bg-emerald-50' };
}

/* ---------- Template stats ---------- */
const templateStats = computed(() => {
    const all = props.templates || [];
    const totalUsage = all.reduce((sum, t) => sum + (t.usage_count || 0), 0);
    const mostUsed = all.length > 0 ? [...all].sort((a, b) => (b.usage_count || 0) - (a.usage_count || 0))[0] : null;
    const byChannel = {};
    all.forEach(t => {
        byChannel[t.channel] = (byChannel[t.channel] || 0) + 1;
    });
    return { total: all.length, totalUsage, mostUsed, byChannel };
});

/* ---------- Expand body preview ---------- */
const expandedId = ref(null);
function toggleExpand(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

/* ---------- Duplicate template ---------- */
const duplicating = ref(null);
const duplicateSuccess = ref(null);
function duplicateTemplate(template) {
    duplicating.value = template.id;
    router.post('/secretary/crm/templates/duplicate', {
        _duplicate: template.id,
        name: template.name + (isRtl.value ? ' (نسخة)' : ' (Copy)'),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            duplicateSuccess.value = template.id;
            setTimeout(() => { duplicateSuccess.value = null; }, 2500);
        },
        onFinish: () => { duplicating.value = null; },
    });
}

/* ---------- Inline name edit ---------- */
const editingNameId = ref(null);
const editingNameValue = ref('');

function startEditName(template) {
    editingNameId.value = template.id;
    editingNameValue.value = template.name;
}

function saveEditName(templateId) {
    if (!editingNameValue.value.trim()) { editingNameId.value = null; return; }
    router.post(`/secretary/crm/templates/${templateId}/rename`, {
        name: editingNameValue.value.trim(),
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => { editingNameId.value = null; },
    });
}

function cancelEditName() {
    editingNameId.value = null;
    editingNameValue.value = '';
}

/* ---------- Last used date formatter ---------- */
function formatLastUsed(date) {
    if (!date) return isRtl.value ? 'لم يُستخدم' : 'Never used';
    const d = new Date(date);
    const diff = Math.floor((Date.now() - d.getTime()) / 86400000);
    if (diff === 0) return isRtl.value ? 'اليوم' : 'Today';
    if (diff === 1) return isRtl.value ? 'أمس' : 'Yesterday';
    if (diff < 7) return isRtl.value ? `منذ ${diff} أيام` : `${diff}d ago`;
    return d.toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-GB', { month: 'short', day: 'numeric' });
}

/* ---------- Current time for preview ---------- */
function currentTime() {
    return new Date().toLocaleTimeString(isRtl.value ? 'ar-SA' : 'en-US', { hour: '2-digit', minute: '2-digit' });
}

/* ---------- Selected lead name ---------- */
const selectedLeadName = computed(() => {
    if (!selectedLead.value) return '';
    const lead = (props.leads || []).find(l => l.id == selectedLead.value);
    return lead ? lead.full_name : '';
});
</script>

<template>
<SecretaryLayout :title="isRtl ? 'قوالب التواصل' : 'Communication Templates'">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/30 to-white p-4 md:p-6 lg:p-8" :dir="isRtl ? 'rtl' : 'ltr'">

    <!-- ============================================ -->
    <!--  HERO HEADER                                 -->
    <!-- ============================================ -->
    <div :class="['relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-700 via-teal-600 to-emerald-500 p-5 sm:p-8 md:p-10 mb-8 shadow-2xl shadow-teal-900/20 transition-all duration-1000', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-8']">
        <!-- Decorative SVG shapes -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <svg class="absolute -top-16 -right-16 w-72 h-72 text-white/[0.06] animate-[spin_60s_linear_infinite]" viewBox="0 0 200 200" fill="currentColor">
                <path d="M100 0C100 55.228 55.228 100 0 100C55.228 100 100 144.772 100 200C100 144.772 144.772 100 200 100C144.772 100 100 55.228 100 0Z"/>
            </svg>
            <svg class="absolute -bottom-12 -left-12 w-56 h-56 text-white/[0.05]" viewBox="0 0 200 200" fill="currentColor">
                <circle cx="100" cy="100" r="80"/>
            </svg>
            <svg class="absolute top-8 right-1/3 w-24 h-24 text-white/[0.04] animate-pulse" viewBox="0 0 100 100" fill="currentColor">
                <rect x="10" y="10" width="80" height="80" rx="20"/>
            </svg>
            <!-- Floating dots -->
            <div class="absolute top-6 left-1/4 w-2 h-2 bg-white/20 rounded-full animate-bounce" style="animation-delay: 0s; animation-duration: 3s;"></div>
            <div class="absolute bottom-10 right-1/4 w-1.5 h-1.5 bg-white/15 rounded-full animate-bounce" style="animation-delay: 1s; animation-duration: 4s;"></div>
            <div class="absolute top-1/3 left-16 w-1 h-1 bg-white/20 rounded-full animate-bounce" style="animation-delay: 0.5s; animation-duration: 3.5s;"></div>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 sm:gap-6">
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center ring-1 ring-white/20 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h10"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight">{{ isRtl ? 'قوالب التواصل' : 'Communication Templates' }}</h1>
                    <p class="text-teal-100/80 mt-1.5 text-sm md:text-base">{{ isRtl ? 'قوالب جاهزة للتواصل السريع والفعال مع العملاء' : 'Ready-made templates for quick and effective client communication' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/10">
                <svg class="w-5 h-5 text-teal-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="text-2xl font-bold text-white">{{ templates?.length || 0 }}</span>
                <span class="text-teal-200 text-sm">{{ isRtl ? 'قالب متاح' : 'templates available' }}</span>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!--  STATS STRIP                                 -->
    <!-- ============================================ -->
    <div :class="['grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-8 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionDelay: '150ms' }">
        <!-- Total -->
        <div class="relative overflow-hidden bg-gradient-to-br from-teal-50 to-white rounded-2xl border border-teal-100/60 p-4 md:p-5 shadow-sm group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="absolute top-0 right-0 w-20 h-20 bg-teal-500/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-teal-700">{{ animatedTotal }}</div>
                <div class="text-xs text-teal-600/70 font-medium mt-0.5">{{ isRtl ? 'إجمالي القوالب' : 'Total Templates' }}</div>
            </div>
        </div>
        <!-- WhatsApp -->
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-50 to-white rounded-2xl border border-emerald-100/60 p-4 md:p-5 shadow-sm group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                        <path :d="channelConfig.whatsapp.icon"/>
                    </svg>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-emerald-700">{{ animatedWhatsapp }}</div>
                <div class="text-xs text-emerald-600/70 font-medium mt-0.5">{{ isRtl ? 'واتساب' : 'WhatsApp' }}</div>
            </div>
        </div>
        <!-- SMS -->
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-100/60 p-4 md:p-5 shadow-sm group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="absolute top-0 right-0 w-20 h-20 bg-[#1B365D]/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig.sms.icon"/>
                    </svg>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-[#1B365D]">{{ animatedSms }}</div>
                <div class="text-xs text-[#1B365D]/70 font-medium mt-0.5">{{ isRtl ? 'رسائل نصية' : 'SMS' }}</div>
            </div>
        </div>
        <!-- Email -->
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-100/60 p-4 md:p-5 shadow-sm group hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
            <div class="absolute top-0 right-0 w-20 h-20 bg-[#1B365D]/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="relative">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig.email.icon"/>
                    </svg>
                </div>
                <div class="text-2xl md:text-3xl font-bold text-[#1B365D]">{{ animatedEmail }}</div>
                <div class="text-xs text-[#1B365D]/70 font-medium mt-0.5">{{ isRtl ? 'بريد إلكتروني' : 'Email' }}</div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!--  SEARCH + FILTERS BAR                        -->
    <!-- ============================================ -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-200/60 p-4 sm:p-5 mb-8 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
         :style="{ transitionDelay: '200ms' }">
        <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center">
            <!-- Search -->
            <div class="flex-1 relative">
                <div :class="['absolute top-1/2 -translate-y-1/2 text-slate-400', isRtl ? 'right-4' : 'left-4']">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/>
                        <path stroke-linecap="round" d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
                <input v-model="searchQuery" type="text"
                       :placeholder="isRtl ? 'بحث في القوالب بالاسم او المحتوى...' : 'Search templates by name or content...'"
                       class="w-full border border-slate-200 rounded-xl py-3 text-sm bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 placeholder:text-slate-400"
                       :class="isRtl ? 'pr-12 pl-4' : 'pl-12 pr-4'"/>
            </div>

            <!-- Channel Segmented Buttons -->
            <div class="flex items-center bg-slate-100/80 rounded-xl p-1 gap-0.5 overflow-x-auto scrollbar-none">
                <button v-for="ch in channels" :key="ch.value"
                        @click="channelFilter = ch.value; applyFilters()"
                        :class="['relative flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 whitespace-nowrap',
                                  channelFilter === ch.value
                                    ? 'bg-white text-teal-700 shadow-sm ring-1 ring-slate-200/60'
                                    : 'text-slate-500 hover:text-slate-700 hover:bg-white/50']">
                    <template v-if="ch.icon === 'whatsapp'">
                        <svg class="w-3.5 h-3.5" :class="channelFilter === ch.value ? 'text-emerald-600' : ''" fill="currentColor" viewBox="0 0 24 24"><path :d="channelConfig.whatsapp.icon"/></svg>
                    </template>
                    <template v-else-if="ch.icon === 'sms'">
                        <svg class="w-3.5 h-3.5" :class="channelFilter === ch.value ? 'text-[#1B365D]' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig.sms.icon"/></svg>
                    </template>
                    <template v-else-if="ch.icon === 'email'">
                        <svg class="w-3.5 h-3.5" :class="channelFilter === ch.value ? 'text-[#1B365D]' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig.email.icon"/></svg>
                    </template>
                    <template v-else>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </template>
                    {{ isRtl ? ch.label.ar : ch.label.en }}
                </button>
            </div>

            <!-- Category Dropdown -->
            <div class="relative">
                <select v-model="categoryFilter" @change="applyFilters"
                        class="appearance-none w-full lg:w-auto border border-slate-200 rounded-xl py-3 text-sm bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 cursor-pointer"
                        :class="isRtl ? 'pr-4 pl-10' : 'pl-4 pr-10'">
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ isRtl ? cat.label.ar : cat.label.en }}</option>
                </select>
                <div :class="['absolute top-1/2 -translate-y-1/2 pointer-events-none text-slate-400', isRtl ? 'left-3' : 'right-3']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!--  TEMPLATE CARDS GRID                         -->
    <!-- ============================================ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
        <div v-for="(template, idx) in templates" :key="template.id"
             :class="['relative bg-white rounded-2xl shadow-sm border border-slate-200/60 overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-500', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10']"
             :style="{ transitionDelay: (250 + idx * 70) + 'ms' }">

            <!-- Channel Colored Top Strip -->
            <div class="h-1.5" :style="{ background: `linear-gradient(90deg, ${channelConfig[template.channel]?.strip || '#0d9488'}, ${channelConfig[template.channel]?.strip || '#0d9488'}88)` }"></div>

            <!-- Duplicate success flash -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="duplicateSuccess === template.id" class="absolute inset-0 z-20 bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center rounded-2xl">
                    <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-teal-700">{{ isRtl ? 'تم تكرار القالب بنجاح' : 'Template duplicated successfully' }}</p>
                </div>
            </Transition>

            <!-- Card Header -->
            <div class="px-3 sm:px-5 pt-4 sm:pt-5 pb-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <!-- Channel icon badge -->
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 shadow-sm transition-transform duration-300 group-hover:scale-110"
                             :style="{ background: channelConfig[template.channel]?.bg, color: channelConfig[template.channel]?.text, ringColor: channelConfig[template.channel]?.border }">
                            <svg class="w-5 h-5" :fill="template.channel === 'whatsapp' ? 'currentColor' : 'none'" :stroke="template.channel === 'whatsapp' ? 'none' : 'currentColor'" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig[template.channel]?.icon"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <!-- Inline name edit -->
                            <div v-if="editingNameId === template.id" class="flex items-center gap-1.5">
                                <input v-model="editingNameValue" type="text"
                                    class="flex-1 min-w-0 rounded-lg border-teal-300 text-sm font-semibold py-1 px-2 focus:ring-teal-500 focus:border-teal-500"
                                    @keyup.enter="saveEditName(template.id)" @keyup.escape="cancelEditName" />
                                <button @click="saveEditName(template.id)" class="w-7 h-7 rounded-lg bg-teal-500 text-white flex items-center justify-center hover:bg-teal-600 flex-shrink-0 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button @click="cancelEditName" class="w-7 h-7 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-gray-200 flex-shrink-0 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <h3 v-else @dblclick="startEditName(template)" class="font-bold text-slate-800 text-sm truncate cursor-pointer hover:text-teal-600 transition-colors" :title="isRtl ? 'انقر مرتين للتعديل' : 'Double-click to rename'">{{ template.name }}</h3>
                            <!-- Badges -->
                            <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                                      :style="{ background: channelConfig[template.channel]?.bg, color: channelConfig[template.channel]?.text }">
                                    {{ isRtl ? channelConfig[template.channel]?.label?.ar : channelConfig[template.channel]?.label?.en }}
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                                      :style="{ background: categoryLabels[template.category]?.color + '15', color: categoryLabels[template.category]?.color }">
                                    {{ isRtl ? categoryLabels[template.category]?.ar : categoryLabels[template.category]?.en }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Usage indicator -->
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <div :class="['inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full border transition-colors',
                            (template.usage_count || 0) >= 20 ? 'bg-teal-50 text-teal-700 border-teal-200' :
                            (template.usage_count || 0) >= 5 ? 'bg-slate-50 text-[#1B365D] border-slate-200' :
                            'bg-slate-50 text-slate-400 border-slate-200']">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            {{ template.usage_count || 0 }}
                        </div>
                        <span :class="['text-[10px] font-semibold', getEffectiveness(template).color]">
                            {{ isRtl ? getEffectiveness(template).label.ar : getEffectiveness(template).label.en }}
                        </span>
                        <span class="text-[10px] text-slate-400 flex items-center gap-0.5">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ formatLastUsed(template.last_used_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Body Preview -->
            <div class="px-3 sm:px-5 pb-3">
                <div @click="toggleExpand(template.id)"
                     class="relative bg-gradient-to-br from-slate-50 to-slate-100/50 rounded-xl p-3.5 text-sm text-slate-600 leading-relaxed cursor-pointer hover:from-slate-100 hover:to-slate-50 transition-all duration-200 border border-slate-100"
                     :class="expandedId !== template.id ? 'max-h-[5.5rem] overflow-hidden' : ''">
                    <div class="whitespace-pre-wrap">{{ isRtl ? template.body_ar : template.body_en }}</div>
                    <div v-if="expandedId !== template.id" class="absolute bottom-0 inset-x-0 h-10 bg-gradient-to-t from-slate-50 via-slate-50/90 to-transparent flex items-end justify-center pb-1.5 rounded-b-xl">
                        <span class="text-[10px] text-teal-600 font-semibold tracking-wide uppercase">{{ isRtl ? 'اعرض المزيد' : 'Show more' }}</span>
                    </div>
                </div>
                <!-- Variables -->
                <div v-if="template.variables && template.variables.length" class="flex flex-wrap gap-1.5 mt-2.5">
                    <span v-for="v in template.variables" :key="v"
                          class="text-[11px] bg-teal-50 text-teal-700 px-2 py-0.5 rounded-md font-mono border border-teal-100" v-text="wrapVar(v)"></span>
                </div>
            </div>

            <!-- Card Actions -->
            <div class="px-3 sm:px-5 pb-5 pt-1">
                <div class="flex items-center gap-2">
                    <!-- Send Button -->
                    <button @click="openSendModal(template)"
                            class="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-teal-600 to-teal-500 hover:from-teal-700 hover:to-teal-600 text-white text-sm font-semibold py-2.5 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        {{ isRtl ? 'إرسال' : 'Send' }}
                    </button>
                    <!-- Preview / Eye -->
                    <button @click="openSendModal(template)"
                            class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-teal-50 hover:text-teal-600 text-slate-500 rounded-xl transition-all duration-200 active:scale-95"
                            :title="isRtl ? 'معاينة' : 'Preview'">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <!-- Copy -->
                    <button @click="copyBody(template)"
                            class="flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-200 active:scale-95"
                            :class="copiedId === template.id ? 'bg-teal-50 text-teal-600' : 'bg-slate-100 hover:bg-slate-200 text-slate-500'"
                            :title="isRtl ? 'نسخ' : 'Copy'">
                        <svg v-if="copiedId !== template.id" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                    <!-- Duplicate -->
                    <button @click="duplicateTemplate(template)" :disabled="duplicating === template.id"
                            class="flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-slate-50 hover:text-[#1B365D] text-slate-500 rounded-xl transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            :title="isRtl ? 'تكرار القالب' : 'Duplicate'">
                        <svg v-if="duplicating !== template.id" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                        <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!--  EMPTY STATE                                 -->
    <!-- ============================================ -->
    <div v-if="!templates || templates.length === 0"
         :class="['bg-white rounded-3xl shadow-sm border border-slate-200/60 p-16 text-center transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10']"
         :style="{ transitionDelay: '300ms' }">
        <!-- Illustrated empty state -->
        <div class="relative w-32 h-32 mx-auto mb-8">
            <div class="absolute inset-0 bg-teal-100/50 rounded-3xl rotate-6"></div>
            <div class="absolute inset-0 bg-teal-50 rounded-3xl -rotate-3"></div>
            <div class="relative bg-white rounded-3xl shadow-sm border border-teal-100 w-full h-full flex items-center justify-center">
                <svg class="w-14 h-14 text-teal-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 12h10"/>
                </svg>
            </div>
            <!-- Floating accent dots -->
            <div class="absolute -top-2 -right-2 w-5 h-5 bg-teal-200 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
            <div class="absolute -bottom-1 -left-3 w-3 h-3 bg-emerald-200 rounded-full animate-bounce" style="animation-delay: 1s; animation-duration: 4s;"></div>
        </div>
        <h3 class="text-xl font-bold text-slate-700 mb-3">{{ isRtl ? 'لا توجد قوالب' : 'No Templates Found' }}</h3>
        <p class="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">{{ isRtl ? 'لم يتم العثور على قوالب تطابق معايير البحث. جرب تعديل الفلاتر أو تواصل مع المدير لإضافة قوالب جديدة.' : 'No templates match your current filters. Try adjusting your search criteria or contact an admin to add new templates.' }}</p>
        <button v-if="channelFilter || categoryFilter || searchQuery"
                @click="channelFilter = ''; categoryFilter = ''; searchQuery = ''; applyFilters()"
                class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-teal-50 text-teal-700 text-sm font-semibold rounded-xl hover:bg-teal-100 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ isRtl ? 'مسح الفلاتر' : 'Clear filters' }}
        </button>
    </div>

</div>

<!-- ============================================ -->
<!--  PREVIEW / SEND MODAL (Teleport to body)    -->
<!-- ============================================ -->
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
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeSendModal"></div>

            <Transition
                enter-active-class="transition duration-400 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-6"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95 translate-y-4"
            >
                <div v-if="showSendModal" class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto ring-1 ring-black/5">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 sm:px-6 py-4 flex items-center justify-between rounded-t-3xl z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center ring-1 shadow-sm"
                                 :style="{ background: channelConfig[selectedTemplate?.channel]?.bg, color: channelConfig[selectedTemplate?.channel]?.text, ringColor: channelConfig[selectedTemplate?.channel]?.border }">
                                <svg class="w-5 h-5" :fill="selectedTemplate?.channel === 'whatsapp' ? 'currentColor' : 'none'" :stroke="selectedTemplate?.channel === 'whatsapp' ? 'none' : 'currentColor'" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="channelConfig[selectedTemplate?.channel]?.icon"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">{{ isRtl ? 'معاينة وإرسال' : 'Preview & Send' }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ selectedTemplate?.name }}</p>
                            </div>
                        </div>
                        <button @click="closeSendModal" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-red-50 hover:text-red-500 text-slate-400 flex items-center justify-center transition-all duration-200 active:scale-95">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5">
                        <!-- Select Lead -->
                        <div>
                            <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 mb-2">
                                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ isRtl ? 'اختر العميل' : 'Select Lead' }}
                            </label>
                            <select v-model="selectedLead" class="w-full border border-slate-200 rounded-xl py-3 px-4 text-sm bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all">
                                <option value="">{{ isRtl ? '-- اختر عميل --' : '-- Choose a lead --' }}</option>
                                <option v-for="lead in leads" :key="lead.id" :value="lead.id">
                                    {{ lead.full_name }} - {{ lead.phone }}
                                </option>
                            </select>
                        </div>

                        <!-- Language Toggle -->
                        <div>
                            <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 mb-2">
                                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                                {{ isRtl ? 'اللغة' : 'Language' }}
                            </label>
                            <div class="flex gap-2">
                                <button @click="selectedLang = 'ar'"
                                        :class="['flex-1 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 border-2',
                                                  selectedLang === 'ar'
                                                    ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-200'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:bg-teal-50']">
                                    العربية
                                </button>
                                <button @click="selectedLang = 'en'"
                                        :class="['flex-1 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 border-2',
                                                  selectedLang === 'en'
                                                    ? 'bg-teal-600 text-white border-teal-600 shadow-md shadow-teal-200'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300 hover:bg-teal-50']">
                                    English
                                </button>
                            </div>
                        </div>

                        <!-- Channel-Appropriate Preview -->
                        <div>
                            <label class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 mb-2">
                                <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ isRtl ? 'معاينة الرسالة' : 'Message Preview' }}
                            </label>

                            <!-- Loading state -->
                            <div v-if="previewLoading" class="flex items-center justify-center gap-2 text-slate-400 py-12 bg-slate-50 rounded-2xl border border-slate-100">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <span class="text-sm">{{ isRtl ? 'جاري التحميل...' : 'Loading preview...' }}</span>
                            </div>

                            <!-- Empty / no lead selected -->
                            <div v-else-if="!previewText && !selectedLead" class="flex flex-col items-center justify-center py-12 bg-slate-50/70 rounded-2xl border border-dashed border-slate-200">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <p class="text-sm text-slate-400">{{ isRtl ? 'اختر عميل لعرض المعاينة' : 'Select a lead to see preview' }}</p>
                            </div>

                            <!-- WhatsApp Preview Mockup -->
                            <div v-else-if="previewText && selectedTemplate?.channel === 'whatsapp'" class="rounded-2xl overflow-hidden border border-emerald-100">
                                <!-- WhatsApp header bar -->
                                <div class="bg-gradient-to-r from-emerald-700 to-emerald-600 px-4 py-3 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-white text-sm font-semibold">{{ selectedLeadName || (isRtl ? 'العميل' : 'Lead') }}</p>
                                        <p class="text-emerald-200 text-[10px]">{{ isRtl ? 'متصل' : 'online' }}</p>
                                    </div>
                                </div>
                                <!-- Chat area -->
                                <div class="bg-[#e5ddd5] p-4" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iMSIgZmlsbD0icmdiYSgwLDAsMCwwLjAzKSIvPjwvc3ZnPg==');">
                                    <div :class="['max-w-[85%] bg-dcfce7 rounded-2xl rounded-tr-sm px-4 py-2.5 shadow-sm', isRtl ? 'mr-auto' : 'ml-auto']"
                                         style="background-color: #dcfce7;">
                                        <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap" :dir="selectedLang === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                        <div class="flex items-center justify-end gap-1 mt-1">
                                            <span class="text-[10px] text-slate-500">{{ currentTime() }}</span>
                                            <svg class="w-3.5 h-3.5 text-[#1B365D]" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SMS Preview Mockup -->
                            <div v-else-if="previewText && selectedTemplate?.channel === 'sms'" class="rounded-2xl overflow-hidden border border-slate-100">
                                <div class="bg-gradient-to-r from-[#1B365D] to-[#1B365D] px-4 py-3 flex items-center justify-center">
                                    <p class="text-white text-sm font-semibold">{{ selectedLeadName || (isRtl ? 'رسالة نصية' : 'Text Message') }}</p>
                                </div>
                                <div class="bg-gradient-to-b from-slate-50 to-white p-4">
                                    <div :class="['max-w-[85%] rounded-2xl rounded-tr-sm px-4 py-3 shadow-sm', isRtl ? 'mr-auto' : 'ml-auto']"
                                         style="background-color: #dbeafe;">
                                        <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap" :dir="selectedLang === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                        <p class="text-[10px] text-slate-500 mt-1.5 text-right">{{ currentTime() }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Preview Mockup -->
                            <div v-else-if="previewText && selectedTemplate?.channel === 'email'" class="rounded-2xl overflow-hidden border border-slate-100">
                                <div class="bg-gradient-to-r from-[#1B365D] to-[#1B365D] px-4 py-3">
                                    <p class="text-white text-sm font-semibold">{{ isRtl ? 'بريد إلكتروني' : 'Email' }}</p>
                                </div>
                                <div class="bg-white p-4 border-b border-slate-100">
                                    <div class="space-y-1.5 text-xs text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-600 w-12">{{ isRtl ? 'إلى:' : 'To:' }}</span>
                                            <span>{{ selectedLeadName || '...' }}</span>
                                        </div>
                                        <div v-if="selectedTemplate?.subject" class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-600 w-12">{{ isRtl ? 'الموضوع:' : 'Subj:' }}</span>
                                            <span class="text-slate-700 font-medium">{{ selectedTemplate.subject }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white px-5 py-4">
                                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap" :dir="selectedLang === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                </div>
                            </div>

                            <!-- Fallback preview -->
                            <div v-else-if="previewText" class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap" :dir="selectedLang === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-2">
                            <button @click="sendTemplate" :disabled="!selectedLead || sending"
                                    :class="['flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl text-sm font-bold transition-all duration-200',
                                              !selectedLead || sending
                                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                                : 'bg-gradient-to-r from-teal-600 to-teal-500 hover:from-teal-700 hover:to-teal-600 text-white shadow-lg shadow-teal-200/50 hover:shadow-xl active:scale-[0.98]']">
                                <svg v-if="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                {{ sending ? (isRtl ? 'جاري الإرسال...' : 'Sending...') : (isRtl ? 'إرسال الآن' : 'Send Now') }}
                            </button>
                            <button v-if="previewText" @click="copyPreview"
                                    :class="['px-4 py-3.5 rounded-xl text-sm font-semibold transition-all duration-200 active:scale-95',
                                              copiedPreview ? 'bg-teal-50 text-teal-600 border-2 border-teal-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 border-2 border-transparent']">
                                <svg v-if="!copiedPreview" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button @click="closeSendModal"
                                    class="px-5 py-3.5 rounded-xl text-sm font-semibold text-slate-500 bg-slate-100 hover:bg-slate-200 transition-all duration-200 active:scale-[0.98]">
                                {{ isRtl ? 'إغلاق' : 'Close' }}
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
