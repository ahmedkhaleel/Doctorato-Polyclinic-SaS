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

function toggleService(id) {
    const idx = form.interested_services.indexOf(id);
    if (idx > -1) {
        form.interested_services.splice(idx, 1);
    } else {
        form.interested_services.push(id);
    }
}

const priorityOptions = [
    { value: 1, label: { en: 'Hot', ar: '\u0633\u0627\u062E\u0646' }, emoji: '\uD83D\uDD25', color: 'bg-red-100 text-red-700 border-red-300 ring-red-400' },
    { value: 2, label: { en: 'Warm', ar: '\u062F\u0627\u0641\u0626' }, emoji: '\u2600\uFE0F', color: 'bg-amber-100 text-amber-700 border-amber-300 ring-amber-400' },
    { value: 3, label: { en: 'Cold', ar: '\u0628\u0627\u0631\u062F' }, emoji: '\u2744\uFE0F', color: 'bg-blue-100 text-blue-700 border-blue-300 ring-blue-400' },
];
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
            <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center text-2xl">
                {{ isEdit ? '\u270F\uFE0F' : '\u2795' }}
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

    <form @submit.prevent="submit" :style="{ direction: isRtl ? 'rtl' : 'ltr' }">

        <!-- Section 1: Contact Info -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '100ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center text-lg">📞</div>
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
                    <input v-model="form.phone" type="tel" dir="ltr"
                           :class="['w-full rounded-xl border px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none', form.errors.phone ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300']"
                           placeholder="+971 XX XXX XXXX" />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
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
                    <input v-model="form.email" type="email" dir="ltr"
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/50 hover:border-slate-300 px-4 py-3 text-sm transition-all duration-200 focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400 outline-none"
                           placeholder="email@example.com" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                </div>
            </div>
        </div>

        <!-- Section 2: Personal Info -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '200ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-lg">👤</div>
                <h2 class="text-lg font-bold text-slate-800">{{ isRtl ? '\u0627\u0644\u0645\u0639\u0644\u0648\u0645\u0627\u062A \u0627\u0644\u0634\u062E\u0635\u064A\u0629' : 'Personal Information' }}</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Gender -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1.5">{{ isRtl ? '\u0627\u0644\u062C\u0646\u0633' : 'Gender' }}</label>
                    <div class="flex gap-3">
                        <button type="button" @click="form.gender = 'male'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'male' ? 'bg-teal-50 border-teal-400 text-teal-700 ring-2 ring-teal-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            🧑 {{ isRtl ? '\u0630\u0643\u0631' : 'Male' }}
                        </button>
                        <button type="button" @click="form.gender = 'female'"
                                :class="['flex-1 py-3 rounded-xl border text-sm font-medium transition-all duration-200', form.gender === 'female' ? 'bg-pink-50 border-pink-400 text-pink-700 ring-2 ring-pink-400/30' : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                            👩 {{ isRtl ? '\u0623\u0646\u062B\u0649' : 'Female' }}
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
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-lg">🎯</div>
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
                        <span class="text-lg">{{ p.emoji }}</span>
                        <span class="block mt-0.5">{{ isRtl ? p.label.ar : p.label.en }}</span>
                    </button>
                </div>
            </div>

            <!-- Interested Services -->
            <div v-if="services && services.length > 0">
                <label class="block text-sm font-semibold text-slate-600 mb-2">{{ isRtl ? '\u0627\u0644\u062E\u062F\u0645\u0627\u062A \u0627\u0644\u0645\u0647\u062A\u0645 \u0628\u0647\u0627' : 'Interested Services' }}</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <button v-for="svc in services" :key="svc.id" type="button"
                            @click="toggleService(svc.id)"
                            :class="['py-2.5 px-3 rounded-xl border text-xs font-medium transition-all duration-200', form.interested_services.includes(svc.id) ? 'bg-teal-50 border-teal-400 text-teal-700 ring-1 ring-teal-300' : 'border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50']">
                        <span v-if="form.interested_services.includes(svc.id)" class="inline-block mr-1">✓</span>
                        {{ isRtl ? svc.name_ar : svc.name_en }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Section 4: Notes -->
        <div :class="['bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6 transition-all duration-700', mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6']"
             :style="{ transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)', transitionDelay: '400ms' }">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 bg-sky-100 rounded-xl flex items-center justify-center text-lg">📝</div>
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
                <span v-if="!form.processing">{{ isEdit ? (isRtl ? '💾 \u062D\u0641\u0638 \u0627\u0644\u062A\u0639\u062F\u064A\u0644\u0627\u062A' : '💾 Save Changes') : (isRtl ? '✨ \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0639\u0645\u064A\u0644' : '✨ Create Lead') }}</span>
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
