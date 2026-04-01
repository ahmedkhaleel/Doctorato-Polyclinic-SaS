<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds all CRM basic settings in one go.
 *
 * Usage:
 *   php artisan db:seed --class=CrmSeeder
 *
 * This calls all CRM-related seeders in the correct dependency order:
 *   1. Lead Sources (required by campaigns & assignment rules)
 *   2. Scoring Rules
 *   3. Assignment Rules (depends on sources & users)
 *   4. Communication Templates (used by sequences)
 *   5. Follow-up Sequences (depends on templates)
 */
class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════╗');
        $this->command->info('║        CRM Settings Seeder               ║');
        $this->command->info('╚══════════════════════════════════════════╝');
        $this->command->info('');

        $this->call([
            LeadSourceSeeder::class,
            LeadScoringRuleSeeder::class,
            LeadAssignmentRuleSeeder::class,
            CommunicationTemplateSeeder::class,
            FollowUpSequenceSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✓ CRM settings seeded successfully!');
        $this->command->info('  • 11 Lead Sources');
        $this->command->info('  • 18 Scoring Rules');
        $this->command->info('  • 7 Assignment Rules');
        $this->command->info('  • 10 Communication Templates');
        $this->command->info('  • 5 Follow-up Sequences');
        $this->command->info('');
    }
}
