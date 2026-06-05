# Admin (Super-Admin) Specialty Screens — Design & Build Plan

**Status:** ✅ Built & deployed (Phases A–E) · **Date:** 2026-06-05
**Scope:** Complete the super-admin oversight surface for **Psychiatry**,
**Neurology**, and **OB/GYN** — the three medical modules whose admin nav
is currently thin (Dashboard / Reports / Settings only).

> Companion docs: `NEUROPSYCH_MODULE_PLAN.md`, `OBGYN_MODULE_PLAN.md`.

---

## 1) Problem & framing

The doctor panel is rich (encounters, scales, risk, medications,
controlled-Rx, neuro tools, courses, diaries). The **admin panel is not**:
it only exposes a dashboard, a reports page, and settings. There are
**15 neuropsych clinical tables** and **4 OB/GYN tables** holding data that
the clinic owner / operations lead has no screen to oversee.

**Admin perspective ≠ a second clinical-entry UI.** The doctor enters care;
the admin/super-admin needs **oversight, compliance, quality, and finance**
views: registers, audit logs, outcome analytics, and "what needs attention"
work-queues. Screens are therefore **read-first**, with action only where an
operations role legitimately acts (billing status, settings, exports).

### Non-functional requirements / constraints
- **RBAC:** reuse `{module}.view`; sensitive screens (risk, psychotherapy,
  controlled substances) gated by `{module}.view_sensitive` (heightened
  RBAC + audit) exactly like the doctor side.
- **Branch-scoping:** all clinical EVENT data is branch-scoped; lists must
  respect the active branch (and "all branches" for super_admin) via the
  existing global scope — no manual `where` needed.
- **Patterns:** mirror existing admin modules (dental/derma/obgyn): Inertia
  page per route, `AdminLayout` nav group, controller method → `Inertia::render`.
- **UI guards (must stay green):** no emoji, labeled icon buttons,
  `v-focus-trap` on modals, `useConfirm` (no native confirm), no static
  English `title`, FormErrors. RTL Arabic-first, Saturday-first weeks.
- **Manifest:** every new Vue page → `npm run build` + commit `public/build`.

---

## 2) Current-state gap analysis

| Capability (admin oversight)        | Psychiatry | Neurology | OB/GYN |
|-------------------------------------|:---------:|:---------:|:------:|
| Dashboard                           | ✅ | ✅ | ✅ |
| Reports                             | ✅ | ✅ | ✅ |
| Settings (fees/AI/module)           | ✅ | ✅ | ✅ |
| Pregnancies register                |  —  |  —  | ✅ |
| **Patients / Cases list**           | ❌ | ❌ | ❌ |
| **Encounters log + billing status** | ❌ | ❌ | ❌ |
| **Outcomes (measurement-based care)** | ❌ | ❌ | n/a |
| **Risk register (sensitive)**       | ❌ | ❌ | n/a |
| **Medications & monitoring queue**  | ❌ | ❌ | n/a |
| **Controlled substances audit (sensitive)** | ❌ | ❌ | n/a |
| **Treatment courses (ECT/rTMS/ketamine)** | ❌ | ❌ | n/a |
| **Neuro procedures + diary engagement** | n/a | ❌ | n/a |
| **ANC due/overdue queue**           | n/a | n/a | ❌ |
| **Lab tests oversight (abnormal/pending)** | n/a | n/a | ❌ |
| **Deliveries / outcomes**           | n/a | n/a | ❌ |

Data already exists for every ❌ above — these are **surfacing** tasks, not
new data models.

---

## 3) Proposed screen catalog

### Psychiatry & Neurology (shared `AdminNeuropsychController`, per-`npModule`)

| # | Screen | Route | Data source | RBAC | Notes |
|---|--------|-------|-------------|------|-------|
| N1 | **Cases** | `/admin/{m}/cases` | `neuropsych_profiles` + last encounter + active dx + risk flag | `{m}.view` | Drill to patient. |
| N2 | **Encounters** | `/admin/{m}/encounters` | `neuropsych_encounters` (+ diagnoses, invoice link) | `{m}.view` | Filters: doctor, date, billed?; billing oversight. |
| N3 | **Outcomes** | `/admin/{m}/outcomes` | `scale_results` (PHQ-9/GAD-7 / HIT-6) | `{m}.view` | % improved, flagged, trend; quality KPI. |
| N4 | **Risk register** 🔒 | `/admin/{m}/risk` | `risk_assessments` (active mod/high) + flagged scales | `{m}.view_sensitive` | Safety oversight; audited. |
| N5 | **Medications** | `/admin/{m}/medications` | `medication_plans` + `medication_monitoring` | `{m}.view` | "Overdue monitoring" queue (clozapine ANC, lithium level). |
| N6 | **Controlled substances** 🔒 | `/admin/{m}/controlled` | `controlled_substance_register` + `controlled_prescriptions` | `{m}.view_sensitive` | Compliance audit log (sign/submit/dispense). |
| N7 | **Treatment courses** | `/admin/{m}/courses` | `treatment_courses` + `course_sessions` | `{m}.view` | Consent compliance, session progress. |
| N8 | **Neuro tools** (neurology only) | `/admin/neurology/neuro` | `neuro_procedures` + `seizure_diary` + `headache_diary` | `neurology.view` | Procedures log + patient diary engagement. |

