# قائمة تنفيذ ميديول الإشعارات الموحّد — TODO كامل

> مرجع التصميم: `docs/NOTIFICATIONS_MODULE_PLAN.md`
> الاصطلاح: لكل مرحلة → بناء + lint (`pint`) + اختبار (`php artisan test`) + بناء أصول (`npm run build`) + commit/push.
> القنوات: `whatsapp` · `sms` · `email` · `in_app`. القاعدة: لا إرسال مباشر — كله عبر `Notifier`.

---

## 📦 حالة التنفيذ (Implementation status — كل المراحل مكتملة)

| المرحلة | الوصف | الحالة | الاختبارات |
|--------|-------|--------|-----------|
| P0 | الصلاحيات (`notifications` + `send`) | ✅ | — |
| P1 | النواة: 6 migrations + المحرّك (Notifier/NotificationService/Drivers/Quota/Job) | ✅ | NotificationHubTest (12) |
| P2 | مزوّد SMS Misr + تطبيع الهاتف المصري | ✅ | SmsMisrProviderTest (6) |
| P3 | واتساب (Cloud API + Bridge) + Webhook استلام/قراءة | ✅ | WhatsAppChannelTest (8) |
| P4 | لوحة التحكم الإدارية (ControlCenter + Logs) | ✅ | AdminNotificationHubTest (11) |
| P5 | موافقة واتساب + fallback + الحصص | ✅ | WhatsAppConsentFallbackTest (4) |
| P6 | تبويب المراسلات في ملف المريض (سجل + إرسال يدوي + تفضيلات) | ✅ | PatientCommunicationTest (4) |
| P7 | أمر إعادة المحاولة + فحوص السلامة + قوالب افتراضية | ✅ | NotificationsRetryTest (7) |

**إجمالي اختبارات الميديول: 52 أخضر.**

**ترحيل المرسِلات عبر `Notifier` (مكتمل — 4 دفعات):**
- ✅ الحجز: تأكيد + تذكير قبل يوم + تذكير نفس اليوم (`SmsNotificationService`)
- ✅ الزيارة + استدعاء الأسنان/الجلدية
- ✅ المستمعات: استلام الدفع + الفاتورة المتأخرة + نقاط الولاء (+ مواءمة الولاء كـ marketing)
- ✅ الترحيب (PatientRegistered) + جاهزية معمل الأسنان + الموافقة على خطة العلاج
- ✅ إصلاح: الإرسال اليدوي + اختبار اللوحة يستخدمان حدث `manual.message` بلا قالب
  (حتى لا يُستبدل نص الموظف بقالب). توافق خلفي: `SmsChannel` يرجع لإعدادات SMS القديمة.
- ⬜ المتبقّي عمداً: `CommunicationService` (محرّك مراسلات الـ CRM للعملاء المحتملين —
  نظام مستقل بقنواته وسجل `LeadActivity`؛ يُدمج لاحقاً كخطوة منفصلة).

كل تدفق مُرحَّل يكسب: سجل تسليم + ظهور في ملف المريض + ترقية واتساب مع fallback +
احترام الموافقة + منع التكرار، دون توقّف إرسال SMS (لا تراجع).

---

## ✅ P0 — التحضير (Foundation prep)
- [ ] تأكيد تشغيل قاعدة البيانات (MySQL :3308) وأن `composer test` أخضر كنقطة بداية.
- [ ] إضافة عائلة صلاحيات `notifications` في `config/permissions.php` (group=system، actions: view/update/send/delete).
- [ ] منح `notifications.*` للأدوار الإدارية عبر migration (نمط `grant_*_permissions_to_clinical_roles`).
- [ ] إضافة مفاتيح اعتماد القنوات إلى `Setting::ENCRYPTED_KEYS` (whatsapp_token, smsmisr_password, smtp_password…).

---

## 🟦 P1 — النواة (Core engine)

### المخطّط (Migrations)
- [ ] `create_notification_events_table` (key, label_ar/en, category, default_channels json, is_active).
- [ ] `create_notification_channels_table` (channel, enabled, provider, config json مشفّر, from_name, daily_cap, monthly_cap).
- [ ] `create_notification_channel_routes_table` (event_key, channel, enabled, priority).
- [ ] `create_notification_logs_table` (recipient_type/id, to, channel, provider, event_key, template_id, status, cost, error, dedup_key, meta json, sent_at, delivered_at, read_at).
- [ ] `create_notification_templates_table` (event_key, channel, subject, body_ar/en, is_active).
- [ ] `seed_notification_events` (الكتالوج: booking.*, appointment.reminder.*, {module}.*, lead.*, invoice.*, loyalty.*, account.*).
- [ ] `seed_default_notification_channels` (الصفوف الأربعة معطّلة افتراضياً عدا in_app).

### النماذج (Models)
- [ ] `NotificationEvent`, `NotificationChannel`, `NotificationChannelRoute`, `NotificationLog`, `NotificationTemplate` (+ علاقات + casts + تشفير `config`).
- [ ] إضافة `morphMany notificationLogs` لـ `Patient` و`User` و`Lead`.

