<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    patients: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
let tmr = null;
watch(search, (v) => {
    clearTimeout(tmr);
    tmr = setTimeout(() => router.get('/admin/physiotherapy/patients', { search: v }, { preserveState: true, replace: true }), 350);
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h1 class="text-xl font-bold text-gray-800">{{ t('Physiotherapy Patients', 'مرضى العلاج الطبيعي') }}</h1>
                <input v-model="search" type="text" :placeholder="t('Search…', 'بحث…')"
                    class="px-4 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 outline-none w-64" />
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-start font-medium px-5 py-3">{{ t('Patient', 'المريض') }}</th>
                            <th class="text-start font-medium px-5 py-3">{{ t('File #', 'رقم الملف') }}</th>
                            <th class="text-start font-medium px-5 py-3">{{ t('Phone', 'الهاتف') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Active Plans', 'خطط نشطة') }}</th>
                            <th class="text-center font-medium px-5 py-3">{{ t('Sessions', 'الجلسات') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in patients.data" :key="p.id" class="border-t border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ p.full_name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ p.file_number }}</td>
                            <td class="px-5 py-3 text-gray-500 tabular-nums" dir="ltr">{{ p.phone }}</td>
                            <td class="px-5 py-3 text-center">
                                <span v-if="p.active_plans" class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs font-semibold" :style="{ backgroundColor: ACCENT + '1A', color: ACCENT }">{{ p.active_plans }}</span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600 tabular-nums">{{ p.sessions }}</td>
                        </tr>
                        <tr v-if="!patients.data.length"><td colspan="5" class="px-5 py-12 text-center text-gray-400">{{ t('No patients found', 'لا يوجد مرضى') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="patients.links && patients.links.length > 3" class="flex flex-wrap gap-1 justify-center">
                <Link v-for="(l, i) in patients.links" :key="i" :href="l.url || ''" v-html="l.label"
                    class="px-3 py-1.5 rounded-lg text-sm" :class="[l.active ? 'text-white' : 'text-gray-600 hover:bg-gray-100', !l.url && 'opacity-40 pointer-events-none']"
                    :style="l.active ? { backgroundColor: ACCENT } : {}" />
            </div>
        </div>
    </AdminLayout>
</template>
