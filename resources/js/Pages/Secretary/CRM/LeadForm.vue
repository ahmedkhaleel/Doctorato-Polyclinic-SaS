<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    lead: Object,
    sources: Array,
    campaigns: Array,
    services: Array,
});

const isEdit = computed(() => !!props.lead);
const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 50); });

const form = useForm({
    full_name: props.lead?.full_name || '',
    phone: props.lead?.phone || '',
    phone2: props.lead?.phone2 || '',
    email: props.lead?.email || '',
    gender: props.lead?.gender || '',
    date_of_birth: props.lead?.date_of_birth ? props.lead.date_of_birth.substring(0,10) : '',
    city: props.lead?.city || '',
    nationality: props.lead?.nationality || '',
    lead_source_id: props.lead?.lead_source_id || '',
    campaign_id: props.lead?.campaign_id || '',
    priority: props.lead?.priority || 2,
    interested_services: props.lead?.interested_services || [],
    notes: props.lead?.notes || '',
});

function submit() {
    const url = isEdit.value
        ? `/secretary/crm/leads/${props.lead.id}`
        : '/secretary/crm/leads';
    form.post(url);
}

const duplicateWarning = ref(null);
const checkingDuplicate = ref(false);

async function checkDuplicate() {
    const phone = form.phone.trim();
    if (!phone || phone.length < 7) {
        duplicateWarning.value = null;
        return;
    }
    checkingDuplicate.value = true;
    try {
        const res = await fetch(`/secretary/crm/check-duplicate?phone=${encodeURIComponent(phone)}`, {
            credentials: 'same-origin',
        });
        const data = await res.json();
        duplicateWarning.value = data.exists ? data.lead : null;
    } catch (e) {
        duplicateWarning.value = null;
    }
    checkingDuplicate.value = false;
}

function toggleService(id) {
    const idx = form.interested_services.indexOf(id);
    if (idx > -1) {
        form.interested_services.splice(idx, 1);
    } else {
        form.interested_services.push(id);
    }
}

/* ---------- Service search filter ---------- */
const serviceSearch = ref('');
const filteredServices = computed(() => {
    if (!props.services?.length) return [];
    if (!serviceSearch.value.trim()) return props.services;
    const q = serviceSearch.value.trim().toLowerCase();
    return props.services.filter(s =>
        (s.name_en || '').toLowerCase().includes(q) ||
        (s.name_ar || '').includes(q)
    );
});

const priorityOptions = [
    { value: 1, label: { en: 'Hot', ar: '\u0633\u0627\u062E\u0646' }, icon: 'hot', color: 'bg-red-100 text-red-700 border-red-300 ring-red-400' },
    { value: 2, label: { en: 'Warm', ar: '\u062F\u0627\u0641\u0626' }, icon: 'warm', color: 'bg-amber-100 text-amber-700 border-amber-300 ring-amber-400' },
    { value: 3, label: { en: 'Cold', ar: '\u0628\u0627\u0631\u062F' }, icon: 'cold', color: 'bg-blue-100 text-blue-700 border-blue-300 ring-blue-400' },
];

/* ---------- Form progress indicator ---------- */
const formSections = [
    { key: 'contact', en: 'Contact', ar: '\u0627\u0644\u0627\u062A\u0635\u0627\u0644', check: () => !!form.full_name && !!form.phone },
    { key: 'personal', en: 'Personal', ar: '\u0627\u0644\u0634\u062E\u0635\u064A\u0629', check: () => !!form.gender || !!form.date_of_birth || !!form.city },
    { key: 'details', en: 'Details', ar: '\u0627\u0644\u062A\u0641\u0627\u0635\u064A\u0644', check: () => !!form.lead_source_id || !!form.priority },
    { key: 'notes', en: 'Notes', ar: '\u0645\u0644\u0627\u062D\u0638\u0627\u062A', check: () => !!form.notes },
];

