<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PostSeeder extends Seeder
{
    /**
     * Seed blog posts with full content, images, and metadata.
     */
    public function run(): void
    {
        $categories = PostCategory::all()->keyBy('slug');
        $doctor = Doctor::first();

        $posts = [
            // ──────────────── Post 1: Skincare Tips ────────────────
            [
                'category_id'    => $categories['skincare-tips']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'الروتين اليومي المثالي للعناية بالبشرة: دليلك الشامل',
                'title_en'       => 'The Perfect Daily Skincare Routine: Your Complete Guide',
                'slug'           => 'perfect-daily-skincare-routine',
                'featured_image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=800&q=80',
                'excerpt_ar'     => 'تعرفي على الخطوات الأساسية لروتين العناية بالبشرة اليومي الذي يناسب جميع أنواع البشرة ويمنحك نتائج مذهلة.',
                'excerpt_en'     => 'Learn the essential steps of a daily skincare routine that suits all skin types and gives you amazing results.',
                'content_ar'     => '<h2>أهمية الروتين اليومي للبشرة</h2>
<p>العناية بالبشرة ليست رفاهية بل ضرورة للحفاظ على صحة ونضارة بشرتك. الروتين اليومي المنتظم هو المفتاح للحصول على بشرة صحية ومشرقة. في عيادة أورا ديرما، نؤمن بأن البشرة الجميلة تبدأ من العناية اليومية الصحيحة.</p>

<h2>الخطوة الأولى: التنظيف</h2>
<p>ابدئي يومك بتنظيف بشرتك بغسول مناسب لنوع بشرتك. استخدمي غسولاً لطيفاً خالياً من الكبريتات إذا كانت بشرتك حساسة، أو غسولاً رغوياً إذا كانت بشرتك دهنية. التنظيف يزيل الأوساخ والزيوت المتراكمة ويهيئ بشرتك لامتصاص المنتجات التالية.</p>

<h2>الخطوة الثانية: التونر</h2>
<p>التونر يساعد على موازنة درجة حموضة البشرة وتضييق المسام. اختاري تونراً يحتوي على حمض الهيالورونيك للترطيب العميق، أو حمض الساليسيليك إذا كنتِ تعانين من حب الشباب.</p>

<h2>الخطوة الثالثة: السيروم</h2>
<p>السيروم هو المنتج الأكثر تركيزاً في روتينك. سيروم فيتامين سي صباحاً يحمي من أضرار أشعة الشمس ويوحد لون البشرة. سيروم الريتينول مساءً يحفز تجدد الخلايا ويقلل التجاعيد.</p>

<h2>الخطوة الرابعة: المرطب</h2>
<p>الترطيب ضروري لجميع أنواع البشرة، حتى الدهنية. اختاري مرطباً خفيفاً على شكل جل للبشرة الدهنية، أو كريماً غنياً للبشرة الجافة. المرطب يحبس الرطوبة ويحمي حاجز البشرة الطبيعي.</p>

<h2>الخطوة الخامسة: واقي الشمس</h2>
<p>واقي الشمس هو أهم خطوة في روتينك الصباحي. استخدمي واقياً بعامل حماية SPF 30 على الأقل يومياً، حتى في الأيام الغائمة. الأشعة فوق البنفسجية هي السبب الرئيسي لشيخوخة البشرة المبكرة والتصبغات.</p>

<h2>نصائح إضافية</h2>
<ul>
<li>اشربي ما لا يقل عن 8 أكواب من الماء يومياً</li>
<li>احصلي على نوم كافٍ من 7 إلى 8 ساعات</li>
<li>تجنبي لمس وجهك بأيدٍ غير نظيفة</li>
<li>قومي بتقشير بشرتك مرة أو مرتين أسبوعياً</li>
<li>استشيري طبيب الجلدية لتحديد المنتجات المناسبة لبشرتك</li>
</ul>

<p>في عيادة أورا ديرما، نقدم استشارات متخصصة لتحديد نوع بشرتك وتصميم روتين عناية مخصص لك. احجزي موعدك الآن واحصلي على بشرة أحلامك.</p>',

                'content_en'     => '<h2>The Importance of a Daily Skincare Routine</h2>
<p>Skincare is not a luxury but a necessity to maintain healthy, radiant skin. A consistent daily routine is the key to achieving beautiful, glowing skin. At AURA Derma Clinic, we believe that beautiful skin starts with the right daily care.</p>

<h2>Step 1: Cleansing</h2>
<p>Start your day by cleansing your skin with a cleanser suited to your skin type. Use a gentle, sulfate-free cleanser for sensitive skin, or a foaming cleanser for oily skin. Cleansing removes accumulated dirt and oils and prepares your skin to absorb subsequent products.</p>

<h2>Step 2: Toner</h2>
<p>Toner helps balance your skin\'s pH and tighten pores. Choose a toner containing hyaluronic acid for deep hydration, or salicylic acid if you struggle with acne.</p>

<h2>Step 3: Serum</h2>
<p>Serum is the most concentrated product in your routine. Vitamin C serum in the morning protects against sun damage and evens skin tone. Retinol serum at night stimulates cell renewal and reduces wrinkles.</p>

<h2>Step 4: Moisturizer</h2>
<p>Moisturizing is essential for all skin types, even oily skin. Choose a lightweight gel moisturizer for oily skin, or a rich cream for dry skin. Moisturizer locks in hydration and protects the skin\'s natural barrier.</p>

<h2>Step 5: Sunscreen</h2>
<p>Sunscreen is the most important step in your morning routine. Use a sunscreen with at least SPF 30 daily, even on cloudy days. UV rays are the primary cause of premature skin aging and hyperpigmentation.</p>

<h2>Additional Tips</h2>
<ul>
<li>Drink at least 8 glasses of water daily</li>
<li>Get adequate sleep of 7 to 8 hours</li>
<li>Avoid touching your face with unclean hands</li>
<li>Exfoliate your skin once or twice a week</li>
<li>Consult a dermatologist to determine the right products for your skin</li>
</ul>

<p>At AURA Derma Clinic, we offer specialized consultations to determine your skin type and design a customized skincare routine for you. Book your appointment now and get the skin of your dreams.</p>',
                'status'         => 'published',
                'is_featured'    => true,
                'published_at'   => Carbon::now()->subDays(2),
            ],

            // ──────────────── Post 2: Latest Treatments ────────────────
            [
                'category_id'    => $categories['latest-treatments']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'تقنية الهيدرافيشيال: ثورة في تنظيف وتجديد البشرة',
                'title_en'       => 'HydraFacial Technology: A Revolution in Skin Cleansing and Renewal',
                'slug'           => 'hydrafacial-technology-revolution',
                'featured_image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'excerpt_ar'     => 'اكتشفي تقنية الهيدرافيشيال الحديثة التي تجمع بين التنظيف العميق والترطيب والتغذية في جلسة واحدة مميزة.',
                'excerpt_en'     => 'Discover the modern HydraFacial technology that combines deep cleansing, hydration, and nourishment in one remarkable session.',
                'content_ar'     => '<h2>ما هي تقنية الهيدرافيشيال؟</h2>
<p>الهيدرافيشيال هي تقنية متقدمة للعناية بالبشرة تجمع بين التنظيف العميق، والتقشير اللطيف، والاستخلاص، والترطيب المكثف في جلسة واحدة. تعتمد على جهاز متطور يستخدم تقنية الدوامة المائية (Vortex Technology) لتنظيف وتغذية البشرة بشكل فعال دون أي ألم أو وقت تعافي.</p>

<h2>كيف تعمل تقنية الهيدرافيشيال؟</h2>
<p>تتم الجلسة على عدة خطوات متتالية:</p>
<ul>
<li><strong>التنظيف والتقشير:</strong> إزالة الخلايا الميتة والأوساخ من سطح البشرة باستخدام محلول خاص</li>
<li><strong>استخلاص الشوائب:</strong> سحب الرؤوس السوداء والأوساخ من المسام باستخدام تقنية الشفط اللطيف</li>
<li><strong>الترطيب والتغذية:</strong> ضخ سيرومات مركزة تحتوي على حمض الهيالورونيك ومضادات الأكسدة والببتيدات</li>
<li><strong>الحماية:</strong> تطبيق طبقة واقية لحبس المكونات النشطة في البشرة</li>
</ul>

<h2>فوائد الهيدرافيشيال</h2>
<p>تتميز هذه التقنية بفوائد عديدة تشمل:</p>
<ul>
<li>تنظيف عميق للمسام وإزالة الرؤوس السوداء</li>
<li>ترطيب مكثف يدوم لأيام</li>
<li>تحسين ملمس البشرة ونعومتها</li>
<li>تقليل الخطوط الدقيقة والتجاعيد</li>
<li>توحيد لون البشرة وتفتيح التصبغات</li>
<li>مناسبة لجميع أنواع البشرة بما فيها الحساسة</li>
<li>نتائج فورية من أول جلسة بدون فترة تعافي</li>
</ul>

<h2>لمن تناسب هذه التقنية؟</h2>
<p>الهيدرافيشيال مناسبة لجميع أنواع البشرة وجميع الأعمار. تُعد خياراً مثالياً لمن يعانون من المسام الواسعة، البشرة الباهتة، حب الشباب الخفيف، أو الجفاف. كما أنها ممتازة كجلسة تحضيرية قبل المناسبات الخاصة.</p>

<h2>عدد الجلسات الموصى بها</h2>
<p>للحصول على أفضل النتائج، ننصح بإجراء جلسة كل 4 إلى 6 أسابيع. يمكنك رؤية نتائج واضحة من الجلسة الأولى، ولكن الجلسات المنتظمة تعزز النتائج وتحافظ على صحة بشرتك على المدى الطويل.</p>

<p>في عيادة أورا ديرما، نستخدم أحدث أجهزة الهيدرافيشيال مع سيرومات طبية عالية الجودة لضمان أفضل النتائج. احجزي جلستك الآن واستمتعي ببشرة نقية ومشرقة.</p>',

                'content_en'     => '<h2>What is HydraFacial Technology?</h2>
<p>HydraFacial is an advanced skincare technology that combines deep cleansing, gentle exfoliation, extraction, and intensive hydration in a single session. It relies on a sophisticated device using Vortex Technology to effectively cleanse and nourish the skin without any pain or downtime.</p>

<h2>How Does HydraFacial Work?</h2>
<p>The session consists of several consecutive steps:</p>
<ul>
<li><strong>Cleansing and Exfoliation:</strong> Removing dead cells and impurities from the skin surface using a special solution</li>
<li><strong>Extraction:</strong> Removing blackheads and debris from pores using gentle suction technology</li>
<li><strong>Hydration and Nourishment:</strong> Infusing concentrated serums containing hyaluronic acid, antioxidants, and peptides</li>
<li><strong>Protection:</strong> Applying a protective layer to lock active ingredients into the skin</li>
</ul>

<h2>Benefits of HydraFacial</h2>
<p>This technology offers numerous benefits including:</p>
<ul>
<li>Deep pore cleansing and blackhead removal</li>
<li>Intensive hydration lasting for days</li>
<li>Improved skin texture and smoothness</li>
<li>Reduction of fine lines and wrinkles</li>
<li>Even skin tone and lightening of pigmentation</li>
<li>Suitable for all skin types including sensitive skin</li>
<li>Immediate results from the first session with no downtime</li>
</ul>

<h2>Who Is This Technology For?</h2>
<p>HydraFacial is suitable for all skin types and ages. It is an ideal option for those with enlarged pores, dull skin, mild acne, or dryness. It is also excellent as a preparatory session before special occasions.</p>

<h2>Recommended Number of Sessions</h2>
<p>For best results, we recommend a session every 4 to 6 weeks. You can see visible results from the first session, but regular sessions enhance results and maintain your skin health long-term.</p>

<p>At AURA Derma Clinic, we use the latest HydraFacial devices with high-quality medical serums to ensure the best results. Book your session now and enjoy clear, radiant skin.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(5),
            ],

            // ──────────────── Post 3: Skincare Tips ────────────────
            [
                'category_id'    => $categories['skincare-tips']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'حماية بشرتك من الشمس: كل ما تحتاجين معرفته عن واقي الشمس',
                'title_en'       => 'Protecting Your Skin from the Sun: Everything You Need to Know About Sunscreen',
                'slug'           => 'sun-protection-sunscreen-guide',
                'featured_image' => 'https://images.unsplash.com/photo-1532413992378-f169ac26fff0?w=800&q=80',
                'excerpt_ar'     => 'واقي الشمس هو خط الدفاع الأول لبشرتك. تعرفي على كيفية اختيار واستخدام واقي الشمس المناسب لحماية بشرتك طوال العام.',
                'excerpt_en'     => 'Sunscreen is your skin\'s first line of defense. Learn how to choose and use the right sunscreen to protect your skin all year round.',
                'content_ar'     => '<h2>لماذا واقي الشمس ضروري؟</h2>
<p>أشعة الشمس فوق البنفسجية هي العامل الأول المسبب لشيخوخة البشرة المبكرة، والتصبغات، وحتى سرطان الجلد. واقي الشمس ليس فقط للصيف أو الشاطئ، بل يجب استخدامه يومياً طوال العام، حتى في الأيام الغائمة.</p>

<h2>أنواع الأشعة فوق البنفسجية</h2>
<p>هناك نوعان رئيسيان من الأشعة فوق البنفسجية التي تصل لبشرتنا:</p>
<ul>
<li><strong>UVA (الأشعة فوق البنفسجية أ):</strong> تخترق الطبقات العميقة من الجلد وتسبب التجاعيد والشيخوخة المبكرة. تمر عبر الزجاج والغيوم</li>
<li><strong>UVB (الأشعة فوق البنفسجية ب):</strong> تؤثر على الطبقة السطحية وتسبب الحروق الشمسية والتصبغات</li>
</ul>

<h2>كيف تختارين واقي الشمس المناسب؟</h2>
<p>عند اختيار واقي الشمس، انتبهي لهذه المعايير:</p>
<ul>
<li>عامل حماية SPF 30 على الأقل للاستخدام اليومي</li>
<li>حماية واسعة الطيف (Broad Spectrum) ضد UVA وUVB</li>
<li>مقاوم للماء إذا كنتِ ستتعرقين أو تسبحين</li>
<li>خالي من العطور للبشرة الحساسة</li>
<li>قوام مناسب لنوع بشرتك: خفيف للدهنية، مرطب للجافة</li>
</ul>

<h2>طريقة الاستخدام الصحيحة</h2>
<p>ضعي واقي الشمس قبل الخروج من المنزل بـ 15-20 دقيقة. استخدمي كمية بحجم ملعقتين صغيرتين للوجه والرقبة. أعيدي التطبيق كل ساعتين، وبشكل أكثر تكراراً عند التعرق أو السباحة.</p>

<h2>أخطاء شائعة في استخدام واقي الشمس</h2>
<ul>
<li>عدم استخدام كمية كافية</li>
<li>نسيان مناطق مثل الأذنين والرقبة واليدين</li>
<li>الاعتقاد بأن المكياج يحتوي على حماية كافية</li>
<li>عدم إعادة التطبيق خلال اليوم</li>
<li>استخدام واقي شمس منتهي الصلاحية</li>
</ul>

<p>في عيادة أورا ديرما، نقدم استشارات متخصصة لاختيار واقي الشمس الأمثل لنوع بشرتك واحتياجاتك. كما نوفر مجموعة مختارة من أفضل واقيات الشمس الطبية المتوفرة عالمياً.</p>',

                'content_en'     => '<h2>Why Is Sunscreen Essential?</h2>
<p>Ultraviolet (UV) rays from the sun are the number one cause of premature skin aging, hyperpigmentation, and even skin cancer. Sunscreen is not just for summer or the beach; it should be used daily throughout the year, even on cloudy days.</p>

<h2>Types of UV Rays</h2>
<p>There are two main types of UV rays that reach our skin:</p>
<ul>
<li><strong>UVA (Ultraviolet A):</strong> Penetrates deep skin layers causing wrinkles and premature aging. Passes through glass and clouds</li>
<li><strong>UVB (Ultraviolet B):</strong> Affects the surface layer causing sunburn and pigmentation</li>
</ul>

<h2>How to Choose the Right Sunscreen?</h2>
<p>When choosing sunscreen, pay attention to these criteria:</p>
<ul>
<li>SPF 30 or higher for daily use</li>
<li>Broad Spectrum protection against both UVA and UVB</li>
<li>Water-resistant if you will be sweating or swimming</li>
<li>Fragrance-free for sensitive skin</li>
<li>Suitable texture for your skin type: lightweight for oily, moisturizing for dry</li>
</ul>

<h2>Proper Application Method</h2>
<p>Apply sunscreen 15-20 minutes before going outside. Use approximately two teaspoons for the face and neck. Reapply every two hours, and more frequently when sweating or swimming.</p>

<h2>Common Sunscreen Mistakes</h2>
<ul>
<li>Not using enough product</li>
<li>Forgetting areas like ears, neck, and hands</li>
<li>Believing makeup provides sufficient protection</li>
<li>Not reapplying throughout the day</li>
<li>Using expired sunscreen</li>
</ul>

<p>At AURA Derma Clinic, we offer specialized consultations to choose the optimal sunscreen for your skin type and needs. We also provide a curated selection of the best medical-grade sunscreens available globally.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(8),
            ],

            // ──────────────── Post 4: Latest Treatments ────────────────
            [
                'category_id'    => $categories['latest-treatments']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'حقن البوتكس والفيلر: الفرق بينهما واستخداماتهما التجميلية',
                'title_en'       => 'Botox and Filler Injections: Differences and Cosmetic Uses',
                'slug'           => 'botox-filler-differences-uses',
                'featured_image' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80',
                'excerpt_ar'     => 'تعرفي على الفرق بين البوتكس والفيلر وأيهما يناسب احتياجاتك التجميلية للحصول على مظهر شبابي طبيعي.',
                'excerpt_en'     => 'Learn about the differences between Botox and fillers and which one suits your cosmetic needs for a natural, youthful appearance.',
                'content_ar'     => '<h2>مقدمة عن الحقن التجميلية</h2>
<p>الحقن التجميلية أصبحت من أكثر الإجراءات التجميلية شيوعاً في العالم لقدرتها على تجديد مظهر الوجه بدون جراحة. البوتكس والفيلر هما الأكثر استخداماً، لكن الكثير من الناس يخلطون بينهما. في هذا المقال، نوضح الفرق بينهما واستخدامات كل منهما.</p>

<h2>ما هو البوتكس؟</h2>
<p>البوتكس هو مادة بروتينية مُنقّاة تعمل على إرخاء العضلات المسؤولة عن التجاعيد التعبيرية. يُستخدم بشكل أساسي لعلاج:</p>
<ul>
<li>خطوط الجبهة الأفقية</li>
<li>التجاعيد بين الحاجبين (خطوط العبوس)</li>
<li>خطوط حول العينين (أقدام الغراب)</li>
<li>رفع الحواجب بشكل طبيعي</li>
<li>تقليل التعرق المفرط</li>
</ul>

<h2>ما هو الفيلر؟</h2>
<p>الفيلر هو مادة هلامية (عادةً حمض الهيالورونيك) تُحقن تحت الجلد لملء الفراغات وإعادة الحجم المفقود. يُستخدم لـ:</p>
<ul>
<li>نفخ الشفاه وتحديدها</li>
<li>ملء خطوط الابتسامة (الطيات الأنفية الشفوية)</li>
<li>نحت الفك والذقن</li>
<li>إعادة الحجم للخدود</li>
<li>تحسين مظهر الهالات السوداء</li>
</ul>

<h2>الفرق الرئيسي</h2>
<p><strong>البوتكس</strong> يعمل على العضلات لمنع التقلصات التي تسبب التجاعيد. <strong>الفيلر</strong> يعمل على ملء الفراغات وإضافة حجم. يمكن استخدامهما معاً للحصول على نتائج شاملة.</p>

<h2>مدة النتائج</h2>
<p>نتائج البوتكس تدوم من 4 إلى 6 أشهر، بينما تدوم نتائج الفيلر من 6 أشهر إلى سنتين حسب نوع الفيلر ومنطقة الحقن.</p>

<h2>هل الحقن آمنة؟</h2>
<p>نعم، الحقن التجميلية آمنة عندما تُجرى بواسطة طبيب متخصص ذو خبرة. في عيادة أورا ديرما، نستخدم فقط المنتجات المعتمدة عالمياً ونتبع أعلى معايير السلامة لضمان نتائج طبيعية وآمنة.</p>',

                'content_en'     => '<h2>Introduction to Cosmetic Injections</h2>
<p>Cosmetic injections have become one of the most common cosmetic procedures worldwide for their ability to rejuvenate facial appearance without surgery. Botox and fillers are the most widely used, but many people confuse them. In this article, we clarify the differences and uses of each.</p>

<h2>What Is Botox?</h2>
<p>Botox is a purified protein that works by relaxing the muscles responsible for expression wrinkles. It is primarily used to treat:</p>
<ul>
<li>Horizontal forehead lines</li>
<li>Frown lines between the eyebrows</li>
<li>Lines around the eyes (crow\'s feet)</li>
<li>Natural brow lifting</li>
<li>Reducing excessive sweating</li>
</ul>

<h2>What Are Fillers?</h2>
<p>Fillers are gel-like substances (usually hyaluronic acid) injected under the skin to fill gaps and restore lost volume. They are used for:</p>
<ul>
<li>Lip augmentation and definition</li>
<li>Filling smile lines (nasolabial folds)</li>
<li>Jawline and chin contouring</li>
<li>Restoring cheek volume</li>
<li>Improving the appearance of dark circles</li>
</ul>

<h2>The Key Difference</h2>
<p><strong>Botox</strong> works on muscles to prevent contractions that cause wrinkles. <strong>Fillers</strong> work by filling gaps and adding volume. They can be used together for comprehensive results.</p>

<h2>Duration of Results</h2>
<p>Botox results last 4 to 6 months, while filler results last from 6 months to 2 years depending on the type of filler and injection area.</p>

<h2>Are Injections Safe?</h2>
<p>Yes, cosmetic injections are safe when performed by an experienced, specialized physician. At AURA Derma Clinic, we use only globally approved products and follow the highest safety standards to ensure natural, safe results.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(12),
            ],

            // ──────────────── Post 5: Health Awareness ────────────────
            [
                'category_id'    => $categories['health-awareness']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'حب الشباب: أسبابه وأحدث طرق العلاج الفعّالة',
                'title_en'       => 'Acne: Causes and Latest Effective Treatment Methods',
                'slug'           => 'acne-causes-latest-treatments',
                'featured_image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                'excerpt_ar'     => 'حب الشباب مشكلة شائعة تؤثر على الثقة بالنفس. تعرفي على أسبابه وأحدث الطرق العلاجية المتاحة للتخلص منه نهائياً.',
                'excerpt_en'     => 'Acne is a common problem that affects self-confidence. Learn about its causes and the latest treatment methods available to eliminate it for good.',
                'content_ar'     => '<h2>ما هو حب الشباب؟</h2>
<p>حب الشباب هو حالة جلدية تحدث عندما تنسد بصيلات الشعر بالزيوت والخلايا الميتة، مما يؤدي إلى ظهور البثور والرؤوس السوداء والبيضاء. وعلى الرغم من أنه أكثر شيوعاً في مرحلة المراهقة، إلا أنه يمكن أن يصيب الأشخاص في أي عمر.</p>

<h2>الأسباب الرئيسية</h2>
<ul>
<li><strong>الهرمونات:</strong> التغيرات الهرمونية، خاصة خلال المراهقة والدورة الشهرية والحمل</li>
<li><strong>الوراثة:</strong> إذا كان أحد الوالدين يعاني من حب الشباب، فاحتمالية إصابتك أعلى</li>
<li><strong>الإفراط في إنتاج الزهم:</strong> الغدد الدهنية النشطة تنتج زيوتاً زائدة</li>
<li><strong>البكتيريا:</strong> بكتيريا P. acnes تتكاثر في المسام المسدودة وتسبب الالتهاب</li>
<li><strong>النظام الغذائي:</strong> بعض الأطعمة مثل منتجات الألبان والسكريات قد تزيد من حب الشباب</li>
<li><strong>التوتر:</strong> يزيد من إنتاج هرمون الكورتيزول الذي يحفز الغدد الدهنية</li>
</ul>

<h2>أنواع حب الشباب</h2>
<p>يتراوح حب الشباب من الخفيف إلى الشديد:</p>
<ul>
<li>الرؤوس السوداء والبيضاء (غير الملتهبة)</li>
<li>البثور الحمراء (الحطاطات)</li>
<li>البثور المليئة بالقيح (البثرات)</li>
<li>العقيدات والأكياس (الحالات الشديدة)</li>
</ul>

<h2>أحدث طرق العلاج في عيادة أورا ديرما</h2>
<p>نقدم في عيادتنا مجموعة شاملة من العلاجات المتقدمة:</p>
<ul>
<li><strong>التقشير الكيميائي:</strong> يزيل الطبقة السطحية ويفتح المسام المسدودة</li>
<li><strong>العلاج بالليزر:</strong> يقتل البكتيريا ويقلل الالتهاب ويحفز تجدد الجلد</li>
<li><strong>الميزوثيرابي:</strong> حقن فيتامينات ومضادات أكسدة مباشرة في البشرة</li>
<li><strong>العلاج الضوئي LED:</strong> أشعة زرقاء لقتل البكتيريا وحمراء لتقليل الالتهاب</li>
<li><strong>العلاجات الموضعية والفموية:</strong> بروتوكولات علاجية مخصصة لكل حالة</li>
</ul>

<h2>نصائح للوقاية</h2>
<ul>
<li>نظفي بشرتك مرتين يومياً بغسول مناسب</li>
<li>لا تعصري أو تضغطي على البثور</li>
<li>استخدمي مستحضرات تجميل خالية من الزيوت</li>
<li>غيري وسادتك بانتظام</li>
<li>قللي من التوتر واحصلي على نوم كافٍ</li>
</ul>

<p>لا تدعي حب الشباب يؤثر على ثقتك بنفسك. في عيادة أورا ديرما، نقدم خطط علاجية مخصصة تناسب حالتك. احجزي استشارتك اليوم.</p>',

                'content_en'     => '<h2>What Is Acne?</h2>
<p>Acne is a skin condition that occurs when hair follicles become clogged with oil and dead skin cells, leading to pimples, blackheads, and whiteheads. Although it is most common during adolescence, it can affect people at any age.</p>

<h2>Main Causes</h2>
<ul>
<li><strong>Hormones:</strong> Hormonal changes, especially during puberty, menstrual cycles, and pregnancy</li>
<li><strong>Genetics:</strong> If a parent had acne, your likelihood of developing it is higher</li>
<li><strong>Excess Sebum Production:</strong> Overactive sebaceous glands produce excess oil</li>
<li><strong>Bacteria:</strong> P. acnes bacteria multiply in clogged pores causing inflammation</li>
<li><strong>Diet:</strong> Some foods like dairy products and sugars may worsen acne</li>
<li><strong>Stress:</strong> Increases cortisol production which stimulates oil glands</li>
</ul>

<h2>Types of Acne</h2>
<p>Acne ranges from mild to severe:</p>
<ul>
<li>Blackheads and whiteheads (non-inflammatory)</li>
<li>Red bumps (papules)</li>
<li>Pus-filled bumps (pustules)</li>
<li>Nodules and cysts (severe cases)</li>
</ul>

<h2>Latest Treatment Methods at AURA Derma Clinic</h2>
<p>We offer a comprehensive range of advanced treatments at our clinic:</p>
<ul>
<li><strong>Chemical Peeling:</strong> Removes the surface layer and unclogs pores</li>
<li><strong>Laser Therapy:</strong> Kills bacteria, reduces inflammation, and stimulates skin renewal</li>
<li><strong>Mesotherapy:</strong> Direct injection of vitamins and antioxidants into the skin</li>
<li><strong>LED Light Therapy:</strong> Blue light to kill bacteria and red light to reduce inflammation</li>
<li><strong>Topical and Oral Treatments:</strong> Customized treatment protocols for each case</li>
</ul>

<h2>Prevention Tips</h2>
<ul>
<li>Cleanse your skin twice daily with an appropriate cleanser</li>
<li>Do not squeeze or pick at pimples</li>
<li>Use oil-free cosmetics</li>
<li>Change your pillowcase regularly</li>
<li>Reduce stress and get adequate sleep</li>
</ul>

<p>Don\'t let acne affect your confidence. At AURA Derma Clinic, we provide customized treatment plans that suit your condition. Book your consultation today.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(15),
            ],

            // ──────────────── Post 6: Latest Treatments ────────────────
            [
                'category_id'    => $categories['latest-treatments']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'إزالة الشعر بالليزر: الحل الدائم للشعر غير المرغوب فيه',
                'title_en'       => 'Laser Hair Removal: The Permanent Solution for Unwanted Hair',
                'slug'           => 'laser-hair-removal-permanent-solution',
                'featured_image' => 'https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80',
                'excerpt_ar'     => 'تخلصي من عناء إزالة الشعر التقليدية مع تقنية الليزر المتقدمة التي توفر نتائج دائمة وبشرة ناعمة كالحرير.',
                'excerpt_en'     => 'Say goodbye to traditional hair removal with advanced laser technology that provides permanent results and silky-smooth skin.',
                'content_ar'     => '<h2>لماذا إزالة الشعر بالليزر؟</h2>
<p>إذا كنتِ تعانين من عناء الحلاقة المتكررة، الشمع المؤلم، أو الكريمات المزيلة للشعر، فإن إزالة الشعر بالليزر هي الحل المثالي والدائم. تقنية الليزر الحديثة توفر تقليلاً دائماً لنمو الشعر بنسبة تصل إلى 90% بعد إتمام الجلسات الموصى بها.</p>

<h2>كيف يعمل الليزر؟</h2>
<p>يعمل الليزر عن طريق إرسال نبضات ضوئية مركزة تستهدف صبغة الميلانين في بصيلة الشعر. الحرارة الناتجة تدمر البصيلة وتمنعها من إنتاج شعر جديد، بينما تبقى البشرة المحيطة سليمة وآمنة.</p>

<h2>المناطق التي يمكن علاجها</h2>
<ul>
<li>الوجه (الشفة العلوية، الذقن، السوالف)</li>
<li>تحت الإبط</li>
<li>الذراعين والساقين</li>
<li>منطقة البكيني</li>
<li>الظهر والصدر</li>
<li>أي منطقة أخرى في الجسم</li>
</ul>

<h2>عدد الجلسات المطلوبة</h2>
<p>يحتاج معظم الأشخاص من 6 إلى 8 جلسات بفاصل 4 إلى 6 أسابيع بين كل جلسة. يعود ذلك لأن الشعر ينمو في دورات مختلفة، والليزر يكون فعّالاً فقط على الشعر في مرحلة النمو النشط.</p>

<h2>التحضير للجلسة</h2>
<ul>
<li>تجنبي التعرض للشمس قبل الجلسة بأسبوعين</li>
<li>احلقي المنطقة المراد علاجها قبل الجلسة بيوم</li>
<li>تجنبي نتف الشعر أو استخدام الشمع قبل الجلسة بـ 4 أسابيع</li>
<li>أخبري الطبيب عن أي أدوية تتناولينها</li>
</ul>

<h2>أحدث أجهزة الليزر في عيادة أورا ديرما</h2>
<p>نستخدم في عيادتنا أحدث أجهزة الليزر المعتمدة عالمياً والمناسبة لجميع أنواع البشرة، بما فيها البشرة الداكنة. أجهزتنا مزودة بأنظمة تبريد متقدمة لضمان راحتك أثناء الجلسة.</p>

<p>احجزي جلستك الأولى في عيادة أورا ديرما واستمتعي ببشرة ناعمة خالية من الشعر.</p>',

                'content_en'     => '<h2>Why Laser Hair Removal?</h2>
<p>If you\'re tired of frequent shaving, painful waxing, or depilatory creams, laser hair removal is the ideal, permanent solution. Modern laser technology provides a permanent reduction in hair growth of up to 90% after completing the recommended sessions.</p>

<h2>How Does Laser Work?</h2>
<p>The laser works by sending concentrated light pulses that target the melanin pigment in the hair follicle. The resulting heat destroys the follicle and prevents it from producing new hair, while the surrounding skin remains intact and safe.</p>

<h2>Areas That Can Be Treated</h2>
<ul>
<li>Face (upper lip, chin, sideburns)</li>
<li>Underarms</li>
<li>Arms and legs</li>
<li>Bikini area</li>
<li>Back and chest</li>
<li>Any other body area</li>
</ul>

<h2>Number of Sessions Required</h2>
<p>Most people need 6 to 8 sessions with 4 to 6 weeks between each session. This is because hair grows in different cycles, and the laser is only effective on hair in the active growth phase.</p>

<h2>Pre-Session Preparation</h2>
<ul>
<li>Avoid sun exposure two weeks before the session</li>
<li>Shave the treatment area one day before the session</li>
<li>Avoid plucking or waxing 4 weeks before the session</li>
<li>Inform your doctor about any medications you are taking</li>
</ul>

<h2>Latest Laser Devices at AURA Derma Clinic</h2>
<p>At our clinic, we use the latest globally approved laser devices suitable for all skin types, including darker skin tones. Our devices are equipped with advanced cooling systems to ensure your comfort during the session.</p>

<p>Book your first session at AURA Derma Clinic and enjoy smooth, hair-free skin.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(18),
            ],

            // ──────────────── Post 7: Health Awareness ────────────────
            [
                'category_id'    => $categories['health-awareness']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'التصبغات الجلدية: أنواعها وعلاجها للحصول على بشرة موحدة اللون',
                'title_en'       => 'Skin Pigmentation: Types and Treatments for Even-Toned Skin',
                'slug'           => 'skin-pigmentation-types-treatments',
                'featured_image' => 'https://images.unsplash.com/photo-1505944270255-72b8c68c6a70?w=800&q=80',
                'excerpt_ar'     => 'التصبغات الجلدية من أكثر المشاكل شيوعاً. تعرفي على أنواعها وأسبابها وأحدث العلاجات للحصول على بشرة صافية وموحدة.',
                'excerpt_en'     => 'Skin pigmentation is one of the most common skin concerns. Learn about its types, causes, and latest treatments for clear, even-toned skin.',
                'content_ar'     => '<h2>ما هي التصبغات الجلدية؟</h2>
<p>التصبغات الجلدية هي مناطق داكنة أو فاتحة تظهر على البشرة نتيجة زيادة أو نقصان في إنتاج صبغة الميلانين. تُعد من أكثر المشاكل الجلدية شيوعاً وتؤثر على مظهر البشرة بشكل ملحوظ.</p>

<h2>أنواع التصبغات الشائعة</h2>
<ul>
<li><strong>الكلف:</strong> بقع بنية تظهر عادةً على الوجه، شائعة عند النساء خاصةً أثناء الحمل</li>
<li><strong>النمش:</strong> بقع صغيرة بنية فاتحة تظهر في المناطق المعرضة للشمس</li>
<li><strong>فرط التصبغ التالي للالتهاب:</strong> بقع داكنة تظهر بعد التهاب الجلد أو حب الشباب</li>
<li><strong>البقع الشمسية:</strong> بقع داكنة تظهر مع التقدم في العمر في المناطق المعرضة للشمس</li>
</ul>

<h2>الأسباب</h2>
<p>تتعدد أسباب التصبغات وتشمل:</p>
<ul>
<li>التعرض المفرط لأشعة الشمس دون حماية</li>
<li>التغيرات الهرمونية (الحمل، موانع الحمل)</li>
<li>الالتهابات والإصابات الجلدية</li>
<li>بعض الأدوية التي تزيد حساسية الجلد للشمس</li>
<li>العوامل الوراثية</li>
</ul>

<h2>العلاجات المتقدمة في أورا ديرما</h2>
<p>نقدم مجموعة متكاملة من العلاجات المتخصصة:</p>
<ul>
<li><strong>التقشير الكيميائي:</strong> يزيل الطبقات السطحية المتصبغة ويكشف عن بشرة جديدة</li>
<li><strong>ليزر تفتيح البشرة:</strong> يستهدف خلايا الميلانين بدقة عالية</li>
<li><strong>الميزوثيرابي بالفيتامينات:</strong> حقن مواد تفتيح مثل فيتامين C والجلوتاثيون</li>
<li><strong>كريمات التفتيح الطبية:</strong> بروتوكولات علاجية منزلية مخصصة</li>
<li><strong>تقنية الميكرونيدلينج:</strong> تحفز إنتاج الكولاجين وتساعد على تجدد البشرة</li>
</ul>

<h2>الوقاية من التصبغات</h2>
<ul>
<li>استخدام واقي شمس يومياً بعامل حماية 50 أو أعلى</li>
<li>ارتداء قبعة ونظارات شمسية عند الخروج</li>
<li>تجنب التعرض المباشر للشمس وقت الذروة</li>
<li>استخدام منتجات تحتوي على فيتامين C ومضادات الأكسدة</li>
</ul>

<p>في عيادة أورا ديرما، نصمم خطة علاج مخصصة لكل حالة للحصول على أفضل النتائج. احجزي موعدك لاستشارة مجانية.</p>',

                'content_en'     => '<h2>What Is Skin Pigmentation?</h2>
<p>Skin pigmentation refers to dark or light areas that appear on the skin due to increased or decreased melanin production. It is one of the most common skin concerns and significantly affects skin appearance.</p>

<h2>Common Types of Pigmentation</h2>
<ul>
<li><strong>Melasma:</strong> Brown patches usually on the face, common in women especially during pregnancy</li>
<li><strong>Freckles:</strong> Small light-brown spots in sun-exposed areas</li>
<li><strong>Post-inflammatory Hyperpigmentation:</strong> Dark spots after skin inflammation or acne</li>
<li><strong>Sun Spots:</strong> Dark patches that develop with age in sun-exposed areas</li>
</ul>

<h2>Causes</h2>
<p>Pigmentation has multiple causes including:</p>
<ul>
<li>Excessive sun exposure without protection</li>
<li>Hormonal changes (pregnancy, birth control)</li>
<li>Inflammation and skin injuries</li>
<li>Certain medications that increase sun sensitivity</li>
<li>Genetic factors</li>
</ul>

<h2>Advanced Treatments at AURA Derma</h2>
<p>We offer a comprehensive range of specialized treatments:</p>
<ul>
<li><strong>Chemical Peeling:</strong> Removes pigmented surface layers to reveal new skin</li>
<li><strong>Skin Lightening Laser:</strong> Precisely targets melanin cells</li>
<li><strong>Vitamin Mesotherapy:</strong> Injections of lightening agents like Vitamin C and Glutathione</li>
<li><strong>Medical Lightening Creams:</strong> Customized at-home treatment protocols</li>
<li><strong>Microneedling:</strong> Stimulates collagen production and helps skin renewal</li>
</ul>

<h2>Preventing Pigmentation</h2>
<ul>
<li>Use daily sunscreen with SPF 50 or higher</li>
<li>Wear a hat and sunglasses when going outdoors</li>
<li>Avoid direct sun exposure during peak hours</li>
<li>Use products containing Vitamin C and antioxidants</li>
</ul>

<p>At AURA Derma Clinic, we design a customized treatment plan for each case to achieve the best results. Book your appointment for a free consultation.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(22),
            ],

            // ──────────────── Post 8: Q&A ────────────────
            [
                'category_id'    => $categories['qa']->id ?? null,
                'author_id'      => $doctor->id ?? null,
                'title_ar'       => 'أكثر 10 أسئلة شيوعاً عن العناية بالبشرة والتجميل',
                'title_en'       => 'Top 10 Most Common Questions About Skincare and Aesthetics',
                'slug'           => 'top-10-skincare-questions-answers',
                'featured_image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',
                'excerpt_ar'     => 'إجابات شاملة على أكثر الأسئلة التي يطرحها مرضانا حول العناية بالبشرة وإجراءات التجميل والعلاجات المتقدمة.',
                'excerpt_en'     => 'Comprehensive answers to the most frequently asked questions by our patients about skincare, cosmetic procedures, and advanced treatments.',
                'content_ar'     => '<h2>1. ما هو أفضل عمر لبدء العناية بالبشرة؟</h2>
<p>لا يوجد عمر محدد، لكن ننصح ببدء روتين أساسي (تنظيف + ترطيب + واقي شمس) من عمر المراهقة. العلاجات المتقدمة مثل البوتكس والفيلر يُفضل البدء بها من منتصف العشرينيات كإجراء وقائي.</p>

<h2>2. هل المنتجات الطبيعية أفضل للبشرة؟</h2>
<p>ليس بالضرورة. العديد من المنتجات الطبيعية قد تسبب حساسية أو تهيجاً. المنتجات الطبية المُختبرة سريرياً تكون أكثر فعالية وأماناً. الأهم هو اختيار منتجات تناسب نوع بشرتك بإشراف طبيب متخصص.</p>

<h2>3. كم مرة يجب زيارة طبيب الجلدية؟</h2>
<p>ننصح بزيارة طبيب الجلدية مرة على الأقل سنوياً لفحص البشرة العام. إذا كانت لديك مشاكل جلدية محددة، قد تحتاجين لزيارات أكثر تكراراً حسب خطة العلاج.</p>

<h2>4. هل البوتكس يجمد الوجه؟</h2>
<p>لا، إذا تم حقنه بالجرعة الصحيحة وبواسطة طبيب ماهر. في عيادة أورا ديرما، نستخدم تقنيات حقن دقيقة تحافظ على التعبيرات الطبيعية للوجه مع تقليل التجاعيد.</p>

<h2>5. هل الليزر مؤلم؟</h2>
<p>معظم أجهزة الليزر الحديثة مزودة بأنظمة تبريد تجعل الجلسة مريحة. قد تشعرين ببعض الوخز الخفيف، لكنه محتمل ولا يحتاج لتخدير في معظم الحالات.</p>

<h2>6. ما الفرق بين التقشير الكيميائي والتقشير بالليزر؟</h2>
<p>التقشير الكيميائي يستخدم أحماض لإزالة الطبقات السطحية، وهو مناسب للتصبغات الخفيفة وتحسين الملمس. التقشير بالليزر أكثر دقة ويمكنه التعامل مع مشاكل أعمق مثل الندبات والتجاعيد العميقة.</p>

<h2>7. هل يمكنني وضع المكياج بعد الجلسات؟</h2>
<p>يعتمد على نوع الجلسة. بعد الهيدرافيشيال يمكنك وضع المكياج فوراً. بعد التقشير أو الليزر، ننصح بالانتظار 24-48 ساعة واستخدام مستحضرات لطيفة على البشرة.</p>

<h2>8. ما هي أفضل الفيتامينات للبشرة؟</h2>
<p>فيتامين C لتفتيح البشرة ومقاومة الأكسدة، فيتامين E للترطيب والحماية، فيتامين A (الريتينول) لمكافحة الشيخوخة، وفيتامين B3 (النياسيناميد) لتنظيم إفراز الدهون وتضييق المسام.</p>

<h2>9. هل العلاجات التجميلية آمنة أثناء الحمل؟</h2>
<p>ننصح بتجنب معظم العلاجات التجميلية أثناء الحمل والرضاعة كإجراء احترازي. يمكنك الاستمرار في الروتين الأساسي (تنظيف، ترطيب، واقي شمس) مع تجنب الريتينول وبعض الأحماض.</p>

<h2>10. كيف أختار العيادة المناسبة؟</h2>
<p>ابحثي عن عيادة مرخصة بأطباء متخصصين ذوي خبرة، تستخدم أجهزة ومنتجات معتمدة عالمياً، وتقدم استشارة شاملة قبل أي إجراء. في عيادة أورا ديرما، نفخر بتوفير كل هذه المعايير مع اهتمام شخصي بكل مريضة.</p>',

                'content_en'     => '<h2>1. What Is the Best Age to Start Skincare?</h2>
<p>There is no specific age, but we recommend starting a basic routine (cleanse + moisturize + sunscreen) from teenage years. Advanced treatments like Botox and fillers are best started in your mid-twenties as a preventive measure.</p>

<h2>2. Are Natural Products Better for Skin?</h2>
<p>Not necessarily. Many natural products can cause allergies or irritation. Clinically tested medical products are more effective and safer. What matters most is choosing products that suit your skin type under the guidance of a specialist.</p>

<h2>3. How Often Should I Visit a Dermatologist?</h2>
<p>We recommend visiting a dermatologist at least once a year for a general skin examination. If you have specific skin concerns, you may need more frequent visits depending on your treatment plan.</p>

<h2>4. Does Botox Freeze Your Face?</h2>
<p>No, not when injected in the right dosage by a skilled physician. At AURA Derma Clinic, we use precise injection techniques that preserve natural facial expressions while reducing wrinkles.</p>

<h2>5. Is Laser Treatment Painful?</h2>
<p>Most modern laser devices are equipped with cooling systems that make the session comfortable. You may feel slight tingling, but it is tolerable and does not require anesthesia in most cases.</p>

<h2>6. What Is the Difference Between Chemical and Laser Peeling?</h2>
<p>Chemical peeling uses acids to remove surface layers and is suitable for mild pigmentation and texture improvement. Laser peeling is more precise and can address deeper issues like scars and deep wrinkles.</p>

<h2>7. Can I Wear Makeup After Treatment Sessions?</h2>
<p>It depends on the type of session. After HydraFacial, you can apply makeup immediately. After peeling or laser, we recommend waiting 24-48 hours and using gentle products on the skin.</p>

<h2>8. What Are the Best Vitamins for Skin?</h2>
<p>Vitamin C for brightening and antioxidant protection, Vitamin E for hydration and protection, Vitamin A (Retinol) for anti-aging, and Vitamin B3 (Niacinamide) for oil regulation and pore tightening.</p>

<h2>9. Are Cosmetic Treatments Safe During Pregnancy?</h2>
<p>We recommend avoiding most cosmetic treatments during pregnancy and breastfeeding as a precautionary measure. You can continue your basic routine (cleanse, moisturize, sunscreen) while avoiding retinol and certain acids.</p>

<h2>10. How Do I Choose the Right Clinic?</h2>
<p>Look for a licensed clinic with experienced specialist doctors, using globally approved devices and products, offering a comprehensive consultation before any procedure. At AURA Derma Clinic, we are proud to provide all these standards with personal attention to every patient.</p>',
                'status'         => 'published',
                'is_featured'    => false,
                'published_at'   => Carbon::now()->subDays(25),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => $post['slug']],
                $post
            );
        }
    }
}
