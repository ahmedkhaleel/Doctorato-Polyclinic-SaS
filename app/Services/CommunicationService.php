<?php

namespace App\Services;

use App\Mail\LeadTemplateMail;
use App\Models\CommunicationTemplate;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles communication with leads via WhatsApp, Email, and SMS channels.
 */
class CommunicationService
{
    /**
     * Send a message to a lead via the specified channel.
     *
     * @return array{success: bool, message: string, redirect_url: ?string}
     */
    public static function send(
        Lead $lead,
        CommunicationTemplate $template,
        string $channel,
        string $language = 'en',
        ?int $performedBy = null,
    ): array {
        $variables = static::buildVariables($lead);
        $renderedBody = $template->renderBody($language, $variables);
        $renderedSubject = $template->subject
            ? static::replaceVariables($template->subject, $variables)
            : $template->name;

        $result = match ($channel) {
            'whatsapp' => static::sendWhatsApp($lead, $renderedBody),
            'email' => static::sendEmail($lead, $renderedSubject, $renderedBody),
            'sms' => static::sendSms($lead, $renderedBody),
            default => ['success' => false, 'message' => 'Unknown channel.', 'redirect_url' => null],
        };

        // Log activity regardless of send success
        LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => $channel,
            'subject' => $template->name,
            'description' => $renderedBody,
            'direction' => 'outbound',
            'performed_by' => $performedBy ?? auth()->id(),
            'metadata' => [
                'template_id' => $template->id,
                'language' => $language,
                'send_result' => $result['success'] ? 'sent' : 'failed',
            ],
        ]);

        // Mirror into the unified hub log for cross-channel reporting (Delivery Log,
        // analytics, cost) — visibility only; CRM keeps its own delivery + threads.
        static::logToHub($lead, $channel, $renderedBody, $renderedSubject, $result);

        // Increment template usage
        $template->incrementUsage();

        // Update last contacted
        $lead->markAsContacted();

        return $result;
    }

    /**
     * Record a CRM lead message in the unified notification log (reporting only).
     * WhatsApp here is click-to-chat (a link is generated) so it's logged as sent.
     */
    protected static function logToHub(Lead $lead, string $channel, string $body, ?string $subject, array $result): void
    {
        try {
            $to = $channel === 'email' ? $lead->email : $lead->phone;
            $sent = (bool) ($result['success'] ?? false);

            NotificationLog::create([
                'recipient_type' => $lead->getMorphClass(),
                'recipient_id' => $lead->getKey(),
                'to' => $to,
                'channel' => $channel,
                'provider' => 'crm',
                'event_key' => 'crm.message',
                'status' => $sent ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED,
                'cost' => ($channel === 'sms' && $sent) ? SmsService::estimateCost($body) : 0,
                'error' => $sent ? null : ($result['message'] ?? null),
                'meta' => ['body' => $body, 'subject' => $subject, 'source' => 'crm'],
                'sent_at' => $sent ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[crm.hub-log] failed', ['lead_id' => $lead->id ?? null, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Build template variables from a lead.
     */
    public static function buildVariables(Lead $lead): array
    {
        $clinicName = Setting::get('clinic_name_en', 'Doctorato Polyclinic');

        // CRM-1 — variables v2: service / source / assignee / follow-up / module
        // context so templates can personalize beyond name+phone.
        $moduleNames = [
            'derma' => 'الجلدية', 'dental' => 'الأسنان', 'pediatric' => 'الأطفال',
            'obgyn' => 'النساء والتوليد', 'psychiatry' => 'الطب النفسي',
            'neurology' => 'المخ والأعصاب', 'physiotherapy' => 'العلاج الطبيعي',
        ];
        $services = $lead->interested_services;

        return [
            'name' => $lead->full_name,
            'first_name' => explode(' ', $lead->full_name)[0],
            'phone' => $lead->phone ?? '',
            'email' => $lead->email ?? '',
            'clinic_name' => $clinicName,
            'clinic_phone' => Setting::get('clinic_phone', ''),
            'date' => now()->format('d/m/Y'),
            'service' => is_array($services) ? implode('، ', $services) : (string) ($services ?? ''),
            'source' => $lead->source?->name_ar ?? $lead->source?->name_en ?? '',
            'assigned_to' => $lead->assignedUser?->name ?? '',
            'next_follow_up' => $lead->next_follow_up_at?->format('d/m/Y H:i') ?? '',
            'module' => $moduleNames[$lead->module] ?? ($lead->module ?? ''),
        ];
    }

    /**
     * Generate WhatsApp wa.me redirect link.
     */
    protected static function sendWhatsApp(Lead $lead, string $body): array
    {
        if (! $lead->phone) {
            return ['success' => false, 'message' => 'Lead has no phone number.', 'redirect_url' => null];
        }

        $phone = preg_replace('/[^0-9]/', '', $lead->phone);
        $redirectUrl = 'https://wa.me/'.$phone.'?text='.urlencode($body);

        return [
            'success' => true,
            'message' => 'WhatsApp message ready. Opening WhatsApp...',
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * Send an email to the lead using Laravel Mail.
     */
    protected static function sendEmail(Lead $lead, string $subject, string $body): array
    {
        if (! $lead->email) {
            return ['success' => false, 'message' => 'Lead has no email address.', 'redirect_url' => null];
        }

        try {
            \App\Jobs\SendEmailJob::dispatch($lead->email, new LeadTemplateMail($subject, $body, $lead->full_name), 'crm_email');

            return [
                'success' => true,
                'message' => 'Email queued for delivery.',
                'redirect_url' => null,
            ];
        } catch (\Exception $e) {
            Log::error('CRM Email failed', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue email: '.$e->getMessage(),
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Send an SMS to the lead via the configured SMS provider.
     */
    protected static function sendSms(Lead $lead, string $body): array
    {
        if (! $lead->phone) {
            return ['success' => false, 'message' => 'Lead has no phone number.', 'redirect_url' => null];
        }

        \App\Jobs\SendSmsJob::dispatch($lead->phone, $body, null, 'crm_sms');

        return [
            'success' => true,
            'message' => 'SMS queued for delivery.',
            'redirect_url' => null,
        ];
    }

    /**
     * Replace {{variable}} placeholders in a string.
     */
    protected static function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{'.$key.'}}', $value, $text);
        }

        return $text;
    }
}
