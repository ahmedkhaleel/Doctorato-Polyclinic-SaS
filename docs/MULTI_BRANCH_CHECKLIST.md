# Multi-Branch — Pre-Implementation Runbook & Master Checklist

> رفيق تنفيذي لـ `docs/MULTI_BRANCH_ADR.md`. يحصر **كل بند في النظام** يجب لمسه أو التحقق منه قبل/أثناء التنفيذ — حتى لا نغفل شيئاً.
> القاعدة الذهبية في كل بند: **لا يتغيّر أي سلوك قبل وجود فرع ثانٍ** (additive + backfill→1 + scope = no-op على فرع واحد).
> الرموز: ☐ مهمة · ⚠️ خطر/انتباه · 🔒 قرار مُثبّت · 🧪 يحتاج اختبار عزل.

---

## القرارات المُثبّتة (Locked)
- 🔒 المريض **مشترك** (ملف واحد + `home_branch_id`)؛ لا `branch_id` على `patients` أو سجلّاته التابعة (documents/photos/vitals/insurances/wallets/referrals).
- 🔒 قاعدة بيانات واحدة + `branch_id` + Global Scope + `BranchContext` (Option A).
- 🔒 التنفيذ يبدأ من **B0** عند الإذن؛ كل مرحلة قابلة للشحن/الإيقاف.

---

## 0) Pre-flight — قبل لمس أي كود (إلزامي)
- ☐ **نسخة احتياطية كاملة** للإنتاج (DB + files) عبر `backup:run` + تنزيلها محلياً.
- ☐ **بيئة staging** = نسخة من الإنتاج لتجربة كل مرحلة عليها أولاً.
- ☐ **خط أساس أخضر**: تشغيل `composer check` وتوثيق العدد الحالي للاختبارات والإخفاقات المعروفة (26 محلياً جلسات/بريد تنجح في CI) — أي إخفاق جديد لاحقاً = تراجع.
- ☐ **علم تفعيل** `config('branches.enabled')` (افتراضي false) + `config('branches.default_id')=1` — يسمح بإطفاء السلوك كله فوراً.
- ☐ تأكيد **عامل الطابور** (`queue:work`) و**cron** يعملان على الإنتاج (الإشعارات/التذكيرات تعتمد عليهما).
- ☐ فرع git مخصّص `feature/multi-branch` + PR لكل مرحلة (مراجعة قبل الدمج).
- ☐ مراجعة `data:integrity-check` الحالي ليصبح branch-aware لاحقاً (لا يبلّغ خطأً زائفاً).

---

## 1) B0 — الأساس (Foundation) 🧪
- ☐ migration `create_branches_table`: `id, name_ar, name_en, code(unique), phone, address, timezone, is_active, is_default, created_at`.
- ☐ بذر **"الفرع الرئيسي"** `id=1, code='main', is_default=true`.
- ☐ موديل `Branch` (+ علاقات: users, doctors, و hasMany للكيانات المربوطة).
- ☐ `App\Services\Branch\BranchContext` (singleton): `currentId/set/isAllBranches/runForBranch/runWithoutScope`.
- ☐ `config/branches.php` (enabled, default_id).
- ☐ تسجيل الـ singleton في `AppServiceProvider`.
- ☐ 🧪 اختبار: السياق يرجع الفرع الافتراضي (1) عندما لا session.
- ✅ المعيار: لا تغيّر سلوكي إطلاقاً (لا أعمدة scoped بعد).

---

## 2) B1 — المخطّط: إضافة `branch_id` (nullable) + backfill→1 + فهارس
> دفعة migration لكل نطاق (idempotent، `if (!Schema::hasColumn)`), ثم `UPDATE ... SET branch_id=1`, ثم فهرس مركّب.

### 2.1 الحجوزات والمواعيد (ابدأ بهذا النطاق)
- ☐ `bookings` ⚠️(توليد `booking_number`) · `booking_appointments` · `booking_services` · `booking_consents`
- ☐ `package_bundle_bookings` ⚠️(رقم) · `package_bundle_booking_appointments` · `package_bundle_booking_services`
- ☐ `online_consultations`

