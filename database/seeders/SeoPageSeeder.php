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
                'title_ar' => 'عيادة دكتوراتو للجلدية والتجميل',
                'title_en' => 'Doctorato Polyclinic',
                'description_ar' => 'عيادة دكتوراتو للجلدية والتجميل - أحدث تقنيات العناية بالبشرة والتجميل في مصر مع نخبة من أمهر الأطباء المتخصصين.',
                'description_en' => 'Doctorato Polyclinic - The latest skincare and cosmetic technologies in Egypt with an elite team of specialist doctors.',
                'keywords' => 'dermatology, skincare, cosmetic clinic, Egypt, عيادة جلدية, تجميل, عناية بالبشرة, مصر, دكتوراتو',
            ],
            [
                'page_identifier' => 'about',
                'page_name_en' => 'About Page',
                'page_name_ar' => 'صفحة من نحن',
                'title_ar' => 'عن العيادة - عيادة دكتوراتو',
                'title_en' => 'About Us - Doctorato Polyclinic',
                'description_ar' => 'تعرف على عيادة دكتوراتو للجلدية والتجميل، رؤيتنا ورسالتنا وفريقنا الطبي المتميز.',
                'description_en' => 'Learn about Doctorato Polyclinic, our vision, mission, and distinguished medical team.',
                'keywords' => 'about Doctorato, dermatology clinic, cosmetic clinic Egypt, عن دكتوراتو, عيادة متعددة التخصصات',
            ],
            [
                'page_identifier' => 'services',
                'page_name_en' => 'Services Page',
                'page_name_ar' => 'صفحة الخدمات',
                'title_ar' => 'خدماتنا - عيادة دكتوراتو',
                'title_en' => 'Our Services - Doctorato Polyclinic',
                'description_ar' => 'استكشف خدمات عيادة دكتوراتو للجلدية والتجميل - علاجات البشرة والليزر والتجميل بأحدث التقنيات.',
                'description_en' => 'Explore Doctorato Polyclinic services - skin treatments, laser, and cosmetic procedures with the latest technologies.',
                'keywords' => 'dermatology services, laser treatment, cosmetic procedures, skin care, خدمات جلدية, ليزر, تجميل',
            ],
            [
                'page_identifier' => 'gallery',
                'page_name_en' => 'Gallery Page',
                'page_name_ar' => 'صفحة المعرض',
                'title_ar' => 'معرض الصور - عيادة دكتوراتو',
                'title_en' => 'Gallery - Doctorato Polyclinic',
                'description_ar' => 'شاهد معرض صور عيادة دكتوراتو - نتائج العلاجات والعيادة وفريق العمل.',
                'description_en' => 'View Doctorato Polyclinic gallery - treatment results, clinic facilities, and our team.',
                'keywords' => 'clinic gallery, before after, treatment results, معرض العيادة, نتائج العلاج',
            ],
            [
                'page_identifier' => 'offers',
                'page_name_en' => 'Offers Page',
                'page_name_ar' => 'صفحة العروض',
                'title_ar' => 'العروض - عيادة دكتوراتو',
                'title_en' => 'Offers - Doctorato Polyclinic',
                'description_ar' => 'اكتشف أحدث عروض وخصومات عيادة دكتوراتو للجلدية والتجميل.',
                'description_en' => 'Discover the latest offers and discounts at Doctorato Polyclinic.',
                'keywords' => 'clinic offers, discounts, dermatology deals, عروض العيادة, خصومات, تجميل',
            ],
            [
                'page_identifier' => 'faq',
                'page_name_en' => 'FAQ Page',
                'page_name_ar' => 'صفحة الأسئلة الشائعة',
                'title_ar' => 'الأسئلة الشائعة - عيادة دكتوراتو',
                'title_en' => 'FAQ - Doctorato Polyclinic',
                'description_ar' => 'إجابات على الأسئلة الشائعة حول خدمات وعلاجات عيادة دكتوراتو.',
                'description_en' => 'Answers to frequently asked questions about Doctorato Polyclinic services and treatments.',
                'keywords' => 'FAQ, questions, dermatology FAQ, أسئلة شائعة, استفسارات',
            ],
            [
                'page_identifier' => 'booking',
                'page_name_en' => 'Booking Page',
                'page_name_ar' => 'صفحة الحجز',
                'title_ar' => 'حجز موعد - عيادة دكتوراتو',
                'title_en' => 'Book Appointment - Doctorato Polyclinic',
                'description_ar' => 'احجز موعدك الآن في عيادة دكتوراتو للجلدية والتجميل - حجز سريع وسهل.',
                'description_en' => 'Book your appointment now at Doctorato Polyclinic - quick and easy booking.',
                'keywords' => 'book appointment, clinic booking, dermatology appointment, حجز موعد, حجز عيادة',
            ],
            [
                'page_identifier' => 'contact',
                'page_name_en' => 'Contact Page',
                'page_name_ar' => 'صفحة تواصل معنا',
                'title_ar' => 'تواصل معنا - عيادة دكتوراتو',
                'title_en' => 'Contact Us - Doctorato Polyclinic',
                'description_ar' => 'تواصل مع عيادة دكتوراتو - عنوان العيادة وأرقام الهاتف ونموذج التواصل.',
                'description_en' => 'Contact Doctorato Polyclinic - clinic address, phone numbers, and contact form.',
                'keywords' => 'contact clinic, phone, address, location, تواصل, عنوان العيادة, هاتف',
            ],
            [
                'page_identifier' => 'blog',
                'page_name_en' => 'Blog Page',
                'page_name_ar' => 'صفحة المدونة',
                'title_ar' => 'المدونة - عيادة دكتوراتو',
                'title_en' => 'Blog - Doctorato Polyclinic',
                'description_ar' => 'اقرأ أحدث المقالات والنصائح الطبية من خبراء عيادة دكتوراتو للجلدية والتجميل.',
                'description_en' => 'Read the latest articles and medical tips from Doctorato Polyclinic dermatology and aesthetics experts.',
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
