<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8: Performance composite indexes for heavily-queried dental tables.
 * These cover the most common WHERE + JOIN patterns in dashboard, reports, and notification scans.
 */
return new class extends Migration
{
    public function up(): void
    {
        // visits: module + visit_date (dental dashboard, report date-range queries)
        Schema::table('visits', function (Blueprint $table) {
            $table->index(['module', 'visit_date'], 'visits_module_visit_date_index');
            $table->index(['module', 'status', 'visit_date'], 'visits_module_status_date_index');
        });

        // dental_treatments: status + created_at (report revenue queries)
        Schema::table('dental_treatments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'dental_treatments_status_created_index');
            $table->index(['patient_id', 'status'], 'dental_treatments_patient_status_index');
            $table->index(['doctor_id', 'status', 'created_at'], 'dental_treatments_doctor_status_date_index');
        });

        // dental_treatment_plans: status (active plans dashboard query)
        Schema::table('dental_treatment_plans', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'dental_plans_status_created_index');
        });

        // dental_lab_orders: status + order_date (report, dashboard queries)
        Schema::table('dental_lab_orders', function (Blueprint $table) {
            $table->index(['status', 'order_date'], 'dental_lab_status_order_date_index');
        });

        // dental_smart_notifications: status + scheduled_at (send-pending scan)
        if (Schema::hasTable('dental_smart_notifications')) {
            Schema::table('dental_smart_notifications', function (Blueprint $table) {
                $table->index(['status', 'scheduled_at'], 'dental_smart_notif_status_scheduled_index');
                $table->index('dedup_key', 'dental_smart_notif_dedup_index');
            });
        }

        // medical_data_access_logs: patient + date (audit trail queries)
        Schema::table('medical_data_access_logs', function (Blueprint $table) {
            $table->index(['data_category', 'created_at'], 'medical_access_category_date_index');
        });

        // patients: risk flag composite (dental risk patient count)
        Schema::table('patients', function (Blueprint $table) {
            $table->index(
                ['latex_allergy', 'anesthesia_complications', 'has_bleeding_disorder', 'takes_blood_thinners', 'has_heart_condition', 'has_hepatitis', 'has_hiv', 'is_pregnant'],
                'patients_dental_risk_flags_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_module_visit_date_index');
            $table->dropIndex('visits_module_status_date_index');
        });

        Schema::table('dental_treatments', function (Blueprint $table) {
            $table->dropIndex('dental_treatments_status_created_index');
            $table->dropIndex('dental_treatments_patient_status_index');
            $table->dropIndex('dental_treatments_doctor_status_date_index');
        });

        Schema::table('dental_treatment_plans', function (Blueprint $table) {
            $table->dropIndex('dental_plans_status_created_index');
        });

        Schema::table('dental_lab_orders', function (Blueprint $table) {
            $table->dropIndex('dental_lab_status_order_date_index');
        });

        if (Schema::hasTable('dental_smart_notifications')) {
            Schema::table('dental_smart_notifications', function (Blueprint $table) {
                $table->dropIndex('dental_smart_notif_status_scheduled_index');
                $table->dropIndex('dental_smart_notif_dedup_index');
            });
        }

        Schema::table('medical_data_access_logs', function (Blueprint $table) {
            $table->dropIndex('medical_access_category_date_index');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_dental_risk_flags_index');
        });
    }
};
