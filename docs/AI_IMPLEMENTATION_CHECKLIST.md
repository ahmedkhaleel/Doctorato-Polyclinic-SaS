# خطة تنفيذ الذكاء الاصطناعي — Checklist لكل مرحلة

> مرجع التتبّع التنفيذي لخطة `AI_INTEGRATION_PLAN.md`. كل بند مربّع تحديد `[ ]`.
> **لا يُعتبر أي بند منجزًا إلا بعد اجتياز "تعريف الإنجاز" أسفله.**
> الحالة: مقترحة · التاريخ: 2026-06-01

---

## 0. أعراف عامة تُطبَّق في كل مرحلة (حتى لا تُنسى)

مأخوذة من `CLAUDE.md` — تُراجَع مع كل PR:

- [ ] **Migrations**: idempotent + data-safe (تحقّق `Schema::hasTable/hasColumn`)، `branch_id` nullable + backfill→1 + فهرس مركّب حيث يلزم.
- [ ] **HTTP**: التطبيق يستخدم `POST /update` و`POST /delete` — **ليس** PUT/DELETE.
- [ ] **Inertia**: أي صفحة Vue جديدة → `npm run build` + commit لـ `public/build/manifest.json` (CI يفحصها).
- [ ] **الصلاحيات**: أي ميزة جديدة تُسجَّل في `config/permissions.php` + تُمنح في `RoleSeeder`.
- [ ] **الإعدادات الحساسة**: مفاتيح API تُضاف لـ `Setting::isEncryptedKey()` (تشفير).
- [ ] **فرع-واعٍ**: الجداول التشغيلية تُختم بـ `branch_id` (Console = all-branches، HTTP = الفرع الحالي).
- [ ] **بلا إيموجي**: لا أيقونات إيموشن — SVG/عناصر فقط (يفحصها `NoEmojiInPagesTest`).
- [ ] **ثنائي اللغة**: كل نص ar/en + احترام `prefers-reduced-motion` في الأنيميشن.
- [ ] **الهوية**: navy `#1B365D` + gold `#C4A265`، RTL/LTR.
- [ ] **الإنتاج**: `BRANCHES_ENABLED` يبقى false؛ لا تُكتب مفاتيح/أسعار في الكود.

### تعريف الإنجاز (Definition of Done) — لكل مرحلة
- [ ] كل بنود المرحلة منفّذة.
- [ ] اختبارات المرحلة خضراء + `composer test` كامل أخضر (1078+).
- [ ] `./vendor/bin/pint` نظيف على الملفات المعدّلة.
- [ ] `npm run build` ناجح + `manifest.json` مُحدّث ومُلتزَم.
- [ ] الميزة تعمل مع `ai_enabled=false` (بديل آمن، لا 500).
- [ ] commit برسالة واضحة + `Co-Authored-By` + push.

---

## المرحلة 0 — البنية التحتية (Foundation)

> الهدف: أساس آمن قابل للتشغيل/الإيقاف، **دون تفعيل أي ميزة بعد**.

### 0.1 الاعتماد والتهيئة
- [ ] `composer require openai-php/laravel`.
- [ ] `config/ai.php` (provider، نماذج افتراضية، timeouts، توكنز افتراضية) — تقرأ من `Setting`/env.
- [ ] إضافة مفاتيح env اختيارية (`OPENAI_API_KEY` كـ fallback للتطوير فقط).

### 0.2 الإعدادات (Settings)
- [ ] إضافة مفاتيح `ai_*` الـ14 (راجع الخطة §3.1) في `SettingSeeder` (group=`ai`، قيم افتراضية آمنة، `ai_enabled=false`).
- [ ] إضافة `ai_openai_api_key` (+ أي مفتاح سرّي) إلى `Setting::isEncryptedKey()`.
- [ ] دعم per-branch override (موجود أصلًا عبر `settings.branch_id`).

