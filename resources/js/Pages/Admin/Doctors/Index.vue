<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/Composables/usePermissions.js';
import { useLocale } from '@/Composables/useLocale.js';

const { can } = usePermissions();
const { t } = useLocale();

const props = defineProps({
    doctors: Object,
    filters: Object,
    modules: Object,
});

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');
const moduleFilter = ref(props.filters?.module || '');
const mounted = ref(false);
const deleteId = ref(null);
let searchTimeout = null;

onMounted(() => {
    setTimeout(() => mounted.value = true, 50);
});

const activeModules = computed(() => {
    const mods = [];
    if (props.modules) {
        Object.values(props.modules).forEach(m => {
            if (m.enabled === true) mods.push(m);
        });
    }
    return mods;
});

const medicalModules = computed(() => activeModules.value.filter(m => ['derma', 'dental', 'pediatric'].includes(m.slug)));

const stats = computed(() => {
    const all = props.doctors?.data || [];
    return {
        total: props.doctors?.total || all.length,
        active: all.filter(d => d.status === 'active').length,
        inactive: all.filter(d => d.status !== 'active').length,
        withLogin: all.filter(d => d.user_id).length,
    };
});

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/doctors', {
            search: search.value || undefined,
            module: moduleFilter.value || undefined,
        }, { preserveState: true, replace: true });
    }, 400);
}

watch(search, applyFilters);
watch(moduleFilter, () => {
    clearTimeout(searchTimeout);
    router.get('/admin/doctors', {
        search: search.value || undefined,
        module: moduleFilter.value || undefined,
    }, { preserveState: true, replace: true });
});

function confirmDelete(id) {
    deleteId.value = id;
}

function cancelDelete() {
    deleteId.value = null;
}

function executeDelete() {
    if (deleteId.value) {
        router.post(`/admin/doctors/${deleteId.value}/delete`);
        deleteId.value = null;
    }
}

function getModuleColor(mod) {
    if (!props.modules) return '#C4A265';
    const found = Object.values(props.modules).find(m => m.slug === mod);
    return found?.color || '#C4A265';
}
</script>

