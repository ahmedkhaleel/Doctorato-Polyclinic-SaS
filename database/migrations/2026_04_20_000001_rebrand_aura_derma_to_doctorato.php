<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rebrand: replace leftover "Aura Derma" / "أورا ديرما" / "اورا ديرما"
 * strings in seeded DB content with "Doctorato" / "دكتوراتو".
 *
 * Targets:
 *   - discount_codes.popup_title_ar / popup_description_ar
 *   - testimonials.review_ar / review_en
 *   - pages.seo_title_en / seo_desc_en / content_en
 *   - posts.content_en
 *   - services.full_desc_en / full_desc_ar
 *   - settings.value for clinic_name_* and social keys (only if value
 *     contains Aura — never overwrites a value the user set manually)
 */
return new class extends Migration
{
    /** @var array<string, string>  Ordered longest-first so "Aura Derma Clinic" matches before "Aura" */
    private array $replacements = [
        'أورا ديرما'           => 'دكتوراتو',
        'اورا ديرما'           => 'دكتوراتو',
        'أورا درما'            => 'دكتوراتو',
        'اورا درما'            => 'دكتوراتو',
        'Aura Derma Clinic'     => 'Doctorato Polyclinic',
        'Aura Derma'            => 'Doctorato',
        'Aura Clinic'           => 'Doctorato Polyclinic',
        'AuraDerma'             => 'Doctorato',
        'عيادة أورا'            => 'عيادة دكتوراتو',
        'عيادة اورا'            => 'عيادة دكتوراتو',
        'أورا'                  => 'دكتوراتو',
        'اورا'                  => 'دكتوراتو',
        'Aura'                  => 'Doctorato',
    ];

    public function up(): void
    {
        // Tables + columns to sweep
        $targets = [
            'discount_codes'     => ['popup_title_ar', 'popup_title_en', 'popup_description_ar', 'popup_description_en', 'notes'],
            'testimonials'       => ['review_ar', 'review_en'],
            'pages'              => ['seo_title_en', 'seo_title_ar', 'seo_desc_en', 'seo_desc_ar', 'content_en', 'content_ar'],
            'posts'              => ['content_en', 'content_ar', 'title_en', 'title_ar', 'excerpt_en', 'excerpt_ar'],
            'services'           => ['full_desc_en', 'full_desc_ar', 'short_desc_en', 'short_desc_ar'],
            'service_categories' => ['description_en', 'description_ar'],
            'faqs'               => ['question_en', 'question_ar', 'answer_en', 'answer_ar'],
            'seo_pages'          => ['meta_title_en', 'meta_title_ar', 'meta_description_en', 'meta_description_ar'],
        ];

        foreach ($targets as $table => $columns) {
            if (! Schema::hasTable($table)) continue;

            foreach ($columns as $col) {
                if (! Schema::hasColumn($table, $col)) continue;

                foreach ($this->replacements as $from => $to) {
                    // MySQL-safe REPLACE() — only updates rows that actually contain the string
                    DB::table($table)
                        ->where($col, 'like', '%' . $from . '%')
                        ->update([
                            $col => DB::raw("REPLACE(`{$col}`, " . DB::getPdo()->quote($from) . ", " . DB::getPdo()->quote($to) . ")"),
                        ]);
                }
            }
        }

        // Settings: only rebrand clinic_name_* keys — leave social media URLs
        // (facebook/instagram/tiktok) alone since they're legitimate external links.
        if (Schema::hasTable('settings')) {
            foreach (['clinic_name_ar', 'clinic_name_en', 'clinic_name', 'site_name'] as $key) {
                $row = DB::table('settings')->where('key', $key)->first();
                if ($row && $row->value) {
                    $newValue = $row->value;
                    foreach ($this->replacements as $from => $to) {
                        $newValue = str_replace($from, $to, $newValue);
                    }
                    if ($newValue !== $row->value) {
                        DB::table('settings')->where('key', $key)->update(['value' => $newValue]);
                    }
                }
            }

            // sms_sender_name: if still equal to 'AuraDerma', update to 'Doctorato'
            DB::table('settings')
                ->where('key', 'sms_sender_name')
                ->where('value', 'AuraDerma')
                ->update(['value' => 'Doctorato']);
        }
    }

    public function down(): void
    {
        // No-op: we don't want to restore old brand text
    }
};
