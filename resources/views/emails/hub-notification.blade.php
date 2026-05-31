<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clinicName }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Tajawal',-apple-system,Segoe UI,Tahoma,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
        <tr><td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(27,54,93,0.08);">
                <!-- Header -->
                <tr><td style="background:linear-gradient(135deg,#1B365D,#2a4a7a);padding:28px 32px;text-align:center;">
                    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:.3px;">{{ $clinicName }}</h1>
                    <div style="height:3px;width:48px;background:#C4A265;margin:12px auto 0;border-radius:2px;"></div>
                </td></tr>
                <!-- Body -->
                <tr><td style="padding:32px;color:#1f2937;font-size:15px;line-height:1.9;">
                    {!! nl2br(e($bodyText)) !!}
                </td></tr>
                <!-- Footer -->
                <tr><td style="padding:20px 32px;background:#f8fafc;border-top:1px solid #eef2f7;text-align:center;color:#94a3b8;font-size:12px;">
                    <p style="margin:0 0 6px;">© {{ date('Y') }} {{ $clinicName }}</p>
                    @if ($unsubscribeUrl)
                        <p style="margin:0;">
                            <a href="{{ $unsubscribeUrl }}" style="color:#94a3b8;text-decoration:underline;">إلغاء الاشتراك من الرسائل التسويقية · Unsubscribe</a>
                        </p>
                    @endif
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
