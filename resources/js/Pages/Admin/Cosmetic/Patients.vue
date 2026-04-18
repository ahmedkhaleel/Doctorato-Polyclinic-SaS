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
watch(search, v => { clearTimeout(tm); tm = setTimeout(() => router.get('/admin/cosmetic/patients', { search: v || undefined }, { preserveState: true, preserveScroll: true }), 400); });

function t(en, ar) { return isRtl.value ? ar : en; }
</script>

<template>
    <div class="space-y-6 pb-10">
        <!-- Navy Hero -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1B365D] via-[#1B365D] to-[#0F2444] shadow-xl">
            <div class="pointer-events-none absolute -top-16 -end-16 h-56 w-56 rounded-full bg-[#C4A265]/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 start-1/3 h-48 w-48 rounded-full bg-[#C4A265]/10 blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-[#C4A265] to-transparent"></div>
            <div class="relative p-4 md:p-7 flex items-start gap-3 md:gap-4">
                <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-[#C4A265] to-[#8B7043] flex items-center justify-center shadow-lg flex-shrink-0">
                    <svg class="w-6 h-6 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-[3px] w-6 bg-[#C4A265] rounded-full"></span>
                        <span class="text-[10px] font-bold text-[#C4A265] tracking-[0.25em] uppercase">{{ isRtl ? 'الجلدية والتجميل' : 'DERMA & COSMETIC' }}</span>
                    </div>
                    <h1 class="text-xl md:text-3xl font-extrabold text-white tracking-tight">{{ t('Cosmetic Patients', 'مرضى التجميل') }}</h1>
                    <p class="text-xs md:text-sm text-white/70 mt-1">{{ t('All patients with cosmetic visits', 'جميع المرضى لديهم زيارات تجميل') }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <input v-model="search" type="text" :placeholder="t('Search…', 'بحث…')" class="doctorato-input w-full md:w-96 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-[#C4A265]/30 focus:border-[#1B365D]" />
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-sm">
                    <thead class="bg-[#1B365D]/5 text-[#1B365D]">
                        <tr>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Name', 'الاسم') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden sm:table-cell">{{ t('Phone', 'الهاتف') }}</th>
                            <th class="text-start px-5 py-3 text-[11px] font-bold uppercase tracking-wider hidden md:table-cell">{{ t('File #', 'رقم الملف') }}</th>
                            <th class="text-end px-5 py-3 text-[11px] font-bold uppercase tracking-wider">{{ t('Actions', 'إجراءات') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="p in patients.data" :key="p.id" class="hover:bg-[#C4A265]/5 transition">
                            <td class="px-5 py-3 font-medium text-slate-800">{{ p.full_name }}</td>
                            <td class="px-5 py-3 text-slate-600 hidden sm:table-cell">{{ p.phone || '-' }}</td>
                            <td class="px-5 py-3 text-slate-600 hidden md:table-cell">{{ p.file_number || '-' }}</td>
                            <td class="px-5 py-3 text-end">
                                <Link :href="`/admin/patients/${p.id}?tab=cosmetic`" class="text-[#C4A265] hover:text-[#8B7043] text-xs font-bold">{{ t('Open', 'فتح') }}</Link>
                            </td>
                        </tr>
                        <tr v-if="!patients.data.length"><td colspan="4" class="text-center py-8 text-slate-400">{{ t('No patients', 'لا يوجد مرضى') }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div v-if="patients.links && patients.links.length > 3" class="flex justify-center gap-1 p-4 border-t border-slate-100">
                <Link v-for="l in patients.links" :key="l.label" :href="l.url || '#'"
                    :class="['px-3 py-1.5 rounded-lg text-xs font-semibold', l.active ? 'bg-gradient-to-r from-[#C4A265] to-[#8B7043] text-white' : l.url ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-slate-50 text-slate-300 pointer-events-none']"
                    v-html="l.label"></Link>
            </div>
        </div>
    </div>
</template>
