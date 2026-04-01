<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed static pages.
     */
    public function run(): void
    {
        $pages = [
            // ── About Us ────────────────────────────────────────────────
            [
                'slug'       => 'about',
                'title_ar'   => 'من نحن',
                'title_en'   => 'About Us',
                'content_ar' => '<p>أورا ديرما كلينك هي عيادة متخصصة في الأمراض الجلدية والتجميل والليزر، تأسست على يد د. أسماء حمدي الحاصلة على دكتوراه وزمالة الأمراض الجلدية والتجميل والليزر. نقدم في عيادتنا خدمات طبية وتجميلية متكاملة باستخدام أحدث الأجهزة والتقنيات العالمية المعتمدة.</p>
<p>يضم فريقنا الطبي نخبة من أطباء الجلدية والتجميل ذوي الخبرة والكفاءة العالية، ونسعى دائماً لتقديم أفضل رعاية صحية لمرضانا في بيئة مريحة وآمنة. نؤمن بأن كل مريض يستحق عناية خاصة، لذلك نقدم بروتوكولات علاجية مخصصة تناسب كل حالة على حدة.</p>
<p>تقع عيادتنا في موقع متميز في كايرو ميديكال سنتر بمدينة ٦ أكتوبر، ونعمل يومياً من الساعة 10 صباحاً حتى 10 مساءً لخدمة مرضانا.</p>',
                'content_en' => '<p>Aura Derma Clinic is a specialized dermatology, aesthetics, and laser clinic founded by Dr. Asmaa Hamdy, who holds a Doctorate and Fellowship in Dermatology, Aesthetics & Laser. We provide comprehensive medical and cosmetic services using the latest internationally certified devices and technologies.</p>
<p>Our medical team includes elite dermatology and aesthetics doctors with extensive experience and high competence. We always strive to provide the best healthcare for our patients in a comfortable and safe environment. We believe every patient deserves special care, so we provide customized treatment protocols tailored to each individual case.</p>
<p>Our clinic is conveniently located at Cairo Medical Center in 6th of October City, and we operate daily from 10:00 AM to 10:00 PM to serve our patients.</p>',
                'seo_title_ar' => 'من نحن - أورا ديرما كلينك',
                'seo_title_en' => 'About Us - Aura Derma Clinic',
                'seo_desc_ar'  => 'تعرف على عيادة أورا ديرما كلينك المتخصصة في الأمراض الجلدية والتجميل والليزر في ٦ أكتوبر.',
                'seo_desc_en'  => 'Learn about Aura Derma Clinic, specialized in dermatology, aesthetics, and laser in 6th of October City.',
            ],

            // ── Privacy Policy ──────────────────────────────────────────
            [
                'slug'       => 'privacy-policy',
                'title_ar'   => 'سياسة الخصوصية',
                'title_en'   => 'Privacy Policy',
                'content_ar' => '<p>آخر تحديث: مارس 2026</p>

<h2>مقدمة</h2>
<p>تلتزم عيادة أورا ديرما كلينك ("العيادة"، "نحن") بحماية خصوصية مرضاها وزوار موقعها الإلكتروني. توضح سياسة الخصوصية هذه كيفية جمع واستخدام وتخزين وحماية بياناتكم الشخصية والطبية عند استخدامكم لموقعنا الإلكتروني أو الاستفادة من خدماتنا العلاجية والتجميلية.</p>
<p>باستخدامكم لموقعنا أو حجز موعد لدينا، فإنكم توافقون على الممارسات الموضحة في هذه السياسة.</p>

<h2>البيانات الشخصية التي نجمعها</h2>
<p>نقوم بجمع أنواع مختلفة من البيانات لتقديم خدماتنا الطبية والتجميلية بأعلى جودة:</p>
<h3>بيانات التعريف الشخصي</h3>
<ul>
<li>الاسم الكامل</li>
<li>رقم الهاتف والبريد الإلكتروني</li>
<li>تاريخ الميلاد والجنس</li>
<li>العنوان (عند الحاجة)</li>
</ul>
<h3>البيانات الطبية</h3>
<ul>
<li>التاريخ المرضي والحالة الصحية العامة</li>
<li>الحساسية والأدوية المستخدمة</li>
<li>نتائج الفحوصات والتشخيصات الجلدية</li>
<li>صور طبية للحالة قبل وبعد العلاج (بموافقتكم)</li>
<li>بروتوكولات العلاج وملاحظات الطبيب</li>
</ul>
<h3>بيانات الحجز والمعاملات</h3>
<ul>
<li>تفاصيل المواعيد والخدمات المطلوبة</li>
<li>سجل الزيارات والمدفوعات</li>
<li>الفواتير والإيصالات</li>
</ul>

<h2>كيف نستخدم بياناتكم</h2>
<p>نستخدم البيانات المجمعة للأغراض التالية حصرا:</p>
<ul>
<li>تقديم الخدمات الطبية والتجميلية وإدارة خطط العلاج</li>
<li>إدارة المواعيد وإرسال التذكيرات</li>
<li>التواصل معكم بخصوص حالتكم الصحية ومتابعة نتائج العلاج</li>
<li>إصدار الفواتير ومعالجة المدفوعات</li>
<li>تحسين جودة خدماتنا وتطوير بروتوكولات العلاج</li>
<li>الامتثال للمتطلبات القانونية والتنظيمية المعمول بها في جمهورية مصر العربية</li>
</ul>

<h2>حماية البيانات الطبية</h2>
<p>نولي أهمية قصوى لحماية سجلاتكم الطبية والصور الخاصة بحالاتكم. نلتزم بالتالي:</p>
<ul>
<li>تخزين جميع البيانات الطبية في أنظمة مشفرة ومحمية</li>
<li>تقييد الوصول إلى السجلات الطبية على الطبيب المعالج والطاقم الطبي المعتمد فقط</li>
<li>عدم استخدام صور المرضى قبل وبعد العلاج لأي غرض تسويقي إلا بموافقة خطية صريحة</li>
<li>الاحتفاظ بالسجلات الطبية وفقا للمدة المحددة قانونا</li>
</ul>

<h2>مشاركة البيانات مع أطراف ثالثة</h2>
<p>لا نبيع أو نؤجر أو نشارك بياناتكم الشخصية مع أي طرف ثالث لأغراض تسويقية. قد نشارك بياناتكم في الحالات التالية فقط:</p>
<ul>
<li>بموافقتكم الصريحة والمسبقة</li>
<li>مع مختبرات أو مراكز طبية بغرض إجراء فحوصات ضرورية لعلاجكم</li>
<li>عند الاقتضاء القانوني أو بأمر من جهة قضائية مختصة</li>
<li>لحماية حقوق العيادة القانونية أو سلامة المرضى</li>
</ul>

<h2>ملفات تعريف الارتباط (Cookies)</h2>
<p>يستخدم موقعنا الإلكتروني ملفات تعريف الارتباط لتحسين تجربة التصفح. تساعدنا هذه الملفات في:</p>
<ul>
<li>تذكر تفضيلات اللغة والإعدادات</li>
<li>تحليل حركة المرور على الموقع لتحسين الأداء</li>
<li>توفير تجربة تصفح مخصصة</li>
</ul>
<p>يمكنكم التحكم في إعدادات ملفات تعريف الارتباط من خلال متصفحكم في أي وقت.</p>

<h2>حقوقكم</h2>
<p>تتمتعون بالحقوق التالية فيما يتعلق ببياناتكم الشخصية:</p>
<ul>
<li>الحق في الاطلاع على بياناتكم الشخصية المحفوظة لدينا</li>
<li>الحق في طلب تصحيح أي بيانات غير دقيقة</li>
<li>الحق في طلب حذف بياناتكم (مع مراعاة الالتزامات القانونية للاحتفاظ بالسجلات الطبية)</li>
<li>الحق في سحب موافقتكم على معالجة البيانات في أي وقت</li>
<li>الحق في تقديم شكوى إلى الجهات المختصة بحماية البيانات</li>
</ul>

<h2>أمان البيانات</h2>
<p>نتخذ إجراءات أمنية صارمة لحماية بياناتكم تشمل:</p>
<ul>
<li>تشفير البيانات أثناء النقل والتخزين باستخدام بروتوكولات SSL/TLS</li>
<li>نظام صلاحيات متعدد المستويات للوصول إلى البيانات</li>
<li>نسخ احتياطية دورية ومشفرة</li>
<li>تدريب الموظفين على حماية البيانات والخصوصية</li>
<li>مراجعة دورية لإجراءات الأمان وتحديثها</li>
</ul>

<h2>التعديلات على سياسة الخصوصية</h2>
<p>نحتفظ بحق تحديث هذه السياسة من وقت لآخر لتعكس التغييرات في ممارساتنا أو المتطلبات القانونية. سيتم نشر أي تعديلات على هذه الصفحة مع تحديث تاريخ "آخر تحديث". ننصح بمراجعة هذه السياسة بشكل دوري.</p>

<h2>تواصل معنا</h2>
<p>إذا كانت لديكم أي أسئلة أو استفسارات حول سياسة الخصوصية أو كيفية معالجة بياناتكم، يمكنكم التواصل معنا عبر:</p>
<ul>
<li>زيارة العيادة: كايرو ميديكال سنتر، مدينة ٦ أكتوبر</li>
<li>الهاتف: خلال ساعات العمل من 10 صباحا حتى 10 مساء</li>
<li>البريد الإلكتروني أو نموذج التواصل على الموقع الإلكتروني</li>
</ul>',
                'content_en' => '<p>Last Updated: March 2026</p>

<h2>Introduction</h2>
<p>Aura Derma Clinic ("the Clinic", "we", "us") is committed to protecting the privacy of our patients and website visitors. This Privacy Policy explains how we collect, use, store, and protect your personal and medical data when you use our website or avail of our medical and cosmetic services.</p>
<p>By using our website or booking an appointment with us, you consent to the practices described in this policy.</p>

<h2>Personal Data We Collect</h2>
<p>We collect various types of data to provide our medical and cosmetic services at the highest quality:</p>
<h3>Personal Identification Data</h3>
<ul>
<li>Full name</li>
<li>Phone number and email address</li>
<li>Date of birth and gender</li>
<li>Address (when required)</li>
</ul>
<h3>Medical Data</h3>
<ul>
<li>Medical history and general health condition</li>
<li>Allergies and current medications</li>
<li>Examination results and dermatological diagnoses</li>
<li>Medical photographs of the condition before and after treatment (with your consent)</li>
<li>Treatment protocols and physician notes</li>
</ul>
<h3>Booking and Transaction Data</h3>
<ul>
<li>Appointment details and requested services</li>
<li>Visit history and payment records</li>
<li>Invoices and receipts</li>
</ul>

<h2>How We Use Your Data</h2>
<p>We use the collected data exclusively for the following purposes:</p>
<ul>
<li>Providing medical and cosmetic services and managing treatment plans</li>
<li>Managing appointments and sending reminders</li>
<li>Communicating with you regarding your health condition and treatment follow-ups</li>
<li>Issuing invoices and processing payments</li>
<li>Improving our service quality and developing treatment protocols</li>
<li>Complying with legal and regulatory requirements applicable in the Arab Republic of Egypt</li>
</ul>

<h2>Medical Data Protection</h2>
<p>We place the utmost importance on protecting your medical records and condition photographs. We commit to:</p>
<ul>
<li>Storing all medical data in encrypted and protected systems</li>
<li>Restricting access to medical records to the treating physician and authorized medical staff only</li>
<li>Not using patient before-and-after photos for any marketing purpose without explicit written consent</li>
<li>Retaining medical records in accordance with legally mandated retention periods</li>
</ul>

<h2>Third-Party Data Sharing</h2>
<p>We do not sell, rent, or share your personal data with any third party for marketing purposes. We may share your data only in the following cases:</p>
<ul>
<li>With your explicit prior consent</li>
<li>With laboratories or medical centers for conducting tests necessary for your treatment</li>
<li>When required by law or by order of a competent judicial authority</li>
<li>To protect the legal rights of the Clinic or patient safety</li>
</ul>

<h2>Cookies</h2>
<p>Our website uses cookies to enhance your browsing experience. These cookies help us:</p>
<ul>
<li>Remember language preferences and settings</li>
<li>Analyze website traffic to improve performance</li>
<li>Provide a personalized browsing experience</li>
</ul>
<p>You can control cookie settings through your browser at any time.</p>

<h2>Your Rights</h2>
<p>You have the following rights regarding your personal data:</p>
<ul>
<li>The right to access your personal data stored with us</li>
<li>The right to request correction of any inaccurate data</li>
<li>The right to request deletion of your data (subject to legal obligations for medical record retention)</li>
<li>The right to withdraw your consent to data processing at any time</li>
<li>The right to file a complaint with the competent data protection authorities</li>
</ul>

<h2>Data Security</h2>
<p>We implement strict security measures to protect your data, including:</p>
<ul>
<li>Data encryption during transfer and storage using SSL/TLS protocols</li>
<li>Multi-level access permission system for data access</li>
<li>Regular encrypted backups</li>
<li>Employee training on data protection and privacy</li>
<li>Periodic review and update of security procedures</li>
</ul>

<h2>Changes to This Privacy Policy</h2>
<p>We reserve the right to update this policy from time to time to reflect changes in our practices or legal requirements. Any amendments will be posted on this page with an updated "Last Updated" date. We recommend reviewing this policy periodically.</p>

<h2>Contact Us</h2>
<p>If you have any questions or inquiries about this Privacy Policy or how your data is processed, you can contact us via:</p>
<ul>
<li>Visit the Clinic: Cairo Medical Center, 6th of October City</li>
<li>Phone: During working hours from 10:00 AM to 10:00 PM</li>
<li>Email or the contact form on our website</li>
</ul>',
                'seo_title_ar' => 'سياسة الخصوصية - أورا ديرما كلينك',
                'seo_title_en' => 'Privacy Policy - Aura Derma Clinic',
                'seo_desc_ar'  => 'سياسة الخصوصية وحماية البيانات الشخصية والطبية في عيادة أورا ديرما كلينك للأمراض الجلدية والتجميل.',
                'seo_desc_en'  => 'Privacy policy and protection of personal and medical data at Aura Derma Clinic for dermatology and aesthetics.',
            ],

            // ── Terms of Use ────────────────────────────────────────────
            [
                'slug'       => 'terms-of-use',
                'title_ar'   => 'الشروط والأحكام',
                'title_en'   => 'Terms & Conditions',
                'content_ar' => '<p>آخر تحديث: مارس 2026</p>

<h2>مقدمة</h2>
<p>مرحبا بكم في الموقع الإلكتروني لعيادة أورا ديرما كلينك. باستخدامكم لهذا الموقع أو الاستفادة من خدماتنا، فإنكم توافقون على الالتزام بهذه الشروط والأحكام. يرجى قراءتها بعناية قبل استخدام الموقع أو حجز أي خدمة. إذا كنتم لا توافقون على أي من هذه الشروط، يرجى عدم استخدام الموقع.</p>

<h2>الخدمات الطبية والتجميلية</h2>
<p>تقدم عيادة أورا ديرما كلينك خدمات متخصصة في مجال الأمراض الجلدية والتجميل والليزر. يرجى مراعاة ما يلي:</p>
<ul>
<li>جميع الخدمات الطبية والتجميلية تقدم تحت إشراف أطباء متخصصين ومرخصين</li>
<li>نتائج العلاجات التجميلية قد تختلف من شخص لآخر حسب طبيعة البشرة والحالة الصحية</li>
<li>يلتزم المريض بإبلاغ الطبيب بتاريخه المرضي الكامل وأي أدوية يتناولها</li>
<li>بعض الإجراءات التجميلية تتطلب توقيع نموذج موافقة مسبقة قبل البدء</li>
<li>لا تضمن العيادة نتائج محددة لأي إجراء طبي أو تجميلي</li>
</ul>

<h2>المحتوى الطبي والتثقيفي</h2>
<p>المعلومات المنشورة على هذا الموقع هي لأغراض تثقيفية وتعريفية فقط، وتخضع للتوضيحات التالية:</p>
<ul>
<li>لا تعتبر المعلومات المنشورة بديلا عن الاستشارة الطبية المتخصصة</li>
<li>لا يجب اتخاذ أي قرار علاجي بناء على محتوى الموقع فقط دون استشارة الطبيب</li>
<li>المعلومات الطبية على الموقع تمثل معلومات عامة وقد لا تنطبق على حالتكم الخاصة</li>
<li>نسعى لتقديم معلومات دقيقة ومحدثة، لكننا لا نضمن خلو المحتوى من الأخطاء</li>
</ul>

<h2>حجز المواعيد</h2>
<p>عند حجز موعد من خلال الموقع الإلكتروني أو عبر الهاتف، تنطبق الشروط التالية:</p>
<ul>
<li>الحجز عبر الموقع يعتبر طلب حجز مبدئي ويتطلب تأكيدا من العيادة</li>
<li>يجب الحضور في الموعد المحدد أو إبلاغنا بالإلغاء قبل 24 ساعة على الأقل</li>
<li>التأخر عن الموعد لأكثر من 15 دقيقة قد يؤدي إلى إعادة جدولة الموعد</li>
<li>الإلغاء المتكرر دون إشعار مسبق قد يؤثر على أولوية الحجز مستقبلا</li>
<li>تحتفظ العيادة بحق إعادة جدولة المواعيد عند الضرورة مع إشعار المريض مسبقا</li>
</ul>

<h2>الأسعار والدفع</h2>
<ul>
<li>أسعار الخدمات المعروضة على الموقع هي أسعار استرشادية وقد تتغير دون إشعار مسبق</li>
<li>السعر النهائي يحدد بعد الفحص والتقييم من قبل الطبيب المعالج</li>
<li>يتم الدفع في العيادة عند تلقي الخدمة ما لم يتم الاتفاق على خلاف ذلك</li>
<li>في حالة الباقات العلاجية متعددة الجلسات، يلتزم المريض بإتمام الجلسات خلال المدة المحددة</li>
<li>لا يتم استرداد المبالغ المدفوعة مقابل الجلسات المكتملة</li>
</ul>

<h2>حقوق الملكية الفكرية</h2>
<p>جميع المحتويات المنشورة على هذا الموقع محمية بموجب قوانين الملكية الفكرية:</p>
<ul>
<li>النصوص والمقالات والمحتوى التثقيفي</li>
<li>الصور والرسومات والتصاميم</li>
<li>شعار أورا ديرما كلينك والعلامات التجارية</li>
<li>تصميم الموقع وواجهة المستخدم</li>
</ul>
<p>يحظر نسخ أو إعادة نشر أو توزيع أي محتوى من هذا الموقع دون إذن خطي مسبق من العيادة.</p>

<h2>صور قبل وبعد العلاج</h2>
<p>فيما يخص الصور التوضيحية لنتائج العلاجات المنشورة على الموقع:</p>
<ul>
<li>جميع الصور تم نشرها بموافقة خطية من المرضى المعنيين</li>
<li>النتائج المعروضة هي نتائج فعلية لكنها لا تمثل ضمانا لنتائج مماثلة</li>
<li>تختلف النتائج من شخص لآخر حسب عوامل متعددة منها نوع البشرة والحالة الصحية والالتزام بتعليمات ما بعد العلاج</li>
</ul>

<h2>إخلاء المسؤولية</h2>
<ul>
<li>لا تتحمل العيادة مسؤولية أي أضرار ناتجة عن استخدام المعلومات المنشورة على الموقع دون استشارة طبية</li>
<li>لا نضمن توفر الموقع بشكل مستمر وقد يتعرض لانقطاعات تقنية مؤقتة</li>
<li>روابط المواقع الخارجية المدرجة على موقعنا لا تعني تأييدنا لمحتواها ولا نتحمل مسؤوليتها</li>
</ul>

<h2>القانون الحاكم</h2>
<p>تخضع هذه الشروط والأحكام لقوانين جمهورية مصر العربية وتفسر وفقا لها. أي نزاع ينشأ عن استخدام هذا الموقع أو الخدمات المقدمة يخضع للاختصاص القضائي للمحاكم المصرية المختصة.</p>

<h2>التعديلات على الشروط والأحكام</h2>
<p>تحتفظ عيادة أورا ديرما كلينك بحق تعديل هذه الشروط والأحكام في أي وقت. سيتم نشر أي تعديلات على هذه الصفحة ويعتبر استمراركم في استخدام الموقع بعد التعديل موافقة على الشروط الجديدة.</p>

<h2>تواصل معنا</h2>
<p>لأي استفسارات حول هذه الشروط والأحكام، يمكنكم التواصل معنا عبر:</p>
<ul>
<li>زيارة العيادة: كايرو ميديكال سنتر، مدينة ٦ أكتوبر</li>
<li>الهاتف: خلال ساعات العمل من 10 صباحا حتى 10 مساء</li>
<li>البريد الإلكتروني أو نموذج التواصل على الموقع الإلكتروني</li>
</ul>',
                'content_en' => '<p>Last Updated: March 2026</p>

<h2>Introduction</h2>
<p>Welcome to the Aura Derma Clinic website. By using this website or availing of our services, you agree to comply with these Terms and Conditions. Please read them carefully before using the website or booking any service. If you do not agree to any of these terms, please do not use the website.</p>

<h2>Medical and Cosmetic Services</h2>
<p>Aura Derma Clinic provides specialized services in dermatology, aesthetics, and laser treatments. Please note the following:</p>
<ul>
<li>All medical and cosmetic services are provided under the supervision of specialized and licensed physicians</li>
<li>Results of cosmetic treatments may vary from person to person depending on skin type and health condition</li>
<li>Patients are required to disclose their complete medical history and any medications they are taking</li>
<li>Some cosmetic procedures require signing a prior consent form before commencement</li>
<li>The Clinic does not guarantee specific results for any medical or cosmetic procedure</li>
</ul>

<h2>Medical and Educational Content</h2>
<p>Information published on this website is for educational and informational purposes only, subject to the following clarifications:</p>
<ul>
<li>Published information is not a substitute for specialized medical consultation</li>
<li>No treatment decisions should be made based solely on website content without consulting a physician</li>
<li>Medical information on the website represents general information and may not apply to your specific condition</li>
<li>We strive to provide accurate and up-to-date information, but we do not guarantee the content is error-free</li>
</ul>

<h2>Appointment Booking</h2>
<p>When booking an appointment through the website or by phone, the following terms apply:</p>
<ul>
<li>Online booking is considered a preliminary booking request and requires confirmation from the Clinic</li>
<li>You must attend at the scheduled time or notify us of cancellation at least 24 hours in advance</li>
<li>Arriving more than 15 minutes late may result in rescheduling the appointment</li>
<li>Frequent cancellations without prior notice may affect future booking priority</li>
<li>The Clinic reserves the right to reschedule appointments when necessary with prior patient notification</li>
</ul>

<h2>Pricing and Payment</h2>
<ul>
<li>Service prices displayed on the website are indicative and may change without prior notice</li>
<li>The final price is determined after examination and assessment by the treating physician</li>
<li>Payment is made at the Clinic upon receiving the service unless otherwise agreed</li>
<li>For multi-session treatment packages, patients are required to complete sessions within the specified timeframe</li>
<li>Amounts paid for completed sessions are non-refundable</li>
</ul>

<h2>Intellectual Property Rights</h2>
<p>All content published on this website is protected under intellectual property laws:</p>
<ul>
<li>Texts, articles, and educational content</li>
<li>Photos, graphics, and designs</li>
<li>Aura Derma Clinic logo and trademarks</li>
<li>Website design and user interface</li>
</ul>
<p>Copying, republishing, or distributing any content from this website without prior written permission from the Clinic is prohibited.</p>

<h2>Before and After Photos</h2>
<p>Regarding illustrative photos of treatment results published on the website:</p>
<ul>
<li>All photos have been published with written consent from the respective patients</li>
<li>Results shown are actual results but do not represent a guarantee of similar outcomes</li>
<li>Results vary from person to person based on multiple factors including skin type, health condition, and adherence to post-treatment instructions</li>
</ul>

<h2>Disclaimer</h2>
<ul>
<li>The Clinic assumes no responsibility for any damages resulting from the use of information published on the website without medical consultation</li>
<li>We do not guarantee continuous website availability and it may experience temporary technical interruptions</li>
<li>External website links included on our website do not imply our endorsement of their content, and we bear no responsibility for them</li>
</ul>

<h2>Governing Law</h2>
<p>These Terms and Conditions are governed by and construed in accordance with the laws of the Arab Republic of Egypt. Any dispute arising from the use of this website or the services provided shall be subject to the jurisdiction of the competent Egyptian courts.</p>

<h2>Amendments to Terms and Conditions</h2>
<p>Aura Derma Clinic reserves the right to modify these Terms and Conditions at any time. Any amendments will be posted on this page, and your continued use of the website after modification constitutes acceptance of the new terms.</p>

<h2>Contact Us</h2>
<p>For any inquiries about these Terms and Conditions, you can contact us via:</p>
<ul>
<li>Visit the Clinic: Cairo Medical Center, 6th of October City</li>
<li>Phone: During working hours from 10:00 AM to 10:00 PM</li>
<li>Email or the contact form on our website</li>
</ul>',
                'seo_title_ar' => 'الشروط والأحكام - أورا ديرما كلينك',
                'seo_title_en' => 'Terms & Conditions - Aura Derma Clinic',
                'seo_desc_ar'  => 'الشروط والأحكام الخاصة باستخدام موقع وخدمات عيادة أورا ديرما كلينك للأمراض الجلدية والتجميل.',
                'seo_desc_en'  => 'Terms and conditions for using Aura Derma Clinic website and services for dermatology and aesthetics.',
            ],

            // ── Booking Policy ──────────────────────────────────────────
            [
                'slug'       => 'booking-policy',
                'title_ar'   => 'سياسة الحجز',
                'title_en'   => 'Booking Policy',
                'content_ar' => '<h3>حجز المواعيد</h3>
<p>يمكنكم حجز المواعيد من خلال الاتصال الهاتفي أو الواتساب أو عبر نموذج الحجز على الموقع الإلكتروني. سيتم التأكيد على الموعد خلال ساعات العمل.</p>
<h3>الإلغاء وإعادة الجدولة</h3>
<p>في حالة الرغبة في إلغاء أو تغيير الموعد، يرجى إبلاغنا قبل 24 ساعة على الأقل من الموعد المحدد. الإلغاء المتكرر دون إخطار مسبق قد يؤثر على أولوية الحجز مستقبلاً.</p>
<h3>التأخير عن الموعد</h3>
<p>نرجو الحضور قبل الموعد بـ 10 دقائق على الأقل. في حالة التأخير لأكثر من 15 دقيقة، قد يتم تأجيل الموعد حسب توفر المواعيد.</p>
<h3>الاستشارة الأولى</h3>
<p>في الزيارة الأولى، يقوم الطبيب بتقييم حالتك بشكل شامل ووضع خطة علاجية مخصصة. يرجى إحضار أي تقارير طبية سابقة ذات صلة.</p>',
                'content_en' => '<h3>Appointment Booking</h3>
<p>You can book appointments by phone, WhatsApp, or through the booking form on our website. Appointments will be confirmed during working hours.</p>
<h3>Cancellation and Rescheduling</h3>
<p>If you wish to cancel or reschedule, please notify us at least 24 hours before the scheduled appointment. Frequent cancellations without prior notice may affect future booking priority.</p>
<h3>Late Arrival</h3>
<p>Please arrive at least 10 minutes before your appointment. Delays of more than 15 minutes may result in rescheduling based on availability.</p>
<h3>Initial Consultation</h3>
<p>During the first visit, the doctor will comprehensively evaluate your condition and develop a customized treatment plan. Please bring any relevant previous medical reports.</p>',
                'seo_title_ar' => 'سياسة الحجز - أورا ديرما كلينك',
                'seo_title_en' => 'Booking Policy - Aura Derma Clinic',
                'seo_desc_ar'  => 'تعرف على سياسة حجز المواعيد في عيادة أورا ديرما كلينك.',
                'seo_desc_en'  => 'Learn about the appointment booking policy at Aura Derma Clinic.',
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