### 2.2 الزيارات والإكلينيك
- ☐ `visits` · `visit_photos` · `medical_certificates` ⚠️(رقم)
- ☐ `prescriptions` · `prescription_items`
- ☐ Dental: `dental_treatments, dental_treatment_plans, dental_treatment_plan_steps, treatment_plan_consents, dental_lab_orders, dental_scheduled_followups, dental_chart_entries?, periodontal_charts?, dental_xrays, dental_comparisons, dental_smart_notifications`
- ☐ Derma/Cosmetic: `derma_sessions, derma_treatment_plans, cosmetic_sessions, cosmetic_procedures, cosmetic_package_purchases, cosmetic_photos, cosmetic_consents`
- ☐ Pediatric: `pediatric_well_child_visits, pediatric_vaccinations ⚠️(رقم), pediatric_growth_records, pediatric_milestones, pediatric_screening_tests, pediatric_nutrition_records` (ملاحظة: السجلات المرتبطة بالمريض دائماً مثل allergies/chronic/family_history → تتبع المريض المشترك، **بلا** branch_id)
- ☐ OB/GYN: `pregnancies, antenatal_visits, obstetric_ultrasounds, obgyn_lab_tests ⚠️(رقم), pap_smear_screenings ⚠️(رقم), delivery_records, contraception_records` (و`obgyn_profiles` تتبع المريض → بلا branch_id)

### 2.3 المالية
- ☐ `invoices` ⚠️(رقم) · `invoice_items` · `payments` · `payment_transactions` · `credit_notes` ⚠️(رقم) · `discount_usage` · `marketer_commissions`
- ☐ (قرار) `patient_wallets`/`wallet_transactions`: تتبع المريض المشترك → **بلا** branch_id (محفظة واحدة للمريض). تأكيد المنطق المحاسبي.

### 2.4 المخزون
- ☐ `supplies` · `supply_transactions` · `purchase_orders` · `purchase_order_items` · `suppliers`(قرار: مشترك أم per-branch؟ غالباً مشترك) · `supply_categories`(مشترك)

### 2.5 الموارد البشرية
- ☐ `employees` · `employee_shifts` · `shifts` · `attendances` · `leaves` · `salary_slips` · `penalties` · `advances` · `doctor_payouts` · `doctor_payout_visits`

### 2.6 جداول الأطباء (Pivot + branch_id)
- ☐ `doctor_schedules` · `doctor_vacations` · `doctor_service_rates`
- ☐ pivot `branch_doctor`

### 2.7 CRM (قرار: مشترك أم per-branch؟)
- ☐ `leads, lead_activities, lead_follow_ups, lead_stage_history, lead_sequence_enrollments, crm_campaigns, marketer_commissions`
- ⚠️ قرار مطلوب: هل التسويق مركزي (مشترك) أم لكل فرع قمعه؟ (الافتراضي المقترح: **مشترك** مع `branch_id` اختياري لإسناد العميل لفرع).

### 2.8 المتابعات والرضا والتأمين
- ☐ `patient_recall_reminders` · `patient_satisfactions` · `insurance_claims` · `insurance_pre_authorizations`
- ☐ `notification_logs` (branch_id للتقارير لكل فرع) · `scheduled_notifications`

### 2.9 المصروفات
- ☐ `expenses` · `expense_items` · `expense_categories`(مشترك)

> ⚠️ لكل جدول كبير: فهرس مركّب `(branch_id, <العمود الأكثر فلترةً>)` (مثل `(branch_id, status)`، `(branch_id, created_at)`).
> ✅ المعيار: nullable + backfill → كل الصفوف فرع 1 → صفر تأثير.

---

