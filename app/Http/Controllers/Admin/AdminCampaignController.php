<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationCampaign;
use App\Services\Notifications\CampaignService;
use App\Services\Notifications\SegmentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCampaignController extends Controller
{
    public function __construct(private SegmentResolver $resolver, private CampaignService $campaigns) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Notifications/Campaigns', [
            'campaigns' => NotificationCampaign::latest()->limit(100)->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'channel' => $c->channel, 'status' => $c->status,
                'audience_count' => $c->audience_count, 'sent_count' => $c->sent_count,
                'scheduled_at' => $c->scheduled_at?->toIso8601String(),
                'sent_at' => $c->sent_at?->toIso8601String(),
                'created_at' => $c->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /** Live audience size for the given rules (used in the builder UI). */
    public function preview(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->resolver->count($request->input('rules', []))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCampaign($request);

        $campaign = NotificationCampaign::create([
            'name' => $data['name'],
            'channel' => $data['channel'],
            'subject' => $data['subject'] ?? null,
            'body_ar' => $data['body_ar'],
            'body_en' => $data['body_en'] ?? null,
            'rules' => $data['rules'] ?? [],
            'created_by' => $request->user()?->id,
            'audience_count' => $this->resolver->count($data['rules'] ?? []),
            'status' => ! empty($data['scheduled_at']) ? NotificationCampaign::STATUS_SCHEDULED : NotificationCampaign::STATUS_DRAFT,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        // "Send now" when explicitly requested and not scheduled.
        if ($request->boolean('send_now') && ! $campaign->scheduled_at) {
            $this->campaigns->send($campaign);

            return back()->with('success', __('Campaign sent.'));
        }

        return back()->with('success', __('Campaign saved.'));
    }

    public function send(NotificationCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === NotificationCampaign::STATUS_SENT) {
            return back()->with('error', __('Campaign already sent.'));
        }
        $count = $this->campaigns->send($campaign);

        return back()->with('success', __('Campaign sent to :n recipients.', ['n' => $count]));
    }

    public function destroy(NotificationCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('success', __('Campaign deleted.'));
    }

    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'channel' => 'required|in:whatsapp,sms,email',
            'subject' => 'nullable|string|max:255',
            'body_ar' => 'required|string|max:2000',
            'body_en' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date|after:now',
            'rules' => 'nullable|array',
            'rules.gender' => 'nullable|in:male,female',
            'rules.age_min' => 'nullable|integer|min:0|max:120',
            'rules.age_max' => 'nullable|integer|min:0|max:120',
            'rules.created_within_days' => 'nullable|integer|min:1',
            'rules.inactive_days' => 'nullable|integer|min:1',
            'rules.marketing_channel' => 'nullable|in:email,sms,whatsapp',
        ]);
    }
}
