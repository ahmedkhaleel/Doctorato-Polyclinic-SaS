<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AiAssist from '@/Components/Ai/AiAssist.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    templates: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const expandedKey = ref(null);
const editForms = {};

function getForm(t) {
    if (! editForms[t.id]) {
        editForms[t.id] = useForm({
            body_ar: t.body_ar,
            body_en: t.body_en,
            description: t.description ?? '',
            is_active: t.is_active,
        });
    }
    return editForms[t.id];
}

function save(t) {
    getForm(t).post(`/admin/sms-templates/${t.id}`, { preserveScroll: true });
}

const grouped = computed(() => {
    const out = {};
    for (const t of props.templates) {
        if (! out[t.category]) out[t.category] = [];
        out[t.category].push(t);
    }
    return out;
});

const categoryLabel = (c) => ({
    bookings:  isRtl.value ? 'الحجوزات' : 'Bookings',
    reminders: isRtl.value ? 'تذكيرات' : 'Reminders',
    marketing: isRtl.value ? 'العروض'   : 'Marketing',
})[c] ?? c;

</script>

<template>
    <div class="max-w-5xl mx-auto p-4 md:p-6 space-y-5">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1B365D]">{{ isRtl ? 'قوالب الرسائل النصية' : 'SMS Templates' }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ isRtl
                    ? 'حرّر نص الرسائل بدون تعديل الكود. استخدم متغيرات بين أقواس مزدوجة للقيم الديناميكية.'
                    : 'Edit message copy without touching code. Use double-brace placeholders for dynamic values.' }}
            </p>
        </div>

        <div v-for="(items, cat) in grouped" :key="cat" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-gray-100 font-bold text-slate-700 flex items-center gap-2">
                <span class="w-1.5 h-4 rounded-full bg-[#C4A265]"></span>{{ categoryLabel(cat) }}
            </div>

            <div v-for="t in items" :key="t.id" class="border-b border-gray-100 last:border-b-0">
                <button
                    @click="expandedKey = expandedKey === t.key ? null : t.key"
                    class="w-full px-5 py-4 flex items-start gap-4 hover:bg-slate-50/50 transition text-start"
                >
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <code class="text-xs font-mono text-[#1B365D] bg-[#C4A265]/10 px-2 py-0.5 rounded">{{ t.key }}</code>
                            <span v-if="!t.is_active" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-200">
                                {{ isRtl ? 'معطل' : 'INACTIVE' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-1">{{ t.description }}</p>
                        <p class="text-sm text-slate-700 truncate">{{ isRtl ? t.body_ar : t.body_en }}</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform"
                         :class="expandedKey === t.key ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-if="expandedKey === t.key" class="px-5 pb-5 bg-slate-50/40 border-t border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ isRtl ? 'النص العربي' : 'Arabic body' }}</label>
                            <textarea v-model="getForm(t).body_ar" rows="3" dir="rtl"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none font-mono"></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">{{ getForm(t).body_ar.length }} {{ isRtl ? 'حرف' : 'chars' }}</p>
                            <AiAssist feature="comms_drafting" label-ar="صياغة (عربي)" label-en="Draft (AR)"
                                :build-vars="() => ({ channel: 'SMS', topic: t.name || t.event_key })"
                                @insert="(txt) => getForm(t).body_ar = txt" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">English body</label>
                            <textarea v-model="getForm(t).body_en" rows="3" dir="ltr"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none font-mono"></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">{{ getForm(t).body_en.length }} chars</p>
                            <AiAssist feature="comms_drafting" label-ar="صياغة (إنجليزي)" label-en="Draft (EN)"
                                :build-vars="() => ({ channel: 'SMS', topic: t.name || t.event_key })"
                                @insert="(txt) => getForm(t).body_en = txt" />
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ isRtl ? 'الوصف' : 'Description' }}</label>
                        <input v-model="getForm(t).description" type="text"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:border-[#C4A265] focus:ring-2 focus:ring-[#C4A265]/30 outline-none" />
                    </div>

                    <div v-if="t.placeholders?.length" class="mt-3">
                        <span class="text-xs text-slate-500">{{ isRtl ? 'المتغيرات المتاحة:' : 'Available placeholders:' }}</span>
                        <span v-for="p in t.placeholders" :key="p"
                            class="ml-1 inline-block text-xs font-mono bg-emerald-50 border border-emerald-200 text-emerald-700 px-2 py-0.5 rounded">
                            <span v-pre>{{</span>{{ p }}<span v-pre>}}</span>
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="getForm(t).is_active" class="rounded text-[#C4A265]" />
                            <span>{{ isRtl ? 'مفعّل' : 'Active' }}</span>
                        </label>
                        <button @click="save(t)" :disabled="getForm(t).processing"
                            class="ml-auto rtl:mr-auto rtl:ml-0 px-5 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-bold hover:bg-[#0F2444] disabled:opacity-50">
                            {{ getForm(t).processing
                                ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...')
                                : (isRtl ? 'حفظ' : 'Save') }}
                        </button>
                    </div>
                    <p v-if="getForm(t).recentlySuccessful" class="text-sm text-emerald-600 mt-2 inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" /></svg>
                        {{ isRtl ? 'تم الحفظ' : 'Saved' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
