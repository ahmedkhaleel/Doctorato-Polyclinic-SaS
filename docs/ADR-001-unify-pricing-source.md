# ADR-001: Unify the pricing source of truth (P5-4)

**Status:** Accepted — Phases 0–3 implemented & shipped (read unified + dual-write); Phase 4 (retire legacy keys) pending a clean production cycle
**Date:** 2026-06-06
**Deciders:** Owner (info@markeza-group.com) + engineering
**Supersedes/affects:** `app/Services/Pricing/PricingResolver.php`, settings write paths

> **Update (2026-06-06):** Phases 0–3 are **implemented & shipped** safely (read
> path unified with legacy fallback; editors dual-write). Behaviour is identical
> in every environment until a *positive* `module_settings` value exists, so no
> live fee changed. **Phase 4 (retire legacy keys) is still pending** a clean
> production cycle + the owner running `pricing:backfill-module-settings` and
> confirming an unchanged `pricing:audit --json`. P5-4 touches **live revenue**,
> so Phase 4 stays gated.

---

## 1. Context

A medical module's consultation pricing (consultant fee, specialist fee, base
fee, follow-up fee, follow-up window) lives in **two different stores** today.
`PricingResolver::source()` hides this split from callers, but the data and the
**write paths** remain divergent:

| Module | Driver | Where fees are stored (keys) |
|---|---|---|
| derma / dermatology | `settings` (global `Setting`) | `dermatology_consultant_fee`, `dermatology_specialist_fee`, `default_dermatology_fee`, `followup_fee`, `followup_window_days` |
| cosmetic | `settings` | `cosmetic_consultation_fee` (×3), `followup_fee`, `followup_window_days` |
| dental | `settings` | `dental_consultant_fee`, `dental_specialist_fee`, `followup_fee`, `followup_window_days` |
| pediatric | `settings` | `pediatric_consultant_fee`, `pediatric_specialist_fee`, `pediatric_followup_fee`, `followup_window_days` |
| obgyn / psychiatry / neurology | `module` (`module_settings`) | `consultant_fee`, `specialist_fee`, `consultation_fee`, `followup_fee`, `followup_window_days` |

Per-doctor overrides are **separate** (columns on `doctors`, e.g.
`dermatology_fee`, `psychiatry_consultation_fee`) and are **out of scope** here
— they already work and stay as-is.

### Write paths (who edits the legacy `Setting` fee keys)
- `app/Http/Controllers/Admin/SettingController.php` (global settings page → `Admin/Settings/Index.vue`)
- `app/Http/Controllers/Admin/AdminPediatricController.php` (→ `Admin/Pediatric/Settings.vue`)
- Doctor + booking controllers read/seed defaults (`DoctorController`, `BookingController`, `SecretaryBookingController`, `LeadController`).

`module_settings` fees (obgyn/psych/neuro) are edited via `SettingController` too
(module settings pages).

### Forces / problems
1. **Two stores, two editors** → drift risk: a value can be changed in one place
   and silently ignored by the resolver path that reads the other.
2. **Shared keys** (`followup_fee`, `followup_window_days`) are *global* for the
   legacy modules — changing the derma follow-up fee also changes dental's.
   `module_settings` already gives obgyn/psych/neuro **per-module** follow-up
   values; the legacy modules cannot have distinct ones today.
3. New modules must remember which store to use — cognitive cost + bug surface.

---

## 2. Decision (proposed)

Make **`module_settings` the single source of truth** for all six medical
modules' consultation pricing. `PricingResolver::source()` collapses to one
`module` driver for every module; the legacy global `Setting` fee keys are
**migrated into `module_settings`** and then retired.

Benefits: one store, per-module follow-up values for everyone, one editor, no
drift, simpler mental model, branch-override support everywhere (module_settings
already supports `branch_id`).

---

## 3. Options considered

### Option A — Do nothing (keep the split, resolver hides it)
| Dimension | Assessment |
|---|---|
| Complexity | None |
| Risk | None now, ongoing drift risk + per-module follow-up impossible for legacy |
| Cost | Zero |

