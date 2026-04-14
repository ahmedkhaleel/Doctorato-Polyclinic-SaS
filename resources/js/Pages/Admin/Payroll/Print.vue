<script setup>
import { onMounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    slip: Object,
});

const months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const { formatCurrency, currencyCode } = useCurrency();

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
}

const earningKeys = [
    { key: 'basic_salary', tKey: 'a_basic_salary' },
    { key: 'housing_allowance', tKey: 'a_housing_allowance' },
    { key: 'transport_allowance', tKey: 'a_transport_allowance' },
    { key: 'other_allowances', tKey: 'a_other_allowances' },
    { key: 'overtime_amount', tKey: 'a_overtime' },
    { key: 'bonus', tKey: 'a_bonus' },
    { key: 'commission_amount', tKey: 'a_commission' },
];

const deductionKeys = [
    { key: 'insurance_deduction', tKey: 'a_insurance' },
    { key: 'tax_deduction', tKey: 'a_tax' },
    { key: 'absence_deduction', tKey: 'a_absence' },
    { key: 'advance_deduction', tKey: 'a_advance_deduction' },
    { key: 'penalty_deduction', tKey: 'a_penalty_deduction' },
    { key: 'other_deductions', tKey: 'a_other_deductions' },
];

const generatedDate = new Date().toLocaleDateString('en-GB', { dateStyle: 'long' });

function printPage() {
    window.print();
}

onMounted(() => {
    setTimeout(() => window.print(), 500);
});
</script>

