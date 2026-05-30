# خريطة تنفيذ ميديول النساء والتوليد (OB/GYN Module)

> Obstetrics & Gynecology — تصميم وتنفيذ كامل، متّسق مع بنية الميديولات الطبية
> القائمة (Derma / Dental / Pediatric). المرجع المعماري: ميديول **طب الأطفال**.

- **Slug:** `obgyn`
- **الاسم:** النساء والتوليد — Obstetrics & Gynecology
- **اللون:** `#EC4899` (وردي — اللون العالمي المتعارف عليه للتخصص، وغير مستخدم)
- **التصنيف:** medical module (`MEDICAL_MODULES`) — له أطباء/زيارات/فواتير

---

## 1. المتطلبات (Requirements)

### وظيفية
ينقسم التخصص إلى شقّين، والميديول يغطّيهما:

**أ. التوليد (Obstetrics) — متابعة الحمل**
- فتح ملف حمل (Pregnancy episode) لكل حمل، مع LMP و EDD وحساب عمر الحمل تلقائياً.
- زيارات متابعة الحمل (Antenatal Care / ANC): وزن، ضغط، ارتفاع قاع الرحم، نبض الجنين، وضعية الجنين، تورّم، تحليل بول (زلال/سكر)، شكوى، خطة، موعد الزيارة القادمة.
- سونار التوليد: BPD/HC/AC/FL، الوزن التقديري للجنين (EFW)، المشيمة، السائل الأمنيوسي، عدد/حيوية الأجنّة.
- تحاليل الحمل (CBC، فصيلة الدم، سكر الحمل OGTT، TORCH…).
- سجل الولادة (Delivery): التاريخ، نوع الولادة (طبيعي/قيصري)، النتيجة، وزن المولود، المضاعفات.

**ب. أمراض النساء (Gynecology)**
- مسحة عنق الرحم (Pap smear) + حالة HPV + موعد التجديد.
- وسائل منع الحمل / تنظيم الأسرة (Contraception).
- البيانات التناسلية الأساسية: العمر عند البلوغ، Gravida/Para/Abortus، فصيلة الدم وعامل ريسوس، آخر دورة، طول الدورة.

### غير وظيفية
- نفس مستوى الأداء/التخزين للميديولات الحالية (MySQL، caching للإعدادات).
- خصوصية طبية: صلاحيات `view_sensitive_medical` لبيانات النساء الحسّاسة.
- الهوية البصرية المعتمدة (navy + gold) + أنيميشن + RTL/LTR + i18n كامل.
- لا تُعرض أي تبويبة إلا بصلاحية `obgyn.*` (مبدأ الأمان الأساسي).

### قيود
- استضافة cPanel مشتركة، `public/build` مرفوع، migrations idempotent + data-safe.
- إعادة استخدام كل البنية القائمة (Visit, Invoice, Payment, InsuranceClaim, ServiceSupply, AuditLogger) — لا اختراع موازٍ.

---

## 2. التصميم العالي (High-Level)

```
                         ┌────────────────────────────┐
                         │      ModuleManager          │
                         │  + 'obgyn' (MEDICAL_MODULES)│
                         └────────────┬───────────────┘
                                      │ feature flag
        ┌──────────────┬─────────────┼──────────────┬─────────────┐
        ▼              ▼             ▼              ▼             ▼
   Admin panel    Doctor panel  Secretary     Patient portal   Console
   (تحكّم كامل)   (سريري)        (تسجيل/مواعيد) (متابعتي)        (تذكيرات)
        │              │             │              │             │
        └──────────────┴─────────────┴──────────────┴─────────────┘
                                      │
                    ┌─────────────────┼──────────────────┐
                    ▼                 ▼                  ▼
              Pregnancy ──< AntenatalVisit          ObgynProfile
                 │   │        │  └─ Visit(module=obgyn) ─> Invoice(module=obgyn) ─> Payment
                 │   ├──< ObstetricUltrasound
                 │   ├──< ObgynLabTest
                 │   └──< DeliveryRecord (1:1 عند الولادة)
              PapSmearScreening   ContraceptionRecord   (مستوى المريضة)
                    │
              ObstetricCalculatorService (EDD, GA, trimester, جدول ANC)
```

