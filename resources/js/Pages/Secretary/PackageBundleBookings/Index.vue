<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/secretary/bundle-bookings', { search: val || undefined, status: statusFilter.value || undefined }, {
            preserveState: true, replace: true,
        });
    }, 400);
});

watch(statusFilter, (val) => {
    router.get('/secretary/bundle-bookings', { search: search.value || undefined, status: val || undefined }, {
        preserveState: true, replace: true,
    });
});

const { formatCurrency, currencyCode } = useCurrency();

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-indigo-100 text-indigo-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

function getProgress(booking) {
    if (!booking.bundle_services || booking.bundle_services.length === 0) return 0;
    const total = booking.bundle_services.reduce((s, bs) => s + bs.sessions_count, 0);
    const done = booking.bundle_services.reduce((s, bs) => s + bs.completed_sessions, 0);
    return total > 0 ? Math.round((done / total) * 100) : 0;
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">{{ isRtl ? 'حجوزات الباقات' : 'Bundle Bookings' }}</h1>
            <Link
                href="/secretary/bundle-bookings/create"
                class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                style="background-color: #0d9488;"
            >
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Bundle Booking
            </Link>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
            <input v-model="search" type="text" placeholder="Search by booking number, patient..."
                   class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent" />
            <select v-model="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-200 focus:border-transparent">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                <option value="pending">Pending</option>
                <option value="confirmed">{{ isRtl ? 'مؤكد' : 'Confirmed' }}</option>
                <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ isRtl ? 'حجز' : 'Booking' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bundle</th>
                            <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium" style="color: #0d9488;">{{ b.booking_number }}</div>
                                <div class="text-xs text-gray-400">{{ new Date(b.created_at).toLocaleDateString() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ b.patient?.full_name }}</div>
                                <div class="text-xs text-gray-400">{{ b.patient?.phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ b.package_bundle?.name_en || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ formatCurrency(b.total_price) }}</div>
                                <div class="text-xs" :class="Number(b.balance_due) > 0 ? 'text-red-500' : 'text-green-500'">
                                    {{ Number(b.balance_due) > 0 ? 'Due: ' + formatCurrency(b.balance_due) : 'Paid' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500" :style="`width: ${getProgress(b)}%; background-color: #0d9488;`"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-10">{{ getProgress(b) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold" :class="statusColors[b.status] || 'bg-gray-100 text-gray-800'">
                                    {{ b.status?.replace(/_/g, ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm">
                                <Link :href="`/secretary/bundle-bookings/${b.id}`"
                                      class="p-2 rounded-lg text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition-all duration-200 inline-block"
                                      title="View">
                                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!bookings.data || bookings.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No bundle bookings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="bookings.links && bookings.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-500">Showing {{ bookings.from }} to {{ bookings.to }} of {{ bookings.total }} results</p>
                <nav class="flex space-x-1">
                    <template v-for="link in bookings.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" v-html="link.label"
                              class="px-3 py-1 text-sm rounded border transition"
                              :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                              :style="link.active ? 'background-color: #0d9488;' : ''"
                              preserve-state />
                        <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
