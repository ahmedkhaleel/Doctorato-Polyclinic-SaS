<script setup>
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const pg = usePage();
const locale = computed(() => pg.props.locale || 'ar');
const isRtl = computed(() => (pg.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    payout: Object,
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB');
}

const { formatCurrency } = useCurrency();

const paymentLabels = {
    cash: 'Cash',
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    mobile_wallet: 'Mobile Wallet',
};

function printPage() {
    window.print();
}

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <!-- Screen-only toolbar -->
    <div class="no-print" style="position: fixed; top: 0; left: 0; right: 0; background: #1f2937; color: white; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; z-index: 50;">
        <span style="font-size: 14px;">{{ $t('a_payout_receipt') }} - {{ payout.payout_number }}</span>
        <div style="display: flex; gap: 12px;">
            <button
                @click="printPage"
                style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; background-color: #C4A265; color: white; border: none; cursor: pointer;"
            >
                {{ $t('a_print') }}
            </button>
            <button
                @click="window.history.back()"
                style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; background-color: #4b5563; color: white; border: none; cursor: pointer;"
            >
                {{ $t('a_close') }}
            </button>
        </div>
    </div>

    <div class="print-page" style="margin-top: 56px;">
        <!-- Clinic Header -->
        <div class="header">
            <div class="header-left">
                <h1 class="clinic-name">AURA Derma Clinic</h1>
                <p class="clinic-address">Dermatology & Cosmetic Center</p>
                <p class="clinic-address">Cairo, Egypt</p>
            </div>
            <div class="header-right">
                <h2 class="receipt-title">{{ $t('a_commission_payment_receipt') }}</h2>
                <p class="receipt-number">{{ payout.payout_number }}</p>
                <p class="receipt-date">{{ formatDate(payout.paid_at) }}</p>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Doctor Info -->
        <div class="doctor-section">
            <h3 class="section-label">{{ $t('a_paid_to') }}:</h3>
            <p class="doctor-name">{{ $t('a_dr') }} {{ payout.doctor?.name_en }}</p>
            <p class="doctor-detail">{{ $t('a_period') }}: {{ formatDate(payout.period_start) }} — {{ formatDate(payout.period_end) }}</p>
        </div>

        <!-- Visits Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">#</th>
                    <th class="text-left">{{ $t('a_date') }}</th>
                    <th class="text-left">{{ $t('a_patient') }}</th>
                    <th class="text-left">{{ $t('a_service') }}</th>
                    <th class="text-right">{{ $t('a_amount') }}</th>
                    <th class="text-right">{{ $t('a_rate') }}</th>
                    <th class="text-right">{{ $t('a_commission') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(visit, index) in payout.visits" :key="visit.id">
                    <td>{{ index + 1 }}</td>
                    <td>{{ formatDate(visit.visit_date) }}</td>
                    <td>{{ visit.patient?.full_name }}</td>
                    <td>{{ visit.service?.name_en || (visit.visit_type === 'consultation' ? $t('a_consultation') : $t('a_session')) }}</td>
                    <td class="text-right">{{ formatCurrency(visit.pivot?.visit_amount) }}</td>
                    <td class="text-right">{{ visit.pivot?.commission_rate }}%</td>
                    <td class="text-right">{{ formatCurrency(visit.pivot?.commission_amount) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <span class="totals-label">{{ $t('a_total_revenue') }}</span>
                <span class="totals-value">{{ formatCurrency(payout.total_revenue) }}</span>
            </div>
            <div class="totals-row total-commission">
                <span class="totals-label">{{ $t('a_total_commission') }}</span>
                <span class="totals-value">{{ formatCurrency(payout.total_commission) }}</span>
            </div>
            <div v-if="parseFloat(payout.deductions) > 0" class="totals-row">
                <span class="totals-label">
                    {{ $t('a_deductions') }}
                    <span v-if="payout.deduction_notes" class="deduction-note">({{ payout.deduction_notes }})</span>
                </span>
                <span class="totals-value deduction">-{{ formatCurrency(payout.deductions) }}</span>
            </div>
            <div class="totals-row net-final">
                <span class="totals-label">{{ $t('a_net_amount_paid') }}</span>
                <span class="totals-value">{{ formatCurrency(payout.net_amount) }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-row">
                <span class="payment-label">{{ $t('a_payment_method') }}:</span>
                <span class="payment-value">{{ paymentLabels[payout.payment_method] || payout.payment_method }}</span>
            </div>
            <div v-if="payout.payment_reference" class="payment-row">
                <span class="payment-label">{{ $t('a_reference') }}:</span>
                <span class="payment-value">{{ payout.payment_reference }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">{{ $t('a_payment_date') }}:</span>
                <span class="payment-value">{{ formatDate(payout.paid_at) }}</span>
            </div>
            <div class="payment-row">
                <span class="payment-label">{{ $t('a_processed_by') }}:</span>
                <span class="payment-value">{{ payout.paid_by_user?.name || '-' }}</span>
            </div>
        </div>

        <!-- Status Badge -->
        <div class="status-badge status-paid">{{ $t('a_paid') }}</div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">{{ $t('a_clinic_representative') }}</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p class="signature-label">{{ $t('a_doctor_signature') }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $t('a_thank_you_service') }}</p>
            <p class="footer-small">{{ $t('a_computer_generated_receipt') }}</p>
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

.print-page {
    max-width: 800px;
    margin: 0 auto;
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

.clinic-address {
    font-size: 12px;
    color: #888;
    margin: 0;
    line-height: 1.6;
}

.header-right { text-align: right; }

.receipt-title {
    font-size: 18px;
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
.text-center { text-align: center; }
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
