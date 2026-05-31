# مقترح تطوير ميديول الإشعارات على مستوى النظام بالكامل

> وثيقة استراتيجية/تصميمية. مرجع التنفيذ الحالي: `docs/NOTIFICATIONS_TODO.md` + `docs/NOTIFICATIONS_MODULE_PLAN.md`.
> الهدف: تحويل «مركز الإشعارات» من قناة إرسال موحّدة إلى **منصّة تواصل ذكية ثنائية الاتجاه** تخدم كل ميديولات النظام وكل المستخدمين (مرضى، عملاء محتملون، أطباء، سكرتارية، إدارة).

---

## 0. الوضع الحالي (ما تم إنجازه — P0→P7 + الترحيل)

| الطبقة | الحالة |
|--------|--------|
| المحرّك الموحّد (`Notifier` → `NotificationService`) | ✅ توجيه + موافقة + حصص + منع تكرار + قوالب + fallback + سجل |
| القنوات | ✅ WhatsApp (Cloud API + Bridge + Webhook) · SMS (SMS Misr + Twilio + Unifonic + Gateway) · Email (SMTP لكل عيادة) · In-App (سجل) |
| لوحة التحكم | ✅ ControlCenter (قنوات/توجيه/أحداث/قوالب/إعدادات) + سجل التسليم |
| ملف المريض | ✅ تبويب المراسلات (سجل + إرسال يدوي + موافقة) |
| الموافقة الذاتية | ✅ المريض يتحكم بـ بريد/SMS/واتساب من بوابته |
| التشغيل | ✅ `notifications:retry` + فحوص سلامة + قوالب افتراضية |
| الترحيل | ✅ الحجوزات/الزيارات/الاستدعاءات/المدفوعات/الفواتير/الولاء/الترحيب/الأسنان عبر `Notifier` |

**النواقص الجوهرية الحالية** (نقاط الانطلاق للتطوير):
1. قناة `in_app` تُسجّل فقط — **لا يوجد جرس/خلاصة** تعرضها للمريض أو الطاقم.
2. لا توجد **إشعارات تشغيلية للطاقم** عبر المركز (طبيب/سكرتير/إدارة).
3. لا توجد **مراسلة ثنائية الاتجاه** (الرسائل الواردة من واتساب تُسجَّل فقط بلا ردّ/توجيه).
4. تغطية الأحداث **جزئية** — كل ميديول طبّي لديه أحداث أكثر مما هو مفعّل.
5. لا يوجد **تحليلات/قياس** (نِسب التسليم، التكلفة، القمع، الأفضل قناةً).
6. لا توجد **حملات/جدولة/تقسيم** (segmentation + drip + scheduled send).
7. الموافقة بسيطة (boolean) — بلا **quiet hours / تكرار أقصى / STOP keyword**.

---

## 1. الرؤية والمبادئ الحاكمة

- **حدث واحد ← قرار مركزي**: كل أجزاء النظام تنادي `Notifier::event()` فقط؛ المنطق (قناة/لغة/توقيت/موافقة) مركزي.
- **القالب هو مصدر الحقيقة**: المحتوى يُدار من لوحة التحكم لا من الكود.
- **الموافقة مقدّسة**: لا تسويق بلا opt-in؛ احترام STOP وأوقات الهدوء.
- **التكلفة محكومة**: حصص + سقوف تكلفة + fallback ذكي (الأرخص/الأنجح أولاً).
- **كل شيء قابل للقياس والتتبّع**: سجل تسليم + إيصالات + تحليلات.
- **بلا تسريب صلاحيات**: لا يظهر أي تبويب/بيان إلا بصلاحية صحيحة.

---

## 2. محاور التطوير الكبرى (Pillars)

### المحور A — توسعة كتالوج الأحداث لكل ميديول
تفعيل الأحداث الناقصة وربطها بنقاط اللمس الفعلية. (انظر §4 للكتالوج الكامل لكل ميديول.)

### المحور B — مركز الإشعارات داخل النظام (In-App Feed + Bell)
- جدول/خلاصة موحّدة تقرأ `notification_logs` (channel=in_app) لكل مستلَم (Patient/User).
- **جرس** في البوابات الأربع (مريض/طبيب/سكرتير/إدارة) + عدّاد غير المقروء + وضع علامة مقروء.
- بثّ لحظي عبر **Reverb** (موجود في النظام) لتحديث الجرس فوراً.
- صفحة «كل الإشعارات» مع فلترة حسب النوع/الميديول.

