# خطة ميديول الإشعارات الموحّد (Unified Notifications Hub)

> مركز إشعارات واحد يحكم **WhatsApp + SMS + Email + In‑App** عبر كل النظام،
> بتحكّم كامل: تفعيل القنوات، توجيه كل حدث لقناته، الحصص (الكوتا) الإجمالية
> وبكل قناة، الموافقات، سجلّ تسليم كامل، وتحويل احتياطي (fallback).

---

## 0. الوضع الحالي (مبني على الكود)

| المكوّن | الحالة |
|--------|--------|
| **SMS** | `SmsService::send()` — مزوّدون: `unifonic`, `twilio`, `gateway`, `none` (يُختار عبر `Setting('sms_provider')`). يُرسَل عبر `SendSmsJob` / `BulkSmsJob`. |
| **Email** | `SendEmailJob` → `Mail::to()->send($mailable)` بإعدادات `.env` العامة فقط. |
| **In‑App** | جدول `notifications` (Laravel) + `Message` (مستخدم↔مستخدم) + `SendNotificationJob`. |
| **القوالب** | `CommunicationTemplate` (channel + subject + body_ar/en + متغيّرات) و`SmsTemplate`. |
| **الموافقات** | أعمدة المريض `notify_{email,sms}_{bookings,reminders,marketing}` + `canReceive($channel,$category)`. |
| **مفاتيح أحداث** | `sms_on_booking_confirmed`, `sms_on_visit_completed`, `sms_on_lab_order_ready`, `sms_recall_*`, وحصص يومية مبعثرة (`sms_*_max_per_day`). |
| **المُرسِلون** | مشتّتون: `DentalSmartNotificationService`, تذكيرات obgyn/pediatric/bookings — كلٌّ يستدعي `SmsService`/`SendSmsJob` مباشرة. |

**الثغرات:** لا واتساب · لا سجلّ تسليم موحّد · لا كوتا إجمالية · لا مصفوفة توجيه حدث→قناة · إيميل عام فقط (لا SMTP لكل عيادة) · لا fallback بين القنوات · التحكّم موزّع على عشرات مفاتيح `Setting`.

---

## 1. المتطلبات

### وظيفية
- نقطة إرسال **واحدة** لكل النظام: `Notifier::event($eventKey, $recipient, $data)`.
- **3 قنوات خارجية** + داخلية: WhatsApp، SMS، Email، In‑App.
- **WhatsApp**: وضعان — **Cloud API الرسمي (Meta)** + **Bridge** (خادم WhatsApp‑Web/Baileys غير رسمي).
- **SMS**: **Twilio** (دولي) + **SMS Misr** (محلي مصري أرخص) + الإبقاء على Unifonic/Gateway، مع اختيار المزوّد تلقائياً حسب الدولة/التكلفة.
- **Email**: إعدادات SMTP **قابلة للضبط من اللوحة** (لكل عيادة/عميل) لا من `.env` فقط.
- **تحكّم كامل**: تشغيل/إيقاف كل قناة · مصفوفة «حدث → القنوات الفعّالة» · من يُرسِل (الأدوار) · **حصص إجمالية ولكل قناة** (يومي/شهري) · موافقات المريض · **سجلّ كل رسالة** (لمن/أي قناة/الحالة/التكلفة).
- **fallback**: لو فشلت قناة، يجرّب البديل (مثلاً WhatsApp → SMS).
- **broadcast**: إرسال جماعي مُجزّأ (segments) باحترام الكوتا والموافقات.

### غير وظيفية
- يعمل على **cPanel مشترك** (queue عبر database driver، لا Redis مفترض).
- التكلفة: تتبّع تكلفة كل رسالة (SMS/واتساب مدفوع) + سقف إنفاق.
- أمان: كل بيانات الاعتماد **مشفّرة** (نمط `Setting::ENCRYPTED_KEYS`).
- متوافق مع RTL/LTR والهوية، وكل تبويب مقيّد بصلاحية `notifications.*`.

---

## 2. التصميم العالي (High‑Level)

