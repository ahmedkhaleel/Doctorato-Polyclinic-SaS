<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Seed sample client testimonials.
     */
    public function run(): void
    {
        $serviceIds = Service::pluck('id')->toArray();

        $testimonials = [
            [
                'client_name_ar' => 'نورا أحمد',
                'client_name_en' => 'Noura Ahmed',
                'service_id'     => $serviceIds[0] ?? null,
                'rating'         => 5,
                'review_ar'      => 'تجربة رائعة في عيادة دكتوراتو كلينك. الدكتورة أسماء حمدي متميزة جداً وشرحت لي كل خطوات العلاج بالتفصيل. النتائج كانت مذهلة وبشرتي أصبحت أفضل بكثير بعد جلسات علاج حب الشباب.',
                'review_en'      => 'An amazing experience at Doctorato Polyclinic. Dr. Asmaa Hamdy is exceptional and explained every step of the treatment in detail. The results were stunning and my skin improved dramatically after the acne treatment sessions.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 1,
            ],
            [
                'client_name_ar' => 'سارة محمد',
                'client_name_en' => 'Sara Mohamed',
                'service_id'     => $serviceIds[1] ?? null,
                'rating'         => 5,
                'review_ar'      => 'أنصح الجميع بزيارة عيادة دكتوراتو. الأجهزة حديثة جداً والفريق الطبي محترف ولطيف. أجريت جلسات ليزر لإزالة الشعر والنتائج فاقت توقعاتي. شكراً لكم.',
                'review_en'      => 'I recommend everyone to visit Aura Clinic. The equipment is very modern and the medical team is professional and friendly. I had laser hair removal sessions and the results exceeded my expectations. Thank you.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 2,
            ],
            [
                'client_name_ar' => 'منى عبد الله',
                'client_name_en' => 'Mona Abdullah',
                'service_id'     => $serviceIds[2] ?? null,
                'rating'         => 4,
                'review_ar'      => 'العيادة نظيفة ومرتبة والاستقبال ممتاز. عملت جلسة هيدرافيشل وكانت النتيجة واضحة من أول جلسة. بشرتي أصبحت مشرقة وناعمة. سأستمر بالمتابعة معهم.',
                'review_en'      => 'The clinic is clean and organized with excellent reception. I had a HydraFacial session and the result was noticeable from the first session. My skin became radiant and smooth. I will continue my follow-ups with them.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 3,
            ],
            [
                'client_name_ar' => 'فاطمة حسن',
                'client_name_en' => 'Fatma Hassan',
                'service_id'     => $serviceIds[3] ?? null,
                'rating'         => 5,
                'review_ar'      => 'من أفضل العيادات التي زرتها. الدكتورة متابعة لحالتي بشكل مستمر والنتائج ممتازة. عملت حقن بوتوكس وكانت النتيجة طبيعية جداً ومرضية. أشكر كل الفريق الطبي.',
                'review_en'      => 'One of the best clinics I have visited. The doctor follows up on my case continuously and the results are excellent. I had Botox injections and the result was very natural and satisfying. I thank the entire medical team.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 4,
            ],
            [
                'client_name_ar' => 'هدى إبراهيم',
                'client_name_en' => 'Hoda Ibrahim',
                'service_id'     => $serviceIds[4] ?? null,
                'rating'         => 4,
                'review_ar'      => 'تجربتي مع عيادة دكتوراتو كانت ممتازة. الموظفون ودودون والأطباء ذوو خبرة عالية. عملت جلسات تقشير كيميائي وبشرتي تحسنت بشكل ملحوظ. المكان مريح ونظيف.',
                'review_en'      => 'My experience with Aura Clinic was excellent. The staff is friendly and the doctors are highly experienced. I had chemical peeling sessions and my skin improved noticeably. The place is comfortable and clean.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 5,
            ],
            [
                'client_name_ar' => 'ريم خالد',
                'client_name_en' => 'Reem Khaled',
                'service_id'     => $serviceIds[5] ?? null,
                'rating'         => 5,
                'review_ar'      => 'عيادة متميزة بكل المقاييس. الدكتورة أسماء حمدي من أفضل أطباء الجلدية. عملت جلسات فيلر للشفايف والنتيجة طبيعية وجميلة جداً. أنصح بها بشدة لكل من تبحث عن نتائج حقيقية.',
                'review_en'      => 'An outstanding clinic by all standards. Dr. Asmaa Hamdy is one of the best dermatologists. I had lip filler sessions and the result was very natural and beautiful. I highly recommend it to anyone looking for real results.',
                'photo'          => null,
                'video_url'      => null,
                'status'         => 'published',
                'display_order'  => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::updateOrCreate(
                ['client_name_en' => $testimonial['client_name_en']],
                $testimonial
            );
        }
    }
}
