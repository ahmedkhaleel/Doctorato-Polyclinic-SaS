# Physiotherapy Module — Full Design & Integration Plan
## خطة مديول العلاج الطبيعي (تصميم + دمج كامل مع النظام)

**Date:** 2026-06-08 · **Type:** module design + IA + backend + DB + frontend +
visualization plan (خطة قبل التنفيذ) · **Slug:** `physiotherapy`

> الهدف: مديول **علاج طبيعي متكامل** يندمج في كل النظام **بنفس نمط بقية التخصصات**
> (derma/obgyn/neuropsych) — طبيب + أدمن + سكرتارية + مريض، حجوزات كاملة (جلسة
> مفردة + سلسلة متكررة + باقات + زيارة منزلية)، مع مخططات وشارتس ومجسمات JS
> (خريطة ألم، ROM، قوة عضلية، مقاييس وظيفية، تقدّم الأهداف، نموذج 3D اختياري).

---

## 0) القالب القانوني للدمج (مبني على الكود الفعلي)

أي مديول طبي في النظام يُسجَّل عبر:
- **`ModuleManager`**: إضافة `physiotherapy` إلى `MODULES` (slug/permissions) و
  **`MEDICAL_MODULES`** (`['derma','dental','pediatric','obgyn','psychiatry','neurology']`
  → نضيف `'physiotherapy'`). هذا وحده يفعّل: أطباء/زيارات/حجوزات/تسعير/عمولة لهذا التخصص.
- **مجموعة migrations** تحاكي مجموعة neuropsych (≈18 ملف) — idempotent + branch-aware.
- **نقاط لمس الخدمات** (تُحدّث للتعرّف على الموديول الجديد، غالبًا تلقائيًا عبر
  `MEDICAL_MODULES`): `BookingWorkflowService` · `OutcomeService` ·
  `CommissionCalculator` · `Pricing/PricingResolver` · `SmsNotificationService` ·
  `Admin/BookingController` · خدمة فوترة جديدة `PhysioBillingService` (نمط
  `NeuroPsychBillingService`).
- **المسارات**: مجموعات في `doctor.php` / `admin.php` / `secretary.php` / `patient.php`
  بحماية `module:physiotherapy` + `permission:physiotherapy.*`.
- **السايدبار**: مجموعة `physiotherapy` (featured) تحت محور **«التخصصات»** +
  `GROUP_ORDER` + `PILLARS` — يحرسها `AdminSidebarIntegrityTest`.
- **لوحة الزيارة**: `SpecialtyPanel` → `PhysioPanel` (طبيب + أدمن) + extras في
  `DoctorVisitController`/`Admin\VisitController`.
- **التصوّر السريري**: إعادة استخدام `Components/Charts/*` (TrendLine, BodyMap,
  ProgressRing, CalendarHeatmap, Sparkline) + بدائيات جديدة (RadialChart, StrengthGrid).
- **القياسات الوظيفية = نفس محرك MBC**: `ScaleResult` + `ScaleEngine` المستخدم
  في النفسي يصلح حرفيًا لمقاييس العلاج الطبيعي (ODI/NDI/VAS…) — إعادة استخدام لا بناء.
- **بذور ديمو**: توسعة `SpecialtyDoctorDemoSeeder` بطبيب علاج طبيعي + بياناته.
- **اختبارات**: `tests/Feature/Physio/`.

---

## 1) المتطلبات الدولية لمديول العلاج الطبيعي (طبيب + أدمن)

### أ) التقييم (Assessment) — وفق إطار ICF + ICD-10
- **شكوى ذاتية (Subjective):** الشكوى الحالية HPC، تاريخ مرضي PMH، الأدوية،
  **علامات حمراء (Red flags)** + موانع/احتياطات (pacemaker للـTENS/US، خثار، كسر…).
