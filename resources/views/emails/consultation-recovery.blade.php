@php
    $isRtl = ($locale ?? 'ar') === 'ar';
    $dir   = $isRtl ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isRtl ? 'حجزك في انتظارك' : 'Complete your booking' }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI'" : "-apple-system, 'Segoe UI'" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 32px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .video-circle { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #C4A265, #D9B985); margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(196,162,101,.4); position: relative; }
        .video-circle svg { width: 32px; height: 32px; color: #1B365D; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; position: relative; }
        .header h1 { margin: 6px 0 0; font-size: 23px; font-weight: 800; position: relative; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 18px; }
        .booking-card { background: linear-gradient(135deg, #fefce8, #fef9c3); border: 1px solid #fde68a; border-radius: 10px; padding: 18px 20px; margin: 18px 0; }
        .row { padding: 6px 0; border-bottom: 1px dashed #fbbf24; display: table; width: 100%; }
        .row:last-child { border-bottom: none; }
        .row .label { display: table-cell; width: 35%; font-size: 12px; color: #92400e; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
        .row .value { display: table-cell; font-size: 14px; color: #78350f; font-weight: 700; }
        .urgency { background: #fee2e2; border-left: 4px solid #dc2626; padding: 12px 14px; border-radius: 6px; margin: 16px 0; font-size: 13px; color: #991b1b; line-height: 1.55; }
        .cta-wrap { text-align: center; margin: 24px 0 8px; }
        .cta { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #059669, #10b981); color: #fff !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 14px rgba(5,150,105,.3); }
        .small-link { font-size: 12px; color: #64748b; text-align: center; margin-top: 12px; }
        .small-link a { color: #1B365D; }
        .footer { background: #0d2240; padding: 16px 24px; text-align: center; color: rgba(255,255,255,.55); font-size: 11px; }
        .footer strong { color: #C4A265; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="video-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="23 7 16 12 23 17 23 7"/>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                </svg>
            </div>
            <div class="brand-eyebrow">Doctorato Polyclinic</div>
            <h1>{{ $isRtl ? 'حجزك في انتظار الدفع' : 'Your booking is waiting' }}</h1>
        </div>

        <div class="content">
            <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},</p>

            <p class="intro">
                {{ $isRtl
                    ? 'بدأتِ حجز استشارة أونلاين معنا قبل قليل ولكن لم تكتمل عملية الدفع. لا تقلقي — موعدك ما زال محجوزاً مؤقتاً.'
                    : 'You started booking an online consultation but the payment didn\'t complete. Don\'t worry — your slot is still held for now.' }}
            </p>

            <div class="booking-card">
                @if(!empty($doctorName))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الطبيب' : 'Doctor' }}</div>
                        <div class="value">{{ $doctorName }}</div>
                    </div>
                @endif
                @if(!empty($appointmentDate))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الموعد' : 'When' }}</div>
                        <div class="value">{{ $appointmentDate }} · {{ $appointmentTime }}</div>
                    </div>
                @endif
                @if(!empty($fee))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الرسوم' : 'Fee' }}</div>
                        <div class="value">{{ $fee }}</div>
                    </div>
                @endif
            </div>

            <div class="urgency">
                <strong>{{ $isRtl ? 'تنبيه:' : 'Heads up:' }}</strong>
                {{ $isRtl
                    ? 'الموعد سيتاح للحجز من قِبل مرضى آخرين تلقائياً خلال الساعات القادمة إذا لم تكتمل عملية الدفع.'
                    : 'The slot will be released for other patients within the next few hours if payment doesn\'t complete.' }}
            </div>

            <div class="cta-wrap">
                <a href="{{ $resumeUrl }}" class="cta">
                    {{ $isRtl ? '✓ إكمال الحجز والدفع الآن' : '✓ Complete Booking & Pay Now' }}
                </a>
            </div>

            <p class="small-link">
                {{ $isRtl ? 'لم تعودي ترغبين في الحجز؟' : 'No longer want to book?' }}
                <a href="{{ $resumeUrl }}">{{ $isRtl ? 'يمكنك إلغاؤه من البوابة' : 'You can cancel from the portal' }}</a>.
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} <strong>Doctorato Polyclinic</strong><br>
            {{ $isRtl ? 'أبو ظبي - الإمارات العربية المتحدة' : 'Abu Dhabi, United Arab Emirates' }}
        </div>
    </div>
</body>
</html>
