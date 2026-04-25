@php
    $isRtl = ($locale ?? 'ar') === 'ar';
    $dir   = $isRtl ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isRtl ? 'إعادة تعيين كلمة المرور' : 'Reset your password' }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI'" : "-apple-system, 'Segoe UI'" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 32px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .lock-circle { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #C4A265, #D9B985); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(196,162,101,.4); position: relative; }
        .lock-circle svg { width: 30px; height: 30px; color: #1B365D; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; position: relative; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; position: relative; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 18px; }
        .portal-pill { display: inline-block; padding: 4px 10px; background: #1B365D; color: #C4A265; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .cta-wrap { text-align: center; margin: 24px 0 16px; }
        .cta { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #C4A265, #D9B985); color: #1B365D !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(196,162,101,.3); }
        .url-fallback { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin: 16px 0; font-family: ui-monospace, 'SF Mono', Menlo, monospace; font-size: 11px; color: #475569; word-break: break-all; line-height: 1.5; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 14px; border-radius: 6px; margin: 18px 0; font-size: 13px; color: #92400e; line-height: 1.55; }
        .meta { font-size: 12px; color: #64748b; margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .footer { background: #0d2240; padding: 16px 24px; text-align: center; color: rgba(255,255,255,.55); font-size: 11px; }
        .footer strong { color: #C4A265; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="lock-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div class="brand-eyebrow">Doctorato Polyclinic</div>
            <h1>{{ $isRtl ? 'إعادة تعيين كلمة المرور' : 'Reset your password' }}</h1>
        </div>

        <div class="content">
            <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $userName }},</p>

            <p class="intro">
                {{ $isRtl
                    ? 'وصلنا طلب لإعادة تعيين كلمة المرور لحسابك على بوابة'
                    : 'We received a request to reset the password for your' }}
                <span class="portal-pill">{{ strtoupper($portalKey) }}</span>
                {{ $isRtl ? 'في Doctorato. اضغط على الزر أدناه لتعيين كلمة مرور جديدة.' : 'portal at Doctorato. Click the button below to choose a new password.' }}
            </p>

            <div class="cta-wrap">
                <a href="{{ $resetUrl }}" class="cta">
                    {{ $isRtl ? 'تعيين كلمة المرور الجديدة' : 'Reset Password' }}
                </a>
            </div>

            <p style="font-size: 12px; color: #94a3b8; text-align: center; margin: 6px 0 0;">
                {{ $isRtl ? 'الرابط صالح لمدة' : 'This link expires in' }}
                <strong>{{ $expiryMinutes ?? 60 }} {{ $isRtl ? 'دقيقة' : 'minutes' }}</strong>.
            </p>

            <div class="warning">
                <strong>{{ $isRtl ? 'لم تطلب هذا؟' : "Didn't request this?" }}</strong>
                {{ $isRtl
                    ? 'لا تحتاج لفعل شيء — سيستمر حسابك بكلمة المرور الحالية. إذا تكرر طلب إعادة التعيين دون علمك، تواصل مع الدعم فوراً.'
                    : 'No action needed — your current password keeps working. If you keep getting reset requests you didn\'t initiate, contact support immediately.' }}
            </div>

            <div class="meta">
                {{ $isRtl ? 'الرابط لا يعمل؟ انسخ والصق هذا في متصفحك:' : 'Button not working? Copy and paste this into your browser:' }}
                <div class="url-fallback">{{ $resetUrl }}</div>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} <strong>Doctorato Polyclinic</strong><br>
            {{ $isRtl ? 'أبو ظبي - الإمارات العربية المتحدة' : 'Abu Dhabi, United Arab Emirates' }}
        </div>
    </div>
</body>
</html>
