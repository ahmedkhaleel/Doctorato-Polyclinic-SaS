# Visit Page — Per-Specialty Clinical Cockpit
## خطة إعادة تصميم وتطوير صفحة الزيارة لكل تخصص

**Date:** 2026-06-07 · **Scope:** `/doctor/visits/{id}` across all 6 medical
specialties · **Type:** design + ADR + phased build plan (تصميم + قرار معماري + خطة بناء)

> الهدف: تحويل صفحة الزيارة من نموذج عام واحد إلى **قمرة قيادة إكلينيكية لكل تخصص**
> (per-specialty clinical cockpit) — لوحة مصمّمة على هوية التخصص، ببياناته الحقيقية،
> مع حركة (animation) راقية، دون كسر أي شيء قائم وبأقل تكرار ممكن.

---

## 1) الوضع الحالي (Current state — grounded in code)

- **`resources/js/Pages/Doctor/Visits/Show.vue`** (962 سطرًا) = الصفحة العامة لكل
  التخصصات عدا الأطفال. أقسامها المشتركة:
  Hero header · Diagnosis/Notes · Prescriptions · Photos (before/after) ·
  Vitals history + trends · Side panel (patient info, vitals widget, insurance,
  visit details, invoice) · Complete/Cancel modals.
- **`DoctorVisitController::show()`** يحمّل: patient+insurance, service,
  prescriptions, invoice+payments, photos, dentalTreatments, vitals (+alerts +history).
- **الأسنان فقط** له قسم إكلينيكي داخل الصفحة (`isDental`): جدول علاجات + مخطط
  أسنان مصغّر + أشعة + **بانر تنبيهات طبية** (risk flags) — وهذا هو **القالب المرجعي**.
- **الأطفال** له صفحة مستقلة (`Doctor/Pediatric/Visits/Show.vue`، 762 سطرًا).
- **الجلدية / النساء والتوليد / النفسي / المخ والأعصاب**: **لا لوحة خاصة** —
  الصفحة العامة فقط. هذه هي الفجوة.

**النماذج والمتحكّمات جاهزة بالفعل** (لا حاجة لبناء طبقة بيانات جديدة):
DermaSession, DermaTreatmentPlan · Pregnancy, ObgynLabTest, DeliveryRecord ·
NeuropsychEncounter, ScaleResult, MedicationPlan, RiskAssessment · والمتحكّمات
`DoctorDermaController`, `DoctorObgynController`, `Neuropsych*Controller`.

---

## 2) القرار المعماري (ADR — how to structure it)

### الخيارات

#### Option A — نموذج عام واحد + أقسام شرطية (الوضع الحالي مكبّرًا)
أضف `v-if="isDerma"`, `v-if="isObgyn"`… داخل `Show.vue` نفسه (مثل الأسنان اليوم).
| البُعد | التقييم |
|---|---|
| التعقيد | يتضخّم الملف من 962 إلى ~3000+ سطرًا |
| العزل | ضعيف — كل التخصصات في ملف واحد |
| القابلية للاختبار | صعبة |
| السرعة للسوق | سريعة لأول تخصص، بطيئة تراكميًا |

**رفض** — يخلق ملفًا عملاقًا هشًّا.

#### Option B — صفحة مستقلة لكل تخصص (مثل الأطفال)
`Derma/Visits/Show.vue`, `Obgyn/Visits/Show.vue`… كل واحدة كاملة.
| البُعد | التقييم |
|---|---|
| التعقيد | تكرار ضخم لكل الـ chrome المشترك (hero/vitals/invoice/prescriptions) |
| العزل | ممتاز |
| الصيانة | سيئة — أي تحسين مشترك يُكرّر 6 مرات |

**رفض** — تكرار غير مقبول؛ الأطفال صار كذلك تاريخيًا فقط.

#### ✅ Option C — هيكل مشترك + لوحة تخصص ديناميكية (المُوصى به)
`Show.vue` يبقى **الغلاف المشترك** (hero, vitals, prescriptions, photos, invoice,
modals). يُستبدل قسم الأسنان المضمّن بفتحة (slot) ديناميكية واحدة:

```
Doctor/Visits/Show.vue                  ← الغلاف المشترك (يَنحُف، لا يتضخّم)
  └─ <SpecialtyVisitPanel :module=…>     ← يحمّل lazy حسب الموديول
       ├─ Visit/Panels/DentalPanel.vue   ← (يُستخرج من القسم الحالي)
       ├─ Visit/Panels/DermaPanel.vue    ← جديد
       ├─ Visit/Panels/ObgynPanel.vue    ← جديد
       ├─ Visit/Panels/NeuroPsychPanel.vue ← جديد (psychiatry+neurology)
       └─ Visit/Panels/GenericPanel.vue  ← fallback
```

