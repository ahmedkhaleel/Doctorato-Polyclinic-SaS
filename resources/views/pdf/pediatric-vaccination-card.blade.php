@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Vaccination Card - {{ $patient->full_name ?? 'Patient' }}</title>
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
            size: A4 landscape;
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
            font-size: 10px;
            line-height: 1.5;
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
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 3px solid #C4A265;
        }

        .clinic-logo-img {
            width: 45px;
            height: auto;
            margin-bottom: 2px;
        }

        .clinic-logo-fallback {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #C4A265;
            color: #ffffff;
            text-align: center;
            line-height: 40px;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .clinic-name {
            font-size: 18px;
            font-weight: bold;
            color: #C4A265;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .clinic-name-ar {
            font-size: 12px;
            color: #9a8254;
            letter-spacing: 1px;
            margin-top: -2px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .doc-title-ar {
            font-size: 12px;
            color: #666666;
            margin-top: -1px;
        }

        /* Patient Info */
        .patient-info {
            background-color: #faf7f0;
            border: 1px solid #e8dfc8;
            border-radius: 5px;
            padding: 8px 14px;
            margin-bottom: 10px;
        }

        .patient-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-info-table td {
            padding: 2px 8px;
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

        /* Vaccination Table */
        .vac-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .vac-table thead tr {
            background-color: #C4A265;
        }

        .vac-table th {
            padding: 6px 7px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #b3924d;
        }

        .vac-table th.num-col {
            width: 22px;
            text-align: center;
        }

        .vac-table tbody tr {
            border-bottom: 1px solid #e5e2da;
            page-break-inside: avoid;
        }

        .vac-table tbody tr:nth-child(even) {
            background-color: #fdfcfa;
        }

        .vac-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .vac-table td {
            padding: 5px 7px;
            border-left: 1px solid #e5e2da;
            border-right: 1px solid #e5e2da;
            color: #444444;
            vertical-align: middle;
        }

        .vac-table td.num {
            text-align: center;
            color: #C4A265;
            font-weight: bold;
        }

        .vac-table td.vaccine-name {
            font-weight: bold;
            color: #2d2d2d;
            font-size: 10px;
        }

        .vac-table td.vaccine-name .ar-name {
            font-weight: normal;
            color: #888888;
            font-size: 9px;
            display: block;
        }

        .vac-table tbody tr:last-child td {
            border-bottom: 2px solid #C4A265;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-given {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-scheduled {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .status-missed {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .status-postponed {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .no-records {
            text-align: center;
            color: #999999;
            padding: 20px;
            border-bottom: 2px solid #C4A265;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 2px solid #C4A265;
            text-align: center;
            font-size: 9px;
            color: #999999;
        }

        .footer-line {
            margin-bottom: 2px;
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
        <div class="doc-title">Vaccination Card</div>
        <div class="doc-title-ar" dir="rtl">{{ $ar('بطاقة التطعيمات') }}</div>
    </div>

    {{-- Patient Information --}}
    <div class="patient-info">
        <table class="patient-info-table">
            <tr>
                <td style="width: 25%;">
                    <div class="info-label">Patient Name</div>
                    <div class="info-value">{{ $ar($patient->full_name ?? '-') }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">{{ $patient->date_of_birth ? $patient->date_of_birth->format('d M Y') : '-' }}</div>
                </td>
                <td style="width: 15%;">
                    <div class="info-label">Age</div>
                    <div class="info-value">{{ $ageMonths ?? '-' }} months</div>
                </td>
                <td style="width: 20%;">
                    <div class="info-label">File Number</div>
                    <div class="info-value gold">{{ $patient->file_number ?? '-' }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="info-label">Guardian Name</div>
                    <div class="info-value">{{ $ar($patient->guardian_name ?? '-') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Vaccination Table --}}
    <table class="vac-table">
        <thead>
            <tr>
                <th class="num-col">#</th>
                <th style="width: 20%;">Vaccine Name</th>
                <th style="width: 8%;">Dose</th>
                <th style="width: 10%;">Scheduled Age</th>
                <th style="width: 12%;">Scheduled Date</th>
                <th style="width: 12%;">Given Date</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 12%;">Batch Number</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vaccinations as $index => $v)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td class="vaccine-name">
                    {{ $v->vaccine_name ?? '-' }}
                    @if($v->vaccine_name_ar ?? null)
                        <span class="ar-name" dir="rtl">{{ $ar($v->vaccine_name_ar) }}</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $v->dose_number ?? '-' }}</td>
                <td>{{ $v->scheduled_age ?? '-' }}</td>
                <td>{{ $v->scheduled_date ? \Carbon\Carbon::parse($v->scheduled_date)->format('d M Y') : '-' }}</td>
                <td>{{ $v->given_date ? \Carbon\Carbon::parse($v->given_date)->format('d M Y') : '-' }}</td>
                <td style="text-align: center;">
                    @php
                        $status = strtolower($v->status ?? 'scheduled');
                    @endphp
                    @if($status === 'given')
                        <span class="status-badge status-given">Given</span>
                    @elseif($status === 'scheduled')
                        <span class="status-badge status-scheduled">Scheduled</span>
                    @elseif($status === 'missed')
                        <span class="status-badge status-missed">Missed</span>
                    @elseif($status === 'postponed')
                        <span class="status-badge status-postponed">Postponed</span>
                    @else
                        <span class="status-badge status-scheduled">{{ ucfirst($status) }}</span>
                    @endif
                </td>
                <td style="text-align: center; font-family: monospace; font-size: 9px;">{{ $v->batch_number ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="no-records">No vaccination records found.</td>
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
    </div>
</body>
</html>
