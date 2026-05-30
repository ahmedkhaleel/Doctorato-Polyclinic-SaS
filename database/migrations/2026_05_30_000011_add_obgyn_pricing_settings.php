<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_settings')) {
            return;
        }

        $now = now();
        $settings = [
            // Pricing group
            ['module' => 'obgyn', 'key' => 'consultation_fee', 'value' => '250', 'type' => 'number', 'label_ar' => 'رسوم الكشف', 'label_en' => 'Consultation Fee', 'group' => 'pricing', 'display_order' => 1],
            ['module' => 'obgyn', 'key' => 'anc_fee', 'value' => '150', 'type' => 'number', 'label_ar' => 'رسوم متابعة الحمل', 'label_en' => 'Antenatal Visit Fee', 'group' => 'pricing', 'display_order' => 2],
            ['module' => 'obgyn', 'key' => 'ultrasound_fee', 'value' => '200', 'type' => 'number', 'label_ar' => 'رسوم السونار', 'label_en' => 'Ultrasound Fee', 'group' => 'pricing', 'display_order' => 3],
            ['module' => 'obgyn', 'key' => 'delivery_fee', 'value' => '5000', 'type' => 'number', 'label_ar' => 'رسوم الولادة', 'label_en' => 'Delivery Fee', 'group' => 'pricing', 'display_order' => 4],
            ['module' => 'obgyn', 'key' => 'lab_fee', 'value' => '120', 'type' => 'number', 'label_ar' => 'رسوم التحاليل', 'label_en' => 'Lab Test Fee', 'group' => 'pricing', 'display_order' => 5],
            ['module' => 'obgyn', 'key' => 'pap_smear_fee', 'value' => '180', 'type' => 'number', 'label_ar' => 'رسوم مسحة عنق الرحم', 'label_en' => 'Pap Smear Fee', 'group' => 'pricing', 'display_order' => 6],

            // Commission group
            ['module' => 'obgyn', 'key' => 'default_commission', 'value' => '30', 'type' => 'number', 'label_ar' => 'عمولة افتراضية %', 'label_en' => 'Default Commission %', 'group' => 'commission', 'display_order' => 1],
            ['module' => 'obgyn', 'key' => 'consultation_commission', 'value' => '50', 'type' => 'number', 'label_ar' => 'عمولة الكشف %', 'label_en' => 'Consultation Commission %', 'group' => 'commission', 'display_order' => 2],
            ['module' => 'obgyn', 'key' => 'delivery_commission', 'value' => '40', 'type' => 'number', 'label_ar' => 'عمولة الولادة %', 'label_en' => 'Delivery Commission %', 'group' => 'commission', 'display_order' => 3],

            // Duration group
            ['module' => 'obgyn', 'key' => 'consultation_duration', 'value' => '20', 'type' => 'number', 'label_ar' => 'مدة الكشف (دقيقة)', 'label_en' => 'Consultation Duration (min)', 'group' => 'duration', 'display_order' => 1],
            ['module' => 'obgyn', 'key' => 'anc_duration', 'value' => '15', 'type' => 'number', 'label_ar' => 'مدة متابعة الحمل (دقيقة)', 'label_en' => 'Antenatal Visit Duration (min)', 'group' => 'duration', 'display_order' => 2],

            // ANC schedule model
            ['module' => 'obgyn', 'key' => 'anc_schedule_model', 'value' => 'who8', 'type' => 'text', 'label_ar' => 'نموذج جدول متابعة الحمل', 'label_en' => 'Antenatal Schedule Model', 'group' => 'clinical', 'display_order' => 1],
        ];

        foreach ($settings as $setting) {
            DB::table('module_settings')->updateOrInsert(
                ['module' => $setting['module'], 'key' => $setting['key']],
                array_merge($setting, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('module_settings')
            ->where('module', 'obgyn')
            ->whereIn('group', ['pricing', 'commission', 'duration', 'clinical'])
            ->delete();
    }
};
