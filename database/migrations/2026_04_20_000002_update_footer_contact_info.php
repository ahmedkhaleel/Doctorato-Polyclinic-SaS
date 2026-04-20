<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Set footer contact info to Doctorato's canonical UAE details:
 *   phone_1  → +971557961688 (main phone + WhatsApp)
 *   phone_2  → (empty — removed secondary legacy number)
 *   whatsapp → +971557961688
 *   email    → info@doctorato.com
 *
 * Only updates rows that still hold the old Egyptian defaults
 * (01007729159 / 0238244047) OR are empty. If an operator has
 * already customised them in the admin UI, leaves those values alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();

        $targets = [
            'phone_1'  => '+971557961688',
            'phone_2'  => '',
            'whatsapp' => '+971557961688',
            'email'    => 'info@doctorato.com',
        ];

        // Legacy values we consider "old defaults" and safe to replace
        $legacyValues = [
            'phone_1'  => ['01007729159', '', null],
            'phone_2'  => ['0238244047', '01007729159', null],
            'whatsapp' => ['01007729159', '', null],
            'email'    => ['', null],
        ];

        foreach ($targets as $key => $newValue) {
            $row = DB::table('settings')->where('key', $key)->first();
            $current = $row?->value;

            $shouldReplace = in_array($current, $legacyValues[$key] ?? [null], true);

            if ($row && $shouldReplace) {
                DB::table('settings')
                    ->where('key', $key)
                    ->update(['value' => $newValue, 'updated_at' => $now]);
            } elseif (! $row) {
                DB::table('settings')->insert([
                    'key'        => $key,
                    'value'      => $newValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            // If a custom value exists that isn't in the legacy list, leave it alone.
        }
    }

    public function down(): void
    {
        // No-op: don't restore legacy numbers
    }
};