## 3) B2 — طبقة الموديل: تطبيق `BelongsToBranch` (نطاقاً تلو الآخر) 🧪
- ☐ `app/Models/Concerns/BelongsToBranch.php` (Global Scope + creating()).
- ☐ تطبيقه بالترتيب: الحجوزات → الزيارات → المالية → المخزون → HR → الإكلينيك → CRM.
- ☐ 🧪 **اختبار عزل لكل نطاق**: صفّان بفرعين مختلفين، السياق=فرع A، الاستعلام يرى A فقط؛ الإنشاء يملأ branch_id من السياق؛ super_admin ("كل الفروع") يرى الاثنين.
- ⚠️ مراجعة كل `whereDoesntHave` / joins / `withoutGlobalScopes` الموجودة (خاصة في `data:integrity-check`) لتفادي نتائج خاطئة.
- ✅ المعيار: السياق=1 للجميع → نتائج مطابقة للحالي + كل الاختبارات الحالية خضراء.

---

## 4) B3 — المصادقة والصلاحيات والتبديل
- ☐ pivot `branch_user` (+ `is_primary`).
- ☐ تعبئة: كل المستخدمين الحاليين → الفرع 1 (primary).
- ☐ `BranchContext` يقرأ من `session('current_branch_id')`؛ افتراضه الفرع الأساسي للمستخدم.
- ☐ صلاحية نطاق الفرع: `super_admin` = كل الفروع؛ غيره = فروعه فقط.
- ☐ `POST /{panel}/switch-branch` (Admin/Doctor/Secretary) + رفض فرع غير مُخوّل.
- ☐ تمرير الفرع النشط + قائمة فروع المستخدم إلى Inertia (shared props) لكل لوحة.
- ⚠️ `PatientAuth`: المريض مشترك → لا تبديل فرع؛ تُفلتر معاملاته بطبيعتها.
- 🧪 اختبار: تبديل الفرع يغيّر النتائج؛ مستخدم بلا تخويل يُرفض.

---

## 5) B4 — الإعدادات والأرقام التسلسلية لكل فرع
### 5.1 الإعدادات
- ☐ `settings.branch_id` (nullable) + فهرس.
- ☐ `Setting::get($key)` branch-aware مع **fallback**: قيمة الفرع ← ثم العامة (`branch_id IS NULL`). الحفاظ على التوقيع (توافق خلفي).
- ☐ مراجعة الكاش (مفاتيح الكاش تصبح per-branch: `setting:{branch}:{key}`).
- ☐ مفاتيح حسّاسة per-branch: اعتمادات الدفع/الواتساب/SMTP/مرسِل SMS (يربط بميديول الإشعارات).

### 5.2 الأرقام التسلسلية (الـ 6 نماذج المولِّدة) ⚠️
- ☐ `Booking::booted` (`booking_number`) → بادئة فرع `{BR}-BK-…` + تسلسل لكل فرع.
- ☐ `Invoice` (`invoice_number`) → per-branch.
- ☐ `CreditNote` → per-branch.
- ☐ `MedicalCertificate` → per-branch.
- ☐ `PackageBundleBooking` → per-branch.
- ☐ `Patient::booted` (`file_number`) → **يبقى عاماً** (مريض مشترك) — تأكيد عدم لمسه.
- ⚠️ تأكيد عدم وجود قيد unique عام يكسر التسلسل لكل فرع (اجعل unique مركّباً `(branch_id, number)` حيث يلزم).

---

## 6) Jobs & Console Commands — كل واحد يجب أن يَعي الفرع صراحةً ⚠️🧪
> القاعدة: الـ jobs/cron بلا session → **يجب ألّا تُفلتر صامتةً لفرع واحد**. إمّا `runWithoutScope` + معالجة كل الفروع، أو `runForBranch($id)` صراحةً. السلوك المطلوب: تعالج **كل الفروع** كما تفعل اليوم.

### 6.1 Jobs (8)
- ☐ `ProcessNotificationJob` — يحمل recipient؛ السياق من الـ recipient/branch (مرّر branch_id في الـ job).
- ☐ `SendSmsJob, SendEmailJob, BulkSmsJob, SendNotificationJob, ProcessBookingSmsJob, ProcessFollowUpSequenceJob` — تأكد أنها لا تُفلتر صامتة؛ المرسِل/الإعدادات حسب فرع السجل.
- ☐ `RecalculateCommissionsJob` — per-branch أو all.

