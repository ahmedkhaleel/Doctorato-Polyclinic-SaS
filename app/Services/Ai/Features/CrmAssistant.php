<?php

namespace App\Services\Ai\Features;

use App\Models\Lead;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;

/**
 * CRM-2 — AI capabilities for the leads pipeline, on the existing gated layer
 * (kill-switch, per-feature flags, budget, rate-limit, PHI redaction).
 *
 * Features (all default OFF in AiFeatureFlag):
 *  - crm_lead_summary   summarizeLead()  — timeline digest + next-best-actions
 *  - lead_reply         draftMessage()   — channel/tone-aware outreach draft
 *  - crm_intent_score   scoreIntent()    — bounded ±15 score adjustment + reason
 *  - crm_inbound_triage triageInbound()  — classify an inbound message
 *  - crm_dormancy_risk  dormancyRank()   — batch-rank pipeline leads at risk
 *
 * The AI NEVER changes a lead status: scoreIntent returns a clamped suggestion
 * the controller applies via Lead::addScore + a visible LeadActivity.
 */
class CrmAssistant
{
    public const INTENT_MAX_ADJUSTMENT = 15;

    public function __construct(private readonly AiManager $ai) {}

    public function summarizeLead(Lead $lead, string $locale = 'ar', array $options = []): AiResult
    {
        $context = $this->leadContext($lead);

        $system = $locale === 'ar'
            ? 'أنت مساعد CRM لعيادة طبية. من سياق العميل المحتمل، اكتب: (1) ملخصاً موجزاً في 2-3 جمل لوضع العميل الحالي، (2) ثم قائمة "الخطوات التالية المقترحة" من 2-3 إجراءات عملية محددة (مثل: اتصال متابعة، عرض سعر، حجز موعد). لا تخترع معلومات غير موجودة في السياق، ولا تقدم أي ادعاءات طبية.'
            : 'You are a CRM assistant for a medical clinic. From the lead context, write: (1) a 2-3 sentence digest of where this lead stands, (2) then a "Suggested next steps" list of 2-3 concrete actions (e.g. follow-up call, price quote, book appointment). Never invent facts not in the context; no medical claims.';

        return $this->ai->generate('crm_lead_summary', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Lead context (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        ], $options);
    }