### المحور C — مركز التفضيلات والموافقة الموحّد (Preference Center)
- صفحة موحّدة (مريض + إدارة) لكل القنوات × الفئات.
- **Quiet Hours** (لا إرسال غير العاجل بين 22:00–08:00 محلياً).
- **Frequency cap** لكل مستلَم (حد أقصى للرسائل التسويقية/أسبوع).
- **STOP / إلغاء الاشتراك**: كلمة «إلغاء/STOP» واردة → opt-out تلقائي + تأكيد.
- رابط إلغاء اشتراك في رسائل البريد (one-click unsubscribe).
- **Digest mode**: تجميع التذكيرات غير العاجلة في ملخص يومي/أسبوعي.

### المحور D — إشعارات الطاقم التشغيلية (Staff Notifications)
- توسيع المستلَم ليشمل `User` (طبيب/سكرتير/إدارة) عبر morph الموجود.
- أمثلة: حجز جديد مُسنَد للطبيب، طابور اليوم، قيمة مخبرية حرجة، رسالة مريض واردة، مخزون منخفض، إغلاق الرواتب، طلب إجازة.
- قنوات الطاقم: in_app (افتراضي) + واتساب/SMS للعاجل + بريد للملخصات.

### المحور E — المراسلة ثنائية الاتجاه (Two-Way / Conversations)
- الرسائل الواردة (واتساب webhook موجود) → **خيوط محادثة (threads)** مربوطة بالمريض/العميل.
- **Inbox موحّد** للسكرتارية: ردّ مباشر، تعيين، حالة (مفتوح/مغلق)، قوالب ردّ سريع.
- ربط بالـ CRM (تحويل محادثة إلى Lead/حجز) وبملف المريض.
- نافذة الـ 24 ساعة لواتساب: داخلها نص حرّ، خارجها قوالب معتمدة (template approval flow).

### المحور F — الجدولة والحملات والتقسيم (Campaigns + Segmentation + Drip)
- **Segmentation**: شرائح ديناميكية (مرضى السكري، لم يزر منذ 6 أشهر، عيد ميلاد هذا الشهر، حوامل في الثلث الثالث…).
- **Scheduled / Drip**: سلاسل تنقيط (welcome series، post-op care، ANC journey).
- **A/B testing** للقوالب + اختيار الفائز تلقائياً.
- **حملات موسمية** (رمضان/العيد) مع موافقة تسويقية + سقف تكرار.

### المحور G — التحليلات ولوحة القياس (Analytics)
- KPIs: معدّل التسليم/القراءة لكل قناة، التكلفة اليومية/الشهرية لكل قناة وميديول، نسبة الفشل وأسبابها، قمع الحجز (أُرسل تذكير → حضر).
- لوحة per-event وper-channel وper-module + اتجاهات زمنية + أعلى أسباب الفشل.
- تنبيه عند تجاوز سقف التكلفة أو ارتفاع نسبة الفشل أو توقف العامل (queue worker).

### المحور H — الموثوقية والامتثال (Reliability & Compliance)
- **DLR/إيصالات** لكل القنوات (واتساب موجود؛ إضافة SMS Misr DLR + Twilio status callback).
- **DLQ + retry مع backoff** (موجود retry؛ إضافة طابور رسائل ميتة وتنبيه).
- **Rate limiting** لكل مزوّد (تفادي الحظر) + توزيع زمني للحملات.
- **الامتثال**: سجل موافقة مؤرّخ (consent log)، STOP، الاحتفاظ بالبيانات (data retention)، تشفير الاعتمادات (موجود)، PDPL/خصوصية.
- **تعدّد العملاء (multi-clinic)**: إعدادات قناة/مزوّد/قالب مستقلة لكل عيادة (الأساس موجود في `notification_channels.config`).

### المحور I — الذكاء (Smart Layer)
- **Smart send-time**: إرسال التذكير في الوقت الأرجح للقراءة لكل مريض.
- **Channel preference learning**: تعلّم القناة الأنجح لكل مستلَم وإعادة ترتيب fallback.
- **توليد/ترجمة القوالب بالذكاء** + فحص النبرة + كشف المحتوى التسويقي خارج الموافقة.
- **تلخيص المحادثات الواردة** + اقتراح ردّ للسكرتارية.

---

## 3. خارطة الطريق المرحلية (Phases 8 → 14)

