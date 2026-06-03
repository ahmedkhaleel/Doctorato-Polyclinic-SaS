<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useConfirm } from '@/Composables/useConfirm.js';

const { can } = usePermissions();
const { confirm } = useConfirm();

const props = defineProps({ templates: Object, filters: Object });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
const statsAnimated = ref(false);
onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
    setTimeout(() => statsAnimated.value = true, 400);
});

const channelFilter = ref(props.filters?.channel || '');
const categoryFilter = ref(props.filters?.category || '');
const hoveredCard = ref(null);
const expandedCard = ref(null);

const channels = [
    { value: 'whatsapp', label: 'WhatsApp', gradient: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-200', glow: 'shadow-emerald-500/30', strip: 'from-emerald-400 to-emerald-500', iconFill: true, icon: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z' },
    { value: 'sms', label: 'SMS', gradient: 'from-[#1B365D] to-[#1B365D]', bg: 'bg-slate-50', text: 'text-[#1B365D]', ring: 'ring-slate-200', glow: 'shadow-[#1B365D]/30', strip: 'from-slate-400 to-[#1B365D]', iconFill: false, icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z' },
    { value: 'email', label: 'Email', gradient: 'from-[#1B365D] to-[#1B365D]', bg: 'bg-slate-50', text: 'text-[#1B365D]', ring: 'ring-slate-200', glow: 'shadow-[#1B365D]/30', strip: 'from-slate-400 to-[#1B365D]', iconFill: false, icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
];

const categories = [
    { value: 'welcome', label: 'Welcome', gradient: 'from-amber-400 to-[#C4A265]', icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
    { value: 'follow_up', label: 'Follow Up', gradient: 'from-slate-400 to-teal-500', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
    { value: 'appointment_reminder', label: 'Reminder', gradient: 'from-amber-400 to-[#C4A265]', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { value: 'promotion', label: 'Promotion', gradient: 'from-slate-400 to-[#1B365D]', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { value: 're_engagement', label: 'Re-engage', gradient: 'from-lime-400 to-emerald-500', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { value: 'thank_you', label: 'Thank You', gradient: 'from-amber-400 to-amber-500', icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' },
    { value: 'custom', label: 'Custom', gradient: 'from-slate-400 to-gray-500', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
];

/* Computed Stats */
const totalTemplates = computed(() => props.templates?.total || props.templates?.data?.length || 0);
const activeTemplates = computed(() => props.templates?.data?.filter(t => t.is_active)?.length || 0);
const whatsappCount = computed(() => props.templates?.data?.filter(t => t.channel === 'whatsapp')?.length || 0);
const smsCount = computed(() => props.templates?.data?.filter(t => t.channel === 'sms')?.length || 0);
const emailCount = computed(() => props.templates?.data?.filter(t => t.channel === 'email')?.length || 0);

/* Animated counter */
const animatedValues = ref({ total: 0, active: 0, whatsapp: 0, sms: 0, email: 0 });
function animateCounter(key, target) {
    const duration = 800;
    const start = 0;
    const startTime = performance.now();
    function step(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        animatedValues.value[key] = Math.round(start + (target - start) * eased);
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}
watch(statsAnimated, (val) => {
    if (val) {
        animateCounter('total', totalTemplates.value);
        animateCounter('active', activeTemplates.value);
        animateCounter('whatsapp', whatsappCount.value);
        animateCounter('sms', smsCount.value);
        animateCounter('email', emailCount.value);
    }
});

function applyFilters() {
    router.get('/admin/templates', {
        channel: channelFilter.value || undefined,
        category: categoryFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

watch([channelFilter, categoryFilter], applyFilters);

function channelMeta(ch) {
    return channels.find(c => c.value === ch) || { label: ch, gradient: 'from-gray-400 to-gray-500', bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-200', glow: '', strip: 'from-gray-300 to-gray-400', iconFill: false, icon: '' };
}

function categoryMeta(cat) {
    return categories.find(c => c.value === cat) || { label: cat, gradient: 'from-slate-400 to-gray-500', icon: '' };
}

function truncateBody(body, len = 80) {
    if (!body) return '';
    const clean = body.replace(/\n/g, ' ');
    return clean.length > len ? clean.substring(0, len) + '...' : clean;
}

function deleteTemplate(id) {
    confirm('Are you sure you want to delete this template?', () => {
        router.post(`/admin/templates/${id}/delete`);
    });
}
</script>

<template>
    <AdminLayout :title="$t('a_communication_templates')">
        <div class="space-y-8">

            <!-- ===== HERO HEADER ===== -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-4 md:p-8 md:p-10"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);">

                <!-- Floating decorative shapes -->
                <svg class="absolute top-0 right-0 w-96 h-96 opacity-[0.04] animate-spin" style="animation-duration: 60s;" viewBox="0 0 200 200" fill="none">
                    <path d="M100 0C155.228 0 200 44.772 200 100C200 155.228 155.228 200 100 200C44.7715 200 0 155.228 0 100C0 44.772 44.7715 0 100 0Z" stroke="#C4A265" stroke-width="0.5"/>
                    <path d="M100 20C144.183 20 180 55.817 180 100C180 144.183 144.183 180 100 180C55.8172 180 20 144.183 20 100C20 55.817 55.8172 20 100 20Z" stroke="#C4A265" stroke-width="0.5"/>
                    <path d="M100 40C133.137 40 160 66.863 160 100C160 133.137 133.137 160 100 160C66.8629 160 40 133.137 40 100C40 66.863 66.8629 40 100 40Z" stroke="#C4A265" stroke-width="0.5"/>
                </svg>
                <svg class="absolute -bottom-10 -left-10 w-72 h-72 opacity-[0.03]" viewBox="0 0 200 200" fill="none">
                    <polygon points="100,10 190,60 190,140 100,190 10,140 10,60" stroke="#C4A265" stroke-width="0.8" fill="none">
                        <animateTransform attributeName="transform" type="rotate" from="0 100 100" to="360 100 100" dur="80s" repeatCount="indefinite"/>
                    </polygon>
                    <polygon points="100,30 170,70 170,130 100,170 30,130 30,70" stroke="#C4A265" stroke-width="0.5" fill="none">
                        <animateTransform attributeName="transform" type="rotate" from="360 100 100" to="0 100 100" dur="60s" repeatCount="indefinite"/>
                    </polygon>
                </svg>
                <!-- Gradient orb -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full opacity-20"
                    style="background: radial-gradient(circle, #C4A265 0%, transparent 70%); filter: blur(80px);"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#C4A265] to-[#D4B87A] flex items-center justify-center shadow-lg shadow-[#C4A265]/30">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                            </div>
                            <div class="h-8 w-px bg-gradient-to-b from-transparent via-[#C4A265]/40 to-transparent"></div>
                            <span class="text-[#C4A265]/80 text-xs font-semibold tracking-widest uppercase">CRM</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white tracking-tight">{{ $t('a_communication_templates') }}</h1>
                        <p class="text-gray-400 mt-2 text-sm max-w-lg leading-relaxed">{{ $t('a_manage_templates_description') }}</p>
                    </div>
                    <Link v-if="can('communication_templates.create')" href="/admin/templates/create"
                        class="group inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl text-gray-900 text-sm font-bold shadow-xl shadow-[#C4A265]/25 hover:shadow-2xl hover:shadow-[#C4A265]/40 hover:-translate-y-1 active:translate-y-0 transition-all duration-300"
                        style="background: linear-gradient(135deg, #C4A265 0%, #D4B87A 50%, #E8D5A8 100%);">
                        <svg class="w-5 h-5 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        {{ $t('a_new_template') }}
                    </Link>
                </div>
            </div>

            <!-- ===== STATS STRIP ===== -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 150ms;">
                <!-- Total -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-gray-100 p-5 group hover:shadow-lg hover:shadow-gray-200/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-bold text-gray-900 tabular-nums">{{ animatedValues.total }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-1">{{ $t('a_total') }}</p>
                    </div>
                </div>
                <!-- Active -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-gray-100 p-5 group hover:shadow-lg hover:shadow-emerald-100/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-bold text-emerald-600 tabular-nums">{{ animatedValues.active }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-1">{{ $t('a_active') }}</p>
                    </div>
                </div>
                <!-- WhatsApp -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-gray-100 p-5 group hover:shadow-lg hover:shadow-emerald-100/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-bold text-emerald-600 tabular-nums">{{ animatedValues.whatsapp }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-1">WhatsApp</p>
                    </div>
                </div>
                <!-- SMS -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-gray-100 p-5 group hover:shadow-lg hover:shadow-slate-100/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-50/50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-bold text-[#1B365D] tabular-nums">{{ animatedValues.sms }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-1">SMS</p>
                    </div>
                </div>
                <!-- Email -->
                <div class="col-span-2 md:col-span-1 relative overflow-hidden rounded-2xl bg-white border border-gray-100 p-5 group hover:shadow-lg hover:shadow-slate-100/50 hover:-translate-y-0.5 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-50/50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <p class="text-2xl md:text-3xl font-bold text-[#1B365D] tabular-nums">{{ animatedValues.email }}</p>
                        <p class="text-xs text-gray-400 font-medium mt-1">Email</p>
                    </div>
                </div>
            </div>

            <!-- ===== CATEGORY PILLS ===== -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 250ms;">
                <div class="flex items-center gap-3 mb-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_category') }}</h3>
                    <div class="flex-1 h-px bg-gradient-to-r from-gray-200 to-transparent" :class="isRtl ? 'bg-gradient-to-l' : ''"></div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="categoryFilter = ''"
                        :class="categoryFilter === '' ? 'bg-gray-900 text-white shadow-lg shadow-gray-900/20 scale-105' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300 hover:text-gray-700'"
                        class="px-4 py-2 text-xs font-bold rounded-xl transition-all duration-300">
                        {{ $t('a_all_categories') }}
                    </button>
                    <button v-for="cat in categories" :key="cat.value" @click="categoryFilter = categoryFilter === cat.value ? '' : cat.value"
                        :class="categoryFilter === cat.value
                            ? `bg-gradient-to-r ${cat.gradient} text-white shadow-lg scale-105`
                            : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300 hover:text-gray-700'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold rounded-xl transition-all duration-300">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="cat.icon" /></svg>
                        {{ cat.label }}
                    </button>
                </div>
            </div>

            <!-- ===== CHANNEL SEGMENTED CONTROL ===== -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 350ms;">
                <div class="flex items-center gap-3 mb-4">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ $t('a_channel') }}</h3>
                    <div class="flex-1 h-px bg-gradient-to-r from-gray-200 to-transparent" :class="isRtl ? 'bg-gradient-to-l' : ''"></div>
                </div>
                <div class="inline-flex items-center bg-gray-100/80 backdrop-blur-sm rounded-2xl p-1.5 gap-1">
                    <button @click="channelFilter = ''"
                        :class="channelFilter === ''
                            ? 'bg-white text-gray-900 shadow-md ring-1 ring-gray-200/50'
                            : 'text-gray-400 hover:text-gray-600'"
                        class="px-5 py-2.5 text-xs font-bold rounded-xl transition-all duration-300">
                        {{ $t('a_all_channels') }}
                    </button>
                    <button v-for="ch in channels" :key="ch.value" @click="channelFilter = channelFilter === ch.value ? '' : ch.value"
                        :class="channelFilter === ch.value
                            ? `bg-gradient-to-r ${ch.gradient} text-white shadow-lg ${ch.glow}`
                            : 'text-gray-400 hover:text-gray-600'"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-bold rounded-xl transition-all duration-300">
                        <svg v-if="ch.iconFill" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path :d="ch.icon"/></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="ch.icon"/></svg>
                        {{ ch.label }}
                    </button>
                </div>
            </div>

            <!-- ===== TEMPLATE CARD GRID ===== -->
            <div v-if="templates.data?.length"
                class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
                :class="mounted ? 'opacity-100' : 'opacity-0'"
                style="transition: opacity 0.5s ease;">

                <div v-for="(tpl, idx) in templates.data" :key="tpl.id"
                    class="group relative bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-gray-200/60 hover:-translate-y-1 transition-all duration-500"
                    :class="mounted ? 'opacity-100 translate-y-0 scale-100' : 'opacity-0 translate-y-8 scale-95'"
                    :style="{ transitionDelay: (200 + idx * 80) + 'ms', transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)' }"
                    @mouseenter="hoveredCard = tpl.id" @mouseleave="hoveredCard = null">

                    <!-- Channel color strip at top -->
                    <div class="h-1.5 w-full bg-gradient-to-r" :class="channelMeta(tpl.channel).strip"></div>

                    <!-- Channel icon badge floating top-right -->
                    <div class="absolute top-5 ltr:right-4 rtl:left-4 z-10">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:shadow-xl"
                            :class="channelMeta(tpl.channel).gradient">
                            <svg v-if="channelMeta(tpl.channel).iconFill" class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path :d="channelMeta(tpl.channel).icon"/></svg>
                            <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="channelMeta(tpl.channel).icon"/></svg>
                        </div>
                    </div>

                    <div class="p-5 pt-4">
                        <!-- Template name -->
                        <h3 class="text-base font-bold text-gray-900 ltr:pr-14 rtl:pl-14 leading-snug group-hover:text-[#C4A265] transition-colors duration-300">{{ tpl.name }}</h3>

                        <!-- Subject line -->
                        <p v-if="tpl.subject" class="text-xs text-gray-400 mt-1 flex items-center gap-1.5 ltr:pr-14 rtl:pl-14">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                            <span class="truncate">{{ tpl.subject }}</span>
                        </p>

                        <!-- Category badge -->
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide text-white bg-gradient-to-r"
                                :class="categoryMeta(tpl.category).gradient">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryMeta(tpl.category).icon" /></svg>
                                {{ categoryMeta(tpl.category).label }}
                            </span>
                        </div>

                        <!-- Body preview -->
                        <div class="mt-3 relative">
                            <p class="text-xs text-gray-500 leading-relaxed transition-all duration-300"
                                :class="expandedCard === tpl.id ? '' : 'line-clamp-2'"
                                @click="expandedCard = expandedCard === tpl.id ? null : tpl.id">
                                {{ tpl.body_en || tpl.body_ar || '' }}
                            </p>
                            <button v-if="(tpl.body_en || tpl.body_ar || '').length > 100"
                                @click="expandedCard = expandedCard === tpl.id ? null : tpl.id"
                                class="text-[10px] text-[#C4A265] font-semibold mt-1 hover:underline">
                                {{ expandedCard === tpl.id ? 'Show less' : 'Show more' }}
                            </button>
                        </div>

                        <!-- Variables tags -->
                        <div v-if="tpl.variables && tpl.variables.length" class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="v in (Array.isArray(tpl.variables) ? tpl.variables : [])" :key="v"
                                class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-[10px] font-mono text-gray-500 ring-1 ring-gray-200/60">
                                {{ '{' + v + '}' }}
                            </span>
                        </div>

                        <!-- Divider -->
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <!-- Left: usage + status -->
                                <div class="flex items-center gap-4">
                                    <!-- Usage count -->
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" /></svg>
                                        <span class="text-xs font-bold text-gray-600 tabular-nums">{{ tpl.usage_count || 0 }}</span>
                                        <span class="text-[10px] text-gray-400">uses</span>
                                    </div>

                                    <!-- Active status -->
                                    <div class="flex items-center gap-1.5">
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span v-if="tpl.is_active" class="absolute inline-flex h-full w-full rounded-full opacity-75 animate-ping"
                                                style="background-color: #22c55e; animation-duration: 2s;"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="tpl.is_active ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                                        </span>
                                        <span class="text-xs font-semibold" :class="tpl.is_active ? 'text-emerald-600' : 'text-gray-400'">
                                            {{ tpl.is_active ? $t('a_active') : $t('a_inactive') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right: actions -->
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-1 group-hover:translate-y-0">
                                    <Link v-if="can('communication_templates.update')" :href="`/admin/templates/${tpl.id}/edit`"
                                        class="p-2 rounded-xl text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/10 transition-all duration-200" :title="$t('a_edit')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </Link>
                                    <button v-if="can('communication_templates.delete')" @click="deleteTemplate(tpl.id)"
                                        class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== EMPTY STATE ===== -->
            <div v-if="!templates.data?.length"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 400ms;"
                class="relative overflow-hidden bg-white rounded-3xl border border-gray-100 p-16 text-center">

                <!-- Decorative background elements -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute top-10 left-10 w-32 h-32 rounded-full bg-[#C4A265]/5 animate-pulse" style="animation-duration: 4s;"></div>
                    <div class="absolute bottom-10 right-10 w-24 h-24 rounded-full bg-[#1B365D]/5 animate-pulse" style="animation-duration: 3s; animation-delay: 1s;"></div>
                    <div class="absolute top-1/2 left-1/4 w-16 h-16 rounded-full bg-emerald-500/5 animate-pulse" style="animation-duration: 5s; animation-delay: 0.5s;"></div>
                    <svg class="absolute top-6 right-1/4 w-6 h-6 text-gray-200 animate-bounce" style="animation-duration: 3s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
                    <svg class="absolute bottom-8 left-1/3 w-5 h-5 text-gray-200 animate-bounce" style="animation-duration: 4s; animation-delay: 1.5s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </div>

                <div class="relative z-10">
                    <!-- Illustrated icon -->
                    <div class="mx-auto w-24 h-24 rounded-3xl bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200/50 flex items-center justify-center mb-6 shadow-sm">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-700">{{ $t('a_no_templates_found') }}</h3>
                    <p class="text-sm text-gray-400 mt-2 max-w-sm mx-auto leading-relaxed">{{ $t('a_no_templates_hint') }}</p>
                    <Link v-if="can('communication_templates.create')" href="/admin/templates/create"
                        class="inline-flex items-center gap-2 mt-6 px-4 md:px-6 py-3 rounded-xl text-white text-sm font-bold shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 hover:-translate-y-0.5 transition-all duration-300"
                        style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        {{ $t('a_new_template') }}
                    </Link>
                </div>
            </div>

            <!-- ===== PAGINATION ===== -->
            <div v-if="templates.links?.length > 3"
                :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                style="transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); transition-delay: 500ms;"
                class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-2xl border border-gray-100 px-4 md:px-6 py-4">
                <p class="text-xs text-gray-400 font-medium">
                    {{ $t('a_showing') }}
                    <span class="text-gray-700 font-bold">{{ templates.from }}-{{ templates.to }}</span>
                    {{ $t('a_of') }}
                    <span class="text-gray-700 font-bold">{{ templates.total }}</span>
                </p>
                <div class="flex items-center gap-1.5">
                    <template v-for="link in templates.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url"
                            :class="link.active
                                ? 'bg-gradient-to-r from-[#C4A265] to-[#D4B87A] text-white shadow-md shadow-[#C4A265]/20 scale-110'
                                : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'"
                            class="min-w-[36px] h-9 px-3 flex items-center justify-center text-xs font-bold rounded-xl transition-all duration-300"
                            v-html="link.label" />
                        <span v-else
                            class="min-w-[36px] h-9 px-3 flex items-center justify-center text-xs text-gray-300 font-medium"
                            v-html="link.label" />
                    </template>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
