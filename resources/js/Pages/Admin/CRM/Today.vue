<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import WhatsAppLink from '@/Components/Crm/WhatsAppLink.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({
    overdue: Array,
    slaBreaches: Array,
    slaTargetMinutes: Number,
    hotUncontacted: Array,
    dormancyLeads: Array,
    dormancyMode: String,
    todayFollowUps: Array,
});

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => {
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduce) { mounted.value = true; return; }
    setTimeout(() => mounted.value = true, 50);
});

const totalActions = computed(() =>
    (props.overdue?.length || 0) + (props.slaBreaches?.length || 0) +
    (props.hotUncontacted?.length || 0) + (props.dormancyLeads?.length || 0) +
    (props.todayFollowUps?.length || 0)
);

const todayLabel = computed(() => new Date().toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'long', day: 'numeric', month: 'long' }));

const followUpTypeLabels = {
    call: { ar: 'اتصال', en: 'Call' }, whatsapp: { ar: 'واتساب', en: 'WhatsApp' },
    email: { ar: 'بريد', en: 'Email' }, sms: { ar: 'SMS', en: 'SMS' }, meeting: { ar: 'مقابلة', en: 'Meeting' },
};
const ft = (type) => { const l = followUpTypeLabels[type]; return l ? (isRtl.value ? l.ar : l.en) : type; };

const fmtTime = (iso) => iso ? new Date(iso).toLocaleTimeString(isRtl.value ? 'ar-EG' : 'en-GB', { hour: '2-digit', minute: '2-digit' }) : '';
const fmtAgo = (minutes) => {
    if (minutes < 60) return isRtl.value ? `${minutes} دقيقة` : `${minutes}m`;
    const h = Math.floor(minutes / 60);
    if (h < 24) return isRtl.value ? `${h} ساعة` : `${h}h`;
    const d = Math.floor(h / 24);
    return isRtl.value ? `${d} يوم` : `${d}d`;
};
const overdueAgo = (iso) => fmtAgo(Math.max(1, Math.floor((Date.now() - new Date(iso).getTime()) / 60000)));

const completing = ref(null);
function completeFollowUp(f) {
    completing.value = f.id;
    router.post(`/admin/follow-ups/${f.id}/complete`, {}, {
        preserveScroll: true,
        onFinish: () => completing.value = null,
    });
}

