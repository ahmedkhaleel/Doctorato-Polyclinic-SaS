<script setup>
import { ref, onMounted , computed } from 'vue';
import { useForm, router , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();

const props = defineProps({ rules: Array, sources: Array, users: Array });

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '', rule_type: 'source_based', lead_source_id: '',
    assign_to_user_id: '', priority: 10, is_active: true,
});

const ruleTypes = [
    { value: 'source_based', label: 'Source Based', desc: 'Assign leads from a specific source to a user', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', color: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60' },
    { value: 'round_robin', label: 'Round Robin', desc: 'Distribute leads evenly across team members', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', color: 'bg-slate-50 text-[#1B365D] ring-1 ring-slate-200/60' },
    { value: 'manual', label: 'Manual Fallback', desc: 'Default assignment when no other rule matches', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', color: 'bg-gray-100 text-gray-600 ring-1 ring-gray-200/60' },
];

function getRuleType(value) {
    return ruleTypes.find(r => r.value === value) || ruleTypes[2];
}

function openCreate() {
    editingId.value = null;
    form.reset();
    form.rule_type = 'source_based';
    form.priority = 10;
    form.is_active = true;
    showForm.value = true;
}

function openEdit(rule) {
    editingId.value = rule.id;
    form.name = rule.name;
    form.rule_type = rule.rule_type;
    form.lead_source_id = rule.lead_source_id || '';
    form.assign_to_user_id = rule.assign_to_user_id;
    form.priority = rule.priority;
    form.is_active = rule.is_active;
    showForm.value = true;
}

function cancel() {
    showForm.value = false;
    editingId.value = null;
    form.reset();
}

function submit() {
    const url = editingId.value
        ? `/admin/assignment-rules/${editingId.value}`
        : '/admin/assignment-rules';
    form.post(url, { onSuccess: () => { cancel(); } });
}

function deleteRule(id) {
    if (confirm('Delete this assignment rule?')) {
        router.post(`/admin/assignment-rules/${id}/delete`);
    }
}

function toggleActive(rule) {
    const toggleForm = useForm({
        name: rule.name,
        rule_type: rule.rule_type,
        lead_source_id: rule.lead_source_id || '',
        assign_to_user_id: rule.assign_to_user_id,
        priority: rule.priority,
        is_active: !rule.is_active,
    });
    toggleForm.post(`/admin/assignment-rules/${rule.id}`);
}

function priorityColor(priority) {
    if (priority >= 80) return { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-200/60', label: 'Critical' };
    if (priority >= 50) return { bg: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-200/60', label: 'High' };
    if (priority >= 20) return { bg: 'bg-slate-50', text: 'text-[#1B365D]', ring: 'ring-slate-200/60', label: 'Medium' };
    return { bg: 'bg-gray-50', text: 'text-gray-600', ring: 'ring-gray-200/60', label: 'Low' };
}
</script>

<template>
    <AdminLayout :title="$t('a_assignment_rules')">
        <div class="space-y-6">
            <!-- Header -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 ease-out flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ $t('a_assignment_rules') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_assignment_rules_description') }}</p>
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
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ editingId ? 'Edit' : 'New' }} Assignment Rule</h3>
                                <p class="text-xs text-gray-400">{{ $t('a_assignment_rules_description') }}</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Rule Name - Full Width -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_rule_name') }}<span class="text-red-400">*</span></label>
                                <input v-model="form.name" type="text"
                                    class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200"
                                    :placeholder="$t('a_assignment_rule_placeholder')" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.name }}</p>
                            </div>

                            <!-- Rule Type Selection Cards -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $t('a_rule_type') }}<span class="text-red-400">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <button v-for="rt in ruleTypes" :key="rt.value" type="button" @click="form.rule_type = rt.value"
                                        :class="form.rule_type === rt.value
                                            ? 'border-[#C4A265] ring-2 ring-[#C4A265]/20 bg-[#C4A265]/[0.03]'
                                            : 'border-gray-200/80 bg-gray-50/50 hover:bg-gray-50 hover:border-gray-300'"
                                        class="px-4 py-4 border rounded-xl transition-all duration-200 ltr:text-left rtl:text-right group relative">
                                        <div v-if="form.rule_type === rt.value" class="absolute top-2.5 ltr:right-2.5 rtl:left-2.5 w-5 h-5 rounded-full flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <div :class="form.rule_type === rt.value ? 'bg-[#C4A265]/10 text-[#C4A265] scale-110' : 'bg-gray-100 text-gray-400 group-hover:bg-gray-200'"
                                                class="w-9 h-9 rounded-lg flex items-center justify-center transition-all duration-300 flex-shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="rt.icon" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold" :class="form.rule_type === rt.value ? 'text-[#C4A265]' : 'text-gray-600'">{{ rt.label }}</p>
                                                <p class="text-[10px] text-gray-400 mt-0.5 leading-relaxed">{{ rt.desc }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Fields Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_lead_source') }}</label>
                                    <select v-model="form.lead_source_id"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                                        <option value="">{{ $t('a_any_source') }}</option>
                                        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                                    </select>
                                    <p class="text-xs text-gray-400 mt-1.5">{{ $t('a_leave_empty_all_sources') }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_assign_to') }}<span class="text-red-400">*</span></label>
                                    <select v-model="form.assign_to_user_id"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 appearance-none cursor-pointer">
                                        <option value="">Select user...</option>
                                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                    <p v-if="form.errors.assign_to_user_id" class="text-xs text-red-500 mt-1.5 font-medium">{{ form.errors.assign_to_user_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $t('a_priority') }}</label>
                                    <input v-model="form.priority" type="number" min="0" max="100"
                                        class="w-full px-4 py-3 text-sm bg-gray-50/80 border border-gray-200/80 rounded-xl focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#C4A265] focus:bg-white transition-all duration-200 font-mono" />
                                    <p class="text-xs text-gray-400 mt-1.5">{{ $t('a_higher_priority_hint') }}</p>
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
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_rule') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_type') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_source') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold">{{ $t('a_assign_to') }}</th>
                                <th class="px-4 md:px-6 py-4 font-semibold text-center">{{ $t('a_priority') }}</th>
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
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                            :class="getRuleType(rule.rule_type).color">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getRuleType(rule.rule_type).icon" /></svg>
                                        </div>
                                        <span class="font-semibold text-gray-800">{{ rule.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <span :class="getRuleType(rule.rule_type).color"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold capitalize">
                                        {{ rule.rule_type?.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div v-if="rule.lead_source" class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full ring-2 ring-white shadow-sm" :style="{ backgroundColor: rule.lead_source.color }"></span>
                                        <span class="text-gray-700 text-sm font-medium">{{ rule.lead_source.name_en }}</span>
                                    </div>
                                    <span v-else class="inline-flex items-center gap-1 text-gray-400 text-xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>{{ $t('a_any_source') }}</span>
                                </td>
                                <td class="px-4 md:px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[10px] font-bold shadow-sm flex-shrink-0"
                                            style="background: linear-gradient(135deg, #C4A265, #D4B87A);">
                                            {{ rule.assign_to_user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </div>
                                        <span class="text-gray-700 text-sm font-medium">{{ rule.assign_to_user?.name || '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 md:px-6 py-4 text-center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span :class="[priorityColor(rule.priority).bg, priorityColor(rule.priority).text, 'ring-1', priorityColor(rule.priority).ring]"
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold font-mono min-w-[2.5rem] justify-center">
                                            {{ rule.priority }}
                                        </span>
                                        <span class="text-[9px] text-gray-400 font-medium">{{ priorityColor(rule.priority).label }}</span>
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
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 font-semibold">{{ $t('a_no_assignment_rules') }}</p>
                    <p class="text-xs text-gray-400 mt-1">New leads will not be auto-assigned until you create a rule</p>
                </div>
            </div>

            <!-- Info Box -->
            <div :class="mounted ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
                class="transition-all duration-500 delay-200 ease-out bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="h-1 bg-gradient-to-r from-slate-400 via-slate-300 to-slate-400"></div>
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-[#1B365D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800 mb-2">{{ $t('a_how_assignment_works') }}</h4>
                            <ul class="text-xs text-gray-500 space-y-2 leading-relaxed">
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-300 mt-1.5 flex-shrink-0"></span>
                                    When a new lead is created (from website contact form or manual entry), the system checks active rules by priority (highest first)
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-slate-400 mt-1.5 flex-shrink-0"></span>
                                    <span><strong class="text-gray-700">Source Based:</strong> Matches leads from a specific source (e.g., all Facebook leads go to a specific team member)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-slate-400 mt-1.5 flex-shrink-0"></span>
                                    <span><strong class="text-gray-700">Round Robin:</strong> Distributes leads evenly (future enhancement)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-400 mt-1.5 flex-shrink-0"></span>
                                    <span><strong class="text-gray-700">Manual Fallback:</strong> Used when no source-based rule matches (set source to "Any")</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-300 mt-1.5 flex-shrink-0"></span>
                                    Rules only apply to leads without an existing assignee
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
