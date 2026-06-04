# خطة تنفيذ وحدتي الطب النفسي + العصبي — Checklist تنفيذي كامل

> ### ✅ حالة التنفيذ: **NP0 → NP7 منفّذة بالكامل ومُختبَرة** (الوحدتان مطفأتان افتراضيًا)
> - NP0 الأساس · NP1 اللقاء+MSE+التشخيص · NP2 المقاييس (PHQ-9/GAD-7/HIT-6 + بوّابة المريض)
>   · NP3 تقييم الخطر C-SSRS + خطة السلامة + البانر + RBAC حسّاس · NP4 الأدوية+المراقبة+سجلّ
>   المواد الخاضعة · NP5 الإجراءات (بوتوكس يخصم المخزون) + مفكّرتا النوبات/الصداع · NP6 الدورات
>   (ECT/rTMS/كيتامين) + بوّابة الموافقة · NP7 لوحات الأدمن + إعدادات + أعلام AI + توثيق.
> - مجموعة اختبار `tests/Feature/Neuropsych/` خضراء بالكامل + الحُرّاس الستّة. المؤجَّل:
>   e-prescribing كامل للمواد الخاضعة (NP-Future-1) + عارض DICOM (NP-Future-2).


> رفيق تنفيذي لـ `docs/NEUROPSYCH_MODULE_PLAN.md` (ADR-NP01). يحصر **كل خطوة** — صغيرة
> وكبيرة — من NP0 حتى الإطلاق. القرار المعتمد: **وحدتان** `psychiatry` + `neurology`
> فوق **طبقة سريرية مشتركة** (تُبنى مرّة واحدة). الحالة: تصميم — يبدأ التنفيذ عند الإذن.

---

## 0) الاصطلاحات ومعيار «تمام» (DoD) لكل خطوة

- **سير كل مرحلة:** فرع `feature/neuropsych-NPx` → بناء → `vendor/bin/pint` (الملفات المتغيّرة فقط)
  → `php artisan test` (الموجّهة) → `npm run build` (إن تغيّرت الواجهة) → الحُرّاس الستّة →
  commit (بادئة `feat/test/docs`) → push.
- **DoD لأي شاشة:** بلا إيموجي · الإجراءات أيقونات + tooltip ثنائي اللغة · مودالات بـ
  `v-focus-trap` · النماذج بـ `<FormErrors>` + قفل `processing` · تأكيدات عبر `useConfirm`
  (لا `window.confirm`) · حالة فارغة · RTL/LTR · اختبار CRUD للمسار.
- **DoD لأي جدول (migration):** idempotent · additive · `branch_id` حيث ينطبق (الأحداث
  السريرية فقط؛ ملف المريض **مشترك** بلا branch) · backfill→1 · فهرس مركّب · اختبار عزل.
- **الثوابت:** OpenAI فقط للـ AI · لا أسرار في الكود · POST `/update`+`/delete` (لا PUT/DELETE)
  · كل ميزة AI خلف feature-flag + AiGate + PhiRedactor · سجلّات النفسي = أعلى حساسية (RBAC).
- **علم التفعيل:** الوحدتان OFF افتراضيًا؛ لا تظهران حتى يفعّلهما الأدمن.

---

## NP0 — الأساس + الطبقة المشتركة 🧪

### NP0.1 تسجيل الوحدتين
- ☐ `ModuleManager::MODULES`: إضافة `psychiatry` (ar «الطب النفسي»، en «Psychiatry»،
  أيقونة دماغ، لون `#7C3AED`، `is_core=false`).
- ☐ `MODULES`: إضافة `neurology` (ar «طب الأعصاب»، en «Neurology»، أيقونة، لون `#0EA5E9`).
- ☐ إضافة الاثنتين إلى `MEDICAL_MODULES`.
- ☐ migration بذر `module_settings` للوحدتين (enabled=0) + `cache()->forget`.
- ☐ اختبار: `ModuleManager::enable('psychiatry')` يُظهر القسم؛ معطّلة افتراضيًا.

