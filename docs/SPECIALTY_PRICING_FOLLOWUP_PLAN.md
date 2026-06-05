# Unified Specialty Pricing & Follow-up — Design & Build Plan

**Status:** Proposed for review (planning first, per owner request) · **Date:** 2026-06-06
**Goal:** Every medical specialty module (derma, dental, pediatric, obgyn,
psychiatry, neurology) must have a **complete, uniform** pricing + follow-up
configuration:
- consultant-grade consultation fee + specialist-grade consultation fee
  (priced separately by `doctor.doctor_type`),
- a base consultation fee (fallback),
- a follow-up fee,
- a follow-up window (how many days after a visit a return counts as a
  follow-up),
- per-doctor fee/commission overrides,
- and bookable from **secretary, admin, and the public website** when the
  module is enabled (and hidden everywhere when disabled).

---

## 1) Current state (verified from code)

| Setting | derma | dental | pediatric | obgyn | psychiatry | neurology |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Consultant vs Specialist fee | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Base consultation fee | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Follow-up fee | ✅ (global) | ✅ (global) | ✅ (own) | ❌ | ❌ | ❌ |
| Follow-up window | ⚠️ one global `followup_window_days`=15 for the whole system | | | ❌ | ❌ | ❌ |
| Auto follow-up eligibility | ✅ derma only | ❌ | ❌ | ❌ | ❌ | ❌ |
| Bookable (secretary/admin/web) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

### Where values live today (inconsistent — the core problem)
- **derma / dental:** global `Setting` keys — `dermatology_consultant_fee`,
  `dermatology_specialist_fee`, `cosmetic_consultation_fee`,
  `dental_consultant_fee`, `dental_specialist_fee`, plus global `followup_fee`
  + `followup_window_days`. Edited on the **Settings → consultation** page.
- **pediatric:** global `Setting` `pediatric_consultant_fee` /
  `pediatric_specialist_fee` (+ doctor `pediatric_followup_fee`/commission).
- **obgyn / psychiatry / neurology:** `module_settings` `consultation_fee`
  only (added in migration `…000020`), edited on the **ModuleDetail** page.
  No consultant/specialist split, no follow-up.
- **doctor-level overrides:** `doctors.{module}_consultation_fee` exist for
  obgyn/psychiatry/neurology; derma/dental have `dermatology_fee`,
  `cosmetic_fee`, `dental_consultation_fee`; pediatric has
  `pediatric_consultation_fee`.
- **Fee resolution:** `CommissionCalculator::getConsultationFee()` +
  per-screen logic in `SecretaryBookingController`/`Admin\BookingController` +
  `Frontend\BookingController` + the two booking `Create.vue` files.
- **Follow-up:** `BookingWorkflowService::checkFollowUpEligibility()` is
  **hardcoded to `consultation_type = 'dermatology'`** and a single global
  window/fee.

**Net problems:** (a) obgyn/psych/neuro can't price consultant vs specialist
or follow-ups; (b) follow-up is derma-only and globally-windowed; (c) the
same fee logic is duplicated across 5+ places with per-module `if` ladders.

---

## 2) Target model (unified)

### 2.1 Single per-module pricing record (in `module_settings`, group `pricing`)
For **every** medical module, the same keys:

| key | meaning |
|---|---|
| `consultant_fee` | consultation fee when doctor_type = consultant |
| `specialist_fee` | consultation fee when doctor_type = specialist |
| `consultation_fee` | base / fallback consultation fee |
| `followup_fee` | fee for a follow-up consultation |
| `followup_window_days` | days after a visit during which a return is a follow-up (0 = follow-ups disabled for the module) |

Commission group (already partly present): `default_commission`,
`consultation_commission`, `followup_commission`.

### 2.2 Doctor-level override (unchanged, highest priority)
`doctors.{module}_consultation_fee` (and existing derma/dental/pediatric
fee fields) still win when set > 0, so a star doctor can be priced above the
module default.

### 2.3 Resolution order (one function, all modules)
`PricingResolver::consultationFee(Doctor $d, string $module, bool $isFollowUp): float`

1. `isFollowUp` → doctor follow-up override (if any) → module `followup_fee`.
2. doctor `{module}` consultation override (> 0).
3. `doctor_type === 'consultant'` → `consultant_fee`; `=== 'specialist'` →
   `specialist_fee`.
4. `consultation_fee` (base).
5. `0`.

This replaces the per-module `if` ladders in CommissionCalculator and the
three booking controllers — one source of truth.

### 2.4 Follow-up mechanic (module-aware)
`checkFollowUpEligibility(int $patientId, string $module): ?array`
- read **that module's** `followup_window_days` + `followup_fee`;
- find the patient's most recent completed consultation **of that module's
  consultation_type** within the window;
