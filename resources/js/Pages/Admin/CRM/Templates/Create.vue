<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const activeTab = ref('en');

const form = useForm({
    name: '', channel: 'whatsapp', category: 'follow_up',
    subject: '', body_en: '', body_ar: '', variables: [],
});

const availableVariables = [
    '{name}', '{phone}', '{email}', '{service}', '{date}', '{time}', '{clinic_name}', '{clinic_phone}',
];

function toggleVariable(v) {
    const idx = form.variables.indexOf(v);
    if (idx >= 0) form.variables.splice(idx, 1);
    else form.variables.push(v);
}

function insertVariable(v, field) {
    form[field] += ` ${v}`;
}

function submit() { form.post('/admin/templates'); }

const channelOptions = [
    { value: 'whatsapp', label: 'WhatsApp', desc: 'Rich messaging', iconColor: 'text-green-500', bg: 'bg-green-50 border-green-200 ring-green-100' },
    { value: 'sms', label: 'SMS', desc: 'Text messages', iconColor: 'text-blue-500', bg: 'bg-blue-50 border-blue-200 ring-blue-100' },
    { value: 'email', label: 'Email', desc: 'Email campaigns', iconColor: 'text-purple-500', bg: 'bg-purple-50 border-purple-200 ring-purple-100' },
];
</script>

<template>
    <AdminLayout :title="$t('a_create_template')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 flex items-center gap-4">
                <Link href="/admin/templates" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_create_template') }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('a_configure_template_description') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Left Column: Details -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Template Details -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-100 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <div class="absolute top-0 ltr:left-0 rtl:right-0 ltr:right-0 rtl:left-0 h-1 bg-gradient-to-r from-[#C4A265] via-[#D4B87A] to-[#C4A265] rounded-t-2xl"></div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $t('a_template_details') }}</h3>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name') }}<span class="text-red-400">*</span></label>
                                <input v-model="form.name" type="text"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                    :placeholder="$t('a_template_name_placeholder')" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_channel') }}<span class="text-red-400">*</span></label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button v-for="ch in channelOptions" :key="ch.value" type="button"
                                        @click="form.channel = ch.value"
                                        :class="form.channel === ch.value ? `${ch.bg} ring-2` : 'bg-gray-50/80 border-gray-200/80 hover:bg-gray-100'"
                                        class="px-4 py-3 border rounded-xl transition-all duration-200 ltr:text-left rtl:text-right">
                                        <p class="text-sm font-semibold" :class="form.channel === ch.value ? ch.iconColor : 'text-gray-600'">{{ ch.label }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ ch.desc }}</p>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_category') }}<span class="text-red-400">*</span></label>
                                <select v-model="form.category"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200">
                                    <option value="welcome">Welcome</option>
                                    <option value="follow_up">{{ $t('a_follow_up') }}</option>
                                    <option value="appointment_reminder">Appointment Reminder</option>
                                    <option value="promotion">Promotion</option>
                                    <option value="re_engagement">Re-engagement</option>
                                    <option value="thank_you">Thank You</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>

                            <div v-if="form.channel === 'email'">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_subject') }}</label>
                                <input v-model="form.subject" type="text"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                    :placeholder="$t('a_email_subject_line')" />
                            </div>
                        </div>
                    </div>

                    <!-- Message Content with Tabs -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-6">{{ $t('a_message_content') }}</h3>

                        <!-- Language Tabs -->
                        <div class="flex bg-gray-100 rounded-xl p-1 mb-5">
                            <button type="button" @click="activeTab = 'en'"
                                :class="activeTab === 'en' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                class="flex-1 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_english') }}</button>
                            <button type="button" @click="activeTab = 'ar'"
                                :class="activeTab === 'ar' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                                class="flex-1 py-2 text-xs font-semibold rounded-lg transition-all duration-200">{{ $t('a_arabic') }}</button>
                        </div>

                        <!-- English Body -->
                        <div v-show="activeTab === 'en'">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_body_english') }}<span class="text-red-400">*</span></label>
                                <div class="flex gap-1 flex-wrap justify-end">
                                    <button v-for="v in form.variables" :key="v" type="button" @click="insertVariable(v, 'body_en')"
                                        class="px-2 py-0.5 text-[10px] font-mono bg-[#C4A265]/10 text-[#C4A265] rounded-md hover:bg-[#C4A265]/20 transition-all duration-200">{{ v }}</button>
                                </div>
                            </div>
                            <textarea v-model="form.body_en" rows="6"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                :placeholder="$t('a_enter_message_en')"></textarea>
                            <p v-if="form.errors.body_en" class="text-xs text-red-500 mt-1.5">{{ form.errors.body_en }}</p>
                        </div>

                        <!-- Arabic Body -->
                        <div v-show="activeTab === 'ar'">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_body_arabic') }}<span class="text-red-400">*</span></label>
                                <div class="flex gap-1 flex-wrap justify-end">
                                    <button v-for="v in form.variables" :key="v" type="button" @click="insertVariable(v, 'body_ar')"
                                        class="px-2 py-0.5 text-[10px] font-mono bg-[#C4A265]/10 text-[#C4A265] rounded-md hover:bg-[#C4A265]/20 transition-all duration-200">{{ v }}</button>
                                </div>
                            </div>
                            <textarea v-model="form.body_ar" rows="6" dir="rtl"
                                class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl resize-none focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 placeholder-gray-300"
                                placeholder="..."></textarea>
                            <p v-if="form.errors.body_ar" class="text-xs text-red-500 mt-1.5">{{ form.errors.body_ar }}</p>
                        </div>

                        <!-- Live Preview -->
                        <div v-if="(activeTab === 'en' && form.body_en) || (activeTab === 'ar' && form.body_ar)" class="mt-5 p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $t('a_preview') }}</p>
                            <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap" :dir="activeTab === 'ar' ? 'rtl' : 'ltr'">
                                {{ activeTab === 'en' ? form.body_en : form.body_ar }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Variables + Actions -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Variables -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-150 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-5">{{ $t('a_available_variables') }}</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button v-for="v in availableVariables" :key="v" type="button" @click="toggleVariable(v)"
                                :class="form.variables.includes(v) ? 'bg-[#C4A265] text-white shadow-md shadow-[#C4A265]/20 ring-2 ring-[#C4A265]/20' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200'"
                                class="px-3 py-2.5 text-xs font-mono rounded-xl transition-all duration-200 flex items-center gap-2">
                                <svg v-if="form.variables.includes(v)" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else class="w-3.5 h-3.5 flex-shrink-0 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ v }}
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-4 leading-relaxed">Select variables to mark as used, then click them above the text area to insert into your message.</p>
                    </div>

                    <!-- Actions -->
                    <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 delay-200 bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                        <div class="space-y-3">
                            <button type="submit" :disabled="form.processing"
                                class="w-full px-6 py-3 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>{{ $t('a_create_template') }}</button>
                            <Link href="/admin/templates"
                                class="block w-full px-6 py-3 text-center rounded-xl text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 border border-gray-200 transition-all duration-200">{{ $t('a_cancel') }}</Link>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
