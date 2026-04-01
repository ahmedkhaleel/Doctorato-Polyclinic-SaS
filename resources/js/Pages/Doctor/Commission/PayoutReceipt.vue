<script setup>
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const { formatCurrency, currencyCode } = useCurrency();

const props = defineProps({
    payout: Object,
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(isRtl.value ? 'ar-EG' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const paymentLabels = computed(() => ({
    cash: isRtl.value ? 'نقدي' : 'Cash',
    bank_transfer: isRtl.value ? 'تحويل بنكي' : 'Bank Transfer',
    check: isRtl.value ? 'شيك' : 'Check',
    mobile_wallet: isRtl.value ? 'محفظة إلكترونية' : 'Mobile Wallet',
}));

function printPage() {
    window.print();
}

onMounted(() => {
    setTimeout(() => window.print(), 600);
});
</script>

<template>
    <!-- Screen-only toolbar -->
    <div class="no-print toolbar">
        <div class="toolbar-left">
            <div class="toolbar-logo">
                <svg class="toolbar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <span class="toolbar-title">{{ isRtl ? `إيصال العمولة — ${payout.payout_number}` : `Commission Receipt — ${payout.payout_number}` }}</span>
        </div>
        <div class="toolbar-actions">
            <button @click="printPage" class="btn-print">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                {{ isRtl ? 'طباعة الإيصال' : 'Print Receipt' }}
            </button>
            <button @click="window.history.back()" class="btn-close">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                {{ isRtl ? 'إغلاق' : 'Close' }}
            </button>
        </div>
    </div>

    <div class="print-page" :dir="isRtl ? 'rtl' : 'ltr'">
        <!-- Clinic Header -->
        <div class="header">
            <div class="header-left">
                <h1 class="clinic-name">AURA Derma Clinic</h1>
                <p class="clinic-subtitle">{{ isRtl ? 'مركز الأمراض الجلدية والتجميل' : 'Dermatology & Cosmetic Center' }}</p>
                <p class="clinic-address">{{ isRtl ? 'القاهرة، مصر' : 'Cairo, Egypt' }}</p>
            </div>
            <div class="header-right">
                <h2 class="receipt-title">{{ isRtl ? 'إيصال دفع العمولة' : 'COMMISSION PAYMENT RECEIPT' }}</h2>
                <p class="receipt-number">{{ payout.payout_number }}</p>
                <p class="receipt-date">{{ formatDate(payout.paid_at) }}</p>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Doctor Info -->
        <div class="doctor-section">
            <h3 class="section-label">{{ isRtl ? 'الدفع إلى:' : 'Paid To:' }}</h3>
            <p class="doctor-name">{{ isRtl ? `د. ${payout.doctor?.name_ar || payout.doctor?.name_en}` : `Dr. ${payout.doctor?.name_en}` }}</p>
            <p class="doctor-detail">{{ isRtl ? 'الفترة:' : 'Period:' }} {{ formatDate(payout.period_start) }} — {{ formatDate(payout.period_end) }}</p>
        </div>

        <!-- Visits Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">{{ isRtl ? 'التاريخ' : 'Date' }}</th>
                    <th class="text-left">{{ isRtl ? 'المريض' : 'Patient' }}</th>
                    <th class="text-left">{{ isRtl ? 'الخدمة' : 'Service' }}</th>
                    <th class="text-right">{{ isRtl ? 'المبلغ' : 'Amount' }}</th>
                    <th class="text-right">{{ isRtl ? 'النسبة' : 'Rate' }}</th>
                    <th class="text-right">{{ isRtl ? 'العمولة' : 'Commission' }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(visit, index) in payout.visits" :key="visit.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(visit.visit_date) }}</td>
                    <td>{{ visit.patient?.full_name }}</td>
                    <td>{{ visit.service?.name_en || (visit.visit_type === 'consultation' ? (isRtl ? 'استشارة' : 'Consultation') : (isRtl ? 'جلسة' : 'Session')) }}</td>
                    <td class="text-right">{{ formatCurrency(visit.pivot?.visit_amount) }}</td>
                    <td class="text-right">{{ visit.pivot?.commission_rate }}%</td>
                    <td class="text-right">{{ formatCurrency(visit.pivot?.commission_amount) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <span class="totals-label">{{ isRtl ? 'إجمالي الإيرادات' : 'Total Revenue' }}</span>
                <span class="totals-value">{{ formatCurrency(payout.total_revenue) }}</span>
            </div>
            <div class="totals-row total-commission">
                <span class="totals-label">{{ isRtl ? 'إجمالي العمولة' : 'Total Commission' }}</span>
                <span class="totals-value">{{ formatCurrency(payout.total_commission) }}</span>
            </div>
            <div v-if="parseFloat(payout.deductions) > 0" class="totals-row">
                <span class="totals-label">
                    {{ isRtl ? 'الخصومات' : 'Deductions' }}
                    <span v-if="payout.deduction_notes" class="deduction-note">({{ payout.deduction_notes }})</span>
                </span>
                <span class="totals-value deduction">-{{ formatCurrency(payout.deductions) }}</span>
            </div>
            <div class="totals-row net-final">
                <span class="totals-label">{{ isRtl ? 'صافي المبلغ المدفوع' : 'Net Amount Paid' }}</span>
                <span class="totals-value">{{ formatCurrency(payout.net_amount) }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-row">
                <span class="payment-label">{{ isRtl ? 'طريقة الدفع:' : 'Payment Method:' }}</span>
                <span class="payment-value">{{ paymentLabels[payout.payment_method] || payout.payment_method }}</span>
            </div>
            <div v-if="payout.payment_reference" class="payment-row">
                <span class="payment-label">{{ isRtl ? 'المرجع:' : 'Reference:' }}</span>
                <span class="payment-value">{{ payout.payment_reference }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">{{ isRtl ? 'تاريخ الدفع:' : 'Payment Date:' }}</span>
                <span class="payment-value">{{ formatDate(payout.paid_at) }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">{{ isRtl ? 'تمت المعالجة بواسطة:' : 'Processed By:' }}</span>
                <span class="payment-value">{{ payout.paid_by_user?.name || '-' }}</span>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="status-badge status-paid">{{ isRtl ? 'مدفوع' : 'PAID' }}</div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">{{ isRtl ? 'ممثل العيادة' : 'Clinic Representative' }}</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">{{ isRtl ? 'توقيع الطبيب' : 'Doctor Signature' }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ isRtl ? 'شكراً لخدمتكم في عيادة أورا ديرما' : 'Thank you for your service at AURA Derma Clinic' }}</p>
            <p class="footer-small">{{ isRtl ? 'هذا إيصال صادر إلكترونياً.' : 'This is a computer-generated receipt.' }}</p>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        size: A4;
        margin: 15mm;
    }
}

/* Toolbar */
.toolbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #1f2937, #111827);
    color: white;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 50;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.toolbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.toolbar-logo {
    width: 36px;
    height: 36px;
    background: rgba(196, 162, 101, 0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toolbar-icon {
    width: 20px;
    height: 20px;
    color: #C4A265;
}

.toolbar-title {
    font-size: 14px;
    font-weight: 500;
}

.toolbar-actions {
    display: flex;
    gap: 10px;
}

.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    background: #C4A265;
    color: white;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-print:hover { background: #b3934f; }

.btn-close {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    background: #374151;
    color: #d1d5db;
    border: 1px solid rgba(255,255,255,0.1);
    cursor: pointer;
    transition: background 0.2s;
}

.btn-close:hover { background: #4b5563; }

.btn-icon {
    width: 16px;
    height: 16px;
}

/* Print Page */
.print-page {
    max-width: 800px;
    margin: 72px auto 0;
    padding: 40px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
    background: #fff;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.header-left { flex: 1; }

.clinic-name {
    font-size: 28px;
    font-weight: 700;
    color: #C4A265;
    margin: 0 0 4px 0;
    letter-spacing: 1px;
}

.clinic-subtitle {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.6;
}

.clinic-address {
    font-size: 12px;
    color: #888;
    margin: 0;
    line-height: 1.6;
}

.header-right { text-align: right; }

.receipt-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin: 0 0 4px 0;
    letter-spacing: 1px;
}

.receipt-number {
    font-size: 14px;
    font-family: monospace;
    color: #C4A265;
    margin: 0;
    font-weight: 600;
}

.receipt-date {
    font-size: 12px;
    color: #888;
    margin: 4px 0 0 0;
}

.divider {
    height: 2px;
    background: linear-gradient(to right, #C4A265, transparent);
    margin: 20px 0;
}

.doctor-section { margin-bottom: 30px; }

.section-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #888;
    margin: 0 0 6px 0;
}

.doctor-name {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 4px 0;
}

.doctor-detail {
    font-size: 13px;
    color: #666;
    margin: 0;
    line-height: 1.6;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
    font-size: 12px;
}

.items-table thead tr { background-color: #f8f5f0; }

.items-table th {
    padding: 8px 10px;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #666;
    border-bottom: 2px solid #C4A265;
}

.items-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
    color: #444;
}

.items-table tbody tr:last-child td { border-bottom: 2px solid #eee; }

.text-left { text-align: left; }
.text-right { text-align: right; }

.totals-section {
    width: 320px;
    margin-left: auto;
    margin-bottom: 30px;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 13px;
}

.totals-label { color: #666; }

.totals-value {
    font-weight: 500;
    color: #333;
}

.totals-value.deduction { color: #dc2626; }

.total-commission {
    border-top: 1px solid #eee;
    padding-top: 8px;
}

.net-final {
    border-top: 2px solid #C4A265;
    border-bottom: 1px solid #eee;
    padding: 10px 0;
    margin: 4px 0;
}

.net-final .totals-label,
.net-final .totals-value {
    font-size: 16px;
    font-weight: 700;
    color: #C4A265;
}

.deduction-note {
    font-size: 10px;
    color: #999;
}

.payment-info {
    background: #f8f5f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 13px;
}

.payment-label { color: #666; }
.payment-value { font-weight: 500; color: #333; }

.status-badge {
    display: inline-block;
    padding: 6px 20px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    border-radius: 4px;
    margin-bottom: 30px;
}

.status-paid {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.signatures {
    display: flex;
    justify-content: space-between;
    margin: 50px 0 30px;
}

.signature-box {
    width: 200px;
    text-align: center;
}

.signature-line {
    border-bottom: 1px solid #ccc;
    margin-bottom: 8px;
    height: 40px;
}

.signature-label {
    font-size: 11px;
    color: #888;
    margin: 0;
}

.footer {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.footer p {
    font-size: 13px;
    color: #888;
    margin: 0;
}

.footer-small {
    font-size: 11px !important;
    color: #bbb !important;
    margin-top: 4px !important;
}

@media print {
    .no-print { display: none !important; }
    .print-page { padding: 0; margin-top: 0 !important; }
    .status-badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .items-table thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .payment-info { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
