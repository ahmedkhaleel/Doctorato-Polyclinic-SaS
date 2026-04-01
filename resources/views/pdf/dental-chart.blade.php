@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn($text) => ArabicPdfHelper::shape($text);

    $conditionColors = [
        'healthy' => '#22c55e',
        'decayed' => '#ef4444',
        'filled' => '#3b82f6',
        'missing' => '#6b7280',
        'crown' => '#f59e0b',
        'bridge' => '#8b5cf6',
        'implant' => '#06b6d4',
        'root_canal' => '#ec4899',
        'extracted' => '#1f2937',
    ];

    $conditionLabels = [
        'healthy' => 'Healthy',
        'decayed' => 'Decayed',
        'filled' => 'Filled',
        'missing' => 'Missing',
        'crown' => 'Crown',
        'bridge' => 'Bridge',
        'implant' => 'Implant',
        'root_canal' => 'Root Canal',
        'extracted' => 'Extracted',
    ];

    $conditionLabelsAr = [
        'healthy' => 'سليم',
        'decayed' => 'تسوس',
        'filled' => 'حشوة',
        'missing' => 'مفقود',
        'crown' => 'تاج',
        'bridge' => 'جسر',
        'implant' => 'زراعة',
        'root_canal' => 'علاج عصب',
        'extracted' => 'مخلوع',
    ];
