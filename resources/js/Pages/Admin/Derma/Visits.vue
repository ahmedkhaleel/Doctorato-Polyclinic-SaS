<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({ visits: Object, filters: Object });

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
let tm = null;
function apply() {
    clearTimeout(tm);
    tm = setTimeout(() => router.get('/admin/derma/visits', {
        search: search.value || undefined, status: status.value || undefined,
    }, { preserveState: true, preserveScroll: true }), 400);
}
watch([search, status], apply);

function t(en, ar) { return isRtl.value ? ar : en; }
function fmt(d) { if (!d) return '-'; return new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB'); }
</script>

<template>
    <div class="space-y-6 pb-10">
        <div class="bg-gradient-to-br from-amber-600 to-amber-500 rounded-2xl p-6 shadow-lg">
            <h1 class="text-2xl font-bold text-white">{{ t('Derma Visits', 'زيارات الجلدية') }}</h1>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-wrap gap-3">
            <input v-model="search" type="text" :placeholder="t('Search patient…', 'بحث عن مريض…')"
                class="doctorato-input flex-1 min-w-[220px] px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30/20 focus:border-[#1B365D]" />
            <select v-model="status" class="doctorato-input px-4 py-2.5 border border-gray-200 rounded-xl text-sm">
                <option value="">{{ t('All statuses', 'كل الحالات') }}</option>
                <option value="scheduled">{{ t('Scheduled', 'مجدولة') }}</option>
                <option value="in_progress">{{ t('In progress', 'قيد التنفيذ') }}</option>
                <option value="completed">{{ t('Completed', 'مكتملة') }}</option>
                <option value="cancelled">{{ t('Cancelled', 'ملغاة') }}</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Date', 'التاريخ') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500">{{ t('Patient', 'المريض') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden md:table-cell">{{ t('Doctor', 'الطبيب') }}</th>
                        <th class="text-start px-5 py-3 font-semibold text-gray-500 hidden lg:table-cell">{{ t('Status', 'الحالة') }}</th>
                        <th class="text-end px-5 py-3 font-semibold text-gray-500">{{ t('Actions', 'إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="v in visits.data" :key="v.id" class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-700">{{ fmt(v.visit_date) }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800">{{ v.patient?.full_name || '-' }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ v.doctor?.name_ar || v.doctor?.name_en_ar || v.doctor?.name_ar || v.doctor?.name_en_en || '-' }}</td>
                        <td class="px-5 py-3 text-gray-600 hidden lg:table-cell">{{ v.status }}</td>
                        <td class="px-5 py-3 text-end">
                            <Link :href="`/admin/visits/${v.id}`" class="text-amber-600 text-xs font-semibold">{{ t('Open', 'فتح') }}</Link>
                        </td>
                    </tr>
                    <tr v-if="!visits.data.length"><td colspan="5" class="text-center py-8 text-gray-400">{{ t('No visits', 'لا توجد زيارات') }}</td></tr>
                </tbody>
            </table>
            <div v-if="visits.links && visits.links.length > 3" class="flex justify-center gap-1 p-4 border-t">
                <Link v-for="l in visits.links" :key="l.label" :href="l.url || '#'"
                    :class="['px-3 py-1.5 rounded-lg text-xs', l.active ? 'bg-amber-600 text-white' : l.url ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-50 text-gray-300 pointer-events-none']"
                    v-html="l.label"></Link>
            </div>
        </div>
    </div>
</template>
