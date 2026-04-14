<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate dedup_keys (keep latest)
        if (Schema::hasTable('dental_smart_notifications') && Schema::hasColumn('dental_smart_notifications', 'dedup_key')) {
            DB::statement("
                DELETE t1 FROM dental_smart_notifications t1
                INNER JOIN dental_smart_notifications t2
                WHERE t1.id < t2.id AND t1.dedup_key = t2.dedup_key
                AND t1.dedup_key IS NOT NULL
            ");

            Schema::table('dental_smart_notifications', function (Blueprint $table) {
                // Drop existing index, add unique
                try {
                    $table->dropIndex(['dedup_key']);
                } catch (\Exception $e) {
                    // Index may not exist
                }
                $table->unique('dedup_key', 'dental_smart_notifications_dedup_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dental_smart_notifications') && Schema::hasColumn('dental_smart_notifications', 'dedup_key')) {
            Schema::table('dental_smart_notifications', function (Blueprint $table) {
                $table->dropUnique('dental_smart_notifications_dedup_key_unique');
                $table->index('dedup_key');
            });
        }
    }
};
