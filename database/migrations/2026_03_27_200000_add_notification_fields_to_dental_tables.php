<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dental_lab_orders') && !Schema::hasColumn('dental_lab_orders', 'notification_sent_at')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                $table->timestamp('notification_sent_at')->nullable()->after('special_instructions');
            });
        }

        if (Schema::hasTable('dental_treatment_plans') && !Schema::hasColumn('dental_treatment_plans', 'overdue_notified_at')) {
            Schema::table('dental_treatment_plans', function (Blueprint $table) {
                $table->timestamp('overdue_notified_at')->nullable()->after('notes');
                $table->timestamp('approaching_notified_at')->nullable()->after('overdue_notified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dental_lab_orders') && Schema::hasColumn('dental_lab_orders', 'notification_sent_at')) {
            Schema::table('dental_lab_orders', function (Blueprint $table) {
                $table->dropColumn('notification_sent_at');
            });
        }

        if (Schema::hasTable('dental_treatment_plans') && Schema::hasColumn('dental_treatment_plans', 'overdue_notified_at')) {
            Schema::table('dental_treatment_plans', function (Blueprint $table) {
                $table->dropColumn(['overdue_notified_at', 'approaching_notified_at']);
            });
        }
    }
};