### 6.2 الأوامر المجدولة (cron) — جدول القرار
| الأمر | السلوك المطلوب |
|------|----------------|
| `bookings:create-daily-visits` | كل الفروع (يعالج كل الحجوزات؛ الزيارة ترث branch_id من الحجز) ⚠️ |
| `bookings:send-reminders` · `patients:send-sms-reminders` (day/same) · `patients:send-recall-reminders` | كل الفروع؛ المرسِل/القناة حسب فرع المريض/الحجز |
| `dental:check-alerts` · `dental:process-followups` · `dental:smart-notifications` · `dental:expire-consents` | كل الفروع |
| `obgyn:reminders` (anc/edd/pap) · `pediatric:vaccination-reminders` (upcoming/overdue) | كل الفروع |
| `invoices:check-overdue` · `insurance:expire-pre-auths` · `loyalty:expire-points` · `satisfaction:send-surveys` | كل الفروع |
| `inventory:check-low-stock` | **لكل فرع** (المخزون per-branch) — تنبيه لمسؤول كل فرع |
| `hr:payroll-reminder` | لكل فرع (موظفون per-branch) |
| `leads:check-dormant` · `leads:remind-follow-ups` · `leads:daily-report` · `sequences:process` | كل الفروع (حسب قرار CRM) |
| `notifications:retry` · `:dispatch-campaigns` · `:dispatch-scheduled` · `:run-sequences` · `:cost-check` | كل الفروع؛ الإعدادات/السقوف per-branch مع fallback |
| `telemedicine:*` · `backup:*` · `sitemap:generate` · `health:alert` · `trash:prune` · `data:integrity-check` · `reports:weekly` | **عام** (بلا فرع) — لكن `reports:weekly` + `leads:daily-report` تُضيف تفصيلاً per-branch |
- 🧪 اختبار: أمر تذكير مع فرعين → يعالج الاثنين (لا يقتصر على فرع السياق الافتراضي).

---

## 7) UI / Vue (التصميم بالهوية كحلي/ذهبي + أنيميشن + RTL/LTR)
- ☐ **مُبدّل فرع** في رؤوس Admin/Doctor/Secretary (نمط مُبدّل اللغة) + شارة الفرع النشط.
- ☐ وضع **"كل الفروع"** للـ super_admin (للتقارير/الإشراف).
- ☐ **صفحة إدارة الفروع** (CRUD) — قسم System (B5).
- ☐ صفحات الفهارس (bookings/visits/invoices/inventory…): عمود/فلتر الفرع عند "كل الفروع".
- ☐ تعيين موظفين/أطباء لفرع في شاشات Users/Doctors.
- ☐ تأكيد كل صفحة محميّة بالصلاحية (لا تبويب فرع يظهر بلا تخويل).
- ☐ بناء الأصول `npm run build` + التحقق من الـ manifest لكل صفحة جديدة.

---

## 8) ميديول الإشعارات (المبني حديثاً) — تكامل الفرع
- ☐ `notification_channels` per-branch (مرسِل/اعتمادات لكل فرع) عبر طبقة B4.
- ☐ `notification_logs.branch_id` (للتحليلات لكل فرع + سجل التسليم).
- ☐ الحملات/السلاسل/القوالب: قرار per-branch أم مشترك (الافتراضي مشترك مع استهداف per-branch).
- ☐ `NotificationHealthService` + `/health` + Diagnostics: إظهار per-branch.
- ☐ `StaffNotifier`: تنبيهات الطاقم تذهب لمستخدمي الفرع المعني.

---

