<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

/* ---- mount animation stagger ---- */
const mounted = ref(false);
const sectionVisible = reactive({ header: false, steps: false, details: false, content: false, sidebar: false, actions: false });
onMounted(() => {
    setTimeout(() => { mounted.value = true; sectionVisible.header = true; }, 50);
    setTimeout(() => { sectionVisible.steps = true; }, 150);
    setTimeout(() => { sectionVisible.details = true; }, 250);
    setTimeout(() => { sectionVisible.content = true; }, 400);
    setTimeout(() => { sectionVisible.sidebar = true; }, 350);
    setTimeout(() => { sectionVisible.actions = true; }, 500);
});

/* ---- wizard step tracking (visual only) ---- */
const currentStep = ref(1);
const steps = [
    { id: 1, label: 'Template Details', key: 'details' },
    { id: 2, label: 'Content', key: 'content' },
    { id: 3, label: 'Review', key: 'review' },
];
/* Auto-detect step based on form state */
watch(() => [form?.name, form?.channel], () => {
    if (form.name && form.channel) currentStep.value = 2;
}, { deep: true });

/* ---- active language tab ---- */
const activeTab = ref('en');
const tabIndicatorStyle = computed(() => ({
    transform: activeTab.value === 'en' ? 'translateX(0%)' : 'translateX(100%)',
    width: '50%',
}));

/* ---- form ---- */
const form = useForm({
    name: '',
    channel: 'whatsapp',
    category: 'follow_up',
    subject: '',
    body_en: '',
    body_ar: '',
    variables: [],
});

const availableVariables = [
    { key: '{name}', icon: 'user' },
    { key: '{phone}', icon: 'phone' },
    { key: '{email}', icon: 'mail' },
    { key: '{service}', icon: 'service' },
    { key: '{date}', icon: 'calendar' },
    { key: '{time}', icon: 'clock' },
    { key: '{clinic_name}', icon: 'building' },
    { key: '{clinic_phone}', icon: 'phone-office' },
];

/* ---- character counts ---- */
const charCountEn = computed(() => form.body_en.length);
const charCountAr = computed(() => form.body_ar.length);
const maxChars = computed(() => form.channel === 'sms' ? 160 : form.channel === 'whatsapp' ? 4096 : 10000);

/* ---- channel card animation state ---- */
const channelBounce = ref('');
function selectChannel(val) {
    channelBounce.value = val;
    form.channel = val;
    setTimeout(() => { channelBounce.value = ''; }, 400);
}

const channelOptions = [
    { value: 'whatsapp', label: 'WhatsApp', desc: 'Rich messaging with media', color: '#25D366', bgClass: 'bg-emerald-50', borderClass: 'border-emerald-200', ringClass: 'ring-emerald-300', textClass: 'text-emerald-600' },
    { value: 'sms', label: 'SMS', desc: 'Direct text messages', color: '#3B82F6', bgClass: 'bg-blue-50', borderClass: 'border-blue-200', ringClass: 'ring-blue-300', textClass: 'text-blue-600' },
    { value: 'email', label: 'Email', desc: 'Professional campaigns', color: '#8B5CF6', bgClass: 'bg-purple-50', borderClass: 'border-purple-200', ringClass: 'ring-purple-300', textClass: 'text-purple-600' },
];

/* ---- floating label focus state ---- */
const nameInputFocused = ref(false);
const subjectInputFocused = ref(false);

/* ---- variable helpers ---- */
function toggleVariable(v) {
    const idx = form.variables.indexOf(v);
    if (idx >= 0) form.variables.splice(idx, 1);
    else form.variables.push(v);
}

function insertVariable(v, field) {
    form[field] += ` ${v}`;
}

/* ---- preview ---- */
const previewText = computed(() => {
    const raw = activeTab.value === 'en' ? form.body_en : form.body_ar;
    if (!raw) return '';
    let text = raw;
    text = text.replace(/\{name\}/g, 'Ahmed');
    text = text.replace(/\{phone\}/g, '+966 5XX XXX XXX');
    text = text.replace(/\{email\}/g, 'patient@example.com');
    text = text.replace(/\{service\}/g, 'Skin Consultation');
    text = text.replace(/\{date\}/g, '2026-04-15');
    text = text.replace(/\{time\}/g, '10:30 AM');
    text = text.replace(/\{clinic_name\}/g, 'Aura Derma Clinic');
    text = text.replace(/\{clinic_phone\}/g, '+966 XX XXX XXXX');
    return text;
});

