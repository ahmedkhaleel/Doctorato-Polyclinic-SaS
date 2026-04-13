@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);

    $latestRecord = $growthRecords->first();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Growth Report - {{ $patient->full_name ?? 'Patient' }}</title>
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

        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Amiri', Arial, Helvetica, sans-serif;
            color: #2d2d2d;
            font-size: 11px;
            line-height: 1.6;
            background: #ffffff;
        }

        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 70px;
            color: rgba(196, 162, 101, 0.04);
            font-weight: bold;
            letter-spacing: 15px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: -1;
        }

        /* Header */
        .clinic-header {
            text-align: center;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 3px solid #C4A265;
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
            font-size: 20px;
            font-weight: bold;
            color: #C4A265;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .clinic-name-ar {
            font-size: 14px;
            color: #9a8254;
            letter-spacing: 1px;
            margin-top: -2px;
        }

        .doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 4px;
        }

        .doc-title-ar {
            font-size: 13px;
            color: #666666;
            margin-top: -1px;
        }

        /* Patient Info */
        .patient-info {
            background-color: #faf7f0;
            border: 1px solid #e8dfc8;
            border-radius: 5px;
            padding: 8px 14px;
            margin-bottom: 12px;
        }

        .patient-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-info-table td {
            padding: 3px 8px;
            vertical-align: top;
        }

        .info-label {
            color: #888888;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #333333;
            font-weight: bold;
            font-size: 11px;
        }

        .info-value.gold {
            color: #C4A265;
            font-family: monospace;
        }

        .info-value.gender {
            display: inline-block;
            padding: 0 6px;
            background-color: #f0ece0;
            border-radius: 3px;
            font-size: 10px;
            color: #666666;
        }

        /* Summary Section */
        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #C4A265;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #f0ece0;
        }

        .summary-section {
            margin-bottom: 12px;
        }

        .summary-cards {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-card {
            background-color: #faf8f3;
            border: 1px solid #e8e0d0;
            border-radius: 5px;
            padding: 10px 14px;
            text-align: center;
            width: 25%;
        }

        .summary-card .metric-label {
            font-size: 8px;
            color: #888888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .summary-card .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #C4A265;
        }

        .summary-card .metric-unit {
            font-size: 9px;
            color: #999999;
            font-weight: normal;
        }

        .summary-card .metric-date {
            font-size: 8px;
            color: #bbbbbb;
            margin-top: 2px;
        }

        /* Growth Status */
        .growth-status {
            background-color: #f9faf8;
            border: 1px solid #d5dcd0;
            border-left: 4px solid #6b8f5e;
            border-radius: 0 5px 5px 0;
            padding: 8px 14px;
            margin-bottom: 12px;
        }

        .growth-status .label {
            font-size: 9px;
            font-weight: bold;
            color: #6b8f5e;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .growth-status .value {
            font-size: 11px;
            color: #444444;
        }

        .percentile-normal {
            color: #166534;
            font-weight: bold;
        }

        .percentile-warning {
            color: #92400e;
            font-weight: bold;
        }

        .percentile-alert {
            color: #991b1b;
            font-weight: bold;
        }

        /* Growth Table */
        .growth-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .growth-table thead tr {
            background-color: #C4A265;
        }

        .growth-table th {
            padding: 7px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #b3924d;
        }

        .growth-table th.num-col {
            width: 25px;
            text-align: center;
        }

        .growth-table tbody tr {
            border-bottom: 1px solid #e5e2da;
            page-break-inside: avoid;
        }

        .growth-table tbody tr:nth-child(even) {
            background-color: #fdfcfa;
        }

        .growth-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .growth-table td {
            padding: 6px 8px;
            border-left: 1px solid #e5e2da;
            border-right: 1px solid #e5e2da;
            color: #444444;
            vertical-align: middle;
        }

        .growth-table td.num {
            text-align: center;
            color: #C4A265;
            font-weight: bold;
        }

        .growth-table tbody tr:last-child td {
            border-bottom: 2px solid #C4A265;
        }

        .no-records {
            text-align: center;
            color: #999999;
            padding: 20px;
            border-bottom: 2px solid #C4A265;
        }

        .record-count {
            display: inline-block;
            background-color: #C4A265;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 8px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 2px solid #C4A265;
            text-align: center;
            font-size: 9px;
            color: #999999;
        }

        .footer-line {
            margin-bottom: 2px;
        }

        .footer-generated {
            font-style: italic;
            color: #bbbbbb;
        }
    </style>
</head>
<body>
    <div class="watermark">AURA</div>

    {{-- Clinic Header --}}
    <div class="clinic-header">
        @if(file_exists(public_path('images/logo/logo.png')))
            <img src="{{ public_path('images/logo/logo.png') }}" class="clinic-logo-img" alt="AURA">
        @else
            <div class="clinic-logo-fallback">A</div>
        @endif
        <div class="clinic-name">{{ $clinicName ?? 'AURA Derma Clinic' }}</div>
        <div class="clinic-name-ar">{{ $ar('اورا ديرما كلينيك') }}</div>
        <div class="doc-title">Growth Report</div>
        <div class="doc-title-ar" dir="rtl">{{ $ar('تقرير النمو') }}</div>
    </div>

    {{-- Patient Information --}}
    <div class="patient-info">
        <table class="patient-info-table">
            <tr>
                <td style="width: 30%;">
                    <div class="info-label">Patient Name</div>
                    <div class="info-value">{{ $ar($patient->full_name ?? '-') }}</div>
                </td>
                <td style="width: 18%;">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">{{ $patient->date_of_birth ? $patient->date_of_birth->format('d M Y') : '-' }}</div>
                </td>
                <td style="width: 12%;">
                    <div class="info-label">Gender</div>
                    <div class="info-value gender">{{ ucfirst($patient->gender ?? '-') }}</div>
                </td>
                <td style="width: 18%;">
                    <div class="info-label">File Number</div>
                    <div class="info-value gold">{{ $patient->file_number ?? '-' }}</div>
                </td>
                <td style="width: 22%;">
                    <div class="info-label">Current Age</div>
                    <div class="info-value">{{ $ageMonths ?? '-' }} months</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Latest Measurements Summary --}}
    @if($latestRecord)
    <div class="summary-section">
        <div class="section-title">Latest Measurements</div>
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="metric-label">Weight</div>
                    <div class="metric-value">
                        {{ $latestRecord->weight ?? '-' }}
                        <span class="metric-unit">kg</span>
                    </div>
                    <div class="metric-date">{{ $latestRecord->recorded_at ? \Carbon\Carbon::parse($latestRecord->recorded_at)->format('d M Y') : ($latestRecord->date ? \Carbon\Carbon::parse($latestRecord->date)->format('d M Y') : '') }}</div>
                </td>
                <td style="width: 4px;"></td>
                <td class="summary-card">
                    <div class="metric-label">Height</div>
                    <div class="metric-value">
                        {{ $latestRecord->height ?? '-' }}
                        <span class="metric-unit">cm</span>
                    </div>
                    <div class="metric-date">{{ $latestRecord->recorded_at ? \Carbon\Carbon::parse($latestRecord->recorded_at)->format('d M Y') : ($latestRecord->date ? \Carbon\Carbon::parse($latestRecord->date)->format('d M Y') : '') }}</div>
                </td>
                <td style="width: 4px;"></td>
                <td class="summary-card">
                    <div class="metric-label">Head Circumference</div>
                    <div class="metric-value">
                        {{ $latestRecord->head_circumference ?? '-' }}
                        <span class="metric-unit">cm</span>
                    </div>
                    <div class="metric-date">{{ $latestRecord->recorded_at ? \Carbon\Carbon::parse($latestRecord->recorded_at)->format('d M Y') : ($latestRecord->date ? \Carbon\Carbon::parse($latestRecord->date)->format('d M Y') : '') }}</div>
                </td>
                <td style="width: 4px;"></td>
                <td class="summary-card">
                    <div class="metric-label">BMI</div>
                    <div class="metric-value">
                        {{ $latestRecord->bmi ?? (($latestRecord->weight && $latestRecord->height) ? number_format($latestRecord->weight / (($latestRecord->height / 100) ** 2), 1) : '-') }}
                    </div>
                    <div class="metric-date">kg/m&sup2;</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Growth Status --}}
    @if($latestRecord && ($latestRecord->weight_percentile ?? null || $latestRecord->height_percentile ?? null || $latestRecord->bmi_percentile ?? null))
    <div class="growth-status">
        <div class="label">Growth Status Indicators</div>
        <div class="value">
            @if($latestRecord->weight_percentile ?? null)
                <strong>Weight:</strong>
                @if($latestRecord->weight_percentile >= 5 && $latestRecord->weight_percentile <= 95)
                    <span class="percentile-normal">{{ $latestRecord->weight_percentile }}th percentile (Normal)</span>
                @elseif($latestRecord->weight_percentile < 5 || $latestRecord->weight_percentile > 95)
                    <span class="percentile-alert">{{ $latestRecord->weight_percentile }}th percentile (Needs Attention)</span>
                @else
                    <span class="percentile-warning">{{ $latestRecord->weight_percentile }}th percentile</span>
                @endif
                &nbsp;&nbsp;
            @endif
            @if($latestRecord->height_percentile ?? null)
                <strong>Height:</strong>
                @if($latestRecord->height_percentile >= 5 && $latestRecord->height_percentile <= 95)
                    <span class="percentile-normal">{{ $latestRecord->height_percentile }}th percentile (Normal)</span>
                @elseif($latestRecord->height_percentile < 5 || $latestRecord->height_percentile > 95)
                    <span class="percentile-alert">{{ $latestRecord->height_percentile }}th percentile (Needs Attention)</span>
                @else
                    <span class="percentile-warning">{{ $latestRecord->height_percentile }}th percentile</span>
                @endif
                &nbsp;&nbsp;
            @endif
            @if($latestRecord->bmi_percentile ?? null)
                <strong>BMI:</strong>
                @if($latestRecord->bmi_percentile >= 5 && $latestRecord->bmi_percentile <= 85)
                    <span class="percentile-normal">{{ $latestRecord->bmi_percentile }}th percentile (Normal)</span>
                @elseif($latestRecord->bmi_percentile > 85 && $latestRecord->bmi_percentile <= 95)
                    <span class="percentile-warning">{{ $latestRecord->bmi_percentile }}th percentile (Overweight)</span>
                @elseif($latestRecord->bmi_percentile > 95)
                    <span class="percentile-alert">{{ $latestRecord->bmi_percentile }}th percentile (Obese)</span>
                @elseif($latestRecord->bmi_percentile < 5)
                    <span class="percentile-alert">{{ $latestRecord->bmi_percentile }}th percentile (Underweight)</span>
                @endif
            @endif
        </div>
    </div>
    @endif

    {{-- Growth Records Table --}}
    <div class="section-title">
        Growth History
        <span class="record-count">{{ $growthRecords->count() }} {{ $growthRecords->count() === 1 ? 'Record' : 'Records' }}</span>
    </div>
    <table class="growth-table">
        <thead>
            <tr>
                <th class="num-col">#</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 12%;">Age (months)</th>
                <th style="width: 14%;">Weight (kg)</th>
                <th style="width: 14%;">Height (cm)</th>
                <th style="width: 16%;">Head Circ (cm)</th>
                <th style="width: 12%;">BMI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($growthRecords as $index => $record)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>{{ $record->recorded_at ? \Carbon\Carbon::parse($record->recorded_at)->format('d M Y') : ($record->date ? \Carbon\Carbon::parse($record->date)->format('d M Y') : '-') }}</td>
                <td style="text-align: center;">{{ $record->age_months ?? '-' }}</td>
                <td style="text-align: center;">{{ $record->weight ? number_format($record->weight, 2) : '-' }}</td>
                <td style="text-align: center;">{{ $record->height ? number_format($record->height, 1) : '-' }}</td>
                <td style="text-align: center;">{{ $record->head_circumference ? number_format($record->head_circumference, 1) : '-' }}</td>
                <td style="text-align: center;">
                    {{ $record->bmi ?? (($record->weight && $record->height) ? number_format($record->weight / (($record->height / 100) ** 2), 1) : '-') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="no-records">No growth records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-line">
            {{ $clinicName ?? 'AURA Derma Clinic' }} &middot; Phone: {{ $clinicPhone ?? '-' }}
        </div>
        <div class="footer-line">
            Printed on: {{ now()->format('d M Y, h:i A') }}
        </div>
        <div class="footer-generated">
            Generated by Aura Derma Clinic
        </div>
    </div>
</body>
</html>