```
        كل النظام (حجوزات، زيارات، حمل، تطعيمات، فواتير، CRM …)
                              │  Notifier::event('booking.confirmed', $patient, [...])
                              ▼
                  ┌────────────────────────────┐
                  │   NotificationService       │  «العقل»
                  │  (dispatcher + governance)  │
                  └─────────────┬──────────────┘
        ┌──────────────┬────────┼────────────┬─────────────────┐
        ▼              ▼        ▼             ▼                 ▼
   resolveRoute   checkConsent checkQuota  renderTemplate   pickChannels+fallback
   (حدث→قنوات)    (موافقة)     (الكوتا)     (القالب+المتغيّرات)   (الترتيب)
        └──────────────┴────────┴────────────┴─────────────────┘
                              │  لكل قناة: NotificationMessage
                              ▼
              ┌───────────────┴────────────────┐
              ▼            ▼            ▼        ▼
         EmailDriver   SmsDriver   WhatsAppDriver  InAppDriver
              │         (twilio/      (cloud_api/      │
              │          smsmisr/      bridge)         │
              │          unifonic)                     │
              ▼            ▼            ▼               ▼
        SendChannelJob (queued, retry, dedup) ──► notification_logs (سجلّ كل محاولة)
                                              └──► webhooks ◄── حالة التسليم (delivered/read/failed)
```

**القاعدة الذهبية:** لا أحد في النظام يستدعي SMS/Email مباشرة بعد الآن — كله عبر `Notifier`. (المُرسِلون القدامى يُغلَّفون تدريجياً، انظر §8.)

---

## 3. نموذج البيانات (Data Model)

| الجدول | الغرض / أهم الحقول |
|--------|------------------|
| `notification_events` | كتالوج الأحداث: `key` (booking.confirmed), `label_ar/en`, `category` (transactional/reminder/marketing), `default_channels` (json), `is_active`. |
| `notification_channel_routes` | مصفوفة التوجيه: `event_key`, `channel` (whatsapp/sms/email/in_app), `enabled`, `priority` (لترتيب fallback). |
| `notification_channels` | إعداد كل قناة: `channel`, `enabled`, `provider`, `config` (json مشفّر: SMTP/Twilio/SMSMisr/WhatsApp creds), `from_name`, `daily_cap`, `monthly_cap`. |
| `notification_quotas` (أو عدّاد cache) | عدّاد الاستهلاك: `scope` (global/channel), `period` (day/month), `count`, `cost`, `window_start`. |
| `notification_logs` | **سجلّ كل رسالة**: `recipient_type/id`, `to` (phone/email), `channel`, `provider`, `event_key`, `template_id`, `status` (queued/sent/delivered/read/failed), `cost`, `error`, `dedup_key`, `meta` (json), timestamps. |
| `notification_templates` | توحيد القوالب متعدّدة القنوات: `event_key`, `channel`, `subject`, `body_ar/en`, متغيّرات. (يدمج/يستبدل `CommunicationTemplate` تدريجياً). |
| **المريض/المستخدم** | إضافة أعمدة موافقة الواتساب: `notify_whatsapp_{bookings,reminders,marketing}` + رقم واتساب اختياري. |

> الكوتا والسجلّ معاً يحقّقان طلبك: «التحكم في العدد الإجمالي، ومَن يُرسِل، وأي وسيلة تعمل».

---

## 4. القنوات (Channel Drivers)

كلها تنفّذ واجهة واحدة:
```php
interface ChannelDriver {
    public function key(): string;          // 'whatsapp' | 'sms' | 'email' | 'in_app'
    public function isConfigured(): bool;
    public function send(NotificationMessage $m): DeliveryResult; // status + cost + provider_ref
}
```

### 📧 Email — `EmailChannel`
- إعدادات SMTP **من قاعدة البيانات** (host/port/user/pass/encryption/from) عبر `notification_channels.config` المشفّر — تُحقن في `config('mail')` وقت الإرسال (runtime mailer) بدل `.env` فقط.
- يدعم **لكل عيادة** إعداداتها (في SaaS متعدّد المستأجرين: scope على tenant_id؛ الآن: إعداد واحد عام + جاهزية التوسّع).