/* Sections in priority order; rendered generically */
const sections = computed(() => [
    {
        key: 'overdue', kind: 'followups', items: props.overdue || [],
        titleAr: 'متابعات متأخرة', titleEn: 'Overdue follow-ups',
        accent: 'from-rose-500 to-red-500', chip: 'bg-rose-50 text-rose-700 border-rose-100',
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    },
    {
        key: 'sla', kind: 'leads', items: props.slaBreaches || [],
        titleAr: `خرق هدف الاستجابة (${props.slaTargetMinutes} دقيقة)`, titleEn: `SLA breach (${props.slaTargetMinutes}m target)`,
        accent: 'from-amber-500 to-orange-500', chip: 'bg-amber-50 text-amber-700 border-amber-100',
        icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        key: 'hot', kind: 'leads', items: props.hotUncontacted || [],
        titleAr: 'ساخن بلا تواصل', titleEn: 'Hot, never contacted',
        accent: 'from-[#C4A265] to-[#B8944F]', chip: 'bg-[#C4A265]/10 text-[#B8944F] border-[#C4A265]/20',
        icon: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z',
    },
    {
        key: 'dormancy', kind: 'dormancy', items: props.dormancyLeads || [],
        titleAr: 'خطر الخمول', titleEn: 'Dormancy risk',
        accent: 'from-violet-500 to-purple-500', chip: 'bg-violet-50 text-violet-700 border-violet-100',
        icon: 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z',
    },
    {
        key: 'today', kind: 'followups', items: props.todayFollowUps || [],
        titleAr: 'متابعات اليوم القادمة', titleEn: "Today's upcoming follow-ups",
        accent: 'from-[#1B365D] to-slate-600', chip: 'bg-slate-50 text-[#1B365D] border-slate-100',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
].filter(s => s.items.length));
</script>

<template>
    <AdminLayout :title="isRtl ? 'مهام اليوم — CRM' : 'CRM Today'">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Hero -->
            <div
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="relative overflow-hidden rounded-2xl bg-gradient-to-l from-[#1B365D] via-[#23456f] to-[#1B365D] p-6 md:p-8 transition-all duration-700"
            >
                <div class="absolute inset-0 opacity-[0.06]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"></div>
                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-white">{{ isRtl ? 'مهام اليوم' : 'Today\'s Work Queue' }}</h1>
                        <p class="text-sm text-white/60 mt-1">{{ todayLabel }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-center bg-white/10 backdrop-blur rounded-xl px-5 py-3">
                            <p class="text-2xl font-bold text-[#C4A265] tabular-nums">{{ totalActions }}</p>
                            <p class="text-[11px] text-white/60">{{ isRtl ? 'إجراء مطلوب' : 'actions needed' }}</p>
                        </div>
                        <Link href="/admin/crm" class="text-xs font-semibold text-white/80 hover:text-white border border-white/20 rounded-xl px-4 py-3 hover:bg-white/10 transition-colors">
                            {{ isRtl ? 'لوحة CRM' : 'CRM Dashboard' }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- All clear -->
            <div v-if="!sections.length"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 py-16 text-center transition-all duration-700 delay-150"
            >
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-base font-semibold text-gray-700">{{ isRtl ? 'كل شيء تحت السيطرة' : 'All clear' }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ isRtl ? 'لا توجد إجراءات عاجلة الآن — تابع خط الأنابيب' : 'No urgent actions right now — check the pipeline' }}</p>
            </div>

            <!-- Sections -->
            <div
                v-for="(section, sIdx) in sections" :key="section.key"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                :style="{ transitionDelay: (150 + sIdx * 90) + 'ms' }"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-700 hover:shadow-md"
            >
                <div class="h-1 bg-gradient-to-r" :class="section.accent"></div>
                <div class="p-5">
                    <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2 mb-1">
                        <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" /></svg>
                        {{ isRtl ? section.titleAr : section.titleEn }}
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full border" :class="section.chip">{{ section.items.length }}</span>
                        <span v-if="section.key === 'dormancy' && dormancyMode" class="text-[10px] text-gray-400 font-normal">
                            {{ dormancyMode === 'ai' ? (isRtl ? 'ترتيب بالذكاء الاصطناعي' : 'AI-ranked') : (isRtl ? 'ترتيب آلي' : 'auto-ranked') }}
                        </span>
                    </h2>

                    <ul class="divide-y divide-gray-50">
                        <!-- Follow-up rows -->
                        <template v-if="section.kind === 'followups'">
                            <li v-for="f in section.items" :key="'f' + f.id" class="py-2.5 flex items-center justify-between gap-3 group">
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border shrink-0" :class="section.chip">{{ ft(f.type) }}</span>
                                    <Link :href="`/admin/leads/${f.lead.id}`" class="text-sm font-semibold text-gray-800 hover:text-[#C4A265] truncate transition-colors">{{ f.lead.full_name }}</Link>
                                    <span class="text-[11px] text-gray-400 shrink-0">
                                        {{ section.key === 'overdue' ? (isRtl ? `متأخرة ${overdueAgo(f.scheduled_at)}` : `${overdueAgo(f.scheduled_at)} late`) : fmtTime(f.scheduled_at) }}
                                    </span>
                                    <span v-if="f.notes" class="text-[11px] text-gray-400 truncate hidden md:inline">{{ f.notes }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a v-if="f.lead.phone" :href="`tel:${f.lead.phone}`" :aria-label="isRtl ? 'اتصال' : 'Call'" :title="isRtl ? 'اتصال' : 'Call'"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </a>
                                    <WhatsAppLink :phone="f.lead.phone" :is-rtl="isRtl" size="w-4 h-4" />
                                    <button v-if="can('leads.update')" type="button" @click="completeFollowUp(f)" :disabled="completing === f.id"
                                        class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg text-emerald-700 bg-emerald-50 border border-emerald-100 hover:bg-emerald-100 transition-colors disabled:opacity-50">
                                        {{ completing === f.id ? '…' : (isRtl ? 'تمت' : 'Done') }}
                                    </button>
                                </div>
                            </li>
                        </template>

                        <!-- Lead rows (SLA / hot) -->
                        <template v-else-if="section.kind === 'leads'">
                            <li v-for="l in section.items" :key="'l' + l.id" class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <span v-if="l.source" class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: l.source.color || '#94a3b8' }"></span>
                                    <Link :href="`/admin/leads/${l.id}`" class="text-sm font-semibold text-gray-800 hover:text-[#C4A265] truncate transition-colors">{{ l.full_name }}</Link>
                                    <span class="text-[11px] text-gray-400 shrink-0">
                                        {{ section.key === 'sla'
                                            ? (isRtl ? `ينتظر ${fmtAgo(l.waiting_minutes)}` : `waiting ${fmtAgo(l.waiting_minutes)}`)
                                            : (l.assigned_user?.name || (isRtl ? 'غير معين' : 'unassigned')) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a v-if="l.phone" :href="`tel:${l.phone}`" :aria-label="isRtl ? 'اتصال' : 'Call'" :title="isRtl ? 'اتصال' : 'Call'"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                    </a>
                                    <WhatsAppLink :phone="l.phone" :is-rtl="isRtl" size="w-4 h-4" />
                                    <Link :href="`/admin/leads/${l.id}`" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg text-[#1B365D] bg-slate-50 border border-slate-100 hover:bg-slate-100 transition-colors">
                                        {{ isRtl ? 'فتح' : 'Open' }}
                                    </Link>
                                </div>
                            </li>
                        </template>

                        <!-- Dormancy rows -->
                        <template v-else>
                            <li v-for="l in section.items" :key="'d' + l.id" class="py-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0 flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="l.risk === 'high' ? 'bg-red-500' : 'bg-amber-400'"></span>
                                    <Link :href="`/admin/leads/${l.id}`" class="text-sm font-semibold text-gray-800 hover:text-[#C4A265] truncate transition-colors">{{ l.full_name }}</Link>
                                    <span class="text-[11px] text-gray-400 truncate hidden sm:inline">{{ l.reason }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <WhatsAppLink :phone="l.phone" :is-rtl="isRtl" size="w-4 h-4" />
                                    <Link :href="`/admin/leads/${l.id}`" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg text-[#1B365D] bg-slate-50 border border-slate-100 hover:bg-slate-100 transition-colors">
                                        {{ isRtl ? 'فتح' : 'Open' }}
                                    </Link>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
