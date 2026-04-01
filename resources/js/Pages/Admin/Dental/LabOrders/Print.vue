<script setup>
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    groupedOrders: Object,
    totalCount: Number,
    clinicName: String,
    clinicPhone: String,
    printDate: String,
});

const statusLabels = {
    ordered: { ar: 'تم الطلب', en: 'Ordered' },
    in_production: { ar: 'قيد التصنيع', en: 'In Production' },
    ready: { ar: 'جاهز', en: 'Ready' },
    delivered: { ar: 'تم التسليم', en: 'Delivered' },
    adjustment: { ar: 'تعديل', en: 'Adjustment' },
    completed: { ar: 'مكتمل', en: 'Completed' },
};

const itemLabels = {
    crown: { ar: 'تاج', en: 'Crown' },
    bridge: { ar: 'جسر', en: 'Bridge' },
    denture: { ar: 'طقم أسنان', en: 'Denture' },
    retainer: { ar: 'مثبت', en: 'Retainer' },
    aligner: { ar: 'تقويم شفاف', en: 'Aligner' },
    veneer: { ar: 'قشرة', en: 'Veneer' },
    implant_abutment: { ar: 'دعامة زراعة', en: 'Implant Abutment' },
    night_guard: { ar: 'واقي ليلي', en: 'Night Guard' },
    inlay_onlay: { ar: 'حشوة', en: 'Inlay/Onlay' },
};

const materialLabels = {
    zirconia: { ar: 'زركونيا', en: 'Zirconia' },
    porcelain: { ar: 'بورسلان', en: 'Porcelain' },
    emax: { ar: 'إيماكس', en: 'E-Max' },
    metal: { ar: 'معدن', en: 'Metal' },
    composite: { ar: 'كمبوزيت', en: 'Composite' },
    acrylic: { ar: 'أكريليك', en: 'Acrylic' },
    pfm: { ar: 'بورسلان على معدن', en: 'PFM' },
};