- **فحص موضوعي (Objective):**
  - **مدى الحركة ROM (Goniometry):** لكل مفصل/مستوى، AROM/PROM، بالدرجات، مقابل
    **القيم الطبيعية المعيارية** (corridor).
  - **القوة العضلية MMT (Oxford 0–5):** لكل مجموعة عضلية.
  - **الألم:** مقياس NPRS/VAS 0–10 + **خريطة جسم** (موقع/نوع/شدة).
  - اختبارات خاصة (Special tests)، القوام (Posture)، المشية (Gait)، التوازن،
    فحص عصبي مبدئي، قياسات (محيط/طول الطرف).
- **التشخيص:** ICD-10 + **ICF** (بنية/وظيفة الجسم · النشاط · المشاركة).

### ب) مقاييس النتائج المعيارية (PROMs) — تُدار كـ MBC
ODI (أسفل الظهر) · NDI (الرقبة) · DASH/QuickDASH (طرف علوي) · LEFS (طرف سفلي) ·
KOOS/HOOS (ركبة/ورك) · WOMAC (خشونة) · Berg Balance · TUG · 6MWT · PSFS ·
Roland-Morris · Barthel/FIM (ADL). كلها {مقياس → درجة → شدّة/اتجاه + **MCID**}.

### ج) خطة العلاج (Plan of Care)
- **أهداف SMART** (قصيرة/طويلة المدى) + قائمة مشاكل + نسبة تحقّق.
- **الطرائق (Modalities):** كهربائية (TENS, US, IFC, Laser, Shockwave, Traction)
  مع **تسجيل البارامترات** (جرعة/تردد/زمن) · علاج يدوي · علاج حركي · مائي · تثقيف.
- **التردد/المدة:** مثل 3×/أسبوع × 4 أسابيع = 12 جلسة.

### د) الجلسات + البرنامج المنزلي (HEP)
- **سجل جلسات** (SOAP لكل جلسة + الطرائق المطبّقة + التمارين + الحضور + التقدّم).
- **وصف تمارين / HEP:** مكتبة تمارين (اسم/منطقة/صورة/فيديو/افتراضي sets/reps/hold)،
  تُوصف للمريض (sets/reps/hold/freq/resistance)، **ورقة HEP قابلة للطباعة + عرض في
  بوّابة المريض** مع تتبّع الإنجاز.
- **إعادة تقييم** دورية (مقارنة المقاييس مقابل MCID) + **ملخّص خروج (Discharge)**.

### هـ) الإحالة والفوترة
- **إحالة واردة** من طبيب (تشخيص + احتياطات).
- **باقات جلسات** (موجودة: package bundles) + **زيارة منزلية** + تسعير لكل جلسة/تقييم.

---

## 2) الداتا بيز (جداول جديدة — idempotent + branch-aware)

| الجدول | الغرض |
|---|---|
| `physio_assessments` | تقييم (subjective/objective JSON, ICD, ICF, red_flags, posture, gait) |
| `physio_rom_measurements` | ROM لكل (مفصل، مستوى): arom, prom, normal_ref, side, recorded_at |
| `physio_strength_tests` | MMT: muscle_group, grade 0–5, side, recorded_at |
| `physio_pain_points` | خريطة الألم: view, x, y, intensity 0–10, type (reuse نمط `derma_lesions`) |
| `physio_treatment_plans` | الأهداف + الطرائق + التردد + الحالة + completed/estimated_sessions |
| `physio_goals` | هدف SMART: type, baseline, target, current, achieved%, due |
| `physio_sessions` | جلسة: soap, modalities JSON(+params), techniques, attended, cost, visit_id |
| `exercises` (catalog) | مكتبة التمارين: name_ar/en, region, media_path, default sets/reps/hold |
| `physio_exercise_prescriptions` | وصف للمريض: exercise_id, sets, reps, hold, freq, resistance, status |
| `hep_adherence_logs` | تتبّع إنجاز المريض للبرنامج المنزلي (date, done, pain_after) |
| (reuse) `scale_results` | مقاييس النتائج (ODI/VAS/LEFS…) عبر `ScaleEngine` |
| (settings) `module_settings` | أسعار/تهيئة العلاج الطبيعي (consultant/specialist/followup/session) |

