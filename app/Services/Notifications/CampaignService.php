<?php

namespace App\Services\Notifications;

use App\Models\NotificationCampaign;

/**
 * Sends a campaign to its resolved audience via the marketing campaign.message
 * event. Consent, quiet-hours and frequency-cap are enforced downstream by the
 * hub, so opted-out / capped recipients are skipped automatically.
 */
class CampaignService
{
    public function __construct(private SegmentResolver $resolver) {}

    public function send(NotificationCampaign $campaign): int
    {
        $campaign->update(['status' => NotificationCampaign::STATUS_SENDING]);

        $channel = $campaign->channel;
        $dispatched = 0;

        $ab = $campaign->ab_enabled && ! empty($campaign->body_ar_b);

        $this->resolver->query($campaign->rules ?? [])->chunkById(200, function ($patients) use ($campaign, $channel, $ab, &$dispatched) {
            foreach ($patients as $p) {
                $to = $channel === 'email' ? $p->email : $p->phone;
                if (! $to) {
                    continue;
                }

                // A/B: alternate variants across the audience by send index.
                $variant = $ab ? ($dispatched % 2 === 0 ? 'A' : 'B') : null;
                $en = $p->preferred_language === 'en';
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