| المرحلة | العنوان | المحاور | الناتج | الحالة |
|--------|---------|---------|--------|--------|
| **P8** | In-App Feed + Bell | B | جرس + خلاصة (polling) في بوابة المريض + خدمة قابلة لإعادة الاستخدام | ✅ مكتمل (InAppFeedTest 7) |
| **P9** | Staff Notifications | D + A | أحداث طاقم + StaffNotifier + تنبيه الحجز الجديد + خلاصة 3 بوابات | ✅ مكتمل (StaffNotificationTest 5) |
| **P10** | Preference Center 2.0 | C + H | quiet hours + frequency cap + STOP + سجل موافقة | ✅ مكتمل (PreferenceCenterTest 6) |
| **P11** | Analytics Dashboard | G | لوحة تحليلات (تسليم/تكلفة/قمع/أعطال) | ✅ مكتمل (NotificationAnalyticsTest 3) |
| **P12** | Two-Way Inbox | E | محادثات + Inbox مشترك (admin/secretary) + ردّ | ✅ مكتمل (NotificationInboxTest 5) |
| **P13** | Campaigns + Segmentation | F | شرائح + حملات + جدولة + أمر إرسال مجدول | ✅ مكتمل (CampaignTest 6) |
| **P14** | Smart Layer (channel learning) | I | ترتيب القنوات حسب تفاعل المريض | ✅ مكتمل (SmartRoutingTest 4) |

> **الحالة الإجمالية:** كل مراحل خارطة الطريق (P8→P14) مكتملة. إضافةً لذلك أُغلقت ثماني فجوات تالية (كلٌّ مُختبَرة ومدفوعة):
>
> | # | الفجوة | الحالة |
> |---|--------|--------|
> | 1 | DLR لمزوّدي SMS (Twilio + SMS Misr) → تسليم/قراءة حقيقية | ✅ SmsDlrTest (6) |
> | 2 | توحيد وصول خلاصة الطاقم + تنبيه رسائل التواصل | ✅ StaffUnificationTest (4) |
> | 3 | تأجيل أوقات الهدوء + بنية الجدولة | ✅ (held→released) |
> | 4 | بريد HTML بالهوية + إلغاء اشتراك بنقرة واحدة | ✅ EmailChannelTest (4) |
> | 5 | SMS وارد (محادثات ثنائية + STOP عبر SMS) | ✅ SmsInboundTest (4) |
> | 6 | تنبيه سقف التكلفة الشهري للإدارة | ✅ CostCheckTest (4) |
> | 7 | سجلّ قوالب واتساب المعتمدة (Meta) | ✅ WhatsAppTemplateRegistryTest (5) |
> | 8 | التوقيت الذكي للرسائل التسويقية | ✅ SmartSendTimeTest (5) |
>
> **المتبقّي (المشروع التالي الكبير):** سلاسل التنقيط (drip sequences) واختبار A/B للحملات — كلٌّ منهما ميزة متعددة الجداول (تعريف السلسلة + تسجيل المستلَمين + تقدّم الخطوات) تُبنى كوحدة مستقلة. ودمج CommunicationService الكامل للـ CRM يبقى مستثنى عمداً.

> منطق الترتيب: P8/P9 يحققان قيمة فورية ظاهرة للمستخدمين بأقل مخاطرة (يبنيان فوق ما هو قائم). P10/P11 يرفعان النضج (امتثال + قياس). P12/P13/P14 توسّعات استراتيجية.

---

## 4. كتالوج الأحداث الكامل لكل ميديول

> ✅ مفعّل الآن · ➕ مقترح إضافته · 👤 مستلَم طاقم (لا مريض)

### الحجوزات والزيارات (core)
- ✅ booking.confirmed / rescheduled / cancelled
- ✅ appointment.reminder.day_before / same_day · ✅ visit.completed
- ➕ booking.no_show · booking.request_received · waitlist.slot_available
- ➕ appointment.reminder.hour_before · visit.checked_in
- ➕ prescription.ready · lab_results.ready · medical_certificate.ready

### التطبيب عن بُعد (telemedicine)
- ✅ (recovery email) — ➕ telemedicine: booked · payment_pending · room_link_ready · starting_soon (T-10m) · doctor_joined · missed · recording_ready

### الأسنان (dental)
- ✅ dental.followup · lab_ready · treatment_plan_approved
- ➕ treatment_plan.proposed · post_op_care (سلسلة بعد العملية) · installment.due · recall (دوري)

### الجلدية (derma)
- ✅ derma.recall — ➕ session_package.progress · post_procedure_care · product.ready

### الأطفال (pediatric)
- ✅ vaccination_due / overdue — ➕ vaccination.completed · growth_checkup.due · milestone.reminder

### النساء والتوليد (obgyn)
- ✅ anc_reminder · edd_approaching · pap_recall — ➕ ultrasound.ready · lab.ready · contraception.refill_due · postpartum.checkup

### الفواتير والمالية (finance)
- ✅ invoice.overdue · payment.received — ➕ invoice.issued · payment.failed · refund.issued · installment.reminder · wallet.credited · overdue.escalation (تصعيد متدرّج)

