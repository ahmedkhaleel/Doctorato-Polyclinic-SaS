# ADR-001: تفعيل الفروع المتعددة (Multi-Branch) على مستوى النظام

**Status:** Proposed
**Date:** 2026-05-31
**Deciders:** صاحب المنتج (Dr. Ahmed) + قائد الهندسة
**Scope:** تحويل نظام عيادة واحدة إلى نظام متعدد الفروع — كل شيء مبني على الفرع — دون التأثير على الخدمات الشغّالة.

---

## 1. Context (السياق)

النظام الحالي (Laravel 12 + Vue 3 + Inertia على cPanel/MySQL) مبني لعيادة **واحدة**:
- **160 موديل · ~180 جدول · 268 migration · 136 كنترولر admin · 5 لوحات** (Frontend/Patient/Doctor/Secretary/Admin/Webmaster).
- **لا يوجد أي مفهوم فرع/موقع** (`branch_id` غير موجود في أي جدول).
- الإعدادات `settings` جدول key/value **عام** مع cache متعدد الطبقات.
- المصادقة عبر `Role` + permissions، و middleware لكل لوحة.
- التذكيرات/الإشعارات/الجدولة تعتمد على `queue:work` + cron.

**القوى المؤثّرة (Forces):**
- المطلوب: كل كيان تشغيلي (حجز، زيارة، فاتورة، مخزون، موظف، جدول طبيب…) ينتمي لفرع، مع لوحات وتقارير لكل فرع + تقرير مُجمّع.
- قيد صارم: **بدون كسر** البيانات أو الخدمات الحالية (zero-downtime، تدريجي، قابل للتراجع).
- قيد البنية: استضافة مشتركة (cPanel)، MySQL واحد، لا Redis، queue=database.
- حجم ضخم: لمس ~180 جدولاً دفعةً واحدة = انتحار. نحتاج استراتيجية تدريجية آمنة.

**سؤال جوهري يحدد كل التصميم:** هل المريض يخصّ فرعاً واحداً أم مشترك بين الفروع؟
→ **القرار: المريض مشترك على مستوى المنشأة** (ملف طبي واحد، `home_branch_id` كمرجع)، بينما **المعاملات** (الحجوزات/الزيارات/الفواتير) مرتبطة بالفرع الذي حدثت فيه. هذا يمنع تكرار الملفات الطبية ويسمح للمريض بزيارة أي فرع.

---

## 2. Decision (القرار المقترح)

اعتماد **Option A — قاعدة بيانات واحدة + عمود `branch_id` (row-level scoping) + Global Scope + سياق فرع (Branch Context)**، مع تصنيف الكيانات إلى ثلاث فئات، وتطبيق **تدريجي نطاقاً تلو الآخر (domain-by-domain)** خلف "علم" يجعل السلوك مطابقاً للحالي طالما لا يوجد سوى فرع واحد.

المكوّنات الأساسية:
1. جدول `branches` + بذر **"الفرع الرئيسي" (id=1)**.
2. `branch_id` (nullable مبدئياً) على الجداول التشغيلية فقط + backfill → 1.
3. **`BranchContext`** (singleton) يحمل الفرع النشط للطلب الحالي.
4. **`BelongsToBranch`** trait: Global Scope يفلتر تلقائياً + يملأ `branch_id` عند الإنشاء.
5. ربط **User ↔ Branches** (متعدد) + ربط **Doctor ↔ Branches** (متعدد) + جداول الأطباء تحمل `branch_id`.
6. طبقة **إعدادات لكل فرع** (override فوق العام).
7. مُبدّل فرع (Branch Switcher) في لوحات الطاقم + دور `super_admin` يرى "كل الفروع".

---

## 3. Options Considered (الخيارات)

