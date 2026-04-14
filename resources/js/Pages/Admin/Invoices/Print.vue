<script setup>
import { onMounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useCurrency } from '@/Composables/useCurrency.js';

const { formatCurrency, currencyCode } = useCurrency();

const page = usePage();
const locale = computed(() => page.props.locale || 'ar');
const isRtl = computed(() => (page.props.dir || 'rtl') === 'rtl');

const props = defineProps({
    invoice: Object,
});

function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

const statusLabels = {
    paid: isRtl.value ? 'مدفوع' : 'PAID',
    partial: isRtl.value ? 'مدفوع جزئياً' : 'PARTIALLY PAID',
    unpaid: isRtl.value ? 'غير مدفوع' : 'UNPAID',
    cancelled: isRtl.value ? 'ملغي' : 'CANCELLED',
};

const balance = (props.invoice?.total || 0) - (props.invoice?.paid_amount || 0);

function printPage() {
    window.print();
}

function closePage() {
    router.visit(`/admin/invoices/${props.invoice.id}`);
}

onMounted(() => {
    setTimeout(() => window.print(), 600);
});
</script>

<template>
    <!-- Screen-only toolbar -->
    <div class="no-print toolbar">
        <div class="toolbar-left">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>{{ invoice.invoice_number }}</span>
        </div>
        <div class="toolbar-actions">
            <button @click="printPage" class="toolbar-btn btn-print">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ $t('a_print') }}
            </button>
            <a :href="`/admin/invoices/${invoice.id}/pdf`" class="toolbar-btn btn-pdf">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                PDF
            </a>
            <button @click="closePage" class="toolbar-btn btn-close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ $t('a_close') }}
            </button>
        </div>
    </div>

    <!-- Invoice Page -->
    <div class="print-page">
        <!-- Gold accent top border -->
        <div class="accent-bar"></div>

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="/images/logo/logo-md.png" alt="Doctorato Polyclinic" class="clinic-logo" />
                <div>
                    <h1 class="clinic-name">Doctorato Polyclinic</h1>
                    <p class="clinic-tagline">Doctorato Polyclinic</p>
                    <p class="clinic-location">Cairo, Egypt</p>
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-label">{{ $t('a_invoice') }}</div>
                <div class="invoice-meta">
                    <div class="meta-row">
                        <span class="meta-key">{{ $t('a_number') }}</span>
                        <span class="meta-val mono">{{ invoice.invoice_number }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-key">{{ $t('a_date') }}</span>
                        <span class="meta-val">{{ formatDate(invoice.invoice_date || invoice.created_at) }}</span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-key">{{ $t('a_status') }}</span>
                        <span class="status-pill" :class="`pill-${invoice.status}`">
                            {{ statusLabels[invoice.status] || invoice.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="gold-divider">
            <div class="divider-line"></div>
            <div class="divider-diamond"></div>
            <div class="divider-line"></div>
        </div>

        <!-- Bill To / Invoice Details -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-header">
                    <svg width="14" height="14" fill="none" stroke="#C4A265" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>{{ $t('a_bill_to') }}</span>
                </div>
                <p class="info-name">{{ invoice.patient?.full_name }}</p>
                <p v-if="invoice.patient?.phone" class="info-detail">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ invoice.patient.phone }}
                </p>
                <p v-if="invoice.patient?.file_number" class="info-detail">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    File #{{ invoice.patient.file_number }}
                </p>
            </div>
            <div class="info-card">
                <div class="info-card-header">
                    <svg width="14" height="14" fill="none" stroke="#C4A265" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>{{ $t('a_from') }}</span>
                </div>
                <p class="info-name">Doctorato Polyclinic</p>
                <p class="info-detail">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Cairo, Egypt
                </p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-desc">{{ $t('a_description') }}</th>
                    <th class="col-qty">{{ $t('a_qty') }}</th>
                    <th class="col-price">{{ $t('a_unit_price') }}</th>
                    <th class="col-disc">{{ $t('a_discount') }}</th>
                    <th class="col-total">{{ $t('a_total') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in invoice.items" :key="item.id">
                    <td class="cell-num">{{ index + 1 }}</td>
                    <td class="cell-desc">
                        <span class="desc-en">{{ item.description_en }}</span>
                        <span v-if="item.description_ar" class="desc-ar">{{ item.description_ar }}</span>
                    </td>
                    <td class="cell-qty">{{ item.quantity }}</td>
                    <td class="cell-price">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="cell-disc">{{ item.discount > 0 ? formatCurrency(item.discount) : '-' }}</td>
                    <td class="cell-total">{{ formatCurrency(item.total) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-wrapper">
            <div class="summary-note">
                <div class="note-icon">
                    <svg width="16" height="16" fill="none" stroke="#C4A265" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p>{{ $t('a_payment_due_notice') }}</p>
            </div>

            <div class="summary-box">
                <div class="summary-row">
                    <span>{{ $t('a_subtotal') }}</span>
                    <span>{{ formatCurrency(invoice.subtotal) }}</span>
                </div>
                <div v-if="invoice.discount_amount > 0" class="summary-row row-discount">
                    <span>{{ $t('a_discount') }} <span v-if="invoice.discount_code" class="code-tag">{{ invoice.discount_code }}</span></span>
                    <span class="text-red">-{{ formatCurrency(invoice.discount_amount) }}</span>
                </div>
                <div class="summary-row row-total">
                    <span>{{ $t('a_total') }}</span>
                    <span>{{ formatCurrency(invoice.total) }}</span>
                </div>
                <div class="summary-row row-paid">
                    <span>{{ $t('a_paid_amount') }}</span>
                    <span class="text-green">{{ formatCurrency(invoice.paid_amount) }}</span>
                </div>
                <div class="summary-row row-balance">
                    <span>{{ $t('a_balance_due') }}</span>
                    <span :class="balance > 0 ? 'text-red-bold' : 'text-green-bold'">{{ formatCurrency(balance) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-divider"></div>
            <div class="footer-content">
                <p class="footer-thanks">{{ $t('a_thank_you_clinic') }}</p>
                <p class="footer-note">{{ $t('a_computer_generated_invoice') }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        size: A4;
        margin: 12mm 15mm;
    }
}

* { box-sizing: border-box; }

/* ─── Toolbar ──────────────────────────────────────── */
.toolbar {
    position: fixed;
    top: 0; left: 0; right: 0;
    background: #111827;
    color: #f9fafb;
    padding: 10px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 50;
    box-shadow: 0 2px 12px rgba(0,0,0,.25);
}

.toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 500;
}

.toolbar-actions {
    display: flex;
    gap: 8px;
}

.toolbar-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: opacity .15s;
}
.toolbar-btn:hover { opacity: .85; }

.btn-print { background: #C4A265; color: #fff; }
.btn-pdf { background: #059669; color: #fff; }
.btn-close { background: #374151; color: #d1d5db; }

/* ─── Page ─────────────────────────────────────────── */
.print-page {
    max-width: 800px;
    margin: 64px auto 40px;
    padding: 0 44px 44px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: #1f2937;
    background: #fff;
}

.accent-bar {
    height: 4px;
    background: linear-gradient(90deg, #C4A265, #d4b87a, #C4A265);
    border-radius: 0 0 2px 2px;
    margin-bottom: 36px;
}

/* ─── Header ───────────────────────────────────────── */
.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.clinic-logo {
    width: 68px;
    height: 68px;
    object-fit: contain;
    flex-shrink: 0;
}

.clinic-name {
    font-size: 24px;
    font-weight: 800;
    color: #C4A265;
    margin: 0;
    letter-spacing: .5px;
    line-height: 1.2;
}

.clinic-tagline {
    font-size: 12px;
    color: #6b7280;
    margin: 3px 0 0;
    font-weight: 500;
}

.clinic-location {
    font-size: 11px;
    color: #9ca3af;
    margin: 1px 0 0;
}

.header-right {
    text-align: right;
}

.invoice-label {
    font-size: 28px;
    font-weight: 800;
    color: #1f2937;
    letter-spacing: 4px;
    margin-bottom: 12px;
    line-height: 1;
}

.invoice-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.meta-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    font-size: 12px;
}

.meta-key {
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .5px;
    font-weight: 600;
    font-size: 10px;
}

.meta-val {
    color: #374151;
    font-weight: 600;
}

.meta-val.mono {
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: #C4A265;
    font-size: 13px;
}

.status-pill {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .8px;
    padding: 2px 10px;
    border-radius: 10px;
    text-transform: uppercase;
}

.pill-paid { background: #dcfce7; color: #166534; }
.pill-partial { background: #fef3c7; color: #92400e; }
.pill-unpaid { background: #fee2e2; color: #991b1b; }
.pill-cancelled { background: #f3f4f6; color: #6b7280; }

/* ─── Divider ──────────────────────────────────────── */
.gold-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 28px;
}

.divider-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, #C4A265 20%, #C4A265 80%, transparent);
}

.divider-diamond {
    width: 8px;
    height: 8px;
    background: #C4A265;
    transform: rotate(45deg);
    flex-shrink: 0;
}

/* ─── Info Grid ────────────────────────────────────── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 32px;
}

.info-card {
    padding: 16px 20px;
    border: 1px solid #f3f0eb;
    border-radius: 10px;
    background: #fdfcfa;
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #C4A265;
    margin-bottom: 10px;
}

.info-name {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
}

.info-detail {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6b7280;
    margin: 0;
    line-height: 1.8;
}

/* ─── Items Table ──────────────────────────────────── */
.items-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 28px;
    font-size: 13px;
}

.items-table thead tr {
    background: #1f2937;
}

.items-table th {
    padding: 10px 14px;
    font-weight: 600;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: #d1d5db;
}

.items-table th:first-child { border-radius: 8px 0 0 0; }
.items-table th:last-child { border-radius: 0 8px 0 0; }

.col-num { width: 40px; text-align: center; }
.col-desc { text-align: left; }
.col-qty { width: 60px; text-align: center; }
.col-price { width: 120px; text-align: right; }
.col-disc { width: 100px; text-align: right; }
.col-total { width: 120px; text-align: right; }

.items-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #f3f4f6;
    color: #374151;
}

.items-table tbody tr:hover { background: #fdfcfa; }

.items-table tbody tr:last-child td {
    border-bottom: 2px solid #e5e7eb;
}

.cell-num { text-align: center; color: #9ca3af; font-weight: 500; }
.cell-desc {}
.cell-qty { text-align: center; font-weight: 500; }
.cell-price { text-align: right; }
.cell-disc { text-align: right; color: #9ca3af; }
.cell-total { text-align: right; font-weight: 600; color: #1f2937; }

.desc-en { display: block; font-weight: 500; }
.desc-ar { display: block; font-size: 11px; color: #9ca3af; margin-top: 2px; direction: rtl; }

/* ─── Summary ──────────────────────────────────────── */
.summary-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 32px;
    margin-bottom: 20px;
}

.summary-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    flex: 1;
    padding: 14px 16px;
    background: #fffbf2;
    border: 1px solid #f0e6d2;
    border-radius: 10px;
    max-width: 320px;
    margin-top: 4px;
}

.note-icon { flex-shrink: 0; margin-top: 1px; }

.summary-note p {
    font-size: 11px;
    color: #92700c;
    margin: 0;
    line-height: 1.6;
}

.summary-box {
    width: 300px;
    flex-shrink: 0;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 16px;
    font-size: 13px;
    color: #4b5563;
    border-bottom: 1px solid #f3f4f6;
}

.summary-row:last-child { border-bottom: none; }

.row-total {
    background: #fdfcfa;
    border-bottom: 1px solid #e5e7eb !important;
}

.row-total span {
    font-size: 15px;
    font-weight: 800;
    color: #C4A265;
}

.row-balance {
    background: #f9fafb;
}

.row-balance span:first-child {
    font-weight: 700;
    color: #1f2937;
}

.code-tag {
    font-family: monospace;
    font-size: 10px;
    background: #f3f4f6;
    color: #6b7280;
    padding: 1px 6px;
    border-radius: 4px;
    margin-left: 4px;
}

.text-red { color: #dc2626; }
.text-green { color: #059669; font-weight: 600; }
.text-red-bold { color: #dc2626; font-weight: 800; font-size: 15px; }
.text-green-bold { color: #059669; font-weight: 800; font-size: 15px; }

/* ─── Footer ───────────────────────────────────────── */
.footer {
    margin-top: 36px;
}

.footer-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, #d1d5db, transparent);
    margin-bottom: 20px;
}

.footer-content {
    text-align: center;
}

.footer-thanks {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 4px;
}

.footer-thanks strong {
    color: #C4A265;
}

.footer-note {
    font-size: 10px;
    color: #c0bfbd;
    margin: 0;
    letter-spacing: .2px;
}

/* ─── Print ────────────────────────────────────────── */
@media print {
    .no-print { display: none !important; }

    .print-page {
        padding: 0;
        margin: 0 auto;
    }

    .accent-bar {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .items-table thead tr {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .status-pill,
    .info-card,
    .summary-note,
    .summary-box,
    .row-total,
    .row-balance {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
