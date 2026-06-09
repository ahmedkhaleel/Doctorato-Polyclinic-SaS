# CRM Overhaul Plan — Medical-Clinic CRM + OpenAI Automation

Design + build plan to bring the CRM module to "integrated, professional,
clinic-grade" across flow, automation (AI via the existing OpenAI layer),
design/UX, and analytics. Grounded in a full code audit (backend + frontend),
not assumptions.

---

## 0. What the audit found (ground truth)

### Already built and mature — do NOT rebuild
- **Backend (13 models, full lifecycle):** `Lead` (9 statuses: new → contacted
  → qualified → appointment_booked → consultation_done → negotiation →
  converted / lost / dormant), `LeadSource` (11 seeded), `LeadScoringRule`
  (17 event rules, applied via `applyToLead`), `LeadAssignmentRule`
  (priority-matched auto-routing), `CommunicationTemplate` ({{var}} rendering,
  ar/en), `FollowUpSequence` + `Step` (7 action types) + `LeadSequenceEnrollment`
  (cron `sequences:process` every 5 min), `LeadActivity` (14 types),
  `LeadFollowUp` (reminders cron), `LeadStageHistory` (auto on status change),
  `CrmCampaign`, `CrmSetting`, `MarketerCommission` relation. Multi-branch
  ready (`StampsBranch`). 4 cron commands. 5 test files.
- **Lead capture:** auto-create from website contact + website booking
  (`LeadService`), dedupe-by-phone/email on create, UTM capture, auto-assign,
  auto-enroll in sequences, admin notify.
- **Conversion:** `convert` → Patient + optional Booking (with slot picker),
  cancels sequences, campaign conversion counters.
- **Frontend (27 pages, ~15k LOC):** CRM Dashboard (hero, 7 KPI, funnel,
  pipeline bars, sources, mini-calendar), Leads Index (table/grid/kanban,
  filters, bulk actions, import/export/merge), Pipeline kanban (SortableJS
  drag-drop + quick-view drawer), Lead Show (1,845 LOC: activity log,
  follow-up scheduler, quick-send templates with preview, convert-with-booking
  modal), Reports (funnel, source/campaign/team performance, loss reasons,
  conversion-time buckets), Sequences/Templates/Scoring/Assignment config pages.
- **AI subsystem (production-grade, generic):** `app/Services/Ai/` —
  `AiManager` (generate/stream/embed + response cache), `OpenAiDriver`
  (provider via `ai_provider` setting, key `ai_openai_api_key` encrypted),
  `AiGate` (global kill-switch `ai_enabled` + per-feature `AiFeatureFlag` +
  monthly budget `ai_monthly_budget_usd` + per-actor rate limit),
  `AiCostMeter` (USD metering + `ai:budget-alert` hourly), **`PhiRedactor`**
  (redacts PHI before the provider, restores after), Features pattern
  (`ClinicalAssistant`, `NoShowPredictor`, `TextAssistant`,
  `EmbeddingService`, …). Queue: database driver on prod.

### Factual gaps (the actual work)
| # | Gap | Evidence |
|---|-----|----------|
| G1 | **Zero AI in CRM** — scoring/messages/triage are static rules | no CRM feature in `Ai/Features`; `CommunicationTemplate` bodies hand-written |
| G2 | **ROI is a placeholder** — `CrmCampaign::roi` returns 0; no revenue attribution per source/campaign | accessor stub; reports show counts/rates only |
| G3 | **Referral commissions not wired** — `Lead.referral_code` + `commissions` relation exist; conversion never creates `MarketerCommission` | `convert()` has no commission logic |
| G4 | **Template variables limited** to name/phone/clinic/date — no service, doctor, appointment, source | `CommunicationService::buildVariables()` |
| G5 | **No phone normalization** — `wa.me` links + SMS can get malformed numbers | `Lead.phone` plain string |
| G6 | **Notification campaigns decoupled** from CRM segments (separate `AdminCampaignController`) | two campaign worlds, no shared audience |
| G7 | **No configurable SLA** — dashboard computes response metrics with hardcoded thresholds | no `CrmSetting` SLA keys |
| G8 | **No CSV-import dedupe** (only manual merge + single-lead AJAX check) | `importCsv` inserts blind |
| G9 | **Design below cockpit standard:** no `useCountUp`, CSS bars instead of `TrendLine`, no action strip, no greeting, monolithic pages (zero shared CRM components), static (unanimated) activity timeline | frontend audit vs `Doctor/Dental/Dashboard.vue` |
| G10 | **No "today work queue"** — staff must hunt across tabs for overdue/hot/new | dashboard mini-calendar only |
| G11 | Kanban lacks drag feedback, column totals/expected value | `Pipeline.vue` |
| G12 | No webhooks for external integrations (Meta lead forms, Zapier) | none registered |

