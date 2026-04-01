<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    bookings: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const source = ref(props.filters?.source || '');
const moduleFilter = ref(props.filters?.module || '');

const modules = computed(() => page.props.modules || {});
const activeModules = computed(() => {
    const mods = [];
    if (modules.value) {
        Object.values(modules.value).forEach(m => { if (m.enabled) mods.push(m); });
    }
    return mods;
});

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(status, () => applyFilters());
watch(source, () => applyFilters());
watch(moduleFilter, () => applyFilters());

function applyFilters() {
    router.get('/secretary/bookings', {
        search: search.value || undefined,
        status: status.value || undefined,
        source: source.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

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

const statusLabels = {
    unconfirmed: 'Unconfirmed',
    confirmed: 'Confirmed',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
    new: isRtl.value ? 'جديد' : 'New',
    pending: 'Pending',
    contacted: isRtl.value ? 'تم التواصل' : 'Contacted',
};

const sourceColors = {
    website: 'bg-blue-50 text-blue-600 border-blue-200',
    secretary: 'bg-teal-50 text-teal-600 border-teal-200',
    admin: 'bg-amber-50 text-amber-600 border-amber-200',
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

const invoiceStatusColors = {
    unpaid: 'bg-red-50 text-red-600 border-red-200',
    partial: 'bg-amber-50 text-amber-600 border-amber-200',
    paid: 'bg-emerald-50 text-emerald-600 border-emerald-200',
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'الحجوزات' : 'Bookings' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Manage all bookings and appointments</p>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/secretary/bundle-bookings/create" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm border-2 border-teal-500 text-teal-600 hover:bg-teal-50 transition-all duration-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    Book Package
                </Link>
                <Link href="/secretary/bookings/create" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white font-semibold text-sm bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 transition-all duration-300 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    {{ isRtl ? 'حجز جديد' : 'New Booking' }}
                </Link>
            </div>
        </div>

        <!-- Module Tabs -->
        <div v-if="activeModules.length > 1" class="bg-white rounded-lg shadow-sm p-1.5 flex gap-1 flex-wrap mb-4">
            <button
                @click="moduleFilter = ''"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                :class="moduleFilter === '' ? 'bg-gray-800 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
            >
                {{ isRtl ? 'الكل' : 'All' }}
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
                <span>{{ isRtl ? mod.name_ar : mod.name_en }}</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input v-model="search" type="text" placeholder="Search by name, phone, booking #, file #..." class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 w-72" />
            <select v-model="status" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Statuses' }}</option>
                <option value="unconfirmed">Unconfirmed</option>
                <option value="confirmed">{{ isRtl ? 'مؤكد' : 'Confirmed' }}</option>
                <option value="in_progress">{{ isRtl ? 'جاري' : 'In Progress' }}</option>
                <option value="completed">{{ isRtl ? 'مكتمل' : 'Completed' }}</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select v-model="source" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500">
                <option value="">{{ isRtl ? 'جميع المصادر' : 'All Sources' }}</option>
                <option value="website">Website</option>
                <option value="secretary">Secretary</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/80">
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Booking #</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Services</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'المصدر' : 'Source' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'الفاتورة' : 'Invoice' }}</th>
                        <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="booking in bookings.data" :key="booking.id" class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-1.5">
                                <svg v-if="booking.module === 'dental'" class="w-3.5 h-3.5 text-cyan-500 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Dental"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                <span class="font-mono text-xs text-gray-600">{{ booking.booking_number || `#${booking.id}` }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 font-semibold text-gray-800">{{ getPatientName(booking) }}</td>
                        <td class="px-6 py-3 text-gray-500" dir="ltr">{{ getPatientPhone(booking) }}</td>
                        <td class="px-6 py-3 text-gray-500">
                            <span class="max-w-[200px] truncate block">{{ getServicesText(booking) }}</span>
                            <span v-if="booking.booking_services_count > 0" class="text-xs text-gray-400">({{ booking.booking_services_count }} services)</span>
                        </td>
                        <td class="px-6 py-3">
                            <span v-if="booking.source" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="sourceColors[booking.source] || 'bg-gray-50 text-gray-500'">
                                {{ booking.source }}
                            </span>
                            <span v-else class="text-xs text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border" :class="statusColors[booking.status] || 'bg-gray-50 text-gray-600 border-gray-200'">
                                {{ statusLabels[booking.status] || booking.status }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span v-if="getInvoiceStatus(booking)" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium border" :class="invoiceStatusColors[getInvoiceStatus(booking)] || 'bg-gray-50 text-gray-500'">
                                {{ getInvoiceStatus(booking) }}
                            </span>
                            <span v-else class="text-xs text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-3 ltr:text-right rtl:text-left">
                            <Link :href="`/secretary/bookings/${booking.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold">{{ isRtl ? 'عرض' : 'View' }}</Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="bookings.data?.length === 0" class="py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <p class="text-sm text-gray-400">No bookings found</p>
            </div>

            <div v-if="bookings.links?.length > 3" class="flex items-center justify-center gap-1 px-6 py-4 border-t border-gray-100">
                <template v-for="link in bookings.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="link.active ? 'bg-teal-500 text-white' : 'text-gray-500 hover:bg-gray-100'" v-html="link.label" preserve-state />
                    <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
