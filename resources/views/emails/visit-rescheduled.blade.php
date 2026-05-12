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
        .status-bar { background: #d97706; color: #fff; padding: 10px 24px; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-align: center; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 18px; }
        .new-slot { background: linear-gradient(135deg, #FAF7F0, #fff); border: 2px solid #C4A265; border-radius: 10px; padding: 18px 20px; margin: 18px 0; text-align: center; }
        .new-slot .label { font-size: 11px; color: #8B7043; letter-spacing: .15em; text-transform: uppercase; font-weight: 700; margin-bottom: 6px; }
        .new-slot .date { font-size: 22px; font-weight: 800; color: #1B365D; }
        .new-slot .time { font-size: 16px; color: #475569; margin-top: 4px; }
        .changes { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin: 18px 0; }
        .changes h3 { font-size: 12px; color: #C4A265; letter-spacing: .15em; text-transform: uppercase; font-weight: 700; margin: 0 0 12px; }
        .change-row { display: table; width: 100%; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .change-row:last-child { border-bottom: none; }
        .change-row .field { display: table-cell; width: 28%; font-size: 12px; color: #64748b; font-weight: 600; vertical-align: top; }
        .change-row .delta { display: table-cell; font-size: 13px; }
        .change-row .from { color: #94a3b8; text-decoration: line-through; }
        .change-row .arrow { display: inline-block; margin: 0 6px; color: #C4A265; font-weight: 700; }
        .change-row .to { color: #1B365D; font-weight: 700; }
        .cta-wrap { text-align: center; margin: 24px 0 8px; }
        .cta { display: inline-block; padding: 12px 26px; background: linear-gradient(135deg, #C4A265, #D9B985); color: #1B365D !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(196,162,101,.3); }
        .footer { background: #f8fafc; padding: 18px 24px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="brand-eyebrow">{{ $isRtl ? 'تحديث على الموعد' : 'Appointment Update' }}</span>
        <h1>{{ $heading }}</h1>
    </div>
    <div class="status-bar">⚠️ {{ $isRtl ? 'يرجى مراجعة التفاصيل الجديدة أدناه' : 'Please review the new details below' }}</div>

    <div class="content">
        <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},</p>
        <p class="intro">{{ $intro }}</p>

        {{-- Big new-slot panel --}}
        <div class="new-slot">
            <div class="label">{{ $isRtl ? 'موعدك الجديد' : 'Your new appointment' }}</div>
            <div class="date">{{ $newDate }}</div>
            @if($newTime)
                <div class="time">⏰ {{ $newTime }}</div>
            @endif
        </div>

        {{-- Change diff --}}
        @if(count($rows ?? []))
        <div class="changes">
            <h3>{{ $isRtl ? 'تفاصيل التغيير' : 'What changed' }}</h3>
            @foreach($rows as $row)
                <div class="change-row">
                    <div class="field">{{ $row['label'] }}</div>
                    <div class="delta">
                        <span class="from">{{ $row['from'] ?: '—' }}</span>
                        <span class="arrow">→</span>
                        <span class="to">{{ $row['to'] ?: '—' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        @if($visitUrl)
        <div class="cta-wrap">
            <a href="{{ $visitUrl }}" class="cta">{{ $isRtl ? '📅 عرض الزيارة' : '📅 View appointment' }}</a>
        </div>
        @endif

        <p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 16px;">
            {{ $isRtl
                ? 'لو الموعد الجديد غير مناسب، تواصل معنا في أقرب وقت.'
                : 'If the new time doesn\'t work, please contact us at your earliest convenience.' }}
        </p>
    </div>

    <div class="footer">
        Doctorato Polyclinic
    </div>
</div>
</body>
</html>
