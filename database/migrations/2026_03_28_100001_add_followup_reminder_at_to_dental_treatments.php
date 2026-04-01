<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dental_treatments') && !Schema::hasColumn('dental_treatments', 'followup_reminder_at')) {
            Schema::table('dental_treatments', function (Blueprint $table) {
                $table->timestamp('followup_reminder_at')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dental_treatments') && Schema::hasColumn('dental_treatments', 'followup_reminder_at')) {
            Schema::table('dental_treatments', function (Blueprint $table) {
                $table->dropColumn('followup_reminder_at');
            });
        }
    }
};
