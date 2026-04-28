@php
    $isRtl = ($locale ?? 'ar') === 'ar';
    $dir   = $isRtl ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI'" : "-apple-system, 'Segoe UI'" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 620px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 30px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; }
        .header h1 { margin: 6px 0 0; font-size: 22px; font-weight: 800; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 18px; }
        .section { background: linear-gradient(135deg, #f8fafc, #fff); border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; margin: 14px 0; }
        .section-title { font-size: 11px; color: #C4A265; letter-spacing: .15em; text-transform: uppercase; font-weight: 700; margin: 0 0 10px; }
        .row { display: table; width: 100%; padding: 6px 0; border-bottom: 1px dashed #e2e8f0; }
        .row:last-child { border-bottom: none; }
        .row .label { display: table-cell; width: 35%; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; }
        .row .value { display: table-cell; font-size: 14px; color: #1e293b; font-weight: 600; }
        .rx-item { padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; margin: 6px 0; font-size: 13px; }
        .rx-name { font-weight: 700; color: #1B365D; }
        .rx-meta { color: #64748b; font-size: 11px; margin-top: 2px; }
        .diagnosis-box { background: #fef3c7; border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #f59e0b; padding: 12px 14px; border-radius: 6px; font-size: 13px; color: #78350f; line-height: 1.55; }
        .cta-wrap { text-align: center; margin: 24px 0 8px; }
        .cta { display: inline-block; padding: 12px 26px; background: linear-gradient(135deg, #C4A265, #D9B985); color: #1B365D !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(196,162,101,.3); }
        .footer { background: #f8fafc; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer a { color: #1B365D; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="brand-eyebrow">{{ $isRtl ? 'ملخّص الزيارة' : 'Visit Summary' }}</span>
        <h1>{{ $heading }}</h1>
    </div>

    <div class="content">
        <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},</p>
        <p class="intro">{{ $intro }}</p>

        {{-- Visit details --}}
        <div class="section">
            <p class="section-title">{{ $isRtl ? 'تفاصيل الزيارة' : 'Visit Details' }}</p>
            <div class="row">
                <div class="label">{{ $isRtl ? 'التاريخ' : 'Date' }}</div>
                <div class="value">{{ $visitDate }}</div>
            </div>
            <div class="row">
                <div class="label">{{ $isRtl ? 'الطبيب' : 'Doctor' }}</div>
                <div class="value">{{ $doctorName }}</div>
            </div>
            @if($serviceName)
            <div class="row">
                <div class="label">{{ $isRtl ? 'الخدمة' : 'Service' }}</div>
                <div class="value">{{ $serviceName }}</div>
            </div>
            @endif
        </div>

        {{-- Diagnosis --}}
        @if($diagnosis)
        <div class="section">
            <p class="section-title">{{ $isRtl ? 'التشخيص والملاحظات' : 'Diagnosis & Notes' }}</p>
            <div class="diagnosis-box">{{ $diagnosis }}</div>
        </div>
        @endif

        {{-- Prescriptions --}}
        @if(count($prescriptions ?? []))
        <div class="section">
            <p class="section-title">{{ $isRtl ? 'الوصفة الطبية' : 'Prescription' }}</p>
            @foreach($prescriptions as $rx)
                <div class="rx-item">
                    <div class="rx-name">{{ $rx['name'] }}</div>
                    @if($rx['dosage'] ?? null)
                        <div class="rx-meta">{{ $rx['dosage'] }}{{ ($rx['duration'] ?? null) ? ' · ' . $rx['duration'] : '' }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        {{-- Invoice link --}}
        @if($invoiceUrl)
        <div class="cta-wrap">
            <a href="{{ $invoiceUrl }}" class="cta">{{ $isRtl ? '📄 عرض الفاتورة' : '📄 View Invoice' }}</a>
        </div>
        @endif

        @if($portalUrl)
        <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 16px;">
            {{ $isRtl ? 'يمكنك عرض كل سجلاتك في' : 'View your full record at' }}
            <a href="{{ $portalUrl }}" style="color: #1B365D; font-weight: 600; text-decoration: none;">{{ $isRtl ? 'بوابة المريض' : 'the patient portal' }}</a>
        </p>
        @endif
    </div>

    <div class="footer">
        Doctorato Polyclinic
    </div>
</div>
</body>
</html>