### 💬 SMS — `SmsChannel` (يلفّ `SmsService` الحالي)
- استراتيجية مزوّد: `twilio` (دولي) · **`smsmisr` (جديد — مصر)** · `unifonic` · `gateway` · `none`.
- **اختيار ذكي**: أرقام مصر (+20) → SMS Misr (أرخص)؛ غيرها → Twilio. قابل للتجاوز يدوياً.
- إضافة `SmsMisrDriver`: REST لـ smsmisr.com (`username/password/sender` مشفّرة).

### 🟢 WhatsApp — `WhatsAppChannel` (وضعان)
- **`cloud_api`**: WhatsApp Business Cloud API الرسمي (Meta) — رسائل **قوالب معتمدة** (templates) للإشعارات خارج نافذة 24 ساعة + رسائل حرّة داخلها. (`phone_number_id`, `access_token`, `waba_id` مشفّرة).
- **`bridge`**: خادم جسر غير رسمي (Baileys/WhatsApp‑Web) عبر webhook URL + توكن — أرخص لكن أقل موثوقية/مخاطرة حظر. (`bridge_url`, `bridge_token`).
- استقبال **حالة التسليم** (sent/delivered/read) عبر webhook → تحديث `notification_logs`.

### 🔔 In‑App — `InAppChannel`
- يلفّ نظام `notifications`/`Message` الحالي (للوحات الأدمن/الطبيب/المريض).

---

## 5. مركز التحكّم (Admin Control Center) — `/admin/notifications`

تبويبات (كلها مقيّدة بـ`notifications.view`/`.update`):
1. **القنوات (Channels):** تفعيل/إيقاف كل قناة + بيانات الاعتماد + **زر اختبار إرسال** لكل قناة (مثل `testAgora`).
2. **مصفوفة التوجيه (Routing):** جدول «حدث × قناة» بمفاتيح تشغيل + ترتيب fallback. (يجيب «أي وسيلة تعمل لكل حدث»).
3. **الحصص والحدود (Quotas):** سقف يومي/شهري **إجمالي** + لكل قناة + سقف تكلفة. عند بلوغ السقف يتوقّف الإرسال (يُسجَّل سبب التوقّف).
4. **الموافقات (Consent):** الافتراضات + احترام تفضيلات المريض لكل (قناة×فئة).
5. **السجلّ والتحليلات (Logs & Analytics):** كل رسالة (لمن/قناة/حالة/تكلفة) + رسوم: المُرسَل/المُسلَّم/الفاشل، التكلفة الشهرية، أكثر الأحداث إرسالاً.
6. **الإرسال اليدوي/الجماعي (Broadcast):** اختيار شريحة (مرضى/أطباء حسب فلتر) + قناة + قالب → إرسال مُجزّأ يحترم الكوتا والموافقات.
7. **القوالب (Templates):** تحرير قوالب كل حدث لكل قناة (ar/en + متغيّرات + معاينة).

---

## 6. تدفّق الإرسال + الموثوقية

```
Notifier::event(key, recipient, data)
  1. الحدث مفعّل؟ القناة(ات) الموجَّهة؟           (notification_events + routes)
  2. موافقة المستلِم لكل (قناة×فئة)؟              (consent) — يتخطّى transactional الحرِج
  3. ضمن الكوتا (إجمالي + قناة + تكلفة)؟           (quota) — وإلا يُسجَّل skipped:quota
  4. dedup_key؟ (منع التكرار خلال نافذة)          (notification_logs)
  5. render القالب (لغة المستلِم + متغيّرات)
  6. لكل قناة بالأولوية → SendChannelJob (queued)
       └ نجح → status=sent (+cost) ؛ فشل → جرّب القناة التالية (fallback) ؛ retry×3 backoff
  7. webhook التسليم → status=delivered/read/failed
```
- **Idempotency**: `dedup_key` (مثل `booking_reminder:{id}:{date}`) يمنع الإرسال المزدوج.
- **Rate/Quota**: عدّاد ذرّي (DB/cache) قبل الإرسال.
- **Retry/Fallback**: إعادة المحاولة داخل القناة، ثم القناة التالية بالأولوية.
- **التكلفة**: كل `DeliveryResult` يحمل تكلفة → تُجمَع في الكوتا والتقارير.