### المحرّك (Services)
- [ ] واجهة `App\Notifications\Contracts\ChannelDriver` (`key/isConfigured/send`).
- [ ] DTO: `NotificationMessage` (recipient, to, channel, subject, body, event_key, dedup_key, meta) + `DeliveryResult` (status, provider, cost, providerRef, error).
- [ ] `NotificationService` (dispatcher): resolveRoute → checkConsent → checkQuota → dedup → renderTemplate → pickChannels(fallback) → dispatch jobs + log.
- [ ] `Notifier` facade/helper: `Notifier::event($key, $recipient, array $data, ?array $channels)`.
- [ ] `SendChannelJob` (queued, tries=3, backoff) — ينفّذ driver + يحدّث `notification_logs`.
- [ ] محرّكات أولية: `InAppChannel` (يلفّ `notifications`/`Message`) + `EmailChannel` (runtime mailer من DB config) + `SmsChannel` (يلفّ `SmsService` الحالي كما هو).
- [ ] خدمة الكوتا `NotificationQuota` (عدّاد day/month لكل قناة + إجمالي + سقف تكلفة) عبر cache/DB ذرّي.
- [ ] خدمة الموافقة: قراءة أعمدة `notify_*` للمريض (transactional الحرِج يتخطّاها).

### اختبارات P1
- [ ] `NotifierRoutingTest` — حدث معطّل لا يُرسِل؛ التوجيه يختار القنوات الصحيحة.
- [ ] `NotificationQuotaTest` — تجاوز السقف يوقف الإرسال ويُسجَّل `skipped:quota`.
- [ ] `NotificationConsentTest` — رفض المريض يمنع marketing/reminder ويُسمح بالحرِج.
- [ ] `NotificationDedupTest` — نفس `dedup_key` لا يُرسَل مرّتين.
- [ ] `NotificationLogTest` — كل محاولة تُسجَّل بالحالة الصحيحة.

---

## 🟦 P2 — SMS Misr + التوجيه الذكي
- [ ] `SmsMisrDriver` (REST: username/password/sender) داخل `SmsChannel`.
- [ ] منطق اختيار المزوّد: أرقام +20 → SMS Misr، غيرها → Twilio (قابل للتجاوز).
- [ ] إعدادات SMS Misr في `notification_channels.config` (مشفّرة) + زر اختبار.
- [ ] اختبار `SmsProviderRoutingTest` (رقم مصري → smsmisr، دولي → twilio).

---

## 🟦 P3 — WhatsApp
- [ ] `WhatsAppChannel` بوضعين: `cloud_api` (Meta) و`bridge`.
- [ ] `cloud_api`: إرسال قوالب معتمدة + رسائل حرّة داخل نافذة 24س (phone_number_id/access_token/waba_id مشفّرة).
- [ ] `bridge`: إرسال عبر webhook URL + token.
- [ ] Webhook استقبال حالة التسليم (sent/delivered/read/failed) → تحديث `notification_logs` (+ توقيع تحقّق).
- [ ] إدارة قوالب الواتساب (ربط template_name لكل حدث).
- [ ] اختبار `WhatsAppChannelTest` (وهمي: تكوين/إرسال/تحديث الحالة من webhook).

---

## 🟦 P4 — مركز التحكّم (Admin UI) — `/admin/notifications`
- [ ] `AdminNotificationController` (channels/routing/quotas/consent/logs/broadcast/templates + test-send).
- [ ] مسارات `/admin/notifications/*` مقيّدة بـ`notifications.view`/`.update`/`.send`.
- [ ] سايدبار الأدمن: قسم «الإشعارات» (gated) بالهوية.
- [ ] صفحات Vue (بالهوية + أنيميشن + RTL/LTR + a11y modals):
  - [ ] `Channels.vue` (تفعيل + اعتماد مشفّر [مقنّع] + زر اختبار لكل قناة).
  - [ ] `Routing.vue` (مصفوفة حدث×قناة + ترتيب fallback).
  - [ ] `Quotas.vue` (سقوف يومي/شهري/تكلفة).
  - [ ] `Logs.vue` (سجلّ + فلاتر + حالة + تكلفة).
  - [ ] `Analytics.vue` (مُرسَل/مُسلَّم/فاشل + تكلفة شهرية + أكثر الأحداث).
  - [ ] `Broadcast.vue` (شريحة + قناة + قالب → إرسال مُجزّأ).
  - [ ] `Templates.vue` (تحرير قوالب كل حدث/قناة ar/en + معاينة).
- [ ] اختبار `AdminNotificationTest` (gating + حفظ إعدادات + test-send + تحديث التوجيه).

---

