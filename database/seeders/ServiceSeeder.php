<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    /**
     * Seed service categories and services.
     */
    public function run(): void
    {
        // ── Service Categories ──────────────────────────────────────────
        $categories = [
            [
                'name_ar'       => 'خدمات الجلدية',
                'name_en'       => 'Dermatology',
                'slug'          => 'dermatology',
                'display_order' => 1,
            ],
            [
                'name_ar'       => 'إزالة الشعر بالليزر',
                'name_en'       => 'Laser Hair Removal',
                'slug'          => 'laser',
                'display_order' => 2,
            ],
            [
                'name_ar'       => 'العناية بالبشرة والتجميل',
                'name_en'       => 'Skincare & Aesthetics',
                'slug'          => 'skincare',
                'display_order' => 3,
            ],
            [
                'name_ar'       => 'العلاجات التجديدية',
                'name_en'       => 'Regenerative Treatments',
                'slug'          => 'regenerative',
                'display_order' => 4,
            ],
            [
                'name_ar'       => 'فيلر',
                'name_en'       => 'Filler',
                'slug'          => 'filler',
                'display_order' => 5,
            ],
            [
                'name_ar'       => 'بوتوكس',
                'name_en'       => 'Botox',
                'slug'          => 'botox',
                'display_order' => 6,
            ],
            [
                'name_ar'       => 'سكن بوسترز',
                'name_en'       => 'Skin Boosters',
                'slug'          => 'skin-boosters',
                'display_order' => 7,
            ],
            [
                'name_ar'       => 'ديرمابن',
                'name_en'       => 'Dermapen',
                'slug'          => 'dermapen',
                'display_order' => 8,
            ],
            [
                'name_ar'       => 'تقشير',
                'name_en'       => 'Peeling',
                'slug'          => 'peeling',
                'display_order' => 9,
            ],
            [
                'name_ar'       => 'هيدرافيشل',
                'name_en'       => 'Hydrafacial',
                'slug'          => 'hydrafacial',
                'display_order' => 10,
            ],
        ];

        $categoryMap = [];
        foreach ($categories as $cat) {
            $created = ServiceCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
            $categoryMap[$cat['slug']] = $created->id;
        }

        // ── Services ────────────────────────────────────────────────────
        $services = [
            // ── Dermatology ─────────────────────────────────────────────
            [
                'category'       => 'dermatology',
                'name_ar'        => 'علاج حب الشباب وآثاره',
                'name_en'        => 'Acne Treatment',
                'short_desc_ar'  => 'علاج شامل لحب الشباب بمختلف درجاته وإزالة آثاره باستخدام أحدث التقنيات الطبية. نقدم بروتوكولات علاجية مخصصة تشمل التقشير والليزر والأدوية الموضعية للحصول على بشرة صافية ونقية.',
                'short_desc_en'  => 'Comprehensive acne treatment for all severity levels using the latest medical technologies. We offer customized treatment protocols including peeling, laser, and topical medications for clear, radiant skin.',
                'featured_image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو علاج حب الشباب؟</h2>
<p>حب الشباب هو من أكثر المشاكل الجلدية شيوعاً ويصيب مختلف الفئات العمرية. في عيادة دكتوراتو، نقدم بروتوكولات علاجية متكاملة تستهدف أسباب حب الشباب من جذوره باستخدام أحدث التقنيات والأجهزة الطبية المعتمدة عالمياً.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يبدأ العلاج بتشخيص دقيق لنوع حب الشباب ودرجته، ثم يتم وضع خطة علاجية مخصصة قد تشمل التقشير الكيميائي، العلاج بالليزر، الأدوية الموضعية والفموية، وجلسات التنظيف العميق. نعمل على علاج الحبوب النشطة ومنع ظهورها مجدداً وإزالة آثارها.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>المراهقون والشباب الذين يعانون من حب الشباب</li>
<li>البالغون الذين يعانون من حب الشباب الهرموني</li>
<li>من يعانون من آثار وندبات حب الشباب القديمة</li>
<li>أصحاب البشرة الدهنية المعرضة لظهور الحبوب</li>
</ul>',
                'full_desc_en'   => '<h2>What is Acne Treatment?</h2>
<p>Acne is one of the most common skin conditions affecting people of all ages. At Doctorato Polyclinic, we offer comprehensive treatment protocols that target the root causes of acne using the latest internationally approved medical technologies and devices.</p>
<h2>How Does the Treatment Work?</h2>
<p>Treatment begins with an accurate diagnosis of the acne type and severity, followed by a customized treatment plan that may include chemical peeling, laser therapy, topical and oral medications, and deep cleansing sessions. We work to treat active breakouts, prevent recurrence, and remove existing scars.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Teenagers and young adults suffering from acne</li>
<li>Adults with hormonal acne</li>
<li>Those with old acne scars and marks</li>
<li>People with oily, acne-prone skin</li>
</ul>',
                'benefits_ar'    => "تقليل ظهور حب الشباب بشكل ملحوظ\nإزالة آثار وندبات حب الشباب القديمة\nتنظيم إفراز الدهون في البشرة\nتحسين ملمس البشرة ونعومتها\nاستعادة الثقة بالنفس والمظهر",
                'benefits_en'    => "Significant reduction in acne breakouts\nRemoval of old acne scars and marks\nRegulation of skin oil production\nImproved skin texture and smoothness\nRestored self-confidence and appearance",
                'results_ar'     => "انخفاض ملحوظ في الحبوب خلال أول أسبوعين\nتحسن واضح في ملمس البشرة بعد 4-6 جلسات\nتلاشي الندبات والآثار بعد إكمال البرنامج العلاجي\nبشرة أكثر صفاءً وإشراقاً",
                'results_en'     => "Noticeable reduction in breakouts within the first two weeks\nClear improvement in skin texture after 4-6 sessions\nFading of scars and marks after completing the treatment program\nClearer, more radiant skin",
                'sessions_count' => 6,
                'show_on_home'   => true,
                                'supply_cost'                  => 80,
                'medical_fee'                  => 420,
                'price_after_discount'         => null,
                'default_sessions'             => 6,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 35,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 1,
            ],
            [
                'category'       => 'dermatology',
                'name_ar'        => 'علاج التصبغات والبقع الداكنة',
                'name_en'        => 'Pigmentation Treatment',
                'short_desc_ar'  => 'علاج فعّال للتصبغات الجلدية والبقع الداكنة والكلف باستخدام تقنيات متقدمة. نعمل على توحيد لون البشرة واستعادة إشراقتها الطبيعية من خلال جلسات متخصصة.',
                'short_desc_en'  => 'Effective treatment for skin pigmentation, dark spots, and melasma using advanced techniques. We work to even out skin tone and restore its natural radiance through specialized sessions.',
                'featured_image' => 'https://images.unsplash.com/photo-1505944270255-72b8c68c6a70?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو علاج التصبغات؟</h2>
<p>التصبغات الجلدية والبقع الداكنة تنتج عن زيادة إنتاج الميلانين في مناطق معينة من الجلد بسبب التعرض لأشعة الشمس أو التغيرات الهرمونية أو الالتهابات. في عيادة دكتوراتو، نستخدم أحدث التقنيات لعلاج جميع أنواع التصبغات بما فيها الكلف والنمش وبقع الشمس.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>نعتمد على مزيج من العلاجات المتقدمة تشمل التقشير الكيميائي المتخصص، وأجهزة الليزر الحديثة، والكريمات الطبية الموضعية. يتم تصميم خطة العلاج بناءً على نوع التصبغ وعمقه ونوع البشرة لضمان أفضل النتائج مع الحفاظ على سلامة الجلد.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من الكلف الناتج عن التغيرات الهرمونية</li>
<li>أصحاب البقع الداكنة الناتجة عن أشعة الشمس</li>
<li>من يعانون من تصبغات ما بعد الالتهابات</li>
<li>الراغبون في توحيد لون البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Pigmentation Treatment?</h2>
<p>Skin pigmentation and dark spots result from excess melanin production in certain areas of the skin due to sun exposure, hormonal changes, or inflammation. At Doctorato Polyclinic, we use the latest technologies to treat all types of pigmentation including melasma, freckles, and sunspots.</p>
<h2>How Does the Treatment Work?</h2>
<p>We rely on a combination of advanced treatments including specialized chemical peels, modern laser devices, and topical medical creams. The treatment plan is designed based on the type and depth of pigmentation and skin type to ensure the best results while maintaining skin safety.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those suffering from melasma caused by hormonal changes</li>
<li>People with sun-induced dark spots</li>
<li>Those with post-inflammatory hyperpigmentation</li>
<li>Anyone seeking to even out their skin tone</li>
</ul>',
                'benefits_ar'    => "توحيد لون البشرة وتفتيح البقع الداكنة\nعلاج الكلف العميق والسطحي\nتحسين إشراقة البشرة ونضارتها\nتقليل ظهور النمش وبقع الشمس\nحماية البشرة من التصبغات المستقبلية",
                'benefits_en'    => "Even skin tone and lightening of dark spots\nTreatment of deep and superficial melasma\nImproved skin radiance and glow\nReduction of freckles and sunspots\nProtection against future pigmentation",
                'results_ar'     => "تفتيح ملحوظ للبقع الداكنة بعد 3-4 جلسات\nتوحيد لون البشرة بشكل تدريجي\nبشرة أكثر إشراقاً ونضارة\nنتائج مستدامة مع العناية المنزلية المناسبة",
                'results_en'     => "Noticeable lightening of dark spots after 3-4 sessions\nGradual evening of skin tone\nBrighter, more radiant skin\nLong-lasting results with proper home care",
                'sessions_count' => 5,
                'show_on_home'   => false,
                                'supply_cost'                  => 120,
                'medical_fee'                  => 580,
                'price_after_discount'         => 550,
                'default_sessions'             => 5,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 35,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 2,
            ],
            [
                'category'       => 'dermatology',
                'name_ar'        => 'فحص الجلد بمنظار الجلد',
                'name_en'        => 'Dermoscopy Examination',
                'short_desc_ar'  => 'فحص دقيق للآفات الجلدية والشامات باستخدام منظار الجلد الرقمي عالي الدقة. يساعد الفحص في التشخيص المبكر للأمراض الجلدية ومتابعة التغيرات الجلدية بشكل دوري.',
                'short_desc_en'  => 'Precise examination of skin lesions and moles using high-resolution digital dermoscopy. This examination aids in early diagnosis of skin conditions and periodic monitoring of skin changes.',
                'featured_image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو فحص منظار الجلد؟</h2>
<p>فحص الديرموسكوبي هو تقنية تشخيصية غير جراحية تستخدم جهاز منظار الجلد الرقمي عالي الدقة لفحص الآفات الجلدية والشامات بتكبير يصل إلى 200 ضعف. يتيح هذا الفحص رؤية تفاصيل دقيقة غير مرئية بالعين المجردة مما يساعد في التشخيص الدقيق والمبكر.</p>
<h2>كيف يتم الفحص؟</h2>
<p>يقوم الطبيب المختص بتوجيه جهاز الديرموسكوب على المنطقة المراد فحصها لالتقاط صور مكبرة عالية الدقة. يتم تحليل الصور لتقييم بنية الآفة الجلدية ولونها ونمطها. الفحص غير مؤلم ولا يستغرق وقتاً طويلاً ويعتبر أداة أساسية في الكشف المبكر عن سرطان الجلد.</p>
<h2>لمن يناسب هذا الفحص؟</h2>
<ul>
<li>من لديهم شامات متعددة أو متغيرة الشكل</li>
<li>الأشخاص المعرضون لخطر سرطان الجلد</li>
<li>من يلاحظون تغيرات في لون أو شكل الآفات الجلدية</li>
<li>المتابعة الدورية لصحة الجلد</li>
</ul>',
                'full_desc_en'   => '<h2>What is Dermoscopy Examination?</h2>
<p>Dermoscopy is a non-invasive diagnostic technique that uses a high-resolution digital dermatoscope to examine skin lesions and moles at up to 200x magnification. This examination reveals fine details invisible to the naked eye, enabling accurate and early diagnosis.</p>
<h2>How Is the Examination Done?</h2>
<p>The specialist directs the dermatoscope over the area to be examined, capturing high-resolution magnified images. The images are analyzed to assess the structure, color, and pattern of the skin lesion. The examination is painless, quick, and is considered an essential tool in early skin cancer detection.</p>
<h2>Who Is This Examination For?</h2>
<ul>
<li>Those with multiple or changing moles</li>
<li>People at higher risk of skin cancer</li>
<li>Those noticing changes in color or shape of skin lesions</li>
<li>Periodic skin health monitoring</li>
</ul>',
                'benefits_ar'    => "تشخيص مبكر ودقيق للأمراض الجلدية\nفحص غير جراحي وغير مؤلم\nمتابعة دورية لتغيرات الشامات\nالكشف المبكر عن سرطان الجلد\nتوثيق رقمي للحالة الجلدية",
                'benefits_en'    => "Early and accurate diagnosis of skin conditions\nNon-invasive and painless examination\nPeriodic monitoring of mole changes\nEarly detection of skin cancer\nDigital documentation of skin condition",
                'results_ar'     => "تقرير تشخيصي فوري ودقيق\nخطة متابعة مخصصة للحالة\nراحة البال من خلال الكشف المبكر\nتوثيق رقمي للمقارنة في الزيارات المستقبلية",
                'results_en'     => "Immediate and accurate diagnostic report\nCustomized follow-up plan for the condition\nPeace of mind through early detection\nDigital documentation for comparison in future visits",
                'sessions_count' => 1,
                'show_on_home'   => false,
                                'supply_cost'                  => 10,
                'medical_fee'                  => 290,
                'price_after_discount'         => null,
                'default_sessions'             => 1,
                'session_duration_minutes'     => 20,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 3,
            ],
            [
                'category'       => 'dermatology',
                'name_ar'        => 'جهاز اكزيمر لعلاج الصدفية والبهاق والثعلبة',
                'name_en'        => 'Excimer Laser',
                'short_desc_ar'  => 'علاج متقدم بجهاز الإكزيمر ليزر لحالات الصدفية والبهاق والثعلبة. يعمل الجهاز على تحفيز الخلايا الصبغية وتقليل الالتهابات بدقة عالية دون التأثير على الأنسجة المحيطة.',
                'short_desc_en'  => 'Advanced treatment with Excimer laser for psoriasis, vitiligo, and alopecia areata. The device stimulates pigment cells and reduces inflammation with high precision without affecting surrounding tissues.',
                'featured_image' => 'https://images.unsplash.com/photo-1609840114035-3c981b782dfe?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو جهاز الإكزيمر ليزر؟</h2>
<p>جهاز الإكزيمر ليزر هو تقنية علاجية متطورة تستخدم أشعة فوق بنفسجية مركزة بطول موجي 308 نانومتر لعلاج الأمراض الجلدية المزمنة مثل الصدفية والبهاق والثعلبة. يتميز الجهاز بدقته العالية في استهداف المناطق المصابة فقط دون التأثير على الجلد السليم المحيط.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يقوم الجهاز بتوجيه شعاع مركز من الأشعة فوق البنفسجية إلى المناطق المصابة، مما يحفز الخلايا الصبغية (الميلانوسايت) في حالات البهاق، ويثبط الجهاز المناعي الموضعي في حالات الصدفية والثعلبة. يتم تحديد عدد الجلسات وشدة الأشعة بناءً على نوع المرض ومساحة المنطقة المصابة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>مرضى البهاق بمختلف درجاته</li>
<li>مرضى الصدفية المحدودة المساحة</li>
<li>من يعانون من الثعلبة</li>
<li>حالات الأكزيما المزمنة المقاومة للعلاج التقليدي</li>
</ul>',
                'full_desc_en'   => '<h2>What is Excimer Laser?</h2>
<p>The Excimer laser is an advanced therapeutic technology that uses focused ultraviolet light at a wavelength of 308 nanometers to treat chronic skin conditions such as psoriasis, vitiligo, and alopecia areata. The device is distinguished by its high precision in targeting only affected areas without impacting the surrounding healthy skin.</p>
<h2>How Does the Treatment Work?</h2>
<p>The device directs a focused beam of ultraviolet light to the affected areas, stimulating pigment cells (melanocytes) in vitiligo cases and suppressing the local immune response in psoriasis and alopecia areata. The number of sessions and light intensity are determined based on the condition type and affected area size.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Vitiligo patients of all severity levels</li>
<li>Patients with limited-area psoriasis</li>
<li>Those suffering from alopecia areata</li>
<li>Chronic eczema cases resistant to conventional treatment</li>
</ul>',
                'benefits_ar'    => "علاج دقيق يستهدف المناطق المصابة فقط\nلا يؤثر على الأنسجة السليمة المحيطة\nنتائج فعالة في إعادة التصبغ للبهاق\nتقليل الالتهابات والحكة في الصدفية\nعلاج آمن ومعتمد عالمياً",
                'benefits_en'    => "Precise treatment targeting only affected areas\nNo impact on surrounding healthy tissue\nEffective results in re-pigmentation for vitiligo\nReduction of inflammation and itching in psoriasis\nSafe and internationally approved treatment",
                'results_ar'     => "بداية ظهور التصبغ في مناطق البهاق بعد 6-8 جلسات\nتحسن ملحوظ في حالات الصدفية بعد 4-6 جلسات\nبدء نمو الشعر في مناطق الثعلبة\nنتائج تراكمية تتحسن مع استمرار الجلسات",
                'results_en'     => "Pigmentation begins appearing in vitiligo areas after 6-8 sessions\nNoticeable improvement in psoriasis cases after 4-6 sessions\nHair regrowth begins in alopecia areata areas\nCumulative results that improve with continued sessions",
                'sessions_count' => 8,
                'show_on_home'   => false,
                                'supply_cost'                  => 50,
                'medical_fee'                  => 550,
                'price_after_discount'         => 500,
                'default_sessions'             => 8,
                'session_duration_minutes'     => 20,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 4,
            ],
            [
                'category'       => 'dermatology',
                'name_ar'        => 'الفحص بالأشعة فوق البنفسجية',
                'name_en'        => "Wood's Light Examination",
                'short_desc_ar'  => 'فحص تشخيصي متخصص باستخدام ضوء وود للكشف عن الأمراض الجلدية الفطرية والبكتيرية والتصبغات غير المرئية. أداة تشخيصية أساسية لتحديد نوع المرض الجلدي بدقة.',
                'short_desc_en'  => "Specialized diagnostic examination using Wood's light to detect fungal, bacterial skin diseases, and invisible pigmentation. An essential diagnostic tool for accurately identifying skin conditions.",
                'featured_image' => 'https://images.unsplash.com/photo-1551190822-a9ce113ac100?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو فحص ضوء وود؟</h2>
<p>فحص ضوء وود هو أداة تشخيصية تستخدم الأشعة فوق البنفسجية ذات الطول الموجي الطويل لفحص الجلد في غرفة مظلمة. يساعد هذا الفحص في الكشف عن العديد من الأمراض الجلدية التي قد لا تكون مرئية تحت الإضاءة العادية، حيث تظهر الآفات الجلدية المختلفة بألوان مميزة تحت ضوء وود.</p>
<h2>كيف يتم الفحص؟</h2>
<p>يتم إجراء الفحص في غرفة مظلمة حيث يوجه الطبيب ضوء وود على المنطقة المراد فحصها. تظهر الآفات الفطرية بلون فلوري مميز، بينما تظهر التصبغات بدرجات مختلفة حسب عمقها. يساعد الفحص في التمييز بين أنواع التصبغات والأمراض الفطرية والبكتيرية بدقة عالية.</p>
<h2>لمن يناسب هذا الفحص؟</h2>
<ul>
<li>من يشتبه في إصابتهم بأمراض فطرية جلدية</li>
<li>تشخيص أنواع التصبغات وتحديد عمقها</li>
<li>الكشف عن الإصابات البكتيرية الجلدية</li>
<li>تقييم حالات البهاق وتحديد مداها</li>
</ul>',
                'full_desc_en'   => '<h2>What is Wood\'s Light Examination?</h2>
<p>Wood\'s light examination is a diagnostic tool that uses long-wave ultraviolet light to examine the skin in a dark room. This examination helps detect numerous skin conditions that may not be visible under normal lighting, as different skin lesions appear in distinctive colors under Wood\'s light.</p>
<h2>How Is the Examination Done?</h2>
<p>The examination is performed in a dark room where the doctor directs the Wood\'s light onto the area to be examined. Fungal lesions appear in a distinctive fluorescent color, while pigmentation shows in varying degrees depending on its depth. The examination helps accurately distinguish between types of pigmentation and fungal and bacterial diseases.</p>
<h2>Who Is This Examination For?</h2>
<ul>
<li>Those suspected of having fungal skin infections</li>
<li>Diagnosing types of pigmentation and determining their depth</li>
<li>Detecting bacterial skin infections</li>
<li>Evaluating vitiligo cases and determining their extent</li>
</ul>',
                'benefits_ar'    => "تشخيص سريع ودقيق للأمراض الجلدية\nفحص غير مؤلم وآمن تماماً\nتحديد نوع التصبغ وعمقه بدقة\nالكشف عن الإصابات الفطرية غير المرئية\nمساعدة في وضع خطة العلاج المناسبة",
                'benefits_en'    => "Quick and accurate diagnosis of skin conditions\nCompletely painless and safe examination\nPrecise determination of pigmentation type and depth\nDetection of invisible fungal infections\nAssistance in developing the appropriate treatment plan",
                'results_ar'     => "تقرير تشخيصي فوري وشامل\nتحديد نوع المرض الجلدي بدقة\nوضع خطة علاجية مبنية على تشخيص دقيق\nمتابعة تطور الحالة المرضية",
                'results_en'     => "Immediate and comprehensive diagnostic report\nAccurate identification of the skin condition type\nDevelopment of a treatment plan based on accurate diagnosis\nMonitoring of condition progression",
                'sessions_count' => 1,
                'show_on_home'   => false,
                                'supply_cost'                  => 5,
                'medical_fee'                  => 195,
                'price_after_discount'         => null,
                'default_sessions'             => 1,
                'session_duration_minutes'     => 15,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 5,
            ],
            [
                'category'       => 'dermatology',
                'name_ar'        => 'علاج السنط بالأمصال والكي',
                'name_en'        => 'Wart Treatment',
                'short_desc_ar'  => 'علاج فعّال للسنط (الثآليل) باستخدام تقنيات متعددة تشمل الأمصال المناعية والكي الكهربائي والتبريد. نختار العلاج الأنسب حسب نوع وحجم وموقع السنط.',
                'short_desc_en'  => 'Effective wart treatment using multiple techniques including immunotherapy serums, electrocautery, and cryotherapy. We select the most suitable treatment based on the type, size, and location of the wart.',
                'featured_image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو علاج السنط؟</h2>
<p>السنط أو الثآليل هي نتوءات جلدية تسببها فيروسات الورم الحليمي البشري (HPV). تظهر في أماكن مختلفة من الجسم وقد تكون مؤلمة أو مزعجة من الناحية الجمالية. في عيادة دكتوراتو نقدم حلولاً متعددة وفعّالة للتخلص من السنط نهائياً.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>نوفر عدة طرق علاجية تشمل العلاج بالأمصال المناعية التي تحفز الجهاز المناعي لمحاربة الفيروس، والكي الكهربائي لإزالة السنط بدقة، والعلاج بالتبريد باستخدام النيتروجين السائل. يختار الطبيب الأسلوب الأنسب بناءً على نوع السنط وحجمه وموقعه وعمر المريض.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من الثآليل في اليدين أو القدمين</li>
<li>حالات السنط المسطح في الوجه</li>
<li>السنط التناسلي</li>
<li>الثآليل المتكررة التي لم تستجب للعلاجات المنزلية</li>
</ul>',
                'full_desc_en'   => '<h2>What is Wart Treatment?</h2>
<p>Warts are skin growths caused by human papillomavirus (HPV). They appear in various body locations and can be painful or cosmetically bothersome. At Doctorato Polyclinic, we offer multiple effective solutions for permanent wart removal.</p>
<h2>How Does the Treatment Work?</h2>
<p>We provide several treatment methods including immunotherapy serums that stimulate the immune system to fight the virus, electrocautery for precise wart removal, and cryotherapy using liquid nitrogen. The doctor selects the most suitable approach based on the wart type, size, location, and patient age.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with warts on hands or feet</li>
<li>Flat wart cases on the face</li>
<li>Genital warts</li>
<li>Recurring warts that haven\'t responded to home treatments</li>
</ul>',
                'benefits_ar'    => "إزالة فعّالة ونهائية للسنط\nتعدد الخيارات العلاجية المتاحة\nتحفيز المناعة الذاتية ضد الفيروس\nعلاج آمن مع حد أدنى من الندبات\nمنع انتشار السنط لمناطق أخرى",
                'benefits_en'    => "Effective and permanent wart removal\nMultiple treatment options available\nStimulation of natural immunity against the virus\nSafe treatment with minimal scarring\nPrevention of wart spread to other areas",
                'results_ar'     => "زوال السنط بعد 1-3 جلسات حسب الحجم\nشفاء المنطقة المعالجة خلال أسبوعين\nتقليل احتمالية عودة السنط\nمظهر طبيعي للجلد بعد العلاج",
                'results_en'     => "Wart disappearance after 1-3 sessions depending on size\nHealing of the treated area within two weeks\nReduced likelihood of wart recurrence\nNatural skin appearance after treatment",
                'sessions_count' => 3,
                'show_on_home'   => false,
                                'supply_cost'                  => 60,
                'medical_fee'                  => 340,
                'price_after_discount'         => 350,
                'default_sessions'             => 3,
                'session_duration_minutes'     => 20,
                'doctor_commission_percentage'  => 35,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 6,
            ],

            // ── Laser Hair Removal ──────────────────────────────────────
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر الجسم الكامل',
                'name_en'        => 'Full Body Laser',
                'short_desc_ar'  => 'إزالة الشعر بالليزر لكامل الجسم باستخدام أحدث أجهزة الليزر المعتمدة عالمياً. نتائج فعّالة وآمنة لجميع أنواع البشرة مع برنامج جلسات مخصص لتحقيق أفضل النتائج.',
                'short_desc_en'  => 'Full body laser hair removal using the latest internationally certified laser devices. Effective and safe results for all skin types with a customized session program for optimal outcomes.',
                'featured_image' => 'https://images.unsplash.com/photo-1598524374912-6b0b0bab3da4?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو ليزر الجسم الكامل؟</h2>
<p>إزالة الشعر بالليزر للجسم الكامل هي إجراء تجميلي متقدم يستهدف بصيلات الشعر في جميع مناطق الجسم باستخدام أجهزة ليزر عالية التقنية. في عيادة دكتوراتو نستخدم أحدث الأجهزة المعتمدة عالمياً والتي تناسب جميع ألوان البشرة وأنواع الشعر.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يعمل الليزر عن طريق إرسال نبضات ضوئية مركزة تستهدف صبغة الميلانين في بصيلات الشعر، مما يؤدي إلى تدمير البصيلة ومنع نمو الشعر مجدداً. يتم ضبط إعدادات الجهاز حسب لون البشرة وسماكة الشعر لضمان أقصى فعالية مع أعلى درجات الأمان.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>الراغبون في التخلص الدائم من شعر الجسم</li>
<li>من يعانون من نمو الشعر الكثيف أو المزعج</li>
<li>أصحاب البشرة الحساسة الذين يعانون من تهيج الحلاقة</li>
<li>جميع أنواع وألوان البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Full Body Laser?</h2>
<p>Full body laser hair removal is an advanced cosmetic procedure that targets hair follicles across all body areas using high-tech laser devices. At Doctorato Polyclinic, we use the latest internationally certified devices suitable for all skin tones and hair types.</p>
<h2>How Does the Treatment Work?</h2>
<p>The laser works by sending focused light pulses that target melanin pigment in hair follicles, destroying the follicle and preventing hair regrowth. Device settings are adjusted according to skin color and hair thickness to ensure maximum effectiveness with the highest safety standards.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those seeking permanent body hair removal</li>
<li>People with thick or bothersome hair growth</li>
<li>Those with sensitive skin who experience shaving irritation</li>
<li>All skin types and tones</li>
</ul>',
                'benefits_ar'    => "إزالة دائمة للشعر غير المرغوب فيه\nمناسب لجميع أنواع وألوان البشرة\nجلسات سريعة ومريحة\nتوفير الوقت والجهد على المدى الطويل\nبشرة ناعمة وخالية من الشعر",
                'benefits_en'    => "Permanent removal of unwanted hair\nSuitable for all skin types and tones\nQuick and comfortable sessions\nLong-term time and effort savings\nSmooth, hair-free skin",
                'results_ar'     => "انخفاض ملحوظ في نمو الشعر بعد 3 جلسات\nنتائج مثالية بعد 6-8 جلسات\nبشرة ناعمة وخالية من الشعر\nنتائج دائمة مع جلسات صيانة سنوية",
                'results_en'     => "Noticeable reduction in hair growth after 3 sessions\nOptimal results after 6-8 sessions\nSmooth, hair-free skin\nPermanent results with annual maintenance sessions",
                'sessions_count' => 8,
                'show_on_home'   => true,
                                'supply_cost'                  => 250,
                'medical_fee'                  => 3250,
                'price_after_discount'         => 2800,
                'default_sessions'             => 8,
                'session_duration_minutes'     => 90,
                'doctor_commission_percentage'  => 25,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 1,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر المناطق الحساسة',
                'name_en'        => 'Sensitive Areas Laser',
                'short_desc_ar'  => 'إزالة الشعر بالليزر للمناطق الحساسة بتقنية آمنة ومريحة. نستخدم أجهزة مخصصة ذات إعدادات دقيقة تناسب البشرة الحساسة مع ضمان الخصوصية والراحة التامة.',
                'short_desc_en'  => 'Laser hair removal for sensitive areas with safe and comfortable technology. We use specialized devices with precise settings suitable for sensitive skin while ensuring complete privacy and comfort.',
                'featured_image' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو ليزر المناطق الحساسة؟</h2>
<p>إزالة الشعر بالليزر في المناطق الحساسة يتطلب خبرة عالية وأجهزة متخصصة توفر أقصى فعالية مع الحفاظ على سلامة وراحة البشرة الحساسة. في عيادة دكتوراتو نوفر بيئة مريحة وخاصة مع فريق طبي متمرس يضمن أفضل تجربة علاجية.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>نستخدم أجهزة ليزر متطورة بإعدادات مخصصة للمناطق الحساسة، حيث يتم ضبط الطاقة والنبضات بدقة لتناسب رقة الجلد في هذه المناطق. يتضمن العلاج نظام تبريد متقدم يقلل من الإحساس بالحرارة ويضمن راحة المريض خلال الجلسة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>الراغبون في إزالة شعر المناطق الحساسة بشكل دائم</li>
<li>من يعانون من تهيج وحساسية من طرق الإزالة التقليدية</li>
<li>من يبحثون عن حل مريح وطويل الأمد</li>
<li>جميع أنواع البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Sensitive Areas Laser?</h2>
<p>Laser hair removal in sensitive areas requires high expertise and specialized devices that provide maximum effectiveness while maintaining the safety and comfort of sensitive skin. At Doctorato Polyclinic, we provide a comfortable and private environment with an experienced medical team ensuring the best treatment experience.</p>
<h2>How Does the Treatment Work?</h2>
<p>We use advanced laser devices with settings customized for sensitive areas, where energy and pulses are precisely adjusted to suit the delicate skin in these areas. The treatment includes an advanced cooling system that reduces heat sensation and ensures patient comfort during the session.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those seeking permanent hair removal in sensitive areas</li>
<li>People experiencing irritation from traditional removal methods</li>
<li>Those looking for a comfortable, long-lasting solution</li>
<li>All skin types</li>
</ul>',
                'benefits_ar'    => "إزالة فعّالة وآمنة للشعر في المناطق الحساسة\nأجهزة مخصصة ذات نظام تبريد متقدم\nخصوصية تامة وراحة أثناء الجلسة\nتقليل التهيج والحساسية\nنتائج طويلة الأمد",
                'benefits_en'    => "Effective and safe hair removal in sensitive areas\nSpecialized devices with advanced cooling system\nComplete privacy and comfort during sessions\nReduced irritation and sensitivity\nLong-lasting results",
                'results_ar'     => "تقليل كبير في نمو الشعر بعد 3 جلسات\nنتائج مثالية بعد 6-8 جلسات\nراحة من التهيج الناتج عن الحلاقة\nبشرة ناعمة ونظيفة",
                'results_en'     => "Significant reduction in hair growth after 3 sessions\nOptimal results after 6-8 sessions\nRelief from shaving-related irritation\nSmooth, clean skin",
                'sessions_count' => 7,
                'show_on_home'   => false,
                                'supply_cost'                  => 60,
                'medical_fee'                  => 940,
                'price_after_discount'         => 800,
                'default_sessions'             => 7,
                'session_duration_minutes'     => 25,
                'doctor_commission_percentage'  => 25,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 2,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر الوجه',
                'name_en'        => 'Facial Laser',
                'short_desc_ar'  => 'إزالة شعر الوجه بالليزر بدقة عالية وأمان تام للبشرة. تقنية متطورة تستهدف بصيلات الشعر دون التأثير على البشرة المحيطة للحصول على وجه ناعم وخالٍ من الشعر.',
                'short_desc_en'  => 'Precise and safe facial laser hair removal. Advanced technology targets hair follicles without affecting surrounding skin for a smooth, hair-free face.',
                'featured_image' => 'https://images.unsplash.com/photo-1512290923902-8a9f81dc236c?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو ليزر الوجه؟</h2>
<p>إزالة شعر الوجه بالليزر هو إجراء تجميلي دقيق يستهدف الشعر غير المرغوب فيه في منطقة الوجه بما يشمل الذقن والشفة العليا والسوالف والجبهة. نستخدم أجهزة ليزر متخصصة بدقة عالية تضمن نتائج ممتازة مع الحفاظ على نعومة وسلامة بشرة الوجه.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم استخدام جهاز ليزر بإعدادات مخصصة لبشرة الوجه الرقيقة، حيث يستهدف بصيلات الشعر بنبضات ضوئية دقيقة. يشمل العلاج نظام تبريد يحمي البشرة من أي تأثير حراري. تختلف عدد الجلسات المطلوبة حسب كثافة الشعر ولون البشرة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>النساء اللواتي يعانين من شعر الوجه الزائد</li>
<li>من يعانون من نمو الشعر الناتج عن التغيرات الهرمونية</li>
<li>الراغبون في التخلص من شعر الذقن أو الشفة العليا</li>
<li>جميع ألوان البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Facial Laser?</h2>
<p>Facial laser hair removal is a precise cosmetic procedure targeting unwanted hair in the facial area including the chin, upper lip, sideburns, and forehead. We use specialized high-precision laser devices ensuring excellent results while maintaining facial skin smoothness and safety.</p>
<h2>How Does the Treatment Work?</h2>
<p>A laser device with settings customized for delicate facial skin is used, targeting hair follicles with precise light pulses. The treatment includes a cooling system that protects the skin from any thermal effects. The number of sessions required varies based on hair density and skin color.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Women with excess facial hair</li>
<li>Those with hair growth caused by hormonal changes</li>
<li>People wanting to remove chin or upper lip hair</li>
<li>All skin tones</li>
</ul>',
                'benefits_ar'    => "إزالة دقيقة لشعر الوجه غير المرغوب فيه\nحماية بشرة الوجه الرقيقة أثناء العلاج\nنتائج سريعة وملحوظة\nتوديع الحلاقة والشمع نهائياً\nبشرة وجه ناعمة ومشرقة",
                'benefits_en'    => "Precise removal of unwanted facial hair\nProtection of delicate facial skin during treatment\nQuick and noticeable results\nSay goodbye to shaving and waxing permanently\nSmooth, radiant facial skin",
                'results_ar'     => "تقليل واضح في شعر الوجه بعد الجلسة الثانية\nنتائج مثالية بعد 5-7 جلسات\nوجه ناعم وخالٍ من الشعر\nتحسن في ملمس ومظهر بشرة الوجه",
                'results_en'     => "Clear reduction in facial hair after the second session\nOptimal results after 5-7 sessions\nSmooth, hair-free face\nImproved facial skin texture and appearance",
                'sessions_count' => 6,
                'show_on_home'   => false,
                                'supply_cost'                  => 40,
                'medical_fee'                  => 560,
                'price_after_discount'         => 500,
                'default_sessions'             => 6,
                'session_duration_minutes'     => 20,
                'doctor_commission_percentage'  => 25,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 3,
            ],

            // ── Skincare & Aesthetics ───────────────────────────────────
            [
                'category'       => 'skincare',
                'name_ar'        => 'جلسات تنظيف البشرة العميق',
                'name_en'        => 'Deep Skin Cleansing',
                'short_desc_ar'  => 'جلسات تنظيف عميق للبشرة لإزالة الشوائب والرؤوس السوداء وتجديد خلايا البشرة. تتضمن الجلسة تقشيراً وتنظيفاً بالبخار وقناعاً مغذياً لبشرة نقية ومشرقة.',
                'short_desc_en'  => 'Deep skin cleansing sessions to remove impurities, blackheads, and renew skin cells. The session includes exfoliation, steam cleansing, and a nourishing mask for pure, radiant skin.',
                'featured_image' => 'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو التنظيف العميق للبشرة؟</h2>
<p>تنظيف البشرة العميق هو إجراء تجميلي شامل يهدف إلى تنقية البشرة من الشوائب والأوساخ المتراكمة في المسام وإزالة الرؤوس السوداء والبيضاء. في عيادة دكتوراتو نقدم جلسات تنظيف متكاملة تجمع بين التقنيات الحديثة والمنتجات الطبية عالية الجودة.</p>
<h2>كيف تتم الجلسة؟</h2>
<p>تبدأ الجلسة بتنظيف البشرة بمنظف طبي مناسب لنوع البشرة، ثم يتم استخدام البخار لفتح المسام وتسهيل عملية التنظيف العميق. يلي ذلك استخراج الرؤوس السوداء والشوائب بأدوات معقمة ومتخصصة، ثم تطبيق تقشير لطيف وسيروم مغذي وقناع علاجي يناسب احتياجات البشرة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>أصحاب البشرة الدهنية والمختلطة</li>
<li>من يعانون من الرؤوس السوداء والمسام الواسعة</li>
<li>الراغبون في تجديد وتنقية البشرة</li>
<li>كإجراء دوري للحفاظ على صحة البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Deep Skin Cleansing?</h2>
<p>Deep skin cleansing is a comprehensive cosmetic procedure aimed at purifying the skin from impurities and accumulated dirt in pores and removing blackheads and whiteheads. At Doctorato Polyclinic, we offer complete cleansing sessions combining modern techniques with high-quality medical products.</p>
<h2>How Is the Session Done?</h2>
<p>The session begins with cleansing the skin using a medical cleanser suited to the skin type, then steam is used to open pores and facilitate deep cleansing. This is followed by extraction of blackheads and impurities with sterilized specialized tools, then application of gentle exfoliation, nourishing serum, and a therapeutic mask tailored to the skin\'s needs.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with oily and combination skin</li>
<li>People with blackheads and enlarged pores</li>
<li>Anyone seeking skin renewal and purification</li>
<li>As a periodic routine to maintain skin health</li>
</ul>',
                'benefits_ar'    => "تنقية البشرة من الشوائب والأوساخ العميقة\nإزالة الرؤوس السوداء وتضييق المسام\nتجديد خلايا البشرة ونضارتها\nتحسين ملمس ومظهر البشرة\nتهيئة البشرة لامتصاص المنتجات العلاجية",
                'benefits_en'    => "Purification of skin from deep impurities and dirt\nRemoval of blackheads and pore minimization\nSkin cell renewal and radiance\nImproved skin texture and appearance\nPreparing skin for better absorption of treatment products",
                'results_ar'     => "بشرة نقية ومشرقة فوراً بعد الجلسة\nمسام أصغر وأنظف\nملمس بشرة أنعم وأكثر نضارة\nتحسن مستمر مع الجلسات الدورية",
                'results_en'     => "Pure, radiant skin immediately after the session\nSmaller, cleaner pores\nSmoother, more radiant skin texture\nContinuous improvement with periodic sessions",
                'sessions_count' => 4,
                'show_on_home'   => false,
                                'supply_cost'                  => 80,
                'medical_fee'                  => 420,
                'price_after_discount'         => 400,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 45,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 1,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'الهيدرافيشل',
                'name_en'        => 'HydraFacial',
                'short_desc_ar'  => 'تقنية الهيدرافيشل المتطورة لتنظيف وترطيب وتجديد البشرة في جلسة واحدة. تجمع بين التنظيف العميق والتقشير اللطيف وضخ السيروم المغذي لبشرة مشرقة ونضرة فوراً.',
                'short_desc_en'  => 'Advanced HydraFacial technology for cleansing, hydrating, and rejuvenating skin in a single session. Combines deep cleansing, gentle exfoliation, and nourishing serum infusion for instantly radiant, glowing skin.',
                'featured_image' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو الهيدرافيشل؟</h2>
<p>الهيدرافيشل هو علاج متطور للبشرة يجمع بين التنظيف العميق والتقشير اللطيف والترطيب المكثف في جلسة واحدة. تعتبر هذه التقنية من أكثر علاجات البشرة شعبية عالمياً لقدرتها على تحسين مظهر البشرة فوراً دون أي فترة نقاهة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يعمل جهاز الهيدرافيشل عبر ثلاث خطوات رئيسية: أولاً التنظيف والتقشير اللطيف لإزالة الخلايا الميتة، ثم الشفط اللطيف لتنظيف المسام واستخراج الشوائب، وأخيراً ضخ سيروم مغذي غني بمضادات الأكسدة وحمض الهيالورونيك والببتيدات لتغذية وترطيب البشرة بعمق.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>جميع أنواع البشرة بما فيها الحساسة</li>
<li>من يرغبون في إشراقة فورية قبل المناسبات</li>
<li>أصحاب البشرة الجافة والباهتة</li>
<li>من يعانون من بداية ظهور التجاعيد الدقيقة</li>
</ul>',
                'full_desc_en'   => '<h2>What is HydraFacial?</h2>
<p>HydraFacial is an advanced skin treatment that combines deep cleansing, gentle exfoliation, and intensive hydration in a single session. This technique is one of the most popular skin treatments worldwide for its ability to instantly improve skin appearance without any downtime.</p>
<h2>How Does the Treatment Work?</h2>
<p>The HydraFacial device works through three main steps: first, gentle cleansing and exfoliation to remove dead cells, then gentle suction to clean pores and extract impurities, and finally infusion of nourishing serum rich in antioxidants, hyaluronic acid, and peptides to deeply nourish and hydrate the skin.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>All skin types including sensitive skin</li>
<li>Those wanting instant glow before events</li>
<li>People with dry, dull skin</li>
<li>Those with early fine lines and wrinkles</li>
</ul>',
                'benefits_ar'    => "تنظيف عميق وترطيب مكثف في جلسة واحدة\nنتائج فورية دون فترة نقاهة\nمناسب لجميع أنواع البشرة\nتقليل المسام وتحسين ملمس البشرة\nتغذية البشرة بمضادات الأكسدة والفيتامينات",
                'benefits_en'    => "Deep cleansing and intensive hydration in one session\nInstant results with no downtime\nSuitable for all skin types\nPore reduction and improved skin texture\nNourishing skin with antioxidants and vitamins",
                'results_ar'     => "بشرة مشرقة ونضرة فوراً بعد الجلسة\nترطيب عميق يدوم لأيام\nمسام أصغر وبشرة أنعم\nتحسن ملحوظ في لون البشرة وتوحيده",
                'results_en'     => "Instantly radiant, glowing skin after the session\nDeep hydration lasting for days\nSmaller pores and smoother skin\nNoticeable improvement in skin tone evenness",
                'sessions_count' => 4,
                'show_on_home'   => true,
                                'supply_cost'                  => 200,
                'medical_fee'                  => 1300,
                'price_after_discount'         => 1200,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 45,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 2,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'حقن البوتوكس والفيلر',
                'name_en'        => 'Botox & Filler',
                'short_desc_ar'  => 'حقن البوتوكس لعلاج التجاعيد وخطوط التعبير والفيلر لنفخ الشفاه وتحديد ملامح الوجه. نستخدم أجود المنتجات العالمية المعتمدة لنتائج طبيعية وآمنة تدوم طويلاً.',
                'short_desc_en'  => 'Botox injections for wrinkles and expression lines, and fillers for lip augmentation and facial contouring. We use the finest internationally certified products for natural, safe, and long-lasting results.',
                'featured_image' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هي حقن البوتوكس والفيلر؟</h2>
<p>البوتوكس هو بروتين نقي يعمل على إرخاء العضلات المسؤولة عن التجاعيد وخطوط التعبير، بينما الفيلر هو مادة حشو تعتمد غالباً على حمض الهيالورونيك لملء الخطوط وتحديد ملامح الوجه. في عيادة دكتوراتو نستخدم أفضل المنتجات العالمية المعتمدة لتحقيق نتائج طبيعية متناسقة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم حقن البوتوكس في مناطق محددة لإرخاء العضلات المسببة للتجاعيد مثل خطوط الجبهة وحول العينين وبين الحاجبين. أما الفيلر فيتم حقنه لتعبئة الخطوط العميقة ونفخ الشفاه وتحديد الفك والذقن وملء الهالات السوداء. يقوم الطبيب بتصميم خطة الحقن بناءً على ملامح الوجه والنتيجة المرغوبة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من تجاعيد الجبهة وخطوط التعبير</li>
<li>الراغبون في نفخ الشفاه بشكل طبيعي</li>
<li>من يريدون تحديد ملامح الوجه والذقن</li>
<li>أصحاب الهالات السوداء والخطوط العميقة</li>
</ul>',
                'full_desc_en'   => '<h2>What are Botox and Filler Injections?</h2>
<p>Botox is a purified protein that relaxes the muscles responsible for wrinkles and expression lines, while filler is a dermal filling substance typically based on hyaluronic acid to fill lines and define facial features. At Doctorato Polyclinic, we use the best internationally certified products to achieve natural, harmonious results.</p>
<h2>How Does the Treatment Work?</h2>
<p>Botox is injected in specific areas to relax wrinkle-causing muscles such as forehead lines, crow\'s feet, and frown lines. Filler is injected to fill deep lines, augment lips, define the jawline and chin, and fill under-eye hollows. The doctor designs the injection plan based on facial features and desired results.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with forehead wrinkles and expression lines</li>
<li>People seeking natural lip augmentation</li>
<li>Those wanting facial and chin contouring</li>
<li>People with dark circles and deep lines</li>
</ul>',
                'benefits_ar'    => "تقليل التجاعيد وخطوط التعبير بشكل فوري\nنفخ الشفاه وتحديد ملامح الوجه بشكل طبيعي\nنتائج سريعة وملحوظة\nمنتجات عالمية معتمدة وآمنة\nإجراء سريع بدون فترة نقاهة طويلة",
                'benefits_en'    => "Immediate reduction of wrinkles and expression lines\nNatural lip augmentation and facial contouring\nQuick and noticeable results\nInternationally certified and safe products\nQuick procedure with minimal downtime",
                'results_ar'     => "نتائج فورية تتحسن خلال أسبوعين\nتأثير البوتوكس يدوم 4-6 أشهر\nتأثير الفيلر يدوم 8-18 شهراً\nمظهر أكثر شباباً ونضارة",
                'results_en'     => "Immediate results that improve within two weeks\nBotox effects last 4-6 months\nFiller effects last 8-18 months\nMore youthful and refreshed appearance",
                'sessions_count' => 1,
                'show_on_home'   => true,
                                'supply_cost'                  => 1000,
                'medical_fee'                  => 2000,
                'price_after_discount'         => null,
                'default_sessions'             => 1,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 35,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 3,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'حقن النضارة',
                'name_en'        => 'Glow Injections',
                'short_desc_ar'  => 'حقن النضارة لتغذية البشرة من الداخل واستعادة حيويتها وإشراقها. تحتوي على مزيج من الفيتامينات وحمض الهيالورونيك ومضادات الأكسدة لبشرة صحية ومتوهجة.',
                'short_desc_en'  => 'Glow injections to nourish skin from within and restore its vitality and radiance. Contains a blend of vitamins, hyaluronic acid, and antioxidants for healthy, glowing skin.',
                'featured_image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هي حقن النضارة؟</h2>
<p>حقن النضارة هي علاج تجميلي يهدف إلى تغذية البشرة من الداخل وإعادة الحيوية والإشراق لها. تحتوي الحقن على مزيج متوازن من حمض الهيالورونيك والفيتامينات ومضادات الأكسدة والأحماض الأمينية التي تعمل على ترطيب البشرة بعمق وتحفيز إنتاج الكولاجين.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم حقن مزيج المواد المغذية في طبقات الجلد الوسطى باستخدام إبر دقيقة جداً. تعمل هذه المواد على ترطيب البشرة من الداخل وتحفيز عمليات التجدد الخلوي وإنتاج الكولاجين. يمكن تطبيق الحقن على الوجه والرقبة واليدين ومنطقة أعلى الصدر.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من بشرة جافة وباهتة</li>
<li>الراغبون في استعادة نضارة البشرة وتوهجها</li>
<li>من يريدون تحسين مرونة وملمس البشرة</li>
<li>كعلاج وقائي ضد علامات الشيخوخة المبكرة</li>
</ul>',
                'full_desc_en'   => '<h2>What are Glow Injections?</h2>
<p>Glow injections are a cosmetic treatment aimed at nourishing the skin from within and restoring its vitality and radiance. The injections contain a balanced blend of hyaluronic acid, vitamins, antioxidants, and amino acids that deeply hydrate the skin and stimulate collagen production.</p>
<h2>How Does the Treatment Work?</h2>
<p>The nutrient blend is injected into the middle layers of the skin using very fine needles. These substances work to hydrate the skin from within and stimulate cellular renewal and collagen production. The injections can be applied to the face, neck, hands, and decollete area.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with dry, dull skin</li>
<li>People seeking to restore skin radiance and glow</li>
<li>Those wanting to improve skin elasticity and texture</li>
<li>As a preventive treatment against early aging signs</li>
</ul>',
                'benefits_ar'    => "ترطيب عميق ومكثف للبشرة\nاستعادة النضارة والإشراق الطبيعي\nتحفيز إنتاج الكولاجين\nتحسين مرونة البشرة وملمسها\nتأخير ظهور علامات الشيخوخة",
                'benefits_en'    => "Deep and intensive skin hydration\nRestoration of natural radiance and glow\nStimulation of collagen production\nImproved skin elasticity and texture\nDelaying the appearance of aging signs",
                'results_ar'     => "بشرة أكثر نضارة وترطيباً بعد الجلسة الأولى\nتحسن ملحوظ في إشراقة البشرة بعد 3 جلسات\nمرونة أفضل وملمس أنعم\nنتائج تراكمية تدوم عدة أشهر",
                'results_en'     => "More radiant and hydrated skin after the first session\nNoticeable improvement in skin glow after 3 sessions\nBetter elasticity and smoother texture\nCumulative results lasting several months",
                'sessions_count' => 4,
                'show_on_home'   => false,
                                'supply_cost'                  => 500,
                'medical_fee'                  => 1500,
                'price_after_discount'         => 1700,
                'default_sessions'             => 3,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 4,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'جلسات الخيوط لشد الجلد',
                'name_en'        => 'Thread Lift',
                'short_desc_ar'  => 'تقنية الخيوط التجميلية لشد الوجه والرقبة بدون جراحة. خيوط طبية قابلة للذوبان تحفز إنتاج الكولاجين وتعيد تحديد ملامح الوجه بشكل طبيعي.',
                'short_desc_en'  => 'Thread lift technique for non-surgical face and neck lifting. Dissolvable medical threads stimulate collagen production and naturally redefine facial contours.',
                'featured_image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هي جلسات الخيوط؟</h2>
<p>جلسات الخيوط التجميلية هي إجراء غير جراحي لشد الوجه والرقبة باستخدام خيوط طبية خاصة قابلة للذوبان. تعمل هذه الخيوط على رفع الأنسجة المترهلة وتحفيز إنتاج الكولاجين الطبيعي مما يمنح الوجه مظهراً أكثر شباباً وحيوية دون الحاجة للجراحة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم إدخال خيوط طبية دقيقة تحت الجلد باستخدام إبر خاصة. تعمل هذه الخيوط على رفع وشد الأنسجة فوراً، وعلى المدى الطويل تحفز إنتاج الكولاجين حول الخيوط مما يعزز تأثير الشد. تذوب الخيوط تدريجياً خلال أشهر لكن تأثير الكولاجين المتكون يستمر لفترة أطول.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من ترهل خفيف إلى متوسط في الوجه</li>
<li>الراغبون في شد الوجه بدون جراحة</li>
<li>من يريدون إعادة تحديد خط الفك والذقن</li>
<li>الباحثون عن بديل غير جراحي لعملية شد الوجه</li>
</ul>',
                'full_desc_en'   => '<h2>What is Thread Lift?</h2>
<p>Thread lift is a non-surgical procedure for face and neck lifting using special dissolvable medical threads. These threads lift sagging tissues and stimulate natural collagen production, giving the face a more youthful and vibrant appearance without the need for surgery.</p>
<h2>How Does the Treatment Work?</h2>
<p>Fine medical threads are inserted under the skin using special needles. These threads immediately lift and tighten tissues, and over time stimulate collagen production around the threads, enhancing the lifting effect. The threads gradually dissolve over months, but the effect of the formed collagen lasts longer.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with mild to moderate facial sagging</li>
<li>People seeking non-surgical face lifting</li>
<li>Those wanting to redefine the jawline and chin</li>
<li>Anyone looking for a non-surgical alternative to facelift surgery</li>
</ul>',
                'benefits_ar'    => "شد فوري للوجه والرقبة بدون جراحة\nتحفيز إنتاج الكولاجين الطبيعي\nفترة نقاهة قصيرة جداً\nنتائج طبيعية ومتناسقة\nإعادة تحديد ملامح الوجه",
                'benefits_en'    => "Immediate face and neck lifting without surgery\nStimulation of natural collagen production\nVery short recovery period\nNatural and harmonious results\nRedefinition of facial contours",
                'results_ar'     => "شد فوري ملحوظ بعد الجلسة\nتحسن تدريجي خلال 2-3 أشهر\nنتائج تدوم 12-18 شهراً\nمظهر أكثر شباباً وحيوية",
                'results_en'     => "Noticeable immediate lifting after the session\nGradual improvement over 2-3 months\nResults lasting 12-18 months\nMore youthful and vibrant appearance",
                'sessions_count' => 1,
                'show_on_home'   => false,
                                'supply_cost'                  => 1500,
                'medical_fee'                  => 3500,
                'price_after_discount'         => null,
                'default_sessions'             => 1,
                'session_duration_minutes'     => 60,
                'doctor_commission_percentage'  => 35,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 5,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'التقشير الكيميائي والبارد',
                'name_en'        => 'Chemical & Cold Peeling',
                'short_desc_ar'  => 'جلسات تقشير كيميائي وبارد لتجديد البشرة وعلاج التصبغات والبقع الداكنة. نستخدم تراكيز مدروسة تناسب نوع بشرتك لنتائج آمنة وفعّالة.',
                'short_desc_en'  => 'Chemical and cold peeling sessions for skin renewal and treatment of pigmentation and dark spots. We use carefully formulated concentrations suited to your skin type for safe, effective results.',
                'featured_image' => 'https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو التقشير الكيميائي والبارد؟</h2>
<p>التقشير الكيميائي والبارد هو إجراء تجميلي يستخدم محاليل كيميائية مدروسة أو تقنية التقشير البارد لإزالة الطبقات التالفة من الجلد وتحفيز نمو خلايا جديدة صحية. في عيادة دكتوراتو نقدم مجموعة متنوعة من التقشيرات بتراكيز وعمق مختلف يناسب كل حالة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>في التقشير الكيميائي يتم تطبيق محلول حمضي على البشرة يعمل على إزالة الطبقات السطحية التالفة. أما التقشير البارد فيستخدم تركيبة خاصة تعمل على تقشير البشرة بلطف دون تقشر ظاهري، وهو مناسب أكثر للبشرة الحساسة والداكنة. يتم اختيار النوع والتركيز بناءً على مشكلة البشرة ونوعها.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من تصبغات وبقع داكنة</li>
<li>أصحاب البشرة الباهتة وغير المتوحدة اللون</li>
<li>من يريدون علاج آثار حب الشباب</li>
<li>الراغبون في تجديد وتفتيح البشرة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Chemical and Cold Peeling?</h2>
<p>Chemical and cold peeling is a cosmetic procedure that uses carefully formulated chemical solutions or cold peeling technology to remove damaged skin layers and stimulate the growth of new healthy cells. At Doctorato Polyclinic, we offer a variety of peels at different concentrations and depths suited to each condition.</p>
<h2>How Does the Treatment Work?</h2>
<p>In chemical peeling, an acid solution is applied to the skin to remove damaged surface layers. Cold peeling uses a special formula that gently exfoliates the skin without visible peeling, making it more suitable for sensitive and darker skin. The type and concentration are chosen based on the skin concern and type.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with pigmentation and dark spots</li>
<li>People with dull, uneven skin tone</li>
<li>Those wanting to treat acne scars</li>
<li>Anyone seeking skin renewal and brightening</li>
</ul>',
                'benefits_ar'    => "تجديد شامل لخلايا البشرة\nعلاج فعّال للتصبغات والبقع الداكنة\nتفتيح وتوحيد لون البشرة\nتحسين ملمس البشرة ونعومتها\nتحفيز إنتاج الكولاجين",
                'benefits_en'    => "Comprehensive skin cell renewal\nEffective treatment for pigmentation and dark spots\nSkin brightening and tone evening\nImproved skin texture and smoothness\nStimulation of collagen production",
                'results_ar'     => "تحسن ملحوظ في لون البشرة بعد الجلسة الأولى\nتفتيح البقع الداكنة بعد 3-4 جلسات\nبشرة أكثر نعومة وإشراقاً\nنتائج مستمرة مع برنامج العناية المنزلي",
                'results_en'     => "Noticeable improvement in skin tone after the first session\nLightening of dark spots after 3-4 sessions\nSmoother, more radiant skin\nSustained results with home care program",
                'sessions_count' => 5,
                'show_on_home'   => true,
                                'supply_cost'                  => 120,
                'medical_fee'                  => 680,
                'price_after_discount'         => 650,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 6,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'الديرما بن والميزوثيرابي',
                'name_en'        => 'Dermapen & Mesotherapy',
                'short_desc_ar'  => 'تقنية الديرما بن والميزوثيرابي لتحفيز الكولاجين وعلاج ندبات حب الشباب والمسام الواسعة. حقن دقيقة تجدد البشرة من الداخل وتحسّن ملمسها ومظهرها.',
                'short_desc_en'  => 'Dermapen and mesotherapy techniques for collagen stimulation and treatment of acne scars and enlarged pores. Micro-injections renew skin from within, improving its texture and appearance.',
                'featured_image' => 'https://images.unsplash.com/photo-1573461160327-b450ce3d8e7f?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو الديرما بن والميزوثيرابي؟</h2>
<p>الديرما بن هو جهاز يستخدم إبراً دقيقة جداً لعمل ثقوب مجهرية في الجلد، مما يحفز عملية الشفاء الطبيعية وإنتاج الكولاجين. الميزوثيرابي هو تقنية حقن مواد علاجية في طبقات الجلد الوسطى. الجمع بين التقنيتين يحقق نتائج استثنائية في تجديد البشرة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم استخدام جهاز الديرما بن لعمل قنوات دقيقة في الجلد، ثم يتم تطبيق سيروم علاجي مخصص يتم امتصاصه بعمق عبر هذه القنوات. تحفز العملية إنتاج الكولاجين والإيلاستين بشكل طبيعي مما يعيد بناء الجلد من الداخل. في جلسات الميزوثيرابي يتم حقن مزيج من الفيتامينات والمعادن ومضادات الأكسدة مباشرة في الجلد.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من ندبات حب الشباب</li>
<li>أصحاب المسام الواسعة</li>
<li>من يريدون تحسين ملمس ومرونة البشرة</li>
<li>الراغبون في تجديد البشرة وتحفيز الكولاجين</li>
</ul>',
                'full_desc_en'   => '<h2>What is Dermapen and Mesotherapy?</h2>
<p>Dermapen is a device that uses very fine needles to create microscopic punctures in the skin, stimulating the natural healing process and collagen production. Mesotherapy is a technique of injecting therapeutic substances into the middle layers of the skin. Combining both techniques achieves exceptional results in skin rejuvenation.</p>
<h2>How Does the Treatment Work?</h2>
<p>The Dermapen device creates fine channels in the skin, then a customized therapeutic serum is applied and deeply absorbed through these channels. The process naturally stimulates collagen and elastin production, rebuilding the skin from within. In mesotherapy sessions, a blend of vitamins, minerals, and antioxidants is injected directly into the skin.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with acne scars</li>
<li>People with enlarged pores</li>
<li>Those wanting to improve skin texture and elasticity</li>
<li>Anyone seeking skin renewal and collagen stimulation</li>
</ul>',
                'benefits_ar'    => "تحفيز طبيعي لإنتاج الكولاجين والإيلاستين\nعلاج فعّال لندبات حب الشباب\nتقليل حجم المسام الواسعة\nتوصيل المواد العلاجية بعمق في الجلد\nتحسين شامل لملمس ومظهر البشرة",
                'benefits_en'    => "Natural stimulation of collagen and elastin production\nEffective treatment for acne scars\nReduction of enlarged pore size\nDeep delivery of therapeutic substances into the skin\nOverall improvement in skin texture and appearance",
                'results_ar'     => "تحسن في ملمس البشرة بعد الجلسة الأولى\nتقليل ملحوظ للندبات بعد 3-4 جلسات\nمسام أصغر وبشرة أكثر نعومة\nنتائج تتحسن باستمرار على مدى الأشهر التالية",
                'results_en'     => "Improved skin texture after the first session\nNoticeable scar reduction after 3-4 sessions\nSmaller pores and smoother skin\nResults that continue to improve over the following months",
                'sessions_count' => 5,
                'show_on_home'   => false,
                                'supply_cost'                  => 250,
                'medical_fee'                  => 950,
                'price_after_discount'         => 1000,
                'default_sessions'             => 6,
                'session_duration_minutes'     => 35,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 7,
            ],
            [
                'category'       => 'skincare',
                'name_ar'        => 'الفراكشنال ليزر',
                'name_en'        => 'Fractional Laser',
                'short_desc_ar'  => 'تقنية الفراكشنال ليزر لعلاج ندبات حب الشباب وعلامات تمدد الجلد وتجديد البشرة. يعمل على تحفيز إنتاج الكولاجين وتحسين ملمس البشرة بشكل ملحوظ.',
                'short_desc_en'  => 'Fractional laser technology for treating acne scars, stretch marks, and skin rejuvenation. Stimulates collagen production and noticeably improves skin texture.',
                'featured_image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو الفراكشنال ليزر؟</h2>
<p>الفراكشنال ليزر هو تقنية متقدمة تستخدم أشعة الليزر المجزأة لعلاج مناطق صغيرة من الجلد في كل نبضة، مما يترك مناطق سليمة بينها تساعد في سرعة الشفاء. تعتبر هذه التقنية من أكثر العلاجات فعالية لندبات حب الشباب وعلامات التمدد وتجديد البشرة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يعمل جهاز الفراكشنال ليزر عن طريق إرسال أعمدة دقيقة من الليزر تخترق طبقات محددة من الجلد، مما يحفز الاستجابة الشفائية الطبيعية وإنتاج الكولاجين الجديد. يتم التحكم في عمق وكثافة الليزر حسب المشكلة المراد علاجها ونوع البشرة لتحقيق أفضل النتائج بأقل فترة تعافي.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من ندبات حب الشباب العميقة</li>
<li>أصحاب علامات تمدد الجلد (السترتش ماركس)</li>
<li>من يريدون تجديد البشرة وتحسين ملمسها</li>
<li>الراغبون في علاج التجاعيد الدقيقة</li>
</ul>',
                'full_desc_en'   => '<h2>What is Fractional Laser?</h2>
<p>Fractional laser is an advanced technology that uses fractionated laser beams to treat small areas of skin with each pulse, leaving intact areas in between that aid in faster healing. This technique is considered one of the most effective treatments for acne scars, stretch marks, and skin rejuvenation.</p>
<h2>How Does the Treatment Work?</h2>
<p>The fractional laser device sends tiny columns of laser that penetrate specific skin layers, stimulating the natural healing response and new collagen production. The depth and intensity of the laser are controlled based on the condition being treated and skin type to achieve optimal results with minimal recovery time.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with deep acne scars</li>
<li>People with stretch marks</li>
<li>Those wanting skin rejuvenation and texture improvement</li>
<li>Anyone seeking to treat fine wrinkles</li>
</ul>',
                'benefits_ar'    => "علاج فعّال لندبات حب الشباب العميقة\nتقليل علامات تمدد الجلد\nتحفيز قوي لإنتاج الكولاجين\nتحسين ملحوظ في ملمس البشرة\nتجديد شامل للبشرة",
                'benefits_en'    => "Effective treatment for deep acne scars\nReduction of stretch marks\nPowerful stimulation of collagen production\nNoticeable improvement in skin texture\nComprehensive skin rejuvenation",
                'results_ar'     => "تحسن ملحوظ في ملمس البشرة بعد 2-3 جلسات\nتقليل واضح في عمق الندبات\nبشرة أكثر نعومة وتوحداً\nنتائج تستمر في التحسن لمدة 6 أشهر بعد العلاج",
                'results_en'     => "Noticeable improvement in skin texture after 2-3 sessions\nClear reduction in scar depth\nSmoother, more even skin\nResults continue to improve for 6 months after treatment",
                'sessions_count' => 4,
                'show_on_home'   => false,
                                'supply_cost'                  => 150,
                'medical_fee'                  => 1350,
                'price_after_discount'         => 1200,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 40,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 8,
            ],

            // ── Regenerative Treatments ─────────────────────────────────
            [
                'category'       => 'regenerative',
                'name_ar'        => 'جلسات البلازما PRP',
                'name_en'        => 'PRP Therapy',
                'short_desc_ar'  => 'علاج بالبلازما الغنية بالصفائح الدموية لتجديد البشرة وعلاج تساقط الشعر. نستخلص البلازما من دم المريض ونحقنها لتحفيز النمو والتجدد الطبيعي للخلايا.',
                'short_desc_en'  => 'Platelet-rich plasma therapy for skin rejuvenation and hair loss treatment. We extract plasma from the patient\'s blood and inject it to stimulate natural cell growth and regeneration.',
                'featured_image' => 'https://images.unsplash.com/photo-1584362917165-526a968579e8?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هي جلسات البلازما PRP؟</h2>
<p>البلازما الغنية بالصفائح الدموية (PRP) هي علاج تجديدي يستخدم مكونات من دم المريض نفسه. يتم سحب كمية صغيرة من الدم ومعالجتها للحصول على بلازما مركزة غنية بعوامل النمو والصفائح الدموية التي تحفز الشفاء وتجديد الأنسجة.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم سحب عينة صغيرة من دم المريض ووضعها في جهاز الطرد المركزي لفصل مكونات الدم واستخلاص البلازما الغنية بالصفائح الدموية. ثم يتم حقن هذه البلازما في المناطق المستهدفة سواء في فروة الرأس لعلاج تساقط الشعر أو في الوجه لتجديد البشرة. تعمل عوامل النمو على تحفيز الخلايا الجذعية وتجديد الأنسجة بشكل طبيعي.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من تساقط الشعر بدرجاته المختلفة</li>
<li>الراغبون في تجديد البشرة بشكل طبيعي</li>
<li>من يبحثون عن علاج آمن من مكونات الجسم الطبيعية</li>
<li>لتسريع شفاء الأنسجة بعد الإجراءات التجميلية</li>
</ul>',
                'full_desc_en'   => '<h2>What is PRP Therapy?</h2>
<p>Platelet-Rich Plasma (PRP) is a regenerative treatment that uses components from the patient\'s own blood. A small amount of blood is drawn and processed to obtain concentrated plasma rich in growth factors and platelets that stimulate healing and tissue regeneration.</p>
<h2>How Does the Treatment Work?</h2>
<p>A small blood sample is drawn from the patient and placed in a centrifuge to separate blood components and extract the platelet-rich plasma. This plasma is then injected into targeted areas, whether the scalp for hair loss treatment or the face for skin rejuvenation. Growth factors stimulate stem cells and naturally regenerate tissues.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those suffering from various degrees of hair loss</li>
<li>People seeking natural skin rejuvenation</li>
<li>Those looking for a safe treatment using the body\'s natural components</li>
<li>For accelerating tissue healing after cosmetic procedures</li>
</ul>',
                'benefits_ar'    => "علاج طبيعي وآمن من مكونات الجسم\nتحفيز نمو الشعر ووقف التساقط\nتجديد البشرة وتحسين ملمسها\nتحفيز إنتاج الكولاجين بشكل طبيعي\nلا توجد مخاطر حساسية أو رفض",
                'benefits_en'    => "Natural and safe treatment from the body's own components\nStimulation of hair growth and stopping hair loss\nSkin rejuvenation and texture improvement\nNatural stimulation of collagen production\nNo risk of allergic reactions or rejection",
                'results_ar'     => "تقليل ملحوظ في تساقط الشعر بعد 3 جلسات\nبدء نمو شعر جديد بعد 4-6 جلسات\nبشرة أكثر نضارة وحيوية\nنتائج تتحسن تدريجياً مع الجلسات المتتالية",
                'results_en'     => "Noticeable reduction in hair loss after 3 sessions\nNew hair growth begins after 4-6 sessions\nMore radiant and vibrant skin\nResults gradually improve with consecutive sessions",
                'sessions_count' => 6,
                'show_on_home'   => true,
                                'supply_cost'                  => 350,
                'medical_fee'                  => 1150,
                'price_after_discount'         => 1200,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 45,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 1,
            ],
            [
                'category'       => 'regenerative',
                'name_ar'        => 'الخلايا الجذعية',
                'name_en'        => 'Stem Cell Therapy',
                'short_desc_ar'  => 'علاج متقدم بالخلايا الجذعية لتجديد البشرة ومكافحة علامات الشيخوخة. تقنية حديثة تعمل على إصلاح الأنسجة التالفة وتحفيز إنتاج الكولاجين لبشرة أكثر شباباً.',
                'short_desc_en'  => 'Advanced stem cell therapy for skin rejuvenation and anti-aging. Modern technology that repairs damaged tissues and stimulates collagen production for younger-looking skin.',
                'featured_image' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو علاج الخلايا الجذعية؟</h2>
<p>علاج الخلايا الجذعية هو من أحدث التقنيات في مجال الطب التجديدي والتجميلي. يعتمد على استخدام الخلايا الجذعية وعوامل النمو المشتقة منها لتحفيز تجديد الأنسجة وإصلاح الخلايا التالفة. يعتبر هذا العلاج ثورة في مجال مكافحة الشيخوخة وتجديد البشرة والشعر.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يتم استخلاص عوامل النمو والبروتينات من الخلايا الجذعية وحقنها في المناطق المستهدفة. تعمل هذه العوامل على تحفيز الخلايا المحيطة للتجدد والإصلاح، وتعزيز إنتاج الكولاجين والإيلاستين، وتحسين الدورة الدموية الموضعية. يمكن استخدام العلاج للوجه والشعر وأي منطقة تحتاج للتجديد.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من علامات الشيخوخة المبكرة</li>
<li>الراغبون في تجديد شامل للبشرة</li>
<li>من يعانون من تساقط الشعر الشديد</li>
<li>الباحثون عن أحدث تقنيات الطب التجديدي</li>
</ul>',
                'full_desc_en'   => '<h2>What is Stem Cell Therapy?</h2>
<p>Stem cell therapy is one of the latest technologies in regenerative and aesthetic medicine. It relies on using stem cells and their derived growth factors to stimulate tissue regeneration and repair damaged cells. This treatment is considered a revolution in anti-aging, skin, and hair rejuvenation.</p>
<h2>How Does the Treatment Work?</h2>
<p>Growth factors and proteins are extracted from stem cells and injected into targeted areas. These factors stimulate surrounding cells to regenerate and repair, enhance collagen and elastin production, and improve local blood circulation. The treatment can be used for the face, hair, and any area requiring rejuvenation.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with early signs of aging</li>
<li>People seeking comprehensive skin rejuvenation</li>
<li>Those with severe hair loss</li>
<li>Anyone looking for the latest regenerative medicine technologies</li>
</ul>',
                'benefits_ar'    => "تجديد شامل للخلايا والأنسجة\nمكافحة فعالة لعلامات الشيخوخة\nتحفيز إنتاج الكولاجين والإيلاستين\nعلاج متقدم لتساقط الشعر\nتحسين الدورة الدموية الموضعية",
                'benefits_en'    => "Comprehensive cell and tissue renewal\nEffective anti-aging treatment\nStimulation of collagen and elastin production\nAdvanced treatment for hair loss\nImproved local blood circulation",
                'results_ar'     => "تحسن في نضارة البشرة بعد الجلسة الأولى\nتجديد واضح للبشرة بعد 3-4 جلسات\nتقوية بصيلات الشعر وتقليل التساقط\nبشرة أكثر شباباً ومرونة",
                'results_en'     => "Improved skin radiance after the first session\nClear skin renewal after 3-4 sessions\nStrengthened hair follicles and reduced hair loss\nMore youthful and elastic skin",
                'sessions_count' => 4,
                'show_on_home'   => false,
                                'supply_cost'                  => 600,
                'medical_fee'                  => 1900,
                'price_after_discount'         => 2000,
                'default_sessions'             => 4,
                'session_duration_minutes'     => 45,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 2,
            ],
            [
                'category'       => 'regenerative',
                'name_ar'        => 'كورس علاجي للشعر',
                'name_en'        => 'Hair Treatment Course',
                'short_desc_ar'  => 'برنامج علاجي متكامل لمشاكل الشعر يشمل البلازما والميزوثيرابي والخلايا الجذعية. كورس مخصص حسب حالة الشعر لوقف التساقط وتحفيز نمو شعر صحي وقوي.',
                'short_desc_en'  => 'Comprehensive hair treatment program including PRP, mesotherapy, and stem cells. A customized course based on hair condition to stop hair loss and stimulate healthy, strong hair growth.',
                'featured_image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800&q=80',
                'full_desc_ar'   => '<h2>ما هو الكورس العلاجي للشعر؟</h2>
<p>الكورس العلاجي للشعر هو برنامج متكامل ومخصص لعلاج مشاكل الشعر بشكل شامل. يجمع البرنامج بين عدة تقنيات علاجية متقدمة تشمل البلازما الغنية بالصفائح الدموية والميزوثيرابي والخلايا الجذعية لتحقيق أفضل النتائج في وقف تساقط الشعر وتحفيز نموه.</p>
<h2>كيف يعمل العلاج؟</h2>
<p>يبدأ البرنامج بتقييم شامل لحالة الشعر وفروة الرأس لتحديد أسباب التساقط ودرجته. بناءً على التقييم يتم وضع خطة علاجية مخصصة تتضمن جلسات البلازما لتحفيز البصيلات، وجلسات الميزوثيرابي لتغذية فروة الرأس بالفيتامينات والمعادن، وعلاج الخلايا الجذعية لتجديد البصيلات الضعيفة. يتم تنظيم الجلسات على مدى عدة أسابيع لتحقيق أقصى استفادة.</p>
<h2>لمن يناسب هذا العلاج؟</h2>
<ul>
<li>من يعانون من تساقط الشعر الشديد أو المزمن</li>
<li>حالات الصلع الوراثي في مراحله المبكرة</li>
<li>من يعانون من ضعف وترقق الشعر</li>
<li>النساء اللواتي يعانين من تساقط الشعر بعد الولادة أو بسبب الضغوط</li>
</ul>',
                'full_desc_en'   => '<h2>What is Hair Treatment Course?</h2>
<p>The hair treatment course is a comprehensive, customized program for treating hair problems holistically. The program combines several advanced therapeutic techniques including platelet-rich plasma, mesotherapy, and stem cells to achieve the best results in stopping hair loss and stimulating growth.</p>
<h2>How Does the Treatment Work?</h2>
<p>The program begins with a comprehensive assessment of hair and scalp condition to determine the causes and degree of hair loss. Based on the assessment, a customized treatment plan is developed that includes PRP sessions to stimulate follicles, mesotherapy sessions to nourish the scalp with vitamins and minerals, and stem cell therapy to rejuvenate weakened follicles. Sessions are organized over several weeks for maximum benefit.</p>
<h2>Who Is This Treatment For?</h2>
<ul>
<li>Those with severe or chronic hair loss</li>
<li>Early-stage hereditary baldness cases</li>
<li>People with weak and thinning hair</li>
<li>Women experiencing postpartum or stress-related hair loss</li>
</ul>',
                'benefits_ar'    => "برنامج علاجي شامل ومتكامل\nالجمع بين عدة تقنيات لنتائج أفضل\nخطة مخصصة حسب حالة كل مريض\nوقف تساقط الشعر وتحفيز النمو\nتقوية بصيلات الشعر وتغذيتها",
                'benefits_en'    => "Comprehensive and integrated treatment program\nCombination of multiple techniques for better results\nCustomized plan based on each patient's condition\nStopping hair loss and stimulating growth\nStrengthening and nourishing hair follicles",
                'results_ar'     => "تقليل ملحوظ في تساقط الشعر خلال الأسابيع الأولى\nبدء نمو شعر جديد بعد إكمال نصف البرنامج\nشعر أكثر كثافة وقوة\nنتائج مستدامة مع جلسات المتابعة الدورية",
                'results_en'     => "Noticeable reduction in hair loss during the first weeks\nNew hair growth begins after completing half the program\nThicker, stronger hair\nSustainable results with periodic follow-up sessions",
                'sessions_count' => 8,
                'show_on_home'   => false,
                                'supply_cost'                  => 200,
                'medical_fee'                  => 800,
                'price_after_discount'         => 800,
                'default_sessions'             => 8,
                'session_duration_minutes'     => 30,
                'doctor_commission_percentage'  => 30,
                'show_on_website' => true,
                'bookable'        => false,
'display_order'  => 3,
            ],

            // ── Filler ────────────────────────────────────────────────────
            [
                'category'       => 'filler',
                'name_ar'        => 'فيلر نيوفيا',
                'name_en'        => 'Neauvia Filler',
                'supply_cost'    => 3650,
                'medical_fee'    => 3350,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],
            [
                'category'       => 'filler',
                'name_ar'        => 'فيلر سيلوسوم',
                'name_en'        => 'Celesome Filler',
                'supply_cost'    => 1750,
                'medical_fee'    => 3250,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 2,
            ],
            [
                'category'       => 'filler',
                'name_ar'        => 'فيلر ايفانثيا',
                'name_en'        => 'Evanthia Filler',
                'supply_cost'    => 3550,
                'medical_fee'    => 3450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 3,
            ],
            [
                'category'       => 'filler',
                'name_ar'        => 'فيلر ميفل',
                'name_en'        => 'Mifill Filler',
                'supply_cost'    => 1550,
                'medical_fee'    => 2450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 4,
            ],

            // ── Botox ─────────────────────────────────────────────────────
            [
                'category'       => 'botox',
                'name_ar'        => 'بوتوكس زيومين',
                'name_en'        => 'Xeomin Botox',
                'supply_cost'    => 2700,
                'medical_fee'    => 2300,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],
            [
                'category'       => 'botox',
                'name_ar'        => 'بوتوكس اليرجان',
                'name_en'        => 'Allergan Botox (LE)',
                'supply_cost'    => 5050,
                'medical_fee'    => 1950,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 2,
            ],
            [
                'category'       => 'botox',
                'name_ar'        => 'بوتوكس جنتوكس',
                'name_en'        => 'Gentox Botox',
                'supply_cost'    => 2100,
                'medical_fee'    => 1900,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 3,
            ],

            // ── Skin Boosters ─────────────────────────────────────────────
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'بروفايلو',
                'name_en'        => 'Profhilo',
                'supply_cost'    => 2750,
                'medical_fee'    => 3750,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'RRS لونج لاستنج',
                'name_en'        => 'RRS Long Lasting',
                'supply_cost'    => 3850,
                'medical_fee'    => 2650,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 2,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'RRS HA Eyes',
                'name_en'        => 'RRS HA Eyes',
                'supply_cost'    => 2800,
                'medical_fee'    => 2700,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 3,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'ريتش',
                'name_en'        => 'Rich',
                'slug_override'  => 'rich-skin-booster',
                'supply_cost'    => 14550,
                'medical_fee'    => 3450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 4,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'راديس',
                'name_en'        => 'Radiesse',
                'supply_cost'    => 9550,
                'medical_fee'    => 5450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 5,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'اوليديا',
                'name_en'        => 'Olidia',
                'supply_cost'    => 6550,
                'medical_fee'    => 5450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 6,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'سكلبترا',
                'name_en'        => 'Sculptra',
                'supply_cost'    => 11050,
                'medical_fee'    => 6950,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 7,
            ],
            [
                'category'       => 'skin-boosters',
                'name_ar'        => 'ياقوت',
                'name_en'        => 'Yakoot',
                'supply_cost'    => 3550,
                'medical_fee'    => 3450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 8,
            ],

            // ── Dermapen ──────────────────────────────────────────────────
            [
                'category'       => 'dermapen',
                'name_ar'        => 'ديرمابن بدون بلازما',
                'name_en'        => 'Dermapen without Plasma',
                'supply_cost'    => 50,
                'medical_fee'    => 800,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],
            [
                'category'       => 'dermapen',
                'name_ar'        => 'ديرمابن مع بلازما',
                'name_en'        => 'Dermapen with Plasma',
                'supply_cost'    => 200,
                'medical_fee'    => 750,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 2,
            ],
            [
                'category'       => 'dermapen',
                'name_ar'        => 'ديرمابن مع بلازما وميزوثيرابي',
                'name_en'        => 'Dermapen with Plasma & Mesotherapy',
                'supply_cost'    => 200,
                'medical_fee'    => 900,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 3,
            ],
            [
                'category'       => 'dermapen',
                'name_ar'        => 'ديرمابن مع بلازما وسبسجن',
                'name_en'        => 'Dermapen with Plasma & Subcision',
                'supply_cost'    => 200,
                'medical_fee'    => 1000,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 4,
            ],
            [
                'category'       => 'dermapen',
                'name_ar'        => 'ديرمابن مع LC بدون بلازما',
                'name_en'        => 'Dermapen with LC without Plasma',
                'supply_cost'    => 650,
                'medical_fee'    => 650,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 5,
            ],

            // ── Peeling ───────────────────────────────────────────────────
            [
                'category'       => 'peeling',
                'name_ar'        => 'جرين بيل',
                'name_en'        => 'Green Peel',
                'supply_cost'    => 550,
                'medical_fee'    => 650,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],

            // ── Hydrafacial ───────────────────────────────────────────────
            [
                'category'       => 'hydrafacial',
                'name_ar'        => 'هيدرافيشل مستوى 1',
                'name_en'        => 'Hydrafacial Level 1',
                'supply_cost'    => 100,
                'medical_fee'    => 400,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 1,
            ],
            [
                'category'       => 'hydrafacial',
                'name_ar'        => 'هيدرافيشل مستوى 2',
                'name_en'        => 'Hydrafacial Level 2',
                'supply_cost'    => 150,
                'medical_fee'    => 600,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 2,
            ],
            [
                'category'       => 'hydrafacial',
                'name_ar'        => 'هيدرافيشل مستوى 3',
                'name_en'        => 'Hydrafacial Level 3',
                'supply_cost'    => 250,
                'medical_fee'    => 750,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 3,
            ],

            // ── Laser (additional area-specific services) ─────────────────
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - أندر أرم',
                'name_en'        => 'Laser - Underarm',
                'supply_cost'    => 0,
                'medical_fee'    => 150,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 10,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - شنب',
                'name_en'        => 'Laser - Mustache',
                'supply_cost'    => 0,
                'medical_fee'    => 100,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 11,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - شنب + ذقن',
                'name_en'        => 'Laser - Mustache + Shin',
                'supply_cost'    => 0,
                'medical_fee'    => 150,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 12,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - بيكيني',
                'name_en'        => 'Laser - Bikini',
                'supply_cost'    => 0,
                'medical_fee'    => 350,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 13,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - وجه',
                'name_en'        => 'Laser - Face',
                'supply_cost'    => 0,
                'medical_fee'    => 250,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 14,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - بيكيني + أندر أرم',
                'name_en'        => 'Laser - Bikini + Underarms',
                'supply_cost'    => 0,
                'medical_fee'    => 450,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 15,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - نصف ذراع علوي',
                'name_en'        => 'Laser - Upper Half Arm',
                'supply_cost'    => 0,
                'medical_fee'    => 400,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 16,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - نصف ذراع سفلي',
                'name_en'        => 'Laser - Lower Half Arm',
                'supply_cost'    => 0,
                'medical_fee'    => 350,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 17,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - نصف ساق علوي',
                'name_en'        => 'Laser - Upper Half Leg',
                'supply_cost'    => 0,
                'medical_fee'    => 700,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 18,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - نصف ساق سفلي',
                'name_en'        => 'Laser - Lower Half Leg',
                'supply_cost'    => 0,
                'medical_fee'    => 650,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 19,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - ذراع كامل',
                'name_en'        => 'Laser - Full Arm',
                'supply_cost'    => 0,
                'medical_fee'    => 700,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 20,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - ساق كاملة',
                'name_en'        => 'Laser - Full Leg',
                'supply_cost'    => 0,
                'medical_fee'    => 1200,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 21,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - بطن',
                'name_en'        => 'Laser - Belly',
                'supply_cost'    => 0,
                'medical_fee'    => 500,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 22,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - ظهر',
                'name_en'        => 'Laser - Back',
                'supply_cost'    => 0,
                'medical_fee'    => 600,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 23,
            ],
            [
                'category'       => 'laser',
                'name_ar'        => 'ليزر - جسم كامل (بدون ظهر وبطن)',
                'name_en'        => 'Laser - Full Body (w/o Back & Belly)',
                'slug_override'  => 'laser-full-body-wo-back-belly',
                'supply_cost'    => 0,
                'medical_fee'    => 2250,
                'show_on_home'   => false,
                'show_on_website' => false,
                'bookable'       => true,
                'display_order'  => 24,
            ],

        ];

        foreach ($services as $order => $service) {
            $categorySlug = $service['category'];
            unset($service['category']);

            // Use slug_override if provided, otherwise generate from name_en
            $slug = $service['slug_override'] ?? Str::slug($service['name_en']);
            unset($service['slug_override']);

            Service::updateOrCreate(
                ['slug' => $slug],
                array_merge($service, [
                    'category_id' => $categoryMap[$categorySlug],
                    'slug'        => $slug,
                    'status'      => 'active',
                ])
            );
        }

        // Recalculate prices: price = supply_cost + medical_fee
        // This is needed because DatabaseSeeder uses WithoutModelEvents
        // which disables the saving hook that normally computes price.
        DB::table('services')
            ->whereNotNull('medical_fee')
            ->update(['price' => DB::raw('COALESCE(supply_cost, 0) + medical_fee')]);
    }
}
