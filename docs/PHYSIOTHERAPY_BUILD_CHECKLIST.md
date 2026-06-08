# Physiotherapy Module — Build Checklist

Status of the integrated Physiotherapy & Rehabilitation module. Mirrors the
neuropsych build. **Ships disabled** — zero visible change until an admin runs
`ModuleManager::enable('physiotherapy')` (or toggles it in the modules UI), so
every phase below is production-safe.

Design doc: `docs/PHYSIOTHERAPY_MODULE_PLAN.md`. Tests: `tests/Feature/Physio/`.
Accent colour: teal `#0D9488`.

---

## Done

- [x] **PT-0 — Registration.** `ModuleManager::MODULES` + `MEDICAL_MODULES`
  include `physiotherapy`; module_settings (general + pricing/commission/duration)
  seeded; `physiotherapy.{view,create,update,delete}` permission catalog + group;
  doctor fee/commission columns (`physiotherapy_consultation_fee/commission`,
  `physiotherapy_session_fee/commission`). Tests: `PhysiotherapyFoundationTest`.
- [x] **PT-1 — Data layer.** One idempotent, branch-aware migration → 9 tables:
  `physio_assessments`, `physio_treatment_plans`, `physio_sessions`,
  `physio_pain_points`, `physio_rom_measurements`, `physio_strength_tests`,
  `exercises` (shared catalog, no branch), `physio_exercise_prescriptions`,
  `hep_adherence_logs`. Eloquent models + `App\Services\Physio\RomNormatives`
  (AAOS normal ROM). Tests: `PhysiotherapyDataLayerTest`.
- [x] **PT-2 — Doctor cockpit + billing.** `DoctorPhysioController` (dashboard,
  patients, patient file, assessment capture with nested ROM/MMT/pain, plan of
  care, billable session log). `PhysioBillingService` (idempotent/reversible
  invoice lines, keyed on `physio_sessions.invoice_item_id`). Wired into
  `PricingResolver` + `CommissionCalculator`. Doctor Vue pages
  (Dashboard/Patients/Show/TreatmentPlans). Tests: `PhysiotherapyDoctorFlowTest`.
- [x] **PT-3 — Visuals + visit panel.** New chart primitives `RadialChart`
  (ROM radar/bar) + `StrengthGrid` (MMT 0–5). `PhysioPanel` visit cockpit
  (ProgressRing + pain TrendLine + ROM RadialChart + StrengthGrid + BodyMap),
  wired into `SpecialtyPanel` + both visit `show()` controllers + visit pages.
  Tests: `PhysiotherapyVisitPanelTest`.
- [x] **PT-4 — Exercise catalog + HEP.** Starter catalog seeded (16 exercises);
  doctor prescribe/stop; patient portal `Patient/Physiotherapy/Overview`
  (plan progress, HEP list with done-today self-log, 12-week adherence
  CalendarHeatmap); patient sidebar entry. Tests: `PhysiotherapyHepTest`.
- [x] **PT-5 — Admin + secretary + sidebar.** `AdminPhysioController`
  (dashboard / patients / exercise CRUD / settings → module_settings);
  `SecretaryPhysioController` front-desk (appointments + roster + plan
  session-counts, **no clinical data**); admin sidebar group under the
  Specialties pillar (GROUP_ORDER + PILLARS); secretary + patient nav.
  Tests: `PhysiotherapyAdminTest`.
- [x] **PT-6 — Bookings (core).** Booking types `physiotherapy_consultation` +
  `physiotherapy_session` accepted by every validator; `BookingWorkflowService`
  module detection + visit `consultation_type` mapping + invoice labels; lead
  conversion fee; admin booking UI cards + fee defaulting.
  Tests: `PhysiotherapyBookingTest`.
- [x] **PT-7 — PROMs via MBC.** ODI / NDI / LEFS added to the shared
  `ScaleEngine` (bilingual items, bands, MCID, direction). Doctor capture
  (`storeScale`), patient-file PROM panel (Sparkline + MCID badge), visit-panel
  PROM chips. Tests: `PhysiotherapyPromTest`.
- [x] **PT-8 — Optional 3D pain map.** `Body3D` (three.js, lazy chunk, WebGL
  fallback to 2D `BodyMap`) as a 2D/3D toggle in the pain-map card.
- [x] **PT-9 — Demo seeder + docs.** `SpecialtyDoctorDemoSeeder` seeds a
  `demo.physiotherapy@doctorato.net` doctor with a full dataset; this checklist
  + CLAUDE.md updated. Test assertions extended in `SpecialtyDoctorDemoSeederTest`.

---

## To activate (owner / server)

1. Assign physiotherapy doctors (`doctors.module = 'physiotherapy'`) and grant
   staff the `physiotherapy.*` permissions.
2. Confirm pricing via `/admin/physiotherapy/settings` (seeded defaults:
   assessment 250, session 200, home-visit surcharge 100).
3. `ModuleManager::enable('physiotherapy')` (or the modules UI).
4. Verify `/health` stays green and run the suite.

---

## Follow-ups (now done)

The PT-6 "full bookings" follow-ups have all shipped as the F-series:

- [x] **F-A** — physiotherapy booking-type cards on the **secretary**,
  **patient**, and **public/frontend** booking forms (admin was done in PT-6).
  Tests via build + existing booking suite.
- [x] **F-B** — **home-visit surcharge**: `physio_sessions.home_visit` +
  `PhysioBillingService` adds the `home_visit_surcharge` setting + doctor toggle.
  Test: `PhysiotherapyHomeVisitTest`.
- [x] **F-C** — **recurring session series**: `DoctorPhysioController.scheduleSeries`
  creates N interval-spaced `physiotherapy_session` bookings via the workflow.
  Test: `PhysiotherapySessionSeriesTest`.
- [x] **F-D** — **session packages**: `physio_packages` catalog +
  `physio_package_purchases` + `physio_sessions.package_purchase_id`; sell +
  draw-down (covered, not billed); surfaced on doctor/secretary/patient.
  Test: `PhysiotherapyPackageTest`.
- [x] **F-E** — **weighted-scoring PROMs**: `ScaleEngine` gains a `mean_scaled`
  scoring mode; **QuickDASH** registered for physiotherapy (sum scales
  unaffected). Test: `PhysiotherapyQuickDashTest`.

Still open (lower priority, additive): KOOS / WOMAC / PSFS instruments
(PSFS needs patient-specific items, not a fixed list) and a dedicated admin
package-catalog CRUD page (the catalog is seeded + editable via DB for now).
