<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(statusFilter, () => applyFilters());

function applyFilters() {
    router.get('/secretary/patients', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const genderLabels = { male: isRtl.value ? 'ذكر' : 'Male', female: isRtl.value ? 'أنثى' : 'Female' };

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ isRtl ? 'المرضى' : 'Patients' }}</h1>
                <p class="text-sm text-gray-500 mt-1">Manage patient records</p>
            </div>
            <Link
                href="/secretary/patients/create"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 shadow-md shadow-teal-500/20 transition-all duration-200"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ isRtl ? 'مريض جديد' : 'New Patient' }}
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, phone, file number..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition"
                />
            </div>
            <select
                v-model="statusFilter"
                class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition"
            >
                <option value="">{{ isRtl ? 'جميع الحالات' : 'All Status' }}</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">File #</th>
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الجنس' : 'Gender' }}</th>
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'تاريخ الميلاد' : 'Date of Birth' }}</th>
                            <th class="ltr:text-left rtl:ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'الحالة' : 'Status' }}</th>
                            <th class="ltr:text-right rtl:text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ isRtl ? 'إجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="patient in patients.data" :key="patient.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="text-sm font-mono font-semibold text-teal-600">{{ patient.file_number }}</span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div v-if="patient.photo" class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-teal-100">
                                        <img
                                            :src="patient.photo.startsWith('http') ? patient.photo : `/storage/${patient.photo}`"
                                            :alt="patient.full_name"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div v-else class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold bg-gradient-to-br from-teal-500 to-cyan-500">
                                        {{ patient.full_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <Link :href="`/secretary/patients/${patient.id}`" class="text-sm font-semibold text-gray-800 hover:text-teal-600 transition-colors">
                                            {{ patient.full_name }}
                                        </Link>
                                        <p v-if="patient.email" class="text-xs text-gray-400 mt-0.5">{{ patient.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ patient.phone || '-' }}</td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-sm text-gray-500 capitalize">{{ genderLabels[patient.gender] || '-' }}</td>
                            <td class="px-6 py-3.5 whitespace-nowrap text-sm text-gray-500">{{ formatDate(patient.date_of_birth) }}</td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                                    :class="patient.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-500 border-gray-200'"
                                >
                                    {{ patient.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap ltr:text-right rtl:text-left space-x-3">
                                <Link :href="`/secretary/patients/${patient.id}`" class="text-teal-600 hover:text-teal-800 text-xs font-semibold transition-colors">{{ isRtl ? 'عرض' : 'View' }}</Link>
                                <Link :href="`/secretary/patients/${patient.id}/edit`" class="text-blue-600 hover:text-blue-800 text-xs font-semibold transition-colors">{{ isRtl ? 'تعديل' : 'Edit' }}</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="!patients.data || patients.data.length === 0" class="py-16 text-center">
                <div class="w-16 h-16 rounded-2xl bg-teal-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No patients found</p>
                <p class="text-xs text-gray-400 mt-1">Try adjusting your search or filters</p>
            </div>

            <!-- Pagination -->
            <div v-if="patients.links && patients.links.length > 3" class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Showing <span class="font-semibold">{{ patients.from }}</span> to <span class="font-semibold">{{ patients.to }}</span> of <span class="font-semibold">{{ patients.total }}</span> results
                </p>
                <nav class="flex items-center gap-1">
                    <template v-for="link in patients.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-teal-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                            v-html="link.label"
                            preserve-state
                        />
                        <span v-else class="px-3 py-1.5 text-xs text-gray-300" v-html="link.label" />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>
