<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * loyalty.earned was seeded as "transactional", but the existing loyalty SMS
 * gated on marketing opt-in. Align the hub event to "marketing" so consent is
 * enforced per channel exactly as before (notify_*_marketing, default opt-in).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_events')) {
            DB::table('notification_events')->where('key', 'loyalty.earned')->update(['category' => 'marketing']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_events')) {
            DB::table('notification_events')->where('key', 'loyalty.earned')->update(['category' => 'transactional']);
        }
    }
};