const previewVisible = computed(() => {
    return (activeTab.value === 'en' && form.body_en.length > 0) || (activeTab.value === 'ar' && form.body_ar.length > 0);
});

/* ---- submit ---- */
function submit() {
    currentStep.value = 3;
    form.post('/admin/templates');
}

/* ---- variable flip state ---- */
const flippedVar = ref('');
function handleToggleVariable(v) {
    flippedVar.value = v;
    toggleVariable(v);
    setTimeout(() => { flippedVar.value = ''; }, 500);
}
</script>

<template>
    <AdminLayout :title="$t('a_create_template')">
        <div class="min-h-screen">
            <!-- ====== HEADER ====== -->
            <div
                :class="sectionVisible.header ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-6'"
                class="transition-all duration-700 ease-out mb-8"
            >
                <!-- Gradient header band -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 p-6 shadow-xl">
                    <!-- Gold accent lines -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
                    </div>
                    <!-- Decorative circles -->
                    <div class="absolute -top-12 ltr:-right-12 rtl:-left-12 w-40 h-40 rounded-full bg-[#C4A265]/5 blur-2xl"></div>
                    <div class="absolute -bottom-8 ltr:-left-8 rtl:-right-8 w-32 h-32 rounded-full bg-[#C4A265]/5 blur-2xl"></div>

                    <div class="relative flex items-center gap-4">
                        <Link href="/admin/templates"
                            class="group flex items-center justify-center w-10 h-10 rounded-xl bg-white/5 border border-white/10 backdrop-blur-sm hover:bg-[#C4A265]/20 hover:border-[#C4A265]/30 transition-all duration-300">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-[#C4A265] transition-colors duration-300" :class="isRtl ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-white tracking-tight">{{ $t('a_create_template') }}</h1>
                            <p class="text-sm text-gray-400 mt-1">{{ $t('a_configure_template_description') }}</p>
                        </div>
                        <!-- Decorative template icon -->
                        <div class="hidden sm:flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-[#C4A265]/20 to-[#C4A265]/5 border border-[#C4A265]/20">
                            <svg class="w-7 h-7 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== STEP WIZARD INDICATOR ====== -->
            <div
                :class="sectionVisible.steps ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-600 ease-out mb-8"
            >
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <template v-for="(step, idx) in steps" :key="step.id">
                            <div class="flex items-center gap-3" :class="idx > 0 ? 'flex-1 justify-center' : ''">
                                <!-- Connector line before (except first) -->
                                <div v-if="idx > 0" class="hidden sm:block flex-1 h-px transition-all duration-500"
                                    :class="currentStep >= step.id ? 'bg-gradient-to-r from-[#C4A265]/60 to-[#C4A265]' : 'bg-gray-200'">
                                </div>
                                <!-- Step circle -->
                                <div class="flex items-center gap-2.5 shrink-0">
                                    <div class="relative flex items-center justify-center w-9 h-9 rounded-full transition-all duration-500"
                                        :class="currentStep >= step.id
                                            ? 'bg-gradient-to-br from-[#C4A265] to-[#D4B87A] shadow-lg shadow-[#C4A265]/25'
                                            : 'bg-gray-100 border-2 border-gray-200'">
                                        <!-- Active glow ring -->
                                        <div v-if="currentStep === step.id"
                                            class="absolute inset-0 rounded-full bg-[#C4A265]/20 animate-ping opacity-75"></div>
                                        <!-- Check icon for completed -->
                                        <svg v-if="currentStep > step.id" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <!-- Step number -->
                                        <span v-else class="text-xs font-bold" :class="currentStep >= step.id ? 'text-white' : 'text-gray-400'">{{ step.id }}</span>
                                    </div>
                                    <span class="hidden sm:inline text-sm font-semibold transition-colors duration-300"
                                        :class="currentStep >= step.id ? 'text-gray-800' : 'text-gray-400'">{{ step.label }}</span>
                                </div>
                                <!-- Connector line after (except last) -->
                                <div v-if="idx < steps.length - 1" class="hidden sm:block flex-1 h-px transition-all duration-500"
                                    :class="currentStep > step.id ? 'bg-gradient-to-r from-[#C4A265] to-[#C4A265]/60' : 'bg-gray-200'">
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ====== MAIN FORM GRID ====== -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- ====== LEFT COLUMN (Details + Content) ====== -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- ---- TEMPLATE DETAILS CARD ---- -->
                    <div
                        :class="sectionVisible.details ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        class="transition-all duration-700 ease-out relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    >
                        <!-- Gold gradient top border -->
                        <div class="h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>

                        <div class="p-8">
                            <div class="flex items-center gap-3 mb-7">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#C4A265]/10">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_template_details') }}</h3>
                            </div>

                            <div class="space-y-6">
                                <!-- Name - floating label -->
                                <div class="relative">
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        id="template-name"
                                        @focus="nameInputFocused = true"
                                        @blur="nameInputFocused = false"
                                        class="peer w-full px-4 pt-6 pb-2 text-sm bg-gray-50/60 border-2 rounded-xl transition-all duration-300 placeholder-transparent focus:bg-white focus:outline-none"
                                        :class="nameInputFocused || form.name
                                            ? 'border-[#C4A265]/40 ring-4 ring-[#C4A265]/10 shadow-sm'
                                            : 'border-gray-200 hover:border-gray-300'"
                                        placeholder="Template name"
                                    />
                                    <label for="template-name"
                                        class="absolute transition-all duration-300 pointer-events-none"
                                        :class="nameInputFocused || form.name
                                            ? 'text-[10px] font-bold text-[#C4A265] uppercase tracking-wider top-2 ltr:left-4 rtl:right-4'
                                            : 'text-sm text-gray-400 top-4 ltr:left-4 rtl:right-4'"
                                    >
                                        {{ $t('a_name') }} <span class="text-red-400">*</span>
                                    </label>
                                    <p v-if="form.errors.name" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.name }}</p>
                                </div>

                                <!-- Channel selector cards -->
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-3 ltr:ml-1 rtl:mr-1">
                                        {{ $t('a_channel') }} <span class="text-red-400">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <button
                                            v-for="ch in channelOptions" :key="ch.value"
                                            type="button"
                                            @click="selectChannel(ch.value)"
                                            class="group relative rounded-xl border-2 p-4 transition-all duration-300 ltr:text-left rtl:text-right overflow-hidden"
                                            :class="[
                                                form.channel === ch.value
                                                    ? `${ch.bgClass} ${ch.borderClass} ring-4 ${ch.ringClass} shadow-lg scale-[1.02]`
                                                    : 'bg-gray-50/60 border-gray-200 hover:border-gray-300 hover:bg-gray-50 hover:shadow-md',
                                                channelBounce === ch.value ? 'animate-bounce-once' : ''
                                            ]"
                                        >
                                            <!-- Glow effect when selected -->
                                            <div v-if="form.channel === ch.value"
                                                class="absolute inset-0 opacity-20 blur-xl"
                                                :style="{ backgroundColor: ch.color }"></div>

                                            <div class="relative">
                                                <!-- Channel icon -->
                                                <div class="mb-3 flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-300"
                                                    :class="form.channel === ch.value ? `${ch.bgClass} shadow-sm` : 'bg-gray-100 group-hover:bg-gray-200'">
                                                    <!-- WhatsApp icon -->
                                                    <svg v-if="ch.value === 'whatsapp'" class="w-5 h-5 transition-colors duration-300" :class="form.channel === ch.value ? ch.textClass : 'text-gray-400 group-hover:text-gray-500'" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                    </svg>
                                                    <!-- SMS icon -->
                                                    <svg v-if="ch.value === 'sms'" class="w-5 h-5 transition-colors duration-300" :class="form.channel === ch.value ? ch.textClass : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                    </svg>
                                                    <!-- Email icon -->
                                                    <svg v-if="ch.value === 'email'" class="w-5 h-5 transition-colors duration-300" :class="form.channel === ch.value ? ch.textClass : 'text-gray-400 group-hover:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <p class="text-sm font-bold transition-colors duration-300"
                                                    :class="form.channel === ch.value ? ch.textClass : 'text-gray-700 group-hover:text-gray-800'">
                                                    {{ ch.label }}
                                                </p>
                                                <p class="text-[10px] mt-0.5 transition-colors duration-300"
                                                    :class="form.channel === ch.value ? ch.textClass + '/70' : 'text-gray-400'">
                                                    {{ ch.desc }}
                                                </p>
                                                <!-- Selected indicator dot -->
                                                <div v-if="form.channel === ch.value"
                                                    class="absolute top-3 ltr:right-3 rtl:left-3 w-2.5 h-2.5 rounded-full shadow-sm"
                                                    :style="{ backgroundColor: ch.color }">
                                                    <div class="absolute inset-0 rounded-full animate-ping opacity-40"
                                                        :style="{ backgroundColor: ch.color }"></div>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                    <p v-if="form.errors.channel" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.channel }}</p>
                                </div>

                                <!-- Category dropdown -->
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 ltr:ml-1 rtl:mr-1">
                                        {{ $t('a_category') }} <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <select v-model="form.category"
                                            class="w-full appearance-none px-4 py-3.5 text-sm bg-gray-50/60 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-[#C4A265]/10 focus:border-[#C4A265]/40 focus:bg-white transition-all duration-300 hover:border-gray-300 cursor-pointer">
                                            <option value="welcome">Welcome</option>
                                            <option value="follow_up">{{ $t('a_follow_up') }}</option>
                                            <option value="appointment_reminder">Appointment Reminder</option>
                                            <option value="promotion">Promotion</option>
                                            <option value="re_engagement">Re-engagement</option>
                                            <option value="thank_you">Thank You</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                        <div class="absolute inset-y-0 ltr:right-3 rtl:left-3 flex items-center pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.category" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.category }}</p>
                                </div>

                                <!-- Subject (email only) with slide animation -->
                                <Transition
                                    enter-active-class="transition-all duration-500 ease-out"
                                    enter-from-class="opacity-0 -translate-y-4 max-h-0"
                                    enter-to-class="opacity-100 translate-y-0 max-h-32"
                                    leave-active-class="transition-all duration-300 ease-in"
                                    leave-from-class="opacity-100 translate-y-0 max-h-32"
                                    leave-to-class="opacity-0 -translate-y-4 max-h-0"
                                >
                                    <div v-if="form.channel === 'email'" class="relative overflow-hidden">
                                        <input
                                            v-model="form.subject"
                                            type="text"
                                            id="template-subject"
                                            @focus="subjectInputFocused = true"
                                            @blur="subjectInputFocused = false"
                                            class="peer w-full px-4 pt-6 pb-2 text-sm bg-gray-50/60 border-2 rounded-xl transition-all duration-300 placeholder-transparent focus:bg-white focus:outline-none"
                                            :class="subjectInputFocused || form.subject
                                                ? 'border-[#C4A265]/40 ring-4 ring-[#C4A265]/10 shadow-sm'
                                                : 'border-gray-200 hover:border-gray-300'"
                                            placeholder="Email subject line"
                                        />
                                        <label for="template-subject"
                                            class="absolute transition-all duration-300 pointer-events-none"
                                            :class="subjectInputFocused || form.subject
                                                ? 'text-[10px] font-bold text-[#C4A265] uppercase tracking-wider top-2 ltr:left-4 rtl:right-4'
                                                : 'text-sm text-gray-400 top-4 ltr:left-4 rtl:right-4'"
                                        >
                                            {{ $t('a_subject') }}
                                        </label>
                                        <p v-if="form.errors.subject" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.subject }}</p>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>

                    <!-- ---- MESSAGE CONTENT CARD ---- -->
                    <div
                        :class="sectionVisible.content ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        class="transition-all duration-700 ease-out relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    >
                        <!-- Gold gradient top border -->
                        <div class="h-1 bg-gradient-to-r from-[#D4B87A] via-[#C4A265] to-[#D4B87A]"></div>

                        <div class="p-8">
                            <div class="flex items-center gap-3 mb-7">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#C4A265]/10">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_message_content') }}</h3>
                            </div>

                            <!-- Language Tab Switcher with sliding indicator -->
                            <div class="relative bg-gray-100 rounded-xl p-1 mb-6">
                                <!-- Sliding indicator -->
                                <div
                                    class="absolute top-1 bottom-1 bg-white rounded-lg shadow-sm transition-all duration-400 ease-out"
                                    :style="tabIndicatorStyle"
                                ></div>
                                <div class="relative flex">
                                    <button type="button" @click="activeTab = 'en'"
                                        class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-colors duration-300 flex items-center justify-center gap-2 z-10"
                                        :class="activeTab === 'en' ? 'text-gray-800' : 'text-gray-500 hover:text-gray-600'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                        </svg>
                                        {{ $t('a_english') }}
                                    </button>
                                    <button type="button" @click="activeTab = 'ar'"
                                        class="flex-1 py-2.5 text-xs font-bold rounded-lg transition-colors duration-300 flex items-center justify-center gap-2 z-10"
                                        :class="activeTab === 'ar' ? 'text-gray-800' : 'text-gray-500 hover:text-gray-600'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                        </svg>
                                        {{ $t('a_arabic') }}
                                    </button>
                                </div>
                            </div>

                            <!-- English Body -->
                            <div v-show="activeTab === 'en'">
                                <!-- Quick-insert variable buttons -->
                                <div class="flex items-center gap-2 mb-3 flex-wrap">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider shrink-0">Insert:</span>
                                    <TransitionGroup
                                        enter-active-class="transition-all duration-300"
                                        enter-from-class="opacity-0 scale-75"
                                        enter-to-class="opacity-100 scale-100"
                                    >
                                        <button v-for="v in form.variables" :key="v" type="button"
                                            @click="insertVariable(v, 'body_en')"
                                            class="px-2.5 py-1 text-[10px] font-mono bg-gradient-to-r from-[#C4A265]/10 to-[#D4B87A]/10 text-[#C4A265] rounded-lg border border-[#C4A265]/20 hover:from-[#C4A265]/20 hover:to-[#D4B87A]/20 hover:shadow-sm transition-all duration-200 hover:scale-105 active:scale-95">
                                            {{ v }}
                                        </button>
                                    </TransitionGroup>
                                    <span v-if="form.variables.length === 0" class="text-[10px] text-gray-300 italic">Select variables from the sidebar</span>
                                </div>

                                <div class="relative">
                                    <textarea v-model="form.body_en" rows="7"
                                        class="w-full px-4 py-3.5 text-sm leading-relaxed bg-gray-50/60 border-2 border-gray-200 rounded-xl resize-none focus:ring-4 focus:ring-[#C4A265]/10 focus:border-[#C4A265]/40 focus:bg-white transition-all duration-300 placeholder-gray-300 hover:border-gray-300"
                                        :placeholder="$t('a_enter_message_en')"></textarea>
                                    <!-- Character count -->
                                    <div class="absolute bottom-3 ltr:right-3 rtl:left-3 flex items-center gap-1.5">
                                        <div class="h-1.5 w-16 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="charCountEn > maxChars ? 'bg-red-400' : charCountEn > maxChars * 0.8 ? 'bg-amber-400' : 'bg-[#C4A265]'"
                                                :style="{ width: Math.min((charCountEn / maxChars) * 100, 100) + '%' }">
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-mono tabular-nums"
                                            :class="charCountEn > maxChars ? 'text-red-500 font-bold' : 'text-gray-400'">
                                            {{ charCountEn }}/{{ maxChars }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="form.errors.body_en" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.body_en }}</p>
                            </div>

                            <!-- Arabic Body -->
                            <div v-show="activeTab === 'ar'">
                                <!-- Quick-insert variable buttons -->
                                <div class="flex items-center gap-2 mb-3 flex-wrap">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider shrink-0">Insert:</span>
                                    <TransitionGroup
                                        enter-active-class="transition-all duration-300"
                                        enter-from-class="opacity-0 scale-75"
                                        enter-to-class="opacity-100 scale-100"
                                    >
                                        <button v-for="v in form.variables" :key="v" type="button"
                                            @click="insertVariable(v, 'body_ar')"
                                            class="px-2.5 py-1 text-[10px] font-mono bg-gradient-to-r from-[#C4A265]/10 to-[#D4B87A]/10 text-[#C4A265] rounded-lg border border-[#C4A265]/20 hover:from-[#C4A265]/20 hover:to-[#D4B87A]/20 hover:shadow-sm transition-all duration-200 hover:scale-105 active:scale-95">
                                            {{ v }}
                                        </button>
                                    </TransitionGroup>
                                    <span v-if="form.variables.length === 0" class="text-[10px] text-gray-300 italic">Select variables from the sidebar</span>
                                </div>

                                <div class="relative">
                                    <textarea v-model="form.body_ar" rows="7" dir="rtl"
                                        class="w-full px-4 py-3.5 text-sm leading-relaxed bg-gray-50/60 border-2 border-gray-200 rounded-xl resize-none focus:ring-4 focus:ring-[#C4A265]/10 focus:border-[#C4A265]/40 focus:bg-white transition-all duration-300 placeholder-gray-300 hover:border-gray-300"
                                        placeholder="..."></textarea>
                                    <!-- Character count -->
                                    <div class="absolute bottom-3 ltr:right-3 rtl:left-3 flex items-center gap-1.5">
                                        <div class="h-1.5 w-16 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                :class="charCountAr > maxChars ? 'bg-red-400' : charCountAr > maxChars * 0.8 ? 'bg-amber-400' : 'bg-[#C4A265]'"
                                                :style="{ width: Math.min((charCountAr / maxChars) * 100, 100) + '%' }">
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-mono tabular-nums"
                                            :class="charCountAr > maxChars ? 'text-red-500 font-bold' : 'text-gray-400'">
                                            {{ charCountAr }}/{{ maxChars }}
                                        </span>
                                    </div>
                                </div>
                                <p v-if="form.errors.body_ar" class="text-xs text-red-500 mt-2 ltr:ml-1 rtl:mr-1">{{ form.errors.body_ar }}</p>
                            </div>

                            <!-- ---- LIVE PREVIEW MOCKUP ---- -->
                            <Transition
                                enter-active-class="transition-all duration-500 ease-out"
                                enter-from-class="opacity-0 translate-y-4"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-300 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div v-if="previewVisible" class="mt-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Live Preview</span>
                                    </div>

                                    <!-- WhatsApp preview -->
                                    <div v-if="form.channel === 'whatsapp'" class="bg-[#E5DDD5] rounded-2xl p-5 border border-gray-200/50">
                                        <div class="bg-[#075E54] rounded-t-xl px-4 py-2.5 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-300/30 flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white/80" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                            </div>
                                            <span class="text-white text-sm font-medium">Patient</span>
                                        </div>
                                        <div class="px-3 py-4">
                                            <div class="max-w-[85%] ltr:ml-auto rtl:mr-auto">
                                                <div class="bg-[#DCF8C6] rounded-xl rounded-tr-sm px-4 py-2.5 shadow-sm">
                                                    <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                                    <div class="flex items-center justify-end gap-1 mt-1">
                                                        <span class="text-[10px] text-gray-500">10:30 AM</span>
                                                        <svg class="w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 24 16"><path d="M2 8l5 5L18 2" stroke="currentColor" stroke-width="2" fill="none"/><path d="M7 8l5 5L23 2" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SMS preview -->
                                    <div v-else-if="form.channel === 'sms'" class="bg-gray-50 rounded-2xl p-5 border border-gray-200/50">
                                        <div class="bg-gray-800 rounded-t-xl px-4 py-2.5 flex items-center justify-center">
                                            <span class="text-white text-xs font-medium">Messages</span>
                                        </div>
                                        <div class="bg-white px-4 py-5 rounded-b-xl">
                                            <div class="max-w-[80%] ltr:ml-auto rtl:mr-auto">
                                                <div class="bg-blue-500 rounded-2xl rounded-br-sm px-4 py-3 shadow-sm">
                                                    <p class="text-sm text-white leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                                </div>
                                                <p class="text-[10px] text-gray-400 mt-1.5 ltr:text-right rtl:text-left">Delivered</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email preview -->
                                    <div v-else class="bg-gray-50 rounded-2xl border border-gray-200/50 overflow-hidden">
                                        <div class="bg-white border-b border-gray-100 px-5 py-3 flex items-center gap-3">
                                            <div class="flex gap-1.5">
                                                <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
                                                <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-400 flex-1 text-center">New Message</span>
                                        </div>
                                        <div class="p-5 bg-white">
                                            <div class="space-y-2 mb-4 pb-4 border-b border-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-12 shrink-0">To:</span>
                                                    <span class="text-xs text-gray-600">patient@example.com</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-bold text-gray-400 w-12 shrink-0">Subject:</span>
                                                    <span class="text-xs font-semibold text-gray-800">{{ form.subject || 'No subject' }}</span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">{{ previewText }}</p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>

                <!-- ====== RIGHT SIDEBAR (Variables + Actions) ====== -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- ---- VARIABLES PANEL ---- -->
                    <div
                        :class="sectionVisible.sidebar ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        class="transition-all duration-700 ease-out relative bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
                    >
                        <!-- Gold gradient top border -->
                        <div class="h-1 bg-gradient-to-r from-[#C4A265]/60 via-[#C4A265] to-[#C4A265]/60"></div>

                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#C4A265]/10">
                                    <svg class="w-4 h-4 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">{{ $t('a_available_variables') }}</h3>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Click to toggle, then insert into message</p>
                                </div>
                            </div>

                            <!-- Variable grid -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <button
                                    v-for="v in availableVariables" :key="v.key"
                                    type="button"
                                    @click="handleToggleVariable(v.key)"
                                    class="group relative px-3 py-3 text-xs font-mono rounded-xl transition-all duration-300 flex items-center gap-2 overflow-hidden"
                                    :class="[
                                        form.variables.includes(v.key)
                                            ? 'bg-gradient-to-br from-[#C4A265] to-[#D4B87A] text-white shadow-lg shadow-[#C4A265]/25 scale-[1.02]'
                                            : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 hover:shadow-sm',
                                        flippedVar === v.key ? 'animate-flip' : ''
                                    ]"
                                >
                                    <!-- Glow when selected -->
                                    <div v-if="form.variables.includes(v.key)"
                                        class="absolute inset-0 bg-[#C4A265]/20 blur-xl"></div>

                                    <div class="relative flex items-center gap-2 w-full">
                                        <!-- Icon -->
                                        <div class="w-5 h-5 rounded-md flex items-center justify-center shrink-0 transition-colors duration-300"
                                            :class="form.variables.includes(v.key) ? 'bg-white/20' : 'bg-gray-200/60'">
                                            <!-- user -->
                                            <svg v-if="v.icon === 'user'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <!-- phone -->
                                            <svg v-else-if="v.icon === 'phone'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <!-- mail -->
                                            <svg v-else-if="v.icon === 'mail'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <!-- service -->
                                            <svg v-else-if="v.icon === 'service'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                            <!-- calendar -->
                                            <svg v-else-if="v.icon === 'calendar'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <!-- clock -->
                                            <svg v-else-if="v.icon === 'clock'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <!-- building -->
                                            <svg v-else-if="v.icon === 'building'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            <!-- phone-office -->
                                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>

                                        <span class="truncate text-[11px]">{{ v.key }}</span>

                                        <!-- Check indicator -->
                                        <svg v-if="form.variables.includes(v.key)" class="w-3.5 h-3.5 ltr:ml-auto rtl:mr-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            <!-- Selected variables floating tags -->
                            <Transition
                                enter-active-class="transition-all duration-400 ease-out"
                                enter-from-class="opacity-0 translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                            >
                                <div v-if="form.variables.length > 0" class="mt-5 pt-4 border-t border-gray-100">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5">Selected Variables</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <TransitionGroup
                                            enter-active-class="transition-all duration-300 ease-out"
                                            enter-from-class="opacity-0 scale-50"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition-all duration-200 ease-in"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-50"
                                        >
                                            <span v-for="v in form.variables" :key="v"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-mono bg-gradient-to-r from-[#C4A265]/10 to-[#D4B87A]/10 text-[#C4A265] rounded-lg border border-[#C4A265]/20 shadow-sm shadow-[#C4A265]/5">
                                                <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                                {{ v }}
                                            </span>
                                        </TransitionGroup>
                                    </div>
                                </div>
                            </Transition>

                            <!-- Hint text -->
                            <div class="mt-5 flex items-start gap-2 p-3 bg-amber-50/50 rounded-xl border border-amber-100/50">
                                <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-[10px] text-amber-700/70 leading-relaxed">
                                    Toggle variables here, then click them above the text area to insert into your message body. Variables will be replaced with real values when sent.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ---- ACTION BUTTONS ---- -->
                    <div
                        :class="sectionVisible.actions ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        class="transition-all duration-700 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                    >
                        <div class="space-y-3">
                            <!-- Submit button -->
                            <button type="submit" :disabled="form.processing"
                                class="group relative w-full px-6 py-3.5 rounded-xl text-white text-sm font-bold transition-all duration-300 overflow-hidden disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2.5 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:scale-[1.02] active:scale-[0.98]"
                                style="background: linear-gradient(135deg, #C4A265 0%, #D4B87A 50%, #C4A265 100%); background-size: 200% 200%;">
                                <!-- Shimmer effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                <!-- Spinner -->
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <!-- Icon -->
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <span class="relative">{{ $t('a_create_template') }}</span>
                            </button>

                            <!-- Cancel button -->
                            <Link href="/admin/templates"
                                class="group block w-full px-6 py-3 text-center rounded-xl text-sm font-semibold text-gray-500 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 transition-all duration-300 hover:shadow-sm hover:scale-[1.01] active:scale-[0.99]">
                                {{ $t('a_cancel') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Sticky helper - form summary -->
                    <div
                        :class="sectionVisible.actions ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                        class="transition-all duration-700 ease-out sticky top-6 bg-gradient-to-br from-gray-50 to-white rounded-2xl border border-gray-100 p-5"
                    >
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3">Quick Summary</p>
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">Name</span>
                                <span class="text-[11px] font-semibold text-gray-700 max-w-[60%] truncate">{{ form.name || '--' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">Channel</span>
                                <span class="text-[11px] font-semibold capitalize"
                                    :class="form.channel === 'whatsapp' ? 'text-emerald-600' : form.channel === 'sms' ? 'text-blue-600' : 'text-purple-600'">
                                    {{ form.channel }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">Category</span>
                                <span class="text-[11px] font-semibold text-gray-700 capitalize">{{ form.category.replace('_', ' ') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">Variables</span>
                                <span class="text-[11px] font-semibold text-[#C4A265]">{{ form.variables.length }} selected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Custom bounce animation for channel card selection */
@keyframes bounce-once {
    0%, 100% { transform: scale(1.02); }
    50% { transform: scale(1.06); }
}
.animate-bounce-once {
    animation: bounce-once 0.4s ease-in-out;
}

/* Flip animation for variable toggle */
@keyframes flip {
    0% { transform: perspective(400px) rotateY(0deg) scale(1.02); }
    40% { transform: perspective(400px) rotateY(90deg) scale(0.95); }
    60% { transform: perspective(400px) rotateY(90deg) scale(0.95); }
    100% { transform: perspective(400px) rotateY(0deg) scale(1.02); }
}
.animate-flip {
    animation: flip 0.5s ease-in-out;
}

/* Smooth tab indicator transition */
.duration-400 {
    transition-duration: 400ms;
}

.duration-600 {
    transition-duration: 600ms;
}
</style>
