<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track which leave request produced each attendance row, so on rejection
 * we only remove the exact rows this leave created — never a manually-entered
 * row the employee already had for that day. Idempotent + data-safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendances') && ! Schema::hasColumn('attendances', 'leave_id')) {
            Schema::table('attendances', function (Blueprint $t) {
                $t->foreignId('leave_id')->nullable()->after('notes')
                    ->constrained('leaves')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'leave_id')) {
            Schema::table('attendances', function (Blueprint $t) {
                $t->dropConstrainedForeignId('leave_id');
            });
        }
    }
};
