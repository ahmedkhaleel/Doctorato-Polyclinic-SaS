# MASTER PROMPT: Build Doctorato Marketing & Product Website

## PROJECT OVERVIEW

Build a **world-class, premium marketing website** for **"Doctorato"** — a comprehensive Medical Clinic & Healthcare Management Platform (SaaS). This website will serve as the primary sales tool to attract clinics, medical centers, and dental practices across the Middle East and internationally.

**Tech Stack:** Laravel 11 + Inertia.js v2 + Vue 3 (Composition API) + Tailwind CSS v4 + GSAP/Framer Motion animations
**Languages:** Bilingual — Arabic (RTL, primary) & English (LTR) with language switcher
**Multi-Currency:** Support multiple currencies with automatic switching based on visitor region
**Fonts:** Tajawal (Arabic) + Poppins (English)
**Color Palette:**
- Primary: `#1B4F72` (Deep Navy Blue — trust, authority)
- Secondary: `#C4A265` (Elegant Gold — luxury, premium)
- Accent: `#2E86C1` (Bright Blue — tech, innovation)
- Light BG: `#EBF5FB` (Soft Blue tint)
- Light Gold BG: `#FFF8E7` (Warm Gold tint)
- Dark: `#1C2833` (Text color)
- Gray: `#566573` (Secondary text)
- Success: `#27AE60`, Danger: `#E74C3C`, Warning: `#F39C12`

---

## WEBSITE ARCHITECTURE & PAGES

### PAGE 1: HOME (Landing Page)

#### Section 1.1: Hero Section (Full Viewport)
- **Background:** Dark navy gradient (`#1B4F72` → `#0D2B45`) with subtle animated particle/grid pattern
- **Content:**
  - Doctorato logo (centered, animated fade-in)
  - Main headline (Arabic): `"نظام دكتوراتو — مستقبل إدارة العيادات الطبية"`
  - Sub-headline: `"منصة متكاملة بـ 6 بوابات مستقلة و800+ خاصية لإدارة كل جانب من جوانب عيادتك"`
  - Two CTA buttons:
    - Gold button: `"اطلب عرضاً تجريبياً مجاناً"` → scrolls to demo request form
    - Outline white button: `"شاهد الخطط والأسعار"` → scrolls to pricing
  - Animated stats counter row (triggers on scroll):
    - `800+` API Endpoints | `346` Interactive Screens | `6` Independent Portals | `151` Database Tables | `80+` Granular Permissions
- **Animation:** Numbers count up from 0, headline types in, CTA buttons slide up with stagger

#### Section 1.2: Trust Bar (Logos/Badges)
- Scrolling marquee of trust signals:
  - "Built with Laravel 11" | "Vue.js 3" | "Tailwind CSS v4" | "HIPAA-Ready" | "Arabic & English" | "SSL Encrypted" | "99.9% Uptime"

#### Section 1.3: Problem → Solution Section
- **Left side:** Pain points with red X icons:
  - "هل تعاني من تشتت البيانات الطبية بين أنظمة متعددة؟"
  - "هل تفقد متابعة المرضى المحتملين بسبب عدم وجود CRM؟"
  - "هل تواجه صعوبة في إدارة مواعيد الأسنان وخطط العلاج؟"
  - "هل تضيع وقتك في إعداد الرواتب وتتبع الحضور يدوياً؟"
  - "هل تجد صعوبة في تتبع المخزون وطلبات الشراء؟"
  - "هل تفتقر لنظام فوترة متكامل مع التأمين الطبي؟"
- **Right side:** Solution with green checkmarks showing Doctorato dashboard mockup
- **Animation:** Left slides in from left, right slides in from right on scroll

#### Section 1.4: The 6 Portals Showcase
- **Design:** 6 interactive cards in a hexagonal or grid layout
- **Each card** has: Icon, Portal name (AR + EN), number of screens, brief description, "اكتشف المزيد" link
- **Cards:**

