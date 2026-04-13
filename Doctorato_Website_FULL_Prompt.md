# DOCTORATO WEBSITE — FULL EXECUTION PROMPT FOR CLAUDE CODE

> **IMPORTANT:** This is a step-by-step execution prompt. Follow every instruction precisely. Do NOT skip any step. Do NOT abbreviate. Do NOT assume — implement exactly as described.

---

## PHASE 0: PROJECT INITIALIZATION

### Step 0.1: Create Laravel Project
```bash
composer create-project laravel/laravel doctorato-website
cd doctorato-website
```

### Step 0.2: Install All Dependencies
```bash
# Backend
composer require inertiajs/inertia-laravel
composer require tightenco/ziggy

# Frontend
npm install vue@3 @inertiajs/vue3 @vitejs/plugin-vue
npm install tailwindcss@4 @tailwindcss/vite
npm install gsap
npm install @vueuse/core
npm install lucide-vue-next
npm install vue-i18n@9
npm install @headlessui/vue
npm install swiper
npm install vue3-smooth-scroll
```

### Step 0.3: Configure vite.config.js
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        tailwindcss(),
    ],
    resolve: { alias: { '@': '/resources/js' } },
});
```

### Step 0.4: Configure resources/css/app.css
```css
@import "tailwindcss";

/* ===== CUSTOM THEME ===== */
@theme {
    --color-primary: #1B4F72;
    --color-primary-light: #2874A6;
    --color-primary-dark: #0D2B45;
    --color-secondary: #C4A265;
    --color-secondary-light: #D4B87A;
    --color-secondary-dark: #A88B4A;
    --color-accent: #2E86C1;
    --color-accent-light: #5DADE2;
    --color-dark: #1C2833;
    --color-gray: #566573;
    --color-gray-light: #AEB6BF;
    --color-light-blue: #EBF5FB;
    --color-light-gold: #FFF8E7;
    --color-success: #27AE60;
    --color-danger: #E74C3C;
    --color-warning: #F39C12;

    --font-family-ar: 'Tajawal', sans-serif;
    --font-family-en: 'Poppins', sans-serif;
}

/* ===== GOOGLE FONTS ===== */
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap');

/* ===== RTL SUPPORT ===== */
[dir="rtl"] body { font-family: var(--font-family-ar); }
[dir="ltr"] body { font-family: var(--font-family-en); }

/* ===== GLOBAL STYLES ===== */
html { scroll-behavior: smooth; }

/* Scrollbar */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #f1f1f1; }
::-webkit-scrollbar-thumb { background: var(--color-primary); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: var(--color-secondary); }

/* Selection */
::selection { background: var(--color-secondary); color: white; }
```

### Step 0.5: Configure resources/js/app.js
```javascript
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ar from './locales/ar.json';
import en from './locales/en.json';

import '../css/app.css';

const i18n = createI18n({
    legacy: false,
    locale: document.documentElement.lang || 'ar',
    fallbackLocale: 'en',
    messages: { ar, en },
});