```js
// SpecialtyVisitPanel.vue — مُحمّل كسول، مع prefers-reduced-motion
const panels = {
  dental:     defineAsyncComponent(() => import('./Panels/DentalPanel.vue')),
  derma:      defineAsyncComponent(() => import('./Panels/DermaPanel.vue')),
  obgyn:      defineAsyncComponent(() => import('./Panels/ObgynPanel.vue')),
  psychiatry: defineAsyncComponent(() => import('./Panels/NeuroPsychPanel.vue')),
  neurology:  defineAsyncComponent(() => import('./Panels/NeuroPsychPanel.vue')),
}
```

| البُعد | التقييم |
|---|---|
| التعقيد | الـ chrome المشترك مرّة واحدة؛ منطق التخصص معزول وصغير |
| العزل | ممتاز — كل لوحة ملف مستقل قابل للاختبار |
| الأداء | lazy-load: لا يُحمّل JS تخصص آخر |
| الصيانة | أي تحسين مشترك في مكان واحد |
| المخاطرة | منخفضة — استخراج الأسنان أولًا يثبت النمط دون سلوك جديد |

**القرار: Option C.** نبدأ باستخراج الأسنان إلى `DentalPanel.vue` (سلوك مطابق =
شبكة أمان)، ثم نبني لوحات التخصصات الأربعة الناقصة على نفس العقد.

---

## 3) نظام التصميم للّوحة (Design system — shared primitives)

عناصر مشتركة تُبنى مرة وتُستخدم في كل لوحة (تحت `Components/Visit/`):

| Primitive | الغرض |
|---|---|
| `PanelShell.vue` | بطاقة القسم: رأس بشريط لون التخصص + أيقونة + عنوان + إجراء (SVG، لا كلمات) |
| `StatChip.vue` | رقم/مؤشّر مع count-up عند الظهور (الجلسات، أسبوع الحمل، درجة المقياس) |
| `ClinicalTimeline.vue` | خط زمني للأحداث (جلسات derma، تحاليل، مقاييس عبر الوقت) |
| `TrendSpark.vue` | خط بياني صغير (sparkline) لاتجاه (PHQ-9، ضغط، وزن) — يعيد استخدام منطق vitals الموجود |
| `LockedPanel.vue` | بديل مقفول لبيانات حسّاسة (RBAC) — أيقونة قفل + سبب |
| `UiEmptyState` | (موجود) — "لا سجلات بعد" مع CTA للإضافة |

**رموز الهوية (accent tokens)** — موجودة في البراند، تُمرّر للّوحة:

```
dental → #C4A265 (gold)   · derma → #8B5CF6 (violet)
obgyn  → #DB2777 (pink)    · pediatric → #F59E0B (amber)
neurology → #0D9488 (teal) · psychiatry → #4F46E5 (indigo)
```

**الحركة (Animation) — منضبطة لا فوضوية:**
- ظهور متدرّج عند التحميل (staggered reveal، `animation-delay` تتابعي للبطاقات).
- count-up للأرقام في `StatChip` (مرة واحدة عند الدخول للـ viewport).
- توسعة/طيّ ناعمة للأقسام (height/opacity).
- skeleton أثناء lazy-load اللوحة.
- **احترام `prefers-reduced-motion`**: تعطيل كل ما سبق إلى ظهور فوري.
- لا أيقونات إيموجي (يحرسها `NoEmojiInPagesTest`)؛ كل الأيقونات SVG.

---

## 4) محتوى لوحة كل تخصص (Per-specialty panel spec)

> كل لوحة: ترويسة بلون التخصص + 3–4 StatChips + المحتوى الإكلينيكي + روابط
> "افتح الملف الكامل". كلها للقراءة داخل الزيارة (الإجراء العميق في صفحة التخصص).

### 4.1 🦷 Dental (استخراج فقط — لا سلوك جديد)
يُنقل القسم الحالي كما هو إلى `DentalPanel.vue`: جدول العلاجات + المخطط المصغّر +
الأشعة + بانر التنبيهات الطبية. StatChips: عدد العلاجات · إجمالي التكلفة · أسنان متأثرة.

### 4.2 💜 Derma
- **StatChips:** جلسات مكتملة/إجمالي · الخطة النشطة · موعد الجلسة القادمة.
- **المحتوى:** خطة العلاج النشطة (نوع: ليزر/تقشير… + شريط تقدّم الجلسات) ·
  جدول جلسات هذه الزيارة (`DermaSession` المرتبطة بالـ visit) · صور قبل/بعد
  (تُعيد استخدام قسم الصور الموجود مع تجميع بالمنطقة).
