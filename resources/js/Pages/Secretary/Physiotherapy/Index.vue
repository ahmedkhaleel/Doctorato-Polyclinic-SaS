<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import SecretaryLayout from '@/Layouts/SecretaryLayout.vue';

defineOptions({ layout: SecretaryLayout });

const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');
const ACCENT = '#0D9488';
const t = (en, ar) => (isRtl.value ? ar : en);

const props = defineProps({
    appointments: { type: Array, default: () => [] },
    roster: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
let tmr = null;
function onSearch() {
    clearTimeout(tmr);
    tmr = setTimeout(() => router.get('/secretary/physiotherapy/overview', { search: search.value }, { preserveState: true, replace: true }), 350);
}
const dateLabel = (d) => (d ? new Date(d).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) : '');
const docName = (d) => (d ? (isRtl.value ? d.name_ar : d.name_en) : '—');
</script>

<template>
    <div class="space-y-5" :dir="isRtl ? 'rtl' : 'ltr'">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800">{{ t('Physiotherapy — Front Desk', 'العلاج الطبيعي — مكتب الاستقبال') }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">{{ t('Scheduling & billing only — no clinical records.', 'الحجوزات والفواتير فقط — بدون سجلات طبية.') }}</p>
            </div>
            <input v-model="search" @input="onSearch" type="text" :placeholder="t('Search…', 'بحث…')"
                class="px-4 py-2 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 outline-none w-64" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Upcoming appointments -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Upcoming Appointments', 'المواعيد القادمة') }}</h2>
                <p v-if="!appointments.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No upcoming appointments', 'لا توجد مواعيد') }}</p>
                <table v-else class="w-full text-sm">
                    <tbody>
                        <tr v-for="a in appointments" :key="a.id" class="border-b border-gray-50 last:border-0">
                            <td class="py-2 font-medium text-gray-700">{{ a.patient?.full_name }}</td>
                            <td class="py-2 text-gray-500 text-xs">{{ docName(a.doctor) }}</td>
                            <td class="py-2 text-gray-500 text-xs">{{ dateLabel(a.date) }} {{ a.time }}</td>
                            <td class="py-2 text-end"><span class="text-[11px] px-2 py-0.5 rounded-full" :class="a.status === 'confirmed' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'">{{ a.status }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Active plans (scheduling info only) -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <h2 class="font-semibold text-gray-800 mb-3">{{ t('Active Plans — sessions remaining', 'الخطط النشطة — الجلسات المتبقية') }}</h2>
                <p v-if="!plans.length" class="text-sm text-gray-400 py-6 text-center">{{ t('No active plans', 'لا توجد خطط نشطة') }}</p>
                <ul v-else class="space-y-2">
                    <li v-for="p in plans" :key="p.id" class="flex items-center justify-between text-sm">
                        <span class="text-gray-700">{{ p.patient?.full_name }}</span>
                        <span class="text-xs text-gray-500">{{ p.completed_sessions }}/{{ p.estimated_sessions }} · {{ p.frequency }}
                            <span class="font-semibold" :style="{ color: ACCENT }">({{ p.sessions_remaining }} {{ t('left', 'متبقٍ') }})</span></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Prepaid packages — remaining sessions -->
        <div v-if="packages.length" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h2 class="font-semibold text-gray-800 mb-3">{{ t('Prepaid Packages — remaining sessions', 'الباقات المدفوعة — الجلسات المتبقية') }}</h2>
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <li v-for="p in packages" :key="p.id" class="flex items-center justify-between text-sm border-b border-gray-50 pb-1">
                    <span class="text-gray-700">{{ p.patient?.full_name }} <span class="text-xs text-gray-400">· {{ isRtl ? p.package?.name_ar : p.package?.name_en }}</span></span>
                    <span class="text-xs"><span class="font-semibold" :style="{ color: ACCENT }">{{ p.sessions_remaining }}</span> / {{ p.total_sessions }} {{ t('left', 'متبقٍ') }}</span>
                </li>
            </ul>
        </div>

        <!-- Roster with outstanding balance -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <h2 class="font-semibold text-gray-800 mb-3">{{ t('Patient Roster', 'سجل المرضى') }}</h2>
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="text-start font-medium pb-2">{{ t('Patient', 'المريض') }}</th>
                        <th class="text-start font-medium pb-2">{{ t('File #', 'رقم الملف') }}</th>
                        <th class="text-start font-medium pb-2">{{ t('Phone', 'الهاتف') }}</th>
                        <th class="text-end font-medium pb-2">{{ t('Outstanding', 'المستحق') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in roster" :key="r.id" class="border-t border-gray-50">
                        <td class="py-2 font-medium text-gray-700">{{ r.full_name }}</td>
                        <td class="py-2 text-gray-500">{{ r.file_number }}</td>
                        <td class="py-2 text-gray-500 tabular-nums" dir="ltr">{{ r.phone }}</td>
                        <td class="py-2 text-end tabular-nums" :class="r.outstanding > 0 ? 'text-red-500 font-medium' : 'text-gray-400'">{{ r.outstanding > 0 ? Number(r.outstanding).toLocaleString() : '—' }}</td>
                    </tr>
                    <tr v-if="!roster.length"><td colspan="4" class="py-8 text-center text-gray-400">{{ t('No patients', 'لا يوجد مرضى') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
