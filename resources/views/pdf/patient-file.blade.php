@php
    use Carbon\Carbon;

    $sensitive = $canViewSensitive ?? false;
    $isRtl = app()->getLocale() === 'ar';

    $fmtDate = function ($d) {
        if (!$d) return '—';
        try {
            return Carbon::parse($d)->format('d M Y');
        } catch (\Throwable) {
            return (string) $d;
        }
    };

    $specialtyLabelAr = [
        'derma' => 'الجلدية',
        'dental' => 'الأسنان',
        'pediatric' => 'طب الأطفال',
    ];
    $specialtyLabelEn = [
        'derma' => 'Dermatology',
        'dental' => 'Dental',
        'pediatric' => 'Pediatric',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>Patient File - {{ $patient->full_name }}</title>
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
            margin: 14mm 12mm 16mm 12mm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Amiri', Arial, sans-serif;
            color: #2d2d2d;
            font-size: 10px;
            line-height: 1.5;
        }

        /* Header */
        .clinic-header {
            border-bottom: 3px solid #C4A265;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .clinic-header table { width: 100%; border-collapse: collapse; }
        .clinic-header td { vertical-align: middle; }
        .clinic-logo {
            display: inline-block;
            width: 48px; height: 48px;
            border-radius: 50%;
            background-color: #C4A265;
            color: #fff;
            text-align: center;
            line-height: 48px;
            font-size: 22px;
            font-weight: bold;
        }
        .clinic-name { font-size: 18px; font-weight: bold; color: #C4A265; letter-spacing: 2px; }
        .clinic-name-ar { font-size: 13px; color: #9a8254; }
        .doc-title { font-size: 14px; font-weight: bold; color: #333; margin-top: 2px; text-transform: uppercase; letter-spacing: 2px; }
        .doc-title-ar { font-size: 12px; color: #666; }

        /* Sections */
        .section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #C4A265;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 4px 8px;
            background-color: #faf7f0;
            border-left: 3px solid #C4A265;
            border-radius: 2px;
            margin-bottom: 6px;
        }

        /* Patient info card */
        .patient-card {
            background-color: #faf7f0;
            border: 1px solid #e8dfc8;
            border-radius: 5px;
            padding: 10px 14px;
        }
        .patient-card table { width: 100%; border-collapse: collapse; }
        .patient-card td { padding: 3px 8px; vertical-align: top; }
        .info-label {
            color: #888;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }
        .info-value { color: #333; font-weight: bold; font-size: 10.5px; }

        /* Alerts */
        .alert-box {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-left: 4px solid #ea580c;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 5px;
        }
        .alert-box.high {
            background-color: #fef2f2;
            border-color: #fecaca;
            border-left-color: #dc2626;
        }
        .alert-title { font-weight: bold; font-size: 10px; color: #991b1b; }
        .alert-content { font-size: 10px; color: #555; margin-top: 2px; }

        /* Data tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 6px;
        }
        table.data-table thead { background-color: #C4A265; }
        table.data-table thead th {
            padding: 5px 6px;
            color: #fff;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border: 1px solid #b3924d;
        }
        table.data-table tbody td {
            padding: 5px 6px;
            border: 1px solid #e6e1d4;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) { background-color: #faf8f3; }

        /* Specialty badge */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
            margin-right: 3px;
        }
        .badge-derma { background-color: #C4A265; }
        .badge-dental { background-color: #06b6d4; }
        .badge-pediatric { background-color: #10b981; }

        /* Financial summary */
        .fin-summary { width: 100%; border-collapse: collapse; }
        .fin-card {
            width: 25%;
            padding: 8px;
            text-align: center;
            border: 1px solid #e8e0d0;
            background-color: #faf8f3;
            border-radius: 3px;
        }
        .fin-label { font-size: 8.5px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
        .fin-value { font-size: 13px; font-weight: bold; color: #C4A265; margin-top: 2px; }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5px;
            color: #999;
            padding: 4px 0;
            border-top: 1px solid #eee;
        }

        .restricted {
            color: #991b1b;
            font-style: italic;
            font-size: 9px;
        }

        .no-data { color: #aaa; font-style: italic; font-size: 9.5px; }

        /* Page breaks */
        .page-break { page-break-before: always; }

        .two-col { width: 100%; border-collapse: collapse; }
        .two-col > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 4px; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="clinic-header">
        <table>
            <tr>
                <td style="width: 60px;">
                    <div class="clinic-logo">D</div>
                </td>
                <td>
                    <div class="clinic-name">DOCTORATO</div>
                    <div class="clinic-name-ar">دكتوراتو</div>
                </td>
                <td style="text-align: right;">
                    <div class="doc-title">Complete Patient File</div>
                    <div class="doc-title-ar">ملف المريض الكامل</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- PATIENT INFO -->
    <div class="section">
        <div class="section-title">Patient Information · معلومات المريض</div>
        <div class="patient-card">
            <table>
                <tr>
                    <td style="width: 33%;">
                        <span class="info-label">Full Name · الاسم</span>
                        <span class="info-value">{{ $patient->full_name }}</span>
                    </td>
                    <td style="width: 33%;">
                        <span class="info-label">File Number · رقم الملف</span>
                        <span class="info-value" style="color: #C4A265;">{{ $patient->file_number ?? '—' }}</span>
                    </td>
                    <td style="width: 33%;">
                        <span class="info-label">Gender · الجنس</span>
                        <span class="info-value">{{ $patient->gender ?? '—' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Date of Birth · تاريخ الميلاد</span>
                        <span class="info-value">{{ $fmtDate($patient->date_of_birth) }}</span>
                    </td>
                    <td>
                        <span class="info-label">Age · العمر</span>
                        <span class="info-value">{{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}</span>
                    </td>
                    <td>
                        <span class="info-label">Blood Type · فصيلة الدم</span>
                        <span class="info-value">{{ $patient->blood_type ?? '—' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Phone · الهاتف</span>
                        <span class="info-value">{{ $patient->phone ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="info-label">Email · البريد</span>
                        <span class="info-value">{{ $patient->email ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="info-label">Nationality · الجنسية</span>
                        <span class="info-value">{{ $patient->nationality ?? '—' }}</span>
                    </td>
                </tr>
                @if($patient->address)
                <tr>
                    <td colspan="3">
                        <span class="info-label">Address · العنوان</span>
                        <span class="info-value">{{ $patient->address }}</span>
                    </td>
                </tr>
                @endif
                @if($patient->emergency_contact_name || $patient->emergency_contact_phone)
                <tr>
                    <td colspan="3">
                        <span class="info-label">Emergency Contact · اتصال طوارئ</span>
                        <span class="info-value">
                            {{ $patient->emergency_contact_name ?? '—' }}
                            @if($patient->emergency_contact_phone) · {{ $patient->emergency_contact_phone }} @endif
                        </span>
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- ACTIVE SPECIALTIES -->
    @if(!empty($activeSpecialties))
    <div class="section">
        <div class="section-title">Active Specialties · التخصصات النشطة</div>
        <div style="padding: 4px 8px;">
            @foreach($activeSpecialties as $sp)
                <span class="badge badge-{{ $sp }}">{{ $specialtyLabelEn[$sp] ?? $sp }} · {{ $specialtyLabelAr[$sp] ?? '' }}</span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- MEDICAL ALERTS -->
    <div class="section">
        <div class="section-title">Medical Alerts · تنبيهات طبية</div>
        @php $hasAlerts = false; @endphp

        @if($patient->allergies)
            @php $hasAlerts = true; @endphp
            <div class="alert-box high">
                <div class="alert-title">Allergies · حساسية</div>
                <div class="alert-content">{{ $patient->allergies }}</div>
            </div>
        @endif

        @if($patient->chronic_conditions)
            @php $hasAlerts = true; @endphp
            <div class="alert-box">
                <div class="alert-title">Chronic Conditions · أمراض مزمنة</div>
                <div class="alert-content">{{ $patient->chronic_conditions }}</div>
            </div>
        @endif

        @if($patient->current_medications)
            @php $hasAlerts = true; @endphp
            <div class="alert-box">
                <div class="alert-title">Current Medications · أدوية حالية</div>
                <div class="alert-content">{{ $patient->current_medications }}</div>
            </div>
        @endif

        @if($patient->has_bleeding_disorder || $patient->takes_blood_thinners)
            @php $hasAlerts = true; @endphp
            <div class="alert-box high">
                <div class="alert-title">Bleeding Risk · خطر نزيف</div>
                <div class="alert-content">
                    @if($patient->has_bleeding_disorder) Has bleeding disorder. @endif
                    @if($patient->takes_blood_thinners) Takes blood thinners{{ $patient->blood_thinner_name ? ' (' . $patient->blood_thinner_name . ')' : '' }}. @endif
                </div>
            </div>
        @endif

        @if($patient->has_heart_condition)
            @php $hasAlerts = true; @endphp
            <div class="alert-box high">
                <div class="alert-title">Heart Condition · مشاكل قلبية</div>
            </div>
        @endif

        @if($patient->has_diabetes)
            @php $hasAlerts = true; @endphp
            <div class="alert-box">
                <div class="alert-title">Diabetes · سكري</div>
                <div class="alert-content">{{ $patient->diabetes_type ?? '' }}</div>
            </div>
        @endif

        @if($patient->is_pregnant || $patient->is_breastfeeding)
            @php $hasAlerts = true; @endphp
            <div class="alert-box">
                <div class="alert-title">
                    @if($patient->is_pregnant) Pregnant · حامل @endif
                    @if($patient->is_breastfeeding) · Breastfeeding · مرضعة @endif
                </div>
            </div>
        @endif

        @if($patient->has_hepatitis || $patient->has_hiv)
            @php $hasAlerts = true; @endphp
            <div class="alert-box high">
                <div class="alert-title">Infectious Disease · أمراض معدية</div>
                <div class="alert-content">
                    @if($sensitive)
                        @if($patient->has_hepatitis) Hepatitis {{ $patient->hepatitis_type ?? '' }}. @endif
                        @if($patient->has_hiv) HIV positive. @endif
                    @else
                        <span class="restricted">[RESTRICTED — requires patients.view_sensitive_medical permission]</span>
                    @endif
                </div>
            </div>
        @endif

        @if(!$hasAlerts)
            <p class="no-data">No medical alerts recorded.</p>
        @endif
    </div>

    <!-- FINANCIAL SUMMARY -->
    <div class="section">
        <div class="section-title">Financial Summary · ملخص مالي</div>
        <table class="fin-summary">
            <tr>
                <td class="fin-card">
                    <div class="fin-label">Total Visits</div>
                    <div class="fin-value">{{ $financialSummary['visit_count'] }}</div>
                </td>
                <td class="fin-card">
                    <div class="fin-label">Total Invoiced</div>
                    <div class="fin-value">{{ number_format($financialSummary['total_invoiced'], 2) }}</div>
                </td>
                <td class="fin-card">
                    <div class="fin-label">Total Paid</div>
                    <div class="fin-value" style="color: #059669;">{{ number_format($financialSummary['total_paid'], 2) }}</div>
                </td>
                <td class="fin-card">
                    <div class="fin-label">Outstanding</div>
                    <div class="fin-value" style="color: {{ $financialSummary['outstanding'] > 0 ? '#dc2626' : '#059669' }};">{{ number_format($financialSummary['outstanding'], 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- VISITS TIMELINE -->
    <div class="section">
        <div class="section-title">Visits Timeline · الزيارات</div>
        @if($patient->visits->isEmpty())
            <p class="no-data">No visits recorded.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 10%;">Module</th>
                        <th style="width: 18%;">Doctor</th>
                        <th style="width: 20%;">Service</th>
                        <th style="width: 10%;">Status</th>
                        <th>Diagnosis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->visits as $visit)
                        <tr>
                            <td>{{ $fmtDate($visit->visit_date ?? $visit->created_at) }}</td>
                            <td>{{ $visit->module ?? '—' }}</td>
                            <td>{{ $visit->doctor?->name_en ?? $visit->doctor?->name_ar ?? '—' }}</td>
                            <td>{{ $visit->service?->name_en ?? $visit->service?->name_ar ?? $visit->visit_type ?? '—' }}</td>
                            <td>{{ $visit->status ?? '—' }}</td>
                            <td>{{ $visit->diagnosis ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- DENTAL SECTION -->
    @if(in_array('dental', $activeSpecialties))
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">Dental Records · سجلات الأسنان</div>

        <h4 style="font-size: 10px; font-weight: bold; color: #0e7490; margin-bottom: 4px;">Treatments</h4>
        @if($patient->dentalTreatments->isEmpty())
            <p class="no-data">No dental treatments recorded.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 8%;">Tooth</th>
                        <th style="width: 18%;">Procedure</th>
                        <th style="width: 18%;">Doctor</th>
                        <th style="width: 10%;">Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->dentalTreatments as $t)
                        <tr>
                            <td>{{ $fmtDate($t->treatment_date ?? $t->created_at) }}</td>
                            <td>{{ $t->tooth_number ?? '—' }}</td>
                            <td>{{ $t->procedure_name ?? $t->procedure ?? '—' }}</td>
                            <td>{{ $t->doctor?->name_en ?? $t->doctor?->name_ar ?? '—' }}</td>
                            <td>{{ $t->status ?? '—' }}</td>
                            <td>{{ $t->treatment_description ?? $t->description ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($patient->dentalXrays->isNotEmpty())
            <h4 style="font-size: 10px; font-weight: bold; color: #0e7490; margin: 10px 0 4px 0;">X-Rays</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 20%;">Type</th>
                        <th style="width: 25%;">Doctor</th>
                        <th>Findings</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->dentalXrays as $x)
                        <tr>
                            <td>{{ $fmtDate($x->taken_date ?? $x->created_at) }}</td>
                            <td>{{ $x->xray_type ?? '—' }}</td>
                            <td>{{ $x->doctor?->name_en ?? '—' }}</td>
                            <td>{{ $x->findings ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @endif

    <!-- PEDIATRIC SECTION -->
    @if(in_array('pediatric', $activeSpecialties))
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">Pediatric Records · سجلات طب الأطفال</div>

        @if($patient->guardian_name)
            <p style="margin-bottom: 6px; font-size: 10px;">
                <strong>Guardian:</strong> {{ $patient->guardian_name }}
                @if($patient->guardian_relation) ({{ $patient->guardian_relation }}) @endif
                @if($patient->guardian_phone) · {{ $patient->guardian_phone }} @endif
            </p>
        @endif

        @if($patient->pediatricAllergies->isNotEmpty())
            <h4 style="font-size: 10px; font-weight: bold; color: #047857; margin-bottom: 4px;">Allergies</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Allergen</th>
                        <th style="width: 20%;">Severity</th>
                        <th>Reaction</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->pediatricAllergies as $a)
                        <tr>
                            <td>{{ $a->allergen ?? $a->name ?? '—' }}</td>
                            <td>{{ $a->severity ?? '—' }}</td>
                            <td>{{ $a->reaction ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($patient->pediatricGrowthRecords->isNotEmpty())
            <h4 style="font-size: 10px; font-weight: bold; color: #047857; margin: 10px 0 4px 0;">Growth Measurements</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Date</th>
                        <th style="width: 12%;">Age (mo)</th>
                        <th style="width: 14%;">Weight (kg)</th>
                        <th style="width: 14%;">Height (cm)</th>
                        <th style="width: 14%;">Head (cm)</th>
                        <th style="width: 10%;">BMI</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->pediatricGrowthRecords as $g)
                        <tr>
                            <td>{{ $fmtDate($g->measurement_date) }}</td>
                            <td>{{ $g->age_months ?? '—' }}</td>
                            <td>{{ $g->weight_kg ?? '—' }}</td>
                            <td>{{ $g->height_cm ?? '—' }}</td>
                            <td>{{ $g->head_circumference_cm ?? '—' }}</td>
                            <td>{{ $g->bmi ?? '—' }}</td>
                            <td>{{ $g->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($patient->pediatricVaccinations->isNotEmpty())
            <h4 style="font-size: 10px; font-weight: bold; color: #047857; margin: 10px 0 4px 0;">Vaccinations</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Vaccine</th>
                        <th style="width: 15%;">Dose</th>
                        <th style="width: 15%;">Scheduled</th>
                        <th style="width: 15%;">Given</th>
                        <th style="width: 15%;">Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->pediatricVaccinations as $v)
                        <tr>
                            <td>{{ $v->vaccine_name ?? $v->vaccine ?? '—' }}</td>
                            <td>{{ $v->dose ?? '—' }}</td>
                            <td>{{ $fmtDate($v->scheduled_date) }}</td>
                            <td>{{ $fmtDate($v->given_date) }}</td>
                            <td>{{ $v->status ?? '—' }}</td>
                            <td>{{ $v->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @endif

    <!-- DERMA SECTION (basic) -->
    @if(in_array('derma', $activeSpecialties))
    <div class="section">
        <div class="section-title">Dermatology · الجلدية</div>
        @php
            $dermaVisits = $patient->visits->where('module', 'derma');
            $dermaDiagnoses = $dermaVisits->pluck('diagnosis')->filter()->unique()->take(10);
        @endphp
        @if($dermaDiagnoses->isNotEmpty())
            <h4 style="font-size: 10px; font-weight: bold; color: #9a7d3e; margin-bottom: 4px;">Recent Diagnoses</h4>
            <ul style="padding-left: 20px; font-size: 9.5px; margin-bottom: 6px;">
                @foreach($dermaDiagnoses as $d)
                    <li>{{ $d }}</li>
                @endforeach
            </ul>
        @else
            <p class="no-data">No dermatology diagnoses recorded.</p>
        @endif
    </div>
    @endif

    <!-- PRESCRIPTIONS -->
    <div class="page-break"></div>
    <div class="section">
        <div class="section-title">Prescriptions · الوصفات الطبية</div>
        @if($patient->prescriptions->isEmpty())
            <p class="no-data">No prescriptions recorded.</p>
        @else
            @foreach($patient->prescriptions as $p)
                <div style="border: 1px solid #e5d8bd; background: #fdfbf5; border-radius: 4px; padding: 6px 10px; margin-bottom: 6px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="font-size: 10px;">
                                <strong>{{ $fmtDate($p->created_at) }}</strong>
                                @if($p->doctor) · {{ $p->doctor->name_en ?? $p->doctor->name_ar }}@endif
                            </td>
                            <td style="text-align: right; font-size: 9px; color: #999;">#{{ $p->id }}</td>
                        </tr>
                    </table>
                    @if($p->diagnosis)
                        <div style="font-size: 9.5px; color: #555; margin-top: 3px;"><em>Dx: {{ $p->diagnosis }}</em></div>
                    @endif
                    @if(isset($p->items) && $p->items->isNotEmpty())
                        <ul style="padding-left: 18px; margin-top: 4px; font-size: 9.5px;">
                            @foreach($p->items as $item)
                                <li>
                                    <strong>{{ $item->medication_name ?? $item->medicine_name ?? $item->name ?? 'Item' }}</strong>
                                    @if($item->dosage) — {{ $item->dosage }}@endif
                                    @if($item->instructions) — {{ $item->instructions }}@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <!-- INVOICES -->
    <div class="section">
        <div class="section-title">Invoices · الفواتير</div>
        @if($patient->invoices->isEmpty())
            <p class="no-data">No invoices recorded.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">Date</th>
                        <th style="width: 14%;">Invoice #</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;">Total</th>
                        <th style="width: 15%;">Paid</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patient->invoices as $inv)
                        @php
                            $balance = ((float) $inv->total) - ((float) ($inv->paid_amount ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $fmtDate($inv->invoice_date ?? $inv->created_at) }}</td>
                            <td>{{ $inv->invoice_number ?? $inv->id }}</td>
                            <td>{{ $inv->status ?? '—' }}</td>
                            <td>{{ number_format((float) $inv->total, 2) }}</td>
                            <td>{{ number_format((float) ($inv->paid_amount ?? 0), 2) }}</td>
                            <td>{{ number_format($balance, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        Generated on {{ $exportedAt->format('d M Y H:i') }} by {{ $exportedBy }} · DOCTORATO Patient File
    </div>

</body>
</html>
