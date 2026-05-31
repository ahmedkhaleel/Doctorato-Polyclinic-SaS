<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/Composables/useCurrency.js';
import { useLocale } from '@/Composables/useLocale.js';

const { formatCurrency } = useCurrency();
const { t, locale } = useLocale();

const props = defineProps({
    report: Object,
    branchesEnabled: Boolean,
});

const from = ref(props.report?.from || '');
const to = ref(props.report?.to || '');

const isAr = computed(() => (locale?.value || 'ar') === 'ar');
const branchName = (row) => (isAr.value ? row.name_ar : row.name_en) || row.code;

const apply = () => {
    router.get('/admin/reports/branch-comparison',
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, replace: true });
};

// Share-of-total bar width for the collected column (visual comparison).
const maxCollected = computed(() =>
    Math.max(1, ...(props.report?.rows || []).map(r => Number(r.collected) || 0)));
const barWidth = (v) => `${Math.round(((Number(v) || 0) / maxCollected.value) * 100)}%`;
</script>

<template>
    <AdminLayout>
        <div class="bc-wrap" :dir="isAr ? 'rtl' : 'ltr'">
            <header class="bc-head">
                <div>
                    <h1>{{ isAr ? 'مقارنة الفروع' : 'Branch Comparison' }}</h1>
                    <p class="bc-sub">
                        {{ isAr ? 'مؤشرات الأداء لكل فرع' : 'Performance KPIs per branch' }}
                        <span class="bc-range">· {{ report.from }} → {{ report.to }}</span>
                    </p>
                </div>
                <form class="bc-filters" @submit.prevent="apply">
                    <input type="date" v-model="from" class="bc-input" />
                    <span class="bc-arrow">→</span>
                    <input type="date" v-model="to" class="bc-input" />
                    <button type="submit" class="bc-btn">{{ isAr ? 'تطبيق' : 'Apply' }}</button>
                </form>
            </header>

            <div v-if="!branchesEnabled" class="bc-note">
                {{ isAr
                    ? 'وضع الفروع غير مُفعّل بعد — تظهر البيانات الحالية تحت الفرع الرئيسي.'
                    : 'Multi-branch is not enabled yet — current data shows under the main branch.' }}
            </div>

            <div class="bc-cards">
                <div class="bc-card"><span>{{ isAr ? 'الحجوزات' : 'Bookings' }}</span><strong>{{ report.totals.bookings }}</strong></div>
                <div class="bc-card"><span>{{ isAr ? 'الزيارات' : 'Visits' }}</span><strong>{{ report.totals.visits }}</strong></div>
                <div class="bc-card"><span>{{ isAr ? 'الفواتير' : 'Invoiced' }}</span><strong>{{ formatCurrency(report.totals.invoiced) }}</strong></div>
                <div class="bc-card bc-accent"><span>{{ isAr ? 'المُحصّل' : 'Collected' }}</span><strong>{{ formatCurrency(report.totals.collected) }}</strong></div>
                <div class="bc-card"><span>{{ isAr ? 'المتبقي' : 'Outstanding' }}</span><strong>{{ formatCurrency(report.totals.outstanding) }}</strong></div>
            </div>

            <div class="bc-table-wrap">
                <table class="bc-table">
                    <thead>
                        <tr>
                            <th>{{ isAr ? 'الفرع' : 'Branch' }}</th>
                            <th>{{ isAr ? 'الحجوزات' : 'Bookings' }}</th>
                            <th>{{ isAr ? 'الزيارات' : 'Visits' }}</th>
                            <th>{{ isAr ? 'الفواتير' : 'Invoiced' }}</th>
                            <th>{{ isAr ? 'المُحصّل' : 'Collected' }}</th>
                            <th>{{ isAr ? 'المتبقي' : 'Outstanding' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.rows" :key="row.branch_id" :style="{ '--i': i }" class="bc-row">
                            <td class="bc-branch">
                                <span class="bc-code">{{ row.code }}</span>
                                {{ branchName(row) }}
                            </td>
                            <td>{{ row.bookings }}</td>
                            <td>{{ row.visits }}</td>
                            <td>{{ formatCurrency(row.invoiced) }}</td>
                            <td class="bc-collected">
                                <span class="bc-bar" :style="{ width: barWidth(row.collected) }"></span>
                                <span class="bc-val">{{ formatCurrency(row.collected) }}</span>
                            </td>
                            <td>{{ formatCurrency(row.outstanding) }}</td>
                        </tr>
                        <tr v-if="!report.rows.length">
                            <td colspan="6" class="bc-empty">{{ isAr ? 'لا توجد بيانات' : 'No data' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.bc-wrap { padding: 1.5rem; color: #1B365D; }
.bc-head { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; }
.bc-head h1 { font-size: 1.5rem; font-weight: 800; color: #1B365D; }
.bc-sub { color: #64748b; font-size: .9rem; margin-top: .15rem; }
.bc-range { color: #C4A265; font-weight: 600; }
.bc-filters { display: flex; align-items: center; gap: .5rem; }
.bc-input { border: 1px solid #d8dee9; border-radius: .5rem; padding: .4rem .6rem; font-size: .85rem; }
.bc-arrow { color: #94a3b8; }
.bc-btn { background: #1B365D; color: #fff; border: 0; border-radius: .5rem; padding: .45rem 1rem; font-weight: 700; cursor: pointer; transition: background .2s ease; }
.bc-btn:hover { background: #24477a; }
.bc-note { background: #fff8ec; border: 1px solid #C4A265; color: #8a6d2f; border-radius: .6rem; padding: .7rem 1rem; font-size: .85rem; margin-bottom: 1rem; }

.bc-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .8rem; margin-bottom: 1.25rem; }
.bc-card { background: #fff; border: 1px solid #eef1f6; border-radius: .8rem; padding: .9rem 1rem; box-shadow: 0 1px 3px rgba(27,54,93,.05); display: flex; flex-direction: column; gap: .25rem; }
.bc-card span { font-size: .78rem; color: #64748b; }
.bc-card strong { font-size: 1.25rem; color: #1B365D; }
.bc-card.bc-accent { border-color: #C4A265; background: linear-gradient(180deg,#fffdf8,#fff); }
.bc-card.bc-accent strong { color: #b1863a; }

.bc-table-wrap { background: #fff; border: 1px solid #eef1f6; border-radius: .9rem; overflow: hidden; }
.bc-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
.bc-table thead th { background: #1B365D; color: #fff; text-align: start; padding: .7rem .9rem; font-weight: 600; font-size: .82rem; }
.bc-table tbody td { padding: .7rem .9rem; border-top: 1px solid #f1f4f9; }
.bc-row { animation: bcfade .35s ease both; animation-delay: calc(var(--i) * 45ms); }
.bc-branch { font-weight: 700; }
.bc-code { display: inline-block; background: #eef1f6; color: #1B365D; border-radius: .35rem; padding: .05rem .4rem; font-size: .7rem; margin-inline-end: .4rem; }
.bc-collected { position: relative; min-width: 140px; }
.bc-bar { position: absolute; inset-block: 6px; inset-inline-start: 4px; background: rgba(196,162,101,.22); border-radius: .3rem; z-index: 0; transition: width .4s ease; }
.bc-val { position: relative; z-index: 1; font-weight: 600; }
.bc-empty { text-align: center; color: #94a3b8; padding: 1.5rem; }

@keyframes bcfade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) {
    .bc-row { animation: none; }
    .bc-bar, .bc-btn { transition: none; }
}
</style>
