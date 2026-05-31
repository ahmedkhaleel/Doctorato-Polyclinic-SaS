<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Models\NotificationChannelRoute;
use App\Models\NotificationEvent;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Services\Notifications\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Admin Control Center for the unified Notifications Hub.
 *
 * Reads never expose raw credentials — only presence flags. Writes are
 * permission-gated (see admin.php). SMS provider credentials live in Settings
 * (legacy SmsService); email/whatsapp credentials live in the encrypted
 * notification_channels.config.
 */
class AdminNotificationHubController extends Controller
{
    private const CHANNELS = ['whatsapp', 'sms', 'email', 'in_app'];

    /** Required credential keys per channel, used to compute "configured". */
    private const REQUIRED = [
        'email' => ['host', 'username', 'password'],
        'whatsapp_cloud_api' => ['phone_number_id', 'access_token'],
        'whatsapp_bridge' => ['base_url'],
    ];

    public function index()
    {
        $channels = NotificationChannel::all()->keyBy('channel');

        $channelCards = collect(self::CHANNELS)->map(function ($key) use ($channels) {
            $c = $channels->get($key);

            return [
                'channel' => $key,
                'enabled' => (bool) ($c->enabled ?? false),
                'provider' => $c->provider ?? null,
                'from_name' => $c->from_name ?? null,
                'daily_cap' => $c->daily_cap ?? null,
                'monthly_cap' => $c->monthly_cap ?? null,
                'configured' => $this->isConfigured($key, $c),
                'config_presence' => $this->configPresence($key, $c),
            ];
        })->values();

        $events = NotificationEvent::orderBy('category')->orderBy('key')->get()
            ->map(fn ($e) => [
                'key' => $e->key,
                'label_ar' => $e->label_ar,
                'label_en' => $e->label_en,
                'category' => $e->category,
                'is_active' => (bool) $e->is_active,
            ]);

        $routes = NotificationChannelRoute::all()
            ->groupBy('event_key')
            ->map(fn ($g) => $g->mapWithKeys(fn ($r) => [$r->channel => ['enabled' => (bool) $r->enabled, 'priority' => (int) $r->priority]]));

        $templates = NotificationTemplate::orderBy('event_key')->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'event_key' => $t->event_key,
                'channel' => $t->channel,
                'subject' => $t->subject,
                'body_ar' => $t->body_ar,
                'body_en' => $t->body_en,
                'is_active' => (bool) $t->is_active,
            ]);

        return Inertia::render('Admin/Notifications/ControlCenter', [
            'channelKeys' => self::CHANNELS,
            'channels' => $channelCards,
            'events' => $events,
            'routes' => $routes,
            'templates' => $templates,
            'providerOptions' => [
                'sms' => ['smsmisr', 'twilio', 'unifonic', 'gateway', 'none'],
                'whatsapp' => ['cloud_api', 'bridge'],
                'email' => ['smtp'],
            ],
            'smsSettings' => [
                'sms_provider' => Setting::get('sms_provider', 'none'),
                'sms_sender_name' => Setting::get('sms_sender_name', 'Doctorato'),
                'sms_default_country_code' => Setting::get('sms_default_country_code', '20'),
                'sms_smsmisr_sender' => Setting::get('sms_smsmisr_sender', ''),
                'sms_smsmisr_username' => Setting::get('sms_smsmisr_username', ''),
                'sms_smsmisr_environment' => Setting::get('sms_smsmisr_environment', '1'),
                'has_smsmisr_password' => (bool) Setting::get('sms_smsmisr_password'),
                'sms_twilio_from_number' => Setting::get('sms_twilio_from_number', ''),
                'has_twilio_token' => (bool) Setting::get('sms_twilio_auth_token'),
            ],
            'globalSettings' => [
                'notifications_global_daily_cap' => (int) Setting::get('notifications_global_daily_cap', '0'),
                'sms_cost_per_segment' => (float) Setting::get('sms_cost_per_segment', '0'),
                'has_whatsapp_verify_token' => (bool) Setting::get('whatsapp_webhook_verify_token'),
                'notifications_quiet_start' => Setting::get('notifications_quiet_start', ''),
                'notifications_quiet_end' => Setting::get('notifications_quiet_end', ''),
                'notifications_marketing_weekly_cap' => (int) Setting::get('notifications_marketing_weekly_cap', '0'),
                'notifications_smart_routing' => Setting::get('notifications_smart_routing', '0') === '1',
                'notifications_monthly_cost_cap' => (float) Setting::get('notifications_monthly_cost_cap', '0'),
            ],
            'stats' => $this->stats(),
        ]);
    }

    public function logs(Request $request)
    {
        $query = NotificationLog::query()->latest();

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('event_key')) {
            $query->where('event_key', $request->input('event_key'));
        }
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn ($q) => $q->where('to', 'like', "%{$s}%")->orWhere('event_key', 'like', "%{$s}%"));
        }

        return Inertia::render('Admin/Notifications/Logs', [
            'logs' => $query->paginate(30)->withQueryString(),
            'filters' => $request->only(['channel', 'status', 'event_key', 'search']),
            'channelKeys' => self::CHANNELS,
            'statuses' => ['queued', 'sent', 'delivered', 'read', 'failed', 'skipped'],
            'stats' => $this->stats(),
        ]);
    }

    public function analytics(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = max(1, min(365, $days));
        $since = now()->subDays($days)->startOfDay();

        $counted = [NotificationLog::STATUS_SENT, NotificationLog::STATUS_DELIVERED, NotificationLog::STATUS_READ];

        // Per-channel status matrix + cost.
        $rows = NotificationLog::where('created_at', '>=', $since)
            ->select('channel', 'status', DB::raw('count(*) as c'), DB::raw('COALESCE(SUM(cost),0) as cost'))
            ->groupBy('channel', 'status')->get();

        $byChannel = [];
        foreach (self::CHANNELS as $ch) {
            $byChannel[$ch] = ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'failed' => 0, 'skipped' => 0, 'cost' => 0.0];
        }
        foreach ($rows as $r) {
            if (! isset($byChannel[$r->channel])) {
                continue;
            }
            $byChannel[$r->channel][$r->status] = ($byChannel[$r->channel][$r->status] ?? 0) + (int) $r->c;
            $byChannel[$r->channel]['total'] += (int) $r->c;
            $byChannel[$r->channel]['cost'] += (float) $r->cost;
        }
        foreach ($byChannel as $ch => &$m) {
            $reached = $m['sent'] + $m['delivered'] + $m['read'];
            $attempted = $reached + $m['failed'];
            $m['delivery_rate'] = $attempted > 0 ? round($reached / $attempted * 100, 1) : null;
            $m['read_rate'] = $reached > 0 ? round($m['read'] / $reached * 100, 1) : null;
        }
        unset($m);

        // Per-event breakdown (top 15 by volume).
        $perEvent = NotificationLog::where('created_at', '>=', $since)
            ->select('event_key',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw('COALESCE(SUM(cost),0) as cost'))
            ->groupBy('event_key')->orderByDesc('total')->limit(15)->get();

        // Daily series for the trend chart.
        $daily = NotificationLog::where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as d'),
                DB::raw('count(*) as total'),
                DB::raw('COALESCE(SUM(cost),0) as cost'))
            ->groupBy('d')->orderBy('d')->get();

        // Top failure reasons.
        $failures = NotificationLog::where('created_at', '>=', $since)
            ->where('status', NotificationLog::STATUS_FAILED)->whereNotNull('error')
            ->select('error', DB::raw('count(*) as c'))
            ->groupBy('error')->orderByDesc('c')->limit(10)->get();

        return Inertia::render('Admin/Notifications/Analytics', [
            'days' => $days,
            'channelKeys' => self::CHANNELS,
            'byChannel' => $byChannel,
            'totals' => [
                'sent' => array_sum(array_column($byChannel, 'sent')) + array_sum(array_column($byChannel, 'delivered')) + array_sum(array_column($byChannel, 'read')),
                'failed' => array_sum(array_column($byChannel, 'failed')),
                'skipped' => array_sum(array_column($byChannel, 'skipped')),
                'cost' => round(array_sum(array_column($byChannel, 'cost')), 2),
            ],
            'perEvent' => $perEvent,
            'daily' => $daily,
            'failures' => $failures,
        ]);
    }

    public function updateChannel(Request $request, string $channel)
    {
        abort_unless(in_array($channel, self::CHANNELS, true), 404);

        $data = $request->validate([
            'enabled' => 'sometimes|boolean',
            'provider' => 'nullable|string|max:30',
            'from_name' => 'nullable|string|max:100',
            'daily_cap' => 'nullable|integer|min:0',
            'monthly_cap' => 'nullable|integer|min:0',
            'config' => 'nullable|array',          // email/whatsapp credentials (write-only)
            'sms' => 'nullable|array',             // sms provider settings written to Settings
        ]);

        $model = NotificationChannel::firstOrNew(['channel' => $channel]);
        $model->enabled = $request->boolean('enabled', $model->enabled ?? false);
        if (array_key_exists('provider', $data)) {
            $model->provider = $data['provider'];
        }
        $model->from_name = $data['from_name'] ?? $model->from_name;
        $model->daily_cap = $data['daily_cap'] ?? null;
        $model->monthly_cap = $data['monthly_cap'] ?? null;

        // Merge new credential values into the encrypted config (skip blanks so a
        // blank field doesn't wipe an existing secret).
        if (! empty($data['config']) && in_array($channel, ['email', 'whatsapp'], true)) {
            $existing = $model->config ?? [];
            foreach ($data['config'] as $k => $v) {
                if ($v !== null && $v !== '') {
                    $existing[$k] = $v;
                }
            }
            $model->config = $existing;
        }
        $model->save();

        // SMS provider credentials → Settings (skip blanks for secrets).
        if ($channel === 'sms' && ! empty($data['sms'])) {
            $this->saveSmsSettings($data['sms']);
        }

        return back()->with('success', __('Channel updated.'));
    }

    public function updateRoute(Request $request)
    {
        $data = $request->validate([
            'event_key' => 'required|string|exists:notification_events,key',
            'channel' => 'required|string|in:whatsapp,sms,email,in_app',
            'enabled' => 'required|boolean',
            'priority' => 'nullable|integer|min:0|max:99',
        ]);

        NotificationChannelRoute::updateOrCreate(
            ['event_key' => $data['event_key'], 'channel' => $data['channel']],
            ['enabled' => $data['enabled'], 'priority' => $data['priority'] ?? 0]
        );

        return back()->with('success', __('Routing updated.'));
    }

    public function updateEvent(Request $request, string $key)
    {
        $event = NotificationEvent::where('key', $key)->firstOrFail();
        $event->update($request->validate(['is_active' => 'required|boolean']));

        return back()->with('success', __('Event updated.'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $this->validateTemplate($request);
        NotificationTemplate::updateOrCreate(
            ['event_key' => $data['event_key'], 'channel' => $data['channel']],
            $data
        );

        return back()->with('success', __('Template saved.'));
    }

    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $template->update($this->validateTemplate($request));

        return back()->with('success', __('Template updated.'));
    }

    public function destroyTemplate(NotificationTemplate $template)
    {
        $template->delete();

        return back()->with('success', __('Template deleted.'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'notifications_global_daily_cap' => 'nullable|integer|min:0',
            'sms_cost_per_segment' => 'nullable|numeric|min:0',
            'whatsapp_webhook_verify_token' => 'nullable|string|max:255',
            'notifications_quiet_start' => 'nullable|date_format:H:i',
            'notifications_quiet_end' => 'nullable|date_format:H:i',
            'notifications_marketing_weekly_cap' => 'nullable|integer|min:0',
            'notifications_smart_routing' => 'nullable|boolean',
            'notifications_monthly_cost_cap' => 'nullable|numeric|min:0',
        ]);

        if (array_key_exists('notifications_monthly_cost_cap', $data)) {
            Setting::set('notifications_monthly_cost_cap', (string) ($data['notifications_monthly_cost_cap'] ?? 0));
        }

        if (array_key_exists('notifications_smart_routing', $data)) {
            Setting::set('notifications_smart_routing', $request->boolean('notifications_smart_routing') ? '1' : '0');
        }

        if (array_key_exists('notifications_global_daily_cap', $data)) {
            Setting::set('notifications_global_daily_cap', (string) ($data['notifications_global_daily_cap'] ?? 0));
        }
        if (array_key_exists('sms_cost_per_segment', $data)) {
            Setting::set('sms_cost_per_segment', (string) ($data['sms_cost_per_segment'] ?? 0));
        }
        if (! empty($data['whatsapp_webhook_verify_token'])) {
            Setting::set('whatsapp_webhook_verify_token', $data['whatsapp_webhook_verify_token']);
        }
        foreach (['notifications_quiet_start', 'notifications_quiet_end', 'notifications_marketing_weekly_cap'] as $k) {
            if (array_key_exists($k, $data)) {
                Setting::set($k, (string) ($data[$k] ?? ''));
            }
        }

        return back()->with('success', __('Settings updated.'));
    }

    // ── WhatsApp template registry ─────────────────────────

    public function whatsappTemplates()
    {
        return Inertia::render('Admin/Notifications/WhatsAppTemplates', [
            'templates' => \App\Models\WhatsappTemplate::orderBy('event_key')->get(),
            'events' => NotificationEvent::orderBy('key')->get(['key', 'label_ar', 'label_en']),
        ]);
    }

    public function storeWhatsappTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'language' => 'required|string|max:10',
            'event_key' => 'nullable|string|exists:notification_events,key',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:60',
            'body_preview' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]);

        \App\Models\WhatsappTemplate::create($data);

        return back()->with('success', __('WhatsApp template saved.'));
    }

    public function updateWhatsappTemplate(Request $request, \App\Models\WhatsappTemplate $whatsappTemplate)
    {
        $whatsappTemplate->update($request->validate([
            'name' => 'sometimes|string|max:150',
            'language' => 'sometimes|string|max:10',
            'event_key' => 'nullable|string|exists:notification_events,key',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:60',
            'body_preview' => 'nullable|string|max:2000',
            'is_active' => 'sometimes|boolean',
        ]));

        return back()->with('success', __('WhatsApp template updated.'));
    }

    public function destroyWhatsappTemplate(\App\Models\WhatsappTemplate $whatsappTemplate)
    {
        $whatsappTemplate->delete();

        return back()->with('success', __('WhatsApp template deleted.'));
    }

    /** Send a one-off test message on a channel (permission: notifications.send). */
    public function test(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:whatsapp,sms,email,in_app',
            'to' => 'required|string|max:190',
        ]);

        $logs = Notifier::eventNow('manual.message', null, [
            'to' => $data['to'],
            'body' => __('Test message from the Doctorato Notifications Hub.'),
            'subject' => 'Doctorato — Test',
        ], [$data['channel']]);

        $log = collect($logs)->first();
        $ok = $log && $log->status === NotificationLog::STATUS_SENT;

        return back()->with($ok ? 'success' : 'error',
            $ok ? __('Test message sent.') : __('Test failed: ').($log->error ?? 'unknown'));
    }

    // ── helpers ────────────────────────────────────────────

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'event_key' => 'required|string|exists:notification_events,key',
            'channel' => 'required|string|in:whatsapp,sms,email,in_app',
            'subject' => 'nullable|string|max:255',
            'body_ar' => 'required|string',
            'body_en' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
    }

    private function saveSmsSettings(array $sms): void
    {
        $plain = ['sms_provider', 'sms_sender_name', 'sms_default_country_code',
            'sms_smsmisr_sender', 'sms_smsmisr_username', 'sms_smsmisr_environment', 'sms_twilio_from_number'];
        foreach ($plain as $key) {
            if (array_key_exists($key, $sms) && $sms[$key] !== null) {
                Setting::set($key, (string) $sms[$key]);
            }
        }
        // Secrets — only write when a non-blank value is supplied.
        foreach (['sms_smsmisr_password', 'sms_twilio_account_sid', 'sms_twilio_auth_token'] as $secret) {
            if (! empty($sms[$secret])) {
                Setting::set($secret, $sms[$secret]);
            }
        }
    }

    private function isConfigured(string $channel, ?NotificationChannel $c): bool
    {
        if ($channel === 'in_app') {
            return true;
        }
        if ($channel === 'sms') {
            return Setting::get('sms_provider', 'none') !== 'none';
        }
        $config = $c?->config ?? [];
        if ($channel === 'email') {
            return ! empty($config['host']) && ! empty($config['username']);
        }
        if ($channel === 'whatsapp') {
            $req = $c?->provider === 'bridge' ? self::REQUIRED['whatsapp_bridge'] : self::REQUIRED['whatsapp_cloud_api'];

            return collect($req)->every(fn ($k) => ! empty($config[$k]));
        }

        return false;
    }

    /** Which credential fields are present (true) — never the values themselves. */
    private function configPresence(string $channel, ?NotificationChannel $c): array
    {
        $config = $c?->config ?? [];
        $keys = match ($channel) {
            'email' => ['host', 'port', 'username', 'password', 'encryption', 'from_address'],
            'whatsapp' => ['phone_number_id', 'access_token', 'base_url', 'api_key', 'session'],
            default => [],
        };

        $out = [];
        foreach ($keys as $k) {
            // Don't leak secrets: passwords/tokens report presence only.
            $out[$k] = in_array($k, ['password', 'access_token', 'api_key'], true)
                ? ! empty($config[$k])
                : ($config[$k] ?? null);
        }

        return $out;
    }

    private function stats(): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $counted = [NotificationLog::STATUS_SENT, NotificationLog::STATUS_DELIVERED, NotificationLog::STATUS_READ];

        $perChannelToday = NotificationLog::where('created_at', '>=', $today)
            ->whereIn('status', $counted)
            ->select('channel', DB::raw('count(*) as c'))
            ->groupBy('channel')->pluck('c', 'channel');

        return [
            'today_total' => (int) $perChannelToday->sum(),
            'today_per_channel' => $perChannelToday,
            'queued' => NotificationLog::where('status', NotificationLog::STATUS_QUEUED)->count(),
            'failed_today' => NotificationLog::where('created_at', '>=', $today)->where('status', NotificationLog::STATUS_FAILED)->count(),
            'month_cost' => (float) NotificationLog::where('created_at', '>=', $month)->sum('cost'),
        ];
    }
}