createInertiaApp({
    title: (title) => title ? `${title} — Doctorato` : 'Doctorato — نظام إدارة العيادات الطبية المتكامل',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: { color: '#C4A265', showSpinner: true },
});
```

### Step 0.6: Inertia Middleware
Run `php artisan inertia:middleware` then add to `app/Http/Kernel.php` or `bootstrap/app.php`.

---

## PHASE 1: DATABASE SCHEMA

### Step 1.1: Create All Migrations

**Migration 1: demo_requests**
```bash
php artisan make:migration create_demo_requests_table
```
```php
Schema::create('demo_requests', function (Blueprint $table) {
    $table->id();
    $table->string('clinic_name');                    // اسم العيادة
    $table->string('full_name');                      // الاسم الكامل
    $table->string('email');                          // البريد الإلكتروني
    $table->string('phone');                          // رقم الهاتف
    $table->string('country_code', 10)->default('+966'); // رمز الدولة
    $table->string('country')->nullable();            // الدولة
    $table->string('doctors_count')->nullable();      // عدد الأطباء: 1-5, 6-15, 16-50, 50+
    $table->string('specialty')->nullable();          // التخصص: derma, dental, general, multi, other
    $table->json('interested_modules')->nullable();   // الوحدات المهتم بها: ["dental","crm","hr","inventory","insurance"]
    $table->string('referral_source')->nullable();    // مصدر المعرفة: google, social, referral, exhibition, other
    $table->text('notes')->nullable();                // ملاحظات
    $table->enum('status', ['new','contacted','demo_scheduled','demo_done','converted','lost'])->default('new');
    $table->text('admin_notes')->nullable();          // ملاحظات المدير
    $table->timestamp('contacted_at')->nullable();
    $table->timestamps();
});
```

**Migration 2: contact_messages**
```bash
php artisan make:migration create_contact_messages_table
```
```php
Schema::create('contact_messages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email');
    $table->string('phone')->nullable();
    $table->string('subject');
    $table->text('message');
    $table->boolean('is_read')->default(false);
    $table->text('admin_reply')->nullable();
    $table->timestamp('replied_at')->nullable();
    $table->timestamps();
});
```

**Migration 3: newsletter_subscribers**
```bash
php artisan make:migration create_newsletter_subscribers_table
```
```php
Schema::create('newsletter_subscribers', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->boolean('is_active')->default(true);
    $table->string('locale', 5)->default('ar');
    $table->timestamps();
});
```

**Migration 4: blog_categories**
```bash
php artisan make:migration create_blog_categories_table
```
```php
Schema::create('blog_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('slug')->unique();
    $table->integer('display_order')->default(0);
    $table->timestamps();
});
```

**Migration 5: blog_posts**
```bash
php artisan make:migration create_blog_posts_table
```
```php
Schema::create('blog_posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
    $table->string('title_ar');
    $table->string('title_en');
    $table->string('slug')->unique();
    $table->text('excerpt_ar')->nullable();
    $table->text('excerpt_en')->nullable();
    $table->longText('content_ar');
    $table->longText('content_en');
    $table->string('featured_image')->nullable();
    $table->string('seo_title_ar')->nullable();
    $table->string('seo_title_en')->nullable();
    $table->text('seo_desc_ar')->nullable();
    $table->text('seo_desc_en')->nullable();
    $table->enum('status', ['draft','published','scheduled'])->default('draft');
    $table->boolean('is_featured')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->unsignedInteger('views_count')->default(0);
    $table->timestamps();
});
```

**Migration 6: pricing_plans**
```bash
php artisan make:migration create_pricing_plans_table
```
```php
Schema::create('pricing_plans', function (Blueprint $table) {
    $table->id();
    $table->string('name_ar');
    $table->string('name_en');
    $table->string('slug')->unique();
    $table->text('description_ar')->nullable();
    $table->text('description_en')->nullable();
    $table->decimal('monthly_price', 10, 2);
    $table->decimal('yearly_price', 10, 2);
    $table->string('currency', 10)->default('SAR');
    $table->boolean('is_popular')->default(false);
    $table->boolean('is_custom')->default(false);        // for "Contact Us" plan
    $table->json('features_ar');                          // ["إدارة المرضى", "الحجوزات", ...]
    $table->json('features_en');
    $table->json('modules_included');                     // ["patients","bookings","invoices","dental","crm","hr","inventory","insurance"]
    $table->integer('max_users')->nullable();             // null = unlimited
    $table->integer('max_patients')->nullable();          // null = unlimited
    $table->string('support_level');                      // email, phone, priority, dedicated
    $table->integer('display_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Migration 7: currencies (NEW — Multi-Currency Support)**
```bash
php artisan make:migration create_currencies_table
```
```php
Schema::create('currencies', function (Blueprint $table) {
    $table->id();
    $table->string('code', 3)->unique();                // SAR, AED, KWD, USD, EUR, etc.
    $table->string('name_ar');                           // ريال سعودي
    $table->string('name_en');                           // Saudi Riyal
    $table->string('symbol', 10);                       // ر.س, د.إ, $, €, etc.
    $table->enum('symbol_position', ['before', 'after'])->default('after'); // before: $299, after: 299 ر.س
    $table->tinyInteger('decimal_places')->default(2);  // KWD/BHD=3, IQD=0, most=2
    $table->decimal('rate_to_sar', 12, 6)->default(1);  // Exchange rate: 1 SAR = X currency
    $table->string('country_code', 2)->nullable();      // SA, AE, KW for auto-detection
    $table->string('flag_emoji', 10)->nullable();       // 🇸🇦, 🇦🇪, 🇰🇼
    $table->integer('display_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Migration 7b: Seed Currencies**
```php
// In DatabaseSeeder or CurrencySeeder
$currencies = [
    ['code'=>'SAR', 'name_ar'=>'ريال سعودي',    'name_en'=>'Saudi Riyal',       'symbol'=>'ر.س',  'symbol_position'=>'after',  'decimal_places'=>2, 'rate_to_sar'=>1.000000,   'country_code'=>'SA', 'flag_emoji'=>'🇸🇦', 'display_order'=>1],
    ['code'=>'AED', 'name_ar'=>'درهم إماراتي',   'name_en'=>'UAE Dirham',        'symbol'=>'د.إ',  'symbol_position'=>'after',  'decimal_places'=>2, 'rate_to_sar'=>0.980000,   'country_code'=>'AE', 'flag_emoji'=>'🇦🇪', 'display_order'=>2],
    ['code'=>'KWD', 'name_ar'=>'دينار كويتي',    'name_en'=>'Kuwaiti Dinar',     'symbol'=>'د.ك',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>0.082000,   'country_code'=>'KW', 'flag_emoji'=>'🇰🇼', 'display_order'=>3],
    ['code'=>'BHD', 'name_ar'=>'دينار بحريني',   'name_en'=>'Bahraini Dinar',    'symbol'=>'د.ب',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>0.100000,   'country_code'=>'BH', 'flag_emoji'=>'🇧🇭', 'display_order'=>4],
    ['code'=>'QAR', 'name_ar'=>'ريال قطري',      'name_en'=>'Qatari Riyal',      'symbol'=>'ر.ق',  'symbol_position'=>'after',  'decimal_places'=>2, 'rate_to_sar'=>0.971000,   'country_code'=>'QA', 'flag_emoji'=>'🇶🇦', 'display_order'=>5],
    ['code'=>'OMR', 'name_ar'=>'ريال عماني',     'name_en'=>'Omani Rial',        'symbol'=>'ر.ع',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>0.103000,   'country_code'=>'OM', 'flag_emoji'=>'🇴🇲', 'display_order'=>6],
    ['code'=>'EGP', 'name_ar'=>'جنيه مصري',      'name_en'=>'Egyptian Pound',    'symbol'=>'ج.م',  'symbol_position'=>'after',  'decimal_places'=>2, 'rate_to_sar'=>13.100000,  'country_code'=>'EG', 'flag_emoji'=>'🇪🇬', 'display_order'=>7],
    ['code'=>'JOD', 'name_ar'=>'دينار أردني',    'name_en'=>'Jordanian Dinar',   'symbol'=>'د.أ',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>0.189000,   'country_code'=>'JO', 'flag_emoji'=>'🇯🇴', 'display_order'=>8],
    ['code'=>'IQD', 'name_ar'=>'دينار عراقي',    'name_en'=>'Iraqi Dinar',       'symbol'=>'د.ع',  'symbol_position'=>'after',  'decimal_places'=>0, 'rate_to_sar'=>349.500000, 'country_code'=>'IQ', 'flag_emoji'=>'🇮🇶', 'display_order'=>9],
    ['code'=>'LBP', 'name_ar'=>'ليرة لبنانية',   'name_en'=>'Lebanese Pound',    'symbol'=>'ل.ل',  'symbol_position'=>'after',  'decimal_places'=>0, 'rate_to_sar'=>23900.000,  'country_code'=>'LB', 'flag_emoji'=>'🇱🇧', 'display_order'=>10],
    ['code'=>'LYD', 'name_ar'=>'دينار ليبي',     'name_en'=>'Libyan Dinar',      'symbol'=>'د.ل',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>1.290000,   'country_code'=>'LY', 'flag_emoji'=>'🇱🇾', 'display_order'=>11],
    ['code'=>'MAD', 'name_ar'=>'درهم مغربي',     'name_en'=>'Moroccan Dirham',   'symbol'=>'د.م',  'symbol_position'=>'after',  'decimal_places'=>2, 'rate_to_sar'=>2.650000,   'country_code'=>'MA', 'flag_emoji'=>'🇲🇦', 'display_order'=>12],
    ['code'=>'TND', 'name_ar'=>'دينار تونسي',    'name_en'=>'Tunisian Dinar',    'symbol'=>'د.ت',  'symbol_position'=>'after',  'decimal_places'=>3, 'rate_to_sar'=>0.830000,   'country_code'=>'TN', 'flag_emoji'=>'🇹🇳', 'display_order'=>13],
    ['code'=>'USD', 'name_ar'=>'دولار أمريكي',   'name_en'=>'US Dollar',         'symbol'=>'$',    'symbol_position'=>'before', 'decimal_places'=>2, 'rate_to_sar'=>0.267000,   'country_code'=>'US', 'flag_emoji'=>'🇺🇸', 'display_order'=>14],
    ['code'=>'EUR', 'name_ar'=>'يورو',           'name_en'=>'Euro',              'symbol'=>'€',    'symbol_position'=>'before', 'decimal_places'=>2, 'rate_to_sar'=>0.245000,   'country_code'=>'EU', 'flag_emoji'=>'🇪🇺', 'display_order'=>15],
    ['code'=>'GBP', 'name_ar'=>'جنيه إسترليني', 'name_en'=>'British Pound',     'symbol'=>'£',    'symbol_position'=>'before', 'decimal_places'=>2, 'rate_to_sar'=>0.210000,   'country_code'=>'GB', 'flag_emoji'=>'🇬🇧', 'display_order'=>16],
];

foreach ($currencies as $currency) {
    \App\Models\Currency::create($currency);
}
```

**Migration 8: testimonials**
```bash
php artisan make:migration create_testimonials_table
```
```php
Schema::create('testimonials', function (Blueprint $table) {
    $table->id();
    $table->string('client_name_ar');
    $table->string('client_name_en');
    $table->string('clinic_name_ar')->nullable();
    $table->string('clinic_name_en')->nullable();
    $table->string('role_ar')->nullable();               // المدير التنفيذي
    $table->string('role_en')->nullable();               // CEO
    $table->text('review_ar');
    $table->text('review_en');
    $table->unsignedTinyInteger('rating')->default(5);   // 1-5
    $table->string('photo')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('display_order')->default(0);
    $table->timestamps();
});
```

**Migration 8: faqs**
```bash
php artisan make:migration create_faqs_table
```
```php
Schema::create('faqs', function (Blueprint $table) {
    $table->id();
    $table->string('category')->default('general');      // general, pricing, technical, dental, crm
    $table->text('question_ar');
    $table->text('question_en');
    $table->text('answer_ar');
    $table->text('answer_en');
    $table->integer('display_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Migration 9: site_settings**
```bash
php artisan make:migration create_site_settings_table
```
```php
Schema::create('site_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->string('group')->default('general');
    $table->timestamps();
});
```

### Step 1.2: Run Migrations
```bash
php artisan migrate
```

### Step 1.3: Create Models
Create a Model for each table in `app/Models/`:
- `DemoRequest.php` — fillable: all fields, casts: `interested_modules => array`
- `ContactMessage.php` — fillable: all fields
- `NewsletterSubscriber.php` — fillable: email, locale
- `BlogCategory.php` — fillable: all, relationship: hasMany posts
- `BlogPost.php` — fillable: all, casts: `published_at => datetime`, relationship: belongsTo category
- `PricingPlan.php` — fillable: all, casts: `features_ar/en => array, modules_included => array`
- `Testimonial.php` — fillable: all
- `Faq.php` — fillable: all
- `SiteSetting.php` — fillable: key, value, group
- `Currency.php` — fillable: all fields, scopes: `scopeActive()`, helper: `convertFromSar($amount)` method that returns `round($amount * $this->rate_to_sar, $this->decimal_places)`, `formatPrice($amount, $locale)` method

### Step 1.4: Create Seeders
```bash
php artisan make:seeder PricingPlanSeeder
php artisan make:seeder FaqSeeder
php artisan make:seeder TestimonialSeeder
```

**PricingPlanSeeder.php** — Seed these 4 plans:

```php
// Plan 1: الأساسي (Basic)
[
    'name_ar' => 'الأساسي',
    'name_en' => 'Basic',
    'slug' => 'basic',
    'description_ar' => 'مثالي للعيادات الصغيرة التي تبدأ رحلة التحول الرقمي',
    'description_en' => 'Perfect for small clinics starting their digital transformation',
    'monthly_price' => 299,
    'yearly_price' => 2870,     // ~20% discount
    'currency' => 'SAR',
    'is_popular' => false,
    'is_custom' => false,
    'features_ar' => json_encode([
        'بوابة المدير + السكرتيرة + الموقع العام',
        'إدارة المرضى (حتى 500 مريض)',
        'نظام الحجوزات والمواعيد',
        'الفواتير والمدفوعات الأساسية',
        'لوحة تحكم بإحصائيات أساسية',
        'طريقة دفع واحدة',
        'دعم فني عبر البريد الإلكتروني',
        'لغة واحدة (عربي أو إنجليزي)',
        '3 مستخدمين كحد أقصى',
        'قالب PDF للفاتورة فقط',
        'نسخ احتياطي أسبوعي',
    ]),
    'features_en' => json_encode([
        'Admin + Secretary + Public Website portals',
        'Patient management (up to 500 patients)',
        'Bookings & appointments system',
        'Basic invoicing & payments',
        'Dashboard with basic statistics',
        'Single payment method',
        'Email support',
        'Single language (Arabic or English)',
        'Up to 3 users',
        'Invoice PDF template only',
        'Weekly backup',
    ]),
    'modules_included' => json_encode(['patients','bookings','invoices','payments','website']),
    'max_users' => 3,
    'max_patients' => 500,
    'support_level' => 'email',
    'display_order' => 1,
],

// Plan 2: الاحترافي (Professional) — MOST POPULAR
[
    'name_ar' => 'الاحترافي',
    'name_en' => 'Professional',
    'slug' => 'professional',
    'description_ar' => 'الخيار الأمثل للعيادات المتوسطة التي تريد تجربة متكاملة',
    'description_en' => 'The ideal choice for medium clinics wanting a complete experience',
    'monthly_price' => 599,
    'yearly_price' => 5750,
    'currency' => 'SAR',
    'is_popular' => true,
    'is_custom' => false,
    'features_ar' => json_encode([
        'كل ميزات الخطة الأساسية +',
        'بوابة الطبيب + بوابة المريض',
        'مرضى غير محدودين',
        'نظام CRM متكامل (عملاء محتملين، أنبوب مبيعات، تسلسلات آلية)',
        'نظام المحفظة الإلكترونية للمرضى',
        'أكواد الخصم والعروض الترويجية',
        'إشعارات البريد الإلكتروني والتذكيرات',
        'الدردشة الداخلية بين الموظفين',
        'شريط الأوامر السريع (Cmd+K)',
        'نظام الإشعارات الفورية',
        'قوالب PDF (فاتورة + وصفة + إيصال)',
        'ثنائي اللغة (عربي + إنجليزي)',
        '10 مستخدمين كحد أقصى',
        'الإشعارات الدائنة (Credit Notes)',
        'استبيان رضا المرضى مع NPS',
        'إدارة المصروفات',
        'سجل تدقيق أساسي',
        'دعم فني عبر الهاتف والبريد',
        'نسخ احتياطي يومي',
    ]),
    'features_en' => json_encode([
        'Everything in Basic plan +',
        'Doctor Portal + Patient Portal',
        'Unlimited patients',
        'Full CRM system (leads, pipeline, sequences)',
        'Patient e-wallet system',
        'Discount codes & promotions',
        'Email notifications & reminders',
        'Internal team chat',
        'Command palette (Cmd+K)',
        'Real-time notifications',
        'PDF templates (invoice + prescription + receipt)',
        'Bilingual (Arabic + English)',
        'Up to 10 users',
        'Credit notes management',
        'Patient satisfaction surveys with NPS',
        'Expense management',
        'Basic audit log',
        'Phone & email support',
        'Daily backup',
    ]),
    'modules_included' => json_encode(['patients','bookings','invoices','payments','website','crm','wallet','discounts','chat','notifications','prescriptions','satisfaction','expenses']),
    'max_users' => 10,
    'max_patients' => null, // unlimited
    'support_level' => 'phone',
    'display_order' => 2,
],

// Plan 3: المتقدم (Enterprise)
[
    'name_ar' => 'المتقدم',
    'name_en' => 'Enterprise',
    'slug' => 'enterprise',
    'description_ar' => 'الحل الشامل للمراكز الطبية التي تريد كل شيء',
    'description_en' => 'The complete solution for medical centers that want everything',
    'monthly_price' => 999,
    'yearly_price' => 9590,
    'currency' => 'SAR',
    'is_popular' => false,
    'is_custom' => false,
    'features_ar' => json_encode([
        'كل ميزات الخطة الاحترافية +',
        'بوابة مدير الموقع (39 واجهة كاملة)',
        'وحدة طب الأسنان الكاملة:',
        '  — مخطط أسنان تفاعلي SVG بترقيم FDI (32 سن × 5 أسطح × 9 حالات)',
        '  — خطط العلاج مع شريط تقدم (4 أولويات، 5 حالات)',
        '  — مخطط اللثة Periodontal (6 قياسات لكل سن)',
        '  — 6 أنواع أشعة (بانوراما، حول ذروية، جناح العض، سيفالومترية، CBCT، إطباقية)',
        '  — طلبات المعمل (8 أنواع قطع، اختيار اللون والمادة، 6 مراحل)',
        '  — موافقات رقمية بتوقيع إلكتروني',
        '  — مقارنات قبل/بعد العلاج',
        '  — قواعد متابعة ذكية وتذكيرات تلقائية',
        'وحدة الموارد البشرية الكاملة:',
        '  — ملفات موظفين شاملة مع هيكل رواتب (أساسي + سكن + نقل + بدلات)',
        '  — تسجيل حضور بتحديد GPS',
        '  — مسيرات رواتب (12+ حقل مالي) مع سير موافقة',
        '  — إدارة الإجازات والورديات والأقسام',
        '  — نظام السلف والعقوبات المالية',
        'وحدة التأمين الطبي الكاملة:',
        '  — إدارة شركات التأمين والخطط (6 فئات VIP-E)',
        '  — 6 أعلام تغطية مستقلة (أسنان، جلدية، تجميل، مختبر، أشعة، أدوية)',
        '  — مطالبات تأمينية بسير عمل 8 مراحل',
        'وحدة المخزون الكاملة:',
        '  — إدارة مواد بـ SKU مع إعادة طلب تلقائي',
        '  — تنبيهات مخزون منخفض وانتهاء صلاحية',
        '  — إدارة موردين وأوامر شراء (7 مراحل)',
        'مستخدمين غير محدودين',
        '8 قوالب PDF كاملة (فاتورة، وصفة، مخطط أسنان، خطة علاج، موافقة، إيصالات، مسير راتب)',
        'تكامل تتبع كامل (GTM, GA4, Facebook, TikTok, Snapchat, Twitter Pixels)',
        'نظام RBAC كامل بأكثر من 80 صلاحية دقيقة',
        'سجل تدقيق شامل + سجل وصول طبي حساس',
        'سلة محذوفات مع استعادة (Soft Delete)',
        'دعم فني ذهبي 24/7',
        'نسخ احتياطي كل 6 ساعات',
    ]),
    'features_en' => json_encode([
        'Everything in Professional plan +',
        'Webmaster Portal (39 full interfaces)',
        'Complete Dental Module:',
        '  — Interactive SVG dental chart with FDI numbering (32 teeth × 5 surfaces × 9 conditions)',
        '  — Treatment plans with progress bar (4 priorities, 5 statuses)',
        '  — Periodontal chart (6 measurements per tooth)',
        '  — 6 X-ray types (panoramic, periapical, bitewing, cephalometric, CBCT, occlusal)',
        '  — Lab orders (8 item types, shade & material selection, 6-stage workflow)',
        '  — Digital consent with e-signature',
        '  — Before/after treatment comparisons',
        '  — Smart follow-up rules & auto-reminders',
        'Complete HR Module:',
        '  — Full employee profiles with salary structure (basic + housing + transport + allowances)',
        '  — GPS attendance tracking',
        '  — Payroll (12+ financial fields) with approval workflow',
        '  — Leave, shift & department management',
        '  — Advances & penalties system',
        'Complete Insurance Module:',
        '  — Insurance companies & plans (6 classes VIP-E)',
        '  — 6 independent coverage flags (dental, derma, cosmetic, lab, xray, medication)',
        '  — Insurance claims with 8-stage workflow',
        'Complete Inventory Module:',
        '  — Supply management with SKU & auto-reorder',
        '  — Low stock & expiry alerts',
        '  — Supplier & purchase order management (7-stage workflow)',
        'Unlimited users',
        '8 complete PDF templates (invoice, prescription, dental chart, treatment plan, consent, receipts, payslip)',
        'Full tracking integration (GTM, GA4, Facebook, TikTok, Snapchat, Twitter)',
        'Full RBAC with 80+ granular permissions',
        'Complete audit log + medical access log',
        'Trash with restore (Soft Delete)',
        'Gold 24/7 support',
        'Backup every 6 hours',
    ]),
    'modules_included' => json_encode(['patients','bookings','invoices','payments','website','crm','wallet','discounts','chat','notifications','prescriptions','satisfaction','expenses','dental','hr','insurance','inventory','webmaster','rbac','audit']),
    'max_users' => null,
    'max_patients' => null,
    'support_level' => 'priority',
    'display_order' => 3,
],

// Plan 4: مخصص (Custom)
[
    'name_ar' => 'مخصص',
    'name_en' => 'Custom',
    'slug' => 'custom',
    'description_ar' => 'حلول مصممة خصيصاً للمراكز الطبية الكبيرة والمستشفيات',
    'description_en' => 'Tailored solutions for large medical centers and hospitals',
    'monthly_price' => 0,
    'yearly_price' => 0,
    'currency' => 'SAR',
    'is_popular' => false,
    'is_custom' => true,
    'features_ar' => json_encode([
        'كل ميزات الخطة المتقدمة +',
        'تركيب على سيرفر خاص (On-Premise)',
        'تطوير ميزات حسب الطلب',
        'تكامل مع أنظمة خارجية (ERP, LIS, RIS, PACS)',
        'تدريب شامل لفريق العمل',
        'مدير حساب مخصص',
        'اتفاقية مستوى خدمة SLA مخصصة',
        'White-Label (تغيير الهوية والشعار)',
        'تقارير مخصصة حسب الطلب',
        'API مفتوح للتكامل',
        'أولوية في الدعم الفني',
        'استشارات تقنية مجانية',
    ]),
    'features_en' => json_encode([
        'Everything in Enterprise plan +',
        'On-Premise installation',
        'Custom feature development',
        'External system integration (ERP, LIS, RIS, PACS)',
        'Comprehensive team training',
        'Dedicated account manager',
        'Custom SLA agreement',
        'White-Label (custom branding)',
        'Custom reports on demand',
        'Open API for integration',
        'Priority technical support',
        'Free technical consultations',
    ]),
    'modules_included' => json_encode(['all']),
    'max_users' => null,
    'max_patients' => null,
    'support_level' => 'dedicated',
    'display_order' => 4,
],
```

**FaqSeeder.php** — Seed these FAQs:
```php
[
    ['category'=>'general', 'question_ar'=>'ما هو نظام دكتوراتو؟', 'question_en'=>'What is Doctorato?',
     'answer_ar'=>'دكتوراتو هو نظام متكامل لإدارة العيادات والمراكز الطبية يتضمن 6 بوابات مستقلة (المدير، الطبيب، السكرتيرة، المريض، مدير الموقع، الموقع العام) وأكثر من 800 خاصية تغطي إدارة المرضى والحجوزات والفواتير وطب الأسنان وCRM والموارد البشرية والمخزون والتأمين. تم بناؤه بأحدث التقنيات (Laravel 11 + Vue.js 3) ويدعم اللغتين العربية والإنجليزية بشكل كامل.',
     'answer_en'=>'Doctorato is a comprehensive clinic & medical center management system featuring 6 independent portals (Admin, Doctor, Secretary, Patient, Webmaster, Public Website) with 800+ features covering patient management, bookings, invoicing, dental, CRM, HR, inventory, and insurance. Built with the latest technologies (Laravel 11 + Vue.js 3) with full Arabic & English support.'],

    ['category'=>'general', 'question_ar'=>'هل يدعم النظام اللغة العربية بالكامل؟', 'question_en'=>'Does the system fully support Arabic?',
     'answer_ar'=>'نعم، يدعم النظام اللغتين العربية والإنجليزية بشكل كامل مع أكثر من 3,000 مفتاح ترجمة. يتضمن دعم كامل لاتجاه الكتابة من اليمين لليسار (RTL) باستخدام خط Tajawal للعربية وخط Poppins للإنجليزية مع تبديل تلقائي حسب اللغة المختارة.',
     'answer_en'=>'Yes, the system fully supports both Arabic and English with 3,000+ translation keys. It includes complete RTL support using Tajawal font for Arabic and Poppins for English with automatic switching.'],

    ['category'=>'technical', 'question_ar'=>'هل يمكنني تفعيل وحدة طب الأسنان فقط بدون باقي الوحدات؟', 'question_en'=>'Can I enable only the dental module without others?',
     'answer_ar'=>'نعم، النظام مبني بتصميم معياري (Modular Architecture) يتيح لك تفعيل أو تعطيل أي وحدة حسب حاجتك. يمكنك تفعيل وحدة طب الأسنان فقط، أو CRM فقط، أو أي مجموعة تناسب عيادتك. كل وحدة محمية بحارس وحدة (Module Guard) مستقل.',
     'answer_en'=>'Yes, the system uses a modular architecture that allows you to enable or disable any module as needed. You can enable only the dental module, only CRM, or any combination that suits your clinic. Each module is protected by an independent Module Guard.'],

    ['category'=>'pricing', 'question_ar'=>'هل يمكنني الترقية من خطة لأخرى؟', 'question_en'=>'Can I upgrade from one plan to another?',
     'answer_ar'=>'نعم، يمكنك الترقية في أي وقت من خطة لأخرى. سيتم احتساب الفرق بشكل تناسبي (Pro-Rata) حسب المدة المتبقية من اشتراكك الحالي. جميع بياناتك وإعداداتك ستبقى كما هي عند الترقية.',
     'answer_en'=>'Yes, you can upgrade at any time. The difference will be calculated pro-rata based on the remaining period of your current subscription. All your data and settings will remain intact upon upgrade.'],

    ['category'=>'pricing', 'question_ar'=>'ما هي مدة العرض التجريبي المجاني؟', 'question_en'=>'How long is the free trial?',
     'answer_ar'=>'نوفر عرضاً تجريبياً مجانياً لمدة 14 يوماً يشمل جميع ميزات الخطة المتقدمة (Enterprise) بدون الحاجة لبطاقة ائتمان. خلال الفترة التجريبية، ستحصل على دعم فني مجاني لمساعدتك في إعداد النظام واستكشاف جميع الميزات.',
     'answer_en'=>'We offer a 14-day free trial that includes all Enterprise plan features with no credit card required. During the trial, you get free technical support to help you set up the system and explore all features.'],

    ['category'=>'technical', 'question_ar'=>'كيف يتم تخزين البيانات الطبية؟ هل هي آمنة؟', 'question_en'=>'How is medical data stored? Is it secure?',
     'answer_ar'=>'نعم، أمان البيانات أولويتنا القصوى. نستخدم تشفير SSL/TLS لجميع الاتصالات، تشفير Bcrypt لكلمات المرور، حماية CSRF لجميع النماذج، نظام صلاحيات RBAC بأكثر من 80 صلاحية دقيقة، سجل تدقيق شامل لجميع العمليات، سجل وصول خاص للبيانات الطبية الحساسة، ونسخ احتياطي منتظم. البيانات مخزنة على سيرفرات محمية مع إمكانية التركيب على سيرفر خاص (On-Premise).',
     'answer_en'=>'Yes, data security is our top priority. We use SSL/TLS encryption, Bcrypt password hashing, CSRF protection, RBAC with 80+ permissions, comprehensive audit logging, medical access logging, and regular backups. Data is stored on secure servers with On-Premise installation option.'],

    ['category'=>'technical', 'question_ar'=>'هل يدعم النظام التكامل مع أنظمة أخرى؟', 'question_en'=>'Does the system support integration with other systems?',
     'answer_ar'=>'في الخطة المخصصة (Custom)، نوفر تكامل مع أنظمة خارجية مثل ERP وأنظمة المختبرات (LIS) وأنظمة الأشعة (RIS/PACS) وأي نظام آخر عبر API. كما يتضمن النظام تكامل جاهز مع Google Tag Manager و Google Analytics 4 و Facebook Pixel و TikTok و Snapchat و Twitter Pixels.',
     'answer_en'=>'In the Custom plan, we offer integration with external systems like ERP, LIS, RIS/PACS, and any other system via API. The system also includes built-in integration with Google Tag Manager, GA4, Facebook Pixel, TikTok, Snapchat, and Twitter Pixels.'],

    ['category'=>'general', 'question_ar'=>'هل يمكن تركيب النظام على سيرفر خاص؟', 'question_en'=>'Can the system be installed on a private server?',
     'answer_ar'=>'نعم، في الخطة المخصصة (Custom) نوفر خيار التركيب على سيرفر خاص (On-Premise) مع تحكم كامل في البيانات والبنية التحتية. هذا الخيار مثالي للمراكز الطبية الكبيرة التي لديها متطلبات أمنية صارمة أو تنظيمية تتطلب بقاء البيانات داخل الدولة.',
     'answer_en'=>'Yes, in the Custom plan we offer On-Premise installation with full control over data and infrastructure. This option is ideal for large medical centers with strict security or regulatory requirements that mandate data residency.'],

    ['category'=>'pricing', 'question_ar'=>'ما هي طرق الدفع المقبولة؟', 'question_en'=>'What payment methods are accepted?',
     'answer_ar'=>'نقبل الدفع عبر: بطاقات الائتمان (Visa, Mastercard, Mada)، التحويل البنكي المباشر، وApple Pay. للخطط السنوية والمخصصة، يمكن ترتيب دفعات شهرية أو ربع سنوية حسب الاتفاق.',
     'answer_en'=>'We accept payment via: Credit cards (Visa, Mastercard, Mada), bank transfer, and Apple Pay. For annual and custom plans, monthly or quarterly payments can be arranged.'],

    ['category'=>'general', 'question_ar'=>'هل يوفر النظام تطبيق جوال؟', 'question_en'=>'Does the system provide a mobile app?',
     'answer_ar'=>'النظام مبني بتصميم متجاوب (Responsive) يعمل بشكل مثالي على جميع الأجهزة (كمبيوتر، لوحي، هاتف) عبر متصفح الويب. تطبيق جوال مخصص (iOS + Android) قيد التطوير وسيكون متاحاً في التحديثات القادمة.',
     'answer_en'=>'The system is built with a responsive design that works perfectly on all devices (desktop, tablet, phone) via web browser. A dedicated mobile app (iOS + Android) is under development and will be available in upcoming updates.'],
]
```

---

## PHASE 2: BACKEND (LARAVEL)

### Step 2.1: Create Controllers

**app/Http/Controllers/HomeController.php**
```php
<?php
namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\Testimonial;
use App\Models\Faq;
use App\Models\Currency;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Home', [
            'plans' => PricingPlan::where('is_active', true)->orderBy('display_order')->get(),
            'testimonials' => Testimonial::where('is_active', true)->orderBy('display_order')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('display_order')->get(),
            'currencies' => Currency::where('is_active', true)->orderBy('display_order')->get(),
            'currentCurrency' => session('currency', 'SAR'),
        ]);
    }
}
```

**app/Http/Controllers/DemoRequestController.php**
```php
<?php
namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|string|max:10',
            'country' => 'nullable|string|max:100',
            'doctors_count' => 'nullable|string',
            'specialty' => 'nullable|string',
            'interested_modules' => 'nullable|array',
            'referral_source' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $demo = DemoRequest::create($validated);

        // Send email notification to admin
        Mail::raw(
            "طلب عرض تجريبي جديد!\n\n" .
            "العيادة: {$demo->clinic_name}\n" .
            "الاسم: {$demo->full_name}\n" .
            "البريد: {$demo->email}\n" .
            "الهاتف: {$demo->country_code}{$demo->phone}\n" .
            "التخصص: {$demo->specialty}\n" .
            "عدد الأطباء: {$demo->doctors_count}\n",
            function ($message) {
                $message->to('info@markeza-group.com')
                    ->subject('طلب عرض تجريبي جديد — Doctorato');
            }
        );

        return back()->with('success', true);
    }
}
```

**app/Http/Controllers/ContactController.php** — Same pattern, validate name/email/phone/subject/message, store, send email.

**app/Http/Controllers/NewsletterController.php** — Validate email, store, return success.

**app/Http/Controllers/BlogController.php** — index (paginate published posts), show (single post by slug, increment views).

**app/Http/Controllers/PricingController.php** — index (all active plans with features).

**app/Http/Controllers/PageController.php** — about, features, contact pages.

### Step 2.2: Routes (routes/web.php)
```php
use App\Http\Controllers\{HomeController, DemoRequestController, ContactController, NewsletterController, BlogController, PricingController, PageController};

// Language switcher
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return back();
})->name('lang.switch');

// Currency switcher
Route::get('/currency/{code}', function ($code) {
    $currency = \App\Models\Currency::where('code', strtoupper($code))->where('is_active', true)->first();
    if ($currency) {
        session(['currency' => $currency->code]);
    }
    return back();
})->name('currency.switch');

// Main pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/features', [PageController::class, 'features'])->name('features');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/demo', [PageController::class, 'demo'])->name('demo');

// Forms
Route::post('/demo-request', [DemoRequestController::class, 'store'])->name('demo.store')->middleware('throttle:3,1');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,1');
Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store')->middleware('throttle:3,1');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
```

### Step 2.3: Locale Middleware
Create middleware to set locale from session:
```php
// app/Http/Middleware/SetLocale.php
public function handle($request, $next)
{
    $locale = session('locale', 'ar');
    app()->setLocale($locale);
    return $next($request);
}
```
Register globally in bootstrap/app.php.

---

## PHASE 3: FRONTEND (VUE 3 + TAILWIND)

### Step 3.1: Directory Structure
```
resources/js/
├── app.js
├── locales/
│   ├── ar.json          (Arabic translations — 200+ keys)
│   └── en.json          (English translations — 200+ keys)
├── Layouts/
│   └── MainLayout.vue   (Navbar + Footer + WhatsApp + ScrollTop)
├── Components/
│   ├── Navbar.vue
│   ├── Footer.vue
│   ├── Hero.vue
│   ├── TrustBar.vue
│   ├── ProblemSolution.vue
│   ├── PortalsShowcase.vue
│   ├── ModulesTabs.vue
│   ├── DashboardPreview.vue
│   ├── DentalSpotlight.vue
│   ├── TechStack.vue
│   ├── TestimonialsCarousel.vue
│   ├── PricingCards.vue
│   ├── DemoRequestForm.vue
│   ├── FaqAccordion.vue
│   ├── NewsletterSignup.vue
│   ├── WhatsAppButton.vue
│   ├── ScrollToTop.vue
│   ├── AnimatedCounter.vue
│   ├── SectionTitle.vue
│   └── LanguageSwitcher.vue
├── Pages/
│   ├── Home.vue
│   ├── Features.vue
│   ├── Pricing.vue
│   ├── About.vue
│   ├── Contact.vue
│   ├── Demo.vue
│   └── Blog/
│       ├── Index.vue
│       └── Show.vue
└── composables/
    ├── useCurrency.js
    ├── useLocale.js
    ├── useScrollAnimation.js
    └── useAnimatedCounter.js
```

### Step 3.2: MainLayout.vue — EXACT IMPLEMENTATION
```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import WhatsAppButton from '@/Components/WhatsAppButton.vue';
import ScrollToTop from '@/Components/ScrollToTop.vue';

const { locale } = useI18n();
const dir = computed(() => locale.value === 'ar' ? 'rtl' : 'ltr');
</script>

<template>
    <div :dir="dir" :lang="locale" :class="locale === 'ar' ? 'font-[Tajawal]' : 'font-[Poppins]'">
        <Navbar />
        <main>
            <slot />
        </main>
        <Footer />
        <WhatsAppButton />
        <ScrollToTop />
    </div>
</template>
```

### Step 3.3: AnimatedCounter.vue — EXACT IMPLEMENTATION
```vue
<script setup>
import { ref, onMounted, watch } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';

const props = defineProps({
    target: { type: Number, required: true },
    duration: { type: Number, default: 2000 },
    suffix: { type: String, default: '' },
    prefix: { type: String, default: '' },
});

const count = ref(0);
const el = ref(null);
const hasAnimated = ref(false);

function animate() {
    if (hasAnimated.value) return;
    hasAnimated.value = true;
    const start = performance.now();
    const step = (now) => {
        const progress = Math.min((now - start) / props.duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        count.value = Math.floor(eased * props.target);
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

const { stop } = useIntersectionObserver(el, ([{ isIntersecting }]) => {
    if (isIntersecting) { animate(); stop(); }
}, { threshold: 0.3 });
</script>

<template>
    <span ref="el">{{ prefix }}{{ count.toLocaleString() }}{{ suffix }}</span>
</template>
```

### Step 3.4: Navbar.vue — Key Requirements
- Position: `fixed top-0 w-full z-50`
- On hero: transparent background, white text
- On scroll (>80px): `bg-white/95 backdrop-blur-md shadow-lg` with dark text
- Logo on right (RTL) / left (LTR)
- Links: الرئيسية، الخصائص، الأسعار، من نحن، المدونة، تواصل
- Language switcher button (AR ↔ EN) — calls `/lang/{locale}`
- CTA button: "اطلب عرضاً تجريبياً" — gold bg, rounded-full
- Mobile: hamburger menu with slide-in drawer
- Use `@headlessui/vue` for mobile menu (Disclosure)
- Smooth scroll to sections using `vue3-smooth-scroll`

### Step 3.5: Hero.vue — Key Requirements
- Full viewport height: `min-h-screen`
- Background: `bg-gradient-to-br from-primary via-primary-dark to-[#0A1628]`
- Animated grid/particle background — use CSS grid animation or GSAP
- Logo: `w-48 h-auto mx-auto` with `opacity-0` → `opacity-100` GSAP tween (duration 1s, delay 0.3s)
- Headline: GSAP `from({y: 40, opacity: 0})` → `to({y: 0, opacity: 1})` delay 0.6s
- Sub-headline: same animation, delay 0.9s
- CTA buttons: stagger animation, delay 1.2s
- Stats row: 5 `AnimatedCounter` components in a grid, each with label below
- Stats grid: `grid grid-cols-2 md:grid-cols-5 gap-6` at bottom of hero
- Each stat: large number (text-4xl font-bold text-secondary) + label below (text-sm text-white/70)

### Step 3.6: PricingCards.vue — Key Requirements
- Toggle: شهري / سنوي — use `@headlessui/vue Switch`
- When yearly: show `وفّر 20%` badge in green
- Grid: `grid md:grid-cols-2 lg:grid-cols-4 gap-6`
- Popular card: `ring-2 ring-secondary scale-105` with "الأكثر شعبية" badge
- Custom card: different style with "تواصل معنا" instead of price
- Each card structure:
  ```
  [Badge if popular]
  [Plan Name — Arabic large, English small]
  [Price: SAR XXX/شهرياً or SAR XXX/سنوياً]
  [Description]
  [Divider]
  [Features list with ✓ green checkmarks]
  [CTA Button — gold for popular, outline for others]
  ```
- Feature list: use `v-for` over plan features, each with `CheckCircle` icon from lucide
- CTA for custom plan: "تواصل معنا للحصول على عرض خاص"
- Animation: cards stagger in on scroll with GSAP

### Step 3.7: DemoRequestForm.vue — Key Requirements
- Split layout: form (left in LTR, right in RTL) + benefits (other side)
- Form uses `useForm` from `@inertiajs/vue3`
- All fields as specified in the database schema
- Country code dropdown: `+966 🇸🇦`, `+971 🇦🇪`, `+965 🇰🇼`, `+973 🇧🇭`, `+968 🇴🇲`, `+974 🇶🇦`, `+20 🇪🇬`, `+962 🇯🇴`, `+961 🇱🇧`, `+212 🇲🇦`, `+216 🇹🇳`, `+1 🇺🇸`, `+44 🇬🇧`
- Interested modules: checkboxes for dental, crm, hr, inventory, insurance
- Specialty dropdown: جلدية، أسنان، عام، متعدد التخصصات، أخرى
- Doctors count: radio buttons: 1-5, 6-15, 16-50, 50+
- Submit button: gold, full-width, with loading spinner
- Success state: show checkmark animation + "تم استلام طلبك بنجاح! سنتواصل معك خلال 24 ساعة."
- Validation errors: show below each field in red
- Right side benefits list with green checkmark icons:
  - ✅ عرض تجريبي مجاني لمدة 14 يوماً
  - ✅ بدون بطاقة ائتمان
  - ✅ إعداد كامل خلال 24 ساعة
  - ✅ دعم فني مجاني خلال الفترة التجريبية
  - ✅ بياناتك آمنة 100%
  - ✅ إلغاء في أي وقت

### Step 3.8: ModulesTabs.vue — Key Requirements
- 10 tabs as listed in the original prompt
- Tab bar: horizontal scrollable on mobile, vertical sidebar on desktop
- Each tab icon: use Lucide icons (Users, Calendar, CreditCard, Stethoscope, Target, Building, Shield, Package, BarChart3, Sparkles)
- Active tab: gold border-bottom or left-border + gold text
- Tab content: appears with fade transition
- Each module content: headline + paragraph + feature grid (2-3 columns) + mockup image placeholder
- Feature grid items: each with icon + title + description
- Use `<Transition>` for tab content switching

### Step 3.9: FaqAccordion.vue — Key Requirements
- Use `@headlessui/vue Disclosure`
- Each FAQ: rounded border, expand/collapse with chevron rotation
- Open state: gold left border + expanded content
- Smooth height transition using `<TransitionRoot>`
- Category filter: tabs above FAQ list (عام، الأسعار، تقني)

### Step 3.10: Footer.vue — Key Requirements
- Dark background: `bg-dark text-white`
- 4 columns on desktop, stacked on mobile
- Newsletter form in footer with email input + subscribe button
- Social media icons: Twitter, Instagram, LinkedIn, YouTube, TikTok
- Bottom bar: copyright + privacy policy + terms links
- Copyright: `© 2026 Doctorato by Markeza Group. جميع الحقوق محفوظة.`

---

## PHASE 4: ALL ANIMATIONS (GSAP)

### Step 4.0: useCurrency.js Composable (Multi-Currency Support)
```javascript
// resources/js/composables/useCurrency.js
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

export function useCurrency() {
    const page = usePage();
    const { locale } = useI18n();

    const currencies = computed(() => page.props.currencies || []);
    const currentCurrencyCode = computed(() => page.props.currentCurrency || 'SAR');
    const currentCurrency = computed(() =>
        currencies.value.find(c => c.code === currentCurrencyCode.value) || currencies.value[0]
    );

    /**
     * Convert amount from SAR to selected currency
     */
    function convertPrice(amountInSar) {
        if (!currentCurrency.value) return amountInSar;
        const converted = amountInSar * currentCurrency.value.rate_to_sar;
        return Number(converted.toFixed(currentCurrency.value.decimal_places));
    }

    /**
     * Format price with currency symbol and locale
     * Examples:
     *   formatPrice(299)  → "299 ر.س" (Arabic, SAR)
     *   formatPrice(299)  → "SAR 299" (English, SAR)
     *   formatPrice(79.5) → "$79.50" (English, USD)
     *   formatPrice(79.5) → "79.50$" (Arabic, USD) → "79.50 دولار أمريكي"
     */
    function formatPrice(amountInSar, options = {}) {
        const currency = options.currency || currentCurrency.value;
        if (!currency) return `${amountInSar}`;

        const converted = convertPrice(amountInSar);
        const isArabic = locale.value === 'ar';

        // Format number with proper separators
        const formattedNumber = new Intl.NumberFormat(
            isArabic ? 'ar-SA' : 'en-US',
            {
                minimumFractionDigits: currency.decimal_places,
                maximumFractionDigits: currency.decimal_places,
            }
        ).format(converted);

        // Position symbol
        if (currency.symbol_position === 'before') {
            return `${currency.symbol}${formattedNumber}`;
        } else {
            return `${formattedNumber} ${currency.symbol}`;
        }
    }

    /**
     * Switch currency via route
     */
    function switchCurrency(code) {
        router.get(`/currency/${code}`, {}, { preserveScroll: true, preserveState: true });
    }

    /**
     * Get currency display name based on locale
     */
    function getCurrencyName(currency) {
        return locale.value === 'ar' ? currency.name_ar : currency.name_en;
    }

    return {
        currencies,
        currentCurrency,
        currentCurrencyCode,
        convertPrice,
        formatPrice,
        switchCurrency,
        getCurrencyName,
    };
}
```

### Step 4.0b: CurrencySwitcher.vue Component
```vue
<!-- resources/js/Components/CurrencySwitcher.vue -->
<script setup>
import { ref, computed } from 'vue';
import { useCurrency } from '@/composables/useCurrency';
import { useI18n } from 'vue-i18n';

const { currencies, currentCurrency, switchCurrency, getCurrencyName } = useCurrency();
const { locale } = useI18n();
const isOpen = ref(false);

function selectCurrency(code) {
    switchCurrency(code);
    isOpen.value = false;
}
</script>

<template>
    <div class="relative">
        <!-- Trigger Button -->
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-light/30 hover:border-secondary transition-colors text-sm"
        >
            <span v-if="currentCurrency?.flag_emoji">{{ currentCurrency.flag_emoji }}</span>
            <span class="font-medium">{{ currentCurrency?.code }}</span>
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-50 max-h-80 overflow-y-auto"
                :class="locale === 'ar' ? 'left-0' : 'right-0'"
            >
                <div class="p-2">
                    <button
                        v-for="currency in currencies"
                        :key="currency.code"
                        @click="selectCurrency(currency.code)"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-light-blue transition-colors text-start"
                        :class="{ 'bg-light-blue font-bold': currency.code === currentCurrency?.code }"
                    >
                        <span class="text-lg">{{ currency.flag_emoji }}</span>
                        <span class="flex-1">
                            <span class="block text-sm font-medium text-dark">{{ getCurrencyName(currency) }}</span>
                            <span class="block text-xs text-gray">{{ currency.code }} — {{ currency.symbol }}</span>
                        </span>
                        <span v-if="currency.code === currentCurrency?.code" class="text-secondary text-lg">&#10003;</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
```

### Step 4.0c: Update PricingCards.vue to use Multi-Currency
In the PricingCards component, use the `useCurrency` composable:
```vue
<script setup>
import { useCurrency } from '@/composables/useCurrency';
const { formatPrice } = useCurrency();

const props = defineProps({
    plans: Array,
    billingCycle: { type: String, default: 'monthly' }, // 'monthly' or 'yearly'
});
</script>

<template>
    <!-- In the price display area of each card: -->
    <span class="text-4xl font-bold text-primary">
        {{ formatPrice(billingCycle === 'monthly' ? plan.monthly_price : plan.yearly_price) }}
    </span>
    <span class="text-gray text-sm">/ {{ billingCycle === 'monthly' ? $t('pricing.monthly') : $t('pricing.yearly') }}</span>

    <!-- Show approximate notice when not SAR -->
    <p v-if="currentCurrencyCode !== 'SAR'" class="text-xs text-gray mt-1 italic">
        {{ $t('pricing.approximate_price') }}
    </p>
</template>
```

### Step 4.0d: Update Navbar to include CurrencySwitcher
In the Navbar component, add the CurrencySwitcher next to the language switcher:
```vue
<!-- In Navbar.vue, next to language switcher -->
<div class="flex items-center gap-3">
    <CurrencySwitcher />
    <button @click="switchLang" class="...">
        {{ locale === 'ar' ? 'EN' : 'عربي' }}
    </button>
</div>
```

### Step 4.1: useScrollAnimation.js Composable
```javascript
import { onMounted, onUnmounted } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export function useScrollAnimation() {
    onMounted(() => {
        // Fade up animations
        gsap.utils.toArray('.animate-fade-up').forEach(el => {
            gsap.from(el, {
                y: 60, opacity: 0, duration: 0.8,
                scrollTrigger: { trigger: el, start: 'top 85%', toggleActions: 'play none none none' },
            });
        });

        // Stagger children
        gsap.utils.toArray('.animate-stagger').forEach(parent => {
            gsap.from(parent.children, {
                y: 40, opacity: 0, duration: 0.6, stagger: 0.15,
                scrollTrigger: { trigger: parent, start: 'top 80%' },
            });
        });

        // Scale in
        gsap.utils.toArray('.animate-scale-in').forEach(el => {
            gsap.from(el, {
                scale: 0.8, opacity: 0, duration: 0.6,
                scrollTrigger: { trigger: el, start: 'top 85%' },
            });
        });

        // Slide from left (for RTL: from right)
        gsap.utils.toArray('.animate-slide-right').forEach(el => {
            gsap.from(el, {
                x: -80, opacity: 0, duration: 0.8,
                scrollTrigger: { trigger: el, start: 'top 80%' },
            });
        });

        gsap.utils.toArray('.animate-slide-left').forEach(el => {
            gsap.from(el, {
                x: 80, opacity: 0, duration: 0.8,
                scrollTrigger: { trigger: el, start: 'top 80%' },
            });
        });
    });

    onUnmounted(() => { ScrollTrigger.getAll().forEach(t => t.kill()); });
}
```

### Step 4.2: Animation Classes to Use
Add these CSS classes to elements throughout the pages:
- `animate-fade-up` — most sections, paragraphs, headings
- `animate-stagger` — parent of card grids, feature lists
- `animate-scale-in` — portal cards, pricing cards, tech logos
- `animate-slide-right` — problem section left side
- `animate-slide-left` — solution section right side

---

## PHASE 5: SEO & META

### Step 5.1: Head Meta Tags (in root blade template)
```html
<meta name="description" content="دكتوراتو — نظام إدارة العيادات الطبية المتكامل. 6 بوابات مستقلة، 800+ خاصية، طب أسنان، CRM، موارد بشرية، مخزون، تأمين. جرّب مجاناً 14 يوم.">
<meta name="keywords" content="نظام إدارة عيادات، برنامج عيادات، نظام حجز مواعيد، إدارة مرضى، فواتير طبية، طب أسنان، CRM عيادات، clinic management, medical software, dental software, Doctorato">
<meta property="og:title" content="دكتوراتو — نظام إدارة العيادات الطبية المتكامل">
<meta property="og:description" content="منصة شاملة بـ 6 بوابات مستقلة و800+ خاصية لإدارة كل جانب من جوانب عيادتك">
<meta property="og:type" content="website">
<meta property="og:url" content="https://doctorato.com">
<meta property="og:image" content="https://doctorato.com/images/og-image.jpg">
<meta name="twitter:card" content="summary_large_image">
<link rel="alternate" hreflang="ar" href="https://doctorato.com/?lang=ar">
<link rel="alternate" hreflang="en" href="https://doctorato.com/?lang=en">
```

### Step 5.2: JSON-LD Structured Data (in Home page)
```json
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "Doctorato",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "Web",
    "description": "Comprehensive Medical Clinic Management Platform with 6 independent portals",
    "offers": {
        "@type": "AggregateOffer",
        "lowPrice": "299",
        "highPrice": "999",
        "priceCurrency": "SAR"
    },
    "provider": {
        "@type": "Organization",
        "name": "Markeza Group",
        "email": "info@markeza-group.com"
    }
}
```

---

## PHASE 6: RESPONSIVE DESIGN BREAKPOINTS

Use Tailwind's default breakpoints:
- `sm:` 640px — Mobile landscape
- `md:` 768px — Tablet
- `lg:` 1024px — Desktop
- `xl:` 1280px — Large desktop
- `2xl:` 1536px — Ultra wide

### Key responsive rules:
- Hero stats: `grid-cols-2 md:grid-cols-3 lg:grid-cols-5`
- Portal cards: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
- Pricing cards: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4`
- Feature grids: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`
- Module tabs: vertical on mobile (accordion), horizontal tabs on desktop
- Navbar: full links on `lg:`, hamburger on mobile
- Footer: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4`
- Demo form: stacked on mobile, side-by-side on `lg:`
- All text: base sizes on mobile, larger on desktop using `text-lg md:text-xl lg:text-2xl`

---

## PHASE 7: TRANSLATION FILES

### Step 7.1: resources/js/locales/ar.json (MUST include 200+ keys)
Create comprehensive Arabic translations covering:
- Navbar links
- Hero section text
- All section headings and descriptions
- Portal names and descriptions
- Module names, features, and descriptions
- Pricing plan names, descriptions, and features
- Form labels, placeholders, and validation messages
- FAQ questions and answers
- Footer text
- Common UI text (loading, submit, success, error, etc.)
- Blog labels
- Contact page text
- **Currency-related keys (REQUIRED):**
  - `currency.select`: "اختر العملة" / "Select Currency"
  - `currency.current`: "العملة الحالية" / "Current Currency"
  - `pricing.approximate_price`: "الأسعار التقريبية بعملتك المحلية" / "Approximate prices in your local currency"
  - `pricing.base_currency_note`: "الأسعار الأساسية بالريال السعودي" / "Base prices in Saudi Riyal"
  - `pricing.monthly`: "شهرياً" / "monthly"
  - `pricing.yearly`: "سنوياً" / "yearly"
  - `pricing.per_month`: "/شهر" / "/mo"
  - `pricing.per_year`: "/سنة" / "/yr"
  - `pricing.save_percent`: "وفّر {percent}%" / "Save {percent}%"
  - `pricing.all_currencies`: "جميع العملات المدعومة" / "All Supported Currencies"
  - `pricing.vat_note`: "الأسعار لا تشمل ضريبة القيمة المضافة" / "Prices are exclusive of VAT"
  - All 16 currency names in both Arabic and English

### Step 7.2: resources/js/locales/en.json
Mirror all Arabic keys with English translations.

---

## PHASE 8: FINAL CHECKLIST

Before considering the project complete, verify:

- [ ] All 7 pages render correctly (Home, Features, Pricing, About, Contact, Blog, Demo)
- [ ] Language switcher works (AR ↔ EN) and persists in session
- [ ] RTL/LTR switches correctly with language
- [ ] All 13 home page sections are implemented
- [ ] Animated counters work on scroll
- [ ] GSAP scroll animations work on all sections
- [ ] Pricing toggle (monthly/yearly) works
- [ ] Demo request form submits and sends email
- [ ] Contact form submits and sends email
- [ ] Newsletter subscription works
- [ ] FAQ accordion expands/collapses smoothly
- [ ] Mobile responsive on all breakpoints
- [ ] Navbar transparent → solid on scroll
- [ ] WhatsApp floating button visible
- [ ] Scroll-to-top button appears after scrolling
- [ ] All SEO meta tags present
- [ ] Fonts load correctly (Tajawal + Poppins)
- [ ] Colors match exact hex values specified
- [ ] No console errors
- [ ] All links work correctly
- [ ] Forms have proper validation
- [ ] Loading states on form submissions
- [ ] Success messages after form submission
- [ ] **Currency switcher displays all 16 currencies correctly**
- [ ] **Currency selection persists in session across page navigations**
- [ ] **Pricing cards update dynamically when currency changes**
- [ ] **Currency symbols positioned correctly (before/after based on currency)**
- [ ] **Decimal places correct for each currency (KWD/BHD=3, IQD/LBP=0, others=2)**
- [ ] **Approximate price notice shown when currency is not SAR**
- [ ] **Currency dropdown works on mobile (responsive)**
- [ ] **All 16 currency seeds present in database**
- [ ] **Bilingual content complete (every text element has AR + EN version)**
- [ ] **hreflang tags present for both ar and en**

---

## CONTACT INFORMATION
- **Company:** Markeza Group (مجموعة ماركيزا للتقنية)
- **Email:** info@markeza-group.com
- **Product:** Doctorato — دكتوراتو
- **WhatsApp:** (add your number)
