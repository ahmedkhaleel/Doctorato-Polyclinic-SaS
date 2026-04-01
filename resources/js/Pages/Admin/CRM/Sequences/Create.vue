<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useForm, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    triggerEvents: Object,
    actionTypes: Object,
    leadStatuses: Object,
    sources: Array,
    campaigns: Array,
    templates: Array,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const form = useForm({
    name: '',
    name_ar: '',
    description: '',
    trigger_event: 'lead_created',
    trigger_conditions: { lead_source_id: null, priority: null, campaign_id: null },
    is_active: true,
    stop_on_reply: true,
    stop_on_conversion: true,
    max_enrollments: null,
    steps: [
        { step_order: 1, delay_minutes: 60, action_type: 'notify_staff', template_id: null, follow_up_type: 'call', target_status: null, score_points: null, message_en: '', message_ar: '', notification_message: '', is_active: true },
    ],
});

function addStep() {
    const lastDelay = form.steps.length > 0 ? form.steps[form.steps.length - 1].delay_minutes : 60;
    form.steps.push({
        step_order: form.steps.length + 1,
        delay_minutes: lastDelay,
        action_type: 'create_follow_up',
        template_id: null,
        follow_up_type: 'call',
        target_status: null,
        score_points: null,
        message_en: '',
        message_ar: '',
        notification_message: '',
        is_active: true,
    });
}

function removeStep(index) {
    form.steps.splice(index, 1);
    form.steps.forEach((s, i) => s.step_order = i + 1);
}

function moveStep(index, direction) {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= form.steps.length) return;
    const temp = form.steps[index];
    form.steps[index] = form.steps[newIndex];
    form.steps[newIndex] = temp;
    form.steps.forEach((s, i) => s.step_order = i + 1);
}

function submit() {
    form.post('/admin/sequences', { preserveScroll: true });
}

const delayPresets = [
    { label: '15m', value: 15 },
    { label: '30m', value: 30 },
    { label: '1h', value: 60 },
    { label: '2h', value: 120 },
    { label: '4h', value: 240 },
    { label: '8h', value: 480 },
    { label: '1d', value: 1440 },
    { label: '2d', value: 2880 },
    { label: '3d', value: 4320 },
    { label: '7d', value: 10080 },
];

const actionColors = {
    create_follow_up: 'border-blue-200 bg-blue-50/50',
    send_whatsapp: 'border-green-200 bg-green-50/50',
    send_email: 'border-purple-200 bg-purple-50/50',
    send_sms: 'border-cyan-200 bg-cyan-50/50',
    notify_staff: 'border-amber-200 bg-amber-50/50',
    change_status: 'border-indigo-200 bg-indigo-50/50',
    add_score: 'border-pink-200 bg-pink-50/50',
};

function formatDelay(minutes) {
    if (minutes < 60) return `${minutes} minutes`;
    const h = Math.floor(minutes / 60);
    if (h < 24) return h === 1 ? '1 hour' : `${h} hours`;
    const d = Math.floor(h / 24);
    const rh = h % 24;
    let str = d === 1 ? '1 day' : `${d} days`;
    if (rh > 0) str += ` ${rh}h`;
    return str;
}

const filteredTemplates = computed(() => (actionType) => {
    const channelMap = { send_whatsapp: 'whatsapp', send_email: 'email', send_sms: 'sms' };
    const channel = channelMap[actionType];
    if (!channel) return props.templates;
    return props.templates.filter(t => t.channel === channel);
});
</script>