function label(map, key) {
    const l = map[key];
    return l ? (isRtl.value ? l.ar : l.en) : (key || '-');
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

onMounted(() => {
    setTimeout(() => window.print(), 500);
});
</script>

<template>
    <div :dir="isRtl ? 'rtl' : 'ltr'" class="print-page">
        <!-- Header -->
        <div class="print-header">
            <div class="header-top">
                <div>
                    <h1 class="clinic-name">{{ clinicName }}</h1>
                    <p class="clinic-phone" v-if="clinicPhone">{{ clinicPhone }}</p>
                </div>
                <div class="header-meta">
                    <p class="print-title">{{ isRtl ? 'قائمة طلبات المعمل' : 'Lab Orders List' }}</p>
                    <p class="print-date">{{ printDate }}</p>
                    <p class="print-count">{{ isRtl ? `${totalCount} طلب` : `${totalCount} orders` }}</p>
                </div>
            </div>
        </div>

        <!-- Per-lab sections -->
        <div v-for="(orders, labName) in groupedOrders" :key="labName" class="lab-section">
            <div class="lab-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                <span>{{ isRtl ? 'المعمل' : 'Lab' }}: {{ labName }}</span>
                <span class="lab-count">({{ orders.length }})</span>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ isRtl ? 'المريض' : 'Patient' }}</th>
                        <th>{{ isRtl ? 'رقم الملف' : 'File #' }}</th>
                        <th>{{ isRtl ? 'النوع' : 'Item' }}</th>
                        <th>{{ isRtl ? 'السن' : 'Tooth' }}</th>
                        <th>{{ isRtl ? 'اللون' : 'Shade' }}</th>
                        <th>{{ isRtl ? 'المادة' : 'Material' }}</th>
                        <th>{{ isRtl ? 'الحالة' : 'Status' }}</th>
                        <th>{{ isRtl ? 'تاريخ الطلب' : 'Order Date' }}</th>
                        <th>{{ isRtl ? 'التاريخ المتوقع' : 'Expected' }}</th>
                        <th>{{ isRtl ? 'الطبيب' : 'Doctor' }}</th>
                        <th>{{ isRtl ? 'ملاحظات' : 'Notes' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(order, idx) in orders" :key="order.id">
                        <td class="row-num">{{ idx + 1 }}</td>
                        <td class="patient-name">{{ order.patient?.full_name || '-' }}</td>
                        <td class="mono">{{ order.patient?.file_number || '-' }}</td>
                        <td>{{ label(itemLabels, order.item_type) }}</td>
                        <td class="mono center">{{ order.tooth_number || '-' }}</td>
                        <td class="center">{{ order.shade || '-' }}</td>
                        <td>{{ label(materialLabels, order.material) }}</td>
                        <td><span class="status-badge" :class="'status-' + order.status">{{ label(statusLabels, order.status) }}</span></td>
                        <td class="date">{{ formatDate(order.order_date) }}</td>
                        <td class="date">{{ formatDate(order.expected_date) }}</td>
                        <td>{{ isRtl ? order.doctor?.name_ar : order.doctor?.name_en || '-' }}</td>
                        <td class="notes">{{ order.special_instructions || order.notes || '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="print-footer">
            <p>{{ clinicName }} — {{ isRtl ? 'تمت الطباعة' : 'Printed' }}: {{ printDate }}</p>
        </div>

        <!-- No-print: back button -->
        <div class="no-print-actions">
            <button onclick="window.print()" class="action-btn print-btn">
                {{ isRtl ? 'طباعة' : 'Print' }}
            </button>
            <button onclick="window.close()" class="action-btn close-btn">
                {{ isRtl ? 'إغلاق' : 'Close' }}
            </button>
        </div>
    </div>
</template>

<style>
/* Reset for print */
.print-page {
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    color: #1f2937;
    font-size: 12px;
}

.print-header {
    border-bottom: 3px solid #06b6d4;
    padding-bottom: 12px;
    margin-bottom: 20px;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.clinic-name {
    font-size: 20px;
    font-weight: 700;
    color: #0e7490;
    margin: 0;
}

.clinic-phone {
    font-size: 13px;
    color: #6b7280;
    margin: 2px 0 0 0;
}

.header-meta {
    text-align: end;
}

.print-title {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
    margin: 0;
}

.print-date {
    font-size: 12px;
    color: #6b7280;
    margin: 2px 0 0 0;
}

.print-count {
    font-size: 12px;
    font-weight: 600;
    color: #0e7490;
    margin: 2px 0 0 0;
}

.lab-section {
    margin-bottom: 24px;
    break-inside: avoid;
}

.lab-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    font-weight: 700;
    color: #0e7490;
    background: #ecfeff;
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    border: 1px solid #cffafe;
}

.lab-count {
    font-weight: 400;
    color: #6b7280;
    font-size: 12px;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.orders-table th {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    padding: 6px 8px;
    text-align: start;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    color: #6b7280;
    letter-spacing: 0.3px;
    white-space: nowrap;
}

.orders-table td {
    border: 1px solid #e5e7eb;
    padding: 5px 8px;
    vertical-align: top;
}

.orders-table tbody tr:nth-child(even) {
    background: #f9fafb;
}

.row-num {
    text-align: center;
    color: #9ca3af;
    font-weight: 600;
    width: 30px;
}

.patient-name {
    font-weight: 600;
    white-space: nowrap;
}

.mono {
    font-family: 'Courier New', monospace;
    font-size: 11px;
}

.center {
    text-align: center;
}

.date {
    white-space: nowrap;
    font-size: 10px;
}

.notes {
    max-width: 120px;
    font-size: 10px;
    color: #6b7280;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.status-ordered { background: #f3f4f6; color: #4b5563; }
.status-in_production { background: #dbeafe; color: #1d4ed8; }
.status-ready { background: #d1fae5; color: #047857; }
.status-delivered { background: #ccfbf1; color: #0f766e; }
.status-adjustment { background: #fed7aa; color: #c2410c; }
.status-completed { background: #dcfce7; color: #15803d; }

.print-footer {
    margin-top: 30px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    font-size: 10px;
    color: #9ca3af;
}

.no-print-actions {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 100;
}

.action-btn {
    padding: 10px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.print-btn {
    background: #06b6d4;
    color: white;
}

.print-btn:hover {
    background: #0891b2;
}

.close-btn {
    background: white;
    color: #4b5563;
    border: 1px solid #e5e7eb;
}

.close-btn:hover {
    background: #f3f4f6;
}

@media print {
    .no-print-actions { display: none !important; }
    .print-page { padding: 0; max-width: none; }
    .lab-section { break-inside: avoid; }

    @page {
        size: landscape;
        margin: 10mm;
    }
}
</style>
