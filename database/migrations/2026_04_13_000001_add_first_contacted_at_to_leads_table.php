<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('first_contacted_at')->nullable()->after('last_contacted_at');
        });

        // Backfill: set first_contacted_at from the earliest "contacted" activity
        DB::statement("
            UPDATE leads
            SET first_contacted_at = (
                SELECT MIN(created_at)
                FROM lead_activities
                WHERE lead_activities.lead_id = leads.id
                  AND (lead_activities.type = 'status_change' OR lead_activities.type = 'call' OR lead_activities.type = 'whatsapp' OR lead_activities.type = 'sms' OR lead_activities.type = 'email')
            )
            WHERE leads.status != 'new'
              AND leads.first_contacted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('first_contacted_at');
        });
    }
};
