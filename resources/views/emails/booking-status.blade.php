@php
    $isRtl  = ($locale ?? 'ar') === 'ar';
    $dir    = $isRtl ? 'rtl' : 'ltr';
    $accent = match($status) {
        'confirmed' => '#059669',
        'cancelled' => '#dc2626',
        default     => '#1B365D',  // pending / created
    };
    $emoji = match($status) {
        'confirmed' => '✅',
        'cancelled' => '⚠️',
        default     => '📅',
    };
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $isRtl ? 'ar' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $heading }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI'" : "-apple-system, 'Segoe UI'" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 620px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 30px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; position: relative; }
        .header h1 { margin: 6px 0 0; font-size: 23px; font-weight: 800; position: relative; }
        .status-bar { background: {{ $accent }}; color: #fff; padding: 10px 24px; font-size: 13px; font-weight: 700; letter-spacing: .04em; }
        .content { padding: 26px 24px; }
        .greeting { font-size: 16px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 18px; }
        .details { background: linear-gradient(135deg, #f8fafc, #fff); border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin: 18px 0; }
        .row { display: table; width: 100%; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .row:last-child { border-bottom: none; }
        .row .label { display: table-cell; width: 35%; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
        .row .value { display: table-cell; font-size: 14px; color: #1e293b; font-weight: 600; }
        .booking-no { display: inline-block; background: #1B365D; color: #C4A265; padding: 6px 14px; border-radius: 999px; font-family: ui-monospace, monospace; font-size: 12px; letter-spacing: .1em; font-weight: 700; }
        .cta-wrap { text-align: center; margin: 24px 0 8px; }
        .cta { display: inline-block; padding: 12px 26px; background: linear-gradient(135deg, #C4A265, #D9B985); color: #1B365D !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(196,162,101,.3); }
        .reason { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 14px; border-radius: 6px; margin: 16px 0; font-size: 13px; color: #92400e; line-height: 1.55; }
        .reminder { background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px 14px; border-radius: 6px; margin: 16px 0; font-size: 13px; color: #0c4a6e; line-height: 1.55; }
        .footer { background: #0d2240; padding: 16px 24px; text-align: center; color: rgba(255,255,255,.55); font-size: 11px; }
        .footer strong { color: #C4A265; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand-eyebrow">Doctorato Polyclinic</div>
            <h1>{{ $emoji }} {{ $heading }}</h1>
        </div>

        <div class="status-bar">
            @switch($status)
                @case('confirmed') {{ $isRtl ? 'تم تأكيد حجزك' : 'Your booking is confirmed' }} @break
                @case('cancelled') {{ $isRtl ? 'تم إلغاء حجزك' : 'Your booking was cancelled' }} @break
                @default {{ $isRtl ? 'تم استلام طلب الحجز' : 'Booking request received' }}
            @endswitch
        </div>

        <div class="content">
            <p class="greeting">{{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},</p>

            <p class="intro">{{ $intro }}</p>

            <div class="details">
                @if(!empty($bookingNumber))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'رقم الحجز' : 'Booking #' }}</div>
                        <div class="value"><span class="booking-no">{{ $bookingNumber }}</span></div>
                    </div>
                @endif
                @if(!empty($doctorName))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الطبيب' : 'Doctor' }}</div>
                        <div class="value">{{ $doctorName }}</div>
                    </div>
                @endif
                @if(!empty($serviceName))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الخدمة' : 'Service' }}</div>
                        <div class="value">{{ $serviceName }}</div>
                    </div>
                @endif
                @if(!empty($appointmentDate))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'التاريخ' : 'Date' }}</div>
                        <div class="value">{{ $appointmentDate }}</div>
                    </div>
                @endif
                @if(!empty($appointmentTime))
                    <div class="row">
                        <div class="label">{{ $isRtl ? 'الوقت' : 'Time' }}</div>
                        <div class="value">{{ $appointmentTime }}</div>
                    </div>
                @endif
            </div>

            @if(!empty($reason))
                <div class="reason">
                    <strong>{{ $isRtl ? 'السبب:' : 'Reason:' }}</strong> {{ $reason }}
                </div>
            @endif

            @if($status === 'confirmed' && !empty($preparationNotes))
                <div class="reminder">
                    <strong>{{ $isRtl ? 'تذكير:' : 'Reminder:' }}</strong> {{ $preparationNotes }}
                </div>
            @endif

            @if(!empty($ctaUrl))
                <div class="cta-wrap">
                    <a href="{{ $ctaUrl }}" class="cta">{{ $ctaLabel ?? ($isRtl ? 'عرض الحجز' : 'View Booking') }}</a>
                </div>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} <strong>Doctorato Polyclinic</strong><br>
            {{ $isRtl ? 'أبو ظبي - الإمارات العربية المتحدة' : 'Abu Dhabi, United Arab Emirates' }}
        </div>
    </div>
</body>
</html>
