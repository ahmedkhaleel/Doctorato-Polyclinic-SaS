<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; direction: rtl; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #A68B52, #C4A265, #D4B87A); padding: 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .content { padding: 24px; }
        .field { margin-bottom: 16px; border-bottom: 1px solid #f0f0f0; padding-bottom: 12px; }
        .field-label { font-weight: bold; color: #A68B52; font-size: 14px; margin-bottom: 4px; }
        .field-value { color: #333; font-size: 16px; }
        .footer { background: #f9f9f9; padding: 16px; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>طلب حجز جديد — AURA Derma Clinic</h1>
        </div>
        <div class="content">
            <div class="field">
                <div class="field-label">الاسم / Name</div>
                <div class="field-value">{{ $booking->full_name }}</div>
            </div>
            <div class="field">
                <div class="field-label">الهاتف / Phone</div>
                <div class="field-value">{{ $booking->phone }}</div>
            </div>
            @if($booking->email)
            <div class="field">
                <div class="field-label">البريد الإلكتروني / Email</div>
                <div class="field-value">{{ $booking->email }}</div>
            </div>
            @endif
            @if($booking->service)
            <div class="field">
                <div class="field-label">الخدمة / Service</div>
                <div class="field-value">{{ $booking->service->name_ar }} — {{ $booking->service->name_en }}</div>
            </div>
            @endif
            @if($booking->doctor)
            <div class="field">
                <div class="field-label">الطبيب / Doctor</div>
                <div class="field-value">{{ $booking->doctor->name_ar }} — {{ $booking->doctor->name_en }}</div>
            </div>
            @endif
            @if($booking->preferred_date)
            <div class="field">
                <div class="field-label">التاريخ المفضل / Preferred Date</div>
                <div class="field-value">{{ $booking->preferred_date->format('Y-m-d') }}</div>
            </div>
            @endif
            @if($booking->preferred_time)
            <div class="field">
                <div class="field-label">الوقت المفضل / Preferred Time</div>
                <div class="field-value">{{ $booking->preferred_time }}</div>
            </div>
            @endif
            @if($booking->notes)
            <div class="field">
                <div class="field-label">ملاحظات / Notes</div>
                <div class="field-value">{{ $booking->notes }}</div>
            </div>
            @endif
        </div>
        <div class="footer">
            AURA Derma Aesthetic Clinic &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
