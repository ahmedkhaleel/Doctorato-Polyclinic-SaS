# Medical Modules — Cross-Portal & Booking Integration Audit

**Date:** 2026-06-05 · **Status:** ✅ Gaps F1–F5 fixed & deployed
**Scope:** Do all 6 medical modules (derma, dental, pediatric, obgyn,
psychiatry, neurology) work end-to-end across every portal, hide when
disabled, expose settings, and flow through booking + packages?

---

## Verdict (post-fix)

| Question | Answer |
|---|---|
| Show when enabled / hide when disabled (admin, patient, doctor)? | ✅ Yes — gated by `moduleKey` (admin/doctor) and `anyModuleKey` (patient neuropsych). |
| Where are settings? | `/admin/settings/modules` (toggle) + `/admin/settings/modules/{module}` (ModuleDetail, all 6). Psych/neuro also `/admin/{m}/settings`. |
| Flow through booking + packages like other specialties? | ✅ Now yes for all six (was ❌ for psychiatry/neurology — and obgyn was missing from the public form). |

---

## Findings (pre-fix) & fixes

### F1 — Booking pipeline (was the big gap) ✅
- **Found:** psychiatry/neurology had no `booking_type`, no module enum, no
  detection logic; both booking UIs hardcoded only dental/pediatric/derma —
  obgyn was also missing from the public `Booking.vue`. Patients could not
  book psych/neuro/obgyn online.
- **Fixed:** `BookingRequest` + `PatientBookingController` enums extended;
  `BookingWorkflowService::moduleFromBookingType()` single source of truth
  (used by website + secretary + patient flows); consultation_type + invoice
  descriptions cover all six; `Booking.vue` + patient `Create.vue` use a
  complete `BOOKING_TYPES_BY_MODULE` map; ar/en labels added. Department
  selector was already enabled-module driven.

### F2 — Module-level pricing ✅
- **Found:** psychiatry/neurology had no `module_settings` pricing (fees only
  on doctor columns); settings page showed no pricing, booking/billing had no
  module fee fallback.
- **Fixed:** migration seeds pricing/commission/duration for both (mirrors
  obgyn). Renders + saves in ModuleDetail automatically.

### F3 — Patient route gate ✅
- **Found:** `/patient/neuropsych/scales|diaries` not wrapped in module
  middleware — reachable by direct URL when modules off (nav hid them).
- **Fixed:** `CheckModule` is now variadic (OR semantics); routes wrapped in
  `module:psychiatry,neurology` (blocked only when both off).

### F4 — Doctor nav consistency ✅
- **Found:** derma & obgyn sidebar sections shown to ALL doctors, while
  dental/pediatric/psychiatry/neurology were limited to their specialty.
- **Fixed:** every medical-specialty section shows only to a doctor of that
  specialty (uniform rule).

### F5 — Patient booking doctor filter ✅
- **Found:** patient booking loaded all active doctors (client-filtered only).
- **Fixed:** server-side filter to doctors of an enabled medical specialty.
- **Packages:** `PackageBundle` carries `module` and bundles support all six
  equally; `PackageBundleBooking` reaches module via its bundle relation — no
  psych/neuro-specific gap, no change needed.

---

## Coverage matrix (post-fix)

| Module | Admin (toggle/settings/nav) | Patient portal | Doctor portal | Bookable (web + portal) | Packages |
|---|:--:|:--:|:--:|:--:|:--:|
| derma | ✅ | ✅ | ✅ | ✅ | ✅ |
| dental | ✅ | ✅ | ✅ | ✅ | ✅ |
| pediatric | ✅ | ✅ | ✅ | ✅ | ✅ |
| obgyn | ✅ | ✅ | ✅ | ✅ (web form fixed) | ✅ |
| psychiatry | ✅ | ✅ (scales/diaries) | ✅ | ✅ (new) | ✅ |
| neurology | ✅ | ✅ (scales/diaries + neuro tools) | ✅ | ✅ (new) | ✅ |

**Tests:** BookingModuleIntegrationTest, NeuropsychPricingSettingsTest,
PatientNeuropsychRouteGuardTest + full Neuropsych/Patient/Booking suites green.
**Delivery:** committed + pushed to `main`; CI (MySQL) green; auto-deployed to cPanel.
