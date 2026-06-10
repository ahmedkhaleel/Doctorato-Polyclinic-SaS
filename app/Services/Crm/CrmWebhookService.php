<?php

namespace App\Services\Crm;

use App\Jobs\SendCrmWebhook;
use App\Models\CrmSetting;
use App\Models\Lead;

/**
 * CRM-3 — signed outbound webhooks for lead lifecycle events
 * (lead.created / lead.status_changed / lead.converted).
 *
 * Configured from CRM Settings: webhooks_enabled (default OFF), webhook_url,
 * webhook_secret. Delivery is queued with 3 retries; the payload is signed
 * HMAC-SHA256 in the X-Doctorato-Signature header so the receiver (n8n, Make,
 * Zapier, custom endpoint) can verify authenticity.
 */
class CrmWebhookService
{
    public static function dispatch(string $event, Lead $lead, array $extra = []): void
    {
        if (! filter_var(CrmSetting::get('webhooks_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $url = trim((string) CrmSetting::get('webhook_url', ''));
        if ($url === '' || ! str_starts_with($url, 'http')) {
            return;
        }

        SendCrmWebhook::dispatch($url, $event, array_merge([
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'lead' => [
                'id' => $lead->id,
                'full_name' => $lead->full_name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'status' => $lead->status,
                'priority' => $lead->priority,
                'score' => $lead->score,
                'module' => $lead->module,
                'source' => $lead->source?->slug,
                'assigned_to' => $lead->assigned_to,
                'patient_id' => $lead->patient_id,
                'created_at' => $lead->created_at?->toIso8601String(),
            ],
        ], $extra));
    }
}
