<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({ rules: Array });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    name_en: '', name_ar: '', event: '', points: 0, description: '', is_active: true,
});

function openCreate() {
    editingId.value = null;
    form.reset();
    form.is_active = true;
    showForm.value = true;
}

function openEdit(rule) {
    editingId.value = rule.id;
    form.name_en = rule.name_en;
    form.name_ar = rule.name_ar;
    form.event = rule.event;
    form.points = rule.points;
    form.description = rule.description || '';
    form.is_active = rule.is_active;
    showForm.value = true;
}

function cancel() {
    showForm.value = false;
    editingId.value = null;
    form.reset();
}

function submit() {
    if (editingId.value) {
        form.post(`/admin/scoring-rules/${editingId.value}`, {
            onSuccess: () => { cancel(); },
        });
    } else {
        form.post('/admin/scoring-rules', {
            onSuccess: () => { cancel(); },
        });
    }
}

function deleteRule(id) {
    if (confirm('Delete this scoring rule?')) {
        router.post(`/admin/scoring-rules/${id}/delete`);
    }
}

function pointsColor(points) {
    if (points > 0) return 'text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200/60';
    if (points < 0) return 'text-red-700 bg-red-50 ring-1 ring-red-200/60';
    return 'text-gray-600 bg-gray-50 ring-1 ring-gray-200/60';
}

function pointsBarWidth(points) {
    const maxPts = 20;
    return Math.min(Math.abs(points) / maxPts * 100, 100);
}

function pointsBgBar(points) {
    const width = Math.max(pointsBarWidth(points), 8);
    if (points > 0) return { width: width + '%', background: 'linear-gradient(90deg, #10b981, #34d399)' };
    if (points < 0) return { width: width + '%', background: 'linear-gradient(90deg, #ef4444, #f87171)' };
    return { width: '8%', background: '#e5e7eb' };
}

function toggleActive(rule) {
    const toggleForm = useForm({
        name_en: rule.name_en,
        name_ar: rule.name_ar,
        event: rule.event,
        points: rule.points,
        description: rule.description || '',
        is_active: !rule.is_active,
    });
    toggleForm.post(`/admin/scoring-rules/${rule.id}`);
}

