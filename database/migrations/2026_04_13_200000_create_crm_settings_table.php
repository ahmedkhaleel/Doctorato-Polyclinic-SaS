<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general, sla, notifications, pipeline
            $table->string('type')->default('string');   // string, number, boolean, json
            $table->timestamps();
        });

        // Seed default values
        $now = now();
        DB::table('crm_settings')->insert([
            [
                'key'        => 'sla_response_target_minutes',
                'value'      => '60',
                'group'      => 'sla',
                'type'       => 'number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'sla_followup_target_hours',
                'value'      => '24',
                'group'      => 'sla',
                'type'       => 'number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'stale_lead_days',
                'value'      => '7',
                'group'      => 'sla',
                'type'       => 'number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'auto_assign_enabled',
                'value'      => 'false',
                'group'      => 'general',
                'type'       => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'auto_assign_method',
                'value'      => 'round_robin',
                'group'      => 'general',
                'type'       => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'default_lead_priority',
                'value'      => '3',
                'group'      => 'general',
                'type'       => 'number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'default_lead_module',
                'value'      => 'derma',
                'group'      => 'general',
                'type'       => 'string',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'notify_on_new_lead',
                'value'      => 'true',
                'group'      => 'notifications',
                'type'       => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'notify_on_status_change',
                'value'      => 'false',
                'group'      => 'notifications',
                'type'       => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'notify_on_overdue_followup',
                'value'      => 'true',
                'group'      => 'notifications',
                'type'       => 'boolean',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'pipeline_stages',
                'value'      => json_encode([
                    ['key' => 'new',               'label' => 'New',              'color' => '#3B82F6'],
                    ['key' => 'contacted',          'label' => 'Contacted',        'color' => '#8B5CF6'],
                    ['key' => 'qualified',          'label' => 'Qualified',        'color' => '#F59E0B'],
                    ['key' => 'appointment_booked', 'label' => 'Appointment Booked','color' => '#10B981'],
                    ['key' => 'consultation_done',  'label' => 'Consultation Done', 'color' => '#06B6D4'],
                    ['key' => 'negotiation',        'label' => 'Negotiation',      'color' => '#EC4899'],
                    ['key' => 'converted',          'label' => 'Converted',        'color' => '#22C55E'],
                    ['key' => 'lost',               'label' => 'Lost',             'color' => '#EF4444'],
                ]),
                'group'      => 'pipeline',
                'type'       => 'json',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_settings');
    }
};
