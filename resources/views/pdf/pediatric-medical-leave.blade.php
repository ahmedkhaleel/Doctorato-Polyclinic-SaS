@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Medical Leave - {{ $patient->full_name ?? 'Patient' }}</title>
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
            color: #2d2d2d; font-size: 11px; line-height: 1.6; background: #ffffff;
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
        .doc-title { font-size: 16px; font-weight: bold; color: #333333; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; }
        .doc-title-ar { font-size: 14px; color: #666666; margin-top: -1px; }

        .patient-info {
            background-color: #faf7f0; border: 1px solid #e8dfc8;
            border-radius: 5px; padding: 10px 14px; margin-bottom: 16px;
        }
        .patient-info-table { width: 100%; border-collapse: collapse; }
        .patient-info-table td { padding: 3px 8px; vertical-align: top; }
        .info-label { color: #888888; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #333333; font-weight: bold; font-size: 11px; }
        .info-value.gold { color: #C4A265; font-family: monospace; }

        .leave-box {
            border: 2px solid #C4A265; border-radius: 8px;
            padding: 20px; margin-bottom: 16px; background: #fffdf8;
        }
        .leave-text {
            font-size: 13px; color: #333; line-height: 2.2; text-align: justify;
        }
        .leave-text .highlight {
            font-weight: bold; color: #C4A265;
            text-decoration: underline; text-decoration-color: #e8dfc8;
        }

        .leave-dates {
            background: #f5f0e5; border-radius: 6px;
            padding: 12px 16px; margin-top: 12px;
        }
        .leave-dates-table { width: 100%; border-collapse: collapse; }
        .leave-dates-table td { padding: 6px 8px; }
        .date-label { font-size: 10px; color: #888; text-transform: uppercase; }
        .date-value { font-size: 14px; font-weight: bold; color: #C4A265; }
        .days-count {
            font-size: 24px; font-weight: bold; color: #C4A265;
            text-align: center;
        }
        .days-label { font-size: 10px; color: #888; text-align: center; text-transform: uppercase; }

        .section { margin-bottom: 14px; }
        .section-title {
            font-size: 12px; font-weight: bold; color: #C4A265;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid #e8dfc8; padding-bottom: 3px; margin-bottom: 6px;
        }
        .section-content { font-size: 11px; color: #444; line-height: 1.7; padding-left: 4px; }

        .signature-line {
            border-top: 1px solid #ccc; padding-top: 4px;
            margin-top: 40px; font-size: 10px; color: #888; text-align: center;
        }

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
        <div class="doc-title">Medical Leave Certificate</div>
        <div class="doc-title-ar" dir="rtl">{{ $ar('شهادة إجازة مرضية') }}</div>
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
        </table>
    </div>

    {{-- Leave Certificate Body --}}
    <div class="leave-box">
        <p class="leave-text">
            This is to certify that <span class="highlight">{{ $patient->full_name ?? '-' }}</span>
            was examined at <span class="highlight">{{ $clinicName ?? 'AURA Derma Clinic' }}</span>
            and requires medical leave from
            <span class="highlight">{{ $leaveFrom ?? '-' }}</span>
            to <span class="highlight">{{ $leaveTo ?? '-' }}</span>
            (inclusive), a total of <span class="highlight">{{ $leaveDays ?? '-' }}</span> day(s).
        </p>
        <br>
        <p class="leave-text" dir="rtl" style="text-align: right;">
            {{ $ar('نشهد بأن المريض/ة') }} <span class="highlight">{{ $ar($patient->full_name ?? '-') }}</span>
            {{ $ar('قد تم فحصه/فحصها ويحتاج/تحتاج إلى إجازة مرضية من') }}
            <span class="highlight">{{ $leaveFrom ?? '-' }}</span>
            {{ $ar('إلى') }}
            <span class="highlight">{{ $leaveTo ?? '-' }}</span>
            {{ $ar('بإجمالي') }}
            <span class="highlight">{{ $leaveDays ?? '-' }}</span>
            {{ $ar('يوم/أيام.') }}
        </p>

        {{-- Leave Dates Summary --}}
        <div class="leave-dates">
            <table class="leave-dates-table">
                <tr>
                    <td style="width: 35%;">
                        <div class="date-label">From / {{ $ar('من') }}</div>
                        <div class="date-value">{{ $leaveFrom ?? '-' }}</div>
                    </td>
                    <td style="width: 35%;">
                        <div class="date-label">To / {{ $ar('إلى') }}</div>
                        <div class="date-value">{{ $leaveTo ?? '-' }}</div>
                    </td>
                    <td style="width: 30%;">
                        <div class="days-count">{{ $leaveDays ?? '-' }}</div>
                        <div class="days-label">Day(s) / {{ $ar('يوم') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Diagnosis --}}
    @if(!empty($diagnosis))
    <div class="section">
        <div class="section-title">Diagnosis / {{ $ar('التشخيص') }}</div>
        <div class="section-content">{{ $diagnosis }}</div>
    </div>
    @endif

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
