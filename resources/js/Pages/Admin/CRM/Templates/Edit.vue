<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ template: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const activeTab = ref('en');
const previewKey = ref(0);

const form = useForm({
    name: props.template.name || '',
    channel: props.template.channel || 'whatsapp',
    category: props.template.category || 'follow_up',
    subject: props.template.subject || '',
    body_en: props.template.body_en || '',
    body_ar: props.template.body_ar || '',
    variables: props.template.variables || [],
    is_active: props.template.is_active ?? true,
});

const availableVariables = [
    '{name}', '{phone}', '{email}', '{service}', '{date}', '{time}', '{clinic_name}', '{clinic_phone}',
];

const channelOptions = [
    { value: 'whatsapp', label: 'WhatsApp', desc: 'Rich messaging', iconColor: 'text-emerald-500', bg: 'bg-emerald-50 border-emerald-200 ring-emerald-100', dotColor: 'bg-emerald-500' },
    { value: 'sms', label: 'SMS', desc: 'Text messages', iconColor: 'text-[#1B365D]', bg: 'bg-slate-50 border-slate-200 ring-slate-100', dotColor: 'bg-[#1B365D]' },
    { value: 'email', label: 'Email', desc: 'Email campaigns', iconColor: 'text-[#1B365D]', bg: 'bg-slate-50 border-slate-200 ring-slate-100', dotColor: 'bg-[#1B365D]' },
];

const categoryLabels = {
    welcome: 'Welcome',
    follow_up: 'Follow Up',
    appointment_reminder: 'Appointment Reminder',
    promotion: 'Promotion',
    re_engagement: 'Re-engagement',
    thank_you: 'Thank You',
    custom: 'Custom',
};

const currentBody = computed(() => activeTab.value === 'en' ? form.body_en : form.body_ar);
const charCount = computed(() => currentBody.value.length);
const maxChars = computed(() => form.channel === 'sms' ? 160 : form.channel === 'whatsapp' ? 4096 : 10000);
const charPercentage = computed(() => Math.min((charCount.value / maxChars.value) * 100, 100));
const charColor = computed(() => charPercentage.value > 90 ? 'text-red-500' : charPercentage.value > 70 ? 'text-amber-500' : 'text-gray-400');

const activeChannelOption = computed(() => channelOptions.find(c => c.value === form.channel));

const formattedDate = computed(() => {
    if (!props.template.created_at) return '--';
    const d = new Date(props.template.created_at);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
});

// Watch for body changes to trigger preview animation
watch(() => [form.body_en, form.body_ar], () => {
    previewKey.value++;
});

function toggleVariable(v) {
    const idx = form.variables.indexOf(v);
    if (idx >= 0) form.variables.splice(idx, 1);
    else form.variables.push(v);
}

function insertVariable(v, field) {
    form[field] += ` ${v}`;
}

function submit() {
    form.post(`/admin/templates/${props.template.id}`);
}
</script>

