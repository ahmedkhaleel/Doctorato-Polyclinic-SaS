<?php

namespace Database\Seeders;

use App\Models\SeoPage;
use Illuminate\Database\Seeder;

class SeoPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'page_identifier' => 'home',
                'page_name_en' => 'Home Page',
                'page_name_ar' => 'الصفحة الرئيسية',
                'title_ar' => 'عيادة أورا ديرما للجلدية والتجميل',
                'title_en' => 'AURA Derma Aesthetic Clinic',
                'description_ar' => 'عيادة أورا ديرما للجلدية والتجميل - أحدث تقنيات العناية بالبشرة والتجميل في مصر مع نخبة من أمهر الأطباء المتخصصين.',
                'description_en' => 'AURA Derma Aesthetic Clinic - The latest skincare and cosmetic technologies in Egypt with an elite team of specialist doctors.',
                'keywords' => 'dermatology, skincare, cosmetic clinic, Egypt, عيادة جلدية, تجميل, عناية بالبشرة, مصر, أورا ديرما',
            ],
            [
                'page_identifier' => 'about',
                'page_name_en' => 'About Page',
                'page_name_ar' => 'صفحة من نحن',
                'title_ar' => 'عن العيادة - عيادة أورا ديرما',
                'title_en' => 'About Us - AURA Derma Clinic',
                'description_ar' => 'تعرف على عيادة أورا ديرما للجلدية والتجميل، رؤيتنا ورسالتنا وفريقنا الطبي المتميز.',
                'description_en' => 'Learn about AURA Derma Aesthetic Clinic, our vision, mission, and distinguished medical team.',
                'keywords' => 'about AURA Derma, dermatology clinic, cosmetic clinic Egypt, عن أورا ديرما, عيادة تجميل',
            ],
            [
                'page_identifier' => 'services',
                'page_name_en' => 'Services Page',
                'page_name_ar' => 'صفحة الخدمات',
                'title_ar' => 'خدماتنا - عيادة أورا ديرما',
                'title_en' => 'Our Services - AURA Derma Clinic',
                'description_ar' => 'استكشف خدمات عيادة أورا ديرما للجلدية والتجميل - علاجات البشرة والليزر والتجميل بأحدث التقنيات.',
                'description_en' => 'Explore AURA Derma Clinic services - skin treatments, laser, and cosmetic procedures with the latest technologies.',
                'keywords' => 'dermatology services, laser treatment, cosmetic procedures, skin care, خدمات جلدية, ليزر, تجميل',
            ],
            [
                'page_identifier' => 'gallery',
                'page_name_en' => 'Gallery Page',
                'page_name_ar' => 'صفحة المعرض',
                'title_ar' => 'معرض الصور - عيادة أورا ديرما',
                'title_en' => 'Gallery - AURA Derma Clinic',
                'description_ar' => 'شاهد معرض صور عيادة أورا ديرما - نتائج العلاجات والعيادة وفريق العمل.',
                'description_en' => 'View AURA Derma Clinic gallery - treatment results, clinic facilities, and our team.',
                'keywords' => 'clinic gallery, before after, treatment results, معرض العيادة, نتائج العلاج',
            ],
            [
                'page_identifier' => 'offers',
                'page_name_en' => 'Offers Page',
                'page_name_ar' => 'صفحة العروض',
                'title_ar' => 'العروض - عيادة أورا ديرما',
                'title_en' => 'Offers - AURA Derma Clinic',
                'description_ar' => 'اكتشف أحدث عروض وخصومات عيادة أورا ديرما للجلدية والتجميل.',
                'description_en' => 'Discover the latest offers and discounts at AURA Derma Aesthetic Clinic.',
                'keywords' => 'clinic offers, discounts, dermatology deals, عروض العيادة, خصومات, تجميل',
            ],
            [
                'page_identifier' => 'faq',
                'page_name_en' => 'FAQ Page',
                'page_name_ar' => 'صفحة الأسئلة الشائعة',
                'title_ar' => 'الأسئلة الشائعة - عيادة أورا ديرما',
                'title_en' => 'FAQ - AURA Derma Clinic',
                'description_ar' => 'إجابات على الأسئلة الشائعة حول خدمات وعلاجات عيادة أورا ديرما.',
                'description_en' => 'Answers to frequently asked questions about AURA Derma Clinic services and treatments.',
                'keywords' => 'FAQ, questions, dermatology FAQ, أسئلة شائعة, استفسارات',
            ],
            [
                'page_identifier' => 'booking',
                'page_name_en' => 'Booking Page',
                'page_name_ar' => 'صفحة الحجز',
                'title_ar' => 'حجز موعد - عيادة أورا ديرما',
                'title_en' => 'Book Appointment - AURA Derma Clinic',
                'description_ar' => 'احجز موعدك الآن في عيادة أورا ديرما للجلدية والتجميل - حجز سريع وسهل.',
                'description_en' => 'Book your appointment now at AURA Derma Aesthetic Clinic - quick and easy booking.',
                'keywords' => 'book appointment, clinic booking, dermatology appointment, حجز موعد, حجز عيادة',
            ],
            [
                'page_identifier' => 'contact',
                'page_name_en' => 'Contact Page',
                'page_name_ar' => 'صفحة تواصل معنا',
                'title_ar' => 'تواصل معنا - عيادة أورا ديرما',
                'title_en' => 'Contact Us - AURA Derma Clinic',
                'description_ar' => 'تواصل مع عيادة أورا ديرما - عنوان العيادة وأرقام الهاتف ونموذج التواصل.',
                'description_en' => 'Contact AURA Derma Clinic - clinic address, phone numbers, and contact form.',
                'keywords' => 'contact clinic, phone, address, location, تواصل, عنوان العيادة, هاتف',
            ],
            [
                'page_identifier' => 'blog',
                'page_name_en' => 'Blog Page',
                'page_name_ar' => 'صفحة المدونة',
                'title_ar' => 'المدونة - عيادة أورا ديرما',
                'title_en' => 'Blog - AURA Derma Clinic',
                'description_ar' => 'اقرأ أحدث المقالات والنصائح الطبية من خبراء عيادة أورا ديرما للجلدية والتجميل.',
                'description_en' => 'Read the latest articles and medical tips from AURA Derma Clinic dermatology and aesthetics experts.',
                'keywords' => 'dermatology blog, skin care tips, medical articles, مدونة طبية, نصائح للبشرة',
            ],
        ];

        foreach ($pages as $page) {
            SeoPage::updateOrCreate(
                ['page_identifier' => $page['page_identifier']],
                $page
            );
        }
    }
}
