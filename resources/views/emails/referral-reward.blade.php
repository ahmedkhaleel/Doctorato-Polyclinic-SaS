@php
    $isRtl = ($locale ?? 'ar') === 'ar';
    $dir   = $isRtl ? 'rtl' : 'ltr';
    $accent = $role === 'friend' ? '#059669' : '#C4A265';
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $heading }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI'" : "-apple-system, 'Segoe UI'" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 32px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .gift-circle { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, {{ $accent }}, {{ $accent }}aa); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px {{ $accent }}40; position: relative; font-size: 32px; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; position: relative; }
        .header h1 { margin: 6px 0 0; font-size: 23px; font-weight: 800; position: relative; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 20px; }
        .code-card { background: linear-gradient(135deg, {{ $accent }}10, {{ $accent }}05); border: 2px dashed {{ $accent }}; border-radius: 12px; padding: 22px 16px; margin: 22px 0; text-align: center; }
        .code-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .15em; font-weight: 600; margin-bottom: 8px; }
        .code-value { font-family: ui-monospace, 'SF Mono', monospace; font-size: 26px; font-weight: 800; color: #1B365D; letter-spacing: .12em; }
        .code-amount { margin-top: 8px; color: {{ $accent }}; font-weight: 700; font-size: 13px; }
        .cta-wrap { text-align: center; margin: 22px 0 8px; }
        .cta { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, {{ $accent }}, {{ $accent }}cc); color: #fff !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px {{ $accent }}50; }
        .footer { background: #0d2240; padding: 16px 24px; text-align: center; color: rgba(255,255,255,.55); font-size: 11px; }
        .footer strong { color: #C4A265; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="gift-circle">🎁</div>
            <div class="brand-eyebrow">Doctorato Polyclinic</div>
            <h1>{{ $heading }}</h1>
        </div>

        <div class="content">
            <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},</p>

            <p class="intro">{{ $intro }}</p>

            <div class="code-card">
                <div class="code-label">{{ $isRtl ? 'كود الخصم' : 'Your discount code' }}</div>
                <div class="code-value">{{ $discountCode }}</div>
                <div class="code-amount">{{ $isRtl ? 'بقيمة' : 'Worth' }} {{ $discountAmount }}</div>
            </div>

            <div class="cta-wrap">
                <a href="{{ $ctaUrl }}" class="cta">{{ $ctaLabel }}</a>
            </div>

            <p style="text-align: center; font-size: 11px; color: #94a3b8; margin-top: 16px;">
                {{ $isRtl ? 'الكود صالح لمدة 3-6 أشهر · استخدام واحد فقط' : 'Code valid for 3-6 months · One-time use' }}
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} <strong>Doctorato Polyclinic</strong><br>
            {{ $isRtl ? 'أبو ظبي - الإمارات العربية المتحدة' : 'Abu Dhabi, United Arab Emirates' }}
        </div>
    </div>
</body>
</html>