---

## 7. الربط بكل النظام (Distribution)

تُعرَّف الأحداث مرّة، وكل المنتجين يطلقونها:
`booking.confirmed`, `visit.completed`, `appointment.reminder`, `invoice.overdue`,
`obgyn.anc_reminder`, `obgyn.edd_approaching`, `obgyn.pap_recall`,
`pediatric.vaccination_due`, `dental.followup`, `loyalty.earned`, `password.reset`, …
- **حُرّاس السلامة**: فحص `data:integrity-check` لرسائل عالقة/فاشلة متراكمة.
- **التقارير المالية**: تكلفة الإشعارات تظهر كمصروف تشغيلي.

---

## 7.1 خريطة التكامل في كل نقطة لمس (Integration Map) ⭐

> **المبدأ:** كل رسالة تُرسَل عبر `Notifier` تُسجَّل في `notification_logs`
> **مرتبطة بالمريض (recipient)** — فتظهر تلقائياً في **سجلّ تواصله** داخل ملفه،
> وفي نفس الوقت تُطلَق من نقطة العمل المناسبة (حجز/متابعة/CRM/تذكير).

### 👤 ملف المريض (Patient File) — `Admin/Patients/Show` + `Timeline`
- **تبويب «التواصل» (Communications):** سجلّ زمني لكل ما أُرسِل لهذا المريض
  (`notification_logs WHERE recipient = patient`): القناة (واتساب/SMS/إيميل)،
  الحدث، النص، الحالة (مُرسَل/مُسلَّم/مقروء/فاشل)، التاريخ، التكلفة.
- **إرسال يدوي فوري:** زر «إرسال رسالة» (اختيار قناة + قالب أو نص حرّ) — يمرّ
  بنفس الحوكمة (موافقة/كوتا/تسجيل).
- **تفضيلات القنوات:** مفاتيح موافقة المريض (واتساب/SMS/إيميل × حجوزات/تذكيرات/تسويق)
  قابلة للتعديل من ملفه مباشرة.
- يُدمَج أيضاً في **بوابة المريض** (يرى رسائله) وملفّات الطبيب/السكرتيرة (قراءة).

### 📅 الحجوزات (Bookings) — أحداث
`booking.created` · `booking.confirmed` · `booking.rescheduled` · `booking.cancelled` · `booking.reminder`.
→ توجيه `BookingController`/`ProcessBookingSmsJob`/`SmsNotificationService` عبر `Notifier`
(بدل النداء المباشر) فيظهر كل تأكيد/تذكير حجز في ملف المريض.

### ⏰ تذكير المواعيد (Appointment Reminders)
`appointment.reminder.day_before` · `appointment.reminder.same_day`.
→ أوامر التذكير المجدولة (`patients:send-sms-reminders`, `bookings:send-reminders`)
تُطلِق الأحداث بدل استدعاء SMS مباشرة؛ القناة تُحدَّد من مصفوفة التوجيه (واتساب أولاً ثم SMS).

### 🔁 المتابعات (Follow‑ups) لكل التخصّصات
`dental.followup` · `derma.recall` · `pediatric.vaccination_due/overdue` ·
`obgyn.anc_reminder` · `obgyn.edd_approaching` · `obgyn.pap_recall`.
→ `DentalFollowupService`/`DentalSmartNotificationService` وأوامر obgyn/pediatric
تمرّ عبر `Notifier` — وكلها تُسجَّل في ملف المريض كـ«متابعة مُرسَلة».

### 📣 CRM والتسويق (Leads)
`lead.welcome` · `lead.nurture.step_{n}` · `lead.follow_up_due` · `lead.reactivation`.
→ `FollowUpAutomationService` + `ProcessFollowUpSequences` + `CommunicationService`
تُعاد فوق الـHub، فتُستخدم نفس القنوات/القوالب/الكوتا. (الـCRM يكسب الواتساب تلقائياً).
- سجلّ تواصل العميل المحتمل يظهر في بطاقة الـLead (نفس مصدر `notification_logs`).