    public function draftMessage(Lead $lead, string $channel, string $tone, string $locale = 'ar', array $options = []): AiResult
    {
        $context = $this->leadContext($lead);

        $system = $locale === 'ar'
            ? "أنت مساعد مبيعات ودود لعيادة طبية. اكتب رسالة {$channel} قصيرة بنبرة {$tone} لعميل محتمل، مخصصة حسب سياقه (خدماته المهتم بها، مرحلته، آخر تواصل). بدون ادعاءات طبية وبدون وعود بأسعار غير مذكورة. أعد نص الرسالة فقط جاهزاً للإرسال."
            : "You are a friendly clinic sales assistant. Write a short {$channel} message in a {$tone} tone to a prospective patient, personalized to their context (interested services, stage, last contact). No medical claims, no price promises not in the context. Return ONLY the ready-to-send message text.";

        return $this->ai->generate('lead_reply', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Lead context (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE)],
        ], $options);
    }

    /**
     * Suggest a bounded score adjustment from the lead's engagement signals.
     *
     * @return array{adjustment:int, reason:string, model:string}
     */
    public function scoreIntent(Lead $lead, array $options = []): array
    {
        $context = $this->leadContext($lead, withActivities: true);

        $system = 'You analyse CRM lead engagement for a medical clinic. From the context, output STRICT JSON only: '
            .'{"adjustment": <integer -15..15>, "reason": "<one short sentence>"} — positive when signals show buying intent '
            .'(responds fast, asks about prices/appointments, multiple inbound contacts), negative when signals show disengagement '
            .'(repeated no-answers, stale, refused). No other text.';

        $result = $this->ai->generate('crm_intent_score', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode($context, JSON_UNESCAPED_UNICODE)],
        ], $options + ['temperature' => 0]);

        $parsed = $this->parseJson($result->text);
        $adjustment = (int) ($parsed['adjustment'] ?? 0);
        // Hard clamp — the model can never move a score more than ±15.
        $adjustment = max(-self::INTENT_MAX_ADJUSTMENT, min(self::INTENT_MAX_ADJUSTMENT, $adjustment));

        return [
            'adjustment' => $adjustment,
            'reason' => (string) ($parsed['reason'] ?? ''),
            'model' => $result->model,
        ];
    }

    /**
     * Classify a raw inbound message (contact form, WhatsApp paste…).
     *
     * @return array{module:?string, urgency:string, priority:int, summary:string, model:string}
     */
    public function triageInbound(string $message, array $options = []): array
    {
        $system = 'You triage inbound messages for a medical polyclinic CRM. Output STRICT JSON only: '
            .'{"module": <one of derma|dental|pediatric|obgyn|psychiatry|neurology|physiotherapy or null>, '
            .'"urgency": <"high"|"normal"|"low">, "priority": <1 hot|2 warm|3 cold>, '
            .'"summary": "<one sentence: what they want>"} . '
            .'High urgency = asks for an appointment now / pain / pregnancy concern. No other text.';

        $result = $this->ai->generate('crm_inbound_triage', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $message],
        ], $options + ['temperature' => 0]);

        $parsed = $this->parseJson($result->text);
        $module = $parsed['module'] ?? null;
        $modules = ['derma', 'dental', 'pediatric', 'obgyn', 'psychiatry', 'neurology', 'physiotherapy'];

        return [
            'module' => in_array($module, $modules, true) ? $module : null,
            'urgency' => in_array($parsed['urgency'] ?? null, ['high', 'normal', 'low'], true) ? $parsed['urgency'] : 'normal',
            'priority' => in_array((int) ($parsed['priority'] ?? 0), [1, 2, 3], true) ? (int) $parsed['priority'] : 2,
            'summary' => (string) ($parsed['summary'] ?? ''),
            'model' => $result->model,
        ];
    }

    /**
     * Rank pre-filtered pipeline leads by dormancy risk in ONE batched call.
     *
     * @param  array<int,array<string,mixed>>  $rows  heuristic snapshots keyed by lead id
     * @return array<int,array{lead_id:int, risk:string, reason:string}>
     */
    public function dormancyRank(array $rows, array $options = []): array
    {
        if ($rows === []) {
            return [];
        }

        $system = 'You assess which CRM leads of a medical clinic are about to go dormant (lost interest). '
            .'For EACH lead in the JSON array, output STRICT JSON only — an array of '
            .'{"lead_id": <id>, "risk": <"high"|"medium"|"low">, "reason": "<one short sentence>"}. '
            .'High risk = long silence after engagement, overdue follow-ups, stalled mid-pipeline. No other text.';

        $result = $this->ai->generate('crm_dormancy_risk', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode(array_values($rows), JSON_UNESCAPED_UNICODE)],
        ], $options + ['temperature' => 0]);

        $parsed = $this->parseJson($result->text);
        $ids = array_column($rows, 'lead_id');
        $out = [];
        foreach (is_array($parsed) ? $parsed : [] as $item) {
            $id = (int) ($item['lead_id'] ?? 0);
            if (! in_array($id, $ids, true)) {
                continue; // the model may not invent leads
            }
            $out[] = [
                'lead_id' => $id,
                'risk' => in_array($item['risk'] ?? null, ['high', 'medium', 'low'], true) ? $item['risk'] : 'medium',
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * Compact, identifier-light context (PhiRedactor still runs as a second
     * net inside AiManager). Counts + stages, not raw personal data.
     */
    public function leadContext(Lead $lead, bool $withActivities = true): array
    {
        $lead->loadMissing(['source:id,name_en', 'activities' => fn ($q) => $q->latest()->limit(8)]);

        $context = [
            'status' => $lead->status,
            'priority' => $lead->priority,
            'score' => $lead->score,
            'module' => $lead->module,
            'source' => $lead->source?->name_en,
            'interested_services' => $lead->interested_services,
            'days_in_pipeline' => (int) $lead->created_at->diffInDays(now()),
            'days_since_last_contact' => $lead->last_contacted_at ? (int) $lead->last_contacted_at->diffInDays(now()) : null,
            'first_contacted' => (bool) $lead->first_contacted_at,
            'next_follow_up_overdue' => (bool) ($lead->next_follow_up_at && $lead->next_follow_up_at->isPast()),
            'total_activities' => $lead->activities()->count(),
            'inbound_activities' => $lead->activities()->where('direction', 'inbound')->count(),
        ];

        if ($withActivities) {
            $context['recent_activities'] = $lead->activities
                ->map(fn ($a) => ['type' => $a->type, 'direction' => $a->direction, 'subject' => $a->subject, 'days_ago' => (int) $a->created_at->diffInDays(now())])
                ->all();
        }

        return $context;
    }

    /** Tolerant JSON extraction (models sometimes wrap output in fences). */
    private function parseJson(string $text): array
    {
        $clean = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? '');
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Last resort: first {...} or [...] block in the text.
        if (preg_match('/(\{.*\}|\[.*\])/s', $clean, $m)) {
            $decoded = json_decode($m[1], true);
        }

        return is_array($decoded) ? $decoded : [];
    }
}
