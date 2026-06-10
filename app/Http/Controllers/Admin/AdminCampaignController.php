<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationCampaign;
use App\Services\Notifications\CampaignService;
use App\Services\Notifications\LeadSegmentResolver;
use App\Services\Notifications\SegmentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCampaignController extends Controller
{
    public function __construct(private SegmentResolver $resolver, private LeadSegmentResolver $leadResolver, private CampaignService $campaigns) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Notifications/Campaigns', [
            'campaigns' => NotificationCampaign::latest()->limit(100)->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'channel' => $c->channel, 'status' => $c->status,
                'audience_count' => $c->audience_count, 'sent_count' => $c->sent_count,
                'ab_enabled' => (bool) $c->ab_enabled,
                'scheduled_at' => $c->scheduled_at?->toIso8601String(),
                'sent_at' => $c->sent_at?->toIso8601String(),
                'created_at' => $c->created_at?->toIso8601String(),
                'ab_results' => $c->ab_enabled ? $this->abResults($c) : null,
            ]),
        ]);
    }

    /** Live audience size for the given rules (used in the builder UI). */
    public function preview(Request $request): JsonResponse
    {
        $rules = $request->input('rules', []);
        $count = ($rules['audience'] ?? 'patients') === 'leads'
            ? $this->leadResolver->count($rules)
            : $this->resolver->count($rules);

        return response()->json(['count' => $count]);
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
            'ab_enabled' => $data['ab_enabled'] ?? false,
            'subject_b' => $data['subject_b'] ?? null,
            'body_ar_b' => $data['body_ar_b'] ?? null,
            'body_en_b' => $data['body_en_b'] ?? null,
            'rules' => $data['rules'] ?? [],
            'created_by' => $request->user()?->id,
            'audience_count' => (($data['rules']['audience'] ?? 'patients') === 'leads')
                ? $this->leadResolver->count($data['rules'] ?? [])
                : $this->resolver->count($data['rules'] ?? []),
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

    /** Per-variant delivery/read counts for an A/B campaign. */
    private function abResults(NotificationCampaign $campaign): array
    {
        $rows = \App\Models\NotificationLog::where('campaign_id', $campaign->id)
            ->whereNotNull('ab_variant')
            ->selectRaw('ab_variant,
                COUNT(*) as total,
                SUM(CASE WHEN status IN (\'sent\',\'delivered\',\'read\') THEN 1 ELSE 0 END) as reached,
                SUM(CASE WHEN status=\'read\' THEN 1 ELSE 0 END) as read_count')
            ->groupBy('ab_variant')->get()->keyBy('ab_variant');

        $out = [];
        foreach (['A', 'B'] as $v) {
            $r = $rows->get($v);
            $reached = (int) ($r->reached ?? 0);
            $reads = (int) ($r->read_count ?? 0);
            $out[$v] = [
                'total' => (int) ($r->total ?? 0),
                'reached' => $reached,
                'reads' => $reads,
                'read_rate' => $reached > 0 ? round($reads / $reached * 100, 1) : null,
            ];
        }
        $out['winner'] = ($out['A']['read_rate'] ?? -1) === ($out['B']['read_rate'] ?? -1)
            ? null : (($out['A']['read_rate'] ?? -1) > ($out['B']['read_rate'] ?? -1) ? 'A' : 'B');

        return $out;
    }

    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'channel' => 'required|in:whatsapp,sms,email',
            'subject' => 'nullable|string|max:255',
            'body_ar' => 'required|string|max:2000',
            'body_en' => 'nullable|string|max:2000',
            'ab_enabled' => 'sometimes|boolean',
            'subject_b' => 'nullable|string|max:255',
            'body_ar_b' => 'nullable|string|max:2000',
            'body_en_b' => 'nullable|string|max:2000',
            'scheduled_at' => 'nullable|date|after:now',
            'rules' => 'nullable|array',
            'rules.gender' => 'nullable|in:male,female',
            'rules.age_min' => 'nullable|integer|min:0|max:120',
            'rules.age_max' => 'nullable|integer|min:0|max:120',
            'rules.created_within_days' => 'nullable|integer|min:1',
            'rules.inactive_days' => 'nullable|integer|min:1',
            'rules.marketing_channel' => 'nullable|in:email,sms,whatsapp',
            // CRM-3: lead-audience campaigns
            'rules.audience' => 'nullable|in:patients,leads',
            'rules.statuses' => 'nullable|array',
            'rules.statuses.*' => 'string|in:'.implode(',', \App\Models\Lead::STATUSES),
            'rules.priority' => 'nullable|integer|in:1,2,3',
            'rules.module' => 'nullable|string|in:derma,dental,pediatric,obgyn,psychiatry,neurology,physiotherapy',
            'rules.lead_source_id' => 'nullable|integer|exists:lead_sources,id',
        ]);
    }
}