### NP0.2 صلاحيات + أدوار
- ☐ migration: صلاحيات `psychiatry.{view,create,update,delete}` و`neurology.{…}`.
- ☐ **صلاحية حسّاسة منفصلة:** `neuropsych.sensitive.view` (ملاحظات العلاج النفسي + تقييم الخطر).
- ☐ منحها للأدوار السريرية المناسبة (طبيب/أدمن) — لا للسكرتارية افتراضيًا للحسّاسة.
- ☐ اختبار: دور بلا `sensitive.view` يُمنع (403) من قراءة الملاحظات الحسّاسة.

### NP0.3 حقول الطبيب
- ☐ migration `add_neuropsych_fields_to_doctors`: `np_track` (enum: psychiatry|neurology|both)،
  `np_subspecialty`، `np_qualifications`, `treats_children` (bool).
- ☐ تحديث نموذج `Doctor` ($fillable) + شاشة Doctors/Edit (حقول الـ track).

### NP0.4 ملف المريض المشترك (Shared، بلا branch)
- ☐ migration `create_neuropsych_profiles_table`: `patient_id` (unique)، `chief_complaint`،
  `hpi`, `past_psych_history`, `past_neuro_history`, `family_history`, `social_history`,
  `substance_history` (json), `developmental_history`, `risk_factors` (json), `current_meds`,
  timestamps + softDeletes. **لا `branch_id`** (مثل obgyn_profiles).
- ☐ نموذج `NeuropsychProfile` (علاقة patient، casts json).
- ☐ ربط بالحساسية الموجودة (allergies) لا تكرارها.

### NP0.5 الطبقة السريرية المشتركة (الأساس — تُبنى مرّة، تُستهلك من الوحدتين)
- ☐ `app/Services/NeuroPsych/` — خدمات مشتركة:
  - ☐ `ScaleEngine` (تعريف/حساب/تخزين نتائج المقاييس).
  - ☐ `RiskAssessmentService` (C-SSRS + خطة سلامة + إطلاق بانر التنبيه).
  - ☐ `MedicationPlanService` (خطط الدواء + جدولة المراقبة).
  - ☐ `NeuroPsychInvoiceService` (يعيد استخدام منطق `CosmeticDermaInvoiceService`).
- ☐ `resources/js/Components/Clinical/` — مكوّنات Vue مشتركة:
  - ☐ `MseForm.vue` (فحص الحالة العقلية المهيكل).
  - ☐ `ScaleRunner.vue` (عرض/ملء أي مقياس).
  - ☐ `ScaleTrendChart.vue` (اتجاه الدرجات زمنيًا).
  - ☐ `RiskAssessmentPanel.vue` (C-SSRS + خطة سلامة).
- ☐ trait `App\Models\Concerns\BelongsToBranch` على جداول الأحداث (لا الملف).
- ☐ اختبار وحدة لكل خدمة مشتركة.

### NP0.6 لوحات/تنقّل
- ☐ تخطيط `Doctor`/`Admin` Sidebar: قسمان (نفسي/عصبي) خلف `moduleKey`.
- ☐ ملفات routes: `routes/admin.php` + `routes/doctor.php` + `routes/patient.php` —
  مجموعات `module:psychiatry` / `module:neurology`.
- ☐ **commit NP0** + اختبار عزل أخضر.

---

## NP1 — اللقاء السريري + MSE + التشخيص

- ☐ migration `create_neuropsych_encounters_table`: `patient_id`, `doctor_id`, `module`
  (psychiatry|neurology), `visit_id?`, `encounter_date`, `note_format` (soap|dap|birp),
  `subjective/objective/assessment/plan` (text), `mse` (json)، `branch_id`، روابط فوترة
  (`invoice_id`,`invoice_item_id`) + `cost` + `completed_at` + softDeletes + فهارس.
- ☐ migration `add_diagnosis_to_encounters` أو جدول `encounter_diagnoses`: `code_system`
  (icd11|dsm5), `code`, `label`, `is_primary`.
