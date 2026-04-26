<script setup>
import { computed, ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    patients: Object,  // paginator
    stats:    Object,
    rules:    Object,
    filters:  Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const search = ref(props.filters?.search || '');

let timer = null;
watch(search, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/loyalty', { search: v }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});
</script>

<template>
    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold text-[#1B365D]">
                {{ isRtl ? 'نظام نقاط الولاء' : 'Loyalty Points' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ isRtl ? 'متابعة وضبط أرصدة المرضى من النقاط' : 'Monitor and adjust patient point balances' }}
            </p>
        </div>

        <!-- Stat row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-[#C4A265]/20 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'الرصيد القائم' : 'Outstanding Balance' }}</p>
                <p class="text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">{{ stats.total_outstanding.toLocaleString() }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ isRtl ? 'نقطة' : 'pts' }}</p>
            </div>
            <div class="bg-white rounded-xl border border-[#C4A265]/20 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'مرضى لديهم نقاط' : 'Patients w/ points' }}</p>
                <p class="text-2xl font-extrabold text-[#1B365D] mt-1 tabular-nums">{{ stats.patients_with_pts.toLocaleString() }}</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-100 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'مكتسب (30 يوم)' : 'Earned (30d)' }}</p>
                <p class="text-2xl font-extrabold text-emerald-600 mt-1 tabular-nums">+{{ stats.awarded_30d.toLocaleString() }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-100 p-4">
                <p class="text-[10px] uppercase tracking-wider text-gray-500">{{ isRtl ? 'مستبدل (30 يوم)' : 'Redeemed (30d)' }}</p>
                <p class="text-2xl font-extrabold text-amber-600 mt-1 tabular-nums">-{{ stats.redeemed_30d.toLocaleString() }}</p>
            </div>
        </div>

        <!-- Rules summary -->
        <div class="bg-gradient-to-r from-[#FAF7F0] to-white rounded-xl border border-[#C4A265]/30 p-4 mb-6">
            <p class="text-[11px] font-bold text-[#8B7043] tracking-wider uppercase mb-2">{{ isRtl ? 'القواعد الحالية' : 'Active rules' }}</p>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 text-xs text-gray-700">
                <p><span class="font-semibold">{{ isRtl ? 'لكل زيارة:' : 'Per visit:' }}</span> {{ rules.points_per_visit }} pts</p>
                <p><span class="font-semibold">{{ isRtl ? 'لكل عملة:' : 'Per currency:' }}</span> {{ rules.points_per_egp }} pt/{{ rules.currency }}</p>
                <p><span class="font-semibold">{{ isRtl ? 'سعر الاستبدال:' : 'Redeem rate:' }}</span> {{ rules.redeem_rate }} {{ rules.currency }}/pt</p>
                <p><span class="font-semibold">{{ isRtl ? 'حد الاستبدال:' : 'Min redeem:' }}</span> {{ rules.min_redeem }} pts</p>
                <p><span class="font-semibold">{{ isRtl ? 'انتهاء الصلاحية:' : 'Expiry:' }}</span>
                    {{ rules.expiry_months > 0 ? rules.expiry_months + (isRtl ? ' شهر' : ' months') : (isRtl ? 'بدون' : 'never') }}
                </p>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">
                {{ isRtl ? 'لتعديل القواعد، انتقل إلى الإعدادات → القيم العامة' : 'Edit rules in Settings → General values' }}
            </p>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 mb-4">
            <input v-model="search" type="text"
                   :placeholder="isRtl ? 'بحث بالاسم / رقم الملف / الهاتف...' : 'Search by name / file # / phone...'"
                   class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1B365D]/20 focus:border-[#1B365D]" />
        </div>

        <!-- Patients table -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-start px-4 py-3">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th class="text-start px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                        <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'الهاتف' : 'Phone' }}</th>
                        <th class="text-end px-4 py-3">{{ isRtl ? 'الرصيد' : 'Balance' }}</th>
                        <th class="text-end px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'القيمة' : 'Value' }}</th>
                        <th class="text-end px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in patients.data" :key="p.id" class="hover:bg-gray-50/50 transition">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ p.full_name }}</td>
                        <td class="px-4 py-3 text-gray-500 hidden sm:table-cell font-mono text-xs">{{ p.file_number || '-' }}</td>
                        <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ p.phone || '-' }}</td>
                        <td class="px-4 py-3 text-end font-bold tabular-nums" :class="p.balance > 0 ? 'text-[#1B365D]' : 'text-gray-300'">
                            {{ p.balance.toLocaleString() }}
                        </td>
                        <td class="px-4 py-3 text-end text-gray-500 tabular-nums hidden sm:table-cell">
                            {{ formatCurrency(p.egp_value) }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <Link :href="`/admin/loyalty/${p.id}`"
                                  class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-[#1B365D] text-white text-xs font-semibold hover:bg-[#22406F]">
                                {{ isRtl ? 'إدارة' : 'Manage' }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!patients.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">
                            {{ isRtl ? 'لا يوجد مرضى مطابقين' : 'No matching patients' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="patients.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in patients.links" :key="link.label"
                      :href="link.url || '#'"
                      v-html="link.label"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium border',
                        link.active ? 'bg-[#1B365D] text-white border-[#1B365D]'
                                    : link.url ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'
                                               : 'bg-gray-50 text-gray-300 border-gray-100 cursor-not-allowed'
                      ]" />
            </div>
        </div>
    </div>
</template>