### OB/GYN (`AdminObgynController`)

| # | Screen | Route | Data source | RBAC | Notes |
|---|--------|-------|-------------|------|-------|
| O1 | **Cases** | `/admin/obgyn/cases` | `obgyn_profiles` | `obgyn.view` | Drill to patient. |
| O2 | **ANC queue** | `/admin/obgyn/anc` | `pregnancies` + visit schedule | `obgyn.view` | Due / overdue antenatal visits work-queue. |
| O3 | **Lab tests** | `/admin/obgyn/labs` | `obgyn_lab_tests` | `obgyn.view` | Pending / abnormal results. |
| O4 | **Encounters** | `/admin/obgyn/encounters` | `obgyn_encounters` | `obgyn.view` | Billing oversight. |
| O5 | **Deliveries** | `/admin/obgyn/deliveries` | `pregnancies` (delivered) | `obgyn.view` | Outcomes register. |

🔒 = sensitive (`view_sensitive`-gated, audited).

---

## 4) Architecture & data flow

```
AdminLayout.vue nav group ({m})
   └─ <Link> /admin/{m}/{screen}
        └─ routes/admin.php  (foreach module loop, middleware: module:{m} + permission)
             └─ AdminNeuropsychController::{screen}(npModule)   ← extend existing controller
                   └─ branch-scoped Eloquent query (global scope) + sensitive gate
                        └─ Inertia::render('Admin/Neuropsych/{Screen}', {...})
                              └─ resources/js/Pages/Admin/Neuropsych/{Screen}.vue
```

- **Reuse** the existing per-module foreach in `routes/admin.php` and the
  shared `AdminNeuropsychController` (add one method per screen). OB/GYN adds
  methods to `AdminObgynController`.
- **Shared Vue page per screen**, parameterized by `npModule` prop (psych vs
  neuro), so neurology and psychiatry share one component — same DRY approach
  the doctor side already uses (`Pages/Doctor/Neuropsych/*`).
- **Sensitive screens**: route middleware `permission:{m}.view_sensitive`; the
  nav item carries `permission: '{m}.view_sensitive'` so it hides for
  non-sensitive admins (item-level filter already in `AdminLayout`).
- **No new tables, no new migrations.** Pure read/aggregate + existing
  billing actions already on services.

---

## 5) Phased build plan (checklist)

Ordered by value/risk. Each phase: controller methods → routes → shared Vue
pages → nav items → `npm run build` → feature tests → green UI guards → commit.

### Phase A — Visibility (read-only, highest value, lowest risk) ✅
- [x] N1 Cases (psych + neuro)  ·  O1 Cases (obgyn)
- [x] N2 Encounters log (+ billing status)  ·  (obgyn encounters = Pregnancies + ANC)
- [x] Nav items + permission wiring + tests

### Phase B — Quality / outcomes ✅
- [x] N3 Outcomes (measurement-based care: PHQ-9/GAD-7/HIT-6 trends + improvement rate)
- [x] O2 ANC due/overdue queue  ·  O3 Lab tests oversight (abnormal-first)

### Phase C — Safety & compliance (sensitive, RBAC + view_sensitive) ✅
- [x] N4 Risk register 🔒
- [x] N5 Medications & overdue-monitoring queue
- [x] N6 Controlled substances audit 🔒

### Phase D — Programs & neuro ✅
- [x] N7 Treatment courses (consent + progress)
- [x] N8 Neuro tools (procedures + diary engagement)
- [x] O5 Deliveries / outcomes register

### Phase E — Dashboard enrichment ✅
- [x] KPI tiles on each module dashboard (active cases, encounters MTD,
      flagged risks, overdue monitoring, revenue) linking into the new lists;
      sensitive tile gated by view_sensitive.

**Tests:** AdminScreensPhaseA–E tests (all green) + 6 UI regression guards.
**Delivery:** committed + pushed to `main`; auto-deployed to cPanel; each
phase verified green in CI (MySQL) before the next.

---

## 6) Trade-offs

| Decision | Pro | Con / revisit |
|----------|-----|---------------|
| Read-first oversight (no clinical entry in admin) | Clear separation of duties; less RBAC surface | Admin must switch to doctor panel to act clinically (acceptable) |
| Shared Vue page per screen (psych/neuro) | DRY; one place to fix | Slight prop branching; fine given identical shape |
| Extend existing controllers vs new ones | Mirrors current structure; fewer files | `AdminNeuropsychController` grows — split later if it exceeds ~10 methods |
| No new tables | Zero migration/deploy risk | Some aggregates computed on the fly — add caching only if reports get heavy |

---

## 7) Open question for the owner

Build order: **recommend Phase A first** (Cases + Encounters for all three
modules) — it's the biggest "now I can see what's happening" win and is pure
read-only/low-risk. Confirm the phase to start, or approve the whole plan to
execute sequentially with a test gate per phase.