**1. بوابة المدير (Admin Portal) — 195 واجهة | 504 مسار**
"مركز القيادة الشامل لإدارة العيادة بالكامل. تحكم في كل شيء من لوحة تحكم واحدة: المرضى، الأطباء، المواعيد، المالية، الموارد البشرية، المخزون، التأمين، CRM، طب الأسنان، المحتوى، والتقارير."

**2. بوابة الطبيب (Doctor Portal) — 29 واجهة | 72 مسار**
"واجهة مصممة خصيصاً للأطباء. إدارة المرضى والمواعيد والزيارات وكتابة الوصفات ومخططات الأسنان وتتبع العمولات والحضور من شاشة واحدة."

**3. بوابة السكرتيرة (Secretary Portal) — 38 واجهة | 84 مسار**
"مركز عمليات الاستقبال. إدارة المرضى والحجوزات والفواتير والمدفوعات وطابور الانتظار ومتابعة العملاء المحتملين بكفاءة عالية."

**4. بوابة المريض (Patient Portal) — 27 واجهة | 35 مسار**
"بوابة خدمة ذاتية تمكّن المرضى من حجز المواعيد ومتابعة السجل الطبي والفواتير وسجلات الأسنان والتوقيع على نماذج الموافقة إلكترونياً."

**5. بوابة مدير الموقع (Webmaster Portal) — 39 واجهة | 81 مسار**
"إدارة كاملة لموقعك الإلكتروني: المقالات، الخدمات، المعرض، الشهادات، الأسئلة الشائعة، SEO، وأدوات التتبع والتحليلات."

**6. الموقع الإلكتروني العام (Public Website) — 14 واجهة | 26 مسار**
"موقع إلكتروني جاهز للعمل بتصميم فاخر مع نظام حجز إلكتروني من 5 خطوات وصفحات خدمات ومدونة ومعرض صور."

- **Animation:** Cards flip or elevate on hover with gold border glow

#### Section 1.5: Key Modules Deep Dive (Tabbed/Accordion)
- Interactive tabbed section — each tab opens a detailed module view with mockup screenshot, feature list, and description

**Tab 1: إدارة المرضى (Patient Management)**
- ملف طبي رقمي شامل بأكثر من 50 حقلاً لكل مريض
- رقم ملف فريد تلقائي، بيانات شخصية كاملة، تاريخ طبي، صورة شخصية
- مصادر الإحالة (حضور مباشر، وسائل التواصل، جوجل، صديق، طبيب، إعلان)
- نظام مستندات متقدم يدعم 14 نوعاً (هوية، جواز، تأمين، تقارير، أشعة، موافقات)
- محفظة إلكترونية (إيداع، سحب، استرداد، دفع، تعديل) مع سجل كامل
- استبيان رضا المرضى مع 6 معايير تقييم + مقياس NPS (0-10)
- حقول طبية متخصصة للأسنان (قلق الأسنان، حساسية اللاتكس، HIV، التهاب الكبد)
- الجدول الزمني (Timeline) لتتبع كل حدث في ملف المريض
- ربط اختياري بحساب مستخدم لتمكين المريض من الدخول لبوابته

**Tab 2: الحجوزات والمواعيد (Bookings & Appointments)**
- نظام حجز متعدد الخدمات والجلسات مع 3 جداول مترابطة
- معالج حجز إلكتروني من 5 خطوات (خدمة → طبيب → تاريخ → بيانات → تأكيد)
- 8 حالات للموعد: مجدول، مؤكد، تسجيل وصول، جارٍ، مكتمل، ملغى، لم يحضر
- تقويم تفاعلي مع ألوان مميزة لكل حالة
- نظام تذكيرات تلقائي عبر البريد الإلكتروني مع تتبع عدد التذكيرات
- دعم 20+ رمز دولة (السعودية، الإمارات، الكويت، مصر، وغيرها)
- ربط بأكواد الخصم والعروض الترويجية
- طباعة إيصال الحجز وإيصال الدفع بتصميم احترافي