- ☐ نماذج: `NeuropsychEncounter` + `EncounterDiagnosis`.
- ☐ متحكّمات: `Doctor/NeuropsychEncounterController` (index/store/update/destroy) — يقرأ params
  بالاسم (تحذير locale)، يربط الفوترة عند الإكمال.
- ☐ شاشة `Doctor/{Psychiatry|Neurology}/Encounter` تستخدم `MseForm.vue` + باحث ICD/DSM.
- ☐ خطة علاج: migration `treatment_plans` (problems/goals/interventions/target_date/status) +
  شاشة + تتبّع تقدّم.
- ☐ اختبارات: إنشاء لقاء يحفظ MSE+التشخيص · الإكمال يولّد فاتورة · إلغاء الإكمال يعكسها.
- ☐ بناء + حُرّاس + **commit NP1**.

---

## NP2 — الرعاية القائمة على القياس (Measurement-Based Care)

- ☐ migration `create_clinical_scales_table`: `key`, `name_ar/en`, `module`, `track`,
  `items` (json: الأسئلة/الخيارات/الأوزان), `scoring` (json: المدى/التفسير), `is_active`.
- ☐ migration `create_scale_results_table`: `patient_id`, `scale_key`, `answers` (json),
  `score`, `severity`, `entered_by` (doctor|patient), `encounter_id?`, `taken_at`.
  (مشترك على مستوى المريض — يتدفّق من البوابة.)
- ☐ بذر المقاييس القياسية: **PHQ-9, GAD-7, C-SSRS, MDQ, PCL-5, Y-BOCS, AUDIT, DAST,
  ASRS, Vanderbilt, EPDS, AIMS** (نفسي) + **MoCA, MMSE, MIDAS, HIT-6, UPDRS, NIHSS,
  EDSS, mRS** (عصبي) — مع نصوص ar/en وقواعد التسجيل.
- ☐ `ScaleEngine`: حساب الدرجة + التفسير (severity bands) لكل مقياس.
- ☐ **بوابة المريض:** `Patient/Neuropsych/Scales` — المريض يملأ المقياس **قبل الزيارة**؛
  الدرجة تتدفّق للملف. (يحترم بوابة المريض الموجودة + الموافقة.)
- ☐ عرض الطبيب: `ScaleRunner.vue` (ملء أثناء الزيارة) + `ScaleTrendChart.vue` (الاتجاه).
- ☐ اختبارات: حساب درجة PHQ-9 صحيح · تخزين/استرجاع · اتجاه عبر زيارتين · بوابة المريض تحفظ.
- ☐ بناء + حُرّاس + **commit NP2**.

---

## NP3 — تقييم الخطر + السلامة (حرج — سلامة المريض)

- ☐ migration `create_risk_assessments_table`: `patient_id`, `type` (suicide|violence|self_harm),
  `tool` (c-ssrs|…), `answers` (json), `risk_level` (low|moderate|high), `safety_plan` (json/text),
  `assessed_by`, `assessed_at`, `encounter_id?`. **حسّاس** (RBAC + تدقيق).
- ☐ `RiskAssessmentService`: تقييم C-SSRS → `risk_level`؛ عند **high** أو PHQ-9 بند 9 > 0
  → يُطلق علمًا على الملف.
- ☐ **بانر تنبيه السلامة:** يُعاد استخدام نمط بانر التنبيهات الطبية في `Patients/Show`؛
  يظهر فورًا للطبيب/السكرتارية عند خطر مرتفع، مع رابط لخطة السلامة.
- ☐ إلزام **خطة سلامة** قبل حفظ تقييم عالي الخطورة (validation).
- ☐ تدقيق: كل قراءة لتقييم خطر تُسجَّل في `AuditLogger`.
- ☐ **اختبار حرج:** C-SSRS موجب → يُنشئ علمًا + يظهر البانر؛ حفظ خطر عالٍ بلا خطة سلامة
  يُرفض (422)؛ دور بلا `sensitive.view` يُمنع.
- ☐ بناء + حُرّاس + **commit NP3**.