---

## 1. Goals

1. **Complete clinic flow:** capture → qualify → book → treat → convert →
   re-activate, with money attribution end-to-end (source/campaign → invoice).
2. **AI automation on the existing OpenAI layer** — budget-gated, PHI-redacted,
   feature-flagged, cached; degrade gracefully when AI is off.
3. **Cockpit-grade UI/UX** matching the dental/neuropsych standard, plus a
   daily work queue so a marketer/secretary never wonders "what now?".
4. Everything idempotent-migrated, tested per phase, guard-test clean,
   module-safe (CRM has no module gate — it is core).

## 2. Non-goals (explicitly out)
- No WhatsApp Business API automation (stays click-to-chat + hub) — provider
  contracts are an owner decision; the plan keeps a clean seam for it.
- No ML model training; "prediction" = LLM/heuristic over existing data.
- No rewrite of the 27 pages — surgical uplift + component extraction.

---

## 3. Phases

### CRM-0 — Design uplift to cockpit standard (frontend-only, zero risk)
- Dashboard: `useCountUp` on KPIs, replace CSS bar trend with shared
  `TrendLine`, add greeting hero variant, **action strip** (overdue follow-ups
  · hot uncontacted · stalled negotiation · SLA breaches) with drill-links.
- Extract shared components: `Crm/LeadCard.vue`, `Crm/ActivityTimeline.vue`
  (staggered reveal), `Crm/StatusBadge.vue`, `Crm/PriorityDot.vue` — replace
  inlined copies in Index/Pipeline/Show.
- Kanban polish: drag glow/ghost contrast, per-column count + sum of expected
  value, reduced-motion aware.
- WhatsApp click-to-chat icon next to every phone (uses existing wa.me flow).
- Tests: page guards + existing CRM tests stay green (design-only).

### CRM-1 — Clinic flow completion (backend)
- **Revenue attribution:** on conversion, stamp `lead_id`-linked patient; a
  `CrmRevenueService` aggregates invoices of converted patients per
  source/campaign/period → real `roi`, `revenue`, `cost_per_acquisition` in
  Reports + Dashboard. (Read-only aggregation; no schema change beyond an
  index.)
- **Referral commissions:** `convert()` auto-creates `MarketerCommission`
  when `referral_code` resolves to a marketer (idempotent; configurable rate
  via `CrmSetting`).
- **Phone normalization:** lightweight EG/SA-aware normalizer on create/import
  (strip spaces/dashes, 00→+, local→E.164-ish); stored normalized, dedupe uses
  it; invalid numbers flagged on the lead (banner), never block creation.
- **Template variables v2:** add interested_service(s), source name, assigned
  doctor, next appointment date/time, module display name.
- **SLA settings:** `crm_sla_first_contact_minutes`, `crm_sla_response_minutes`
  in `CrmSetting` + Settings UI; dashboard/action-strip read them.
- **Import dedupe:** importCsv matches normalized phone/email → updates or
  skips (report: created/updated/skipped) instead of blind insert.
- Tests: revenue attribution math, commission idempotency, normalizer table,
  import dedupe counts.

### CRM-2 — AI features (the OpenAI link) — all via existing `AiManager`
Each is a class in `app/Services/Ai/Features/`, gated by its own
`AiFeatureFlag`, metered, PHI-redacted, cached, and **optional** (UI hides
cleanly when `ai_enabled` off or feature off — exactly like clinical AI).