**Tab 3: الإدارة المالية (Financial Management)**
- نظام فواتير ببنود متعددة ووصف ثنائي اللغة
- 4 حالات فاتورة: مدفوعة، جزئية، غير مدفوعة، ملغاة
- طرق دفع متعددة: نقد، بطاقة ائتمان، تحويل بنكي، محفظة إلكترونية
- إشعارات دائنة (Credit Notes) مع سير عمل 5 مراحل وطرق استرداد متعددة
- أكواد خصم ذكية: نسبة أو مبلغ، حد استخدام، فترة صلاحية، خدمات محددة
- عروض ترويجية بنوافذ منبثقة على الموقع بتصميم ثنائي اللغة
- إدارة المصروفات مع تصنيف حسب الفئة وتحليل هيكل التكاليف
- نظام السلف والعقوبات المالية للموظفين
- تقارير مالية: إجمالي الإيرادات، المصروفات، صافي الدخل، أرصدة غير مدفوعة

**Tab 4: وحدة طب الأسنان (Dental Module) ⭐**
- مخطط أسنان تفاعلي SVG بترقيم FDI الدولي (32 سن)
- 9 حالات للسن: سليم، مسوس، محشو، مفقود، تاج، جسر، زرعة، معالج جذور، مقلوع
- تتبع 5 أسطح لكل سن: وسطي، بعيد، طاحن، خدي، لساني
- خطط علاج بـ 4 أولويات (عاجل، عالٍ، عادي، منخفض) و5 حالات مع شريط تقدم
- قوالب خطط علاج جاهزة لتسريع الإنشاء
- مخطط اللثة (Periodontal): 6 قياسات لكل سن (عمق الجيب، الانحسار، النزيف، البلاك، الحركة، تشعب الجذور)
- 6 أنواع أشعة: بانوراما، حول ذروية، جناح العض، سيفالومترية، CBCT، إطباقية
- طلبات المعمل: 8 أنواع قطع (تاج، جسر، طقم، مثبت، تقويم شفاف، قشرة، دعامة زرعة، واقي ليلي)
- اختيار درجة اللون (A1-D4) والمادة (زركونيا، بورسلين، إيماكس، معدن، كومبوزيت)
- سير عمل المعمل: مطلوب → قيد الإنتاج → جاهز → تسليم → تعديل → مكتمل
- لوحة ربحية طلبات المعمل (تكلفة المعمل vs سعر المريض)
- نظام موافقة رقمية مع توقيع إلكتروني وتسجيل IP والمتصفح واحتفاظ بنسخة النص
- مقارنات قبل/بعد العلاج قابلة للمشاركة مع المريض
- قواعد متابعة ذكية (تذكير تلقائي بعد فترة محددة حسب نوع العلاج)
- قوالب وصفات أسنان جاهزة (مسكنات، مضادات حيوية، غسول فم)
- 3 قوالب PDF متخصصة: مخطط الأسنان، خطة العلاج مع شريط تقدم، نموذج الموافقة

**Tab 5: إدارة علاقات العملاء CRM**
- أنبوب مبيعات بـ 10 مراحل (جديد → محوّل/خسارة) مع عرض Kanban تفاعلي
- ملف عميل محتمل بأكثر من 30 حقلاً مع تتبع UTM كامل
- نظام أولويات 3 مستويات: ساخن 🔥، دافئ 🟡، بارد 🔵
- تسجيل نقاط تلقائي (Lead Scoring) بقواعد قابلة للتخصيص
- تسلسلات متابعة آلية (Sequences) مع خطوات بريد ومهام وتغيير حالة
- حملات تسويقية مع تتبع أداء ونسبة تحويل لكل حملة
- قواعد تعيين تلقائي (Round Robin أو حسب التخصص)
- قوالب بريد إلكتروني بمتغيرات ديناميكية وتصميم RTL عربي
- استيراد جماعي من CSV/Excel ودمج العملاء المكررين
- تقارير: أنبوب التحويل، تحليل المصادر، أداء الموظفين، أسباب الخسارة