---

## NP4 — الأدوية + المراقبة + المواد الخاضعة (تسجيل فقط في MVP)

- ☐ migration `create_medication_plans_table`: `patient_id`, `drug`, `class`, `dose`,
  `frequency`, `route`, `started_at`, `stopped_at?`, `is_controlled` (bool), `notes`.
- ☐ migration `create_medication_monitoring_table`: `medication_plan_id`, `type`
  (clozapine_anc|lithium_level|metabolic), `due_at`, `result?`, `result_at?`, `status`.
- ☐ migration `create_controlled_substance_register`: سجلّ مدقّق للوصفات الخاضعة (تسجيل فقط).
- ☐ خدمة `MedicationPlanService`: جدولة المراقبة تلقائيًا (كلوزابين ANC أسبوعي، ليثيوم دوري،
  أيضي لمضادّات الذهان) عبر cron (branch-aware، `runForBranch`).
- ☐ **تكامل فاحص تعارض الأدوية الموجود** (`DrugInteractionChecker`) لحظة إضافة دواء.
- ☐ شاشات: إدارة الأدوية + لوحة المراقبة المستحقّة + سجلّ المواد الخاضعة.
- ☐ اختبارات: إضافة دواء تجدول المراقبة · فحص التعارض يُستدعى · المتأخّر يظهر · سجلّ مدقّق.
- ☐ بناء + حُرّاس + **commit NP4**.

---

## NP5 — أدوات الأعصاب (Neurology track)

- ☐ migration `create_neuro_exams_table`: `encounter_id`, الأعصاب القحفية (json I–XII)،
  motor/sensory/reflexes/coordination/gait/romberg (json).
- ☐ migration `create_seizure_diary_table`: `patient_id`, `occurred_at`, `type` (ILAE),
  `duration`, `triggers`, `notes`, `entered_by`. (بوابة المريض.)
- ☐ migration `create_headache_diary_table`: `patient_id`, `date`, `intensity`, `duration`,
  `ichd3_type`, `aura` (bool), `meds_taken`, `triggers`. (بوابة المريض.)
- ☐ migration `create_neuro_procedures_table`: `patient_id`, `type` (emg_ncs|lp|eeg|botox|
  nerve_block), `findings` (json), `report_path?`, روابط مخزون (`supply_id`,`consumption_qty`,
  `supply_transaction_id`) للبوتوكس + روابط فوترة + `branch_id`.
- ☐ **إرفاق التصوير:** حقل `report_path`/`image_path` لتقارير MRI/CT/EEG (لا عارض DICOM — مؤجَّل).
- ☐ خدمات/شاشات: الفحص العصبي · مفكّرتا النوبات/الصداع (طبيب + بوابة مريض) · الإجراءات
  (البوتوكس يخصم المخزون مثل حقن التجميل).
- ☐ اختبارات: مفكّرة النوبات من البوابة تحفظ · إجراء بوتوكس يخصم المخزون + يفوتر · إرفاق تقرير.
- ☐ بناء + حُرّاس + **commit NP5**.

---

## NP6 — الدورات العلاجية (ECT/rTMS/كيتامين) + الموافقات

- ☐ migration `create_treatment_courses_table` + `course_sessions`: سلسلة جلسات (يعاد استخدام
  نمط جلسات التجميل: عدد/مكتمل/فوترة لكل جلسة + موافقة).
- ☐ migration `neuropsych_consents` (أو إعادة استخدام نمط CosmeticConsent): موافقة عامة +
  موافقة ECT/rTMS + موافقة تسجيل الجلسة.
- ☐ بوّابة الموافقة: المريض يوقّع رقميًا (نمط `Patient/Dental/Consent/Sign` الموجود).
- ☐ منع تنفيذ جلسة دورة بلا موافقة موقّعة (نمط بوابة موافقة التجميل).
- ☐ اختبارات: إنشاء دورة + جلسات · الجلسة تفوتر · بلا موافقة → تُمنع.
- ☐ بناء + حُرّاس + **commit NP6**.

