@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);
    $patient = $plan->patient;
    $doctor  = $plan->doctor;
    $treatments = $plan->treatments ?? collect();
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dental Treatment Plan - {{ $patient->full_name ?? 'Patient' }}</title>
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

        /* Header */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; padding: 0; }

        .clinic-name { font-size: 18px; font-weight: bold; color: #C4A265; }
        .clinic-name-ar { font-size: 14px; color: #666; }
        .doc-title { font-size: 14px; font-weight: bold; color: #06B6D4; margin-top: 5px; }
        .doc-title-ar { font-size: 12px; color: #06B6D4; }

        /* Info boxes */
        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-grid td { padding: 6px 10px; vertical-align: top; }
        .info-box { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 10px 12px; }
        .info-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 12px; font-weight: bold; color: #333; margin-top: 2px; }

        /* Section */
        .section-title { font-size: 13px; font-weight: bold; color: #06B6D4; margin: 15px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #06B6D4; }

        /* Table */
        .treatments-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .treatments-table th { background: #06B6D4; color: #fff; font-size: 10px; padding: 8px 6px; text-align: left; }
        .treatments-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        .treatments-table tr:nth-child(even) td { background: #f8fffe; }

        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .status-planned { background: #dbeafe; color: #1e40af; }
        .status-in_progress { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Progress */
        .progress-bar { width: 100%; height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden; margin: 5px 0; }
        .progress-fill { height: 100%; background: #06B6D4; border-radius: 6px; }

        /* Summary */
        .summary-table { width: 250px; margin-left: auto; border-collapse: collapse; }
        .summary-table td { padding: 4px 8px; font-size: 11px; }
        .summary-table .total-row td { font-weight: bold; font-size: 13px; border-top: 2px solid #333; padding-top: 8px; }

        /* Footer */
        .footer { margin-top: 25px; padding-top: 12px; border-top: 1px solid #ddd; }
        .footer-text { font-size: 9px; color: #999; text-align: center; }

        /* Signature area */
        .signature-area { margin-top: 40px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin-top: 40px; padding-top: 5px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="page">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="clinic-name">AURA DERMA</div>
                    <div class="clinic-name-ar">{{ $ar('عيادة أورا ديرما التجميلية') }}</div>
                    <div class="doc-title">Dental Treatment Plan</div>
                    <div class="doc-title-ar">{{ $ar('خطة علاج الأسنان') }}</div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div style="font-size: 10px; color: #666;">
                        Date: {{ now()->format('d/m/Y') }}<br>
                        Plan #: {{ $plan->id }}<br>
                        Status: {{ ucfirst(str_replace('_', ' ', $plan->status)) }}
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

        <!-- Medical Alerts -->
        @php $riskFlags = $patient->getDentalRiskFlags(); @endphp
        @if(count($riskFlags) > 0 || $patient->allergies || $patient->current_medications)
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px;">
                @if(count($riskFlags) > 0)
                <tr>
                    <td style="padding: 8px 12px; font-size: 9px;">
                        <span style="font-weight: bold; color: #991b1b; font-size: 10px;">⚠ Medical Alerts / {{ $ar('تنبيهات طبية') }}</span><br>
                        @foreach($riskFlags as $flag)
                            <span style="display: inline-block; padding: 2px 8px; margin: 2px; border-radius: 8px; font-size: 8px; font-weight: bold;
                                {{ $flag['severity'] === 'high' ? 'background: #fee2e2; color: #991b1b;' : ($flag['severity'] === 'medium' ? 'background: #fef3c7; color: #92400e;' : 'background: #dbeafe; color: #1e40af;') }}">
                                {{ $flag['label_en'] }}
                            </span>
                        @endforeach
                    </td>
                </tr>
                @endif
                @if($patient->allergies || $patient->current_medications)
                <tr>
                    <td style="padding: 4px 12px 8px; font-size: 9px;">
                        @if($patient->allergies)
                            <span style="color: #991b1b; font-weight: bold;">Allergies:</span> {{ $patient->allergies }} &nbsp;
                        @endif
                        @if($patient->current_medications)
                            <span style="color: #92400e; font-weight: bold;">Medications:</span> {{ $patient->current_medications }}
                        @endif
                    </td>
                </tr>
                @endif
            </table>
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
                        <div class="info-label">Start Date</div>
                        <div class="info-value">{{ $plan->start_date ?? '-' }}</div>
                    </div>
                </td>
                <td style="width: 25%; padding-left: 5px;">
                    <div class="info-box">
                        <div class="info-label">Expected End</div>
                        <div class="info-value">{{ $plan->expected_end_date ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Progress -->
        @php
            $progressPct = ($plan->estimated_sessions > 0)
                ? min(100, round(($plan->completed_sessions / $plan->estimated_sessions) * 100))
                : 0;
        @endphp
        <div style="margin-bottom: 15px;">
            <div style="font-size: 10px; color: #666; margin-bottom: 3px;">
                Progress: {{ $plan->completed_sessions ?? 0 }} / {{ $plan->estimated_sessions ?? '-' }} sessions ({{ $progressPct }}%)
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $progressPct }}%;"></div>
            </div>
        </div>

        @if($plan->description)
            <div style="background: #f8f9fa; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 10px; color: #555;">
                <strong>Notes:</strong> {{ $plan->description }}
            </div>
        @endif

        <!-- Treatments Table -->
        <div class="section-title">Treatments / {{ $ar('العلاجات') }}</div>

        @if($treatments->count() > 0)
            <table class="treatments-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Treatment Type</th>
                        <th>Tooth</th>
                        <th>Surfaces</th>
                        <th>Description</th>
                        <th>Cost</th>
                        <th>Lab Cost</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($treatments as $i => $treatment)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $treatment->treatment_type)) }}</td>
                            <td>{{ $treatment->tooth_number ?? '-' }}</td>
                            <td>{{ is_array($treatment->surfaces) ? implode(', ', $treatment->surfaces) : '-' }}</td>
                            <td>{{ \Str::limit($treatment->description, 40) ?? '-' }}</td>
                            <td>{{ number_format((float) $treatment->cost, 2) }}</td>
                            <td>{{ number_format((float) $treatment->lab_cost, 2) }}</td>
                            <td style="font-weight: bold;">{{ number_format((float) $treatment->cost + (float) $treatment->lab_cost, 2) }}</td>
                            <td>
                                <span class="status-badge status-{{ $treatment->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $treatment->status)) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #999; font-size: 11px; text-align: center; padding: 20px;">No treatments added to this plan yet.</p>
        @endif

        <!-- Cost Summary -->
        <table class="summary-table">
            <tr>
                <td style="color: #666;">Estimated Cost:</td>
                <td style="text-align: right;">{{ number_format((float) $plan->estimated_cost, 2) }} SAR</td>
            </tr>
            <tr>
                <td style="color: #666;">Actual Cost:</td>
                <td style="text-align: right;">{{ number_format((float) $plan->actual_cost, 2) }} SAR</td>
            </tr>
            <tr class="total-row">
                <td>Remaining:</td>
                <td style="text-align: right;">{{ number_format(max(0, (float) $plan->estimated_cost - (float) $plan->actual_cost), 2) }} SAR</td>
            </tr>
        </table>

        <!-- Signature -->
        <div class="signature-area">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;">
                        <div class="signature-line">
                            Doctor's Signature / {{ $ar('توقيع الطبيب') }}
                        </div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="signature-line" style="margin-left: auto;">
                            Patient's Signature / {{ $ar('توقيع المريض') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                AURA DERMA Aesthetic Clinic &bull; {{ $ar('عيادة أورا ديرما التجميلية') }}
                <br>Generated on {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</body>
</html>