**Tab 6: الموارد البشرية HR**
- ملف موظف شامل: بيانات شخصية، عقد، هيكل راتب (أساسي + سكن + نقل + بدلات)
- 4 أنواع عقود: دوام كامل، جزئي، عقد، مع تواريخ وحالات
- تسجيل حضور بتحديد GPS مع 4 حالات (حاضر، غائب، متأخر، إجازة)
- مسيرات رواتب بـ 12+ حقل مالي مع سير موافقة (مسودة → موافق → مدفوع)
- 7 أنواع إيرادات: أساسي، سكن، نقل، بدلات، ساعات إضافية، مكافآت، عمولات
- 6 أنواع خصومات: تأمين، ضريبة، غياب، سلفة، جزاء، أخرى
- نظام إجازات مع سير عمل موافقة
- إدارة الورديات والأقسام التنظيمية
- حساب صافي الراتب تلقائياً

**Tab 7: التأمين الطبي (Insurance)**
- إدارة شركات التأمين بأسماء ثنائية اللغة وشعارات
- خطط تأمين بـ 6 فئات (VIP, A, B, C, D, E) مع نسبة تغطية وحدود مالية
- 6 أعلام تغطية مستقلة: أسنان، جلدية، تجميل، مختبر، أشعة، أدوية
- تأمين المرضى: رقم العضوية، البوليصة، صور البطاقة، الحد السنوي، التحقق
- مطالبات تأمينية بسير عمل 8 مراحل (مسودة → مقدمة → مراجعة → موافقة → دفع)
- تقارير التأمين: المطالبات المعلقة، المبالغ المغطاة، حصة المرضى

**Tab 8: المخزون (Inventory)**
- إدارة مواد ومستلزمات بـ SKU فريد مع تصنيف ووحدة قياس
- تنبيهات مخزون منخفض مع شريط مرئي ملون لنسبة المخزون
- نظام إعادة طلب تلقائي عند وصول المخزون لنقطة محددة
- تنبيهات انتهاء الصلاحية (30 يوم تحذير)
- إدارة موردين بتقييم ومدة توريد وشروط دفع
- أوامر شراء بسير عمل 7 مراحل مع استلام جزئي/كامل
- سجل حركة مخزون تلقائي (استلام، صرف، تعديل، إرجاع)
- إدارة أدوية مخصصة مع بحث سريع للوصفات

**Tab 9: لوحات التحكم الذكية (Smart Dashboards)**
- عدّادات متحركة (Animated Counters) لجميع مؤشرات الأداء
- رسوم بيانية تفاعلية: خطية (اتجاه الإيرادات)، دائرية (أفضل الخدمات)، أعمدة (أداء الأطباء)
- طابور انتظار فوري بألوان مرمّزة (أخضر < 15 دقيقة، أصفر < 30، أحمر > 30)
- تنبيهات ذكية: مخزون منخفض، متابعات CRM مستحقة، مواعيد مهمة
- لوحة مخصصة لكل بوابة (مدير، طبيب، سكرتيرة، مريض)

**Tab 10: ميزات إضافية (Extra Features)**
- شريط أوامر سريع (Cmd+K / Ctrl+K) للبحث والتنقل من أي صفحة
- دردشة داخلية فورية بين جميع مستخدمي النظام مع مرفقات
- نظام إشعارات فورية مع عدّاد الرسائل غير المقروءة
- 8 قوالب PDF احترافية (وصفة، فاتورة، مخطط أسنان، خطة علاج، موافقة، إيصالات، مسير راتب)
- تكامل تتبع كامل: Google Tag Manager, GA4, Facebook Pixel, TikTok, Snapchat, Twitter
- إدارة SEO متقدمة لكل صفحة وخدمة ومقالة بلغتين
- سجل تدقيق شامل + سجل وصول طبي حساس
- سلة محذوفات مع استعادة (Soft Delete)
- نظام RBAC بأكثر من 80 صلاحية دقيقة موزعة على 8 فئات
- وحدات قابلة للتفعيل/التعطيل حسب حاجة العيادة