- **روابط:** خطة العلاج الكاملة · سجل الجلسات.
- **Controller:** حمّل `patient->dermaTreatmentPlans()->active` + `dermaSessions` للزيارة.

### 4.3 🌸 OB/GYN
- **StatChips:** أسبوع الحمل (محسوب من LMP) · GA · Gravida/Para · حالة الخطورة.
- **المحتوى:** بطاقة الحمل النشط (LMP/EDD + شريط تقدّم الأسابيع 0–40) ·
  آخر التحاليل (`ObgynLabTest`) مع تمييز القيم غير الطبيعية بلون · تنبيه
  "حمل عالي الخطورة" بانر وردي إن وُجد · (إن وُجد) سجل الولادة.
- **روابط:** ملف الحمل الكامل · كل التحاليل · قائمة ANC.
- **Controller:** حمّل `patient->pregnancies()->active->with(labTests, deliveryRecord)`.

### 4.4 🧠 Psychiatry + Neurology (لوحة مشتركة `NeuroPsychPanel`)
- **StatChips:** آخر مقياس (PHQ-9/GAD-7/HIT-6) + شارة الشدّة · أدوية نشطة ·
  (نفسي) مستوى الخطر الحالي.
- **المحتوى:**
  - **تدوينة الزيارة (SOAP/MSE)** من `NeuropsychEncounter` المرتبطة بالزيارة.
  - **اتجاه المقاييس** عبر `TrendSpark` (≥2 نتيجة = خط اتجاه — البيانات موجودة).
  - **خطة الأدوية النشطة** (`MedicationPlan`) مع وسم "مُراقَب/controlled".
  - **تقييم الخطر** (`RiskAssessment`) — **حسّاس**: يُعرض فقط لمن يملك
    `{module}.view_sensitive`، وإلا `LockedPanel`. (التزام RBAC + audit موجود.)
- **روابط:** سجل اللقاءات · المقاييس · الأدوية · (نفسي) خطة السلامة.
- **Controller:** حمّل آخر `encounter` للزيارة + `scaleResults` (آخر فترة) +
  `medicationPlans()->active` + (مع gate) `riskAssessments()->active`.

### 4.5 الغلاف المشترك (Generic shell — تحسينات تفيد الكل)
- **Hero أغنى:** شريط لون التخصص + شارة الموديول + حالة الزيارة + مؤقّت المدّة.
- **شريط تقدّم الزيارة:** انتظار → قيد التنفيذ → مكتملة (stepper متحرّك).
- **Vitals**: موجود وجيّد — يبقى، مع توحيد بصري مع الـ StatChips الجديدة.

---

## 5) عقد الـ Backend (Controller contract)

توسعة `DoctorVisitController::show()` بكتلة per-module تُحاكي كتلة الأسنان، مع
حماية N+1 عبر علاقات المريض، وبوابة الحساسية:

```php
$extra += match ($visit->module) {
    'derma'      => $this->dermaExtras($visit),
    'obgyn'      => $this->obgynExtras($visit),
    'psychiatry',
    'neurology'  => $this->neuroPsychExtras($visit, $request->user()), // RBAC داخلها
    default      => [],
};
```

- لا تغيير في الـ contract للأطفال (صفحته منفصلة).
- لا تسريب بيانات حسّاسة: `neuroPsychExtras` تُسقط `riskAssessment`/notes الحسّاسة
  ما لم يملك المستخدم `{module}.view_sensitive`، وتُسجّل القراءة عبر
  `MedicalDataAccessLog::record(...)`.

---

## 6) خطة التنفيذ المرحلية (Phased plan + checklist)

> كل مرحلة: فرع مستقل، اختبار، نشر متحقّق، لا شيء مكسور. الترتيب يبني شبكة الأمان أولًا.

### Phase 0 — البنية + شبكة الأمان (لا سلوك جديد)
- [ ] أنشئ `Components/Visit/` primitives: `PanelShell`, `StatChip`, `ClinicalTimeline`, `TrendSpark`, `LockedPanel`.
- [ ] أنشئ `Visit/SpecialtyVisitPanel.vue` (موزّع lazy) + `Panels/GenericPanel.vue`.
- [ ] **استخرج** قسم الأسنان الحالي إلى `Panels/DentalPanel.vue` بسلوك **مطابق**.
- [ ] اختبار لقطة/تفاعل: زيارة أسنان تعرض نفس المحتوى تمامًا (regression guard).
- [ ] `npm run build` + تأكيد الـ manifest. **نشر.**

