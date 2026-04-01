<script setup>
import { ref, watch, computed } from 'vue';
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
let searchTimeout = null;

const activeModules = computed(() => {
    const mods = [];
    if (props.modules) {
        Object.values(props.modules).forEach(m => {
            if (m.enabled) mods.push(m);
        });
    }
    return mods;
});

function applyFilters() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/doctors', {
            search: search.value || undefined,
            module: moduleFilter.value || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
}

watch(search, applyFilters);
watch(moduleFilter, () => {
    clearTimeout(searchTimeout);
    router.get('/admin/doctors', {
        search: search.value || undefined,
        module: moduleFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

function deleteDoctor(id) {
    if (window.confirm(t('a_confirm_delete_doctor'))) {
        router.delete(`/admin/doctors/${id}`);
    }
}

const statusColors = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
};
</script>

<template>
    <AdminLayout :title="$t('a_doctors')">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">{{ $t('a_doctors') }}</h1>
                <Link
                    v-if="can('doctors.create')"
                    href="/admin/doctors/create"
                    class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition"
                    style="background-color: #C4A265;"
                >
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New
                </Link>
            </div>

            <!-- Module Tabs -->
            <div v-if="activeModules.length > 1" class="bg-white rounded-lg shadow-sm p-1.5 flex gap-1 flex-wrap">
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

            <div class="bg-white rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="$t('a_search_doctors')"
                    class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-200 focus:border-transparent"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_name') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_module') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_specialization') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_status') }}</th>
                                <th class="px-6 py-3 ltr:text-left rtl:ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_order') }}</th>
                                <th class="px-6 py-3 ltr:text-right rtl:text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('a_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="doctor in doctors.data" :key="doctor.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div v-if="doctor.photo" class="w-8 h-8 rounded-full overflow-hidden ltr:mr-3 rtl:ml-3 flex-shrink-0">
                                            <img :src="doctor.photo.startsWith('http') ? doctor.photo : `/storage/${doctor.photo}`" :alt="doctor.name_en" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <Link v-if="can('doctors.view')" :href="`/admin/doctors/${doctor.id}`" class="text-sm font-medium hover:underline" style="color: #C4A265;">{{ doctor.name_en }}</Link>
                                            <span v-else class="text-sm font-medium text-gray-900">{{ doctor.name_en }}</span>
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <span v-if="doctor.user_id" class="inline-flex items-center gap-1 text-[10px] font-medium text-green-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ $t('a_has_login') }}
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                    {{ $t('a_no_login') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="doctor.module === 'dental' ? 'bg-cyan-50 text-cyan-700' : 'bg-purple-50 text-purple-700'"
                                    >
                                        <svg v-if="doctor.module === 'dental'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" /></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                        {{ doctor.module === 'dental' ? $t('a_dental') : $t('a_derma') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doctor.specialization_en || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="statusColors[doctor.status] || 'bg-gray-100 text-gray-800'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ doctor.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ doctor.display_order ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap ltr:text-right rtl:text-left text-sm ltr:space-x-3 rtl:space-x-reverse rtl:space-x-3">
                                    <Link v-if="can('doctors.view')" :href="`/admin/doctors/${doctor.id}`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_view') }}</Link>
                                    <Link v-if="can('doctors.update')" :href="`/admin/doctors/${doctor.id}/edit`" class="font-medium hover:underline" style="color: #C4A265;">{{ $t('a_edit') }}</Link>
                                    <button v-if="can('doctors.delete')" @click="deleteDoctor(doctor.id)" class="font-medium text-red-600 hover:underline">{{ $t('a_delete') }}</button>
                                </td>
                            </tr>
                            <tr v-if="!doctors.data || doctors.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('a_no_doctors_found') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="doctors.links && doctors.links.length > 3" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <p class="text-sm text-gray-500">{{ $t('a_showing') }} {{ doctors.from }} {{ $t('a_to') }} {{ doctors.to }} {{ $t('a_of') }} {{ doctors.total }} {{ $t('a_results') }}</p>
                    <nav class="flex space-x-1">
                        <template v-for="link in doctors.links" :key="link.label">
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
