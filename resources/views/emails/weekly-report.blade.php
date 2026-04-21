<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Weekly Clinic Report</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Tahoma, sans-serif; background: #f1f5f9; margin: 0; padding: 24px 12px; color: #1e293b; }
        .container { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.06); }
        .header { background: linear-gradient(135deg, #1B365D 0%, #22406F 50%, #1B365D 100%); padding: 28px 24px; color: #fff; position: relative; }
        .header::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top right, rgba(196,162,101,.2), transparent 50%); pointer-events: none; }
        .header .brand { font-size: 12px; color: #C4A265; letter-spacing: .25em; text-transform: uppercase; font-weight: 700; }
        .header h1 { margin: 6px 0 0; font-size: 24px; font-weight: 700; }
        .header .range { margin-top: 8px; font-size: 13px; color: rgba(255,255,255,.7); }
        .content { padding: 24px; }
        .section { margin-bottom: 26px; }
        .section h2 { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: .1em; margin: 0 0 12px; font-weight: 700; border-bottom: 2px solid #C4A265; padding-bottom: 6px; }
        .kpi-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 8px; }
        .kpi-row { display: table-row; }
        .kpi { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; width: 33.33%; vertical-align: top; }
        .kpi .label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; font-weight: 600; }
        .kpi .value { font-size: 24px; font-weight: 800; color: #1B365D; margin: 4px 0 2px; }
        .kpi .sub { font-size: 11px; color: #64748b; }
        .status { padding: 12px 14px; border-radius: 10px; border-left: 4px solid; margin-top: 4px; }
        .status.ok { background: #ecfdf5; border-color: #10b981; color: #047857; }
        .status.warn { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .status ul { margin: 6px 0 0; padding-left: 20px; }
        .status li { font-size: 13px; padding: 2px 0; }
        .cta { display: inline-block; margin-top: 16px; padding: 10px 20px; background: #1B365D; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
        .footer { background: #f8fafc; padding: 14px 24px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">Doctorato Polyclinic</div>
            <h1>📊 Weekly Operations Report</h1>
            <div class="range">{{ $rangeLabel }}</div>
        </div>

        <div class="content">
            {{-- Bookings --}}
            <div class="section">
                <h2>Bookings</h2>
                <div class="kpi-grid">
                    <div class="kpi-row">
                        <div class="kpi">
                            <div class="label">This week</div>
                            <div class="value">{{ $bookings['this_week'] }}</div>
                            <div class="sub">{{ $bookings['delta_label'] }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Confirmed</div>
                            <div class="value">{{ $bookings['confirmed'] }}</div>
                            <div class="sub">of {{ $bookings['this_week'] }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Cancelled</div>
                            <div class="value">{{ $bookings['cancelled'] }}</div>
                            <div class="sub">@if($bookings['this_week'] > 0){{ round($bookings['cancelled'] / $bookings['this_week'] * 100) }}% rate @else — @endif</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenue --}}
            <div class="section">
                <h2>Revenue (Invoices)</h2>
                <div class="kpi-grid">
                    <div class="kpi-row">
                        <div class="kpi">
                            <div class="label">Collected</div>
                            <div class="value">{{ number_format($revenue['collected'], 0) }}</div>
                            <div class="sub">{{ $currency }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Outstanding</div>
                            <div class="value">{{ number_format($revenue['outstanding'], 0) }}</div>
                            <div class="sub">{{ $currency }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Invoices</div>
                            <div class="value">{{ $revenue['invoice_count'] }}</div>
                            <div class="sub">issued this week</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Telemedicine --}}
            @if($telemedicine['enabled'])
            <div class="section">
                <h2>Telemedicine</h2>
                <div class="kpi-grid">
                    <div class="kpi-row">
                        <div class="kpi">
                            <div class="label">Consultations</div>
                            <div class="value">{{ $telemedicine['consultations_this_week'] }}</div>
                            <div class="sub">this week</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Completed</div>
                            <div class="value">{{ $telemedicine['completed'] }}</div>
                            <div class="sub">all-time</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Gateway</div>
                            <div class="value" style="font-size:16px;color:{{ $telemedicine['gateway'] ? '#059669' : '#dc2626' }};">
                                {{ $telemedicine['gateway'] ?: 'NONE' }}
                            </div>
                            <div class="sub">active payment</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- System status --}}
            <div class="section">
                <h2>System Status</h2>
                @if(empty($blockers))
                    <div class="status ok">
                        ✓ All checks passing — system is healthy.
                    </div>
                @else
                    <div class="status warn">
                        ⚠ {{ count($blockers) }} issue(s) need attention:
                        <ul>
                            @foreach($blockers as $b)<li>{{ $b }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <a href="{{ $diagnosticsUrl }}" class="cta">Open Full Diagnostics →</a>
            </div>
        </div>

        <div class="footer">
            Auto-generated every Monday. To stop, clear
            <code>Setting::health_alert_email</code> or remove the super_admin role.
        </div>
    </div>
</body>
</html>
