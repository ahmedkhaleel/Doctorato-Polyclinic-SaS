<?php

namespace App\Services\Notifications;

use App\Models\LeadActivity;
use App\Models\NotificationCampaign;

/**
 * Sends a campaign to its resolved audience via the marketing campaign.message
 * event. Consent, quiet-hours and frequency-cap are enforced downstream by the
 * hub, so opted-out / capped recipients are skipped automatically.
 *
 * CRM-3: rules.audience === 'leads' targets the CRM pipeline instead of
 * patients (resolved by LeadSegmentResolver); each lead send is also logged
 * on the lead timeline so the CRM shows the touch.
 */
class CampaignService
{
    public function __construct(
        private SegmentResolver $resolver,
        private LeadSegmentResolver $leadResolver,
    ) {}

    public function send(NotificationCampaign $campaign): int
    {
        $campaign->update(['status' => NotificationCampaign::STATUS_SENDING]);

        $channel = $campaign->channel;
        $dispatched = 0;

        $ab = $campaign->ab_enabled && ! empty($campaign->body_ar_b);
        $rules = $campaign->rules ?? [];
        $isLeadAudience = ($rules['audience'] ?? 'patients') === 'leads';

        $query = $isLeadAudience ? $this->leadResolver->query($rules) : $this->resolver->query($rules);

        $query->chunkById(200, function ($recipients) use ($campaign, $channel, $ab, $isLeadAudience, &$dispatched) {
            foreach ($recipients as $p) {
                $to = $channel === 'email' ? $p->email : $p->phone;
                if (! $to) {
                    continue;
                }

                // A/B: alternate variants across the audience by send index.
                $variant = $ab ? ($dispatched % 2 === 0 ? 'A' : 'B') : null;
                $en = ($p->preferred_language ?? 'ar') === 'en';
                if ($variant === 'B') {
                    $body = ($en && $campaign->body_en_b) ? $campaign->body_en_b : $campaign->body_ar_b;
                    $subject = $campaign->subject_b ?: $campaign->subject;
                } else {
                    $body = ($en && $campaign->body_en) ? $campaign->body_en : $campaign->body_ar;
                    $subject = $campaign->subject;
                }

                Notifier::event('campaign.message', $p, [
                    'to' => $to,
                    'body' => $body,
                    'subject' => $subject,
                    'name' => $p->full_name,
                    'meta' => array_filter(['campaign_id' => $campaign->id, 'variant' => $variant]),
                ], [$channel]);

                // CRM-3: a campaign touch must show on the lead timeline.
                if ($isLeadAudience) {
                    LeadActivity::create([
                        'lead_id' => $p->id,
                        'type' => $channel === 'email' ? 'email' : ($channel === 'sms' ? 'sms' : 'whatsapp'),
                        'subject' => "Campaign: {$campaign->name}",
                        'description' => $body,
                        'direction' => 'outbound',
                        'metadata' => array_filter(['automated' => true, 'hub_campaign_id' => $campaign->id, 'variant' => $variant]),
                    ]);
                }

                $dispatched++;
            }
        });

        $campaign->update([
            'status' => NotificationCampaign::STATUS_SENT,
            'sent_at' => now(),
            'sent_count' => $dispatched,
            'audience_count' => $dispatched,
        ]);

        return $dispatched;
    }
}
