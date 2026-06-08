<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F-B — mark a physio session as a home visit so PhysioBillingService can add
 * the `home_visit_surcharge` module setting to the session fee. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('physio_sessions') || Schema::hasColumn('physio_sessions', 'home_visit')) {
            return;
        }

        Schema::table('physio_sessions', function (Blueprint $table) {
            $table->boolean('home_visit')->default(false)->after('attended');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('physio_sessions') && Schema::hasColumn('physio_sessions', 'home_visit')) {
            Schema::table('physio_sessions', function (Blueprint $table) {
                $table->dropColumn('home_visit');
            });
        }
    }
};
