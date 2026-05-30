@php
    use App\Helpers\ArabicPdfHelper;
    $ar = fn ($text) => ArabicPdfHelper::shape($text);
    $p = $pregnancy;
    $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Antenatal Card - {{ $p->patient->full_name ?? 'Patient' }}</title>
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
        @page { size: A4 portrait; margin: 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Amiri', Arial, Helvetica, sans-serif; color: #2d2d2d; font-size: 12px; }

        .header { border-bottom: 3px solid #1B365D; padding-bottom: 10px; margin-bottom: 16px; }
        .header .clinic { font-size: 18px; font-weight: bold; color: #1B365D; }
        .header .title { float: right; text-align: right; }
        .header .title .t1 { font-size: 16px; font-weight: bold; color: #DB2777; }
        .header .title .t2 { font-size: 10px; color: #777; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .grid { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .grid td { padding: 5px 8px; border: 1px solid #e5e7eb; font-size: 11px; }
        .grid td.label { background: #f8fafc; color: #555; width: 22%; font-weight: bold; }

        .section-title { background: #1B365D; color: #fff; padding: 6px 10px; font-size: 12px; font-weight: bold; margin: 14px 0 6px; }
        .accent { color: #DB2777; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #C4A265; color: #fff; padding: 6px; font-size: 10px; text-align: left; }
        table.data td { padding: 5px 6px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.data tr:nth-child(even) td { background: #faf7f2; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: bold; }
        .badge.risk { background: #fde2e2; color: #c0392b; }
        .footer { margin-top: 22px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #999; text-align: center; }
        .empty { color: #999; font-style: italic; padding: 6px; }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div class="title">
            <div class="t1">Antenatal Card</div>
            <div class="t2">{{ $fmt(now()) }}</div>
        </div>
        <div class="clinic">{{ $ar($clinicName) }}</div>
        <div style="font-size:10px;color:#777;">Obstetrics &amp; Gynecology @if($clinicPhone) &middot; {{ $clinicPhone }} @endif</div>
    </div>

    {{-- Patient + pregnancy summary --}}
    <table class="grid">
        <tr>
            <td class="label">Patient</td>
            <td>{{ $ar($p->patient->full_name ?? '') }}</td>
            <td class="label">File No.</td>
            <td>{{ $p->patient->file_number ?? $p->patient_id }}</td>
        </tr>
        <tr>
            <td class="label">Phone</td>
            <td>{{ $p->patient->phone ?? '—' }}</td>
            <td class="label">Doctor</td>
            <td>{{ $ar(($p->doctor->name_ar ?? $p->doctor->name_en) ?? '—') }}</td>
        </tr>
        <tr>
            <td class="label">LMP</td>
            <td>{{ $fmt($p->lmp) }}</td>
            <td class="label">EDD</td>
            <td class="accent"><strong>{{ $fmt($p->edd) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Gestational Age</td>
            <td>{{ $gaLabel }}</td>
            <td class="label">Gravida / Para</td>
            <td>G{{ $p->gravida ?? '-' }} P{{ $p->para ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Blood Group</td>
            <td>{{ $p->blood_group ?? '—' }} {{ $p->rh_factor ? '('.$p->rh_factor.')' : '' }}</td>
            <td class="label">Status</td>
            <td>
                {{ ucfirst($p->status) }}
                @if($p->is_high_risk)<span class="badge risk">HIGH RISK</span>@endif
            </td>
        </tr>
    </table>

    {{-- Antenatal visits --}}
    <div class="section-title">Antenatal Visits</div>
    @if($p->antenatalVisits->count())
        <table class="data">
            <thead>
                <tr><th>Date</th><th>GA (w)</th><th>Weight</th><th>BP</th><th>FHR</th><th>Fundal Ht</th><th>Next Visit</th></tr>
            </thead>
            <tbody>
                @foreach($p->antenatalVisits->sortBy('visit_date') as $v)
                    <tr>
                        <td>{{ $fmt($v->visit_date) }}</td>
                        <td>{{ $v->gestational_age_weeks ?? '—' }}</td>
                        <td>{{ $v->weight_kg ? $v->weight_kg.' kg' : '—' }}</td>
                        <td>{{ $v->blood_pressure ?? '—' }}</td>
                        <td>{{ $v->fetal_heart_rate ?? '—' }}</td>
                        <td>{{ $v->fundal_height_cm ? $v->fundal_height_cm.' cm' : '—' }}</td>
                        <td>{{ $fmt($v->next_visit_date) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No antenatal visits recorded yet.</div>
    @endif

    {{-- Ultrasounds --}}
    <div class="section-title">Ultrasound Scans</div>
    @if($p->ultrasounds->count())
        <table class="data">
            <thead>
                <tr><th>Date</th><th>Type</th><th>GA (w)</th><th>EFW (g)</th><th>AFI</th><th>Findings</th></tr>
            </thead>
            <tbody>
                @foreach($p->ultrasounds->sortBy('scan_date') as $u)
                    <tr>
                        <td>{{ $fmt($u->scan_date) }}</td>
                        <td>{{ ucfirst($u->scan_type) }}</td>
                        <td>{{ $u->gestational_age_weeks ?? '—' }}</td>
                        <td>{{ $u->efw_grams ?? '—' }}</td>
                        <td>{{ $u->afi ?? '—' }}</td>
                        <td>{{ $u->findings ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No ultrasound scans recorded yet.</div>
    @endif

    <div class="footer">
        Generated by {{ $ar($clinicName) }} — Antenatal Card. This document is for medical follow-up purposes.
    </div>
</body>
</html>
