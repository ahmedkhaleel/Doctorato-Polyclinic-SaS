<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch B1 — clinical specialty EVENT tables (care delivered AT a branch).
 *
 * Scoped: dental / derma / cosmetic / pediatric / obgyn clinical events.
 * NOT scoped (follow the shared patient, handled elsewhere): per-patient state
 * such as pediatric allergies/chronic/family-history, obgyn_profiles, dental_charts
 * odontogram, templates/catalogs.
 */
return new class extends Migration
{
    private array $tables = [
        // Dental events
        'dental_treatments', 'dental_treatment_plans', 'treatment_plans', 'treatment_plan_consents',
        'dental_chart_entries', 'dental_comparisons', 'dental_smart_notifications',
        'dental_lab_orders', 'dental_scheduled_followups', 'dental_xrays',
        // Derma / cosmetic events
        'derma_sessions', 'derma_treatment_plans', 'derma_photos',
        'cosmetic_sessions', 'cosmetic_procedures', 'cosmetic_package_purchases',
        'cosmetic_photos', 'cosmetic_consents',
        // Pediatric events
        'pediatric_well_child_visits', 'pediatric_vaccinations', 'pediatric_growth_records',
        'pediatric_milestones', 'pediatric_screening_tests', 'pediatric_nutrition_records',
        // OB/GYN events
        'pregnancies', 'antenatal_visits', 'obstetric_ultrasounds', 'obgyn_lab_tests',
        'pap_smear_screenings', 'delivery_records', 'contraception_records',
    ];

    public function up(): void
    {
        $default = (int) config('branches.default_id', 1);
        foreach ($this->tables as $t) {
            if (! Schema::hasTable($t) || Schema::hasColumn($t, 'branch_id')) {
                continue;
            }
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index(['branch_id', 'created_at']);
            });
            DB::table($t)->whereNull('branch_id')->update(['branch_id' => $default]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'branch_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropIndex(['branch_id', 'created_at']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
};