---

## NP7 — التقارير + لوحة الأدمن + تكامل الـ AI + التوثيق

- ☐ لوحة أدمن لكل وحدة: `Admin/{Psychiatry|Neurology}/Dashboard` (مؤشّرات: لقاءات، خطر مرتفع
  نشط، مراقبة دواء متأخّرة) + `Settings` (تسعير، مقاييس مفعّلة) + `Reports`.
- ☐ تقارير PDF: ملخّص نفسي/عصبي، تقرير اتجاه المقاييس، خطاب تحويل (نمط تقارير الأطفال الموجود).
- ☐ تكامل AI (خلف feature-flags جديدة في `AiFeatureFlag` + `AiSettingsSeeder`):
  - ☐ `np_note_assist` (صياغة ملاحظة من MSE — غير تشخيصي).
  - ☐ `np_risk_flag` (كشف خطر من PHQ-9 بند 9 / C-SSRS → بانر).
  - ☐ `np_session_transcription` (تفريغ جلسة العلاج — **بموافقة + PhiRedactor**).
  - ☐ ربط `DrugInteractionChecker` الموجود.
- ☐ شارة الـ Ai تظهر فقط عند تفعيل العلم (نمط `AiAssist.vue`).
- ☐ ترجمات `lang/ar.json` + `lang/en.json` لكل النصوص الجديدة.
- ☐ تحديث `CLAUDE.md` (إضافة الوحدتين للجدول + الـ slugs) + هذا الـ checklist (تأشير).
- ☐ بناء + حُرّاس + **المجموعة الكاملة خضراء** + **commit NP7**.

---

## الفحوصات الشاملة (تسري على كل المراحل)

- ☐ **branch-awareness:** كل جداول الأحداث `BelongsToBranch` + اختبار عزل تحت `tests/Feature/Branch/`؛
  ملف المريض والمقاييس وتقييم الخطر **مشتركة** (بلا branch). `data:integrity-check` يبقى نظيفًا.
- ☐ **الخصوصية:** اختبار وصول لكل سجلّ حسّاس (ملاحظات نفسية + خطر) — منع بلا `sensitive.view`،
  وتدقيق كل قراءة.
- ☐ **الحُرّاس الستّة** خضراء على كل شاشات الوحدتين (إيموجي/confirm/عناوين/أزرار/مودالات/CRUD).
- ☐ **الترجمة:** 0 نص إنجليزي ثابت في عناوين/tooltips.
- ☐ **القُصّر:** قواعد وصول الوصيّ للمرضى الأطفال (ADHD/تطوّري) — اختبار.
- ☐ **الأداء:** فهارس على (`patient_id`)، (`patient_id`,`module`)، (`branch_id`,`completed_at`).

## مصفوفة الاختبار (الحدّ الأدنى)
- وحدة: ScaleEngine (درجات كل مقياس) · RiskAssessmentService · MedicationPlanService (الجدولة).
- Feature: لكل وحدة → عرض الفهرس · إنشاء لقاء + MSE + تشخيص · إكمال يفوتر/يعكس · مقياس من
  البوابة · C-SSRS موجب → بانر · حفظ خطر عالٍ بلا خطة → 422 · بوتوكس يخصم مخزون · دورة بلا
  موافقة → تُمنع · منع `sensitive.view`.
- عزل فرع: لقاء/إجراء/دورة لفرع لا يظهر لفرع آخر؛ الملف مشترك.

## الإطلاق
- ☐ بذر بيانات عرض (staging فقط) · مراجعة طبية للقوالب والمقاييس · تفعيل الوحدة على staging
  والتحقّق end-to-end · تدريب الطاقم · ثم التفعيل على الإنتاج (الوحدتان تبقيان OFF حتى ذلك).

## المسارات المؤجَّلة (خارج هذا الـ MVP)
- NP-Future-1: e-prescribing كامل للمواد الخاضعة (تكامل تنظيمي محلي).
- NP-Future-2: عارض DICOM مدمج.
