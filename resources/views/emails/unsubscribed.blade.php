<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $clinicName }}</title>
</head>
<body style="margin:0;font-family:'Tajawal',-apple-system,Segoe UI,Tahoma,sans-serif;background:#f1f5f9;display:flex;min-height:100vh;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(27,54,93,.1);max-width:440px;width:90%;padding:40px 32px;text-align:center;">
        <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 style="margin:0 0 10px;color:#1B365D;font-size:20px;">تم إلغاء الاشتراك</h1>
        <p style="margin:0;color:#64748b;font-size:15px;line-height:1.8;">
            لن تتلقى بعد الآن رسائل تسويقية من {{ $clinicName }}.<br>
            ستظل تصلك رسائل المواعيد والفواتير الضرورية.
        </p>
        <div style="height:3px;width:48px;background:#C4A265;margin:24px auto 0;border-radius:2px;"></div>
    </div>
</body>
</html>