### 0.3 قاعدة البيانات (Migrations + Models)
- [ ] migration `ai_request_logs` (feature, model, user_id, branch_id, prompt_tokens, completion_tokens, cost_usd, latency_ms, status, error, meta JSON, created_at) — idempotent + فهارس (feature، created_at، branch_id).
- [ ] migration `ai_feature_flags` (key unique, enabled, model_override, label_ar, label_en, group).
- [ ] migration `ai_prompt_templates` (feature, locale, system_prompt, user_template, version, is_active) — unique(feature, locale).
- [ ] Models: `AiRequestLog` (+ `StampsBranch`)، `AiFeatureFlag`، `AiPromptTemplate`.
- [ ] Seeder: `AiFeatureFlagSeeder` (يبذر كل الميزات معطّلة) + `AiPromptTemplateSeeder` (قوالب افتراضية ar/en).

### 0.4 طبقة الخدمة (Services)
- [ ] `app/Services/Ai/Contracts/AiDriver.php` — واجهة: `chat/vision/embed/transcribe/moderate`.
- [ ] `Drivers/OpenAiDriver.php` — تنفيذ OpenAI.
- [ ] `Drivers/NullDriver.php` — بديل آمن (يرجع "غير مفعّل" بلا استثناء).
- [ ] `AiManager.php` — يقرأ الإعدادات، يختار الـ driver، يفوّض للـ Gate.
- [ ] `AiGate.php` — يتحقق: `ai_enabled` + feature flag + الميزانية + حد المعدّل + `PhiRedactor`.
- [ ] `AiCostMeter.php` — حساب التكلفة من التوكنز + كتابة `ai_request_logs`.
- [ ] `PhiRedactor.php` — إخفاء/استرجاع الاسم/الهاتف/الرقم القومي.
- [ ] `AiServiceProvider` — ربط (bind) الواجهة بالـ driver المختار.

### 0.5 الصلاحيات
- [ ] `config/permissions.php`: module `ai` بأفعال `view, manage, prompts, logs, doctor`.
- [ ] `RoleSeeder`: منح super_admin (*) + admin (view/manage/prompts/logs)؛ `ai.doctor` للأدوار المخوّلة.
- [ ] migration `grant_ai_perms_to_existing_roles` (backfill إضافي آمن).

### 0.6 لوحة الأدمن (Controllers + Routes + Vue)
- [ ] `Admin/AiSettingsController` — `index/update` (POST) للإعدادات + `testConnection` (POST).
- [ ] `Admin/AiFeatureController` — `index/update` (POST) لمفاتيح الميزات.
- [ ] `Admin/AiPromptController` — `index/update` (POST) للقوالب.
- [ ] `Admin/AiUsageController` — لوحة الاستهلاك/التكلفة.
- [ ] `Admin/AiLogController` — السجلات + فلترة + تصدير.
- [ ] routes في `routes/admin.php` تحت `permission:ai.view|ai.manage` (OR-gated) + GET/POST فقط.
- [ ] Vue: `Admin/Ai/Settings.vue`, `Features.vue`, `Prompts.vue`, `Usage.vue`, `Logs.vue`.
- [ ] مجموعة سايدبار «الذكاء الاصطناعي» في `AdminLayout.vue` (أيقونة SVG، `moduleKey`/permission gating).
- [ ] زر **اختبار الاتصال** يعرض نجاح/فشل + النموذج المتاح.

### 0.7 الاختبارات (المرحلة 0)
- [ ] `AiManagerTest` — يختار driver، يحترم kill-switch (NullDriver عند الإيقاف).
- [ ] `AiGateTest` — يمنع عند تعطيل الميزة/تجاوز الميزانية/الحد.
- [ ] `PhiRedactorTest` — إخفاء/استرجاع صحيح.
- [ ] `AiCostMeterTest` — حساب تكلفة + كتابة سجل.
- [ ] `AiSettingsPageTest` — الصفحة تُحمَّل، المفتاح يُشفّر، testConnection.
- [ ] تحديث `PermissionsCatalogTest` + `InertiaPagesExistTest` + `NoEmojiInPagesTest`.
- [ ] **تعريف الإنجاز** ✔ (بناء + lint + commit + push).