**تدفق المال (مطابق لباقي الميديولات):** كل زيارة ANC أو سونار تُنشئ `Visit(module='obgyn')` → الـ hook في `Invoice::creating` يضع `module='obgyn'` تلقائياً → تظهر في تقارير الإيراد لكل ميديول، ومحميّة بفحوصات `data:integrity-check`.

---

## 3. نموذج البيانات (Data Model)

### الموديلات (8) — كلها SoftDeletes + doctor_id + LogsActivity

| # | Model | الجدول | أهم الحقول |
|---|-------|--------|-----------|
| 1 | `ObgynProfile` | `obgyn_profiles` | patient_id, menarche_age, gravida, para, abortus, living_children, blood_group, rh_factor, lmp, cycle_length_days, contraception_method, notes |
| 2 | `Pregnancy` | `pregnancies` | patient_id, doctor_id, lmp, **edd, edd_source(lmp/scan)**, gravida, para, conception_method, blood_group, rh_factor, is_high_risk, risk_factors(json), status(active/delivered/miscarried/terminated), notes — **scope `active()`; حمل نشط واحد فقط لكل مريضة** |
| 3 | `AntenatalVisit` | `antenatal_visits` | pregnancy_id, visit_id, doctor_id, visit_date, **phase(antenatal/postnatal)**, gestational_age_weeks, weight_kg, bp_systolic, bp_diastolic, fundal_height_cm, fetal_heart_rate, presentation, edema, urine_protein, urine_glucose, complaints, plan, next_visit_date |
| 4 | `ObstetricUltrasound` | `obstetric_ultrasounds` | pregnancy_id, visit_id, scan_date, scan_type(dating/anomaly/growth/doppler), gestational_age_weeks, bpd_mm, hc_mm, ac_mm, fl_mm, efw_grams, placenta_position, afi, fetal_count, fetal_heart, presentation, findings |
| 5 | `ObgynLabTest` | `obgyn_lab_tests` | pregnancy_id(nullable), patient_id, test_type, value, unit, reference_range, result_date, is_abnormal, notes |
| 6 | `DeliveryRecord` | `delivery_records` | pregnancy_id, delivery_date, delivery_mode(nvd/cesarean/instrumental), place, gestational_age_at_delivery, outcome(live/stillbirth), baby_weight_grams, baby_sex, apgar_1, apgar_5, complications, notes |
| 7 | `PapSmearScreening` | `pap_smear_screenings` | patient_id, doctor_id, test_date, result(normal/ascus/lsil/hsil/cancer), hpv_status(pos/neg/unknown), next_due_date, notes |
| 8 | `ContraceptionRecord` | `contraception_records` | patient_id, doctor_id, method, start_date, end_date, follow_up_date, status(active/stopped), notes |

علاقات: `Patient hasOne ObgynProfile`, `hasMany Pregnancy/PapSmear/Contraception`. `Pregnancy hasMany AntenatalVisit/Ultrasound/LabTest`, `hasOne DeliveryRecord`.

> **قيود محقّقة من الكود (تصحيحات مهمّة):**
> - **female-only:** عمود `patients.gender enum('male','female')` موجود. كل فلوهات obgyn تُقيّد على `gender='female'` (تحقّق + قوائم مفلترة). تسجيل مريضة obgyn يفرض الجنس.
> - **visit_type enum:** `visits.visit_type` = `enum('consultation','session')` فقط. زيارات ANC تستخدم `visit_type='consultation'` وتُميّز عبر `module='obgyn'` + `AntenatalVisit.phase` — **بدون** اختراع قيمة enum جديدة (تفادياً لفخّ الـenum الموثّق).
> - **Prescriptions مشتركة:** موديل `Prescription` يربط عبر `visit_id` (بلا عمود module) — تُعاد استخدامه كما هو لوصفات obgyn (فيتامينات الحمل…).

### خدمة المجال: `ObstetricCalculatorService` (نظير `WhoGrowthStandardsService`)
- `eddFromLmp(lmp)` — قاعدة Naegele (LMP + 280 يوم).
- `gestationalAge(lmp, onDate)` — بالأسابيع+الأيام.
- `trimester(ga)` — 1/2/3.
- `expectedFundalHeight(ga)` — ≈ عمر الحمل بالأسابيع (سم).
- `nextAncSchedule(ga)` — نموذج WHO 8 زيارات.
- `efwPercentile(ga, efw)` — تقدير مئوي مبسّط (Hadlock).