### Option A: Single DB + `branch_id` discriminator (Row-level, Global Scope)
| Dimension | Assessment |
|-----------|------------|
| Complexity | Medium — عمود + trait + سياق؛ لمس تدريجي |
| Cost | منخفض — لا بنية تحتية جديدة |
| Scalability | جيد لعشرات/مئات الفروع لمنشأة واحدة |
| Team familiarity | عالٍ — نمط Laravel قياسي (Global Scopes) |
| Zero-downtime | ✅ ممكن (additive migrations + backfill) |
| Cross-branch reports | ✅ سهل (تجميع بـ groupBy branch_id) |

**Pros:** أقل تغيير، استضافة واحدة، تقارير مُجمّعة بسيطة، نسخ احتياطي واحد، مرضى مشتركون طبيعياً.
**Cons:** خطر تسرّب بيانات بين الفروع لو نُسي scope (يُعالَج بالـ trait + اختبارات)؛ لمس كثير من الجداول (يُعالَج بالتدريج).

### Option B: Database-per-Branch (multi-database tenancy)
| Dimension | Assessment |
|-----------|------------|
| Complexity | High — اتصال ديناميكي + migrations لكل DB |
| Cost | أعلى (DBs متعددة على cPanel = صعب/مكلف) |
| Scalability | عزل قوي لكن إدارة أثقل |
| Team familiarity | منخفض |
| Cross-branch reports | ❌ صعب (تجميع عبر DBs) |
| المرضى المشتركون | ❌ شبه مستحيل (ملف لكل DB) |

**Pros:** عزل بيانات تام، مناسب لو كانت الفروع كيانات قانونية منفصلة تماماً.
**Cons:** التقارير المُجمّعة والمريض المشترك شبه مستحيلين؛ ثقيل على cPanel؛ يكسر بساطة النسخ الاحتياطي.

### Option C: Schema-per-Branch (نفس DB، schemas منفصلة)
| Dimension | Assessment |
|-----------|------------|
| Complexity | High |
| Cost | متوسط |
| Cross-branch reports | ❌ صعب |
| cPanel feasibility | ضعيف (دعم محدود) |

**Pros:** عزل أفضل من A.
**Cons:** نفس مشاكل B تقريباً مع تعقيد إضافي، وضعف الدعم على الاستضافة المشتركة.

---

## 4. Trade-off Analysis (تحليل المقايضات)

- **العزل مقابل الوحدة:** B/C تعطيان عزلاً أقوى، لكن متطلَّبنا الحقيقي (منشأة واحدة، فروع، مرضى مشتركون، تقارير مُجمّعة) يجعل A الأنسب بفارق كبير.
- **المخاطرة الأساسية في A** (تسرّب بين الفروع) تُحلّ هندسياً بـ Global Scope إجباري + trait مركزي + اختبارات عزل لكل نطاق + سياق افتراضي آمن.
- **الاستضافة المشتركة (cPanel)** ترجّح A بشكل حاسم (B/C يحتاجان إدارة DBs متعددة غير عملية هنا).
- **القابلية للتراجع:** A يسمح بـ migrations إضافية (additive) + علم تفعيل، فأي مرحلة قابلة للإيقاف دون فقد بيانات.

**القرار: Option A.**

---

## 5. تصنيف الكيانات (الأهم — يحدد أين يوضع `branch_id`)

### الفئة 1 — مرتبطة بالفرع مباشرةً (تأخذ `branch_id` + `BelongsToBranch`)
الحجوزات والزيارات والمالية والعيادات والموارد والمخزون:
`bookings, booking_appointments, booking_services, booking_consents, visits, visit_photos,
online_consultations, package_bundle_bookings(+children), invoices, invoice_items, payments,
payment_transactions, credit_notes, discount_usage, doctor_schedules, doctor_vacations,
doctor_service_rates, doctor_payouts(+visits), appointments/medical_certificates, prescriptions(+items),
dental_treatments/treatment_plans/lab_orders/scheduled_followups, derma_sessions/treatment_plans,
cosmetic_sessions/procedures/package_purchases, pediatric_* (visits/vaccinations/growth…),
pregnancies/antenatal_visits/obstetric_ultrasounds/obgyn_lab_tests/pap_smear/delivery_records,
patient_satisfactions, patient_recall_reminders, insurance_claims/pre_authorizations,
expenses/expense_items, supplies/supply_transactions/supply_categories?/purchase_orders(+items),
employees/employee_shifts/attendances/leaves/salary_slips/penalties/advances/shifts,
leads(+activities/follow_ups/stage_history) [إن كانت الفروع تتقاسم التسويق → اجعلها branch-aware],
notification_logs (للتقارير لكل فرع), marketer_commissions.`

