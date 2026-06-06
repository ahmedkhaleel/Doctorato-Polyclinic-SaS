# Secretary Portal — Professional UX / Workflow Review
## مراجعة بوّابة السكرتارية بعدسة مستخدم خبير + صاحب عيادة

**Date:** 2026-06-07 · **Scope:** all 61 secretary pages.

> Bottom line: the secretary portal is **the most mature portal in the system** —
> a full CRM, deep booking, queue, invoicing/payments, HR self-service, and
> per-specialty front desks. After the global Ziggy fix every page renders. The
> gaps are **consistency polish + a few front-desk workflow niceties**, not
> missing capability.

---

## 1) What's already strong (so we don't "fix" it)
- **CRM suite** (huge): Leads, LeadShow, Pipeline, Calendar, Dashboard, Performance,
  Reports, Templates, Import/Export, duplicate-check. Genuinely enterprise-grade.
- **Bookings**: Create (1499 lines), Show (1783), Index, printable receipts.
- **Front-desk core**: Dashboard (821), Queue (556), Calendar, Visits, Patients
  (CRUD), Invoices + Payments + printable receipts, Inventory.
- **Pediatric front desk**: Patients/Visits/Vaccinations — rich, with hero headers.
- **HR self-service**: attendance, leaves, salary slips. Chat, notifications.
- **Specialty front desks** (built this session): derma, neuropsych, telemedicine
  overviews — administrative only, no clinical data leakage (tested).

---

## 2) الثابت عبر الصفحات (cross-cutting — small, real)

### S-CC-1 — عدم اتساق نمط الهيدر 🟡 (the main concrete finding)
Two header styles coexist:
- **Gradient hero band** (premium): Dashboard, Bookings, Patients, Pediatric,
  Invoices, Queue, CRM…
- **Thin accent-bar title** (lighter): the three specialty front desks built this
  session (**Derma, Neuropsych, Telemedicine**) + **Obgyn/Pregnancies/Index** +
  the **Dental** sub-pages (LabOrders, TreatmentPlans, PatientChart).
- **Impact:** purely visual inconsistency — moving between sections feels uneven.
- **Fix:** promote the thin-header pages to the same gradient hero band (exactly
  the D3 treatment just shipped for the OB/GYN doctor pages). Low risk, additive.

### S-CC-2 — الواجهات الفارغة 🟡
Some lists still use plain "لا سجلات/لا يوجد" text instead of the shared
`UiEmptyState`. Adopt it on the specialty front desks + dental sub-pages for a
consistent "intentional, not broken" feel.

### S-CC-3 — جداول الجوّال 🟡
The large CRM/booking tables scroll horizontally on phones; the front desk often
works on a tablet. Responsive (stacked-card) variants would help — incremental.

---

## 3) ملاحظات لكل قسم (per-area)

### 3.1 Specialty front desks (derma / neuropsych / telemedicine) 🟡
- Built lean & read-only (appointments + roster + balances / payment-chase).
- Niceties to add: **today's check-ins** highlighted, an inline **"mark arrived"
  / quick status** if the desk manages flow, and the gradient header (S-CC-1).
- Deliberately NO clinical data — keep it that way (compliance).

### 3.2 Dental front desk (LabOrders, TreatmentPlans, PatientChart) 🟡
- Functional but flat-headed; a **lab-order status board** (ordered → ready →
  delivered) would help the desk chase the lab. Data exists.

### 3.3 OB/GYN (Pregnancies/Index) 🟡
- Flat header; otherwise functional. Same hero promotion as S-CC-1.

### 3.4 Package bundles (Create/Index/Show) 🟡
- Flat-headed; large pages (743/781) — functional. Header consistency only.

### 3.5 Invoices/Show, Prescriptions 🟢/🟡
- Show pages flat-headed (acceptable for detail/print). Print pages correctly
  have no hero.

---

## 4) خطة موصى بها (prioritized)

| # | البند | قيمة | نطاق | خطر |
|---|---|---|---|---|
| SD1 | **توحيد الهيدر (gradient hero)** على: derma/neuropsych/telemedicine front desks + Obgyn/Pregnancies + Dental sub-pages | اتساق فوري واضح | صغير | منخفض |
| SD2 | **تبنّي `UiEmptyState`** في الصفحات المسطّحة | اتساق | صغير | منخفض |
| SD3 | **«وصلوا اليوم»** على الفرونت‑ديسك التخصصية + لوحة حالة طلبات معمل الأسنان | كفاءة الاستقبال | متوسط | منخفض |
| SD4 | **جداول متجاوبة** (CRM/الحجوزات على الجوّال/التابلت) | استخدام ميداني | متوسط | منخفض |

**التسلسل:** SD1 (توحيد الهيدر — مطابق لما نُفّذ في D3) → SD2 → SD3 → SD4.

> ملاحظة صادقة: لا يوجد **نقص قدرات** فعليّ في بوّابة السكرتارية — هي الأنضج. العمل
> المتبقّي **تلميع اتساق** + تحسينات استقبال صغيرة، تُنفّذ على فرع باختبار ونشر متحقّق.