### Phase 1 — Derma
- [ ] `dermaExtras()` في الـ controller (+ علاقات).
- [ ] `Panels/DermaPanel.vue` (خطة + جلسات + قبل/بعد).
- [ ] اختبار: زيارة derma تعرض الخطة والجلسات؛ زيارة بلا خطة تعرض EmptyState.
- [ ] **نشر.**

### Phase 2 — OB/GYN
- [ ] `obgynExtras()` (الحمل النشط + التحاليل + الولادة).
- [ ] `Panels/ObgynPanel.vue` (شريط الأسابيع + تحاليل + بانر الخطورة).
- [ ] اختبار: زيارة بحمل نشط · زيارة بلا حمل · حمل عالي الخطورة.
- [ ] **نشر.**

### Phase 3 — Psychiatry + Neurology (+ RBAC)
- [ ] `neuroPsychExtras()` مع بوابة `view_sensitive` + audit.
- [ ] `Panels/NeuroPsychPanel.vue` (SOAP + TrendSpark + أدوية + خطر مقفول/مرئي).
- [ ] اختبار: مستخدم بصلاحية يرى الخطر · بدونها يرى `LockedPanel` · تسجيل audit.
- [ ] **نشر.**

### Phase 4 — تلميع الغلاف + الحركة
- [ ] stepper تقدّم الزيارة + ظهور متدرّج + count-up + skeletons.
- [ ] `prefers-reduced-motion` معطِّل كامل.
- [ ] مرور حُرّاس الواجهة (Emoji/IconLabel/ImageAlt/FocusTrap) — يجب أن تبقى خضراء.
- [ ] **نشر.**

### Phase 5 — (اختياري) محاذاة الأطفال
- [ ] إعادة استخدام نفس الـ primitives في صفحة الأطفال لتوحيد الإحساس (دون إعادة كتابة منطقها).

---

## 7) تحليل المخاطر والمقايضات (Trade-offs & risks)

| المخاطرة | التخفيف |
|---|---|
| تضخّم `Show.vue` | Option C: استخراج لوحات؛ الغلاف يَنحُف فعليًا |
| كسر زيارة الأسنان أثناء الاستخراج | Phase 0 = سلوك مطابق + اختبار قبل أي ميزة جديدة |
| تسريب بيانات نفسية حسّاسة | بوابة `view_sensitive` في الـ backend + `LockedPanel` + audit |
| N+1 في تحميل التخصص | علاقات مُحمّلة مسبقًا عبر المريض (نمط الأسنان) |
| صفحة لا تظهر في الإنتاج | `npm run build` + فحص manifest في CI (lesson مُوثّق) |
| حركة تُتعب أو تُبطئ | lazy-load + `prefers-reduced-motion` + count-up مرّة واحدة |

---

## 8) مقترحات تحسين تجربة المستخدم (UX upgrades worth adding)

1. **اختصارات داخل الزيارة:** "ابدأ جلسة derma"، "سجّل تحليل"، "أضف نتيجة مقياس"
   مباشرة من اللوحة (تفتح صفحة التخصص مع prefill) — توفّر نقرات.
2. **مؤقّت مدّة الزيارة** المرئي + زمن منذ آخر زيارة للمريض.
3. **مقارنة سريعة:** "آخر زيارة مقابل هذه" (derma قبل/بعد، obgyn أسبوع/وزن، neuropsych درجة المقياس).
4. **طباعة/تصدير ملخّص الزيارة** PDF بترويسة الفرع (يوجد per-branch letterheads).
5. **لوحة المخاطر للنفسي** أعلى الصفحة كبانر (مثل بانر الأسنان) عند خطر مرتفع.
6. **تكامل العمولة:** إظهار عمولة الزيارة المحسوبة للطبيب (تُحفظ بالفعل) ضمن بطاقة الفاتورة.

---

## 9) ملخّص القرار

> صفحة الزيارة ليست "فارغة" بل **عامة**: الأسنان والأطفال فقط لهما عمق إكلينيكي.
> الخطة تُعمّم نمط الأسنان (المُثبت) على derma/obgyn/neuropsych عبر **غلاف مشترك +
> لوحات تخصص lazy** (Option C)، ببيانات موجودة فعلًا، وتصميم/حركة موحّدين، وبوابة
> حساسية للنفسي — على مراحل، كلٌّ باختبار ونشر متحقّق، دون كسر أي شيء.
