@php
    $severity = $severity ?? 'warning';        // warning | critical | resolved
    $accent = match($severity) {
        'critical' => '#dc2626',
        'resolved' => '#059669',
        default    => '#d97706',
    };
    $emoji = match($severity) {
        'critical' => '🛑',
        'resolved' => '✅',
        default    => '⚠️',
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'System alert' }}</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: #1B365D; padding: 20px 24px; color: #fff; }
        .header .brand { font-size: 13px; color: #C4A265; letter-spacing: .2em; text-transform: uppercase; font-weight: 600; }
        .header h1 { margin: 4px 0 0; font-size: 20px; font-weight: 700; }
        .severity-bar { background: {{ $accent }}; color: #fff; padding: 10px 24px; font-size: 13px; font-weight: 600; letter-spacing: .05em; }
        .content { padding: 24px; }
        .content p { margin: 0 0 14px; line-height: 1.6; font-size: 14px; color: #334155; }
        .reasons { background: #f8fafc; border-left: 4px solid {{ $accent }}; padding: 14px 16px; border-radius: 6px; margin: 16px 0; }
        .reasons ul { margin: 0; padding-left: 20px; }
        .reasons li { font-size: 14px; color: #1e293b; padding: 2px 0; }
        .metadata { margin-top: 18px; font-size: 12px; color: #64748b; background: #f1f5f9; padding: 10px 14px; border-radius: 6px; font-family: ui-monospace, 'SF Mono', Menlo, monospace; }
        .metadata div { padding: 2px 0; }
        .cta { display: inline-block; margin-top: 18px; padding: 10px 20px; background: #1B365D; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
        .footer { background: #f8fafc; padding: 14px 24px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">Doctorato Polyclinic</div>
            <h1>{{ $emoji }} {{ $subject ?? 'System Alert' }}</h1>
        </div>

        <div class="severity-bar">
            @switch($severity)
                @case('critical') CRITICAL — Immediate attention required @break
                @case('resolved') RESOLVED — All systems operational @break
                @default DEGRADED — System operating with warnings
            @endswitch
        </div>

        <div class="content">
            <p>{{ $intro ?? 'One or more system checks have reported a degraded subsystem.' }}</p>

            @if(!empty($reasons))
                <div class="reasons">
                    <ul>
                        @foreach($reasons as $r)
                            <li>{{ $r }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="metadata">
                <div><strong>Time:</strong> {{ $timestamp ?? now()->toDateTimeString() }}</div>
                <div><strong>Environment:</strong> {{ $environment ?? app()->environment() }}</div>
                @if(!empty($appUrl))
                    <div><strong>App:</strong> {{ $appUrl }}</div>
                @endif
            </div>

            @if(!empty($ctaUrl))
                <a href="{{ $ctaUrl }}" class="cta">{{ $ctaLabel ?? 'Open diagnostics page' }}</a>
            @endif
        </div>

        <div class="footer">
            You received this because you are a super_admin or the designated
            <code>health_alert_email</code> recipient.
        </div>
    </div>
</body>
</html>