## 9) التقارير (per-branch + مُجمّعة)
- ☐ كل ReportController يحترم السياق (per-branch تلقائياً).
- ☐ وضع "كل الفروع": `runWithoutScope` + `groupBy(branch_id)`.
- ☐ **لوحة مقارنة الفروع** (إيرادات/حجوزات/no-show/مخزون لكل فرع).
- ☐ مراجعة كل `DB::raw`/`sum()`/`count()` في التقارير لتفادي تجميع خاطئ عبر الفروع.

---

## 10) البيانات والسلامة
- ☐ Seeders/DemoSeeders: إسناد كل شيء للفرع 1.
- ☐ توسيع `data:integrity-check`: صفوف بلا branch_id (في جداول scoped)، branch_id يتيم (فرع محذوف)، تسرّب عبر الفروع.
- ☐ migration backfill **idempotent + data-safe** (نمط `2026_04_20_*`).
- ☐ خطة استعادة: كل مرحلة لها `down()` آمن.

---

## 11) الاختبارات (Testing Strategy)
- ☐ 🧪 **اختبار عزل لكل نطاق** (فرع A لا يرى بيانات فرع B).
- ☐ اختبار الإنشاء يملأ branch_id من السياق.
- ☐ اختبار super_admin "كل الفروع" يرى الكل.
- ☐ اختبار الأرقام التسلسلية لا تتضارب بين الفروع.
- ☐ اختبار cron/jobs تعالج كل الفروع.
- ☐ اختبار الصلاحيات (رفض فرع غير مُخوّل).
- ☐ regression كامل (`composer check`) أخضر بعد كل مرحلة، مقارنةً بخط الأساس.

---

## 12) النشر والتراجع (cPanel)
- ☐ ترتيب التشغيل: backup → migrate (additive) → backfill → optimize:clear → (opcache reset عند الحاجة).
- ☐ `public/build/` committed (لا npm على الاستضافة) — بناء محلي قبل الدفع.
- ☐ علم `branches.enabled` يبقى محكوماً حتى اكتمال B0–B4 على الإنتاج.
- ☐ خطة rollback لكل مرحلة (migrate:rollback آمن + استعادة نسخة عند الكارثة).
- ☐ بعد النشر: مراقبة `/health` (بطاقة الإشعارات) + سجلات + طابور.

---

## 13) Definition of Done (لكل مرحلة)
- ☐ كل migrations idempotent + data-safe + لها down().
- ☐ اختبار عزل النطاق أخضر + regression أخضر (= خط الأساس).
- ☐ `pint` نظيف + `npm run build` ناجح + manifest محدّث.
- ☐ لا تغيّر سلوكي مرصود طالما فرع واحد.
- ☐ commit/push منفصل + تحديث `CLAUDE.md` (قسم Branches) عند B0.

---

## 14) سجل المخاطر السريع
| الخطر | التخفيف |
|------|---------|
| تسرّب بيانات بين الفروع | trait مركزي + اختبار عزل لكل نطاق + مراجعة joins |
| cron يقتصر على فرع واحد صامتاً | jobs/cron تعمل بلا scope وتعالج كل الفروع (جدول §6.2) |
| أرقام متضاربة | تسلسل + unique مركّب per-branch (§5.2) |
| كسر استعلامات قائمة | تطبيق تدريجي per-domain + regression بعد كل نطاق |
| المحفظة/الملف المشترك يلتبس | 🔒 قرار مُثبّت: مشتركة بلا branch_id |
| أداء | فهارس مركّبة `(branch_id, …)` |

---

### نقاط قرار مفتوحة تحتاج إجابتك قبل B2 (لا تمنع B0/B1)
1. **CRM (leads/campaigns)**: مشترك مركزي أم لكل فرع قمعه؟
2. **الموردون والمخزون**: مورد مشترك ومخزون per-branch (الافتراضي)؟ أم مورد per-branch أيضاً؟
3. **الخدمات والأسعار**: نفس الخدمات/الأسعار لكل الفروع، أم سعر/توفّر per-branch (Service↔Branch)؟
4. **قنوات الإشعارات**: مرسِل واحد للمنشأة أم رقم واتساب/SMS مختلف لكل فرع؟
