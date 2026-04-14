@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Referral Letter - {{ $patient->full_name ?? 'Patient' }}</title>
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

        .letter-body { margin-bottom: 16px; }
        .greeting { font-size: 13px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .letter-text { font-size: 12px; color: #444; line-height: 1.9; text-align: justify; margin-bottom: 10px; }

        .section { margin-bottom: 14px; }
        .section-title {
            font-size: 12px; font-weight: bold; color: #C4A265;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 1px solid #e8dfc8; padding-bottom: 3px; margin-bottom: 6px;
        }
        .section-content { font-size: 11px; color: #444; line-height: 1.7; padding-left: 4px; }

        .referral-box {
            border: 2px solid #C4A265; border-radius: 8px; padding: 12px 16px;
            margin-bottom: 16px; background: #fffdf8;
        }
        .referral-field { margin-bottom: 6px; }
        .referral-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .referral-value { font-size: 12px; color: #333; font-weight: bold; }

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
        <div class="doc-title">Referral Letter</div>
        <div class="doc-title-ar" dir="rtl">{{ $ar('خطاب تحويل طبي') }}</div>
    </div>

    {{-- Referral Details --}}
    <div class="referral-box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div class="referral-field">
                        <div class="referral-label">Referring Doctor / {{ $ar('الطبيب المحول') }}</div>
                        <div class="referral-value">{{ $ar($doctorName ?? '-') }}</div>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="referral-field">
                        <div class="referral-label">Referred To / {{ $ar('التحويل إلى') }}</div>
                        <div class="referral-value">{{ $ar($referredTo ?? 'Specialist') }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="referral-field">
                        <div class="referral-label">Date / {{ $ar('التاريخ') }}</div>
                        <div class="referral-value">{{ $reportDate ?? now()->format('d M Y') }}</div>
                    </div>
                </td>
                <td>
                    <div class="referral-field">
                        <div class="referral-label">Urgency / {{ $ar('الأولوية') }}</div>
                        <div class="referral-value">{{ ucfirst($urgency ?? 'Routine') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Patient Information --}}
    <div class="patient-info">
        <table class="patient-info-table">
            <tr>
                <td style="width: 30%;">
                    <div class="info-label">Patient Name</div>
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

    {{-- Letter Body --}}
    <div class="letter-body">
        <p class="greeting">Dear Colleague,</p>
        <p class="letter-text">
            I am referring the above patient to your care for further evaluation and management.
            Please find below the clinical summary and reason for referral.
        </p>
    </div>

    {{-- Reason for Referral --}}
    @if(!empty($reason))
    <div class="section">
        <div class="section-title">Reason for Referral / {{ $ar('سبب التحويل') }}</div>
        <div class="section-content">{{ $reason }}</div>
    </div>
    @endif

    {{-- Clinical Summary --}}
    @if(!empty($clinicalSummary))
    <div class="section">
        <div class="section-title">Clinical Summary / {{ $ar('الملخص السريري') }}</div>
        <div class="section-content">{{ $clinicalSummary }}</div>
    </div>
    @endif

    {{-- Active Allergies --}}
    @if($allergies->count())
    <div class="section">
        <div class="section-title">Known Allergies</div>
        <div class="section-content">
            @foreach($allergies as $a)
                {{ $a->allergen }} ({{ ucfirst($a->severity ?? '-') }}){{ !$loop->last ? ', ' : '' }}
            @endforeach
        </div>
    </div>
    @endif

    {{-- Additional Notes --}}
    @if(!empty($notes))
    <div class="section">
        <div class="section-title">Additional Notes / {{ $ar('ملاحظات إضافية') }}</div>
        <div class="section-content">{{ $notes }}</div>
    </div>
    @endif

    {{-- Signature --}}
    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line">Referring Doctor / {{ $ar('الطبيب المحول') }}</div>
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
