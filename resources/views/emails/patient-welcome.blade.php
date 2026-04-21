@php
    $isRtl = ($locale ?? 'ar') === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
    $lang = $isRtl ? 'ar' : 'en';
@endphp
<!DOCTYPE html>
<html dir="{{ $dir }}" lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isRtl ? 'مرحباً بك في Doctorato' : 'Welcome to Doctorato' }}</title>
    <style>
        body { font-family: {{ $isRtl ? "'Tajawal', 'Segoe UI', Tahoma" : "-apple-system, 'Segoe UI', Tahoma" }}, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; direction: {{ $dir }}; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 36px 24px; color: #fff; text-align: center; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top {{ $isRtl ? 'left' : 'right' }}, rgba(196,162,101,.25), transparent 55%); pointer-events: none; }
        .logo-circle { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #C4A265, #D9B985); margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(196,162,101,.4); position: relative; }
        .logo-circle span { color: #1B365D; font-weight: 900; font-size: 28px; }
        .brand-eyebrow { font-size: 11px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 800; position: relative; }
        .content { padding: 28px 24px; }
        .greeting { font-size: 17px; font-weight: 600; color: #1B365D; margin: 0 0 8px; }
        .intro { font-size: 14px; line-height: 1.7; color: #475569; margin: 0 0 20px; }
        .features { margin: 22px 0; background: linear-gradient(135deg, #f8fafc, #fff); border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; }
        .features h3 { font-size: 13px; color: #C4A265; text-transform: uppercase; letter-spacing: .12em; margin: 0 0 12px; font-weight: 700; }
        .feature { display: flex; align-items: start; gap: 10px; padding: 6px 0; }
        .feature .dot { width: 6px; height: 6px; border-radius: 50%; background: #C4A265; margin-top: 8px; flex-shrink: 0; }
        .feature .text { font-size: 13px; color: #334155; line-height: 1.5; }
        .cta-wrap { text-align: center; margin: 24px 0 12px; }
        .cta { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #C4A265, #D9B985); color: #1B365D !important; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 14px; letter-spacing: .02em; box-shadow: 0 4px 14px rgba(196,162,101,.3); }
        .contact-line { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 16px; line-height: 1.7; }
        .contact-line a { color: #1B365D; text-decoration: none; font-weight: 600; }
        .footer { background: #0d2240; padding: 16px 24px; text-align: center; color: rgba(255,255,255,.55); font-size: 11px; }
        .footer strong { color: #C4A265; font-weight: 700; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-circle"><span>D</span></div>
            <div class="brand-eyebrow">Doctorato Polyclinic</div>
            <h1>{{ $isRtl ? 'أهلاً بك في دكتوراتو!' : 'Welcome to Doctorato!' }}</h1>
        </div>

        <div class="content">
            <p class="greeting">
                {{ $isRtl ? 'مرحباً' : 'Hello' }} {{ $patientName }},
            </p>

            <p class="intro">
                {{ $isRtl
                    ? 'يسعدنا انضمامك إلى عائلة Doctorato. حسابك الآن جاهز للاستخدام، ويمكنك البدء في حجز مواعيدك وإدارة ملفك الطبي بكل سهولة من بوابة المريض.'
                    : 'Thanks for joining the Doctorato family. Your account is ready — you can start booking appointments and managing your medical profile from the patient portal.' }}
            </p>

            <div class="features">
                <h3>{{ $isRtl ? 'ما الذي يمكنك فعله الآن' : 'What you can do now' }}</h3>
                <div class="feature">
                    <div class="dot"></div>
                    <div class="text">{{ $isRtl ? 'احجز موعد عيادة أو استشارة أونلاين بالفيديو.' : 'Book an in-clinic visit or an online video consultation.' }}</div>
                </div>
                <div class="feature">
                    <div class="dot"></div>
                    <div class="text">{{ $isRtl ? 'اعرض زياراتك السابقة، الوصفات الطبية، وخطط العلاج.' : 'Review past visits, prescriptions, and treatment plans.' }}</div>
                </div>
                <div class="feature">
                    <div class="dot"></div>
                    <div class="text">{{ $isRtl ? 'ادفع فواتيرك واطلع على حالة المدفوعات من أي مكان.' : 'Pay invoices and track payment status from anywhere.' }}</div>
                </div>
                <div class="feature">
                    <div class="dot"></div>
                    <div class="text">{{ $isRtl ? 'استلم تذكيرات المواعيد تلقائياً قبل موعدك.' : 'Get automatic appointment reminders before each visit.' }}</div>
                </div>
            </div>

            <div class="cta-wrap">
                <a href="{{ $portalUrl }}" class="cta">
                    {{ $isRtl ? 'ابدأ الآن — افتح بوابة المريض' : 'Get Started — Open Patient Portal' }}
                </a>
            </div>

            <p class="contact-line">
                {{ $isRtl ? 'في حاجة لمساعدة؟ تواصل معنا على' : 'Need help? Contact us at' }}
                <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} <strong>Doctorato Polyclinic</strong><br>
            {{ $isRtl ? 'أبو ظبي - الإمارات العربية المتحدة' : 'Abu Dhabi, United Arab Emirates' }}
        </div>
    </div>
</body>
</html>