const formProgress = computed(() => {
    const filled = formSections.filter(s => s.check()).length;
    return Math.round((filled / formSections.length) * 100);
});

/* ---------- Email validation ---------- */
const emailValidation = computed(() => {
    const email = form.email.trim();
    if (!email) return null;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(email)) return { valid: false, en: 'Invalid email format', ar: '\u0635\u064A\u063A\u0629 \u0628\u0631\u064A\u062F \u063A\u064A\u0631 \u0635\u062D\u064A\u062D\u0629' };
    const domain = email.split('@')[1]?.toLowerCase();
    const disposable = ['tempmail.com', 'throwaway.email', 'guerrillamail.com', 'mailinator.com', 'yopmail.com'];
    if (disposable.includes(domain)) return { valid: false, en: 'Disposable email not allowed', ar: '\u0628\u0631\u064A\u062F \u0645\u0624\u0642\u062A \u063A\u064A\u0631 \u0645\u0633\u0645\u0648\u062D' };
    return { valid: true, en: 'Valid email', ar: '\u0628\u0631\u064A\u062F \u0635\u062D\u064A\u062D' };
});

/* ---------- Phone validation ---------- */
const phoneValidation = computed(() => {
    const phone = form.phone.trim();
    if (!phone) return null;
    const clean = phone.replace(/[\s\-\(\)]/g, '');
    if (!/^\+?\d{7,15}$/.test(clean)) return { valid: false, en: 'Invalid phone format', ar: '\u0635\u064A\u063A\u0629 \u0647\u0627\u062A\u0641 \u063A\u064A\u0631 \u0635\u062D\u064A\u062D\u0629' };
    if (clean.startsWith('+971') && clean.length !== 13) return { valid: false, en: 'UAE number should be +971XXXXXXXXX', ar: '\u0631\u0642\u0645 \u0627\u0644\u0625\u0645\u0627\u0631\u0627\u062A \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 +971XXXXXXXXX' };
    if (clean.startsWith('+966') && clean.length !== 13) return { valid: false, en: 'KSA number should be +966XXXXXXXXX', ar: '\u0631\u0642\u0645 \u0627\u0644\u0633\u0639\u0648\u062F\u064A\u0629 \u064A\u062C\u0628 \u0623\u0646 \u064A\u0643\u0648\u0646 +966XXXXXXXXX' };
    return { valid: true, en: 'Valid phone number', ar: '\u0631\u0642\u0645 \u0647\u0627\u062A\u0641 \u0635\u062D\u064A\u062D' };
});
</script>

