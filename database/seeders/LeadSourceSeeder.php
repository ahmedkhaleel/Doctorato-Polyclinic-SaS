<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name_en' => 'Website',
                'name_ar' => 'الموقع الإلكتروني',
                'slug' => 'website',
                'icon' => 'globe',
                'color' => '#3B82F6',
                'channel_type' => 'online',
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Phone Call',
                'name_ar' => 'مكالمة هاتفية',
                'slug' => 'phone',
                'icon' => 'phone',
                'color' => '#10B981',
                'channel_type' => 'offline',
                'sort_order' => 2,
            ],
            [
                'name_en' => 'WhatsApp',
                'name_ar' => 'واتساب',
                'slug' => 'whatsapp',
                'icon' => 'whatsapp',
                'color' => '#25D366',
                'channel_type' => 'online',
                'sort_order' => 3,
            ],
            [
                'name_en' => 'Facebook',
                'name_ar' => 'فيسبوك',
                'slug' => 'facebook',
                'icon' => 'facebook',
                'color' => '#1877F2',
                'channel_type' => 'online',
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Instagram',
                'name_ar' => 'انستجرام',
                'slug' => 'instagram',
                'icon' => 'instagram',
                'color' => '#E4405F',
                'channel_type' => 'online',
                'sort_order' => 5,
            ],
            [
                'name_en' => 'TikTok',
                'name_ar' => 'تيك توك',
                'slug' => 'tiktok',
                'icon' => 'tiktok',
                'color' => '#000000',
                'channel_type' => 'online',
                'sort_order' => 6,
            ],
            [
                'name_en' => 'Google Ads',
                'name_ar' => 'اعلانات جوجل',
                'slug' => 'google-ads',
                'icon' => 'google',
                'color' => '#EA4335',
                'channel_type' => 'online',
                'sort_order' => 7,
            ],
            [
                'name_en' => 'Walk-in',
                'name_ar' => 'حضور مباشر',
                'slug' => 'walk-in',
                'icon' => 'walk',
                'color' => '#8B5CF6',
                'channel_type' => 'offline',
                'sort_order' => 8,
            ],
            [
                'name_en' => 'Referral',
                'name_ar' => 'إحالة',
                'slug' => 'referral',
                'icon' => 'users',
                'color' => '#F59E0B',
                'channel_type' => 'referral',
                'sort_order' => 9,
            ],
            [
                'name_en' => 'Snapchat',
                'name_ar' => 'سناب شات',
                'slug' => 'snapchat',
                'icon' => 'snapchat',
                'color' => '#FFFC00',
                'channel_type' => 'online',
                'sort_order' => 10,
            ],
            [
                'name_en' => 'Other',
                'name_ar' => 'أخرى',
                'slug' => 'other',
                'icon' => 'more',
                'color' => '#6B7280',
                'channel_type' => 'offline',
                'sort_order' => 99,
            ],
        ];

        foreach ($sources as $source) {
            LeadSource::updateOrCreate(
                ['slug' => $source['slug']],
                $source
            );
        }
    }
}
