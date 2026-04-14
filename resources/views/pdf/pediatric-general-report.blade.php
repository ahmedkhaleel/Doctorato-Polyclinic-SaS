@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Medical Report - {{ $patient->full_name ?? 'Patient' }}</title>
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

        @page { size: A4; margin: 20mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Amiri', Arial, Helvetica, sans-serif;
            color: #2d2d2d;
            font-size: 11px;
            line-height: 1.6;
            background: #ffffff;
        }

        .watermark {
            position: fixed; top: 45%; left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 80px; color: rgba(196, 162, 101, 0.04);
            font-weight: bold; letter-spacing: 15px; text-transform: uppercase;
            pointer-events: none; z-index: -1;
        }

        .clinic-header {
            text-align: center; padding-bottom: 12px; margin-bottom: 14px;
            border-bottom: 3px solid #C4A265;
        }
        .clinic-logo-img { width: 50px; height: auto; margin-bottom: 4px; }
        .clinic-logo-fallback {
            display: inline-block; width: 44px; height: 44px; border-radius: 50%;
            background-color: #C4A265; color: #ffffff; text-align: center;
            line-height: 44px; font-size: 22px; font-weight: bold; margin-bottom: 4px;
        }
        .clinic-name { font-size: 20px; font-weight: bold; color: #C4A265; letter-spacing: 2px; text-transform: uppercase; }
        .clinic-name-ar { font-size: 13px; color: #9a8254; letter-spacing: 1px; margin-top: -2px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #333333; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; }
        .doc-title-ar { font-size: 13px; color: #666666; margin-top: -1px; }

        .patient-info {
            background-color: #faf7f0; border: 1px solid #e8dfc8;
            border-radius: 5px; padding: 10px 14px; margin-bottom: 16px;
        }
        .patient-info-table { width: 100%; border-collapse: collapse; }
        .patient-info-table td { padding: 3px 8px; vertical-align: top; }
        .info-label { color: #888888; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #333333; font-weight: bold; font-size: 11px; }
        .info-value.gold { color: #C4A265; font-family: monospace; }

        .section { margin-bottom: 14px; }
        .section-title {
            font-size: 12px; font-weight: bold; color: #C4A265;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid #e8dfc8; padding-bottom: 3px; margin-bottom: 6px;
        }
        .section-content { font-size: 11px; color: #444444; line-height: 1.7; padding-left: 4px; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 6px; }
        .data-table th {
            background-color: #f5f0e5; padding: 5px 8px; text-align: left;
            font-size: 9px; font-weight: bold; color: #7a6940;
            text-transform: uppercase; letter-spacing: 0.5px;
            border: 1px solid #e8dfc8;
        }
        .data-table td { padding: 5px 8px; border: 1px solid #e8dfc8; color: #444444; }
        .data-table tbody tr:nth-child(even) { background-color: #fdfcfa; }

        .badge {
            display: inline-block; padding: 1px 8px; border-radius: 10px;
            font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-green { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .badge-red { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-amber { background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }

        .signature-area {
            margin-top: 30px; display: flex; justify-content: space-between;
        }
        .signature-box { text-align: center; width: 40%; }
        .signature-line { border-top: 1px solid #ccc; padding-top: 4px; margin-top: 30px; font-size: 10px; color: #888; }

        .footer {
            margin-top: 16px; padding-top: 8px; border-top: 2px solid #C4A265;
            text-align: center; font-size: 9px; color: #999999;
        }
        .footer-line { margin-bottom: 2px; }
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
        <div class="doc-title">General Medical Report</div>
        <div class="doc-title-ar" dir="rtl">{{ $ar('تقرير طبي عام') }}</div>
    </div>

    {{-- Patient Information --}}
    <div class="patient-info">
        <table class="patient-info-table">
            <tr>
                <td style="width: 30%;">
                    <div class="info-label">Patient Name / {{ $ar('اسم المريض') }}</div>
                    <div class="info-value">{{ $ar($patient->full_name ?? '-') }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value">{{ $patient->date_of_birth ? $patient->date_of_birth->format('d M Y') : '-' }}</div>
                </td>
                <td style="width: 15%;">
                    <div class="info-label">Age</div>
                    <div class="info-value">{{ $ageDisplay ?? '-' }}</div>
                </td>
                <td style="width: 15%;">
                    <div class="info-label">Gender</div>
                    <div class="info-value">{{ ucfirst($patient->gender ?? '-') }}</div>
                </td>
                <td style="width: 20%;">
                    <div class="info-label">File Number</div>
                    <div class="info-value gold">{{ $patient->file_number ?? '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="info-label">Guardian Name / {{ $ar('ولي الأمر') }}</div>
                    <div class="info-value">{{ $ar($patient->guardian_name ?? '-') }}</div>
                </td>
                <td>
                    <div class="info-label">Report Date</div>
                    <div class="info-value">{{ $reportDate ?? now()->format('d M Y') }}</div>
                </td>
                <td colspan="3">
                    <div class="info-label">Doctor</div>
                    <div class="info-value">{{ $ar($doctorName ?? '-') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Latest Growth --}}
    @if($latestGrowth)
    <div class="section">
        <div class="section-title">Growth Measurements / {{ $ar('القياسات') }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Measurement</th>
                    <th>Value</th>
                    <th>Percentile</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Weight / {{ $ar('الوزن') }}</td>
                    <td>{{ $latestGrowth->weight_kg ?? '-' }} kg</td>
                    <td>{{ $latestGrowth->weight_percentile !== null ? 'P'.$latestGrowth->weight_percentile : '-' }}</td>
                </tr>
                <tr>
                    <td>Height / {{ $ar('الطول') }}</td>
                    <td>{{ $latestGrowth->height_cm ?? '-' }} cm</td>
                    <td>{{ $latestGrowth->height_percentile !== null ? 'P'.$latestGrowth->height_percentile : '-' }}</td>
                </tr>
                <tr>
                    <td>Head Circumference / {{ $ar('محيط الرأس') }}</td>
                    <td>{{ $latestGrowth->head_circumference_cm ?? '-' }} cm</td>
                    <td>{{ $latestGrowth->head_percentile !== null ? 'P'.$latestGrowth->head_percentile : '-' }}</td>
                </tr>
                <tr>
                    <td>BMI</td>
                    <td>{{ $latestGrowth->bmi ?? '-' }}</td>
                    <td>{{ $latestGrowth->bmi_percentile !== null ? 'P'.$latestGrowth->bmi_percentile : '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    {{-- Active Allergies --}}
    @if($allergies->count())
    <div class="section">
        <div class="section-title">Active Allergies / {{ $ar('الحساسيات') }}</div>
        <table class="data-table">
            <thead>
                <tr><th>Allergen</th><th>Type</th><th>Severity</th></tr>
            </thead>
            <tbody>
                @foreach($allergies as $a)
                <tr>
                    <td>{{ $ar($a->allergen ?? '-') }}</td>
                    <td>{{ ucfirst($a->allergy_type ?? '-') }}</td>
                    <td>
                        @if($a->severity === 'severe' || $a->severity === 'anaphylaxis')
                            <span class="badge badge-red">{{ ucfirst($a->severity) }}</span>
                        @elseif($a->severity === 'moderate')
                            <span class="badge badge-amber">{{ ucfirst($a->severity) }}</span>
                        @else
                            <span class="badge badge-green">{{ ucfirst($a->severity ?? '-') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Chronic Conditions --}}
    @if($chronicConditions->count())
    <div class="section">
        <div class="section-title">Chronic Conditions / {{ $ar('الأمراض المزمنة') }}</div>
        <table class="data-table">
            <thead>
                <tr><th>Condition</th><th>Type</th><th>Severity</th></tr>
            </thead>
            <tbody>
                @foreach($chronicConditions as $c)
                <tr>
                    <td>{{ $ar($c->condition_name ?? '-') }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($c->condition_type ?? '-')) }}</td>
                    <td>{{ ucfirst($c->severity ?? '-') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Vaccination Summary --}}
    <div class="section">
        <div class="section-title">Vaccination Summary / {{ $ar('ملخص التطعيمات') }}</div>
        <div class="section-content">
            Given: {{ $vaccinationStats['given'] ?? 0 }} &middot;
            Scheduled: {{ $vaccinationStats['scheduled'] ?? 0 }} &middot;
            Missed: {{ $vaccinationStats['missed'] ?? 0 }} &middot;
            Total: {{ $vaccinationStats['total'] ?? 0 }}
        </div>
    </div>

    {{-- Additional Notes --}}
    @if(!empty($notes))
    <div class="section">
        <div class="section-title">Notes / {{ $ar('ملاحظات') }}</div>
        <div class="section-content">{{ $notes }}</div>
    </div>
    @endif

    {{-- Signature --}}
    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line">Doctor's Signature / {{ $ar('توقيع الطبيب') }}</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line">Clinic Stamp / {{ $ar('ختم العيادة') }}</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-line">{{ $clinicName ?? 'AURA Derma Clinic' }} &middot; Phone: {{ $clinicPhone ?? '-' }}</div>
        <div class="footer-line">Printed on: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
</body>
</html>
