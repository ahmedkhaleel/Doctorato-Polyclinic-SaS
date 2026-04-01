@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
    $patient = $prescription->patient;
    $doctor  = $prescription->doctor;
    $items   = $prescription->items;

    // Calculate patient age
    $age = $patient->date_of_birth ? $patient->date_of_birth->age : null;
    $gender = $patient->gender ?? null;
    $hasMedicalAlerts = !empty($patient->medical_notes);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rx #{{ $prescription->id }} - {{ $patient->full_name ?? 'Patient' }}</title>
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
            color: #2d2d2d;
            font-size: 11px;
            line-height: 1.6;
            background: #ffffff;
        }

        .page {
            padding: 25px 35px;
            position: relative;
        }

        /* ═══ Clinic Header ═══ */
        .clinic-header {
            text-align: center;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 3px solid #C4A265;
            position: relative;
        }

        .header-content {
            display: inline-block;
            text-align: center;
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

        .clinic-subtitle {
            font-size: 10px;
            color: #777777;
            letter-spacing: 1px;
        }

        /* ═══ Rx Header Bar ═══ */
        .rx-header {
            background-color: #faf7f0;
            border: 1px solid #e8dfc8;
            border-radius: 5px;
            padding: 8px 15px;
            margin-bottom: 12px;
        }

        .rx-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rx-header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .rx-number {
            font-size: 18px;
            font-weight: bold;
            color: #C4A265;
            font-family: 'Amiri', serif;
        }

        .rx-number span {
            font-size: 11px;
            color: #999999;
            font-weight: normal;
        }

        .rx-title {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            color: #333333;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .rx-date {
            text-align: right;
            font-size: 10px;
            color: #777777;
        }

        .rx-date strong {
            color: #444444;
            font-size: 11px;
        }

        /* ═══ Medical Alert ═══ */
        .medical-alert {
            background-color: #fef2f2;
            border: 1.5px solid #e74c3c;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }

        .medical-alert-title {
            font-size: 9px;
            font-weight: bold;
            color: #e74c3c;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .medical-alert-text {
            font-size: 11px;
            color: #b91c1c;
            font-weight: 500;
        }

        /* ═══ Info Table ═══ */
        .info-table {
            width: 100%;
            border: 1px solid #e0ddd5;
            border-radius: 5px;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px 14px;
            vertical-align: top;
            border: 1px solid #e0ddd5;
        }

        .info-section-title {
            font-size: 9px;
            font-weight: bold;
            color: #C4A265;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #f0ece0;
        }

        .info-row {
            margin-bottom: 2px;
        }

        .info-label {
            color: #888888;
            font-size: 10px;
            display: inline-block;
            min-width: 75px;
        }

        .info-value {
            color: #333333;
            font-weight: 600;
            font-size: 11px;
        }

        .info-value.gold {
            color: #C4A265;
            font-family: monospace;
            font-weight: bold;
        }

        .info-value.gender {
            display: inline-block;
            padding: 0 6px;
            background-color: #f0ece0;
            border-radius: 3px;
            font-size: 10px;
            color: #666666;
        }

        /* ═══ Diagnosis Box ═══ */
        .diagnosis-box {
            background-color: #faf8f3;
            border: 1px solid #e8e0d0;
            border-left: 4px solid #C4A265;
            border-radius: 0 5px 5px 0;
            padding: 8px 14px;
            margin-bottom: 10px;
        }

        .diagnosis-box .label {
            font-size: 9px;
            font-weight: bold;
            color: #C4A265;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .diagnosis-box .value {
            font-size: 12px;
            color: #2d2d2d;
            font-weight: 500;
        }

        /* ═══ Notes Box ═══ */
        .notes-box {
            background-color: #f9faf8;
            border: 1px solid #d5dcd0;
            border-left: 4px solid #6b8f5e;
            border-radius: 0 5px 5px 0;
            padding: 8px 14px;
            margin-bottom: 12px;
        }

        .notes-box .label {
            font-size: 9px;
            font-weight: bold;
            color: #6b8f5e;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 2px;
        }

        .notes-box .value {
            font-size: 11px;
            color: #444444;
            white-space: pre-wrap;
        }

        /* ═══ Medications Table ═══ */
        .medications-header {
            font-size: 9px;
            font-weight: bold;
            color: #C4A265;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #f0ece0;
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }

        .med-table thead tr {
            background-color: #C4A265;
        }

        .med-table th {
            padding: 7px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #b3924d;
        }

        .med-table th.num-col {
            width: 25px;
            text-align: center;
        }

        .med-table th.med-col {
            width: 22%;
        }

        .med-table tbody tr {
            border-bottom: 1px solid #e5e2da;
        }

        .med-table tbody tr:nth-child(even) {
            background-color: #fdfcfa;
        }

        .med-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .med-table td {
            padding: 7px 8px;
            border-left: 1px solid #e5e2da;
            border-right: 1px solid #e5e2da;
            color: #444444;
            vertical-align: top;
        }

        .med-table td.med-name {
            font-weight: bold;
            color: #2d2d2d;
            font-size: 11px;
        }

        .med-table td.num {
            color: #C4A265;
            text-align: center;
            font-weight: bold;
        }

        .med-table td.instructions-cell {
            font-size: 9px;
            color: #666666;
            font-style: italic;
        }

        /* Bottom border for last row */
        .med-table tbody tr:last-child td {
            border-bottom: 2px solid #C4A265;
        }

        .no-meds {
            text-align: center;
            color: #999999;
            padding: 20px;
            border-bottom: 2px solid #C4A265;
        }

        /* Medication count badge */
        .med-count {
            display: inline-block;
            background-color: #C4A265;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 1px 8px;
            border-radius: 10px;
            margin-left: 8px;
        }

        /* ═══ Doctor Signature ═══ */
        .signature-section {
            margin-top: 40px;
            float: right;
            text-align: center;
            min-width: 240px;
        }

        .signature-line {
            border-bottom: 1.5px solid #C4A265;
            height: 45px;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 12px;
            font-weight: bold;
            color: #2d2d2d;
        }

        .signature-spec {
            font-size: 10px;
            color: #777777;
        }

        .signature-label {
            font-size: 8px;
            color: #bbbbbb;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ═══ Footer ═══ */
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 8px;
            border-top: 2px solid #C4A265;
            text-align: center;
            font-size: 9px;
            color: #999999;
        }

        .footer-line {
            margin-bottom: 2px;
        }

        .footer-validity {
            font-style: italic;
            color: #bbbbbb;
        }

        /* ═══ Watermark ═══ */
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
    </style>
</head>
<body>
    <div class="watermark">AURA</div>

    <div class="page">
        <!-- ═══ Clinic Header ═══ -->
        <div class="clinic-header">
            <div class="header-content">
                @if(file_exists(public_path('images/logo/logo.png')))
                    <img src="{{ public_path('images/logo/logo.png') }}" class="clinic-logo-img" alt="AURA">
                @else
                    <div class="clinic-logo-fallback">A</div>
                @endif
                <div class="clinic-name">AURA Derma Clinic</div>
                <div class="clinic-name-ar">{{ $ar('اورا ديرما كلينيك') }}</div>
                <div class="clinic-subtitle">Dermatology &amp; Aesthetic Medicine</div>
            </div>
        </div>

        <!-- ═══ Rx Header Bar ═══ -->
        <div class="rx-header">
            <table class="rx-header-table">
                <tr>
                    <td style="width: 25%;">
                        <div class="rx-number">
                            Rx <span>#</span>{{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <div class="rx-title">Medical Prescription</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="rx-date">
                            <strong>{{ $prescription->created_at ? $prescription->created_at->format('d M Y') : '-' }}</strong>
                            <br>{{ $prescription->created_at ? $prescription->created_at->format('h:i A') : '' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ Medical Alert ═══ -->
        @if($hasMedicalAlerts)
        <div class="medical-alert">
            <div class="medical-alert-title">&#9888; Medical Alert / Allergies</div>
            <div class="medical-alert-text">{{ $ar($patient->medical_notes) }}</div>
        </div>
        @endif

        <!-- ═══ Patient & Doctor Info ═══ -->
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div class="info-section-title">Patient Information</div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value">{{ $ar($patient->full_name ?? '-') }}</span>
                    </div>
                    @if($patient->file_number ?? null)
                    <div class="info-row">
                        <span class="info-label">File #:</span>
                        <span class="info-value gold">{{ $patient->file_number }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        @if($age)
                        <span class="info-label">Age:</span>
                        <span class="info-value">{{ $age }} years</span>
                        @endif
                        @if($gender)
                        &nbsp;&nbsp;
                        <span class="info-value gender">{{ ucfirst($gender) }}</span>
                        @endif
                    </div>
                    @if($patient->phone ?? null)
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value">{{ $patient->phone }}</span>
                    </div>
                    @endif
                </td>
                <td style="width: 50%;">
                    <div class="info-section-title">Prescribing Doctor</div>
                    <div class="info-row">
                        <span class="info-label">Doctor:</span>
                        <span class="info-value">Dr. {{ $ar($doctor->name_en ?? 'Unknown') }}</span>
                    </div>
                    @if($doctor->specialization_en ?? null)
                    <div class="info-row">
                        <span class="info-label">Specialization:</span>
                        <span class="info-value">{{ $ar($doctor->specialization_en) }}</span>
                    </div>
                    @endif
                    @if($doctor->phone ?? null)
                    <div class="info-row">
                        <span class="info-label">Contact:</span>
                        <span class="info-value">{{ $doctor->phone }}</span>
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- ═══ Diagnosis ═══ -->
        @if($prescription->diagnosis)
        <div class="diagnosis-box">
            <div class="label">Diagnosis</div>
            <div class="value">{{ $ar($prescription->diagnosis) }}</div>
        </div>
        @endif

        <!-- ═══ Medications Table ═══ -->
        <div class="medications-header">
            Prescribed Medications
            <span class="med-count">{{ $items->count() }} {{ $items->count() === 1 ? 'Item' : 'Items' }}</span>
        </div>
        <table class="med-table">
            <thead>
                <tr>
                    <th class="num-col">#</th>
                    <th class="med-col">Medication</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Instructions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td class="med-name">{{ $ar($item->medication_name) }}</td>
                    <td>{{ $ar($item->dosage ?: '-') }}</td>
                    <td>{{ $ar($item->frequency ?: '-') }}</td>
                    <td>{{ $ar($item->duration ?: '-') }}</td>
                    <td class="instructions-cell">{{ $ar($item->instructions ?: '-') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-meds">No medications listed.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- ═══ Notes ═══ -->
        @if($prescription->notes)
        <div class="notes-box">
            <div class="label">Additional Notes</div>
            <div class="value">{{ $ar($prescription->notes) }}</div>
        </div>
        @endif

        <!-- ═══ Doctor Signature ═══ -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <div class="signature-name">Dr. {{ $ar($doctor->name_en ?? 'Unknown') }}</div>
            @if($doctor->specialization_en ?? null)
            <div class="signature-spec">{{ $ar($doctor->specialization_en) }}</div>
            @endif
            <div class="signature-label">Signature &amp; Stamp</div>
        </div>

        <!-- ═══ Footer ═══ -->
        <div class="footer">
            <div class="footer-line">
                AURA Derma Clinic &middot; Dermatology &amp; Aesthetic Medicine
            </div>
            <div class="footer-validity">
                This prescription is valid for one month from the date of issue &middot; Rx #{{ str_pad($prescription->id, 5, '0', STR_PAD_LEFT) }}
            </div>
        </div>
    </div>
</body>
</html>
