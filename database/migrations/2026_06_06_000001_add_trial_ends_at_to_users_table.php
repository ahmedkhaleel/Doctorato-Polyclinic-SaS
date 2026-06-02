<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trial expiry for accounts (used by demo/sales accounts). When trial_ends_at is
 * set and in the past, the account is treated as expired: access is blocked and
 * the user is shown a "trial ended — contact us" page. Null = no trial limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'trial_ends_at')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('is_demo')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('trial_ends_at');
            });
        }
    }
};
