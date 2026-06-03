<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * i18n rule (docs/ADMIN_UI_AUDIT_PLAN.md §2): admin pages must not ship static
 * English `title="..."` attributes — neither the AdminLayout page-title prop nor
 * visible action tooltips. Both must be localized bindings (:title="$t(...)" or
 * :title="isRtl ? '…' : '…'"). This guard fails if a static English title sneaks
 * back in. SVG <path>/<svg> elements use d=/viewBox=, not title=, so they don't
 * match.
 */
class NoEnglishTitleAttrInAdminTest extends TestCase
{
    public function test_no_static_english_title_attributes_in_admin_pages(): void
    {
        $dir = resource_path('js/Pages/Admin');

        // A static (non-bound) title attribute whose value starts with a Latin letter.
        // The negative lookbehind for ':' excludes Vue bindings (:title="...").
        $pattern = '/(?<!:)\btitle="[A-Za-z][^"]*"/';

        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }
            foreach (file($file->getPathname()) as $n => $line) {
                // Skip lines that are clearly SVG geometry (defensive; SVGs don't use title=).
                if (str_contains($line, 'viewBox') || str_contains($line, ' d="')) {
                    continue;
                }
                if (preg_match($pattern, $line)) {
                    $offenders[] = str_replace(resource_path('js/Pages').'/', '', $file->getPathname()).':'.($n + 1);
                }
            }
        }

        $this->assertSame([], $offenders,
            'These admin pages have static English title="..." attributes — localize them (:title="$t(...)" or isRtl ternary): '.implode(', ', $offenders));
    }
}
