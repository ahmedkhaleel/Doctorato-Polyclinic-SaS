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

        $this->resolver->query($campaign->rules ?? [])->chunkById(200, function ($patients) use ($campaign, $channel, &$dispatched) {
            foreach ($patients as $p) {
                $to = $channel === 'email' ? $p->email : $p->phone;
                if (! $to) {
                    continue;
                }
                $body = ($p->preferred_language === 'en' && $campaign->body_en) ? $campaign->body_en : $campaign->body_ar;

                Notifier::event('campaign.message', $p, [
                    'to' => $to,
                    'body' => $body,
                    'subject' => $campaign->subject,
                    'name' => $p->full_name,
                    'meta' => ['campaign_id' => $campaign->id],
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
