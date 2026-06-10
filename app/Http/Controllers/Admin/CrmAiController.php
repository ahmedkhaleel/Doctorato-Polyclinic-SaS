<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\CrmAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM-2 — AI endpoints for the leads pipeline. Every call goes through the
 * gated AiManager (kill-switch, per-feature flag, budget, rate limit, PHI
 * redaction); a blocked call returns 422 with a human reason, never a 500.
 */
class CrmAiController extends Controller
{
    public function __construct(private readonly CrmAssistant $assistant) {}

    /** Lead digest + next-best-actions for the Show page card. */
    public function summary(Request $request, Lead $lead): JsonResponse
    {
        return $this->guarded(function () use ($request, $lead) {
            $result = $this->assistant->summarizeLead($lead, $request->input('locale', app()->getLocale()), $this->actorOptions($request));

            return ['text' => $result->text, 'model' => $result->model];
        });
    }

    /** Draft an outreach message (quick-send "اقترح بالذكاء"). */
    public function draft(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:whatsapp,sms,email',
            'tone' => 'nullable|string|in:friendly,formal,urgent',
            'locale' => 'nullable|string|in:ar,en',
        ]);

        return $this->guarded(function () use ($data, $lead, $request) {
            $result = $this->assistant->draftMessage(
                $lead,
                $data['channel'],
                $data['tone'] ?? 'friendly',
                $data['locale'] ?? app()->getLocale(),
                $this->actorOptions($request),
            );

            return ['text' => $result->text, 'model' => $result->model];
        });
    }

    /**
     * Apply a bounded AI score adjustment. The change is visible: it lands as
     * a LeadActivity with the model's reason — never a silent mutation.
     */
    public function scoreIntent(Request $request, Lead $lead): JsonResponse
    {
        return $this->guarded(function () use ($request, $lead) {
            $verdict = $this->assistant->scoreIntent($lead, $this->actorOptions($request));

            if ($verdict['adjustment'] !== 0) {
                $lead->addScore($verdict['adjustment']);

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'system',
                    'subject' => sprintf('AI intent score %+d → %d', $verdict['adjustment'], $lead->fresh()->score),
                    'description' => $verdict['reason'],
                    'metadata' => ['ai' => true, 'model' => $verdict['model'], 'adjustment' => $verdict['adjustment']],
                    'performed_by' => $request->user()?->id,
                ]);
            }

            return [
                'adjustment' => $verdict['adjustment'],
                'reason' => $verdict['reason'],
                'score' => $lead->fresh()->score,
            ];
        });
    }

    /** Classify a raw inbound message (module / urgency / priority / summary). */
    public function triage(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => 'required|string|max:4000']);

        return $this->guarded(fn () => $this->assistant->triageInbound($data['message'], $this->actorOptions($request)));
    }

    private function actorOptions(Request $request): array
    {
        return [
            'rate_key' => 'user:'.$request->user()?->id,
            'actor' => ['type' => 'user', 'id' => $request->user()?->id],
        ];
    }

    /** Uniform success/blocked envelope shared by all endpoints. */
    private function guarded(callable $cb): JsonResponse
    {
        try {
            $payload = $cb();
        } catch (AiUnavailableException $e) {
            return response()->json([
                'ok' => false,
                'reason' => $e->reason,
                'message' => $this->reasonMessage($e->reason),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'reason' => 'error', 'message' => 'تعذر إكمال طلب الذكاء الاصطناعي. / AI request failed.'], 500);
        }

        return response()->json(['ok' => true] + $payload);
    }

    private function reasonMessage(string $reason): string
    {
        return match ($reason) {
            'disabled' => 'الذكاء الاصطناعي معطّل من الإعدادات. / AI is disabled in settings.',
            'feature_off' => 'هذه الميزة معطّلة. فعّلها من صفحة ميزات الذكاء الاصطناعي. / This AI feature is disabled.',
            'over_budget' => 'تم بلوغ ميزانية الذكاء الاصطناعي الشهرية. / Monthly AI budget reached.',
            'rate_limited' => 'طلبات كثيرة، يرجى الإبطاء قليلًا. / Too many requests, slow down.',
            'no_key' => 'مفتاح OpenAI غير مُهيّأ. / OpenAI key not configured.',
            default => 'الذكاء الاصطناعي غير متاح حاليًا. / AI is currently unavailable.',
        };
    }
}