### Migrations (≈ 13، idempotent + data-safe)
1. `seed_obgyn_module_settings` (تفعيل + أسعار افتراضية)
2–9. جداول الموديلات الثمانية
10. `add_obgyn_fields_to_doctors` (is_obgyn, obgyn_consultation_fee)
11. `grant_obgyn_permissions_to_clinical_roles`
12. `link_obstetric_ultrasound_to_inventory` (ServiceSupply لمستلزمات السونار)
13. `add_obgyn_settings_pricing` (anc_fee, ultrasound_fee, delivery_fee, lab_fee)

---

## 4. الصفحات والفلو (Pages & Flow)

### Admin (تحكّم كامل) — `resources/js/Pages/Admin/Obgyn/`
`Dashboard.vue` · `Pregnancies.vue` · `AntenatalVisits.vue` · `Ultrasounds.vue` · `Screenings.vue` (Pap + Contraception) · `Reports.vue` · `Settings.vue`

### Doctor (سريري) — `Pages/Doctor/Obgyn/`
`Dashboard.vue` · `Patients/{Index,Show}.vue` · `Pregnancies/{Index,Show}.vue` (الـ Show = خط زمني للحمل: ANC + سونار + تحاليل) · `Antenatal/{Create,Show}.vue` · `Ultrasound/Create.vue` · `Delivery/Create.vue` · **`Prescriptions/Index.vue`** (وصفات الحمل عبر Prescription المشترك) · `Reports/Index.vue`

### Secretary (تسجيل ومواعيد) — `Pages/Secretary/Obgyn/`
`Patients/{Index,Create,Edit,Show}.vue` · `Pregnancies/Index.vue` · `Antenatal/Index.vue` (جدولة الزيارات)

### Patient portal (متابعتي) — `Pages/Patient/Obgyn/`
`Overview.vue` (حملي الحالي + عدّاد EDD) · `Antenatal.vue` (خط زمني للزيارات) · `Ultrasounds.vue` · `LabResults.vue`

**فلو متابعة الحمل (المسار السعيد):**
```
السكرتيرة تسجّل المريضة → الطبيبة تفتح ملف حمل (LMP→EDD تلقائي)
   → كل زيارة: AntenatalVisit (+Visit module=obgyn → Invoice → Payment)
   → سونار/تحاليل عند الحاجة
   → عند الولادة: DeliveryRecord → الحمل status=delivered
   → المريضة ترى كل ذلك في البوابة + تذكيرات الزيارة القادمة
```
**مسارات الفشل/الاسترداد:** إجهاض/إنهاء → status مناسب يوقف التذكيرات؛ زيارة فائتة → تظهر «متأخرة» في لوحة الطبيبة؛ حمل عالي الخطورة → وسم أحمر بارز.

---

## 5. الربط الكامل بالنظام (Integration) — جوهر الطلب

| نقطة الربط | التفاصيل |
|-----------|---------|
| **ModuleManager** | إضافة `obgyn` إلى `MODULES` + `MEDICAL_MODULES` |
| **Visit** | scope `obgyn()`؛ ANC/سونار يُنشئ Visit(module='obgyn') |
| **Invoice/Payment** | فوترة عبر التدفق الموسوم (module='obgyn')؛ hook التوسيم يعمل تلقائياً |
| **Permissions** | عائلة `obgyn` actions: `view/create/update/delete` فقط (مطابقة لـ pediatric/derma)؛ group `obgyn`. **البيانات الحسّاسة تُحكَم بـ `patients.view_sensitive_medical` القائم — لا نخترع صلاحيات حسّاسة جديدة** |
| **Sidebar** | كل روابط obgyn مقيّدة بـ `obgyn.view` (الأمان) |
| **Inventory** | مستلزمات السونار/الولادة عبر `ServiceSupply` + `consumeForVisit` |
| **Insurance** | مطالبات ANC/الولادة عبر `InsuranceClaim` القائم |
| **Doctor** | حقل `is_obgyn` + `obgyn_consultation_fee` (نظير pediatric) + payment_mode الهجين |
| **Patient** | `hasOne ObgynProfile` + علاقات الحمل |
| **AuditLogger** | على كل إنشاء/تعديل/حذف |
| **Console** | `obgyn:anc-reminders` (تذكير الزيارة القادمة)، `obgyn:edd-approaching` (تنبيه قرب الولادة)، `obgyn:pap-recall` (تجديد المسحة) |
| **data:integrity-check** | فحوصات: حمل نشط بلا ANC منذ 6 أسابيع، حمل delivered بلا DeliveryRecord، ANC visit بلا Visit مرتبط |
| **Settings** | إعدادات الميديول: anc_fee, ultrasound_fee, delivery_fee, نموذج جدول ANC |
| **Service catalog** | `ObgynServiceSeeder` يزرع تصنيفات + خدمات (`module='obgyn'`) — لازم للفوترة **وللظهور تلقائياً في صفحة الخدمات العامة** (نظير `PediatricServiceSeeder`) |
| **Public booking** | أطباء obgyn يظهرون في قائمة الأطباء/الخدمات العامة (مفلتر بالميديول) ومواعيدهم قابلة للحجز — اتساقاً مع باقي الميديولات الطبية |
| **Telemedicine** | استشارة obgyn أونلاين تُنشئ Visit(module=obgyn) — **اختياري/مرحلة لاحقة** (الحالي يدعم derma) |
| **Cross-module → Pediatric** | عند تسجيل ولادة حيّة: خيار إنشاء **ملف طفل (Pediatric patient)** مرتبط بالأم — يربط فلو التوليد بطب الأطفال (اختياري، high-value) |
| **SMS** | التذكيرات عبر `SmsService` القائم + احترام موافقة المريضة (نظير تذكيرات تطعيمات الأطفال) |

