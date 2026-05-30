<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';

defineOptions({ layout: DoctorLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#8B5CF6';

const props = defineProps({
    patients: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
function apply() {
    router.get(route('doctor.derma.patients.index'), { search: search.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <div class="space-y-4" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center gap-3">
            <span class="w-2 h-8 rounded-full" :style="{ background: ACCENT }"></span>
            <h1 class="text-xl font-bold text-gray-800">{{ isRtl ? 'مرضى الجلدية والتجميل' : 'Derma & Cosmetic Patients' }}</h1>
        </div>

        <div class="relative max-w-md">
            <input v-model="search" @keyup.enter="apply" type="text" :placeholder="isRtl ? 'بحث بالاسم أو الهاتف…' : 'Search…'"
                   class="w-full rounded-xl border-gray-200 ps-10 py-2.5 text-sm focus:border-violet-400 focus:ring-violet-400" />
            <svg class="w-5 h-5 text-gray-400 absolute top-2.5 start-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div v-if="patients.data.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link v-for="p in patients.data" :key="p.id" :href="route('doctor.derma.patients.show', p.id)"
                  class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:-translate-y-0.5 transition-all flex items-center gap-3">
                <div class="w-11 h-11 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 font-bold">{{ (p.full_name || '?').charAt(0) }}</div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 group-hover:text-violet-700 transition truncate">{{ p.full_name }}</p>
                    <p class="text-xs text-gray-400">{{ p.phone }} · {{ p.file_number }}</p>
                </div>
            </Link>
        </div>
        <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 py-16 text-center text-gray-400">
            {{ isRtl ? 'لا يوجد مرضى' : 'No patients' }}
        </div>

        <div v-if="patients.links && patients.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <template v-for="(l, i) in patients.links" :key="i">
                <Link v-if="l.url" :href="l.url" v-html="l.label" preserve-scroll
                      class="px-3 py-1.5 rounded-lg text-sm" :class="l.active ? 'bg-[#1B365D] text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'" />
                <span v-else v-html="l.label" class="px-3 py-1.5 rounded-lg text-sm text-gray-300"></span>
            </template>
        </div>
    </div>
</template>