**Doctors:** إضافة حقول `physiotherapy_consultation_fee` + عمولة (migration
`add_physio_fields_to_doctors`، نمط neuropsych).

---

## 3) الباك-إند

- **النماذج:** `PhysioAssessment, PhysioRomMeasurement, PhysioStrengthTest,
  PhysioPainPoint, PhysioTreatmentPlan, PhysioGoal, PhysioSession, Exercise,
  PhysioExercisePrescription, HepAdherenceLog` (كلها `BelongsToBranch` + علاقات).
- **خدمة مشتركة** `App\Services\Physio\` (نمط `NeuroPsych`): `RomNormatives`
  (القيم الطبيعية للمفاصل)، `OutcomeMeasures` (تعريف ODI/VAS… عبر `ScaleEngine`)،
  `PhysioBillingService` (يفوتر الجلسات/التقييم في فاتورة module-tagged + عمولة).
- **المتحكّمات:**
  - `Doctor`: `DoctorPhysioController` (dashboard, patients, assessment, ROM/MMT,
    pain map, plan, sessions, HEP prescribe), `DoctorPhysioExerciseController`.
  - `Admin`: `AdminPhysioController` (dashboard, cases, sessions, outcomes,
    exercises catalog, reports, settings), `ExerciseController` (CRUD مكتبة).
  - `Secretary`: `SecretaryPhysioController` (overview إداري — مواعيد/باقات/فوترة
    فقط، **لا بيانات سريرية**).
  - `Patient`: `PhysioHepController` (عرض البرنامج المنزلي + تتبّع الإنجاز).
- **التسعير/العمولة/الحجز:** `PricingResolver` + `module_settings` (dual-write)؛
  `CommissionCalculator` يلتقط `physiotherapy` عبر `MEDICAL_MODULES`؛ `booking_type`
  = `physiotherapy_consultation` / `physiotherapy_session`.
- **الحساسية/التدقيق:** لا بيانات حسّاسة خاصة (لا `view_sensitive`)، لكن قراءة
  السجلات السريرية عبر الأدمن تُسجَّل في `MedicalDataAccessLog` كالمعتاد.

### المسارات (مجموعات module:physiotherapy)
- `doctor.php`: `/doctor/physio` (dashboard) · `/patients/{p}` · `/assessment` ·
  `/rom` · `/strength` · `/pain` · `/plan` · `/sessions` · `/exercises` · `/hep`.
- `admin.php`: `/admin/physio` + cases/sessions/outcomes/exercises/reports/settings.
- `secretary.php`: `/secretary/physio/overview`.
- `patient.php`: `/{locale}/patient/physio/hep` (+ تتبّع).

---

## 4) الفرونت-إند

- **صفحات الطبيب** (`Pages/Doctor/Physio/*`): Dashboard · Patients/Index+Show
  (ملف العلاج الطبيعي: تقييم + ROM + قوة + ألم + خطة + جلسات + HEP) · Exercises
  (مكتبة) · Sessions.
- **صفحات الأدمن** (`Pages/Admin/Physio/*`): Dashboard · Cases · Sessions ·
  Outcomes · Exercises CRUD · Reports · Settings (أسعار/تهيئة).
- **السكرتارية** (`Pages/Secretary/Physio/Index`): front-desk (مواعيد/باقات/فوترة)
  بنمط `DeskHeader` الموحّد، بلا سريريات.
- **المريض** (`Pages/Patient/Physio/Hep`): ورقة البرنامج المنزلي (تمارين بصور/فيديو
  + sets/reps + زر «تم» + ألم بعد).
- **لوحة الزيارة** (`Components/Doctor/Visit/Panels/PhysioPanel.vue`): يُعرض تلقائيًا
  عبر `SpecialtyPanel` (طبيب + أدمن) — ملخّص: آخر VAS + تقدّم الأهداف (ProgressRing)
  + جلسات اليوم + اتجاه المقياس + روابط الملف الكامل.
- **ملف المريض**: تبويب علاج طبيعي.
- **السايدبار**: مجموعة `physiotherapy` (featured) تحت محور «التخصصات».

---

## 5) المخططات والشارتس والمجسمات (JS) — قلب الطلب

كلها على **مجموعة الشارتس المشتركة** (SVG، بلا مكتبة) + بدائيتين جديدتين:

| العنصر البصري | البدائية | المعيار |
|---|---|---|
| **خريطة الألم** (أمامي/خلفي، نقاط شدّة/نوع) | `BodyMap` (موجود) | Pain body chart |
| **اتجاه ROM** لكل مفصل عبر الجلسات + **نطاق طبيعي** | `TrendLine` (موجود) | Goniometry |
| **رادار ROM** (محاور متعددة: انثناء/تبعيد/دوران… الحالي مقابل الطبيعي) | **`RadialChart` (جديد)** | ROM profile |
| **شبكة القوة العضلية** (0–5 ملوّنة لكل عضلة) | **`StrengthGrid` (جديد)** نمط odontogram/perio | MMT |
| **اتجاه مقاييس النتائج** (ODI/VAS/LEFS) + خط **MCID** + الهدف | `TrendLine` + `Sparkline` | PROMs |
| **حلقات تقدّم الأهداف** (% نحو الهدف الوظيفي) | `ProgressRing` (موجود) | SMART goals |
| **حرارة الحضور والالتزام بالـHEP** | `CalendarHeatmap` (موجود) | Adherence |
| **نموذج 3D للجسم/الهيكل** (تعليم مناطق الألم/العلاج) | **اختياري: `Body3D` (three.js, lazy)** | 3D mapping |

### ADR — المجسّم ثلاثي الأبعاد (three.js)
- **الافتراضي:** `BodyMap` (SVG، موجود، خفيف، RTL) — يغطّي 95% من الحاجة.
- **اختياري (premium):** عارض 3D تفاعلي بـ **three.js + نموذج GLB بشري** —
  **lazy-loaded** خلف إعداد (نمط `VideoRoom` Agora 1.5MB المُحمّل كسولًا)، فلا يثقل
  الحزمة الأساسية. يُفعّل فقط للعيادات التي تريده. **القرار:** ابدأ بالـSVG؛ أضف 3D
  كطبقة اختيارية معزولة لاحقًا (chunk منفصل، يحترم `prefers-reduced-motion`).

---

## 6) الحجوزات الكاملة

- **booking_type** `physiotherapy_consultation` (تقييم) + `physiotherapy_session` (جلسة).
- **سلسلة متكررة:** توليد N حجز بتردد (3×/أسبوع × 4 أسابيع) من خطة العلاج — حجز واحد
  لكل جلسة، مرتبط بالخطة (نمط cron `CreateDailyVisits` branch-aware).
- **باقات الجلسات:** عبر `package_bundles` الموجودة (12 جلسة بسعر).
- **زيارة منزلية:** علم `home_visit` + رسوم إضافية.
- **معالج (therapist):** تعيين الطبيب/الأخصائي؛ الجدولة عبر نظام الـschedules الموجود.
- يندمج في كل أسطح الحجز (واجهة/مريض/سكرتارية/أدمن) لأن `MEDICAL_MODULES` يفعّلها.

---

## 7) خطة التنفيذ المرحلية (كلٌّ بفرع + اختبار + نشر)

| مرحلة | المحتوى | الخطر |
|---|---|---|
| **PT-0** | تسجيل الموديول (`ModuleManager` + MEDICAL_MODULES) + migrations الأساس (settings/permissions/doctor fields/pricing) + `module:physiotherapy` gate | منخفض |
| **PT-1** | جداول السريريات + النماذج + `Physio` service layer (RomNormatives/OutcomeMeasures via ScaleEngine) | متوسط |
| **PT-2** | متحكّمات + مسارات الطبيب: تقييم + ROM + MMT + خريطة ألم + خطة + جلسات (+فوترة/عمولة) | متوسط |
| **PT-3** | البدائيات الجديدة `RadialChart` + `StrengthGrid` + لوحة الزيارة `PhysioPanel` (طبيب+أدمن) | متوسط |
| **PT-4** | مكتبة التمارين + وصف HEP + بوّابة المريض (ورقة HEP + تتبّع) | متوسط |
| **PT-5** | الأدمن (dashboard/cases/outcomes/exercises/reports/settings) + السكرتارية (overview) + السايدبار (محور التخصصات) | متوسط |
| **PT-6** | الحجوزات الكاملة (سلسلة متكررة + باقات + زيارة منزلية) + booking_type في كل الأسطح | متوسط |
| **PT-7** | مقاييس النتائج (PROMs) عبر MBC + اتجاهات + MCID + أهداف | منخفض |
| **PT-8** | (اختياري) نموذج 3D `Body3D` (three.js lazy chunk) | متوسط/معزول |
| **PT-9** | بذور ديمو + توثيق `CLAUDE.md` + `PHYSIOTHERAPY_BUILD_CHECKLIST.md` | منخفض |

---

## 8) الاختبار والسلامة (لا شيء يُكسر)

- migrations idempotent + `branch_id` nullable + backfill→1 + فهارس مركّبة؛ staging أولًا.
- كل صفحة Vue جديدة → `npm run build` + commit (فحص manifest في CI).
- حُرّاس الواجهة (Emoji/IconLabel/ImageAlt/FocusTrap) + **حارس السايدبار** (إضافة
  مجموعة `physiotherapy` لمحور «التخصصات» — وإلا يسقط الحارس).
- `tests/Feature/Physio/`: تفعيل الموديول، تدفّق الطبيب (تقييم→جلسة→فوترة module-tagged
  + عمولة)، عقد لوحة الزيارة، الحجز المتكرر، عقود الشارتس، HEP للمريض.
- المجموعة الكاملة خضراء + CI/Deploy/health بعد كل مرحلة.
- **الترخيص الطبي:** تنبيه موانع/احتياطات (red flags) قبل الطرائق الكهربائية — بانر
  بنمط تنبيهات الأسنان الطبية.

---

## 9) المقايضات والمخاطر

| المخاطرة | التخفيف |
|---|---|
| إعادة اختراع المقاييس | إعادة استخدام `ScaleEngine/ScaleResult` (MBC) حرفيًا |
| ثِقَل 3D | الافتراضي SVG؛ 3D اختياري lazy-chunk معزول |
| تعقيد ROM/MMT الإدخالي | شبكات إدخال مدمجة (نمط perio chart) + قيم طبيعية افتراضية |
| كسر أسطح الحجز | `MEDICAL_MODULES` يفعّل تلقائيًا؛ اختبار حجز لكل سطح |
| تضخّم السايدبار | محور «التخصصات» موجود — صفّ واحد يُضاف (محمي بالحارس) |

---

## 10) ملخّص القرار

> العلاج الطبيعي يندمج **بنفس قالب neuropsych المُثبت** (تسجيل الموديول + مجموعة
> migrations + نقاط لمس الخدمات)، ويعيد استخدام: محرك MBC للمقاييس، مجموعة الشارتس،
> BodyMap، باقات/حجوزات النظام. الجديد الجوهري: جداول ROM/MMT/الألم/الخطة/الجلسات/
> التمارين/HEP + بدائيتان بصريتان (RadialChart, StrengthGrid) + ورقة HEP للمريض +
> (اختياري) مجسّم 3D معزول. يُبنى على 10 مراحل، كلٌّ باختبار ونشر متحقّق، **دون كسر**،
> ويظهر تلقائيًا لكل الأدوار (طبيب/أدمن/سكرتارية/مريض) كبقية التخصصات.
