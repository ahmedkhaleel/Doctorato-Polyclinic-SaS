<script setup>
import { computed, ref } from 'vue';
import { router, useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    patient:      Object,
    balance:      Number,
    egp_value:    Number,
    currency:     String,
    transactions: Object,
});

const { formatCurrency } = useCurrency();
const page = usePage();
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const showAdjust = ref(false);
const form = useForm({ points: '', reason: '' });

function submitAdjust() {
    form.post(`/admin/loyalty/${props.patient.id}/adjust`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAdjust.value = false;
            // Refresh page data
            router.reload({ only: ['balance', 'egp_value', 'transactions'] });
        },
    });
}

function typeLabel(t) {
    const ar = { earn: 'كسب', redeem: 'استبدال', expire: 'انتهاء', adjust: 'تسوية' };
    const en = { earn: 'Earned', redeem: 'Redeemed', expire: 'Expired', adjust: 'Adjusted' };
    return (isRtl.value ? ar[t] : en[t]) || t;
}
function typeColor(t) {
    return {
        earn:   'bg-emerald-100 text-emerald-700',
        redeem: 'bg-amber-100 text-amber-700',
        expire: 'bg-gray-100 text-gray-600',
        adjust: 'bg-slate-100 text-slate-700',
    }[t] || 'bg-gray-100 text-gray-700';
}
</script>

<template>
    <div class="p-4 lg:p-6">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
            <div>
                <Link href="/admin/loyalty" class="text-xs text-[#1B365D] hover:underline mb-1 inline-block">
                    ← {{ isRtl ? 'العودة لقائمة المرضى' : 'Back to patients' }}
                </Link>
                <h1 class="text-2xl font-extrabold text-[#1B365D]">{{ patient.full_name }}</h1>
                <p class="text-xs text-gray-500 mt-0.5 font-mono">
                    {{ patient.file_number || '—' }}
                    <span v-if="patient.phone" class="ms-3">{{ patient.phone }}</span>
                </p>
            </div>
            <button @click="showAdjust = !showAdjust"
                    class="px-4 py-2 rounded-lg bg-[#C4A265] hover:bg-[#8B7043] text-white text-sm font-semibold">
                {{ isRtl ? 'تسوية يدوية' : 'Manual adjustment' }}
            </button>
        </div>

        <!-- Balance card -->
        <div class="bg-gradient-to-br from-[#1B365D] to-[#22406F] text-white rounded-2xl p-6 mb-6 flex items-center gap-6 flex-wrap">
            <div>
                <p class="text-[10px] uppercase tracking-wider text-[#C4A265]">{{ isRtl ? 'الرصيد الحالي' : 'Current balance' }}</p>
                <p class="text-4xl font-extrabold tabular-nums mt-1">{{ balance.toLocaleString() }} <span class="text-base font-normal text-white/70">{{ isRtl ? 'نقطة' : 'pts' }}</span></p>
            </div>
            <div class="h-12 w-px bg-white/20"></div>
            <div>
                <p class="text-[10px] uppercase tracking-wider text-[#C4A265]">{{ isRtl ? 'القيمة' : 'Value' }}</p>
                <p class="text-2xl font-bold mt-1 text-[#C4A265]">{{ formatCurrency(egp_value) }}</p>
            </div>
        </div>

        <!-- Adjust form -->
        <div v-if="showAdjust" class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
            <h3 class="text-sm font-bold text-amber-900 mb-3">{{ isRtl ? 'تسوية يدوية للنقاط' : 'Manual point adjustment' }}</h3>
            <form @submit.prevent="submitAdjust" class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'النقاط (+/-)' : 'Points (+/-)' }}
                        </label>
                        <input v-model="form.points" type="number" required
                               :placeholder="isRtl ? 'مثال: 100 أو -50' : 'e.g. 100 or -50'"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm tabular-nums" />
                        <p v-if="form.errors.points" class="text-xs text-red-600 mt-1">{{ form.errors.points }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-gray-700 mb-1">
                            {{ isRtl ? 'السبب (مطلوب)' : 'Reason (required)' }}
                        </label>
                        <input v-model="form.reason" type="text" required maxlength="255"
                               :placeholder="isRtl ? 'مثال: تعويض شكوى تأخير' : 'e.g. Goodwill credit for delay complaint'"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
                        <p v-if="form.errors.reason" class="text-xs text-red-600 mt-1">{{ form.errors.reason }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 rounded-lg bg-[#1B365D] text-white text-sm font-semibold disabled:opacity-50">
                        {{ form.processing ? (isRtl ? 'جارٍ الحفظ...' : 'Saving...') : (isRtl ? 'تطبيق التسوية' : 'Apply adjustment') }}
                    </button>
                    <button type="button" @click="showAdjust = false"
                            class="px-4 py-2 rounded-lg bg-white border border-gray-200 text-gray-600 text-sm">
                        {{ isRtl ? 'إلغاء' : 'Cancel' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-800">{{ isRtl ? 'سجل المعاملات' : 'Transaction history' }}</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr class="text-[10px] uppercase tracking-wider text-gray-500">
                        <th class="text-start px-4 py-3">{{ isRtl ? 'النوع' : 'Type' }}</th>
                        <th class="text-start px-4 py-3">{{ isRtl ? 'الوصف' : 'Description' }}</th>
                        <th class="text-start px-4 py-3 hidden md:table-cell">{{ isRtl ? 'بواسطة' : 'By' }}</th>
                        <th class="text-start px-4 py-3 hidden sm:table-cell">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                        <th class="text-end px-4 py-3">{{ isRtl ? 'النقاط' : 'Points' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="r in transactions.data" :key="r.id" class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <span :class="typeColor(r.type)" class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">{{ typeLabel(r.type) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ r.description || '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ r.admin_name || '—' }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs tabular-nums hidden sm:table-cell">{{ r.created_at }}</td>
                        <td class="px-4 py-3 text-end font-bold tabular-nums" :class="r.points >= 0 ? 'text-emerald-600' : 'text-amber-600'">
                            {{ r.points >= 0 ? '+' : '' }}{{ r.points.toLocaleString() }}
                        </td>
                    </tr>
                    <tr v-if="!transactions.data.length">
                        <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">
                            {{ isRtl ? 'لا توجد معاملات بعد' : 'No transactions yet' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="transactions.last_page > 1" class="p-4 border-t border-gray-100 flex items-center justify-center flex-wrap gap-2">
                <Link v-for="link in transactions.links" :key="link.label"
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