| Feature | What it does | Surface |
|---|---|---|
| `LeadSummarizer` | 3-bullet Arabic/English summary of the lead (profile + activities + stage history) + **next-best-action** suggestion | Show page card + Pipeline quick-view |
| `LeadMessageDrafter` | Drafts a personalized WhatsApp/SMS/Email in the lead's language for a chosen goal (follow-up, offer, no-show recovery, re-activation); editor can tweak before send | Quick-send composer "اقترح بالذكاء" button |
| `LeadIntentScorer` | Classifies intent/urgency from notes + inbound messages → adds a **bounded** AI score component (±15) with a reason logged as `LeadActivity` | on activity log + nightly batch over active pipeline |
| `InboundTriage` | New website contact/lead: extract module interest, urgency, suggested priority → sets `module`, `priority` suggestion (human-confirmable) | LeadService capture path + badge on new leads |
| `DormancyRisk` | Weekly batch: flags in-pipeline leads likely to go dormant *before* the 30-day rule (recency/frequency heuristics + LLM read of last activities) → action-strip list "معرّضون للفقد" | cron + dashboard strip |
- Caching via AiManager's response cache; batches capped (e.g. 50 leads/run);
  all writes are suggestions/annotations — **never silent status changes**.
- Settings UI: CRM section in the existing AI settings page (flags per feature).
- Tests: each feature with a fake driver (NullDriver pattern), gate-off
  behavior, bounded-score invariant, no-PHI-leak assertion via PhiRedactor hook.

### CRM-3 — Automation integration
- **Campaign↔CRM bridge:** notification campaigns can target a *lead segment*
  (status/priority/source/module filter) — sends logged back as
  `LeadActivity`, enabling sequence `stop_on_reply` and attribution.
- **Sequence step `send_ai_message`:** new action type that calls
  `LeadMessageDrafter` then routes through `CommunicationService` (flag-gated;
  falls back to template when AI off).
- **Webhooks (outbound):** signed POST on `lead.created`, `lead.status_changed`,
  `lead.converted` to URLs configured in CRM settings (retry ×3, log to hub) —
  the Zapier/Meta-forms seam.
- Tests: segment targeting counts, AI-step fallback, webhook signing + retry.

### CRM-4 — "Today" work queue (the usability core)
- New `/admin/crm/today` (default CRM landing): one prioritized list —
  overdue follow-ups → SLA-breaching new leads → hot uncontacted → AI
  dormancy-risk → today's follow-ups — each row with inline actions (call
  logged, WhatsApp, complete/reschedule, snooze) without leaving the page.
- Keyboard-friendly (j/k next/prev, enter opens drawer) + the quick-view drawer
  reused from Pipeline.
- Secretary panel gets a read/act subset (`SecretaryCrmController` extension).
- Tests: queue ordering rules, inline action endpoints.

### CRM-5 — Analytics depth
- Cohort view (month-of-creation × conversion), configurable-SLA breach trend,
  source ROI table now showing **real revenue** (from CRM-1), AI usage/cost
  card for CRM features (reads `AiCostMeter`).
- Tests: cohort math on fixture data.

---

## 4. Order, size, and review gates

| Phase | Size | Risk | Gate |
|---|---|---|---|
| CRM-0 | M (frontend) | none (visual) | screenshots + guards green |
| CRM-1 | M | low | money math reviewed vs sample invoices |
| CRM-2 | L | low (flag-gated) | features OFF by default; demo with flags on |
| CRM-3 | M | medium (sends) | AI-step behind flag; webhook secrets owner-set |
| CRM-4 | M | low | usability pass with real demo data |
| CRM-5 | S | none | — |

Each phase: branch → tests → pint → full suite → ff-merge → push → CI/Deploy
green, like PT/F/G series.

## 5. Constraints honored
- Shared cPanel: AI calls queued (database queue) or request-scoped with
  short timeouts; batches bounded; budget gate already hard-stops spend.
- `ai_openai_api_key` stays an **owner-entered encrypted Setting** (never in
  code/env-committed) — CLAUDE.md rule.
- PHI: every CRM prompt passes `PhiRedactor` (names/phones masked) — leads are
  marketing data but may reference medical interest; redaction stays on.
- Demo-safety: all AI features no-op gracefully when key absent.

## 6. Open decisions for the owner (not blockers)
1. WhatsApp Business API provider (when ready) — plugs into the
  `CommunicationService::sendWhatsApp` seam.
2. SLA targets (defaults proposed: first contact 60 min, response 120 min).
3. Marketer commission default rate for referral conversions.
