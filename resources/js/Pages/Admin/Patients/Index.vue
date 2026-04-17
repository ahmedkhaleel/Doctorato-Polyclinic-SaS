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
    patients: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/patients', { search: val || undefined, status: statusFilter.value || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
});

watch(statusFilter, (val) => {
    router.get('/admin/patients', { search: search.value || undefined, status: val || undefined }, {
        preserveState: true,
        replace: true,
    });
});

function clearSearch() {
    search.value = '';
}

function deletePatient(id) {
    if (window.confirm('Are you sure you want to delete this patient? This action cannot be undone.')) {
        router.post(`/admin/patients/${id}/delete`);
    }
}

function getPatientPhoto(patient) {
    if (!patient.photo) return null;
    return patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    return name.charAt(0).toUpperCase();
}

const genderConfig = {
    male: { classes: 'text-[#1B365D] bg-slate-50 border-slate-100' },
    female: { classes: 'text-[#C4A265] bg-amber-50 border-amber-100' },
};
</script>

<template>
    <AdminLayout :title="$t('a_patients')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800">{{ $t('a_patients') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <span class="font-semibold" style="color: #C4A265;">{{ patients.total }}</span> {{ $t('a_total_patients') }}
                    </p>
                </div>
                <Link
                    v-if="can('patients.create')"
                    href="/admin/patients/create"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl"
                    style="background-color: #C4A265; box-shadow: 0 4px 14px rgba(196, 162, 101, 0.3);"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ $t('a_add_patient') }}
                </Link>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[220px]">
                        <div class="absolute inset-y-0 ltr:left-0 rtl:right-0 ltr:pl-3.5 rtl:pr-3.5 flex items-center pointer-events-none">
                            <svg class="w-4.5 h-4.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('a_search_patients_placeholder')"
                            class="w-full ltr:pl-10 ltr:pr-9 rtl:pr-10 rtl:pl-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-amber-100 focus:border-amber-300 focus:bg-white hover:border-gray-300 transition-all duration-200"
                        />
                        <button v-if="search" @click="clearSearch" class="absolute inset-y-0 ltr:right-0 rtl:left-0 ltr:pr-3 rtl:pl-3 flex items-center text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        </div>
                        <select
                            v-model="statusFilter"
                            class="pl-10 pr-9 py-2.5 border border-gray-200 bg-gray-50/50 rounded-xl text-sm focus:ring-2 focus:ring-amber-100 focus:border-amber-300 focus:bg-white hover:border-gray-300 transition-all duration-200 appearance-none cursor-pointer"
                        >
                            <option value="">{{ $t('a_all_status') }}</option>
                            <option value="active">{{ $t('a_active') }}</option>
                            <option value="inactive">{{ $t('a_inactive') }}</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_patients') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_file_number') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_phone') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_gender') }}</th>
                                <th class="px-4 md:px-6 py-3.5 ltr:text-left rtl:text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-4 md:px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="patient in patients.data"
                                :key="patient.id"
                                class="group hover:bg-gray-50/60 transition-colors duration-150"
                            >
                                <!-- Patient -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div v-if="getPatientPhoto(patient)" class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100 shadow-sm">
                                            <img :src="getPatientPhoto(patient)" :alt="patient.full_name" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center text-white text-sm font-bold shadow-sm" style="background-color: #C4A265;">
                                            {{ getInitials(patient.full_name) }}
                                        </div>
                                        <div>
                                            <Link :href="`/admin/patients/${patient.id}`" class="text-sm font-semibold text-gray-800 hover:underline transition" style="text-decoration-color: #C4A265;">
                                                {{ patient.full_name }}
                                            </Link>
                                            <div v-if="patient.email" class="text-xs text-gray-400 mt-0.5">{{ patient.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- File # -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-100" style="color: #C4A265;">
                                        {{ patient.file_number }}
                                    </span>
                                </td>

                                <!-- Phone -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span dir="ltr">{{ patient.phone }}</span>
                                    </div>
                                </td>

                                <!-- Gender -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <span v-if="patient.gender && genderConfig[patient.gender]"
                                        :class="genderConfig[patient.gender].classes"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border">
                                        <svg v-if="patient.gender === 'female'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M12 12v8m-3-3h6"/></svg>
                                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="10" cy="14" r="4" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M14 10l6-6m0 0h-5m5 0v5"/></svg>
                                        {{ patient.gender === 'male' ? $t('a_male') : $t('a_female') }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span :class="patient.is_active ? 'bg-emerald-400' : 'bg-gray-300'" class="w-2 h-2 rounded-full shrink-0"></span>
                                        <span class="text-sm font-medium" :class="patient.is_active ? 'text-emerald-700' : 'text-gray-500'">
                                            {{ patient.is_active ? $t('a_active') : $t('a_inactive') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <Link v-if="can('patients.view')" :href="`/admin/patients/${patient.id}`"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-amber-50 transition-all duration-200" style="--tw-hover-text-opacity:1;" :title="$t('a_view')">
                                            <svg class="w-4 h-4 hover-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>
                                        <!-- Edit -->
                                        <Link v-if="can('patients.update')" :href="`/admin/patients/${patient.id}/edit`"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-[#1B365D] hover:bg-slate-50 transition-all duration-200" :title="$t('a_edit')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </Link>
                                        <!-- Delete -->
                                        <button v-if="can('patients.delete')" @click="deletePatient(patient.id)"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200" :title="$t('a_delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!patients.data || patients.data.length === 0">
                                <td colspan="6" class="px-4 md:px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-500">{{ $t('a_no_patients_found') }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $t('a_try_adjusting_filters') }}</p>
                                        <Link v-if="can('patients.create') && (search || statusFilter)" href="/admin/patients" class="mt-4 text-xs font-medium px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition">
                                            {{ $t('a_clear_filters') }}
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="patients.links && patients.links.length > 3" class="px-4 md:px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ patients.from }}</span>
                        {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ patients.to }}</span>
                        {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ patients.total }}</span>
                    </p>
                    <nav class="flex items-center gap-1">
                        <template v-for="link in patients.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                                :class="link.active ? 'text-white border-transparent shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50 hover:border-gray-300'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.hover-gold:hover {
    color: #C4A265;
}
tr:hover .hover-gold {
    color: #C4A265;
}
</style>
