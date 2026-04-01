<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->index();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 30)->default('text'); // text, price, percentage, minutes, boolean, select
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->string('group', 50)->default('general'); // general, pricing, commission, duration, features
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['module', 'key']);
        });

        // Seed default module settings
        $this->seedDermaSettings();
        $this->seedDentalSettings();
    }

    private function seedDermaSettings(): void
    {
        $settings = [
            // General
            ['module' => 'derma', 'key' => 'enabled', 'value' => '1', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'name_ar', 'value' => 'الجلدية والتجميل', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'derma', 'key' => 'name_en', 'value' => 'Dermatology & Cosmetics', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'derma', 'key' => 'icon', 'value' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'derma', 'key' => 'color', 'value' => '#8B5CF6', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],

            // Pricing
            ['module' => 'derma', 'key' => 'consultation_fee', 'value' => '200', 'type' => 'price', 'label_ar' => 'سعر الكشف الأولي', 'label_en' => 'Initial Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'followup_fee', 'value' => '100', 'type' => 'price', 'label_ar' => 'سعر المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],
            ['module' => 'derma', 'key' => 'emergency_fee', 'value' => '300', 'type' => 'price', 'label_ar' => 'سعر الطوارئ', 'label_en' => 'Emergency Fee', 'group' => 'pricing', 'display_order' => 3],

            // Commission
            ['module' => 'derma', 'key' => 'default_commission', 'value' => '30', 'type' => 'percentage', 'label_ar' => 'نسبة العمولة الافتراضية', 'label_en' => 'Default Commission Rate', 'group' => 'commission', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'consultation_commission', 'value' => '50', 'type' => 'percentage', 'label_ar' => 'عمولة الاستشارة', 'label_en' => 'Consultation Commission', 'group' => 'commission', 'display_order' => 2],
            ['module' => 'derma', 'key' => 'followup_commission', 'value' => '40', 'type' => 'percentage', 'label_ar' => 'عمولة المتابعة', 'label_en' => 'Follow-up Commission', 'group' => 'commission', 'display_order' => 3],

            // Duration
            ['module' => 'derma', 'key' => 'consultation_duration', 'value' => '30', 'type' => 'minutes', 'label_ar' => 'مدة الكشف الافتراضية', 'label_en' => 'Default Consultation Duration', 'group' => 'duration', 'display_order' => 1],
            ['module' => 'derma', 'key' => 'session_duration', 'value' => '45', 'type' => 'minutes', 'label_ar' => 'مدة الجلسة الافتراضية', 'label_en' => 'Default Session Duration', 'group' => 'duration', 'display_order' => 2],
        ];

        $now = now();
        foreach ($settings as &$s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
        }
        \DB::table('module_settings')->insert($settings);
    }

    private function seedDentalSettings(): void
    {
        $settings = [
            // General
            ['module' => 'dental', 'key' => 'enabled', 'value' => '0', 'type' => 'boolean', 'label_ar' => 'تفعيل القسم', 'label_en' => 'Enable Module', 'group' => 'general', 'display_order' => 1],
            ['module' => 'dental', 'key' => 'name_ar', 'value' => 'طب الأسنان', 'type' => 'text', 'label_ar' => 'اسم القسم (عربي)', 'label_en' => 'Module Name (Arabic)', 'group' => 'general', 'display_order' => 2],
            ['module' => 'dental', 'key' => 'name_en', 'value' => 'Dentistry', 'type' => 'text', 'label_ar' => 'اسم القسم (إنجليزي)', 'label_en' => 'Module Name (English)', 'group' => 'general', 'display_order' => 3],
            ['module' => 'dental', 'key' => 'icon', 'value' => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342', 'type' => 'text', 'label_ar' => 'أيقونة القسم', 'label_en' => 'Module Icon', 'group' => 'general', 'display_order' => 4],
            ['module' => 'dental', 'key' => 'color', 'value' => '#06B6D4', 'type' => 'text', 'label_ar' => 'لون القسم', 'label_en' => 'Module Color', 'group' => 'general', 'display_order' => 5],

            // Pricing
            ['module' => 'dental', 'key' => 'consultation_fee', 'value' => '300', 'type' => 'price', 'label_ar' => 'سعر الكشف الأولي', 'label_en' => 'Initial Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'dental', 'key' => 'followup_fee', 'value' => '0', 'type' => 'price', 'label_ar' => 'سعر المتابعة', 'label_en' => 'Follow-up Fee', 'group' => 'pricing', 'display_order' => 2],
            ['module' => 'dental', 'key' => 'emergency_fee', 'value' => '500', 'type' => 'price', 'label_ar' => 'سعر الطوارئ', 'label_en' => 'Emergency Fee', 'group' => 'pricing', 'display_order' => 3],
            ['module' => 'dental', 'key' => 'xray_fee', 'value' => '150', 'type' => 'price', 'label_ar' => 'سعر الأشعة', 'label_en' => 'X-ray Fee', 'group' => 'pricing', 'display_order' => 4],
            ['module' => 'dental', 'key' => 'default_lab_fee', 'value' => '150', 'type' => 'price', 'label_ar' => 'رسوم المعمل الافتراضية', 'label_en' => 'Default Lab Fee', 'group' => 'pricing', 'display_order' => 5],

            // Commission
            ['module' => 'dental', 'key' => 'default_commission', 'value' => '40', 'type' => 'percentage', 'label_ar' => 'نسبة العمولة الافتراضية', 'label_en' => 'Default Commission Rate', 'group' => 'commission', 'display_order' => 1],
            ['module' => 'dental', 'key' => 'consultation_commission', 'value' => '50', 'type' => 'percentage', 'label_ar' => 'عمولة الاستشارة', 'label_en' => 'Consultation Commission', 'group' => 'commission', 'display_order' => 2],
            ['module' => 'dental', 'key' => 'implant_commission', 'value' => '25', 'type' => 'percentage', 'label_ar' => 'عمولة الزراعة', 'label_en' => 'Implant Commission', 'group' => 'commission', 'display_order' => 3],
            ['module' => 'dental', 'key' => 'orthodontic_commission', 'value' => '20', 'type' => 'percentage', 'label_ar' => 'عمولة التقويم', 'label_en' => 'Orthodontic Commission', 'group' => 'commission', 'display_order' => 4],
            ['module' => 'dental', 'key' => 'cosmetic_commission', 'value' => '20', 'type' => 'percentage', 'label_ar' => 'عمولة تجميل الأسنان', 'label_en' => 'Cosmetic Dental Commission', 'group' => 'commission', 'display_order' => 5],

            // Duration
            ['module' => 'dental', 'key' => 'consultation_duration', 'value' => '20', 'type' => 'minutes', 'label_ar' => 'مدة الكشف الافتراضية', 'label_en' => 'Default Consultation Duration', 'group' => 'duration', 'display_order' => 1],
            ['module' => 'dental', 'key' => 'session_duration', 'value' => '60', 'type' => 'minutes', 'label_ar' => 'مدة الجلسة الافتراضية', 'label_en' => 'Default Session Duration', 'group' => 'duration', 'display_order' => 2],
        ];

        $now = now();
        foreach ($settings as &$s) {
            $s['created_at'] = $now;
            $s['updated_at'] = $now;
        }
        \DB::table('module_settings')->insert($settings);
    }

    public function down(): void
    {
        Schema::dropIfExists('module_settings');
    }
};
