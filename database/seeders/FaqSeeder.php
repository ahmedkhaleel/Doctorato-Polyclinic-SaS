<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed frequently asked questions grouped by category.
     */
    public function run(): void
    {
        $faqs = [
            // ── General ─────────────────────────────────────────────────
            [
                'category'     => 'general',
                'question_ar'  => 'أين يقع عيادة أورا ديرما كلينك؟',
                'question_en'  => 'Where is Aura Derma Clinic located?',
                'answer_ar'    => 'تقع عيادة أورا ديرما كلينك في ٦ أكتوبر - كايرو ميديكال سنتر (CMC) - المحور المركزي - الدور الثاني - عيادة رقم 71.',
                'answer_en'    => 'Aura Derma Clinic is located at CMC (Cairo Medical Center), Central Axis, 6th of October City, 2nd Floor, Clinic No. 71.',
                'display_order' => 1,
            ],
            [
                'category'     => 'general',
                'question_ar'  => 'ما هي مواعيد العمل في العيادة؟',
                'question_en'  => 'What are the clinic working hours?',
                'answer_ar'    => 'العيادة تعمل يومياً من الساعة 10:00 صباحاً حتى 10:00 مساءً.',
                'answer_en'    => 'The clinic operates daily from 10:00 AM to 10:00 PM.',
                'display_order' => 2,
            ],
            [
                'category'     => 'general',
                'question_ar'  => 'من هم الأطباء في العيادة؟',
                'question_en'  => 'Who are the doctors at the clinic?',
                'answer_ar'    => 'يضم فريق أورا ديرما كلينك نخبة من أطباء الجلدية والتجميل بقيادة د. أسماء حمدي الحاصلة على دكتوراه وزمالة في الأمراض الجلدية والتجميل والليزر، بالإضافة إلى فريق متميز من الأخصائيات.',
                'answer_en'    => 'Aura Derma Clinic\'s team includes elite dermatology and aesthetics doctors led by Dr. Asmaa Hamdy, who holds a Doctorate and Fellowship in Dermatology, Aesthetics & Laser, along with a distinguished team of specialists.',
                'display_order' => 3,
            ],
            [
                'category'     => 'general',
                'question_ar'  => 'هل العيادة مجهزة بأحدث الأجهزة؟',
                'question_en'  => 'Is the clinic equipped with the latest devices?',
                'answer_ar'    => 'نعم، عيادة أورا ديرما كلينك مجهزة بأحدث الأجهزة العالمية المعتمدة في مجال الجلدية والتجميل والليزر، ونحرص على تحديث أجهزتنا باستمرار لتقديم أفضل خدمة.',
                'answer_en'    => 'Yes, Aura Derma Clinic is equipped with the latest internationally certified devices in dermatology, aesthetics, and laser. We continuously update our equipment to provide the best service.',
                'display_order' => 4,
            ],

            // ── Laser ───────────────────────────────────────────────────
            [
                'category'     => 'laser',
                'question_ar'  => 'كم عدد جلسات الليزر المطلوبة لإزالة الشعر؟',
                'question_en'  => 'How many laser sessions are needed for hair removal?',
                'answer_ar'    => 'يختلف عدد الجلسات حسب المنطقة ونوع البشرة ولون الشعر، لكن عادةً ما يتطلب الأمر من 6 إلى 10 جلسات للحصول على أفضل النتائج مع فترة فاصلة من 4 إلى 6 أسابيع بين كل جلسة.',
                'answer_en'    => 'The number of sessions varies depending on the area, skin type, and hair color. Typically, 6 to 10 sessions are needed for optimal results, with 4 to 6 weeks between each session.',
                'display_order' => 1,
            ],
            [
                'category'     => 'laser',
                'question_ar'  => 'هل إزالة الشعر بالليزر مؤلمة؟',
                'question_en'  => 'Is laser hair removal painful?',
                'answer_ar'    => 'أجهزة الليزر الحديثة المستخدمة في العيادة مزودة بتقنية تبريد متطورة تقلل من الألم بشكل كبير. قد يشعر المريض بوخز خفيف يشبه لسعة المطاط ولكنه محتمل تماماً.',
                'answer_en'    => 'The modern laser devices used at our clinic are equipped with advanced cooling technology that significantly reduces pain. Patients may feel a slight tingling similar to a rubber band snap, which is completely tolerable.',
                'display_order' => 2,
            ],
            [
                'category'     => 'laser',
                'question_ar'  => 'هل الليزر آمن لجميع أنواع البشرة؟',
                'question_en'  => 'Is laser safe for all skin types?',
                'answer_ar'    => 'نعم، نستخدم أجهزة ليزر متطورة مناسبة لجميع أنواع البشرة بما في ذلك البشرة الداكنة. يقوم الطبيب بضبط إعدادات الجهاز وفقاً لنوع بشرتك لضمان الأمان والفعالية.',
                'answer_en'    => 'Yes, we use advanced laser devices suitable for all skin types, including dark skin. The doctor adjusts the device settings according to your skin type to ensure safety and effectiveness.',
                'display_order' => 3,
            ],
            [
                'category'     => 'laser',
                'question_ar'  => 'ما هي التعليمات قبل وبعد جلسة الليزر؟',
                'question_en'  => 'What are the instructions before and after a laser session?',
                'answer_ar'    => 'قبل الجلسة: تجنب التعرض للشمس وعدم إزالة الشعر بالشمع أو النتف لمدة أسبوعين. بعد الجلسة: استخدام واقي الشمس وكريم مرطب وتجنب الحرارة العالية والساونا لمدة 48 ساعة.',
                'answer_en'    => 'Before the session: Avoid sun exposure and do not wax or pluck hair for two weeks. After the session: Use sunscreen and moisturizer, and avoid high heat and saunas for 48 hours.',
                'display_order' => 4,
            ],

            // ── Skin Treatments ─────────────────────────────────────────
            [
                'category'     => 'skin',
                'question_ar'  => 'ما الفرق بين البوتوكس والفيلر؟',
                'question_en'  => 'What is the difference between Botox and Filler?',
                'answer_ar'    => 'البوتوكس يعمل على إرخاء العضلات لتقليل التجاعيد الناتجة عن الحركة (مثل تجاعيد الجبهة وحول العينين)، بينما الفيلر يعمل على ملء الفراغات وإضافة حجم (مثل نفخ الشفاه وتحديد الفك).',
                'answer_en'    => 'Botox works by relaxing muscles to reduce wrinkles caused by movement (such as forehead and around-eye wrinkles), while Filler works by filling spaces and adding volume (such as lip augmentation and jawline definition).',
                'display_order' => 1,
            ],
            [
                'category'     => 'skin',
                'question_ar'  => 'كم تدوم نتائج الهيدرافيشل؟',
                'question_en'  => 'How long do HydraFacial results last?',
                'answer_ar'    => 'تظهر نتائج الهيدرافيشل فوراً بعد الجلسة وتستمر من أسبوع إلى أسبوعين. ينصح بعمل جلسة شهرية للحفاظ على نضارة وصحة البشرة بشكل مستمر.',
                'answer_en'    => 'HydraFacial results are visible immediately after the session and last 1 to 2 weeks. Monthly sessions are recommended to maintain ongoing skin health and radiance.',
                'display_order' => 2,
            ],
            [
                'category'     => 'skin',
                'question_ar'  => 'هل التقشير الكيميائي مناسب لجميع أنواع البشرة؟',
                'question_en'  => 'Is chemical peeling suitable for all skin types?',
                'answer_ar'    => 'يتوفر لدينا أنواع مختلفة من التقشير الكيميائي تناسب جميع أنواع البشرة. يقوم الطبيب بتقييم بشرتك واختيار النوع والتركيز المناسب لحالتك للحصول على أفضل النتائج.',
                'answer_en'    => 'We offer different types of chemical peels suitable for all skin types. The doctor evaluates your skin and selects the appropriate type and concentration for your condition to achieve the best results.',
                'display_order' => 3,
            ],
            [
                'category'     => 'skin',
                'question_ar'  => 'ما هي جلسات البلازما PRP وما فوائدها؟',
                'question_en'  => 'What is PRP therapy and what are its benefits?',
                'answer_ar'    => 'البلازما الغنية بالصفائح الدموية (PRP) هي علاج يتم فيه سحب عينة دم من المريض وفصل البلازما ثم حقنها في البشرة أو فروة الرأس. تساعد في تجديد البشرة وعلاج تساقط الشعر وتحفيز إنتاج الكولاجين.',
                'answer_en'    => 'Platelet-Rich Plasma (PRP) is a treatment where a blood sample is drawn from the patient, the plasma is separated, then injected into the skin or scalp. It helps rejuvenate skin, treat hair loss, and stimulate collagen production.',
                'display_order' => 4,
            ],

            // ── Booking ─────────────────────────────────────────────────
            [
                'category'     => 'booking',
                'question_ar'  => 'كيف يمكنني حجز موعد في العيادة؟',
                'question_en'  => 'How can I book an appointment at the clinic?',
                'answer_ar'    => 'يمكنك حجز موعد من خلال الاتصال على أرقام العيادة (01007729159 أو 0238244047)، أو عبر الواتساب، أو من خلال نموذج الحجز على موقعنا الإلكتروني.',
                'answer_en'    => 'You can book an appointment by calling the clinic numbers (01007729159 or 0238244047), via WhatsApp, or through the booking form on our website.',
                'display_order' => 1,
            ],
            [
                'category'     => 'booking',
                'question_ar'  => 'هل يجب حجز موعد مسبق أم يمكن الحضور مباشرة؟',
                'question_en'  => 'Do I need to book in advance or can I walk in?',
                'answer_ar'    => 'ننصح بحجز موعد مسبق لضمان الحصول على الخدمة في الوقت المناسب وتجنب الانتظار. ومع ذلك، نرحب بالحالات الطارئة والزيارات المباشرة حسب توفر المواعيد.',
                'answer_en'    => 'We recommend booking in advance to ensure timely service and avoid waiting. However, we welcome emergency cases and walk-in visits based on appointment availability.',
                'display_order' => 2,
            ],
            [
                'category'     => 'booking',
                'question_ar'  => 'هل يمكنني إلغاء أو تغيير موعدي؟',
                'question_en'  => 'Can I cancel or reschedule my appointment?',
                'answer_ar'    => 'نعم، يمكنك إلغاء أو تغيير موعدك بالتواصل معنا قبل الموعد بـ 24 ساعة على الأقل عبر الهاتف أو الواتساب.',
                'answer_en'    => 'Yes, you can cancel or reschedule your appointment by contacting us at least 24 hours before your appointment via phone or WhatsApp.',
                'display_order' => 3,
            ],

            // ── Pricing ─────────────────────────────────────────────────
            [
                'category'     => 'pricing',
                'question_ar'  => 'ما هي أسعار الخدمات في العيادة؟',
                'question_en'  => 'What are the service prices at the clinic?',
                'answer_ar'    => 'تختلف أسعار الخدمات حسب نوع العلاج والحالة الفردية لكل مريض. يتم تحديد التكلفة الدقيقة بعد الاستشارة والفحص الطبي. يسعدنا تقديم استشارة مبدئية لتحديد العلاج المناسب والتكلفة.',
                'answer_en'    => 'Service prices vary depending on the type of treatment and each patient\'s individual condition. The exact cost is determined after consultation and medical examination. We are happy to provide an initial consultation to determine the appropriate treatment and cost.',
                'display_order' => 1,
            ],
            [
                'category'     => 'pricing',
                'question_ar'  => 'هل تتوفر عروض أو باقات مخفضة؟',
                'question_en'  => 'Are there any offers or discounted packages?',
                'answer_ar'    => 'نعم، نقدم عروضاً وباقات مخفضة بشكل دوري على مختلف الخدمات. تابعونا على وسائل التواصل الاجتماعي أو تواصلوا معنا للاطلاع على أحدث العروض المتاحة.',
                'answer_en'    => 'Yes, we regularly offer discounts and packages on various services. Follow us on social media or contact us to learn about the latest available offers.',
                'display_order' => 2,
            ],
            [
                'category'     => 'pricing',
                'question_ar'  => 'هل يمكن الدفع بالتقسيط؟',
                'question_en'  => 'Is installment payment available?',
                'answer_ar'    => 'نعم، نوفر خيارات دفع مرنة تشمل التقسيط على بعض الخدمات والباقات العلاجية. يرجى الاستفسار عند الحجز عن خيارات الدفع المتاحة.',
                'answer_en'    => 'Yes, we offer flexible payment options including installments for certain services and treatment packages. Please inquire about available payment options when booking.',
                'display_order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                [
                    'category'    => $faq['category'],
                    'question_en' => $faq['question_en'],
                ],
                $faq
            );
        }
    }
}