<template>
    <AdminLayout :title="$t('a_doctors')">
        <div class="doctors-page space-y-6">
            <!-- Header -->
            <div
                class="header-section flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                :class="{ 'animate-in': mounted }"
            >
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <span class="header-icon w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #C4A265, #d4b87a);">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                        {{ $t('a_doctors') }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1" :class="isRtl ? 'mr-13' : 'ml-13'">
                        {{ $t('a_manage_doctors_description') || (isRtl ? 'إدارة الأطباء والتخصصات' : 'Manage doctors and specializations') }}
                    </p>
                </div>
                <Link
                    v-if="can('doctors.create')"
                    href="/admin/doctors/create"
                    class="add-btn inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #C4A265, #b8934f);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('a_add_doctor') || (isRtl ? 'إضافة طبيب' : 'Add Doctor') }}
                </Link>
            </div>

            <!-- Stats Cards -->
            <div
                class="grid grid-cols-2 sm:grid-cols-4 gap-4"
                :class="{ 'animate-in delay-1': mounted }"
            >
                <div class="stat-card bg-white rounded-xl p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                            <p class="text-xs text-gray-500">{{ $t('a_total_doctors') || (isRtl ? 'إجمالي الأطباء' : 'Total Doctors') }}</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-xl p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-green-50">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                            <p class="text-xs text-gray-500">{{ $t('a_active') || (isRtl ? 'نشط' : 'Active') }}</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-xl p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-500">{{ stats.inactive }}</p>
                            <p class="text-xs text-gray-500">{{ $t('a_inactive') || (isRtl ? 'غير نشط' : 'Inactive') }}</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-white rounded-xl p-4 border border-gray-100 shadow-sm hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-50">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-600">{{ stats.withLogin }}</p>
                            <p class="text-xs text-gray-500">{{ $t('a_with_login') || (isRtl ? 'لديه حساب' : 'With Login') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div
                class="filters-bar bg-white rounded-xl shadow-sm border border-gray-100 p-4"
                :class="{ 'animate-in delay-2': mounted }"
            >
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <svg class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" :class="isRtl ? 'right-3' : 'left-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('a_search_doctors') || (isRtl ? 'البحث عن طبيب...' : 'Search doctors...')"
                            class="search-input w-full border border-gray-200 rounded-lg text-sm py-2.5 focus:ring-2 focus:border-transparent transition-all duration-200"
                            :class="isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4'"
                            style="--focus-ring: rgba(196, 162, 101, 0.3);"
                        />
                    </div>
                    <!-- Module Tabs -->
                    <div v-if="medicalModules.length > 1" class="flex items-center gap-1.5 bg-gray-50 rounded-lg p-1">
                        <button
                            @click="moduleFilter = ''"
                            class="module-tab px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                            :class="moduleFilter === '' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                        >
                            {{ $t('a_all') }}
                        </button>
                        <button
                            v-for="mod in medicalModules"
                            :key="mod.slug"
                            @click="moduleFilter = mod.slug"
                            class="module-tab px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1.5"
                            :class="moduleFilter === mod.slug ? 'text-white shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            :style="moduleFilter === mod.slug ? { backgroundColor: mod.color } : {}"
                        >
                            <span>{{ locale === 'ar' ? mod.name_ar : mod.name_en }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div
                class="table-container bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                :class="{ 'animate-in delay-3': mounted }"
            >
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-50/50 border-b border-gray-100">
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider" :class="isRtl ? 'text-right' : 'text-left'">{{ $t('a_doctor') || (isRtl ? 'الطبيب' : 'Doctor') }}</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider" :class="isRtl ? 'text-right' : 'text-left'">{{ $t('a_module') }}</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider" :class="isRtl ? 'text-right' : 'text-left'">{{ $t('a_specialization') }}</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">{{ $t('a_order') }}</th>
                                <th class="px-6 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="(doctor, idx) in doctors.data"
                                :key="doctor.id"
                                class="doctor-row group hover:bg-amber-50/30 transition-colors duration-200"
                                :style="mounted ? `transition-delay: ${idx * 40}ms` : ''"
                                :class="{ 'row-animate': mounted }"
                            >
                                <!-- Doctor Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="doctor-avatar w-10 h-10 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-gray-100">
                                            <img
                                                v-if="doctor.photo"
                                                :src="doctor.photo.startsWith('http') ? doctor.photo : `/storage/${doctor.photo}`"
                                                :alt="locale === 'ar' ? doctor.name_ar : doctor.name_en"
                                                class="w-full h-full object-cover"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-white font-bold text-sm" :style="{ backgroundColor: getModuleColor(doctor.module) }">
                                                {{ (locale === 'ar' ? doctor.name_ar : doctor.name_en)?.charAt(0)?.toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <Link
                                                v-if="can('doctors.view')"
                                                :href="`/admin/doctors/${doctor.id}`"
                                                class="text-sm font-semibold text-gray-800 hover:text-amber-700 transition-colors duration-200"
                                            >
                                                {{ locale === 'ar' ? doctor.name_ar : doctor.name_en }}
                                            </Link>
                                            <span v-else class="text-sm font-semibold text-gray-800">
                                                {{ locale === 'ar' ? doctor.name_ar : doctor.name_en }}
                                            </span>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span
                                                    v-if="doctor.user_id"
                                                    class="inline-flex items-center gap-1 text-[10px] font-medium text-green-600"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                    {{ $t('a_has_login') }}
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-400">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                                    {{ $t('a_no_login') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Module -->
                                <td class="px-6 py-4">
                                    <span
                                        class="module-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                                        :style="{
                                            backgroundColor: getModuleColor(doctor.module) + '15',
                                            color: getModuleColor(doctor.module),
                                        }"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: getModuleColor(doctor.module) }"></span>
                                        {{ doctor.module === 'dental' ? $t('a_dental') : $t('a_derma') }}
                                    </span>
                                </td>
                                <!-- Specialization -->
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">
                                        {{ (locale === 'ar' ? doctor.specialization_ar : doctor.specialization_en) || '-' }}
                                    </span>
                                </td>
                                <!-- Status -->
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="status-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="doctor.status === 'active'
                                            ? 'bg-green-50 text-green-700 ring-1 ring-green-200'
                                            : 'bg-gray-50 text-gray-600 ring-1 ring-gray-200'"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="doctor.status === 'active' ? 'bg-green-500' : 'bg-gray-400'"
                                        ></span>
                                        {{ doctor.status === 'active' ? ($t('a_active') || (isRtl ? 'نشط' : 'Active')) : ($t('a_inactive') || (isRtl ? 'غير نشط' : 'Inactive')) }}
                                    </span>
                                </td>
                                <!-- Order -->
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50 text-sm font-medium text-gray-600 ring-1 ring-gray-100">
                                        {{ doctor.display_order ?? '-' }}
                                    </span>
                                </td>
                                <!-- Actions (Icons) -->
                                <td class="px-6 py-4 text-center">
                                    <div class="action-icons flex items-center justify-center gap-1">
                                        <!-- View -->
                                        <Link
                                            v-if="can('doctors.view')"
                                            :href="`/admin/doctors/${doctor.id}`"
                                            class="action-btn w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200"
                                            :title="$t('a_view')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </Link>
                                        <!-- Edit -->
                                        <Link
                                            v-if="can('doctors.update')"
                                            :href="`/admin/doctors/${doctor.id}/edit`"
                                            class="action-btn w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-all duration-200"
                                            :title="$t('a_edit')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <!-- Delete -->
                                        <button
                                            v-if="can('doctors.delete')"
                                            @click="confirmDelete(doctor.id)"
                                            class="action-btn w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all duration-200"
                                            :title="$t('a_delete')"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="!doctors.data || doctors.data.length === 0" class="empty-state py-16 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm font-medium">{{ $t('a_no_doctors_found') || (isRtl ? 'لا يوجد أطباء' : 'No doctors found') }}</p>
                        <p class="text-gray-400 text-xs mt-1">{{ isRtl ? 'جرب تغيير عوامل البحث' : 'Try adjusting your search filters' }}</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="doctors.links && doctors.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        {{ $t('a_showing') }} <span class="font-semibold text-gray-700">{{ doctors.from }}</span>
                        {{ $t('a_to') }} <span class="font-semibold text-gray-700">{{ doctors.to }}</span>
                        {{ $t('a_of') }} <span class="font-semibold text-gray-700">{{ doctors.total }}</span>
                        {{ $t('a_results') }}
                    </p>
                    <nav class="flex items-center gap-1" :class="isRtl ? 'flex-row-reverse' : ''">
                        <template v-for="link in doctors.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                class="pagination-link px-3 py-1.5 text-sm rounded-lg border transition-all duration-200"
                                :class="link.active
                                    ? 'text-white border-transparent shadow-sm'
                                    : 'text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                :style="link.active ? 'background-color: #C4A265;' : ''"
                                preserve-state
                            />
                            <span v-else v-html="link.label" class="px-3 py-1.5 text-sm text-gray-300" />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="deleteId" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="cancelDelete">
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
                    <div class="modal-content relative bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm z-10">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 text-center mb-2">{{ $t('a_confirm_delete') || (isRtl ? 'تأكيد الحذف' : 'Confirm Delete') }}</h3>
                        <p class="text-sm text-gray-500 text-center mb-6">{{ $t('a_confirm_delete_doctor') || (isRtl ? 'هل أنت متأكد من حذف هذا الطبيب؟' : 'Are you sure you want to delete this doctor?') }}</p>
                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors duration-200"
                            >
                                {{ $t('a_cancel') || (isRtl ? 'إلغاء' : 'Cancel') }}
                            </button>
                            <button
                                @click="executeDelete"
                                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-medium text-white bg-red-500 hover:bg-red-600 transition-colors duration-200 shadow-sm"
                            >
                                {{ $t('a_delete') || (isRtl ? 'حذف' : 'Delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>

<style scoped>
/* Animations */
.header-section,
.filters-bar,
.table-container {
    opacity: 0;
    transform: translateY(16px);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.animate-in {
    opacity: 1;
    transform: translateY(0);
}
.delay-1 { transition-delay: 100ms; }
.delay-2 { transition-delay: 200ms; }
.delay-3 { transition-delay: 300ms; }

/* Stats Cards */
.stat-card {
    opacity: 0;
    transform: translateY(12px);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.animate-in.delay-1 .stat-card,
.delay-1.animate-in .stat-card {
    opacity: 1;
    transform: translateY(0);
}
.animate-in .stat-card:nth-child(1) { transition-delay: 120ms; }
.animate-in .stat-card:nth-child(2) { transition-delay: 180ms; }
.animate-in .stat-card:nth-child(3) { transition-delay: 240ms; }
.animate-in .stat-card:nth-child(4) { transition-delay: 300ms; }

/* Table Row Animation */
.doctor-row {
    opacity: 0;
    transform: translateY(8px);
    transition: opacity 0.4s ease, transform 0.4s ease, background-color 0.2s ease;
}
.doctor-row.row-animate {
    opacity: 1;
    transform: translateY(0);
}

/* Search Input Focus */
.search-input:focus {
    ring-color: var(--focus-ring);
    box-shadow: 0 0 0 3px rgba(196, 162, 101, 0.2);
    border-color: #C4A265;
}

/* Action Buttons */
.action-btn {
    opacity: 0.6;
    transform: scale(0.95);
    transition: all 0.2s ease;
}
.group:hover .action-btn {
    opacity: 1;
    transform: scale(1);
}

/* Doctor Avatar */
.doctor-avatar {
    transition: all 0.3s ease;
}
.group:hover .doctor-avatar {
    ring-color: rgba(196, 162, 101, 0.4);
    transform: scale(1.05);
}

/* Module Tab */
.module-tab {
    position: relative;
    overflow: hidden;
}

/* Pagination */
.pagination-link {
    min-width: 36px;
    text-align: center;
}

/* Modal */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: scale(0.9) translateY(10px);
}
.modal-content {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Header Icon */
.header-icon {
    box-shadow: 0 4px 12px rgba(196, 162, 101, 0.3);
}

/* Add Button */
.add-btn {
    background-size: 200% 200%;
    animation: shimmer 3s ease-in-out infinite;
}
@keyframes shimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}
</style>