### الفئة 2 — مشتركة على مستوى المنشأة (بلا `branch_id`)
`users (ترتبط بالفروع عبر pivot), roles, patients (مشترك + home_branch_id),
patient_documents/photos/vitals/insurances/wallets/referrals (تتبع المريض المشترك),
services/service_categories/service_packages, medications, suppliers,
insurance_companies/insurance_plans, communication_templates,
notification_events/channels/templates/sequences (قابلة للجعل per-branch لاحقاً — Phase 5),
pages/posts/faqs/gallery/hero_slides/seo_pages/testimonials/offers (الموقع واحد),
tags/post_categories, payment_methods (يمكن جعلها per-branch لاحقاً), departments.`

### الفئة 3 — متعددة-لمتعدد مع الفرع (Pivot)
- **Doctor ↔ Branch**: طبيب قد يعمل في أكثر من فرع → جدول `branch_doctor`. جداول مواعيده تحمل `branch_id`.
- **User ↔ Branch**: موظف قد يخدم فرعاً أو أكثر → جدول `branch_user` (+ `is_primary`).
- **Service ↔ Branch** (اختياري Phase 5): توفّر خدمة/سعرها في فرع معيّن.

> **الأرقام التسلسلية** (`invoice_number`, `booking_number`) تصبح **لكل فرع** (بادئة فرع) لتفادي التضارب. `file_number` للمريض يبقى عاماً (مريض مشترك).

---

## 6. التصميم التقني

### 6.1 سياق الفرع (`BranchContext`)
```php
// app/Services/Branch/BranchContext.php  (singleton)
- currentId(): ?int           // الفرع النشط (من session للطاقم، أو افتراضي)
- set(int $branchId): void    // عند تبديل الفرع
- isAllBranches(): bool        // وضع "كل الفروع" (super_admin/التقارير)
- runForBranch($id, $cb)       // تنفيذ كتلة ضمن فرع محدد (cron/jobs)
- runWithoutScope($cb)         // تجاوز مؤقت (تقارير مُجمّعة)
```
- **الطاقم:** الفرع النشط من `session('current_branch_id')` ← يُضبط من مُبدّل الفرع، افتراضه الفرع الأساسي للمستخدم.
- **المريض (بوابته):** المريض مشترك؛ معاملاته تُفلتَر حسب الفرع الذي تمّت فيه (لا حاجة لاختيار فرع، يرى كل زياراته).
- **Jobs/Cron:** بلا session → يجب أن تمرّ `branch_id` صراحةً أو تعمل `runWithoutScope` ثم تُفلتر يدوياً (مهم: الأوامر المجدولة الحالية يجب ألّا تنكسر — تعمل بلا scope وتعالج كل الفروع).

### 6.2 الـ Trait
```php
// app/Models/Concerns/BelongsToBranch.php
trait BelongsToBranch {
  static function bootBelongsToBranch() {
    static::addGlobalScope('branch', function ($q) {
      $ctx = app(BranchContext::class);
      if (!$ctx->isAllBranches() && $ctx->currentId()) {
        $q->where($q->getModel()->getTable().'.branch_id', $ctx->currentId());
      }
    });
    static::creating(function ($m) {
      if (empty($m->branch_id) && app(BranchContext::class)->currentId()) {
        $m->branch_id = app(BranchContext::class)->currentId();
      }
    });
  }
  function branch() { return $this->belongsTo(Branch::class); }
}
```
**ضمان عدم الكسر:** طالما يوجد فرع واحد فقط وكل الصفوف `branch_id=1` والسياق الافتراضي=1 → الفلترة تطابق كل البيانات تماماً (سلوك مطابق للحالي). الـ scope لا يفعل شيئاً ضاراً قبل وجود فرع ثانٍ.

