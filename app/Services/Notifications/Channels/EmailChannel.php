<?php

namespace App\Services\Notifications\Channels;

use App\Models\NotificationChannel;
use App\Services\Notifications\Contracts\ChannelDriver;
use App\Services\Notifications\DeliveryResult;
use App\Services\Notifications\NotificationMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

/**
 * Email channel. SMTP credentials live per-clinic in the channel config
 * (encrypted). If config is present we apply it to the runtime mailer; otherwise
 * we fall back to the framework's default mailer.
 */
class EmailChannel implements ChannelDriver
{
    public function key(): string
    {
        return 'email';
    }

    public function isConfigured(): bool
    {
        $channel = NotificationChannel::for('email');

        return $channel && $channel->enabled;
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if (! $message->to) {
            return DeliveryResult::fail('No email address.', 'smtp');
        }

        $channel = NotificationChannel::for('email');
        $config = $channel?->config ?? [];

        $this->applyRuntimeSmtp($config, $channel?->from_name);

        try {
            $subject = $message->subject ?: ($config['default_subject'] ?? 'Doctorato');
            $mailable = new \App\Mail\HubNotificationMail(
                $subject,
                $message->body,
                $message->meta['unsubscribe_url'] ?? null,
                \App\Models\Setting::get('clinic_name_ar', \App\Models\Setting::get('clinic_name_en', 'Doctorato')),
            );

            Mail::to($message->to)->send($mailable);

            return DeliveryResult::ok('smtp', 0.0);
        } catch (\Throwable $e) {
            return DeliveryResult::fail('Email send failed: '.$e->getMessage(), 'smtp');
        }
    }

    /** Override the runtime SMTP mailer from per-clinic config when supplied. */
    private function applyRuntimeSmtp(array $config, ?string $fromName): void
    {
        if (empty($config['host'])) {
            return; // use the app default mailer
        }

        Config::set('mail.mailers.smtp.host', $config['host']);
        Config::set('mail.mailers.smtp.port', $config['port'] ?? 587);
        Config::set('mail.mailers.smtp.username', $config['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $config['password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', $config['encryption'] ?? 'tls');

        if (! empty($config['from_address'])) {
            Config::set('mail.from.address', $config['from_address']);
            Config::set('mail.from.name', $fromName ?: ($config['from_name'] ?? 'Doctorato'));
        }
    }
}
