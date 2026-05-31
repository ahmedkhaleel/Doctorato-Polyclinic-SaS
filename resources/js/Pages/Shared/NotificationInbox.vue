<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

const props = defineProps({
    panel: { type: String, default: 'admin' },
    conversations: Object,
    active: Object,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);
const layout = computed(() => ({ admin: AdminLayout, secretary: SecretaryLayout }[props.panel] || AdminLayout));
const base = computed(() => `/${props.panel}/inbox`);

const reply = ref('');
const channelColor = { whatsapp: '#25D366', sms: '#1B365D' };

function openConversation(contact) {
    router.get(base.value, { contact }, { preserveState: true, preserveScroll: true });
}
function sendReply() {
    if (!reply.value.trim() || !props.active) return;
    router.post(`${base.value}/reply`, { contact: props.active.contact, body: reply.value }, {
        preserveScroll: true,
        onSuccess: () => { reply.value = ''; router.reload({ only: ['active', 'conversations'] }); },
    });
}
const fmt = (iso) => iso ? new Date(iso).toLocaleString(isRtl.value ? 'ar-EG' : 'en-GB') : '';
const fmtShort = (iso) => iso ? new Date(iso).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB') : '';
</script>

<template>
    <component :is="layout" :title="t('صندوق المراسلات', 'Inbox')">
        <div class="max-w-7xl mx-auto p-4 md:p-6" :dir="isRtl ? 'rtl' : 'ltr'">
            <h1 class="text-xl font-bold text-gray-900 mb-4">{{ t('صندوق المراسلات', 'Conversations Inbox') }}</h1>

            <div class="grid lg:grid-cols-3 gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" style="min-height:60vh">
                <!-- Conversation list -->
                <div class="border-e border-gray-100 overflow-y-auto" style="max-height:70vh">
                    <div v-if="!conversations.data.length" class="text-center text-gray-400 py-10 text-sm">{{ t('لا توجد محادثات', 'No conversations') }}</div>
                    <button v-for="c in conversations.data" :key="c.contact" @click="openConversation(c.contact)"
                            class="w-full text-start flex items-center gap-3 px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition"
                            :class="active && active.contact === c.contact ? 'bg-[#1B365D]/[0.05]' : ''">
                        <div class="w-10 h-10 rounded-full bg-[#1B365D]/10 flex items-center justify-center text-[#1B365D] font-bold shrink-0">
                            {{ (c.patient?.name || c.contact).charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 text-sm truncate">{{ c.patient?.name || c.contact }}</p>
                            <p class="text-xs text-gray-400">{{ c.messages }} {{ t('رسالة', 'msgs') }} · {{ fmtShort(c.last_at) }}</p>
                        </div>
                        <span v-if="c.unread > 0" class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center shrink-0">{{ c.unread }}</span>
                    </button>
                </div>

                <!-- Thread -->
                <div class="lg:col-span-2 flex flex-col" style="max-height:70vh">
                    <div v-if="!active" class="flex-1 flex items-center justify-center text-gray-400 text-sm">{{ t('اختر محادثة', 'Select a conversation') }}</div>
                    <template v-else>
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="font-bold text-gray-900">{{ active.patient?.name || active.contact }}</p>
                                <p class="text-xs text-gray-400">{{ active.contact }}<span v-if="active.patient"> · {{ active.patient.file_number }}</span></p>
                            </div>
                            <a v-if="active.patient" :href="`/admin/patients/${active.patient.id}/communications`" class="text-xs font-semibold text-[#1B365D] hover:underline">{{ t('ملف المريض', 'Patient file') }}</a>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-gray-50/40">
                            <div v-for="m in active.messages" :key="m.id" class="flex" :class="m.direction === 'inbound' ? 'justify-start' : 'justify-end'">
                                <div class="max-w-[75%] rounded-2xl px-3.5 py-2 text-sm"
                                     :class="m.direction === 'inbound' ? 'bg-white border border-gray-100 text-gray-800' : 'text-white'"
                                     :style="m.direction === 'inbound' ? '' : { background: channelColor[m.channel] || '#1B365D' }">
                                    <p class="whitespace-pre-wrap">{{ m.body }}</p>
                                    <p class="text-[10px] mt-1 opacity-70">{{ fmt(m.created_at) }} <span v-if="m.direction === 'outbound'">· {{ m.status }}</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 border-t border-gray-100 flex gap-2">
                            <input v-model="reply" @keyup.enter="sendReply" :placeholder="t('اكتب رداً…', 'Type a reply…')" class="flex-1 rounded-xl border-gray-200 text-sm" />
                            <button @click="sendReply" :disabled="!reply.trim()" class="px-4 py-2 rounded-xl text-white text-sm font-semibold disabled:opacity-50" style="background:#1B365D">{{ t('إرسال', 'Send') }}</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </component>
</template>