---

## الموجة 1 — مكاسب سريعة (بلا بيانات طبية)

> نمط موحّد لكل ميزة: `Features/<X>.php` → نقطة استدعاء (controller/زر) → feature flag →
> قالب prompt مبذور → اختبار. **كرّر القائمة التالية لكل ميزة.**

### نمط الميزة الواحدة (Template — انسخه لكل ميزة)
- [ ] `app/Services/Ai/Features/<Feature>.php` (يستدعي AiManager عبر AiGate).
- [ ] قالب prompt ar/en في `AiPromptTemplateSeeder`.
- [ ] مفتاح في `AiFeatureFlagSeeder` (معطّل افتراضيًا).
- [ ] نقطة الاستدعاء: endpoint (POST) + زر في الشاشة المناسبة (loading/error states).
- [ ] بديل عند الإيقاف (الزر مخفي/مُعطّل برسالة).
- [ ] تسجيل في `ai_request_logs` (تلقائي عبر CostMeter).
- [ ] اختبار feature (مع mock للـ driver — لا نداء حقيقي في الاختبارات).

### ميزات الموجة 1
- [ ] **SEO content** (مقالات/أوصاف/meta) — ربط بـ `SeoService`/Posts.
- [ ] **Comms drafting** (واتساب/SMS/إيميل) — ربط بـ `CommunicationService`.
- [ ] **الترجمة ar↔en** — خدمة عامة + زر في المحررات.
- [ ] **Lead reply drafting** — `LeadService`/ContactMessages.
- [ ] **Campaign copy** — `notification_campaigns`.
- [ ] **تعريف الإنجاز** ✔ للموجة.

---

## الموجة 2 — تفاعل المريض + إنتاجية الطبيب (نصية)

- [ ] **مساعد المريض (Chatbot/RAG)**:
  - [ ] migration `ai_embeddings` (+ `ai_conversations`).
  - [ ] خدمة بناء embeddings للأسئلة الشائعة/الخدمات/الأطباء + إعادة بناء عند التغيير.
  - [ ] حساب التشابه (cosine) في PHP (قيد MySQL المشترك).
  - [ ] واجهة chat على الموقع + بوابة المريض (streaming) + توجيه للتخصص/الحجز.
  - [ ] حواجز: لا نصائح طبية، تحويل للحجز/الطبيب.
- [ ] **تحليل مشاعر الرضا** — `PatientSatisfaction`.
- [ ] **رسائل متابعة مخصّصة** — `FollowUpAutomationService`/`PatientEngagementService`.
- [ ] **تذكير مواعيد ذكي** — `DentalSmartNotificationService`.
- [ ] **ميزات الطبيب النصية (D21–D25)**: رد رسائل المرضى، رد التقييمات، شرح كشف العمولة/الراتب، استعلام إحصائياتي، توليد السيرة المهنية.
- [ ] **تعريف الإنجاز** ✔.

---

## الموجة 3 — المساعدة السريرية للطبيب (إنسان في الحلقة)

> **بوابة أمان إلزامية قبل أي ميزة هنا** (راجع §3 أدناه).

- [ ] **D3 تلخيص ملف المريض** (نظرة واحدة).
- [ ] **D4 توليد ملاحظة SOAP** من نقاط مختصرة.
- [ ] **D5 تشخيص تفريقي** (استشاري + تنويه).
- [ ] **D6 اقتراح ICD-10**.
- [ ] **D7 تنسيق/ترجمة الملاحظات** (`DoctorPatientNoteController`).
- [ ] **D8 اقتراح أدوية/جرعات** (`DoctorPrescriptionController`).
- [ ] **D9 فاحص تعارض الأدوية والحساسية** — *يُبنى أولًا* ويُفرض قبل اعتماد أي وصفة.
- [ ] **D10 جرعة حسب العمر/الوزن + تعليمات للمريض** (أطفال).
- [ ] **D1 الموجز الصباحي** + **D2 بطاقة التحضير/ترتيب الطابور**.
- [ ] **D11 مسوّدة خطة علاج الأسنان** + **D14 اقتراح متابعة** + **D15 نص الموافقة**.
- [ ] **D26 تقارير/إحالات/شهادات/ملخص خروج**.
- [ ] **D27 مساعد أسئلة طبية** (مصادر معتمدة + تنويه) + **D28 مواد تثقيف المريض**.
- [ ] **تعريف الإنجاز** ✔.

