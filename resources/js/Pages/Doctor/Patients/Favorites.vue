<script setup>
import { computed } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import DoctorLayout from '@/Layouts/DoctorLayout.vue';
import { useConfirm } from '@/Composables/useConfirm.js';

defineOptions({ layout: DoctorLayout });

const props = defineProps({ patients: Object });

const page = usePage();
const { confirm } = useConfirm();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const t = (ar, en) => (isRtl.value ? ar : en);

function unfavorite(p) {
    confirm(t('إزالة من المفضّلة؟', 'Remove from favorites?'), () => {
        router.post(`/doctor/patients/${p.id}/favorite/toggle`, {}, { preserveScroll: true });
    });
}
</script>

<template>
    <div class="p-4 md:p-6 max-w-4xl mx-auto space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#C4A265,#8B7043)">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ t('مرضاي المفضّلون', 'Favorite Patients') }}</h1>
                <p class="text-xs text-gray-500">{{ t('وصول سريع لمرضاك المميّزين', 'Quick access to your starred patients') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="text-gray-400 text-xs border-b border-gray-100 bg-gray-50/50">
                    <th class="text-start px-4 py-3">{{ t('المريض', 'Patient') }}</th>
                    <th class="px-4 py-3">{{ t('رقم الملف', 'File #') }}</th>
                    <th class="px-4 py-3">{{ t('الهاتف', 'Phone') }}</th>
                    <th class="px-4 py-3">{{ t('زياراتي', 'My Visits') }}</th>
                    <th class="px-4 py-3"></th>
                </tr></thead>
                <tbody>
                    <tr v-for="p in patients.data" :key="p.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <Link :href="`/doctor/patients/${p.id}`" class="hover:text-[#C4A265]">{{ p.full_name }}</Link>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">{{ p.file_number }}</td>
                        <td class="px-4 py-3 text-center text-gray-600" dir="ltr">{{ p.phone }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ p.visits_count }}</td>
                        <td class="px-4 py-3 text-end">
                            <button @click="unfavorite(p)" class="text-xs font-semibold text-[#C4A265] hover:underline">{{ t('إزالة', 'Remove') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!patients.data.length"><td colspan="5" class="text-center text-gray-400 py-12">{{ t('لا يوجد مرضى مفضّلون بعد', 'No favorite patients yet') }}</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="patients.links && patients.links.length > 3" class="flex flex-wrap gap-1 justify-center">
            <component :is="link.url ? Link : 'span'" v-for="link in patients.links" :key="link.label"
                       :href="link.url || undefined"
                       class="px-3 py-1.5 rounded-lg text-sm border"
                       :class="link.active ? 'bg-[#C4A265] text-white border-[#C4A265]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                       v-html="link.label" />
        </div>
    </div>
</template>