### الولاء (loyalty)
- ✅ loyalty.earned — ➕ loyalty.redeemed · tier_upgrade · points_expiring_soon · birthday_bonus

### CRM / العملاء المحتملون (leads)
- ✅ lead.welcome / follow_up_due / reactivation — ➕ lead.nurture_drip · quote.sent · deal.won / lost (دمج عبر P14)

### التأمين (insurance)
- ➕ pre_auth.approved / expiring · claim.status_changed

### المخزون (inventory) 👤
- ➕ inventory.low_stock · purchase_order.status · stock.expiry_approaching

### الموارد البشرية (HR) 👤
- ➕ payslip.ready · shift.reminder · leave.approved / rejected · attendance.anomaly · payroll.close_reminder

### الحساب والأمان (account)
- ✅ account.created · password.reset — ➕ login.new_device · portal.invite

### الرضا والمراجعات
- ➕ satisfaction.survey · review.request (NPS) — (أمر `satisfaction:send-surveys` موجود، يُرحَّل للمركز)

### المناسبات
- ➕ birthday.greeting · seasonal.campaign (رمضان/العيد)

---

## 5. مخطط البيانات الإضافي المقترح

```
notification_threads        (id, subject_type/id [Patient|Lead], channel, status, last_message_at, assigned_to)
notification_messages       (thread_id, direction in/out, body, provider_ref, sent_at, read_at)  // للمحادثات
notification_preferences    (recipient morph, quiet_hours_start/end, digest_mode, frequency_cap, unsubscribed_at)
notification_segments       (id, name, rules json, is_dynamic)
notification_campaigns      (id, name, segment_id, event_key/template, schedule, ab_variant, status, stats json)
notification_consents       (recipient morph, channel, category, opted_in, source, ip, at)  // سجل موافقة مؤرّخ
+ أعمدة على notification_logs: campaign_id, thread_id, opened_at, clicked_at, segment_id
+ أعمدة طاقم: notification_channels لا تتغيّر؛ تفضيلات الطاقم في users (notify_* أو جدول مستقل)
```

---

## 6. المخاطر والمقايضات

| القرار | المقايضة | التوصية |
|--------|----------|---------|
| In-App عبر `notification_logs` مقابل جدول `notifications` القديم | ازدواج مصادر | توحيد القراءة على `notification_logs` تدريجياً |
| إرسال متزامن مقابل queue | الكمون مقابل التعقيد | كله عبر queue (موجود)؛ العامل ضروري في الإنتاج |
| واتساب API مقابل click-to-chat (CRM) | تلقائي مقابل يدوي | إبقاء CRM يدوياً؛ المركز للتلقائي (P14 يوحّدهما بخيار) |
| Smart send-time | قيمة مقابل تعقيد | مرحلة لاحقة (P14) بعد توفّر بيانات القراءة |
| تعدّد العملاء | تعقيد الإعدادات | الأساس موجود؛ توسعته عند الحاجة الفعلية |

---

## 7. مؤشرات النجاح (KPIs)

- معدّل تسليم ≥ 95% لكل قناة · معدّل قراءة واتساب ≥ 70%.
- خفض **no-show** عبر التذكيرات (قياس قبل/بعد).
- زمن ردّ السكرتارية على الوارد < 15 دقيقة (بعد P12).
- التكلفة لكل رسالة مُسلّمة (cost per delivered) + الالتزام بالسقف الشهري.
- 0 شكاوى «رسائل بلا موافقة» (امتثال STOP/opt-in).

---

## 8. الأولويات المقترحة للبدء

**مكاسب سريعة (أسبوع–أسبوعان لكل منها):**
1. **P8 In-App Bell** — قيمة ظاهرة فوراً، يبني فوق Reverb و`notification_logs` الموجودين.
2. **P9 Staff Notifications** — تشغيلي مؤثّر (حجز جديد/قيمة حرجة/مخزون).
3. تفعيل الأحداث الناقصة عالية الأثر: `booking.no_show`, `appointment.reminder.hour_before`, `payment.failed`, `points_expiring_soon`.

**رهانات كبرى (قيمة استراتيجية):**
- **P12 Two-Way Inbox** (تجربة سكرتارية ومرضى نقلة نوعية).
- **P13 Campaigns/Segmentation** (تسويق وعودة المرضى).
- **P14 Smart Layer + دمج CRM**.

كل مرحلة تتبع نفس الانضباط: بناء + `pint` + اختبار باك/فرونت + `npm run build` + commit/push، migrations آمنة وidempotent، والاعتمادات مشفّرة، ولا تبويب بلا صلاحية.
