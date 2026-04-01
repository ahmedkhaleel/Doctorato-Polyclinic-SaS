@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
    $plan = $consent->treatmentPlan;
    $patient = $plan->patient;
    $doctor = $plan->doctor;
    $treatments = $plan->treatments ?? collect();
    $snapshot = $consent->consent_text_snapshot ?? [];
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Treatment Plan Consent - {{ $patient->full_name ?? 'Patient' }}</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Amiri', 'DejaVu Sans', sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.5;
        }

        .page { padding: 30px 40px; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; padding: 0; }

        .clinic-name { font-size: 18px; font-weight: bold; color: #C4A265; }
        .clinic-name-ar { font-size: 14px; color: #666; }
        .doc-title { font-size: 16px; font-weight: bold; color: #06B6D4; margin-top: 8px; }
        .doc-title-ar { font-size: 13px; color: #06B6D4; }

        .consent-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-signed { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-declined { background: #fee2e2; color: #991b1b; }

        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-grid td { padding: 6px 10px; vertical-align: top; }
        .info-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px 12px; }
        .info-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 12px; font-weight: bold; color: #333; margin-top: 2px; }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #06B6D4;
            margin: 15px 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #06B6D4;
        }

        .treatments-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .treatments-table th { background: #06B6D4; color: #fff; font-size: 10px; padding: 8px 6px; text-align: left; }
        .treatments-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        .treatments-table tr:nth-child(even) td { background: #f8fffe; }

        .consent-text {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            font-size: 11px;
            line-height: 1.8;
        }

        .consent-text-ar {
            direction: rtl;
            text-align: right;
            font-size: 12px;
        }

        .risks-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 12px;
            margin: 10px 0;
        }

        .signature-section {
            margin-top: 25px;
            border: 2px solid #06B6D4;
            border-radius: 8px;
            padding: 15px;
        }

        .signature-title {
            font-size: 12px;
            font-weight: bold;
            color: #06B6D4;
            margin-bottom: 10px;
        }

        .signature-details { font-size: 10px; color: #666; margin-top: 8px; }

        .summary-table { width: 250px; margin-left: auto; border-collapse: collapse; }
        .summary-table td { padding: 4px 8px; font-size: 11px; }
        .summary-table .total-row td { font-weight: bold; font-size: 13px; border-top: 2px solid #333; padding-top: 8px; }

        .footer { margin-top: 25px; padding-top: 12px; border-top: 1px solid #ddd; }
        .footer-text { font-size: 9px; color: #999; text-align: center; }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(6, 182, 212, 0.05);
            font-weight: bold;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">CONSENT</div>

    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="clinic-name">AURA DERMA</div>
                    <div class="clinic-name-ar">{{ $ar('عيادة أورا ديرما التجميلية') }}</div>
                    <div class="doc-title">Treatment Plan Consent</div>
                    <div class="doc-title-ar">{{ $ar('موافقة على خطة العلاج') }}</div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div style="font-size: 10px; color: #666;">
                        Date: {{ now()->format('d/m/Y') }}<br>
                        Consent #: {{ $consent->id }}<br>
                        Plan #: {{ $plan->id }}
                    </div>
                    <div style="margin-top: 8px;">
                        @if($consent->status === 'signed')
                            <span class="consent-badge badge-signed">SIGNED / {{ $ar('موقّع') }}</span>
                        @elseif($consent->status === 'pending')
                            <span class="consent-badge badge-pending">PENDING</span>
                        @elseif($consent->status === 'declined')
                            <span class="consent-badge badge-declined">DECLINED</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Patient & Doctor Info -->
        <table class="info-grid">
            <tr>
                <td style="width: 50%; padding-right: 5px;">
                    <div class="info-box">
                        <div class="info-label">Patient / {{ $ar('المريض') }}</div>
                        <div class="info-value">{{ $patient->full_name ?? '-' }}</div>
                        <div style="font-size: 10px; color: #666; margin-top: 3px;">
                            File #: {{ $patient->file_number ?? '-' }} &bull;
                            Phone: {{ $patient->phone ?? '-' }}
                        </div>
                    </div>
                </td>
                <td style="width: 50%; padding-left: 5px;">
                    <div class="info-box">
                        <div class="info-label">Doctor / {{ $ar('الطبيب') }}</div>
                        <div class="info-value">{{ $doctor->name_en ?? '-' }}</div>
                        <div style="font-size: 10px; color: #666; margin-top: 3px;">
                            {{ $doctor->name_ar ? $ar($doctor->name_ar) : '' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Medical Alerts & Conditions -->
        @php $riskFlags = $patient->getDentalRiskFlags(); @endphp
        @if(count($riskFlags) > 0 || $patient->allergies || $patient->current_medications || $patient->chronic_conditions)
            <div style="background: #fef2f2; border: 2px solid #fca5a5; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px;">
                <div style="font-size: 11px; font-weight: bold; color: #991b1b; margin-bottom: 6px;">
                    ⚠ Patient Medical Alerts / {{ $ar('تنبيهات طبية للمريض') }}
                </div>
                @if(count($riskFlags) > 0)
                    <div style="margin-bottom: 6px;">
                        @foreach($riskFlags as $flag)
                            <span style="display: inline-block; padding: 2px 8px; margin: 2px; border-radius: 8px; font-size: 9px; font-weight: bold;
                                {{ $flag['severity'] === 'high' ? 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : ($flag['severity'] === 'medium' ? 'background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;' : 'background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd;') }}">
                                {{ $flag['label_en'] }}
                            </span>
                        @endforeach
                    </div>
                @endif
                <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                    @if($patient->allergies)
                        <tr><td style="padding: 2px 0; color: #991b1b; font-weight: bold; width: 80px;">Allergies:</td><td style="padding: 2px 0; color: #78350f;">{{ $patient->allergies }}</td></tr>
                    @endif
                    @if($patient->chronic_conditions)
                        <tr><td style="padding: 2px 0; color: #92400e; font-weight: bold;">Chronic:</td><td style="padding: 2px 0; color: #78350f;">{{ $patient->chronic_conditions }}</td></tr>
                    @endif
                    @if($patient->current_medications)
                        <tr><td style="padding: 2px 0; color: #92400e; font-weight: bold;">Medications:</td><td style="padding: 2px 0; color: #78350f;">{{ $patient->current_medications }}</td></tr>
                    @endif
                    @if($patient->blood_type)
                        <tr><td style="padding: 2px 0; color: #666; font-weight: bold;">Blood Type:</td><td style="padding: 2px 0;">{{ $patient->blood_type }}</td></tr>
                    @endif
                </table>
            </div>
        @endif

        <!-- Plan Details -->
        <table class="info-grid">
            <tr>
                <td style="width: 50%; padding-right: 5px;">
                    <div class="info-box">
                        <div class="info-label">Plan Title</div>
                        <div class="info-value">{{ $plan->title_en ?: ($plan->title_ar ? $ar($plan->title_ar) : '-') }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding: 0 5px;">
                    <div class="info-box">
                        <div class="info-label">Estimated Cost</div>
                        <div class="info-value">{{ number_format((float) $plan->estimated_cost, 2) }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="info-box">
                        <div class="info-label">Sessions</div>
                        <div class="info-value">{{ $plan->estimated_sessions ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Treatments Table -->
        @if($treatments->count() > 0)
            <div class="section-title">Treatments / {{ $ar('العلاجات') }}</div>
            <table class="treatments-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Treatment Type</th>
                        <th>Tooth</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Lab Cost</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($treatments as $i => $treatment)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $treatment->treatment_type)) }}</td>
                            <td>{{ $treatment->tooth_number ?? '-' }}</td>
                            <td>{{ \Str::limit($treatment->description, 40) ?? '-' }}</td>
                            <td>{{ number_format((float) $treatment->cost, 2) }}</td>
                            <td>{{ number_format((float) $treatment->lab_cost, 2) }}</td>
                            <td style="font-weight: bold;">{{ number_format((float) $treatment->cost + (float) $treatment->lab_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Total -->
            @php
                $totalCost = $treatments->sum(fn($t) => (float) $t->cost + (float) $t->lab_cost);
            @endphp
            <table class="summary-table">
                <tr class="total-row">
                    <td>Total Cost:</td>
                    <td style="text-align: right;">{{ number_format($totalCost, 2) }}</td>
                </tr>
            </table>
        @endif

        <!-- Risks Notes -->
        @if($consent->risks_notes)
            <div class="risks-box">
                <div style="font-size: 11px; font-weight: bold; color: #92400e; margin-bottom: 5px;">
                    Risks & Notes / {{ $ar('المخاطر والملاحظات') }}
                </div>
                <div style="font-size: 10px; color: #78350f;">{{ $consent->risks_notes }}</div>
            </div>
        @endif

        <!-- Consent Text -->
        <div class="consent-text">
            <strong>Consent Declaration:</strong><br>
            I, the undersigned patient, acknowledge that I have read and understood the proposed treatment plan above,
            including the costs and procedures described. I consent to proceed with the treatment as outlined,
            and I understand that there are potential risks associated with any medical procedure.
            I have been given the opportunity to ask questions and have received satisfactory answers.
        </div>

        <div class="consent-text consent-text-ar">
            <strong>{{ $ar('إقرار الموافقة:') }}</strong><br>
            {{ $ar('أنا الموقّع أدناه، أقر بأنني قد قرأت وفهمت خطة العلاج المقترحة أعلاه بما في ذلك التكاليف والإجراءات المذكورة. أوافق على البدء بالعلاج وفقاً لهذه الخطة، وأفهم أن هناك مخاطر محتملة مرتبطة بأي إجراء طبي. لقد أتيحت لي الفرصة لطرح الأسئلة وتلقيت إجابات مرضية.') }}
        </div>

        <!-- Digital Signature -->
        @if($consent->status === 'signed')
            <div class="signature-section">
                <div class="signature-title">Digital Signature / {{ $ar('التوقيع الرقمي') }}</div>

                @if($consent->signature_image_path)
                    <div style="text-align: center; margin: 10px 0;">
                        <img src="{{ storage_path('app/public/' . $consent->signature_image_path) }}" style="max-height: 80px;" />
                    </div>
                @endif

                <div class="signature-details">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%;">
                                <strong>Signed By:</strong> {{ $patient->full_name ?? '-' }}<br>
                                <strong>Date:</strong> {{ $consent->signed_at ? $consent->signed_at->format('d/m/Y H:i') : '-' }}<br>
                                <strong>IP Address:</strong> {{ $consent->patient_ip ?? '-' }}
                            </td>
                            <td style="width: 50%; text-align: right;">
                                <strong>{{ $ar('الموقّع:') }}</strong> {{ $patient->full_name ?? '-' }}<br>
                                <strong>{{ $ar('التاريخ:') }}</strong> {{ $consent->signed_at ? $consent->signed_at->format('d/m/Y H:i') : '-' }}<br>
                                <strong>{{ $ar('رقم الملف:') }}</strong> {{ $patient->file_number ?? '-' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        @else
            <!-- Unsigned: Show signature lines -->
            <div style="margin-top: 40px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;">
                            <div style="border-top: 1px solid #333; width: 200px; margin-top: 40px; padding-top: 5px; font-size: 10px; color: #666;">
                                Doctor's Signature / {{ $ar('توقيع الطبيب') }}
                            </div>
                        </td>
                        <td style="width: 50%; text-align: right;">
                            <div style="border-top: 1px solid #333; width: 200px; margin-left: auto; margin-top: 40px; padding-top: 5px; font-size: 10px; color: #666;">
                                Patient's Signature / {{ $ar('توقيع المريض') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                AURA DERMA Aesthetic Clinic &bull; {{ $ar('عيادة أورا ديرما التجميلية') }}
                <br>This is an electronically signed document &bull; {{ $ar('هذا مستند موقّع إلكترونياً') }}
                <br>Generated on {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