const eventCategories = {
    call: { label: 'Calls', color: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60' },
    appointment: { label: 'Appointments', color: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60' },
    booking: { label: 'Bookings', color: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60' },
    conversion: { label: 'Conversions', color: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60' },
    engagement: { label: 'Engagement', color: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60' },
    default: { label: 'Other', color: 'bg-gray-50 text-gray-600 ring-1 ring-gray-200/60' },
};

function getEventCategory(event) {
    if (!event) return eventCategories.default;
    const lower = event.toLowerCase();
    if (lower.includes('call')) return eventCategories.call;
    if (lower.includes('appointment')) return eventCategories.appointment;
    if (lower.includes('book')) return eventCategories.booking;
    if (lower.includes('convert') || lower.includes('won') || lower.includes('sale')) return eventCategories.conversion;
    if (lower.includes('visit') || lower.includes('click') || lower.includes('open') || lower.includes('reply') || lower.includes('respond')) return eventCategories.engagement;
    return eventCategories.default;
}
</script>

<template>
    <AdminLayout :title="$t('a_scoring_rules')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_scoring_rules') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_scoring_rules_description') }}</p>
                </div>
                <button v-if="can('leads.update')" @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-lg shadow-[#C4A265]/25 hover:shadow-xl hover:shadow-[#C4A265]/35 hover:-translate-y-0.5 active:translate-y-0"
                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>{{ $t('a_add_rule') }}</button>
            </div>

            <!-- Inline Form -->
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2 scale-[0.99]" enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0 scale-100" leave-to-class="opacity-0 -translate-y-2 scale-[0.99]">
                <div v-if="showForm" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="h-1" style="background: linear-gradient(90deg, #C4A265, #D4B87A, #C4A265);"></div>
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-[#C4A265]/20"
                                style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ editingId ? 'Edit' : 'New' }} Scoring Rule</h3>
                                <p class="text-xs text-gray-400">{{ $t('a_scoring_rules_description') }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name_en') }}<span class="text-red-400">*</span></label>
                                    <input v-model="form.name_en" type="text"
                                        class="doctorato-input w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                        :placeholder="$t('a_scoring_rule_placeholder')" />
                                    <p v-if="form.errors.name_en" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.name_en }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_name_ar') }}<span class="text-red-400">*</span></label>
                                    <input v-model="form.name_ar" type="text" dir="rtl"
                                        class="doctorato-input w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200" />
                                    <p v-if="form.errors.name_ar" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.name_ar }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_event_key') }}<span class="text-red-400">*</span></label>
                                    <input v-model="form.event" type="text" :disabled="!!editingId"
                                        class="doctorato-input w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 disabled:bg-gray-100 disabled:cursor-not-allowed font-mono"
                                        placeholder="e.g. call_answered" />
                                    <p v-if="form.errors.event" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.event }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_points') }}<span class="text-red-400">*</span></label>
                                    <input v-model="form.points" type="number" min="-100" max="100"
                                        class="doctorato-input w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 font-mono" />
                                    <!-- Points Scale Indicator -->
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-[10px] font-mono text-red-400">-</span>
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden relative">
                                            <div class="absolute inset-y-0 ltr:left-1 rtl:right-1/2 w-0.5 bg-gray-300"></div>
                                            <div v-if="form.points > 0" class="absolute inset-y-0 ltr:left-1 rtl:right-1/2 bg-emerald-400 ltr:rounded-r- rtl:rounded-l-full transition-all duration-300" :style="{ width: pointsBarWidth(form.points) / 2 + '%' }"></div>
                                            <div v-if="form.points < 0" class="absolute inset-y-0 bg-red-400 ltr:rounded-l- ltr:rounded-r- rtl:rounded-l-full transition-all duration-300" :style="{ width: pointsBarWidth(form.points) / 2 + '%', right: '50%' }"></div>
                                        </div>
                                        <span class="text-[10px] font-mono text-emerald-400">+</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_description') }}</label>
                                    <input v-model="form.description" type="text"
                                        class="doctorato-input w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                        :placeholder="$t('a_brief_description')" />
                                </div>
                                <div class="flex items-end">
                                    <!-- Toggle Switch -->
                                    <label class="flex items-center gap-3 cursor-pointer mb-3">
                                        <button type="button" @click="form.is_active = !form.is_active"
                                            :class="form.is_active ? 'bg-[#C4A265]' : 'bg-gray-300'"
                                            class="relative w-11 h-6 rounded-full transition-colors duration-200 flex-shrink-0">
                                            <span :class="form.is_active ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0.5'"
                                                class="block w-5 h-5 rounded-full bg-white shadow-sm transform transition-transform duration-200 mt-0.5"></span>
                                        </button>
                                        <span class="text-sm font-medium" :class="form.is_active ? 'text-gray-800' : 'text-gray-400'">{{ form.is_active ? $t('a_active') : $t('a_inactive') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" @click="cancel"
                                    class="px-5 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition-all duration-200">{{ $t('a_cancel') }}</button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-4 md:px-6 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-300 shadow-lg shadow-[#C4A265]/20 hover:shadow-xl hover:shadow-[#C4A265]/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                    style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                    <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    {{ editingId ? $t('a_update_rule') : $t('a_create_rule') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>

            <!-- Rules Table -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-100 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="h-1" style="background: linear-gradient(90deg, #C4A265, #D4B87A, #C4A265);"></div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="ltr:text-left rtl:text-right text-[10px] text-gray-400 uppercase tracking-wider border-b border-gray-100 bg-gray-50/60">
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_event') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_name_en') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_name_ar') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_category') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_points') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold ltr:text-right rtl:text-left">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(rule, idx) in rules" :key="rule.id"
                                :style="{ transitionDelay: (idx * 30) + 'ms' }"
                                :class="mounted ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-4'"
                                class="transition-all duration-300 hover:bg-gradient-to-r hover:from-[#C4A265]/[0.03] hover:to-transparent group">
                                <td class="px-4 md:px-6 py-4">
                                    <code class="inline-flex items-center gap-1.5 text-xs bg-gray-800 text-gray-300 px-3 py-1.5 rounded-lg font-mono">
                                        <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        {{ rule.event }}
                                    </code>
                                </td>
                                <td class="px-4 md:px-6 py-4 font-semibold text-gray-800">{{ rule.name_en }}</td>
                                <td class="px-4 md:px-6 py-4 text-gray-600" dir="rtl">{{ rule.name_ar }}</td>
                                <td class="px-4 md:px-6 py-4">
                                    <span :class="getEventCategory(rule.event).color"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold">
                                        {{ getEventCategory(rule.event).label }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <span :class="pointsColor(rule.points)"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold font-mono min-w-[3rem] justify-center">
                                            {{ rule.points > 0 ? '+' : '' }}{{ rule.points }}
                                        </span>
                                        <!-- Visual score bar -->
                                        <div class="w-16 h-1 bg-gray-100 rounded-full overflow-hidden relative">
                                            <div class="absolute inset-y-0 ltr:left-1 rtl:right-1/2 w-px bg-gray-200"></div>
                                            <div v-if="rule.points > 0" class="absolute inset-y-0 ltr:left-1 rtl:right-1/2 ltr:rounded-r- rtl:rounded-l-full transition-all duration-500" :style="{ width: pointsBarWidth(rule.points) / 2 + '%', background: 'linear-gradient(90deg, #10b981, #34d399)' }"></div>
                                            <div v-if="rule.points < 0" class="absolute inset-y-0 ltr:rounded-l- ltr:rounded-r- rtl:rounded-l-full transition-all duration-500" :style="{ width: pointsBarWidth(rule.points) / 2 + '%', right: '50%', background: 'linear-gradient(90deg, #ef4444, #f87171)' }"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <!-- Toggle Switch -->
                                    <button v-if="can('leads.update')" @click="toggleActive(rule)"
                                        :class="rule.is_active ? 'bg-[#C4A265]' : 'bg-gray-300'"
                                        class="relative rounded-full transition-colors duration-200 inline-flex items-center"
                                        :title="rule.is_active ? 'Click to deactivate' : 'Click to activate'"
                                        style="width: 2.5rem; height: 1.375rem;">
                                        <span :class="rule.is_active ? 'translate-x-[1.125rem]' : 'translate-x-0.5'"
                                            class="block w-4 h-4 rounded-full bg-white shadow-sm transform transition-transform duration-200"></span>
                                    </button>
                                    <span v-else :class="rule.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60' : 'bg-gray-100 text-gray-500 ring-1 ring-gray-200/60'"
                                        class="px-2.5 py-1 text-[10px] font-bold rounded-full">
                                        {{ rule.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4 ltr:text-right rtl:text-left">
                                    <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button v-if="can('leads.update')" @click="openEdit(rule)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-[#C4A265] hover:bg-[#C4A265]/5 transition-all duration-200" :title="$t('a_edit')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button v-if="can('leads.update')" @click="deleteRule(rule.id)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                    <!-- Always visible on mobile -->
                                    <div class="flex items-center justify-end gap-1.5 sm:hidden">
                                        <button v-if="can('leads.update')" @click="openEdit(rule)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-[#C4A265] transition-all duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button v-if="can('leads.update')" @click="deleteRule(rule.id)"
                                            class="p-2 rounded-lg text-gray-400 hover:text-red-500 transition-all duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!rules?.length" class="px-4 md:px-6 py-24 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">{{ $t('a_no_scoring_rules') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $t('a_create_first_scoring_rule_hint') }}</p>
                </div>
            </div>

            <!-- Info Box -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-200 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="h-1 bg-gradient-to-r from-amber-400 via-amber-300 to-amber-400"></div>
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-2">{{ $t('a_how_scoring_works') }}</h4>
                            <ul class="text-xs text-gray-500 space-y-2 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-300 mt-1.5 flex-shrink-0"></span>
                                    Each event triggers automatically when the corresponding action occurs (e.g., a call is answered, an appointment is booked)
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-emerald-400 mt-1.5 flex-shrink-0"></span>
                                    Positive points increase the lead's score, indicating higher quality or engagement
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></span>
                                    Negative points decrease the score for events like missed calls or unresponsiveness
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-300 mt-1.5 flex-shrink-0"></span>
                                    The total score helps prioritize which leads to focus on first
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-300 mt-1.5 flex-shrink-0"></span>
                                    Disable a rule (toggle Active off) to stop it from affecting new leads without deleting it
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