## 🟦 P5 — الموافقات + الكوتا + fallback
- [ ] migration: أعمدة `notify_whatsapp_{bookings,reminders,marketing}` + `whatsapp_phone` للمريض/العميل.
- [ ] فرض fallback بين القنوات بالأولوية (واتساب فشل → SMS).
- [ ] فرض الكوتا الإجمالية + سقف التكلفة فعلياً في `NotificationService`.
- [ ] تحديث صفحة تفضيلات المريض (إضافة الواتساب).
- [ ] اختبار `NotificationFallbackTest` + `WhatsAppConsentTest`.

---

## 🟦 P6 — التكامل في كل نقطة لمس (Distribution) ⭐

### ملف المريض
- [ ] تبويب «التواصل» في `Admin/Patients/Show` (+ `Timeline`): سجلّ `notification_logs` للمريض.
- [ ] زر «إرسال رسالة» (قناة + قالب/نص) → `Notifier` (نفس الحوكمة).
- [ ] تعديل تفضيلات قنوات المريض من ملفه.
- [ ] عرض السجلّ في بوابة المريض (قراءة) ولوحة الطبيب/السكرتيرة (قراءة).

### تحويل المُرسِلين القدامى إلى `Notifier` (دون كسر)
- [ ] `ProcessBookingSmsJob` / `SmsNotificationService` → `Notifier::event('booking.*')`.
- [ ] أوامر التذكير (`patients:send-sms-reminders`, `bookings:send-reminders`) → `appointment.reminder.*`.
- [ ] `DentalFollowupService` / `DentalSmartNotificationService` → `dental.*`.
- [ ] أوامر `obgyn:reminders` / `pediatric:vaccination-reminders` → `{module}.*`.
- [ ] `FollowUpAutomationService` + `CommunicationService` + `ProcessFollowUpSequences` (CRM) → `lead.*`.
- [ ] `InvoiceOverdue` listener / loyalty events → `invoice.*` / `loyalty.*`.
- [ ] جعل `SmsService` محرّك SMS داخل الـHub (لا يُحذف).

### ترحيل البيانات
- [ ] migration ينقل مفاتيح `sms_on_*` و`sms_*_max_per_day` → مصفوفة التوجيه + الكوتا.
- [ ] توحيد `CommunicationTemplate`/`SmsTemplate` تحت `notification_templates` (مع إبقاء توافق).
- [ ] سجلّ تواصل العميل المحتمل في بطاقة الـLead (نفس `notification_logs`).

### اختبارات P6
- [ ] `PatientCommunicationsTabTest` (السجلّ يظهر + الإرسال اليدوي يعمل + يُسجَّل).
- [ ] `BookingNotificationIntegrationTest` (تأكيد حجز → حدث → سجلّ في ملف المريض).
- [ ] `CrmSequenceNotificationTest` (خطوة تسلسل → `lead.*` عبر الـHub).
- [ ] تأكيد عدم وجود نداء مباشر متبقٍّ: `grep SmsService::send|SendSmsJob` خارج محرّك SMS.

---

## 🟦 P7 — الإغلاق (Hardening + ship)
- [ ] أمر مجدول `notifications:retry-failed` + تنظيف السجلّ القديم.
- [ ] فحوصات `data:integrity-check`: رسائل عالقة (queued >1h)، فشل متراكم، حدث بلا قناة فعّالة.
- [ ] ربط تكلفة الإشعارات بتقارير المصروفات.
- [ ] `NotificationDemoSeeder` (قنوات + توجيه + سجلّ تجريبي لرؤية اللوحة حيّة).
- [ ] i18n + a11y نهائي للّوحة.
- [ ] تحديث `CLAUDE.md` + `NOTIFICATIONS_MODULE_PLAN.md` (حالة مكتملة).
- [ ] `composer check` أخضر + `npm run build` + رفع نهائي.

---

## كتالوج الأحداث (Event keys) — مرجع
```
booking.created | booking.confirmed | booking.rescheduled | booking.cancelled | booking.reminder
appointment.reminder.day_before | appointment.reminder.same_day
visit.completed
dental.followup | derma.recall
pediatric.vaccination_due | pediatric.vaccination_overdue
obgyn.anc_reminder | obgyn.edd_approaching | obgyn.pap_recall
lead.welcome | lead.nurture.step_n | lead.follow_up_due | lead.reactivation
invoice.created | invoice.overdue | payment.received
loyalty.earned | loyalty.redeemed
account.created | password.reset | otp.verify
```

## تعريف الإنجاز (Definition of Done)
- [ ] كل النظام يُرسِل عبر `Notifier` فقط (صفر نداء مباشر متبقٍّ).
- [ ] الأدمن يتحكّم: تشغيل/إيقاف قناة، توجيه كل حدث، الكوتا الإجمالية + التكلفة، الموافقات.
- [ ] كل رسالة مُسجّلة ومرئية في ملف صاحبها (مريض/عميل) + حالة التسليم.
- [ ] واتساب + SMS (Twilio/SMS Misr) + Email (SMTP من DB) + In‑App تعمل وتُختبَر.
- [ ] fallback + dedup + retry + سقوف فعّالة.
- [ ] اختبارات خضراء + بناء ناجح + مرفوع على GitHub لكل مرحلة.
