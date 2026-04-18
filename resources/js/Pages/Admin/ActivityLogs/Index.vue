<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    logs: Object,
    actions: Array,
    users: Array,
    panels: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const userId = ref(props.filters?.user_id || '');
const action = ref(props.filters?.action || '');
const panel = ref(props.filters?.panel || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const expandedRows = ref({});

let searchTimeout = null;

function applyFilters() {
    router.get('/admin/activity-logs', {
        search: search.value || undefined,
        user_id: userId.value || undefined,
        action: action.value || undefined,
        panel: panel.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([userId, action, panel, dateFrom, dateTo], applyFilters);

function toggleRow(id) {
    expandedRows.value[id] = !expandedRows.value[id];
}

function actionColor(action) {
    if (!action) return 'bg-gray-100 text-gray-600';
    const a = action.toLowerCase();
    if (a === 'login') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (a === 'logout') return 'bg-slate-50 text-slate-700 border border-slate-200';
    if (a === 'failed_login') return 'bg-red-50 text-red-700 border border-red-200';
    if (a === 'started') return 'bg-slate-50 text-[#1B365D] border border-slate-200';
    if (a === 'completed') return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (a === 'cancelled') return 'bg-amber-50 text-[#C4A265] border border-amber-200';
    if (a === 'password_changed') return 'bg-amber-50 text-[#C4A265] border border-amber-200';
    if (a === 'photo_uploaded') return 'bg-slate-50 text-[#1B365D] border border-slate-200';
    if (a === 'duplicated') return 'bg-slate-50 text-[#1B365D] border border-slate-200';
    if (a === 'updated_diagnosis') return 'bg-teal-50 text-teal-700 border border-teal-200';
    if (a.includes('created') || a.includes('create') || a.includes('store')) return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
    if (a.includes('updated') || a.includes('update')) return 'bg-slate-50 text-[#1B365D] border border-slate-200';
    if (a.includes('deleted') || a.includes('delete') || a.includes('destroy')) return 'bg-red-50 text-red-700 border border-red-200';
    if (a.includes('export') || a.includes('download')) return 'bg-amber-50 text-amber-700 border border-amber-200';
    return 'bg-gray-50 text-gray-700 border border-gray-200';
}

function panelColor(panel) {
    if (!panel) return '';
    switch (panel) {
        case 'admin': return 'bg-[#C4A265]/10 text-[#C4A265] border border-[#C4A265]/30';
        case 'secretary': return 'bg-teal-50 text-teal-700 border border-teal-200';
        case 'doctor': return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'webmaster': return 'bg-slate-50 text-[#1B365D] border border-slate-200';
        default: return 'bg-gray-50 text-gray-700 border border-gray-200';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function exportUrl() {
    const params = new URLSearchParams();
    if (dateFrom.value) params.set('date_from', dateFrom.value);
    if (dateTo.value) params.set('date_to', dateTo.value);
    if (userId.value) params.set('user_id', userId.value);
    if (action.value) params.set('action', action.value);
    if (panel.value) params.set('panel', panel.value);
    return '/admin/exports/activity-logs?' + params.toString();
}
</script>

<template>
    <AdminLayout :title="$t('a_activity_logs')">
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $t('a_activity_logs') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_activity_logs_desc') }}</p>
                </div>
                <a
                    :href="exportUrl()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A] text-white text-sm font-medium rounded-xl hover:from-[#A68B52] hover:to-[#C4A265] transition-all duration-200 shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    {{ $t('a_export_excel') }}
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_search') }}</label>
                        <input v-model="search" type="text" :placeholder="$t('a_search_logs_placeholder')"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                    </div>
                    <!-- User -->
                    <div class="w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_user') }}</label>
                        <select v-model="userId"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                            <option value="">{{ $t('a_all_users') }}</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <!-- Action -->
                    <div class="w-44">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_action') }}</label>
                        <select v-model="action"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                            <option value="">{{ $t('a_all_actions') }}</option>
                            <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>
                    <!-- Panel -->
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_panel') }}</label>
                        <select v-model="panel"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                            <option value="">{{ $t('a_all_panels') }}</option>
                            <option v-for="p in panels" :key="p" :value="p" class="capitalize">{{ p }}</option>
                        </select>
                    </div>
                    <!-- Date From -->
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_from') }}</label>
                        <input v-model="dateFrom" type="date" :max="dateTo || undefined"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                    </div>
                    <!-- Date To -->
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('a_to') }}</label>
                        <input v-model="dateTo" type="date" :min="dateFrom || undefined"
                            class="doctorato-input w-full px-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50/80">
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_date_time') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_user') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_panel') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_action') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_description') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_model') }}</th>
                                <th class="px-4 py-3.5 ltr:text-left rtl:text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_ip_address') }}</th>
                                <th class="px-4 py-3.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_details') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="log in logs.data" :key="log.id">
                                <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                    <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-600">{{ formatDate(log.created_at) }}</td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ log.user_name || 'System' }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span v-if="log.panel" :class="panelColor(log.panel)" class="inline-flex px-2 py-0.5 rounded-lg text-[11px] font-semibold capitalize">
                                            {{ log.panel }}
                                        </span>
                                        <span v-else class="text-xs text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span :class="actionColor(log.action)" class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium">
                                            {{ log.action }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 max-w-[280px]">
                                        <span v-if="log.description" class="text-sm text-gray-600 truncate block" :title="log.description">{{ log.description }}</span>
                                        <span v-else class="text-xs text-gray-300">&mdash;</span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-600">
                                        <span v-if="log.model_type_short" class="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ log.model_type_short }}</span>
                                        <span v-if="log.model_id" class="text-gray-400 ml-1">#{{ log.model_id }}</span>
                                        <span v-if="!log.model_type_short" class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-sm text-gray-500 font-mono text-xs">{{ log.ip_address || '-' }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <button
                                            v-if="log.changes || log.user_agent"
                                            @click="toggleRow(log.id)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-lg transition-colors"
                                            :class="expandedRows[log.id] ? 'bg-[#C4A265]/10 text-[#C4A265]' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                        >
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="expandedRows[log.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            {{ $t('a_details') }}
                                        </button>
                                        <span v-else class="text-xs text-gray-300">&mdash;</span>
                                    </td>
                                </tr>
                                <!-- Expanded Details Row -->
                                <tr v-if="expandedRows[log.id] && (log.changes || log.user_agent)">
                                    <td colspan="8" class="px-4 py-4 bg-gray-50/50">
                                        <div class="max-w-5xl space-y-3">
                                            <!-- User Agent -->
                                            <div v-if="log.user_agent" class="flex items-start gap-2">
                                                <span class="text-xs font-semibold text-gray-500 min-w-[80px]">{{ $t('a_browser') }}:</span>
                                                <span class="text-xs text-gray-600 break-all">{{ log.user_agent }}</span>
                                            </div>

                                            <!-- Changes: old/new format -->
                                            <div v-if="log.changes && (log.changes.old || log.changes.new)" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div v-if="log.changes.old && Object.keys(log.changes.old).length">
                                                    <p class="text-xs font-semibold text-red-600 mb-2">{{ $t('a_old_values') }}</p>
                                                    <div class="bg-white rounded-xl border border-red-100 p-3 space-y-1">
                                                        <div v-for="(val, key) in log.changes.old" :key="key" class="flex gap-2 text-xs">
                                                            <span class="font-medium text-gray-600 min-w-[100px]">{{ key }}:</span>
                                                            <span class="text-red-600">{{ val ?? 'null' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="log.changes.new && Object.keys(log.changes.new).length">
                                                    <p class="text-xs font-semibold text-emerald-600 mb-2">{{ $t('a_new_values') }}</p>
                                                    <div class="bg-white rounded-xl border border-emerald-100 p-3 space-y-1">
                                                        <div v-for="(val, key) in log.changes.new" :key="key" class="flex gap-2 text-xs">
                                                            <span class="font-medium text-gray-600 min-w-[100px]">{{ key }}:</span>
                                                            <span class="text-emerald-600">{{ val ?? 'null' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Changes: generic format (non old/new) -->
                                            <div v-else-if="log.changes" class="bg-white rounded-xl border border-gray-200 p-3">
                                                <pre class="text-xs text-gray-600 whitespace-pre-wrap font-mono">{{ JSON.stringify(log.changes, null, 2) }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="!logs.data?.length">
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                        <p class="text-sm text-gray-400">{{ $t('a_no_activity_logs') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.last_page > 1" class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        {{ $t('a_showing') }} {{ logs.from }} {{ $t('a_to') }} {{ logs.to }} {{ $t('a_of') }} {{ logs.total }} {{ $t('a_entries') }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in logs.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                                :class="link.active ? 'bg-[#C4A265] text-white font-medium' : 'text-gray-600 hover:bg-gray-100'"
                                v-html="link.label"
                                preserve-state
                            />
                            <span v-else class="px-3 py-1.5 text-sm text-gray-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
