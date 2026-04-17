<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ patients: Object, filters: Object });

const search = ref(props.filters?.search || '');
let tm = null;
watch(search, v => { clearTimeout(tm); tm = setTimeout(() => router.get('/admin/derma/patients', { search: v || undefined }, { preserveState: true, preserveScroll: true }), 400); });

function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-amber-600 to-amber-500 rounded-2xl p-6 shadow-lg">
            <h1 class="text-2xl font-bold text-white">{{ t('Derma Patients', 'مرضى الجلدية') }}</h1>
            <p class="text-amber-100/80 text-sm mt-1">{{ t('All patients with dermatology visits', 'جميع المرضى الذين لديهم زيارات جلدية') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <input v-model="search" type="text"
                :placeholder="t('Search by name, phone, file #', 'بحث بالاسم أو الهاتف أو رقم الملف')"
                class="w-full md:w-96 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400" />
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 text-gray-500 font-semibold">{{ t('Name', 'الاسم') }}</th>
                        <th class="text-start px-5 py-3 text-gray-500 font-semibold hidden sm:table-cell">{{ t('Phone', 'الهاتف') }}</th>
                        <th class="text-start px-5 py-3 text-gray-500 font-semibold hidden md:table-cell">{{ t('File #', 'رقم الملف') }}</th>
                        <th class="text-start px-5 py-3 text-gray-500 font-semibold hidden lg:table-cell">{{ t('Gender', 'النوع') }}</th>
                        <th class="text-end px-5 py-3 text-gray-500 font-semibold">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in patients.data" :key="p.id" class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ p.full_name }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden sm:table-cell">{{ p.phone || '-' }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ p.file_number || '-' }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden lg:table-cell">{{ p.gender || '-' }}</td>
                        <td class="px-5 py-3 text-end">
                            <Link :href="`/admin/patients/${p.id}?tab=derma`" class="text-amber-600 text-xs font-semibold">{{ t('Open', 'فتح') }}</Link>
                        </td>
                    </tr>
                    <tr v-if="!patients.data.length"><td colspan="5" class="text-center py-8 text-gray-400">{{ t('No patients', 'لا يوجد مرضى') }}</td></tr>
                </tbody>
            </table>
            <div v-if="patients.links && patients.links.length > 3" class="flex justify-center gap-1 p-4 border-t">
                <Link v-for="l in patients.links" :key="l.label" :href="l.url || '#'"
                    :class="['px-3 py-1.5 rounded-lg text-xs', l.active ? 'bg-amber-600 text-white' : l.url ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-50 text-gray-300 pointer-events-none']"
                    v-html="l.label"></Link>
            </div>
        </div>
    </div>
</template>
