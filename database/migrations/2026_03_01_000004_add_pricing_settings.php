<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Copy existing values or use defaults
        $existingDermaFee = Setting::get('default_dermatology_fee', 400);
        $existingCosmeticFee = Setting::get('default_cosmetic_fee', 500);

        Setting::set('dermatology_consultant_fee', $existingDermaFee, 'consultation');
        Setting::set('dermatology_specialist_fee', 300, 'consultation');
        Setting::set('cosmetic_consultation_fee', $existingCosmeticFee, 'consultation');
        Setting::set('followup_fee', 100, 'consultation');
        Setting::set('followup_window_days', 15, 'consultation');
    }

    public function down(): void
    {
        $keys = [
            'dermatology_consultant_fee',
            'dermatology_specialist_fee',
            'cosmetic_consultation_fee',
            'followup_fee',
            'followup_window_days',
        ];

        \DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
