<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';

const { can } = usePermissions();
const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const source = ref(props.filters?.source || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const moduleFilter = ref(props.filters?.module || '');

const modules = computed(() => page.props.modules || {});
/* ── Only show MEDICAL modules that are ENABLED (not HR, inventory, insurance) ── */
const medicalModuleSlugs = ['derma', 'dental'];
const activeModules = computed(() => {
    const mods = [];
    if (modules.value) {
        Object.values(modules.value).forEach(m => {
            if (m.enabled && medicalModuleSlugs.includes(m.slug)) mods.push(m);
        });
    }
    return mods;
});

let searchTimeout = null;

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/bookings', {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            source: source.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            module: moduleFilter.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch(search, applyFilters);
watch(statusFilter, applyFilters);
watch(source, applyFilters);
watch(dateFrom, applyFilters);
watch(dateTo, applyFilters);
watch(moduleFilter, () => {
    clearTimeout(searchTimeout);
    router.get('/admin/bookings', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        source: source.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        module: moduleFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

const statusColors = {
    unconfirmed: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    confirmed: 'bg-blue-50 text-blue-700 border-blue-200',
    in_progress: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    // Legacy statuses
    new: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    pending: 'bg-yellow-50 text-yellow-700 border-yellow-200',
    contacted: 'bg-sky-50 text-sky-700 border-sky-200',
};

/* statusLabels is now computed so it reacts to locale changes */

const sourceColors = {
    website: 'bg-blue-50 text-blue-600 border-blue-200',
    secretary: 'bg-teal-50 text-teal-600 border-teal-200',
    admin: 'bg-amber-50 text-amber-600 border-amber-200',
};

const invoiceStatusColors = {
    unpaid: 'bg-red-50 text-red-600 border-red-200',
    partial: 'bg-amber-50 text-amber-600 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-600 border-emerald-200',
};

function getPatientName(booking) {
    return booking.patient?.full_name || booking.full_name || '-';
}

function getPatientPhone(booking) {
    return booking.patient?.phone || booking.phone || '-';
}

function getServicesText(booking) {
    if (booking.booking_services?.length > 0) {
        return booking.booking_services.map(bs => bs.service?.name_en || bs.service?.name_ar).join(', ');
    }
    return '-';
}

function getInvoiceStatus(booking) {
    if (!booking.invoice) return null;
    return booking.invoice.status;
}

function exportBookings() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (source.value) params.set('source', source.value);
    if (dateFrom.value) params.set('date_from', dateFrom.value);
    if (dateTo.value) params.set('date_to', dateTo.value);
    if (moduleFilter.value) params.set('module', moduleFilter.value);
    window.open(`/admin/bookings/export?${params.toString()}`, '_blank');
}
</script>

<template>
    <AdminLayout :title="$t('a_bookings')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_bookings') }}</h1>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="can('package_bundle_bookings.create')"
                        href="/admin/package-bundle-bookings/create"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium transition border-2"
                        style="border-color: #C4A265; color: #C4A265;"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        {{ $t('a_book_package') }}
                    </Link>
                    <Link
                        v-if="can('bookings.create')"
                        href="/admin/bookings/create"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                        style="background-color: #C4A265;"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('a_new_booking') }}
                    </Link>
                    <button
                        v-if="can('bookings.export')"
                        @click="exportBookings"
                        class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                        style="background-color: #C4A265;"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ $t('a_export') }}
                    </button>
                </div>
            </div>

            <!-- Module Tabs — always show when at least 1 medical module is active -->
            <div v-if="activeModules.length >= 1" class="bg-white rounded-lg shadow-sm p-1.5 flex gap-1 flex-wrap">
                <button
                    @click="moduleFilter = ''"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                    :class="moduleFilter === '' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                >
                    {{ $t('a_all') }}
                </button>
                <button
                    v-for="mod in activeModules"
                    :key="mod.slug"
                    @click="moduleFilter = mod.slug"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1.5"
                    :class="moduleFilter === mod.slug ? 'text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                    :style="moduleFilter === mod.slug ? { backgroundColor: mod.color } : {}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon" /></svg>
                    <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('a_search_bookings_placeholder')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                    />
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                    >
                        <option value="">{{ $t('a_all_statuses') }}</option>
                        <option value="unconfirmed">{{ $t('a_unconfirmed') }}</option>
                        <option value="confirmed">{{ $t('a_confirmed') }}</option>
                        <option value="in_progress">{{ $t('a_in_progress') }}</option>
                        <option value="completed">{{ $t('a_completed') }}</option>
                        <option value="cancelled">{{ $t('a_cancelled') }}</option>
                    </select>
                    <select
                        v-model="source"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                    >
                        <option value="">{{ $t('a_all_sources') }}</option>
                        <option value="website">{{ $t('a_website') }}</option>
                        <option value="secretary">{{ $t('a_secretary') }}</option>
                        <option value="admin">{{ $t('a_admin_source') }}</option>
                    </select>
                    <input
                        v-model="dateFrom"
                        type="date"
                        :max="dateTo || undefined"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                        :placeholder="$t('a_from_date')"
                    />
                    <input
                        v-model="dateTo"
                        type="date"
                        :min="dateFrom || undefined"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                        :placeholder="$t('a_to_date')"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_booking_number') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_patient') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_phone') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_services') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_source') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_invoice') }}</th>
                                <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <svg v-if="booking.module === 'dental'" class="w-3.5 h-3.5 text-cyan-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Dental"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                        <span class="font-mono text-xs text-gray-600">{{ booking.booking_number || `#${booking.id}` }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">{{ getPatientName(booking) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" dir="ltr">{{ getPatientPhone(booking) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <span class="max-w-[200px] truncate block">{{ getServicesText(booking) }}</span>
                                    <span v-if="booking.booking_services_count > 0" class="text-xs text-gray-400">({{ booking.booking_services_count }} {{ $t('a_services') }})</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="booking.source"
                                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border"
                                        :class="sourceColors[booking.source] || 'bg-gray-50 text-gray-500 border-gray-200'"
                                    >
                                        {{ $t('a_' + booking.source) || booking.source }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                        :class="statusColors[booking.status] || 'bg-gray-50 text-gray-600 border-gray-200'"
                                    >
                                        {{ $t('a_' + booking.status) || booking.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="getInvoiceStatus(booking)"
                                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border"
                                        :class="invoiceStatusColors[getInvoiceStatus(booking)] || 'bg-gray-50 text-gray-500 border-gray-200'"
                                    >
                                        {{ $t('a_' + getInvoiceStatus(booking)) || getInvoiceStatus(booking) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm">
                                    <Link :href="`/admin/bookings/${booking.id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                </td>
                            </tr>
                            <tr v-if="!bookings.data || bookings.data.length === 0">
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_bookings_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="bookings.links && bookings.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ bookings.from }} {{ $t('a_to') }} {{ bookings.to }} {{ $t('a_of') }} {{ bookings.total }} {{ $t('a_results') }}</p>
                    <nav class="flex gap-1">
                        <template v-for="link in bookings.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 text-sm rounded border transition"
                                :class="link.active ? 'text-white border-transparent' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm text-gray-400" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