---

## 6. التقارير (Reports) — مثل بقية الميديولات

**Admin Dashboard KPIs:** الحموض النشطة، عالية الخطورة، الولادات هذا الشهر، تغطية ANC (متوسط زيارات/حمل)، EDD خلال 30 يوماً، مسحات مستحقة، إيراد obgyn (شهري/سنوي).

**Reports.vue:** توزيع الحموض حسب الثلث، نوع الولادات (طبيعي vs قيصري %)، نتائج المسحات، الإيراد حسب نوع الخدمة (ANC/سونار/ولادة/تحاليل)، أداء كل طبيبة. كلها مرتبطة بنفس مصدر بيانات الفواتير الموسومة.

---

## 7. الاختبار (Testing)

**Backend (Feature):** `tests/Feature/Obgyn/`
- `PregnancyLifecycleTest` — فتح حمل، حساب EDD/GA، الولادة تُغيّر الحالة.
- `ObstetricCalculatorTest` — Naegele، عمر الحمل، الثلث، جدول ANC.
- `AntenatalBillingTest` — زيارة ANC تُنشئ Visit+Invoice موسومة module=obgyn + Payment.
- `ObgynPermissionTest` — دور بلا `obgyn.view` يُمنع (مبدأ الأمان)؛ دور بلا `view_sensitive_medical` لا يرى الحقول الحسّاسة.
- `ObgynModuleDisabledTest` — تعطيل الميديول يخفي المسارات.

**Frontend:** Inertia render assertions لكل صفحة رئيسية (Dashboard/Pregnancy Show/Antenatal Create) + smoke عبر `npm run build`.

**Code review:** تمريرة `/code-review` على الـ diff قبل الدمج.

---

## 8. خطة التنفيذ على مراحل (Phased Execution)

| المرحلة | المحتوى | المخرج |
|--------|---------|--------|
| **P1 — الأساس** | ModuleManager + permissions + 13 migration + 8 models + ObstetricCalculatorService | الميديول مُسجّل، الجداول جاهزة، اختبار خدمة الحساب يمر |
| **P2 — Backend سريري** | Doctor controllers (حمل/ANC/سونار/ولادة) + فوترة + الربط بـ Visit/Invoice | فلو متابعة الحمل يعمل + AntenatalBillingTest يمر |
| **P3 — Admin + Secretary + Settings** | تحكّم كامل + تسجيل + إعدادات + التقارير | إدارة كاملة + Reports KPIs |
| **P4 — Patient portal** | Overview/Antenatal/Ultrasounds/Labs + عدّاد EDD | المريضة ترى متابعتها |
| **P5 — الواجهة (الهوية)** | كل الصفحات بالهوية المعتمدة + أنيميشن + RTL/LTR + i18n | تصميم متّسق مع الموقع |
| **P6 — التشغيل** | console commands + data:integrity-check + sidebar gating | تذكيرات + حُرّاس السلامة |
| **P7 — الاختبار والبذور** | Feature tests + Frontend + **`ObgynServiceSeeder` (كتالوج الخدمات)** + `ObgynDemoSeeder` (حموض بأعمار مختلفة، ANC، سونار، ولادة، مسحات) | بيانات حيّة + `composer check` أخضر |
| **P8 — البناء والرفع** | `npm run build` + commit + push لكل مرحلة | منشور على GitHub |