#### Section 1.6: Interactive Dashboard Preview
- **Design:** Large browser mockup frame showing animated Doctorato admin dashboard
- **Features to animate:**
  - KPI cards counting up (Revenue, Expenses, Net Income, Unpaid Balance)
  - Chart lines drawing themselves
  - Queue items appearing one by one
  - Notifications popping in
- **Text overlay:** `"لوحة تحكم ذكية تعرض كل شيء في لحظة واحدة"`

#### Section 1.7: Dental Module Spotlight
- **Design:** Full-width section with dark background
- **Content:** Interactive dental chart SVG mockup showing:
  - Teeth grid with color-coded conditions
  - Side panel with treatment plan
  - Lab order status indicators
- **Headline:** `"وحدة طب الأسنان الأكثر تكاملاً في المنطقة"`
- **Stats:** `"32 سن × 5 أسطح × 9 حالات × 6 أنواع أشعة × 8 أنواع قطع معملية"`

#### Section 1.8: Technology Stack Section
- **Design:** Grid of tech logos with descriptions
- Cards for: Laravel 11, Vue.js 3, Inertia.js v2, Tailwind CSS v4, MySQL, Tiptap v3, DomPDF, Chart.js, Lucide Icons, Vite
- Each card: Logo + Name + One-line description of why it's used

#### Section 1.9: Testimonials / Social Proof
- Carousel of testimonial cards (placeholder for now — structure must be ready)
- Rating stars, client name, clinic name, review text
- Video testimonial placeholder

#### Section 1.10: Pricing Plans Section ⭐
- **Design:** 3-4 pricing cards with the popular one highlighted in gold
- **Plans:**