**Pros:** zero risk today. **Cons:** the drift bug and shared-follow-up
limitation persist; every new module re-learns the split.

### Option B — Unify onto `module_settings` (proposed)
| Dimension | Assessment |
|---|---|
| Complexity | Medium (read + write + data migration must move together) |
| Risk | **High if done in one shot** on live revenue; **Low** with expand/contract |
| Cost | ~1–2 days incl. staging verification |

**Pros:** single source, per-module follow-up everywhere, branch overrides,
simpler. **Cons:** must migrate read path, write path, AND data atomically (or
via expand/contract) or fees mis-resolve.

### Option C — Unify onto global `Setting`
Rejected: `Setting` has no per-module/per-branch structure for fees; would lose
the per-module follow-up capability `module_settings` already provides.

**Chosen: Option B, via the expand/contract migration in §4.**

---

## 4. Staged migration plan (expand → migrate → contract)

> Each phase is independently shippable, reversible, and verified on **staging**
> before production. No phase changes a fee value — only where it is read/written.

### Phase 0 — Safety net (no behaviour change)
- Add a **characterization test**: for every module × doctor_type × follow-up
  combination, snapshot `PricingResolver::consultationFee()` + `feesFor()` to a
  fixture. This locks current resolved prices so any later phase that changes a
  number fails loudly.
- Add a `pricing:audit` artisan command that prints the resolved fee table for
  all modules (run on prod read-only before/after to compare).

> **Implementation note (found during Phase 1):** `ModuleManager::setSetting()`
> issues an UPDATE-only query — it no-ops when the (module,key) row doesn't exist.
> Legacy modules have no `module_settings` fee rows yet, so the backfill (and the
> Phase-3 write path) must **upsert** (`updateOrInsert`). The backfill command
> does this; Phase 3 must either fix `setSetting` to upsert or seed rows first.

### Phase 1 — EXPAND: backfill `module_settings` from legacy `Setting`
- Idempotent migration/command copies each legacy module's current values into
  `module_settings` keys (`consultant_fee`, `specialist_fee`, `consultation_fee`,
  `followup_fee`, `followup_window_days`), per module:
  - derma → from `dermatology_*` + shared `followup_*`
  - cosmetic → from `cosmetic_consultation_fee` + shared `followup_*`
  - dental → from `dental_*` + shared `followup_*`
  - pediatric → from `pediatric_*` (+ `pediatric_followup_fee`)
- **Resolver still reads the legacy store** (no read change yet). This phase only
  *populates* the new store. 100% safe; reversible (delete the new rows).