<template>
    <AdminLayout :title="$t('a_create_sequence')">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-8"
                 :class="{ 'translate-y-0 opacity-100': mounted, '-translate-y-4 opacity-0': !mounted }"
                 style="transition: all 0.5s ease-out">
                <Link href="/admin/sequences" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-[#3A3A3A]">{{ $t('a_create_automation_sequence') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $t('a_define_sequence_description') }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                     :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-6 opacity-0': !mounted }"
                     style="transition: all 0.5s ease-out 0.1s">
                    <h2 class="text-base font-semibold text-[#3A3A3A] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $t('a_basic_information') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_en') }}<span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" :placeholder="$t('a_sequence_name_placeholder')"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_name_ar') }}</label>
                            <input v-model="form.name_ar" type="text" dir="rtl" placeholder="مثال: ترحيب بالعميل الجديد"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_description') }}</label>
                            <textarea v-model="form.description" rows="2" :placeholder="$t('a_sequence_description_placeholder')"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors resize-none" />
                        </div>
                    </div>
                </div>

                <!-- Trigger -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                     :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-6 opacity-0': !mounted }"
                     style="transition: all 0.5s ease-out 0.15s">
                    <h2 class="text-base font-semibold text-[#3A3A3A] mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>{{ $t('a_trigger') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_when_this_happens') }}<span class="text-red-500">*</span></label>
                            <select v-model="form.trigger_event"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                                <option v-for="(label, key) in triggerEvents" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_only_from_source') }}</label>
                            <select v-model="form.trigger_conditions.lead_source_id"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                                <option :value="null">{{ $t('a_any_source') }}</option>
                                <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_only_priority') }}</label>
                            <select v-model="form.trigger_conditions.priority"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                                <option :value="null">{{ $t('a_any_priority') }}</option>
                                <option :value="1">&#9679; Hot</option>
                                <option :value="2">&#9679; Warm</option>
                                <option :value="3">&#9679; Cold</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('a_only_from_campaign') }}</label>
                            <select v-model="form.trigger_conditions.campaign_id"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                                <option :value="null">{{ $t('a_any_campaign') }}</option>
                                <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.stop_on_reply" class="w-4 h-4 text-[#C4A265] rounded border-gray-300 focus:ring-[#C4A265]" />
                            <span class="text-sm text-gray-600">{{ $t('a_stop_on_reply') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.stop_on_conversion" class="w-4 h-4 text-[#C4A265] rounded border-gray-300 focus:ring-[#C4A265]" />
                            <span class="text-sm text-gray-600">{{ $t('a_stop_on_conversion') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="w-4 h-4 text-[#C4A265] rounded border-gray-300 focus:ring-[#C4A265]" />
                            <span class="text-sm text-gray-600">{{ $t('a_active') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Steps Builder -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6"
                     :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-6 opacity-0': !mounted }"
                     style="transition: all 0.5s ease-out 0.2s">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-[#3A3A3A] flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Steps ({{ form.steps.length }})
                        </h2>
                        <button type="button" @click="addStep"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-[#C4A265] bg-[#C4A265]/10 rounded-lg hover:bg-[#C4A265]/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>{{ $t('a_add_step') }}</button>
                    </div>

                    <p v-if="form.errors.steps" class="text-xs text-red-500 mb-3">{{ form.errors.steps }}</p>

                    <div class="space-y-4">
                        <div v-for="(step, index) in form.steps" :key="index"
                            :class="actionColors[step.action_type] || 'border-gray-200 bg-gray-50/50'"
                            class="border rounded-xl p-4 transition-all duration-200">

                            <!-- Step Header -->
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 bg-[#3A3A3A] text-white rounded-full flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
                                    <span v-if="index > 0" class="text-xs text-gray-500">after {{ formatDelay(step.delay_minutes) }}</span>
                                    <span v-else class="text-xs text-gray-500">after {{ formatDelay(step.delay_minutes) }} from trigger</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button v-if="index > 0" type="button" @click="moveStep(index, -1)" class="p-1 rounded hover:bg-white/60 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button v-if="index < form.steps.length - 1" type="button" @click="moveStep(index, 1)" class="p-1 rounded hover:bg-white/60 transition-colors">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button v-if="form.steps.length > 1" type="button" @click="removeStep(index)" class="p-1 rounded hover:bg-red-100 transition-colors">
                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <!-- Delay -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_delay_minutes') }}</label>
                                    <input v-model.number="step.delay_minutes" type="number" min="0"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors" />
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        <button v-for="p in delayPresets" :key="p.value" type="button"
                                            @click="step.delay_minutes = p.value"
                                            :class="step.delay_minutes === p.value ? 'bg-[#C4A265] text-white' : 'bg-white text-gray-500 hover:bg-gray-100'"
                                            class="px-1.5 py-0.5 text-[10px] rounded border border-gray-200 transition-colors">
                                            {{ p.label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Type -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_action') }}</label>
                                    <select v-model="step.action_type"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors">
                                        <option v-for="(label, key) in actionTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>

                                <!-- Template (for send actions) -->
                                <div v-if="['send_whatsapp', 'send_email', 'send_sms'].includes(step.action_type)">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_template') }}</label>
                                    <select v-model="step.template_id"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors">
                                        <option :value="null">{{ $t('a_custom_message') }}</option>
                                        <option v-for="t in filteredTemplates(step.action_type)" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                </div>

                                <!-- Follow-up Type -->
                                <div v-if="step.action_type === 'create_follow_up'">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_follow_up_type') }}</label>
                                    <select v-model="step.follow_up_type"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors">
                                        <option value="call">{{ $t('a_call') }}</option>
                                        <option value="whatsapp">{{ $t('a_whatsapp') }}</option>
                                        <option value="email">{{ $t('a_email') }}</option>
                                        <option value="sms">{{ $t('a_sms') }}</option>
                                        <option value="meeting">Meeting</option>
                                    </select>
                                </div>

                                <!-- Target Status -->
                                <div v-if="step.action_type === 'change_status'">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_target_status') }}</label>
                                    <select v-model="step.target_status"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors">
                                        <option v-for="(label, key) in leadStatuses" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>

                                <!-- Score Points -->
                                <div v-if="step.action_type === 'add_score'">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_score_points') }}</label>
                                    <input v-model.number="step.score_points" type="number" min="-100" max="100"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors" />
                                </div>
                            </div>

                            <!-- Custom Message (for send actions without template) -->
                            <div v-if="['send_whatsapp', 'send_email', 'send_sms'].includes(step.action_type) && !step.template_id"
                                class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_message_en') }}</label>
                                    <textarea v-model="step.message_en" rows="2" placeholder="Use {{name}}, {{clinic_name}}, {{date}}..."
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors resize-none" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_message_ar') }}</label>
                                    <textarea v-model="step.message_ar" rows="2" dir="rtl" placeholder="استخدم {{name}}، {{clinic_name}}، {{date}}..."
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors resize-none" />
                                </div>
                            </div>

                            <!-- Notification Message -->
                            <div v-if="['notify_staff', 'create_follow_up'].includes(step.action_type)" class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('a_note_instructions') }}</label>
                                <input v-model="step.notification_message" type="text" placeholder="e.g. Call this lead to confirm their interest"
                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] bg-white transition-colors" />
                            </div>
                        </div>
                    </div>

                    <!-- Add Step Button (bottom) -->
                    <button type="button" @click="addStep"
                        class="mt-4 w-full py-2.5 border-2 border-dashed border-gray-200 rounded-xl text-sm text-gray-400 hover:border-[#C4A265] hover:text-[#C4A265] transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>{{ $t('a_add_another_step') }}</button>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3"
                     :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-6 opacity-0': !mounted }"
                     style="transition: all 0.5s ease-out 0.25s">
                    <Link href="/admin/sequences" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">{{ $t('a_cancel') }}</Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#C4A265] to-[#D4B87A] rounded-xl shadow-md hover:shadow-lg transition-all disabled:opacity-50">
                        {{ form.processing ? $t('a_saving') : $t('a_create_sequence') }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
