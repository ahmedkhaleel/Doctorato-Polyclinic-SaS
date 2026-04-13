<?php

namespace Database\Seeders;

use App\Models\CrmCampaign;
use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class CrmCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $instagramSource = LeadSource::where('slug', 'instagram')->first();
        $facebookSource = LeadSource::where('slug', 'facebook')->first();

        $campaigns = [
            [
                'name' => 'Excimer Laser Services - خدمات الاكزيمر ليزر',
                'description' => 'حملة خدمات الاكزيمر ليزر لعلاج البهاق والصدفية والاكزيما - Excimer Laser campaign for vitiligo, psoriasis and eczema treatments',
                'lead_source_id' => $instagramSource?->id,
                'status' => 'active',
                'budget' => 5000.00,
                'actual_cost' => 0.00,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'utm_source' => 'instagram',
                'utm_medium' => 'social',
                'utm_campaign' => 'excimer-laser-2026',
                'leads_count' => 0,
                'conversions_count' => 0,
            ],
            [
                'name' => 'Cosmetic Services - خدمات التجميل (حقن النضارة والفيلر والبوتكس)',
                'description' => 'حملة خدمات التجميل: حقن النضارة، الفيلر، البوتكس - Cosmetic services campaign: mesotherapy, fillers, botox',
                'lead_source_id' => $facebookSource?->id ?? $instagramSource?->id,
                'status' => 'active',
                'budget' => 8000.00,
                'actual_cost' => 0.00,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'utm_source' => 'social_media',
                'utm_medium' => 'social',
                'utm_campaign' => 'cosmetic-injectables-2026',
                'leads_count' => 0,
                'conversions_count' => 0,
            ],
        ];

        foreach ($campaigns as $campaign) {
            CrmCampaign::updateOrCreate(
                ['utm_campaign' => $campaign['utm_campaign']],
                $campaign
            );
        }
    }
}
