<script setup>
import { ref, onMounted, computed } from 'vue';
import { router, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue';

const { can } = usePermissions();

const props = defineProps({
    sequences: Object,
    filters: Object,
    triggerEvents: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

function applyFilters() {
    router.get('/admin/sequences', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

const deleteModal = ref(false);
const deletingSequence = ref(null);

function confirmDelete(seq) {
    deletingSequence.value = seq;
    deleteModal.value = true;
}

function performDelete() {
    router.post(`/admin/sequences/${deletingSequence.value.id}/delete`, {
        preserveScroll: true,
        onSuccess: () => { deleteModal.value = false; deletingSequence.value = null; },
    });
}

function toggleActive(seq) {
    router.post(`/admin/sequences/${seq.id}/toggle`, {}, { preserveScroll: true });
}

const actionColors = {
    create_follow_up: 'bg-slate-100 text-[#1B365D]',
    send_whatsapp: 'bg-emerald-100 text-emerald-700',
    send_email: 'bg-slate-100 text-[#1B365D]',
    send_sms: 'bg-slate-100 text-[#1B365D]',
    notify_staff: 'bg-amber-100 text-amber-700',
    change_status: 'bg-slate-100 text-[#1B365D]',
    add_score: 'bg-amber-100 text-[#C4A265]',
};

const actionIcons = {
    create_follow_up: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    send_whatsapp: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    send_email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    send_sms: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    notify_staff: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    change_status: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    add_score: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
};

function formatDelay(minutes) {
    if (minutes < 60) return minutes + 'm';
    const h = Math.floor(minutes / 60);
    if (h < 24) return h + 'h';
    const d = Math.floor(h / 24);
    const rh = h % 24;
    return rh > 0 ? `${d}d ${rh}h` : `${d}d`;
}
</script>

<template>
    <AdminLayout :title="$t('a_automation_sequences')">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8"
                 :class="{ 'translate-y-0 opacity-100': mounted, '-translate-y-4 opacity-0': !mounted }"
                 style="transition: all 0.5s ease-out">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-[#3A3A3A]">{{ $t('a_automation_sequences') }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $t('a_sequences_description') }}</p>
                </div>
                <Link v-if="can('leads.create')"
                    href="/admin/sequences/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A] text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all duration-200 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>{{ $t('a_new_sequence') }}</Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6"
                 :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-4 opacity-0': !mounted }"
                 style="transition: all 0.5s ease-out 0.1s">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input v-model="search" @keyup.enter="applyFilters" type="text" :placeholder="$t('a_search_sequences')"
                            class="doctorato-input w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors" />
                    </div>
                    <select v-model="statusFilter" @change="applyFilters"
                        class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265] transition-colors">
                        <option value="">{{ $t('a_all_statuses') }}</option>
                        <option value="active">{{ $t('a_active') }}</option>
                        <option value="inactive">{{ $t('a_inactive') }}</option>
                    </select>
                </div>
            </div>

            <!-- Sequences Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div v-for="(seq, index) in sequences.data" :key="seq.id"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300 hover:-translate-y-0.5"
                    :class="{ 'translate-y-0 opacity-100': mounted, 'translate-y-6 opacity-0': !mounted }"
                    :style="{ transitionDelay: `${0.15 + index * 0.05}s` }">

                    <!-- Card Header -->
                    <div class="p-5 border-b border-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 mb-1.5">
                                    <h3 class="text-base font-semibold text-[#3A3A3A] truncate">{{ seq.name }}</h3>
                                    <span :class="seq.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">
                                        <span :class="seq.is_active ? 'bg-emerald-500' : 'bg-gray-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ seq.is_active ? $t('a_active') : $t('a_inactive') }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    Trigger: <span class="font-medium text-gray-600">{{ triggerEvents[seq.trigger_event] || seq.trigger_event }}</span>
                                </p>
                                <p v-if="seq.description" class="text-xs text-gray-400 mt-1 line-clamp-1">{{ seq.description }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 ltr:ml-3 rtl:mr-3">
                                <button @click="toggleActive(seq)" :title="seq.is_active ? 'Pause' : 'Activate'"
                                    class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg v-if="seq.is_active" class="w-4.5 h-4.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <svg v-else class="w-4.5 h-4.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                <Link v-if="can('leads.update')" :href="`/admin/sequences/${seq.id}/edit`"
                                    class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </Link>
                                <button v-if="can('leads.delete')" @click="confirmDelete(seq)"
                                    class="p-1.5 rounded-lg hover:bg-red-50 transition-colors">
                                    <svg class="w-4.5 h-4.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 mt-3">
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ seq.total_enrollments_count || 0 }} enrolled</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>{{ seq.active_enrollments_count || 0 }} active</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>{{ seq.steps?.length || 0 }} steps</span>
                            </div>
                        </div>
                    </div>

                    <!-- Steps Timeline -->
                    <div class="px-5 py-3 bg-gray-50/50">
                        <div class="flex items-center gap-1 overflow-x-auto pb-1">
                            <template v-for="(step, si) in (seq.steps || []).slice(0, 6)" :key="si">
                                <div v-if="si > 0" class="flex items-center gap-0.5 text-gray-300 flex-shrink-0">
                                    <span class="text-[10px]">{{ formatDelay(step.delay_minutes) }}</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </div>
                                <div :class="actionColors[step.action_type] || 'bg-gray-100 text-gray-600'"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-medium whitespace-nowrap flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="actionIcons[step.action_type] || 'M13 10V3L4 14h7v7l9-11h-7z'"/>
                                    </svg>
                                    {{ step.action_type.replace(/_/g, ' ').replace('send ', '') }}
                                </div>
                            </template>
                            <span v-if="(seq.steps || []).length > 6" class="text-xs text-gray-400 ml-1">+{{ seq.steps.length - 6 }} more</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="sequences.data.length === 0"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-[#C4A265]/20 to-[#D4B87A]/20 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-[#C4A265]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-[#3A3A3A] mb-1">No Automation Sequences</h3>
                <p class="text-sm text-gray-500 mb-5">{{ $t('a_create_first_sequence_hint') }}</p>
                <Link v-if="can('leads.create')" href="/admin/sequences/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#C4A265] to-[#D4B87A] text-white rounded-xl font-medium shadow-md hover:shadow-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Sequence
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="sequences.links && sequences.last_page > 1" class="mt-6 flex justify-center gap-1">
                <template v-for="link in sequences.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        :class="link.active ? 'bg-[#C4A265] text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                        class="px-3 py-1.5 rounded-lg text-sm border border-gray-200 transition-colors"
                        v-html="link.label" />
                    <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label" />
                </template>
            </div>
        </div>

        <ConfirmModal v-if="deleteModal"
            :show="deleteModal"
            title="Delete Sequence"
            message="Are you sure? All active enrollments will be cancelled."
            confirm-text="Delete"
            @confirm="performDelete"
            @cancel="deleteModal = false" />
    </AdminLayout>
</template>