<template>
<SecretaryLayout :title="isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Edit Lead') : (isRtl ? '\u0639\u0645\u064A\u0644 \u062C\u062F\u064A\u062F' : 'New Lead')">
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-teal-50/30 to-slate-50 p-4 md:p-6">

    <!-- Breadcrumb -->
    <nav :class="['flex items-center gap-2 text-sm mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-3']"
         :style="{ direction: isRtl ? 'rtl' : 'ltr' }">
        <Link href="/secretary/crm" class="text-teal-600 hover:text-teal-700 transition-colors">
            {{ isRtl ? '\u0644\u0648\u062D\u0629 CRM' : 'CRM Dashboard' }}
        </Link>
        <span class="text-slate-300">&rsaquo;</span>
        <Link href="/secretary/crm/leads" class="text-teal-600 hover:text-teal-700 transition-colors">
            {{ isRtl ? '\u0627\u0644\u0639\u0645\u0644\u0627\u0621' : 'Leads' }}
        </Link>
        <span class="text-slate-300">&rsaquo;</span>
        <span class="text-slate-500">{{ isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644' : 'Edit') : (isRtl ? '\u062C\u062F\u064A\u062F' : 'New') }}</span>
    </nav>

    <!-- Hero Header -->
    <div :class="['relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 p-6 md:p-8 mb-8 shadow-xl transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '50ms' }">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full"></div>
            <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white rounded-full"></div>
        </div>
        <div class="relative flex items-center gap-4" :style="{ direction: isRtl ? 'rtl' : 'ltr' }">
            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                <svg v-if="isEdit" class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l2.651 2.651M19.513 7.138L8.768 17.883a2 2 0 01-.87.513l-3.898 1.3 1.3-3.898a2 2 0 01.513-.87L16.558 4.183a1.879 1.879 0 012.955 2.955z"/></svg>
                <svg v-else class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">
                    {{ isEdit ? (isRtl ? '\u062A\u0639\u062F\u064A\u0644 \u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644' : 'Edit Lead Details') : (isRtl ? '\u0625\u0636\u0627\u0641\u0629 \u0639\u0645\u064A\u0644 \u0645\u062D\u062A\u0645\u0644 \u062C\u062F\u064A\u062F' : 'Add New Lead') }}
                </h1>
                <p class="text-teal-100 mt-1 text-sm">
                    {{ isEdit ? (isRtl ? '\u062A\u062D\u062F\u064A\u062B \u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644' : 'Update the lead information below') : (isRtl ? '\u0623\u062F\u062E\u0644 \u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644 \u0627\u0644\u062C\u062F\u064A\u062F' : 'Fill in the new lead details below') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Form Progress Indicator -->
    <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4']"
         :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '80ms', direction: isRtl ? 'rtl' : 'ltr' }">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="text-sm font-semibold text-slate-700">{{ isRtl ? '\u0627\u0643\u062A\u0645\u0627\u0644 \u0627\u0644\u0646\u0645\u0648\u0630\u062C' : 'Form Completion' }}</span>
            </div>
            <span :class="['text-sm font-bold tabular-nums', formProgress >= 75 ? 'text-emerald-600' : formProgress >= 50 ? 'text-teal-600' : 'text-slate-400']">{{ formProgress }}%</span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
            <div class="h-full rounded-full transition-all duration-700 ease-out"
                 :style="{ width: formProgress + '%', background: formProgress >= 75 ? 'linear-gradient(90deg, #10b981, #059669)' : formProgress >= 50 ? 'linear-gradient(90deg, #0d9488, #14b8a6)' : 'linear-gradient(90deg, #94a3b8, #cbd5e1)' }"></div>
        </div>
        <div class="flex items-center gap-1">
            <div v-for="(section, idx) in formSections" :key="section.key"
                 class="flex items-center gap-1">
                <div :class="['flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium transition-all duration-300',
                     section.check() ? 'bg-teal-50 text-teal-700' : 'bg-slate-50 text-slate-400']">
                    <svg v-if="section.check()" class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <div v-else class="w-3.5 h-3.5 rounded-full border-2 border-slate-300"></div>
                    {{ isRtl ? section.ar : section.en }}
                </div>
                <svg v-if="idx < formSections.length - 1" class="w-3 h-3 text-slate-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="isRtl ? 'M15 19l-7-7 7-7' : 'M9 5l7 7-7 7'"/></svg>
            </div>
        </div>
    </div>

    <form @submit.prevent="submit" :style="{ direction: isRtl ? 'rtl' : 'ltr' }">

        <!-- Section 1: Contact Info -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0627\u062A\u0635\u0627\u0644' : 'Contact Information' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0627\u0645\u0644' : 'Full Name' }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="form.full_name" type="text"
                           :class="['w-full rounded-xl border px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none', form.errors.full_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300']"
                           :placeholder="isRtl ? '\u0623\u062F\u062E\u0644 \u0627\u0633\u0645 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Enter client name'" />
                    <p v-if="form.errors.full_name" class="mt-1 text-xs text-red-500">{{ form.errors.full_name }}</p>
                </div>
                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062A\u0641' : 'Phone Number' }} <span class="text-red-500">*</span>
                    </label>
                    <input v-model="form.phone" type="tel" dir="ltr" @blur="checkDuplicate"
                           :class="['w-full rounded-xl border px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none', form.errors.phone ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300']"
                           placeholder="+971 XX XXX XXXX" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                    <!-- Phone Validation Hint -->
                    <div v-if="phoneValidation && !form.errors.phone && !checkingDuplicate" class="mt-1.5 flex items-center gap-1.5">
                        <svg v-if="phoneValidation.valid" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg v-else class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <span :class="['text-xs', phoneValidation.valid ? 'text-emerald-600' : 'text-amber-600']">{{ isRtl ? phoneValidation.ar : phoneValidation.en }}</span>
                    </div>
                    <div v-if="checkingDuplicate" class="mt-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-400 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span class="text-xs text-slate-400">{{ isRtl ? '\u062C\u0627\u0631\u064A \u0627\u0644\u062A\u062D\u0642\u0642...' : 'Checking...' }}</span>
                    </div>
                    <!-- Duplicate Warning -->
                    <div v-if="duplicateWarning" class="mt-2 bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <div class="text-sm">
                            <p class="font-medium text-amber-800">{{ isRtl ? 'تحذير: هذا الرقم موجود مسبقاً!' : 'Warning: This phone already exists!' }}</p>
                            <p class="text-amber-600 mt-0.5">{{ duplicateWarning.full_name }} - {{ duplicateWarning.status }}</p>
                            <Link v-if="duplicateWarning.is_mine" :href="`/secretary/crm/leads/${duplicateWarning.id}`" class="text-teal-600 hover:underline text-xs mt-1 inline-block">
                                {{ isRtl ? 'عرض العميل' : 'View Lead' }}
                            </Link>
                        </div>
                    </div>
                </div>
                <!-- Phone 2 -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0631\u0642\u0645 \u0647\u0627\u062A\u0641 \u0628\u062F\u064A\u0644' : 'Alternative Phone' }}
                    </label>
                    <input v-model="form.phone2" type="tel" dir="ltr"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                           placeholder="+971 XX XXX XXXX" />
                    <p v-if="form.errors.phone2" class="mt-1 text-xs text-red-500">{{ form.errors.phone2 }}</p>
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">
                        {{ isRtl ? '\u0627\u0644\u0628\u0631\u064A\u062F \u0627\u0644\u0625\u0644\u0643\u062A\u0631\u0648\u0646\u064A' : 'Email' }}
                    </label>
                    <div class="relative">
                        <input v-model="form.email" type="email" dir="ltr"
                               :class="['w-full rounded-xl border bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 outline-none',
                                   emailValidation && !emailValidation.valid
                                       ? 'border-red-300 focus:ring-red-400/40 focus:border-red-400'
                                       : emailValidation && emailValidation.valid
                                           ? 'border-emerald-300 focus:ring-emerald-400/40 focus:border-emerald-400'
                                           : 'border-slate-200 focus:ring-teal-400/40 focus:border-teal-400']"
                               placeholder="email@example.com" />
                        <!-- Validation icon -->
                        <div v-if="emailValidation" :class="['absolute top-1/2 -translate-y-1/2', isRtl ? 'left-3' : 'right-3']">
                            <svg v-if="emailValidation.valid" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                    </div>
                    <p v-if="emailValidation && !emailValidation.valid" class="mt-1 text-xs text-red-500">{{ isRtl ? emailValidation.ar : emailValidation.en }}</p>
                    <p v-else-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Section 2: Personal Info -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0627\u0644\u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0634\u062E\u0635\u064A\u0629' : 'Personal Information' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Gender -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u062C\u0646\u0633' : 'Gender' }}</label>
                    <div class="flex gap-3">
                        <button type="button" @click="form.gender = 'male'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'male' ? 'bg-teal-50 border-teal-400 text-teal-700 ring-2 ring-teal-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            {{ isRtl ? '\u0630\u0643\u0631' : 'Male' }}
                        </button>
                        <button type="button" @click="form.gender = 'female'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'female' ? 'bg-pink-50 border-pink-400 text-pink-700 ring-2 ring-pink-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            {{ isRtl ? '\u0623\u0646\u062B\u0649' : 'Female' }}
                        </button>
                    </div>
                </div>
                <!-- Date of Birth -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u062A\u0627\u0631\u064A\u062E \u0627\u0644\u0645\u064A\u0644\u0627\u062F' : 'Date of Birth' }}</label>
                    <input v-model="form.date_of_birth" type="date" dir="ltr"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none" />
                </div>
                <!-- City -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u0645\u062F\u064A\u0646\u0629' : 'City' }}</label>
                    <input v-model="form.city" type="text"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                           :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: \u0623\u0628\u0648\u0638\u0628\u064A' : 'e.g. Abu Dhabi'" />
                </div>
                <!-- Nationality -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u062C\u0646\u0633\u064A\u0629' : 'Nationality' }}</label>
                    <input v-model="form.nationality" type="text"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                           :placeholder="isRtl ? '\u0645\u062B\u0627\u0644: \u0625\u0645\u0627\u0631\u0627\u062A\u064A' : 'e.g. Emirati'" />
                </div>
            </div>
        </div>

        <!-- Section 3: Lead Details -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '300ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u062A\u0641\u0627\u0635\u064A\u0644 \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644' : 'Lead Details' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Source -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0645\u0635\u062F\u0631 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Lead Source' }}</label>
                    <select v-model="form.lead_source_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none">
                        <option value="">{{ isRtl ? '-- \u0627\u062E\u062A\u0631 \u0627\u0644\u0645\u0635\u062F\u0631 --' : '-- Select Source --' }}</option>
                        <option v-for="s in sources" :key="s.id" :value="s.id">
                            {{ isRtl ? s.name_ar : s.name_en }}
                        </option>
                    </select>
                </div>
                <!-- Campaign -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u062D\u0645\u0644\u0629' : 'Campaign' }}</label>
                    <select v-model="form.campaign_id"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none">
                        <option value="">{{ isRtl ? '-- \u0627\u062E\u062A\u0631 \u0627\u0644\u062D\u0645\u0644\u0629 --' : '-- Select Campaign --' }}</option>
                        <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Priority -->
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-600 mb-2">{{ isRtl ? '\u0627\u0644\u0623\u0648\u0644\u0648\u064A\u0629' : 'Priority' }} <span class="text-red-500">*</span></label>
                <div class="flex gap-3">
                    <button v-for="p in priorityOptions" :key="p.value" type="button"
                            @click="form.priority = p.value"
                            :class="['flex-1 py-3 px-4 rounded-xl border-2 text-sm font-semibold transition-all duration-300', form.priority === p.value ? p.color + ' ring-2 shadow-sm scale-[1.02]' : 'border-slate-200 text-slate-400 hover:border-slate-300']">
                        <svg v-if="p.icon === 'hot'" class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                        <svg v-else-if="p.icon === 'warm'" class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg v-else class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span class="block mt-0.5">{{ isRtl ? p.label.ar : p.label.en }}</span>
                    </button>
                </div>
            </div>

            <!-- Interested Services -->
            <div v-if="services && services.length > 0">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-semibold text-slate-600">{{ isRtl ? '\u0627\u0644\u062E\u062F\u0645\u0627\u062A \u0627\u0644\u0645\u0647\u062A\u0645 \u0628\u0647\u0627' : 'Interested Services' }}</label>
                    <span v-if="form.interested_services.length > 0" class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-full">
                        {{ form.interested_services.length }} {{ isRtl ? '\u0645\u062E\u062A\u0627\u0631\u0629' : 'selected' }}
                    </span>
                </div>
                <!-- Search filter (shows when 6+ services) -->
                <div v-if="services.length >= 6" class="relative mb-2.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute top-1/2 -translate-y-1/2 pointer-events-none" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
                    <input v-model="serviceSearch" type="text"
                        :placeholder="isRtl ? '\u0628\u062D\u062B \u0641\u064A \u0627\u0644\u062E\u062F\u0645\u0627\u062A...' : 'Search services...'"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 text-xs py-2.5 transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                        :class="isRtl ? 'pr-9 pl-3' : 'pl-9 pr-3'" />
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2" :class="services.length >= 12 ? 'max-h-48 overflow-y-auto rounded-xl' : ''">
                    <button v-for="svc in filteredServices" :key="svc.id" type="button"
                            @click="toggleService(svc.id)"
                            :class="['py-2.5 px-3 rounded-xl border text-xs font-medium transition-all duration-200', form.interested_services.includes(svc.id) ? 'bg-teal-50 border-teal-400 text-teal-700 ring-1 ring-teal-300' : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50']">
                        <svg v-if="form.interested_services.includes(svc.id)" class="inline-block w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ isRtl ? svc.name_ar : svc.name_en }}
                    </button>
                </div>
                <p v-if="serviceSearch && filteredServices.length === 0" class="text-xs text-slate-400 text-center py-3">
                    {{ isRtl ? '\u0644\u0627 \u062A\u0648\u062C\u062F \u0646\u062A\u0627\u0626\u062C' : 'No matching services' }}
                </p>
            </div>
        </div>

        <!-- Section 4: Notes -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0645\u0644\u0627\u062D\u0638\u0627\u062A' : 'Notes' }}</h2>
            </div>
            <textarea v-model="form.notes" rows="4"
                      class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none resize-none"
                      :placeholder="isRtl ? '\u0623\u0636\u0641 \u0645\u0644\u0627\u062D\u0638\u0627\u062A \u062D\u0648\u0644 \u0627\u0644\u0639\u0645\u064A\u0644 \u0627\u0644\u0645\u062D\u062A\u0645\u0644...' : 'Add notes about this lead...'"></textarea>
        </div>

        <!-- Action Buttons -->
        <div :class="['flex items-center justify-between bg-white rounded-2xl shadow-sm border border-slate-100 p-5 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '500ms' }">
            <Link href="/secretary/crm/leads"
                  class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-all duration-200">
                {{ isRtl ? '\u0625\u0644\u063A\u0627\u0621' : 'Cancel' }}
            </Link>
            <button type="submit" :disabled="form.processing"
                    :class="['px-8 py-3 rounded-xl text-white text-sm font-semibold shadow-lg transition-all duration-300 flex items-center gap-2', form.processing ? 'bg-teal-400 cursor-not-allowed' : 'bg-gradient-to-r from-teal-600 to-emerald-500 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]']">
                <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span v-if="!form.processing">{{ isEdit ? (isRtl ? '\u062D\u0641\u0638 \u0627\u0644\u062A\u0639\u062F\u064A\u0644\u0627\u062A' : 'Save Changes') : (isRtl ? '\u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0639\u0645\u064A\u0644' : 'Create Lead') }}</span>
                <span v-else>{{ isRtl ? '\u062C\u0627\u0631\u064A \u0627\u0644\u062D\u0641\u0638...' : 'Saving...' }}</span>
            </button>
        </div>

        <!-- Global Error -->
        <div v-if="Object.keys(form.errors).length > 0"
             class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-600">
            <p class="font-semibold mb-1">{{ isRtl ? '\u064A\u0631\u062C\u0649 \u062A\u0635\u062D\u064A\u062D \u0627\u0644\u0623\u062E\u0637\u0627\u0621 \u0627\u0644\u062A\u0627\u0644\u064A\u0629:' : 'Please fix the following errors:' }}</p>
            <ul class="list-disc list-inside space-y-1">
                <li v-for="(err, field) in form.errors" :key="field">{{ err }}</li>
            </ul>
        </div>
    </form>

</div>
</SecretaryLayout>
</template>
