@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @font-face {
            font-family: 'Amiri';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/Amiri-Regular.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Amiri';
            font-style: normal;
            font-weight: bold;
            src: url("{{ storage_path('fonts/Amiri-Bold.ttf') }}") format("truetype");
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Amiri', 'DejaVu Sans', sans-serif;
            color: #333333;
            font-size: 12px;
            line-height: 1.5;
        }

        .page {
            padding: 30px 40px;
        }

        /* Header */
        .header {
            width: 100%;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .clinic-logo-img {
            width: 55px;
            height: auto;
            margin-bottom: 3px;
        }

        .clinic-logo-fallback {
            display: inline-block;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #C4A265;
            color: #ffffff;
            text-align: center;
            line-height: 48px;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .clinic-name {
            font-size: 22px;
            font-weight: bold;
            color: #C4A265;
            letter-spacing: 1px;
        }

        .clinic-address {
            font-size: 11px;
            color: #888888;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #333333;
            letter-spacing: 2px;
            text-align: right;
        }

        .invoice-number {
            font-size: 13px;
            font-family: monospace;
            color: #C4A265;
            font-weight: bold;
            text-align: right;
        }

        .invoice-date {
            font-size: 11px;
            color: #888888;
            text-align: right;
            margin-top: 3px;
        }

        /* Divider */
        .divider {
            height: 2px;
            background-color: #C4A265;
            margin-bottom: 20px;
        }

        /* Patient Section */
        .patient-section {
            margin-bottom: 20px;
        }

        .section-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888888;
            margin-bottom: 4px;
        }

        .patient-name {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            margin-bottom: 3px;
        }

        .patient-detail {
            font-size: 11px;
            color: #666666;
            line-height: 1.6;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .items-table thead tr {
            background-color: #faf8f3;
        }

        .items-table th {
            padding: 8px 10px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666666;
            border-bottom: 2px solid #C4A265;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eeeeee;
            color: #444444;
        }

        .items-table .text-left { text-align: left; }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }

        .ar-desc {
            color: #999999;
            font-size: 10px;
            direction: rtl;
            unicode-bidi: bidi-override;
        }

        /* Totals */
        .totals-wrapper {
            width: 100%;
        }

        .totals-table {
            width: 280px;
            border-collapse: collapse;
            float: right;
            margin-bottom: 20px;
        }

        .totals-table td {
            padding: 5px 0;
            font-size: 12px;
        }

        .totals-table .label {
            color: #666666;
            text-align: left;
        }

        .totals-table .value {
            font-weight: 500;
            color: #333333;
            text-align: right;
        }

        .totals-table .value.discount {
            color: #dc2626;
        }

        .totals-table .value.paid {
            color: #16a34a;
        }

        .totals-table .total-row td {
            border-top: 2px solid #C4A265;
            border-bottom: 1px solid #eeeeee;
            padding: 8px 0;
        }

        .totals-table .total-row .label,
        .totals-table .total-row .value {
            font-size: 14px;
            font-weight: bold;
            color: #C4A265;
        }

        .totals-table .balance-row td {
            border-top: 1px solid #eeeeee;
            padding-top: 8px;
        }

        .totals-table .balance-row .label {
            font-weight: bold;
            color: #333333;
        }

        .value.balance-due {
            color: #dc2626;
            font-weight: bold;
        }

        .value.balance-clear {
            color: #16a34a;
            font-weight: bold;
        }

        .discount-code {
            font-family: monospace;
            font-size: 10px;
            color: #999999;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 16px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            border-radius: 4px;
            clear: both;
        }

        .status-paid {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-partial {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .status-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .status-cancelled {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }

        /* Footer */
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #eeeeee;
            text-align: center;
        }

        .footer p {
            font-size: 11px;
            color: #888888;
        }

        .footer-small {
            font-size: 10px !important;
            color: #bbbbbb !important;
            margin-top: 3px;
        }

        /* Arabic text styling */
        .ar-text {
            direction: rtl;
            unicode-bidi: bidi-override;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                        @if(file_exists(public_path('images/logo/logo.png')))
                            <img src="{{ public_path('images/logo/logo.png') }}" class="clinic-logo-img" alt="AURA">
                        @else
                            <div class="clinic-logo-fallback">A</div>
                        @endif
                        <div class="clinic-name">AURA Derma Clinic</div>
                        <div class="clinic-address">Dermatology &amp; Cosmetic Center</div>
                        <div class="clinic-address">Cairo, Egypt</div>
                    </td>
                    <td style="width: 50%;">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                        <div class="invoice-date">{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') : ($invoice->created_at ? $invoice->created_at->format('d/m/Y') : '-') }}</div>
                        @if($invoice->module)
                        <div class="invoice-date" style="margin-top: 4px; font-weight: bold; color: #C4A265;">
                            {{ $invoice->module === 'dental' ? 'Dental Department / ' . $ar('قسم الأسنان') : 'Dermatology Department / ' . $ar('قسم الجلدية') }}
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <!-- Patient Info -->
        <div class="patient-section">
            <div class="section-label">Bill To:</div>
            <div class="patient-name">{{ $ar($invoice->patient->full_name ?? '-') }}</div>
            @if($invoice->patient->phone ?? null)
            <div class="patient-detail">Phone: {{ $invoice->patient->phone }}</div>
            @endif
            @if($invoice->patient->file_number ?? null)
            <div class="patient-detail">File #: {{ $invoice->patient->file_number }}</div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 30px;">#</th>
                    <th class="text-left">Description</th>
                    <th class="text-center" style="width: 50px;">Qty</th>
                    <th class="text-right" style="width: 90px;">Unit Price</th>
                    <th class="text-right" style="width: 80px;">Discount</th>
                    <th class="text-right" style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-left">{{ $index + 1 }}</td>
                    <td class="text-left">
                        {{ $ar($item->description_en) }}
                        @if($item->description_ar && $item->description_ar !== $item->description_en)
                        <span class="ar-desc">/ {{ $ar($item->description_ar) }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 0) }} {{ $currency ?? 'EGP' }}</td>
                    <td class="text-right">{{ $item->discount > 0 ? number_format($item->discount, 0) . ' ' . ($currency ?? 'EGP') : '-' }}</td>
                    <td class="text-right">{{ number_format($item->total, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrapper">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ number_format($invoice->subtotal, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">
                        Discount
                        @if($invoice->discountCode)
                        <span class="discount-code">({{ $invoice->discountCode->code }})</span>
                        @endif
                    </td>
                    <td class="value discount">-{{ number_format($invoice->discount_amount, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label">Total</td>
                    <td class="value">{{ number_format($invoice->total, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
                <tr>
                    <td class="label">Paid Amount</td>
                    <td class="value paid">{{ number_format($invoice->paid_amount, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
                @php $balance = $invoice->total - $invoice->paid_amount; @endphp
                <tr class="balance-row">
                    <td class="label">Balance Due</td>
                    <td class="value {{ $balance > 0 ? 'balance-due' : 'balance-clear' }}">{{ number_format($balance, 0) }} {{ $currency ?? 'EGP' }}</td>
                </tr>
            </table>
        </div>

        <!-- Status Badge -->
        @php
            $statusLabels = [
                'paid' => 'PAID',
                'partial' => 'PARTIALLY PAID',
                'unpaid' => 'UNPAID',
                'cancelled' => 'CANCELLED',
            ];
        @endphp
        <div class="status-badge status-{{ $invoice->status }}">
            {{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing AURA Derma Clinic</p>
            <p class="footer-small">This is a computer-generated invoice.</p>
        </div>
    </div>
</body>
</html>