- return `{eligible, follow_up_fee, window_days, original_visit_*}`.
The booking surfaces then offer the follow-up price for any module, not just
derma.

---

## 3) Booking surfaces (must all honor the model when the module is enabled)

| Surface | File | Behavior |
|---|---|---|
| Public website | `Frontend/BookingController` + `Pages/Frontend/Booking.vue` | department list = enabled medical modules; booking-type cards per module; consultation fee shown/charged by resolver |
| Patient portal | `PatientBookingController` + `Pages/Patient/Bookings/Create.vue` | same |
| Secretary | `SecretaryBookingController` + `Pages/Secretary/Bookings/Create.vue` | fee defaults by module + doctor_type + follow-up; confirm path prices correctly |
| Admin | `Admin/BookingController` + `Pages/Admin/Bookings/Create.vue` | same as secretary |

All four already render the six specialties (done in F1/G-SEC/R2). This plan
adds **correct fee defaulting** (consultant/specialist/follow-up) for the
three thin modules and unifies the logic.

---

## 4) Admin settings UI

- **obgyn/psych/neuro:** the new `pricing` keys render + save automatically on
  the existing **ModuleDetail** page (`/admin/settings/modules/{module}`) —
  it already iterates `module_settings` by group (proven in F2). No new UI.
- **derma/dental/pediatric:** keep the current **Settings → consultation**
  page. Optionally (Phase 4) mirror their global keys into `module_settings`
  for one consistent editor; not required for functionality.
- Add labels (ar/en) for the new keys.

---

## 5) Data migration / backfill (idempotent, safe)

- New migration seeds the five `pricing` keys for **obgyn/psychiatry/
  neurology** (consultation_fee already there → keep; add consultant/
  specialist/followup/window with sensible defaults: consultant 350,
  specialist 250, followup 150, window 14).
- **No destructive change** to derma/dental/pediatric global Settings — the
  resolver reads module_settings first, then falls back to the legacy global
  Setting keys for those three, so nothing breaks during/after.
- Backfill is `updateOrInsert` (re-runnable).

---

## 6) Phased build checklist (with tests)

### Phase P1 — Settings + data
- [ ] Migration: add `consultant_fee`, `specialist_fee`, `followup_fee`,
      `followup_window_days` to module_settings for obgyn/psych/neuro.
- [ ] ar/en labels for the keys.
- [ ] **DB test:** keys present + ModuleDetail exposes the pricing group.

### Phase P2 — Unified resolver
- [ ] `PricingResolver` (or extend `CommissionCalculator`) implementing §2.3,
      reading module_settings then legacy global keys for derma/dental/peds.
- [ ] Point CommissionCalculator + the 3 booking controllers at it.
- [ ] **Unit tests:** consultant vs specialist vs base vs doctor-override vs
      follow-up, for each module.

### Phase P3 — Module-aware follow-up
- [ ] Generalize `checkFollowUpEligibility($patientId, $module)`.
- [ ] Wire it in secretary + admin booking (it already exists for derma);
      surface the follow-up price in all booking Create.vue forms per module.
- [ ] **Feature tests:** a recent obgyn/psych/neuro consultation makes the
      next one follow-up-eligible at the module's follow-up fee; outside the
      window it isn't.

### Phase P4 — Booking-surface fee defaulting
- [ ] Secretary/Admin/Website/Patient Create.vue: default the consultation
      price from the resolver by module + doctor_type (+ follow-up flag).
- [ ] **Feature tests:** booking a consultant vs specialist in each module
      yields the right unit_price; enabled-only visibility already covered.

### Phase P5 — Consistency + guards
- [ ] (Optional) mirror derma/dental/pediatric fees into module_settings for
      a single editor.
- [ ] Full suite + 6 UI guards + build + manifest; deploy per phase.

---

## 7) Trade-offs / risks

| Decision | Pro | Con / mitigation |
|---|---|---|
| Keep legacy global keys for derma/dental/peds (resolver falls back) | zero risk to working modules | two storage locations until P5 — documented; resolver hides it |
| consultant/specialist at module level (not new doctor columns) | no schema churn on doctors; matches derma/dental pattern | per-doctor still possible via existing override column |
| Module-aware follow-up window | each specialty sets its own return window | derma behavior preserved (its global window can move into module_settings in P5) |
| One PricingResolver | removes 5 duplicated fee ladders | one well-tested seam; covered by unit tests |

## 8) Out of scope (separate tracks)
- Time-of-day / insurance-adjusted pricing.
- Package/bundle pricing (already separate).
- The earlier-noted security S1 (PHI files) and S2 (prod env) — unrelated.

---

**Recommendation:** execute P1→P4 in order (P5 optional), with a test gate +
deploy after each phase, exactly as the module build was done. Say “ابدأ”
to start with P1.