---

## 8.1 مراجعة الخطة قبل التنفيذ (تصحيحات وإضافات)

**تصحيحات لازمة (تمنع إعادة العمل) — محقّقة من الكود:**
1. **الصلاحيات:** عائلة `obgyn` = `[view,create,update,delete]` فقط (لا صلاحيات حسّاسة جديدة)؛ الحسّاس عبر `patients.view_sensitive_medical`.
2. **visit_type:** enum يقبل `consultation|session` فقط → ANC = `consultation` + تمييز بالـmodule/phase (لا قيمة جديدة).
3. **female-only:** فرض `gender='female'` في كل فلوهات obgyn (العمود موجود).
4. **كتالوج الخدمات:** إضافة `ObgynServiceSeeder` (تصنيفات+خدمات module='obgyn') منفصل عن بذور الـdemo — لازم للفوترة وصفحة الخدمات العامة.
5. **Prescriptions:** صفحة وصفات في لوحة الطبيبة تعيد استخدام `Prescription` المشترك (عبر visit_id).

**إضافات تحسينية:**
6. `edd_source(lmp/scan)` + تصحيح EDD من سونار التأريخ (دقّة سريرية).
7. `phase(antenatal/postnatal)` في زيارة المتابعة لتغطية ما بعد الولادة (PNC) لا الحمل فقط.
8. scope `active()` + ضمان حمل نشط واحد لكل مريضة.
9. ربط اختياري **التوليد → طب الأطفال** عند الولادة الحيّة (إنشاء ملف طفل مرتبط بالأم).
10. الحجز العام + ظهور أطباء obgyn في الموقع (اتساق).
11. i18n: مفاتيح `lang/ar.json` + `en.json` صريحة في P5.
12. تنبيه: إضافة obgyn إلى `MEDICAL_MODULES` تُدخله في حلقات الجاهزية (health/telemedicine) — آمن خلف flag التفعيل، نتحقّق ألا يكسر الجاهزية قبل وجود أطباء.

## 8.2 حالة التنفيذ (مكتمل ✅)

| المرحلة | الحالة | أهم الملفات |
|--------|--------|------------|
| P1 الأساس | ✅ | ModuleManager + permissions + 13 migration + 8 models + `ObstetricCalculatorService` |
| P2 الفوترة | ✅ | `ObgynBillingService` (موسوم/idempotent/reverse) |
| P2ب لوحة الطبيبة | ✅ | `DoctorObgynController` + 3 صفحات (Dashboard/Pregnancies/Show) |
| P3 لوحة الأدمن | ✅ | `AdminObgynController` + Dashboard/Pregnancies/Reports/Settings |
| P4 بوابة المريضة | ✅ | `PatientObgynController` + Overview (عدّاد EDD) |
| P6 الأتمتة | ✅ | `obgyn:reminders` (anc/edd/pap) + 3 فحوصات `data:integrity-check` |
| P7 البذور | ✅ | `ObgynServiceSeeder` (كتالوج) + `ObgynDemoSeeder` (بيانات حيّة) |
| P8 التوثيق/البناء | ✅ | تحديث CLAUDE.md + بناء الأصول |

**التغطية الاختبارية:** 33 اختبار obgyn (calculator/foundation/billing/doctor-flow/admin/patient/automation). كل المسارات مقيّدة بالصلاحيات/الموديل، وكل التدفّق المالي موسوم `obgyn`.

## 9. مفاضلات (Trade-offs) وما سنراجعه مستقبلاً
- **ObgynProfile كجدول منفصل** (لا حقول في patients): أنظف لأن الحقول أنثوية كثيرة، بثمن join إضافي — مقبول.
- **DeliveryRecord 1:1 مع الحمل** يكفي الآن؛ التوائم المتعددة الولادات نادرة — نراجع لو ظهرت الحاجة.
- **حسابات Hadlock/مئويات الجنين مبسّطة**؛ يمكن لاحقاً ربط جداول مرجعية دقيقة كما في WHO growth.
- **الترميز الطبي (ICD-10)** خارج النطاق الآن؛ نقطة توسّع مستقبلية للتأمين.