### 🧾 المالية والولاء (Finance / Loyalty)
`invoice.created` · `invoice.overdue` · `payment.received` · `loyalty.earned` · `loyalty.redeemed`.

### 🔐 الحساب (Account)
`password.reset` · `patient.account_created` · `otp.verify` (لو لزم).

**جدول الربط (مختصر):**

| المصدر الحالي | يصبح | يظهر في |
|--------------|------|---------|
| `ProcessBookingSmsJob` / `SmsNotificationService` | `Notifier::event('booking.*')` | ملف المريض + سجلّ الحجز |
| أوامر `*-reminders` المجدولة | `Notifier::event('appointment.reminder.*')` | ملف المريض |
| `DentalFollowupService` / obgyn|pediatric reminders | `Notifier::event('{module}.followup/…')` | ملف المريض + التخصّص |
| `FollowUpAutomationService` / `CommunicationService` (CRM) | `Notifier::event('lead.*')` | بطاقة الـLead |
| `InvoiceOverdue` listener | `Notifier::event('invoice.overdue')` | ملف المريض + المالية |

> النتيجة: **مصدر واحد للحقيقة** — أي رسالة، من أي نقطة، تُحكَم مركزياً وتُرى في ملف صاحبها.

## 8. خطة الترحيل (دون كسر أي شيء)

1. بناء الـHub جنباً إلى جنب؛ `SmsService` يصبح **محرّك SMS** داخله (لا يُحذف).
2. إنشاء `Notifier` facade + الأحداث + التوجيه + السجلّ.
3. **تغليف تدريجي**: استبدال نداءات `SendSmsJob`/`SendEmailJob` المباشرة بـ`Notifier::event(...)` ميديولاً ميديولاً، مع إبقاء التوافق الخلفي.
4. ترحيل مفاتيح `sms_on_*`/`sms_*_max_per_day` المبعثرة إلى مصفوفة التوجيه + الكوتا (migration ينقل القيم).
5. توحيد `CommunicationTemplate`/`SmsTemplate` تحت `notification_templates`.

---

## 9. خطة التنفيذ على مراحل

| المرحلة | المحتوى |
|--------|---------|
| **P1 — النواة** | جداول (events/routes/channels/logs/quotas) + `ChannelDriver` + `NotificationService` + `Notifier` facade + In‑App & Email drivers + تغليف SMS الحالي. |
| **P2 — SMS Misr + اختيار ذكي** | `SmsMisrDriver` + توجيه حسب الدولة + التكلفة. |
| **P3 — WhatsApp** | `cloud_api` + `bridge` drivers + webhook حالة التسليم + قوالب واتساب. |
| **P4 — مركز التحكّم (UI)** | Channels/Routing/Quotas/Consent/Logs/Broadcast/Templates — بالهوية والأنيميشن. |
| **P5 — الموافقات + الكوتا** | أعمدة واتساب للمريض + فرض الكوتا الإجمالية + سقف التكلفة + fallback. |
| **P6 — الترحيل الكامل** | تحويل كل المُرسِلين المبعثرين إلى `Notifier` + ترحيل المفاتيح/القوالب. |
| **P7 — اختبار + بذور + رفع** | اختبارات (توجيه/كوتا/موافقة/fallback/تسجيل) + بيانات تجريبية + بناء + رفع. |

---

## 10. مفاضلات (Trade‑offs)

- **WhatsApp Cloud API (موثوق/معتمد، بتكلفة وقوالب موافَق عليها) مقابل Bridge (أرخص، خطر حظر).** نوفّر الوضعين ونوصي بالرسمي للإنتاج.
- **SMS Misr (أرخص محلياً) مقابل Twilio (تغطية عالمية).** توجيه هجين حسب الدولة.
- **SMTP لكل عيادة من DB** يزيد المرونة لكن يتطلّب تشفير الاعتماد + حقن runtime mailer.
- **queue على database** (cPanel) كافٍ لأحجامنا؛ نراجع لـRedis لو زاد الحجم.
- **توحيد القوالب** يكسر تكرار `SmsTemplate`/`CommunicationTemplate` لكنه ترحيل لمرة واحدة.
