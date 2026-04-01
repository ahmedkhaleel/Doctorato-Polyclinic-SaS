<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // dental_treatments: completed_at is used by follow-up scans
        // and treatment_plan_id is used in plan progress queries
        Schema::table('dental_treatments', function (Blueprint $table) {
            $table->index('completed_at');
            $table->index('treatment_plan_id');
        });

        // dental_lab_orders: expected_date for overdue queries,
        // status alone for dashboard filters
        Schema::table('dental_lab_orders', function (Blueprint $table) {
            $table->index('expected_date');
            $table->index('status');
        });

        // dental_xrays: taken_date for date-range queries
        Schema::table('dental_xrays', function (Blueprint $table) {
            $table->index('taken_date');
        });

        // dental_treatment_plans: priority for dashboard sorting
        Schema::table('dental_treatment_plans', function (Blueprint $table) {
            $table->index('priority');
        });

        // dental_scheduled_followups: scheduled_date for daily scan queries
        if (Schema::hasTable('dental_scheduled_followups')) {
            Schema::table('dental_scheduled_followups', function (Blueprint $table) {
                if (!Schema::hasIndex('dental_scheduled_followups', 'dental_scheduled_followups_scheduled_date_index')) {
                    $table->index('scheduled_date');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('dental_treatments', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['treatment_plan_id']);
        });

        Schema::table('dental_lab_orders', function (Blueprint $table) {
            $table->dropIndex(['expected_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('dental_xrays', function (Blueprint $table) {
            $table->dropIndex(['taken_date']);
        });

        Schema::table('dental_treatment_plans', function (Blueprint $table) {
            $table->dropIndex(['priority']);
        });

        if (Schema::hasTable('dental_scheduled_followups')) {
            Schema::table('dental_scheduled_followups', function (Blueprint $table) {
                if (Schema::hasIndex('dental_scheduled_followups', 'dental_scheduled_followups_scheduled_date_index')) {
                    $table->dropIndex(['scheduled_date']);
                }
            });
        }
    }
};