### 6.3 الإعدادات لكل فرع
- إضافة `branch_id` (nullable) إلى `settings`. القراءة: **fallback** ← قيمة الفرع إن وُجدت، وإلا القيمة العامة (`branch_id IS NULL`).
- `Setting::get($key)` يصبح branch-aware (يقرأ سياق الفرع)، مع إبقاء التوقيع كما هو (توافق خلفي). مفاتيح حسّاسة (اعتمادات الدفع/الواتساب/SMTP) تصبح قابلة للتخصيص لكل فرع — وهذا يربط مباشرةً بميديول الإشعارات الذي يدعم بالفعل config مشفّر لكل قناة.

### 6.4 الصلاحيات
- إضافة "نطاق فرع" للدور: `super_admin` = كل الفروع؛ مدير فرع = فرعه فقط (عبر `branch_user`).
- `BranchContext::set` يُرفض إن لم يكن المستخدم مُخوّلاً للفرع.

### 6.5 الواجهة (Vue)
- **مُبدّل فرع** في رأس لوحات الطاقم (يشبه مُبدّل اللغة الموجود) — يستدعي `POST /switch-branch`.
- شارة الفرع النشط في كل اللوحات + خيار "كل الفروع" للـ super_admin (للتقارير).
- التصميم بالهوية المعتمدة (كحلي #1B365D / ذهبي #C4A265) + أنيميشن + RTL/LTR، متّسق مع المُبدّلات الحالية.

### 6.6 التقارير
- per-branch: تُفلتر تلقائياً بالسياق.
- مُجمّعة: `runWithoutScope` + `groupBy('branch_id')` + بطاقة لكل فرع + إجمالي. لوحة "مقارنة الفروع".

---

## 7. خطة التنفيذ التدريجية (Zero-Downtime · قابلة للتراجع)

> القاعدة الذهبية: **لا تتغيّر أي وظيفة قبل إنشاء فرع ثانٍ.** كل مرحلة additive ومختبرة وتُدفع منفصلة.

| المرحلة | المحتوى | ضمان عدم الكسر |
|--------|---------|----------------|
| **B0 — الأساس** | جدول `branches` + بذر "الفرع الرئيسي" (id=1). `BranchContext` (افتراضه 1). مُبدّل فرع مخفي. | لا شيء يتغيّر — لا أعمدة scoped بعد |
| **B1 — العمود + Backfill** | إضافة `branch_id` **nullable** لكل جداول الفئة 1 (migrations idempotent، دفعات per-domain). Backfill كل الصفوف → 1. فهارس. | nullable + backfill = صفر تأثير |
| **B2 — الـ Scope بالتدريج** | تطبيق `BelongsToBranch` نطاقاً تلو الآخر (حجوزات ← زيارات ← مالية ← مخزون ← HR ← عيادات)، مع اختبار عزل لكل نطاق. السياق=1 للجميع. | scope على فرع واحد = نتائج مطابقة |
| **B3 — الربط والتبديل** | `branch_user` + `branch_doctor` + جداول مواعيد الأطباء تأخذ branch_id. تفعيل مُبدّل الفرع + صلاحيات الفرع. | ما زال فرع واحد فعلياً |
| **B4 — الإعدادات والأرقام لكل فرع** | `settings.branch_id` + fallback. تسلسل أرقام الفواتير/الحجوزات لكل فرع. | fallback يحفظ السلوك العام |
| **B5 — إنشاء فروع فعلية** | السماح بإنشاء فرع ثانٍ من اللوحة. تعيين موظفين/أطباء/إعدادات. | أول لحظة يظهر فيها تعدد حقيقي — بعد اكتمال 1–4 واختبارها |
| **B6 — التقارير والداشبورد** | فلترة per-branch + لوحة مقارنة مُجمّعة + per-branch KPIs. | إضافات للعرض فقط |
| **B7 — إشعارات/مخزون/HR per-branch** | قنوات إشعار/مرسِل/مخزون/مناوبات لكل فرع (يبني على B4). | اختياري متدرّج |

**معيار القبول لكل مرحلة:** `composer check` أخضر + اختبار عزل النطاق + بناء الأصول + لا تغيّر سلوكي مرصود طالما فرع واحد.

---

## 8. Consequences (التبعات)

**يصبح أسهل:** فتح فروع جديدة دون كود، تقارير لكل فرع + مُجمّعة، إعدادات/مرسِل إشعارات لكل فرع، فصل المخزون والـ HR والمالية لكل فرع.

**يصبح أصعب:** كل استعلام يجب أن يَعي الفرع (يُدار مركزياً بالـ trait)؛ الـ jobs/cron يجب أن تمرّر السياق صراحةً؛ اختبارات أكثر (عزل الفروع)؛ يقظة عند الـ joins/التقارير لتفادي التسرّب.

**نراجعه لاحقاً:** هل نحتاج Service↔Branch وأسعاراً لكل فرع؟ هل المخزون يُنقَل بين الفروع (transfers)؟ هل نحتاج عزلاً أقوى مستقبلاً (الترقية إلى B إن انفصلت الفروع قانونياً)؟

---

## 9. المخاطر والتخفيف

| الخطر | التخفيف |
|------|---------|
| تسرّب بيانات بين الفروع لو نُسي scope | trait مركزي إجباري + اختبار عزل لكل نطاق + مراجعة joins يدوياً |
| كسر الـ cron/jobs الحالية (بلا session) | الـ scope لا يفعّل إلا بسياق؛ jobs تعمل بلا scope وتعالج كل الفروع، أو `runForBranch` صراحةً |
| لمس ~180 جدولاً | لا نلمسها كلها — الفئة 1 فقط، بالتدريج، per-domain، مع backfill |
| أرقام فواتير/حجوزات متضاربة | تسلسل لكل فرع ببادئة (B4) |
| أداء (فهارس) | فهرس مركّب `(branch_id, …)` على الجداول الكبيرة |
| المريض المشترك يلتبس على الطاقم | المريض مشترك صراحةً؛ تبويباته تُظهر الفرع لكل معاملة |

---

## 10. Action Items

1. [ ] **B0**: migration `create_branches_table` + بذر "الفرع الرئيسي" + `BranchContext` + `Branch` model. (آمن 100%)
2. [ ] **B1**: migrations `add_branch_id_to_<domain>` (nullable) + backfill→1 + فهارس، دفعة الحجوزات أولاً.
3. [ ] **B2**: `BelongsToBranch` trait + تطبيقه على نطاق الحجوزات + اختبار عزل، ثم التوسّع نطاقاً تلو الآخر.
4. [ ] **B3**: `branch_user` + `branch_doctor` + مُبدّل الفرع (Vue) + صلاحيات الفرع + `/switch-branch`.
5. [ ] **B4**: `settings.branch_id` + fallback في `Setting::get` + تسلسل أرقام لكل فرع.
6. [ ] **B5**: شاشة إدارة الفروع (CRUD) + إنشاء فرع ثانٍ + تعيينات.
7. [ ] **B6**: فلترة التقارير + لوحة مقارنة الفروع.
8. [ ] **B7** (اختياري): إشعارات/مخزون/HR لكل فرع.
9. [ ] تحديث `CLAUDE.md` بقسم "Branches" + توثيق الـ trait والسياق.

**التقدير المبدئي:** B0–B2 (الأساس + الحجوزات) ~ أسبوع؛ النظام الكامل عبر B0–B6 يُنفَّذ تدريجياً عبر عدة أسابيع، كل مرحلة قابلة للشحن والإيقاف على حدة.