<template>
    <AdminLayout :title="`Edit: ${template.name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <Link href="/admin/templates"
                            class="mt-1 p-2.5 rounded-xl text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 transition-all duration-200 hover:shadow-sm group">
                            <svg class="w-5 h-5 transition-transform duration-200 group-hover:ltr:-translate-x-0.5 group-hover:rtl:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        </Link>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_edit_template') }}</h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ template.name }}</p>
                        </div>
                    </div>

                    <!-- Template Info Badges -->
                    <div class="flex items-center gap-2 flex-wrap justify-end">
                        <!-- Channel Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border"
                            :class="activeChannelOption?.bg">
                            <span class="w-1.5 h-1.5 rounded-full" :class="activeChannelOption?.dotColor"></span>
                            {{ activeChannelOption?.label }}
                        </span>
                        <!-- Category Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-50 border border-gray-200 text-gray-600">
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            {{ categoryLabels[template.category] || template.category }}
                        </span>
                        <!-- Usage Count Badge -->
                        <span v-if="template.usage_count !== undefined" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#C4A265]/10 border border-[#C4A265]/20 text-[#C4A265]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            {{ template.usage_count }} uses
                        </span>
                        <!-- Created Date Badge -->
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-50 border border-gray-200 text-gray-400">
                            <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            {{ formattedDate }}
                        </span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Template Details Card -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-100 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 relative overflow-hidden hover:shadow-md">
                        <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265]"></div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $t('a_template_details') }}</h3>

                        <div class="space-y-5">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name') }}<span class="text-red-400 ltr:ml-0.5 rtl:mr-0.5">*</span></label>
                                <input v-model="form.name" type="text"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                    :placeholder="$t('a_template_name_placeholder')" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5">{{ form.errors.name }}</p>
                            </div>

                            <!-- Channel Selection -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_channel') }}<span class="text-red-400 ltr:ml-0.5 rtl:mr-0.5">*</span></label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <button v-for="ch in channelOptions" :key="ch.value" type="button"
                                        @click="form.channel = ch.value"
                                        :class="form.channel === ch.value ? `${ch.bg} ring-2 scale-[1.02]` : 'bg-gray-50/80 border-gray-200/80 hover:bg-gray-100 hover:scale-[1.01]'"
                                        class="px-4 py-3 border rounded-xl transition-all duration-200 ltr:text-left rtl:text-right">
                                        <div class="flex items-center gap-2">
                                            <!-- WhatsApp Icon -->
                                            <svg v-if="ch.value === 'whatsapp'" class="w-4 h-4" :class="form.channel === ch.value ? ch.iconColor : 'text-gray-400'" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            <!-- SMS Icon -->
                                            <svg v-if="ch.value === 'sms'" class="w-4 h-4" :class="form.channel === ch.value ? ch.iconColor : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                                            <!-- Email Icon -->
                                            <svg v-if="ch.value === 'email'" class="w-4 h-4" :class="form.channel === ch.value ? ch.iconColor : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        </div>
                                        <p class="text-sm font-semibold mt-1.5" :class="form.channel === ch.value ? ch.iconColor : 'text-gray-600'">{{ ch.label }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ ch.desc }}</p>
                                    </button>
                                </div>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_category') }}<span class="text-red-400 ltr:ml-0.5 rtl:mr-0.5">*</span></label>
                                <select v-model="form.category"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none"
                                    style="background-image: url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 fill=%27none%27 viewBox=%270 0 24 24%27 stroke=%27%239CA3AF%27%3E%3Cpath stroke-linecap=%27round%27 stroke-linejoin=%27round%27 stroke-width=%272%27 d=%27M19 9l-7 7-7-7%27/%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1rem;">
                                    <option value="welcome">Welcome</option>
                                    <option value="follow_up">{{ $t('a_follow_up') }}</option>
                                    <option value="appointment_reminder">Appointment Reminder</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="re_engagement">Re-engagement</option>
                                    <option value="thank_you">Thank You</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>

                            <!-- Subject (email only) -->
                            <div v-if="form.channel === 'email'">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_subject') }}</label>
                                <input v-model="form.subject" type="text"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                    :placeholder="$t('a_email_subject_line')" />
                            </div>

                            <!-- Active/Inactive Toggle -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300'"
                                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        :style="form.is_active ? 'box-shadow: 0 0 12px rgba(16, 185, 129, 0.3)' : ''">
                                        <span class="sr-only">Toggle active</span>
                                        <span :class="form.is_active ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'ltr:translate-x-0.5 rtl:-translate-x-0.5'"
                                            class="pointer-events-none relative inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition-transform duration-300 ease-in-out mt-0.5">
                                            <!-- Check icon when active -->
                                            <span :class="form.is_active ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out'"
                                                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity">
                                                <svg v-if="form.is_active" class="h-3 w-3 text-emerald-500" fill="currentColor" viewBox="0 0 12 12">
                                                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                                </svg>
                                                <svg v-else class="h-3 w-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </span>
                                        </span>
                                    </button>
                                    <div>
                                        <span class="text-sm font-semibold" :class="form.is_active ? 'text-emerald-600' : 'text-gray-400'">
                                            {{ form.is_active ? $t('a_active') : $t('a_inactive') }}
                                        </span>
                                        <p class="text-[10px] text-gray-400">
                                            {{ form.is_active ? 'Template can be used in campaigns' : 'Template is hidden from campaigns' }}
                                        </p>
                                    </div>
                                </div>
                                <span :class="form.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-gray-50 text-gray-400 border-gray-200'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold border transition-all duration-300">
                                    <span class="w-1.5 h-1.5 rounded-full transition-colors duration-300" :class="form.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                                    {{ form.is_active ? 'LIVE' : 'OFF' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Message Content Card -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 hover:shadow-md">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $t('a_message_content') }}</h3>

                        <!-- Language Tabs with Sliding Indicator -->
                        <div class="relative flex bg-gray-100 rounded-xl p-1 mb-5">
                            <!-- Sliding indicator -->
                            <div class="absolute top-1 bottom-1 w-[calc(50%-4px)] bg-white rounded-lg shadow-sm transition-all duration-300 ease-out"
                                :class="activeTab === 'en' ? 'ltr:left-1 rtl:right-1' : 'ltr:left-[calc(50%+2px)] rtl:right-[calc(50%+2px)]'"></div>
                            <button type="button" @click="activeTab = 'en'"
                                :class="activeTab === 'en' ? 'text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                                class="relative z-10 flex-1 py-2.5 text-xs font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                                {{ $t('a_english') }}
                            </button>
                            <button type="button" @click="activeTab = 'ar'"
                                :class="activeTab === 'ar' ? 'text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                                class="relative z-10 flex-1 py-2.5 text-xs font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" /></svg>
                                {{ $t('a_arabic') }}
                            </button>
                        </div>

                        <!-- English Body -->
                        <div v-show="activeTab === 'en'">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_body_english') }}<span class="text-red-400 ltr:ml-0.5 rtl:mr-0.5">*</span></label>
                                <div class="flex gap-1 flex-wrap justify-end">
                                    <button v-for="v in form.variables" :key="v" type="button" @click="insertVariable(v, 'body_en')"
                                        class="px-2 py-0.5 text-[10px] font-mono bg-[#C4A265]/10 text-[#C4A265] rounded-md hover:bg-[#C4A265]/20 hover:scale-105 transition-all duration-200">{{ v }}</button>
                                </div>
                            </div>
                            <textarea v-model="form.body_en" rows="6"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"></textarea>
                            <p v-if="form.errors.body_en" class="text-xs text-red-500 mt-1.5">{{ form.errors.body_en }}</p>
                        </div>

                        <!-- Arabic Body -->
                        <div v-show="activeTab === 'ar'">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_body_arabic') }}<span class="text-red-400 ltr:ml-0.5 rtl:mr-0.5">*</span></label>
                                <div class="flex gap-1 flex-wrap justify-end">
                                    <button v-for="v in form.variables" :key="v" type="button" @click="insertVariable(v, 'body_ar')"
                                        class="px-2 py-0.5 text-[10px] font-mono bg-[#C4A265]/10 text-[#C4A265] rounded-md hover:bg-[#C4A265]/20 hover:scale-105 transition-all duration-200">{{ v }}</button>
                                </div>
                            </div>
                            <textarea v-model="form.body_ar" rows="6" dir="rtl"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"></textarea>
                            <p v-if="form.errors.body_ar" class="text-xs text-red-500 mt-1.5">{{ form.errors.body_ar }}</p>
                        </div>

                        <!-- Character Count Bar -->
                        <div class="flex items-center justify-between mt-2">
                            <div class="flex-1 h-1 bg-gray-100 rounded-full overflow-hidden ltr:mr-3 rtl:ml-3">
                                <div class="h-full rounded-full transition-all duration-500 ease-out"
                                    :class="charPercentage > 90 ? 'bg-red-400' : charPercentage > 70 ? 'bg-amber-400' : 'bg-[#C4A265]'"
                                    :style="{ width: charPercentage + '%' }"></div>
                            </div>
                            <span class="text-[10px] font-mono tabular-nums" :class="charColor">
                                {{ charCount }} / {{ maxChars.toLocaleString() }}
                            </span>
                        </div>

                        <!-- Live Preview -->
                        <div v-if="(activeTab === 'en' && form.body_en) || (activeTab === 'ar' && form.body_ar)"
                            class="mt-5 transition-all duration-300">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                {{ $t('a_preview') }}
                            </p>

                            <!-- WhatsApp Preview -->
                            <div v-if="form.channel === 'whatsapp'" class="bg-[#E5DDD5] rounded-xl p-4 border border-[#D1C4A9]/30" :key="'wa-' + previewKey">
                                <div class="max-w-[85%] ltr:ml-auto rtl:mr-auto">
                                    <div class="bg-[#DCF8C6] rounded-2xl ltr:rounded-tr-sm rtl:rounded-tl-sm px-4 py-2.5 shadow-sm">
                                        <p class="text-sm text-gray-800 leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">
                                            {{ activeTab === 'en' ? form.body_en : form.body_ar }}
                                        </p>
                                        <div class="flex items-center justify-end gap-1 mt-1">
                                            <span class="text-[10px] text-gray-500">{{ new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }) }}</span>
                                            <svg class="w-4 h-4 text-[#1B365D]" fill="currentColor" viewBox="0 0 24 24"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12l-1.41 1.41L11.66 19l12-12-1.42-1.41zM.41 13.41L6 19l1.41-1.41L1.83 12 .41 13.41z"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SMS Preview -->
                            <div v-else-if="form.channel === 'sms'" class="bg-gray-50 rounded-xl p-4 border border-gray-200" :key="'sms-' + previewKey">
                                <div class="max-w-[85%] ltr:ml-auto rtl:mr-auto">
                                    <div class="bg-[#1B365D] rounded-2xl ltr:rounded-tr-sm rtl:rounded-tl-sm px-4 py-2.5 shadow-sm">
                                        <p class="text-sm text-white leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">
                                            {{ activeTab === 'en' ? form.body_en : form.body_ar }}
                                        </p>
                                    </div>
                                    <p class="text-[10px] text-gray-400 ltr:text-right rtl:text-left mt-1.5">
                                        Delivered
                                    </p>
                                </div>
                            </div>

                            <!-- Email Preview -->
                            <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm" :key="'email-' + previewKey">
                                <div class="bg-gray-50 border-b border-gray-200 px-4 py-3">
                                    <div class="flex items-center gap-1.5 mb-2">
                                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] text-gray-400"><span class="font-semibold text-gray-500">From:</span> Doctorato Polyclinic</p>
                                        <p class="text-[10px] text-gray-400"><span class="font-semibold text-gray-500">Subject:</span> {{ form.subject || 'No subject' }}</p>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">
                                        {{ activeTab === 'en' ? form.body_en : form.body_ar }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 border-t border-gray-100 px-4 py-2">
                                    <p class="text-[9px] text-gray-300 text-center">Doctorato Polyclinic</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Variables + Actions -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Variables Panel -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-150 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 hover:shadow-md">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $t('a_available_variables') }}</h3>
                            <span class="text-[10px] font-semibold text-[#C4A265] bg-[#C4A265]/10 px-2 py-0.5 rounded-full">
                                {{ form.variables.length }} / {{ availableVariables.length }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button v-for="v in availableVariables" :key="v" type="button" @click="toggleVariable(v)"
                                :class="form.variables.includes(v)
                                    ? 'bg-[#C4A265] text-white shadow-md shadow-[#C4A265]/20 ring-2 ring-[#C4A265]/20 scale-[1.02]'
                                    : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 hover:scale-[1.02]'"
                                class="px-3 py-2.5 text-xs font-mono rounded-xl transition-all duration-200 flex items-center gap-2">
                                <svg v-if="form.variables.includes(v)" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-3.5 h-3.5 flex-shrink-0 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ v }}
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-4 leading-relaxed">
                            Select variables to mark as used, then click them above the text area to insert into your message.
                        </p>
                    </div>

                    <!-- Actions Card -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                        class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 md:p-8 hover:shadow-md">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-5">Actions</h3>
                        <div class="space-y-3">
                            <button type="submit" :disabled="form.processing"
                                class="w-full px-4 md:px-6 py-3.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-lg flex items-center justify-center gap-2"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ $t('a_update_template') }}
                            </button>
                            <Link href="/admin/templates"
                                class="block w-full px-4 md:px-6 py-3 text-center rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0">
                                {{ $t('a_cancel') }}
                            </Link>
                        </div>

                        <!-- Form Dirty Indicator -->
                        <div v-if="form.isDirty" class="mt-4 flex items-center gap-2 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                            <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                            <span class="text-[10px] text-amber-700 font-medium">Unsaved changes</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