### §3 بوابة الأمان السريري (تُنفَّذ مرة وتُفرض على كل ميزة سريرية)
- [ ] مكوّن «مسوّدة قابلة للتحرير» — لا حفظ/إرسال إلا بضغط الطبيب «اعتماد».
- [ ] تنويه ثابت «مُولّد بالذكاء الاصطناعي — للمساعدة لا التشخيص».
- [ ] فرض **D9** قبل اعتماد أي وصفة مقترحة.
- [ ] RAG من بيانات المريض الحقيقية + كتالوج الأدوية (تقليل الهلوسة).
- [ ] `ai_patient_consent_required` يُفحص قبل معالجة بيانات مريض.
- [ ] تسجيل في `medical_data_access_logs` + قرار الطبيب (اعتمد/عدّل/رفض) في `ai_request_logs`.
- [ ] اختبار جودة المخرجات العربية + مراجعة طبيب قبل الإنتاج.

---

## الموجة 4 — صوت / رؤية / تحليلات

- [ ] **D20 تيليمديسن**: تفريغ المكالمة (Whisper) + مسوّدة ملاحظة/وصفة + ترجمة فورية (`OnlineConsultationController`).
- [ ] **D14 Whisper إملاء** عام للطبيب → ملاحظة.
- [ ] **D12 تحليل أشعة الأسنان** (Vision) — استشاري.
- [ ] **D16/D17 صور الجلدية** (Vision) — تقييم مبدئي + سرد التقدّم.
- [ ] **D13 سرد مقارنة قبل/بعد**.
- [ ] **D18 إنذار مخاطر الحمل** (`DoctorObgynController` + `ObstetricCalculatorService`).
- [ ] **D19 تفسير منحنى النمو** (`WhoGrowthStandardsService`) + فجوات التطعيم.
- [ ] **تحليلات بلغة طبيعية** (للأدمن/الطبيب) + **ملخّص تنفيذي** للوحة/التقارير.
- [ ] **OCR التأمين** (بطاقات/هوية).
- [ ] **تعريف الإنجاز** ✔.

---

## الموجة 5 — تنبؤية

- [ ] **توقّع الغياب (No-show)** من تاريخ الحجوزات.
- [ ] **اقتراح إعادة طلب المخزون** (`InventoryManager`).
- [ ] لوحة دقّة التنبؤ + تنبيهات.
- [ ] **تعريف الإنجاز** ✔.

---

## ضبط التكلفة والمراقبة (مستمر عبر كل الموجات)

- [ ] قاطع الميزانية: إيقاف النداءات عند بلوغ `ai_monthly_budget_usd` + تنبيه عند النسبة.
- [ ] تدرّج النماذج (mini للنصي، gpt-4o للسريري/الرؤية) قابل للضبط لكل ميزة.
- [ ] تخزين مؤقت للترجمات/الأوصاف/الـembeddings.
- [ ] Batch للمهام الليلية (تلخيصات/محتوى).
- [ ] لوحة `/admin/ai/usage`: تكلفة شهرية/ميزة/مستخدم/فرع + اتجاه.

---

## مخرجات قابلة للتسليم لكل مرحلة
1. كود + اختبارات خضراء.
2. تحديث `manifest.json` (للصفحات الجديدة).
3. تحديث هذا الـ checklist (تعليم المنجَز).
4. commit + push.
5. ملاحظة للأدمن: ماذا يُفعّل وكيف (المفاتيح/القوالب).

> **لا تُفعَّل أي ميزة على الإنتاج قبل**: `ai_enabled=true` + اختبار الاتصال +
> (للسريري) فاحص التعارض D9 + التنويه + مراجعة طبيب.
