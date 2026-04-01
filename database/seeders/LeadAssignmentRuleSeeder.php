<?php

namespace Database\Seeders;

use App\Models\LeadAssignmentRule;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadAssignmentRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Get a staff user to assign leads to (first active admin/secretary)
        $defaultUser = User::where('is_active', true)->first();
        if (! $defaultUser) {
            $this->command->warn('No active users found. Skipping assignment rules seeder.');
            return;
        }

        $rules = [
            // ── Source-based rules (highest priority) ─────────────
            [
                'name' => 'Website Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'website',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'WhatsApp Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'whatsapp',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Phone Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'phone',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Facebook Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'facebook',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'Instagram Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'instagram',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'Google Ads Leads → Reception',
                'rule_type' => 'source_based',
                'source_slug' => 'google-ads',
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 85,
                'is_active' => true,
            ],

            // ── Fallback rule (lowest priority) ──────────────────
            [
                'name' => 'Manual Fallback — Unmatched Leads',
                'rule_type' => 'manual',
                'source_slug' => null,
                'assign_to_user_id' => $defaultUser->id,
                'conditions' => null,
                'priority' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            $sourceSlug = $rule['source_slug'];
            unset($rule['source_slug']);

            if ($sourceSlug) {
                $source = LeadSource::where('slug', $sourceSlug)->first();
                $rule['lead_source_id'] = $source?->id;
            } else {
                $rule['lead_source_id'] = null;
            }

            LeadAssignmentRule::updateOrCreate(
                ['name' => $rule['name']],
                $rule,
            );
        }
    }
}
