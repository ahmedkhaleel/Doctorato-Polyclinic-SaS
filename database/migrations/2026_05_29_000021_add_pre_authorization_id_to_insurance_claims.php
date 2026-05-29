<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link an insurance claim to the pre-authorization that approved it, so the
 * approve→claim→reimburse flow is traceable end-to-end. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurance_claims') && ! Schema::hasColumn('insurance_claims', 'pre_authorization_id')) {
            Schema::table('insurance_claims', function (Blueprint $t) {
                $t->foreignId('pre_authorization_id')->nullable()
                    ->constrained('insurance_pre_authorizations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('insurance_claims') && Schema::hasColumn('insurance_claims', 'pre_authorization_id')) {
            Schema::table('insurance_claims', function (Blueprint $t) {
                $t->dropConstrainedForeignId('pre_authorization_id');
            });
        }
    }
};
