<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    patients: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const ACCENT = '#0D9488';
const search = ref(props.filters.search || '');
let t = null;
watch(search, (v) => {
    clearTimeout(t);
    t = setTimeout(() => router.get(route('doctor.physiotherapy.patients.index'), { search: v }, { preserveState: true, replace: true }), 350);
});
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'مرضى العلاج الطبيعي' : 'Physiotherapy Patients' }}</h1>
            <div class="relative">
                <svg class="w-4 h-4 absolute top-3 start-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input v-model="search" type="text" :placeholder="isRtl ? 'بحث بالاسم أو الهاتف…' : 'Search name or phone…'"
                       class="ps-9 pe-4 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 outline-none w-64" />
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-start font-medium px-5 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="text-start font-medium px-5 py-3">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                        <th class="text-start font-medium px-5 py-3">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                        <th class="text-center font-medium px-5 py-3">{{ isRtl ? 'خطط نشطة' : 'Active Plans' }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in patients.data" :key="p.id" class="border-t border-gray-50 hover:bg-gray-50/60">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ p.full_name }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ p.file_number }}</td>
                        <td class="px-5 py-3 text-gray-500 tabular-nums" dir="ltr">{{ p.phone }}</td>
                        <td class="px-5 py-3 text-center">
                            <span v-if="p.active_plans" class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 rounded-full text-xs font-semibold"
                                  :style="{ backgroundColor: ACCENT + '1A', color: ACCENT }">{{ p.active_plans }}</span>
                            <span v-else class="text-gray-300">—</span>
                        </td>
                        <td class="px-5 py-3 text-end">
                            <Link :href="route('doctor.physiotherapy.patients.show', p.id)" class="text-teal-600 hover:text-teal-700 font-medium text-sm">
                                {{ isRtl ? 'الملف' : 'Open' }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!patients.data.length">
                        <td colspan="5" class="px-5 py-12 text-center text-gray-400">{{ isRtl ? 'لا يوجد مرضى' : 'No patients found' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="patients.links && patients.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <Link v-for="(l, i) in patients.links" :key="i" :href="l.url || ''" v-html="l.label"
                  class="px-3 py-1.5 rounded-lg text-sm" :class="[l.active ? 'text-white' : 'text-gray-600 hover:bg-gray-100', !l.url && 'opacity-40 pointer-events-none']"
                  :style="l.active ? { backgroundColor: ACCENT } : {}" />
        </div>
    </div>
</template>
