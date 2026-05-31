<?php

namespace App\Services\Branch;

use App\Models\Branch;
use App\Models\Setting;

/**
 * Resolves the clinic letterhead (name / address / phone / logo) for a document,
 * preferring the document's BRANCH identity and falling back to global settings.
 * Settings are read inside the branch's context so per-branch overrides (C2)
 * apply, regardless of which branch the viewer is currently on.
 */
class BranchLetterhead
{
    public static function for(?int $branchId): array
    {
        $locale = app()->getLocale();
        $branch = $branchId ? Branch::find($branchId) : null;

        $resolve = function () use ($branch, $locale) {
            $name = $branch ? $branch->name($locale) : null;
            $tagline = $locale === 'ar'
                ? Setting::get('tagline_ar', Setting::get('site_name_ar'))
                : Setting::get('tagline_en', Setting::get('site_name_en'));

            return [
                'name' => $name ?: Setting::get('site_name', 'AURA Clinic'),
                'tagline' => $tagline ?: '',
                'address' => ($branch && $branch->address) ? $branch->address : Setting::get('address', ''),
                'phone' => ($branch && $branch->phone) ? $branch->phone : Setting::get('phone_1', ''),
                'logo' => Setting::get('logo'),
            ];
        };

        return $branchId
            ? app(BranchContext::class)->runForBranch($branchId, $resolve)
            : $resolve();
    }
}