<template>
    <!-- Screen-only toolbar -->
    <div class="no-print toolbar">
        <span class="toolbar-title">{{ $t('a_salary_slip_preview') }} - {{ slip.slip_number }}</span>
        <div class="toolbar-actions">
            <button @click="printPage" class="toolbar-btn toolbar-btn-gold">
                <svg class="toolbar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ $t('a_print') }}
            </button>
            <a :href="`/admin/payroll/${slip.id}`" class="toolbar-btn toolbar-btn-gray">
                {{ $t('a_back_to_details') }}
            </a>
        </div>
    </div>

    <div class="print-page">
        <!-- Company Header -->
        <div class="header">
            <div class="header-left">
                <h1 class="clinic-name">Doctorato Polyclinic</h1>
                <p class="clinic-subtitle">{{ $t('a_salary_slip') }}</p>
            </div>
            <div class="header-right">
                <p class="slip-period">{{ months[slip.month] || slip.month }} {{ slip.year }}</p>
                <p class="slip-number">{{ slip.slip_number }}</p>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Employee Section -->
        <div class="employee-section">
            <div class="employee-grid">
                <div>
                    <span class="info-label">{{ $t('a_employee_name') }}</span>
                    <span class="info-value">{{ slip.employee?.user?.name }}</span>
                </div>
                <div>
                    <span class="info-label">{{ $t('a_employee_number') }}</span>
                    <span class="info-value info-mono">{{ slip.employee?.employee_number }}</span>
                </div>
                <div>
                    <span class="info-label">{{ $t('a_department') }}</span>
                    <span class="info-value">{{ slip.employee?.department?.name_en }}</span>
                </div>
                <div>
                    <span class="info-label">{{ $t('a_period') }}</span>
                    <span class="info-value">{{ months[slip.month] || slip.month }} {{ slip.year }}</span>
                </div>
            </div>
        </div>

        <!-- Earnings & Deductions Tables -->
        <div class="tables-grid">
            <!-- Earnings Table -->
            <div class="table-section">
                <h3 class="table-heading">{{ $t('a_earnings') }}</h3>
                <table class="slip-table">
                    <thead>
                        <tr>
                            <th class="text-left">{{ $t('a_description') }}</th>
                            <th class="text-right">{{ $t('a_amount') }} ({{ currencyCode }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in earningKeys" :key="item.key">
                            <td>{{ $t(item.tKey) }}</td>
                            <td class="text-right">{{ formatCurrency(slip[item.key]) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td>{{ $t('a_total_earnings') }}</td>
                            <td class="text-right">{{ formatCurrency(slip.total_earnings) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions Table -->
            <div class="table-section">
                <h3 class="table-heading">{{ $t('a_deductions') }}</h3>
                <table class="slip-table">
                    <thead>
                        <tr>
                            <th class="text-left">{{ $t('a_description') }}</th>
                            <th class="text-right">{{ $t('a_amount') }} ({{ currencyCode }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in deductionKeys" :key="item.key">
                            <td>{{ $t(item.tKey) }}</td>
                            <td class="text-right">{{ formatCurrency(slip[item.key]) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row total-row-deduction">
                            <td>{{ $t('a_total_deductions') }}</td>
                            <td class="text-right">{{ formatCurrency(slip.total_deductions) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Net Salary -->
        <div class="net-salary-section">
            <div class="net-salary-row">
                <span class="net-salary-label">{{ $t('a_net_salary') }}</span>
                <span class="net-salary-value">{{ formatCurrency(slip.net_salary) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $t('a_generated_on') }} {{ generatedDate }}</p>
            <p class="footer-small">{{ $t('a_system_generated_document') }}</p>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        size: A4;
        margin: 15mm;
    }

    body {
        margin: 0;
    }

    .no-print {
        display: none !important;
    }

    .print-page {
        padding: 0;
        margin-top: 0;
    }

    .employee-section,
    .net-salary-section,
    .table-heading,
    .slip-table thead tr {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Toolbar (screen only) */
.toolbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #1f2937;
    color: white;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 50;
}

.toolbar-title {
    font-size: 14px;
}

.toolbar-actions {
    display: flex;
    gap: 12px;
}

.toolbar-btn {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.toolbar-btn-gold {
    background-color: #C4A265;
    color: white;
}

.toolbar-btn-gray {
    background-color: #4b5563;
    color: white;
}

.toolbar-icon {
    width: 16px;
    height: 16px;
    margin-right: 6px;
}

/* Print page */
.print-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: #333;
    background: #fff;
    margin-top: 56px;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.header-left { flex: 1; }

.clinic-name {
    font-size: 24px;
    font-weight: 700;
    color: #C4A265;
    margin: 0 0 4px 0;
    letter-spacing: 1px;
}

.clinic-subtitle {
    font-size: 16px;
    color: #666;
    margin: 0;
    font-weight: 500;
}

.header-right { text-align: right; }

.slip-period {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 4px 0;
}

.slip-number {
    font-size: 14px;
    font-family: monospace;
    color: #C4A265;
    margin: 0;
    font-weight: 600;
}

.divider {
    height: 2px;
    background: linear-gradient(to right, #C4A265, transparent);
    margin: 20px 0;
}

/* Employee Section */
.employee-section {
    margin-bottom: 28px;
    background: #f8f5f0;
    padding: 16px 20px;
    border-radius: 6px;
}

.employee-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.info-label {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #999;
    font-weight: 600;
    margin-bottom: 2px;
}

.info-value {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.info-mono {
    font-family: monospace;
    color: #C4A265;
}

/* Tables */
.tables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.table-heading {
    font-size: 13px;
    font-weight: 700;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 10px 0;
    padding-bottom: 6px;
    border-bottom: 2px solid #C4A265;
}

.slip-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.slip-table th {
    padding: 6px 8px;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #888;
    border-bottom: 1px solid #ddd;
}

.slip-table td {
    padding: 7px 8px;
    border-bottom: 1px solid #f0f0f0;
    color: #444;
}

.slip-table tbody tr:last-child td {
    border-bottom: 1px solid #ddd;
}

.total-row td {
    font-weight: 700;
    color: #C4A265;
    border-top: 2px solid #C4A265;
    border-bottom: none;
    padding-top: 10px;
}

.total-row-deduction td {
    color: #dc2626;
    border-top-color: #fca5a5;
}

.text-left { text-align: left; }
.text-right { text-align: right; }

/* Net Salary */
.net-salary-section {
    margin-bottom: 24px;
    border: 2px solid #C4A265;
    border-radius: 6px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #fdfbf7 0%, #f9f3e8 100%);
}

.net-salary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.net-salary-label {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #333;
}

.net-salary-value {
    font-size: 22px;
    font-weight: 700;
    color: #C4A265;
}

/* Footer */
.footer {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.footer p {
    font-size: 12px;
    color: #999;
    margin: 0;
}

.footer-small {
    font-size: 10px !important;
    color: #ccc !important;
    margin-top: 4px !important;
}
</style>