**Plan 1: الأساسي (Basic) — XX ر.س/شهرياً (prices shown in visitor's selected currency)**
- بوابة المدير + السكرتيرة + الموقع العام
- إدارة المرضى (حتى 500 مريض)
- الحجوزات والمواعيد
- الفواتير والمدفوعات
- لوحة تحكم أساسية
- دعم فني عبر البريد
- لغة واحدة

**Plan 2: الاحترافي (Professional) — XX ر.س/شهرياً** ⭐ الأكثر شعبية
- كل ميزات الأساسي +
- بوابة الطبيب + بوابة المريض
- مرضى غير محدودين
- وحدة CRM كاملة
- نظام المحفظة الإلكترونية
- أكواد الخصم والعروض
- إشعارات البريد الإلكتروني
- الدردشة الداخلية
- 5 مستخدمين
- دعم فني عبر الهاتف والبريد
- ثنائي اللغة (عربي + إنجليزي)

**Plan 3: المتقدم (Enterprise) — XX ر.س/شهرياً**
- كل ميزات الاحترافي +
- بوابة مدير الموقع
- وحدة طب الأسنان الكاملة (مخططات، خطط علاج، معمل، أشعة، موافقات رقمية)
- وحدة الموارد البشرية (حضور GPS، رواتب، إجازات، ورديات)
- وحدة التأمين الطبي (شركات، خطط، مطالبات 8 مراحل)
- وحدة المخزون (موردين، أوامر شراء، إعادة طلب تلقائي)
- مستخدمين غير محدودين
- قوالب PDF كاملة (8 قوالب)
- تكامل تتبع (GTM, GA4, Facebook, TikTok, Snapchat, Twitter)
- سجل تدقيق شامل + RBAC كامل (80+ صلاحية)
- دعم فني ذهبي 24/7

**Plan 4: مخصص (Custom)**
- حلول مخصصة للمراكز الطبية الكبيرة
- تركيب على سيرفر خاص (On-Premise)
- تطوير ميزات حسب الطلب
- تدريب فريق العمل
- مدير حساب مخصص
- اتفاقية مستوى خدمة SLA

- **Toggle:** شهري / سنوي (خصم 20% على السنوي)
- **Below cards:** `"جميع الخطط تشمل: SSL، نسخ احتياطي يومي، تحديثات مجانية، 99.9% وقت تشغيل"`

#### Section 1.11: Demo Request Form ⭐
- **Design:** Split section — left: form, right: benefits of trying demo
- **Form fields:**
  - اسم العيادة / المركز الطبي (مطلوب)
  - الاسم الكامل (مطلوب)
  - البريد الإلكتروني (مطلوب)
  - رقم الهاتف مع رمز الدولة (مطلوب)
  - الدولة (قائمة منسدلة)
  - عدد الأطباء (1-5, 6-15, 16-50, 50+)
  - التخصص (جلدية، أسنان، عام، متعدد التخصصات، أخرى)
  - الوحدات المهتم بها (checkboxes): طب الأسنان، CRM، الموارد البشرية، المخزون، التأمين
  - كيف عرفت عنا؟ (جوجل، وسائل التواصل، إحالة، معرض، أخرى)
  - ملاحظات إضافية (اختياري)
  - زر: `"اطلب العرض التجريبي المجاني"`
- **Right side benefits:**
  - ✅ عرض تجريبي مجاني لمدة 14 يوماً
  - ✅ بدون بطاقة ائتمان
  - ✅ إعداد كامل خلال 24 ساعة
  - ✅ دعم فني مجاني خلال الفترة التجريبية
  - ✅ بياناتك آمنة 100%
- **After submit:** Email notification to admin (info@markeza-group.com) + confirmation to user

#### Section 1.12: FAQ Section
- Accordion-style frequently asked questions:
  - ما هو نظام دكتوراتو؟
  - هل يدعم النظام اللغة العربية بالكامل؟
  - هل يمكنني تفعيل وحدة طب الأسنان فقط؟
  - هل يوفر النظام تطبيق جوال؟
  - كيف يتم تخزين البيانات الطبية؟ هل هي آمنة؟
  - هل يمكنني الترقية من خطة لأخرى؟
  - ما هي مدة العرض التجريبي المجاني؟
  - هل يدعم النظام التكامل مع أنظمة أخرى؟
  - هل يمكن تركيب النظام على سيرفر خاص؟
  - ما هي طرق الدفع المقبولة؟
  - ما هي العملات المدعومة؟ / What currencies are supported?
  - هل يمكنني الدفع بعملتي المحلية؟ / Can I pay in my local currency?

#### Section 1.13: Footer
- **Layout:** 4 columns
  - Column 1: Logo + brief description + social media icons
  - Column 2: روابط سريعة (الرئيسية، الخطط، طلب عرض، المدونة، تواصل معنا)
  - Column 3: الوحدات (طب الأسنان، CRM، الموارد البشرية، المخزون، التأمين، المالية)
  - Column 4: تواصل معنا (بريد، هاتف، عنوان، ساعات العمل)
- **Bottom bar:** حقوق النشر + سياسة الخصوصية + الشروط والأحكام
- **Newsletter signup:** "اشترك في نشرتنا البريدية للحصول على آخر التحديثات"

---

### PAGE 2: FEATURES (الخصائص)
- Dedicated page with ALL features organized by module
- Each module gets a full section with:
  - Header with icon and module name
  - Feature grid (3 columns) with icon + title + description for each feature
  - Screenshot/mockup placeholder
  - CTA to demo

### PAGE 3: PRICING (الأسعار)
- Full pricing page with comparison table
- Feature-by-feature comparison across all plans
- FAQ specific to pricing
- Custom plan contact form

### PAGE 4: ABOUT (من نحن)
- Company story (Markeza Group)
- Mission & Vision
- Team section (placeholder)
- Technology philosophy
- Why Doctorato

### PAGE 5: CONTACT (تواصل معنا)
- Contact form (name, email, phone, subject, message)
- Company information (address, phone, email)
- Google Maps embed placeholder
- Working hours
- Social media links

### PAGE 6: BLOG (المدونة)
- Blog listing with categories
- Individual blog post page
- Sidebar with recent posts, categories, tags

### PAGE 7: DEMO (طلب عرض تجريبي)
- Standalone demo request page with more details
- Video walkthrough placeholder
- Step-by-step process: طلب → إعداد → تدريب → انطلاق

---

## GLOBAL COMPONENTS

### Navbar
- Sticky, transparent on hero → solid on scroll
- Logo (left for EN, right for AR)
- Links: الرئيسية، الخصائص، الأسعار، من نحن، المدونة، تواصل
- Language switcher (AR/EN)
- Currency switcher dropdown (16 currencies with flags: SAR, AED, KWD, BHD, QAR, OMR, EGP, JOD, IQD, LBP, LYD, MAD, TND, USD, EUR, GBP)
- CTA button: "اطلب عرضاً تجريبياً" (gold)

### Floating Elements
- WhatsApp floating button (bottom-right for EN, bottom-left for AR)
- Scroll-to-top button
- Cookie consent banner

### Animations (GSAP / Intersection Observer)
- Scroll-triggered animations on all sections
- Number counters animate when visible
- Cards stagger-animate on scroll
- Parallax on hero background
- Smooth section transitions
- Typing effect on hero headline
- Hover effects on all interactive elements (cards, buttons, links)

---

## TECHNICAL REQUIREMENTS

### Bilingual System (Arabic + English) — كامل ثنائي اللغة
- **Full RTL/LTR Support:** The entire website must work flawlessly in both Arabic (RTL) and English (LTR)
- **Language Switcher:** Visible toggle (AR ↔ EN) in navbar, persists via session/cookie
- **Direction Switching:** `dir="rtl"` for Arabic, `dir="ltr"` for English — applied to `<html>` element
- **Font Switching:** Tajawal for Arabic, Poppins for English — switch dynamically based on locale
- **All Content Bilingual:** Every text element on the website (headings, descriptions, buttons, forms, labels, placeholders, error messages, tooltips, meta tags, footer) must have both Arabic and English versions
- **Translation System:** Vue-i18n with 200+ keys per language (ar.json / en.json)
- **Form Validation Messages:** Bilingual error messages
- **Email Templates:** Both Arabic and English versions for confirmation emails
- **SEO per Language:** Separate meta titles, descriptions, Open Graph tags, and `hreflang` tags for each language
- **Blog Content:** Each blog post stored in both Arabic and English
- **URL Structure:** `/ar/...` and `/en/...` prefix routing OR session-based locale switching
- **Date/Time Formatting:** Hijri date support option for Arabic, Gregorian for English

### Multi-Currency System — نظام متعدد العملات
- **Supported Currencies (minimum 8):**
  - 🇸🇦 SAR — ريال سعودي (Saudi Riyal) — **Default**
  - 🇦🇪 AED — درهم إماراتي (UAE Dirham)
  - 🇰🇼 KWD — دينار كويتي (Kuwaiti Dinar)
  - 🇧🇭 BHD — دينار بحريني (Bahraini Dinar)
  - 🇶🇦 QAR — ريال قطري (Qatari Riyal)
  - 🇴🇲 OMR — ريال عماني (Omani Rial)
  - 🇪🇬 EGP — جنيه مصري (Egyptian Pound)
  - 🇺🇸 USD — دولار أمريكي (US Dollar)
  - 🇪🇺 EUR — يورو (Euro)
  - 🇬🇧 GBP — جنيه إسترليني (British Pound)
  - 🇯🇴 JOD — دينار أردني (Jordanian Dinar)
  - 🇮🇶 IQD — دينار عراقي (Iraqi Dinar)
  - 🇱🇧 LBP — ليرة لبنانية (Lebanese Pound)
  - 🇱🇾 LYD — دينار ليبي (Libyan Dinar)
  - 🇲🇦 MAD — درهم مغربي (Moroccan Dirham)
  - 🇹🇳 TND — دينار تونسي (Tunisian Dinar)
- **Currency Switcher:** Dropdown/select in navbar or footer, persists via session/cookie
- **Auto-Detection (Optional):** Detect visitor country via IP geolocation and suggest appropriate currency
- **Pricing Display:**
  - All pricing plans dynamically show prices in the selected currency
  - Currency symbol displayed correctly (before/after number based on locale)
  - Arabic: الأرقام مع رمز العملة (e.g., `٢٩٩ ر.س` or `299 ر.س`)
  - English: Standard format (e.g., `SAR 299` or `$79`)
- **Exchange Rates:**
  - Store base prices in SAR in database
  - `currency_rates` table with rates relative to SAR
  - Admin can update exchange rates manually OR fetch via API (e.g., exchangerate-api.com)
  - Display converted price with a note: "الأسعار التقريبية بعملتك المحلية" / "Approximate prices in your local currency"
- **Database:**
  - `currencies` table: code, name_ar, name_en, symbol, symbol_position (before/after), decimal_places, rate_to_sar, is_active, display_order
  - `pricing_plans.base_price_monthly` and `pricing_plans.base_price_yearly` stored in SAR
  - Conversion calculated on-the-fly or cached
- **Formatting Helper:**
  - `formatCurrency(amount, currencyCode, locale)` — Vue composable/helper
  - Handles decimal places (KWD/BHD = 3 decimals, most = 2 decimals, IQD = 0 decimals)
  - Proper thousands separator based on locale (Arabic: ٬ or , | English: ,)

### Backend (Laravel 11)
- Multi-language system with JSON translation files
- Contact form with email notification (to info@markeza-group.com)
- Demo request form with database storage + email notification
- Newsletter subscription
- Blog with CMS (posts, categories, tags)
- SEO-friendly URLs with meta tags per page
- Sitemap.xml and robots.txt
- Admin panel to manage:
  - Demo requests
  - Contact messages
  - Blog posts
  - Newsletter subscribers
  - Pricing plans (dynamic)
  - Testimonials
  - FAQ entries

### Frontend (Vue 3 + Tailwind v4)
- Fully responsive (mobile-first)
- RTL/LTR dynamic switching
- Dark mode support (optional)
- Lazy loading for images
- Optimized for Core Web Vitals (LCP, FID, CLS)
- Structured data (JSON-LD) for SEO

### Database Tables Needed
- demo_requests
- contact_messages
- newsletter_subscribers
- blog_posts, blog_categories, blog_tags
- pricing_plans, plan_features
- testimonials
- faqs
- currencies (code, name_ar, name_en, symbol, symbol_position, decimal_places, rate_to_sar, is_active, display_order)
- settings (site-wide config)

---

## DESIGN GUIDELINES

- **Premium luxury aesthetic** — think Apple + Stripe + Linear design quality
- **Generous whitespace** — don't crowd sections
- **Consistent spacing** — use Tailwind's spacing scale consistently
- **Typography hierarchy** — clear visual hierarchy with size and weight
- **Gold accents sparingly** — use gold for CTAs, borders, and highlights only
- **Dark sections** for contrast (hero, dental spotlight, CTA sections)
- **Light sections** for content (features, pricing, FAQ)
- **Glassmorphism** effects on cards (subtle backdrop-blur)
- **Micro-interactions** on every clickable element
- **Professional mockups** — use browser frames to showcase the system

---

## SEO & MARKETING

- Meta title: "دكتوراتو — نظام إدارة العيادات الطبية المتكامل | Doctorato"
- Meta description: "منصة شاملة لإدارة العيادات والمراكز الطبية بـ 6 بوابات مستقلة و800+ خاصية. طب أسنان، CRM، موارد بشرية، مخزون، تأمين. جرّب مجاناً."
- Open Graph tags for social sharing
- Twitter Cards
- Schema.org markup (Organization, Product, FAQ, Pricing)
- Canonical URLs
- Hreflang tags (ar, en)

---

## CONTACT INFORMATION
- **Company:** Markeza Group (مجموعة ماركيزا للتقنية)
- **Email:** info@markeza-group.com
- **Product:** Doctorato — دكتوراتو
- **Version:** 2.0 — April 2026
