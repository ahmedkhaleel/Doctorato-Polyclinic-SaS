<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-branch — per-branch operational settings (C2).
 *
 * branch_id = 0 means GLOBAL (applies to every branch / kill-switch off).
 * A branch can store an override row (branch_id = <id>); Setting::get() prefers
 * the override and falls back to the global (0) value. Using 0 (not NULL) keeps
 * the (key, branch_id) unique index meaningful — MySQL treats NULLs as distinct.
 *
 * Idempotent: the whole body is guarded on branch_id not yet existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || Schema::hasColumn('settings', 'branch_id')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->default(0)->after('key');
        });
        DB::table('settings')->update(['branch_id' => 0]); // existing rows → global

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->unique(['key', 'branch_id'], 'settings_key_branch_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasColumn('settings', 'branch_id')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_branch_unique');
            $table->unique('key', 'settings_key_unique');
            $table->dropColumn('branch_id');
        });
    }
};