### Phase 2 — Switch the READ path  ✅ DONE (shipped, fallback-protected variant)
- **Redesigned for safety:** instead of a hard flip that depends on the prod
  backfill having run (which would zero-price legacy modules if it hadn't),
  `feesFor()` now reads module_settings **first** and falls back to the legacy
  Setting when the module_settings value is absent **or ≤ 0**. So:
  - module_settings 0/absent → legacy value (identical to pre-Phase-2);
  - module_settings positive (backfilled/edited) → that value (= legacy after backfill).
- This removes the hard dependency on the prod backfill for correctness and is
  fully reversible (revert `source()`/`feesFor()`).
- **Safety finding (caught by the characterization test):** a module_settings fee
  row pre-seeded to 0 must NEVER zero a live fee → the "≤ 0 ⇒ fall back" rule.
- Full suite green (1535); resolved prices unchanged in every environment.

### Phase 3 — Switch the WRITE path (editors)  ✅ DONE (shipped, dual-write)
- `app/Services/Pricing/PricingSettingsMirror.php` holds the legacy→module_settings
  map and mirrors current legacy fee values into module_settings (upsert).
- `SettingController::update` and `AdminPediatricController::updateSettings` call
  `mirror()` after saving → **dual-write**: the legacy `Setting` is still written
  (rollback path) AND module_settings is synced, so the resolver reflects the
  edit immediately (it prefers the positive module_settings value).
- `pricing:backfill-module-settings` now delegates to the same service.
- **Pre-existing follow-up (NOT changed here):** the pediatric editor saves
  `pediatric_consultation_fee` while the resolver reads `pediatric_consultant_fee`
  / `pediatric_specialist_fee` — a legacy-key mismatch that predates this ADR.
  Mirroring is harmless/idempotent; reconciling those keys is a separate task.
- Tests: mirror sync, `touchesPricing` detection, and the real `/admin/settings`
  endpoint dual-writing a dental fee. Full suite green (1538).

> **Production gate PASSED (2026-06-06):** owner ran `pricing:audit --json` →
> `pricing:backfill-module-settings` → `pricing:audit --json`. Output was
> **identical before and after** for all modules (20 values backfilled), proving
> the read flip + backfill are a true no-op for live pricing. Cleared to soak
> before Phase 4.
>
> **Finding surfaced by the audit — pediatric resolves to 0:** the pediatric
> editor saves `pediatric_consultation_fee` while the resolver reads
> `pediatric_consultant_fee`/`pediatric_specialist_fee`, so pediatric consultation
> fees resolve to 0 (bookings presumably rely on per-doctor overrides or service
> prices). This is PRE-EXISTING (not caused by ADR-001). Reconciling it CHANGES a
> live fee → requires explicit owner decision; tracked as a separate follow-up.

### Phase 4 — CONTRACT: retire legacy keys (GATED: soak + owner go-ahead)
- After ≥1 production cycle with no pricing incidents, remove the legacy
  `Setting` fee keys + the `settings` branch of `source()`. Leave a data
  migration that deletes the orphaned keys (idempotent).

### Rollback
- Phase 1: delete backfilled rows (no-op for reads).
- Phase 2: revert `source()` to the split (one-line revert) — legacy store still
  intact and still written (until Phase 3).
- Phase 3: if dual-write kept, revert resolver/editor; data still in both stores.
- Each phase is a separate PR + deploy; `pricing:audit` diff is the gate.

---

## 5. Acceptance criteria (per phase)
- Phase 0: characterization test green; `pricing:audit` output captured from prod.
- Phase 1: every module has `module_settings` fee rows equal to its current
  resolved legacy values; full suite green.
- Phase 2: characterization snapshot **unchanged**; staging bookings price
  identically; full suite green.
- Phase 3: editing a fee in the admin UI changes the resolved price (end-to-end
  test: edit → book → invoice reflects new fee); no legacy-only write remains.
- Phase 4: no code path references the legacy fee keys; suite green; `pricing:audit`
  identical to the pre-migration capture.

## 6. Consequences
- **Easier:** one store, per-module follow-up fees for all, branch overrides
  everywhere, one settings editor, trivial to add a new module's pricing.
- **Harder (short term):** a 4-PR sequence with staging verification each time.
- **Revisit:** if per-branch pricing demand grows, `module_settings.branch_id`
  already supports it — surface it in the editor at that point.

## 7. Action items
1. [x] **Phase 0 (DONE, shipped):** characterization test (`tests/Feature/Pricing/PricingResolverCharacterizationTest.php`) + `pricing:audit` command (`app/Console/Commands/PricingAuditCommand.php`). Zero behaviour change. **Next: capture `php artisan pricing:audit --json` from production as the baseline before Phase 1.**
2. [x] **Phase 1 (DONE, shipped):** `pricing:backfill-module-settings` command (idempotent upsert; --dry-run) + test proving it populates module_settings, keeps resolved prices identical, and is idempotent. NOT auto-run on prod — owner runs it deliberately, gated by a clean `pricing:audit --json` before/after diff.
3. [x] **Phase 2 (DONE, shipped):** fallback-protected read flip — module_settings primary, legacy Setting fallback when ≤0/absent. Safe pre- or post-backfill; reversible. Full suite green.
4. [x] **Phase 3 (DONE, shipped):** PricingSettingsMirror + dual-write hooks in SettingController & AdminPediatricController; end-to-end edit test green.
5. [ ] Phase 4: retire legacy keys after a clean production cycle.

> **Recommendation:** approve Phases 0–1 first (zero behaviour change, fully
> reversible). Treat Phase 2 (read flip) as the real go/no-go gate, decided only
> after the Phase-0 snapshot proves the backfill is exact on a prod data copy.
