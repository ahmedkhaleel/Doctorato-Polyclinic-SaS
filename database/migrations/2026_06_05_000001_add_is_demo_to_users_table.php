<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Flags demo accounts. Demo users may view/add/edit but never delete or change
 *  core settings (enforced by DemoModeGuard middleware). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_demo')) {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('is_active')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
