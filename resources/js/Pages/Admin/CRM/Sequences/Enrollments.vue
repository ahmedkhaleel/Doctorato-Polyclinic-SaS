<script setup>
import { ref, onMounted , computed } from 'vue';
import { router, Link , usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    sequence: Object,
    enrollments: Object,
    filters: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const mounted = ref(false);
onMounted(() => setTimeout(() => mounted.value = true, 50));

const statusFilter = ref(props.filters?.status || '');

function applyFilters() {
    router.get(`/admin/sequences/${props.sequence.id}/enrollments`, {
        status: statusFilter.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function cancelEnrollment(enrollment) {
    if (confirm('Cancel this enrollment?')) {
        router.post(`/admin/enrollments/${enrollment.id}/cancel`, {}, { preserveScroll: true });
    }
}

const statusColors = {
    active: 'bg-emerald-100 text-emerald-700',
    paused: 'bg-amber-100 text-amber-700',
    completed: 'bg-slate-100 text-[#1B365D]',
    cancelled: 'bg-gray-100 text-gray-500',
    stopped_reply: 'bg-slate-100 text-[#1B365D]',
    stopped_conversion: 'bg-slate-100 text-[#1B365D]',
};

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout :title="`Enrollments: ${sequence.name}`">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-3 mb-6"
                 :class="{ 'translate-y-0 opacity-100': mounted, '-translate-y-4 opacity-0': !mounted }"
                 style="transition: all 0.5s ease-out">
                <Link href="/admin/sequences" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-[#3A3A3A]">{{ $t('a_enrollments') }}</h1>
                    <p class="text-sm text-gray-500">{{ sequence.name }}</p>
                </div>
            </div>

            <!-- Filter -->
            <div class="mb-4">
                <select v-model="statusFilter" @change="applyFilters"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/20 focus:border-[#C4A265]">
                    <option value="">{{ $t('a_all_statuses') }}</option>
                    <option value="active">{{ $t('a_active') }}</option>
                    <option value="completed">{{ $t('a_completed') }}</option>
                    <option value="paused">{{ $t('a_paused') }}</option>
                    <option value="cancelled">{{ $t('a_cancelled') }}</option>
                    <option value="stopped_reply">{{ $t('a_stopped_reply') }}</option>
                    <option value="stopped_conversion">{{ $t('a_stopped_conversion') }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="ltr:text-left rtl:text-right px-5 py-3 font-medium text-gray-600">{{ $t('a_lead') }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3 font-medium text-gray-600">{{ $t('a_status') }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3 font-medium text-gray-600">{{ $t('a_step') }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3 font-medium text-gray-600">{{ $t('a_enrolled') }}</th>
                            <th class="ltr:text-left rtl:text-right px-5 py-3 font-medium text-gray-600">{{ $t('a_next_step') }}</th>
                            <th class="ltr:text-right rtl:text-left px-5 py-3 font-medium text-gray-600">{{ $t('a_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="e in enrollments.data" :key="e.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3">
                                <Link v-if="e.lead" :href="`/admin/leads/${e.lead.id}`" class="text-[#C4A265] hover:underline font-medium">
                                    {{ e.lead.full_name }}
                                </Link>
                                <span v-else class="text-gray-400">{{ $t('a_deleted') }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span :class="statusColors[e.status] || 'bg-gray-100 text-gray-500'"
                                    class="inline-block px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ e.status.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ e.current_step_index }}</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ formatDate(e.enrolled_at) }}</td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ formatDate(e.next_step_at) }}</td>
                            <td class="px-5 py-3 ltr:text-right rtl:text-left">
                                <button v-if="e.status === 'active'" @click="cancelEnrollment(e)"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium">{{ $t('a_cancel') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="enrollments.data.length === 0" class="py-12 text-center text-gray-400 text-sm">{{ $t('a_no_enrollments_found') }}</div>
            </div>
        </div>
    </AdminLayout>
</template>
