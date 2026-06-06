# Doctor Portal — Professional UX / Workflow Review
## مراجعة بوّابة الطبيب بعدسة مستخدم خبير + صاحب عيادة

**Date:** 2026-06-07 · **Scope:** every doctor page across all specialties
(derma, pediatric, obgyn, psychiatry, neurology, dental, telemedicine) + shared
doctor pages (patients, visits, online consultations).

> Context: the system-wide "blank pages" bug (Ziggy `route()` not wired) is FIXED
> and verified live across all specialties. Visual design is broadly consistent
> (a brand gradient header band is present on nearly every page). So this review
> focuses on **workflow & business value**, not cosmetics — what a busy doctor
> running a real clinic still lacks.

---

## 0) Method & evidence
- File inventory + header-band presence per page (1 visual gap found).
- Live walk-through of each specialty landing on `doctorato.net` (all render).
- Cross-referenced controllers for data already available but not surfaced.

---

## 1) الثابت عبر كل التخصصات (cross-cutting gaps — highest ROI)

These repeat in **every** specialty, so fixing the pattern once lifts all six.

### CC-1 — لا «قُمرة قيادة» يومية موحّدة (Daily cockpit) 🔴
- **Derma / Pediatric / OB-GYN** open on a real dashboard (greeting + KPIs).
- **Psychiatry / Neurology / Dental** open on a *list* (encounters / treatments)
  — no "today" view. A doctor's first screen should answer: *who am I seeing
  today, what's urgent, what's unfinished.*
- **Missing on the landing of every specialty:** today's appointment queue with
  status, a worklist of items needing the doctor (overdue follow-ups, abnormal
  labs, unsigned notes, due medication monitoring), and 1-click "start visit".
- **Business impact:** the doctor hunts across pages instead of starting the day
  from one screen → slower throughput, missed follow-ups (= lost revenue).

### CC-2 — ملف المريض لدى الطبيب رفيع (Patient file is the weakest hub) 🔴
- `Doctor/Patients/Show.vue` (~187 lines) is the page the doctor opens most, yet
  it's thin: no unified **clinical timeline**, no **allergy/critical-flag banner**,
  no **active medications**, no **last-visit summary**, no specialty cross-links
  (a derma patient's derma sessions, a peds patient's growth curve) in one place.
- **Business impact:** the doctor can't get a 5-second patient picture before the
  consult → safety risk (allergies) + slower visits.

### CC-3 — لا إجراءات سريعة (No quick actions from lists) 🟠
- Lists show data but rarely a primary action inline (start visit, log session,
  write Rx, schedule follow-up). Derma cards now link to the file; most others
  are read-only rows.
- **Business impact:** every task is multi-click; high-volume clinics feel it.

### CC-4 — حالات تحميل غير متّسقة (Inconsistent loading states) 🟠
- Only **15 / 59** doctor pages reference any loading/skeleton state. The rest
  show a blank area during navigation/XHR — which is *exactly* what made the
  `route()` crash read as "white pages." Even post-fix, slow links flash blank.
- **Fix:** a shared `Skeleton`/loading primitive used on every list/detail.

### CC-5 — لا بحث/لوحة أوامر سريعة للمريض داخل التخصص 🟠
- No in-specialty patient quick-search or command palette (the global one exists
  in the layout, but not a focused "jump to my patient"). Derma added a search;
  others rely on paginated lists.

### CC-6 — الحالات الفارغة غير موحّدة (Empty states) 🟡
- Derma now has premium empty states (icon + guidance + CTA). Others are plain
  "لا يوجد…" text. A shared `UiEmptyState` exists (built this session) but is
  under-adopted → inconsistent "is this broken or just empty?" feel.

### CC-7 — الجداول غير متجاوبة على الجوّال (Mobile tables) 🟡
- Specialty lists are wide tables with horizontal scroll on phones — doctors
  increasingly work on tablets/phones at the bedside.

### CC-8 — نظام التصميم غير موحّد بالكامل (Design system) 🟡
- Toast (×3 panels), Modal (×3), and buttons are still duplicated/inline. The new
  `Ui/Button` + `Ui/EmptyState` primitives exist but adoption is partial. Drift
  risk + slower future changes.

### CC-9 — لا تصدير/طباعة متّسق (Print / export) 🟡
- Only 11 doctor pages offer print/export. A doctor often needs a printable visit
  summary, Rx, or patient sheet from any clinical page.

---

## 2) فجوات لكل تخصص (per-specialty gaps)