@endphp
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Dental Chart - {{ $patient->full_name }}</title>
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
            font-size: 10px;
            line-height: 1.4;
        }

        .page { padding: 20px 30px; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .header-table td { vertical-align: top; padding: 0; }
        .clinic-name { font-size: 16px; font-weight: bold; color: #C4A265; }
        .clinic-name-ar { font-size: 12px; color: #666; }
        .doc-title { font-size: 13px; font-weight: bold; color: #06B6D4; margin-top: 3px; }

        .info-row { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-row td { padding: 4px 8px; }
        .info-label { color: #888; font-size: 9px; }
        .info-value { font-weight: bold; font-size: 11px; }

        .divider { border-top: 2px solid #06B6D4; margin: 10px 0; }

        /* Chart Grid */
        .chart-section { margin-bottom: 10px; }
        .chart-label { font-size: 9px; color: #888; font-weight: bold; margin-bottom: 3px; }
        .teeth-table { width: 100%; border-collapse: collapse; }
        .teeth-table td {
            text-align: center;
            padding: 4px 2px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
            font-weight: bold;
            width: 5.5%;
        }
        .tooth-number { font-size: 9px; color: #666; }

        /* Condition Legend */
        .legend-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .legend-table td { padding: 3px 6px; font-size: 9px; }
        .legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 4px; vertical-align: middle; }

        /* Summary Stats */
        .stats-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .stats-table td { padding: 5px 8px; border: 1px solid #e5e7eb; }
        .stat-label { font-size: 9px; color: #666; }
        .stat-value { font-size: 14px; font-weight: bold; color: #06B6D4; }

        /* Recent Treatments */
        .treatments-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .treatments-table th {
            background: #06B6D4;
            color: white;
            padding: 5px 6px;
            font-size: 9px;
            text-align: left;
            font-weight: bold;
        }
        .treatments-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 9px;
        }
        .treatments-table tr:nth-child(even) td { background: #f9fafb; }

        .footer {
            position: fixed;
            bottom: 15px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 8px;
            color: #aaa;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
<div class="page">
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="clinic-name">Aura Derma Clinic</div>
                <div class="clinic-name-ar" style="direction: rtl;">{{ $ar('عيادة أورا ديرما') }}</div>
                <div class="doc-title">Dental Chart / {{ $ar('مخطط الأسنان') }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div style="font-size: 9px; color: #888;">Date: {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Patient Info -->
    <table class="info-row" style="background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 4px;">
        <tr>
            <td style="width: 25%;">
                <div class="info-label">Patient / {{ $ar('المريض') }}</div>
                <div class="info-value">{{ $patient->full_name }}</div>
            </td>
            <td style="width: 20%;">
                <div class="info-label">File # / {{ $ar('رقم الملف') }}</div>
                <div class="info-value">{{ $patient->file_number }}</div>
            </td>
            <td style="width: 20%;">
                <div class="info-label">Phone / {{ $ar('الهاتف') }}</div>
                <div class="info-value">{{ $patient->phone }}</div>
            </td>
            <td style="width: 15%;">
                <div class="info-label">DOB</div>
                <div class="info-value">{{ $patient->date_of_birth?->format('d/m/Y') ?? '-' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="info-label">Gender / {{ $ar('الجنس') }}</div>
                <div class="info-value">{{ ucfirst($patient->gender ?? '-') }}</div>
            </td>
        </tr>
    </table>

    <!-- Medical Alerts -->
    @php $riskFlags = $patient->getDentalRiskFlags(); @endphp
    @if(count($riskFlags) > 0)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px;">
            <tr>
                <td style="padding: 6px 10px; font-size: 10px; font-weight: bold; color: #991b1b; width: 100px; vertical-align: top;">
                    ⚠ Medical Alerts
                </td>
                <td style="padding: 6px 10px; font-size: 9px;">
                    @foreach($riskFlags as $flag)
                        <span style="display: inline-block; padding: 1px 6px; margin: 1px 2px; border-radius: 8px; font-size: 8px; font-weight: bold;
                            {{ $flag['severity'] === 'high' ? 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;' : ($flag['severity'] === 'medium' ? 'background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;' : 'background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd;') }}">
                            {{ $flag['label_en'] }}
                        </span>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    @if($patient->allergies || $patient->chronic_conditions || $patient->current_medications)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px;">
            <tr>
                @if($patient->allergies)
                    <td style="padding: 5px 8px; font-size: 9px;">
                        <span style="color: #92400e; font-weight: bold;">Allergies:</span>
                        <span style="color: #78350f;">{{ $patient->allergies }}</span>
                    </td>
                @endif
                @if($patient->current_medications)
                    <td style="padding: 5px 8px; font-size: 9px;">
                        <span style="color: #92400e; font-weight: bold;">Medications:</span>
                        <span style="color: #78350f;">{{ $patient->current_medications }}</span>
                    </td>
                @endif
                @if($patient->blood_type)
                    <td style="padding: 5px 8px; font-size: 9px;">
                        <span style="color: #92400e; font-weight: bold;">Blood:</span>
                        <span style="color: #78350f;">{{ $patient->blood_type }}</span>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    <div class="divider"></div>

    <!-- Upper Jaw -->
    <div class="chart-section">
        <div class="chart-label">UPPER JAW / {{ $ar('الفك العلوي') }}</div>
        <table class="teeth-table">
            <tr>
                @foreach(array_merge($upperRight, $upperLeft) as $tooth)
                    <td class="tooth-number">{{ $tooth }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach(array_merge($upperRight, $upperLeft) as $tooth)
                    @php $entry = $chart->get($tooth); $condition = $entry?->condition ?? 'healthy'; @endphp
                    <td style="background: {{ $conditionColors[$condition] ?? '#22c55e' }}20; color: {{ $conditionColors[$condition] ?? '#22c55e' }}; font-size: 8px;">
                        {{ strtoupper(substr($condition, 0, 3)) }}
                    </td>
                @endforeach
            </tr>
        </table>
    </div>

    <!-- Lower Jaw -->
    <div class="chart-section">
        <table class="teeth-table">
            <tr>
                @foreach(array_merge($lowerRight, $lowerLeft) as $tooth)
                    @php $entry = $chart->get($tooth); $condition = $entry?->condition ?? 'healthy'; @endphp
                    <td style="background: {{ $conditionColors[$condition] ?? '#22c55e' }}20; color: {{ $conditionColors[$condition] ?? '#22c55e' }}; font-size: 8px;">
                        {{ strtoupper(substr($condition, 0, 3)) }}
                    </td>
                @endforeach
            </tr>
            <tr>
                @foreach(array_merge($lowerRight, $lowerLeft) as $tooth)
                    <td class="tooth-number">{{ $tooth }}</td>
                @endforeach
            </tr>
        </table>
        <div class="chart-label" style="margin-top: 3px;">LOWER JAW / {{ $ar('الفك السفلي') }}</div>
    </div>

    <!-- Legend -->
    <table class="legend-table">
        <tr>
            @foreach($conditionColors as $key => $color)
                <td>
                    <span class="legend-dot" style="background: {{ $color }};"></span>
                    {{ $conditionLabels[$key] }} / {{ $ar($conditionLabelsAr[$key]) }}
                </td>
            @endforeach
        </tr>
    </table>

    <!-- Summary Stats -->
    @php
        $conditionCounts = $chart->groupBy('condition')->map->count();
        $totalRecorded = $chart->count();
    @endphp
    <table class="stats-table">
        <tr>
            <td style="text-align: center; width: 14%;">
                <div class="stat-label">Total Recorded</div>
                <div class="stat-value">{{ $totalRecorded }}</div>
            </td>
            @foreach(['decayed' => '#ef4444', 'filled' => '#3b82f6', 'crown' => '#f59e0b', 'missing' => '#6b7280', 'implant' => '#06b6d4', 'root_canal' => '#ec4899'] as $cond => $clr)
                <td style="text-align: center; width: 14%;">
                    <div class="stat-label">{{ ucfirst(str_replace('_', ' ', $cond)) }}</div>
                    <div class="stat-value" style="color: {{ $clr }};">{{ $conditionCounts->get($cond, 0) }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <!-- Teeth with Notes -->
    @php $teethWithNotes = $chart->filter(fn($t) => $t->notes); @endphp
    @if($teethWithNotes->count() > 0)
        <div style="margin-top: 8px; font-weight: bold; font-size: 10px; color: #06B6D4;">Notes / {{ $ar('ملاحظات') }}</div>
        <table class="treatments-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Tooth</th>
                    <th style="width: 15%;">Condition</th>
                    <th style="width: 15%;">Surfaces</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teethWithNotes as $entry)
                    <tr>
                        <td style="font-weight: bold;">{{ $entry->tooth_number }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $entry->condition)) }}</td>
                        <td>{{ is_array($entry->surfaces) ? implode(', ', $entry->surfaces) : '-' }}</td>
                        <td>{{ $entry->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Recent Treatments -->
    @if($treatments->count() > 0)
        <div style="margin-top: 10px; font-weight: bold; font-size: 10px; color: #06B6D4;">Recent Treatments / {{ $ar('العلاجات الأخيرة') }}</div>
        <table class="treatments-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Tooth</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 15%;">Doctor</th>
                    <th style="width: 10%;">Cost</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;">Date</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($treatments as $t)
                    <tr>
                        <td style="font-weight: bold;">{{ $t->tooth_number ?? '-' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $t->treatment_type)) }}</td>
                        <td>{{ $t->doctor?->name_en ?? '-' }}</td>
                        <td>{{ number_format($t->cost + $t->lab_cost, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $t->status)) }}</td>
                        <td>{{ $t->created_at->format('d/m/Y') }}</td>
                        <td style="font-size: 8px;">{{ \Str::limit($t->notes, 30) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="footer">
    Aura Derma Clinic &bull; Dental Chart &bull; Generated {{ now()->format('d/m/Y H:i') }} &bull; Confidential
</div>
</body>
</html>