### 2.1 Dermatology & Cosmetic 🟢 (strongest after redesign)
- ✅ Dashboard + patients + plans redesigned (this session).
- Missing: doctor-side **before/after photo compare** slider in the patient file;
  visibility of **cosmetic consent** status (signed/unsigned) on the patient file;
  package "sessions remaining" surfaced on the patient card (data exists).

### 2.2 Pediatrics 🟢 (rich dashboard)
- ✅ Rich dashboard (growth chart, vaccinations, alerts).
- Missing: **immunization catch-up scheduling** (overdue → propose next dates);
  the **growth percentile curve on the patient file** (not just dashboard);
  well-child screening results pre-filled into the visit form.

### 2.3 OB/GYN 🟡
- **Visual gap:** `Obgyn/Gynecology.vue` and `Obgyn/Pregnancies/Index.vue` are the
  ONLY two doctor pages **missing the brand header band** → inconsistent.
- Missing: **ANC worklist** on the dashboard (who's due for an antenatal visit /
  EDD approaching); **postpartum follow-up** scheduler; **lab-results review**
  surfacing (results are entered but not flagged abnormal on a worklist).

### 2.4 Psychiatry & Neurology 🟠 (most workflow gaps)
- **No daily cockpit** — lands on the encounters list. Needs a dashboard:
  today's sessions, **active elevated-risk worklist** (data exists in
  RiskAssessment), **medication-monitoring due** queue (clozapine ANC / lithium —
  data exists in MedicationMonitoring), and **scale-trend** mini-charts (PHQ-9/
  GAD-7 over time — data exists in ScaleResult).
- Missing: a one-screen **case overview** combining encounters + scales + meds +
  risk for a patient (today they're separate tabs).

### 2.5 Dental 🟢 (feature-rich, utilitarian)
- ✅ Odontogram, treatment plans, xrays, lab orders, followups.
- Missing: a **doctor dental cockpit** (lands on treatments list) — today's chair
  schedule + **lab-order status board** (ordered → ready → delivered) + overdue
  follow-ups in one view; the data exists across pages but isn't consolidated.

### 2.6 Telemedicine 🟠
- `OnlineConsultations/Room.vue` is minimal (32 lines) — needs a **pre-call
  checklist** (camera/mic/network ready), in-call patient context panel, and the
  post-call summary (the VisitCompleted summary is wired server-side this session,
  but the room UI doesn't prompt the doctor to write diagnosis/Rx before ending).

---

## 3) خطة موصى بها (prioritized — business value × effort)

| # | البند | لماذا (قيمة العمل) | نطاق | خطر |
|---|---|---|---|---|
| D1 | **قُمرة قيادة موحّدة** لكل تخصص (today's queue + worklist + quick "start visit") — ابدأ بالنفسي/العصبي/الأسنان (التي تفتقدها) | أعلى أثر يومي على الإنتاجية | متوسط/كبير | متوسط |
| D2 | **إثراء ملف المريض لدى الطبيب** (شريط تنبيهات حساسية + أدوية فعّالة + خط زمني + روابط تخصصية + إجراءات سريعة) | سلامة + سرعة الكشف | متوسط | متوسط |
| D3 | **توحيد الهيدر في صفحتي OB/GYN** الناقصتين | اتساق فوري | صغير | منخفض |
| D4 | **مكوّن Skeleton + تبنّيه** في كل القوائم/التفاصيل | يقتل إحساس «الصفحة فاضية» نهائياً | صغير/متوسط | منخفض |
| D5 | **worklists تشغيلية**: متابعات متأخرة، مختبر شاذّ، مراقبة دواء مستحقّة، ملاحظات غير موقّعة | إيراد (لا متابعات ضائعة) + جودة | متوسط | منخفض |
| D6 | **تبنّي `UiEmptyState`/`UiButton`** عبر صفحات الأطباء | اتساق + صيانة | صغير (تدريجي) | منخفض |
| D7 | **جداول متجاوبة** (بطاقات على الجوّال) | استخدام بجانب السرير | متوسط | منخفض |
| D8 | **طباعة/تصدير موحّد** (ملخّص زيارة/وصفة من أي صفحة) | احتياج يومي | صغير/متوسط | منخفض |

**التسلسل المقترح:** D3 + D4 (صغيرة، فورية، آمنة) → D2 (ملف المريض، أعلى قيمة فردية)
→ D1 (قُمرات النفسي/العصبي/الأسنان) → D5 (worklists) → D6/D7/D8 تدريجياً.

> كل بند يُنفّذ على فرع، باختبار، بناء، ونشر مُتحقّق — لا كسر للخدمات الحيّة.
