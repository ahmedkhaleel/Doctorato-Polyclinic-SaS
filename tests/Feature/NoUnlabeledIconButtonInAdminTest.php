<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Accessibility guard: every icon-only <button> in the admin panel (one whose
 * only content is an <svg>, with no visible text or {{ mustache }} label) must
 * carry an accessible name — aria-label or title, bound or static — so
 * screen-reader users (and tooltip users) know what the control does.
 *
 * Sibling to NoEmojiInPagesTest / NoNativeConfirmInAdminTest /
 * NoEnglishTitleAttrInAdminTest. The directive "actions = icon + tooltip, not
 * words" makes a named tooltip the expected pattern, so an unlabeled icon
 * button is a real defect, not a style preference.
 */
class NoUnlabeledIconButtonInAdminTest extends TestCase
{
    public function test_icon_only_buttons_have_an_accessible_name(): void
    {
        $dir = resource_path('js/Pages/Admin');
        $offenders = [];

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if (substr($file, -4) !== '.vue') {
                continue;
            }

            $src = file_get_contents($file->getPathname());
            if (! preg_match_all('/<button\b([^>]*?)>(.*?)<\/button>/s', $src, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $m) {
                $attrs = $m[1];
                $inner = $m[2];

                // icon button only: contains an <svg>...
                if (strpos($inner, '<svg') === false) {
                    continue;
                }
                // ...and has no visible text or {{ mustache }} once tags are stripped
                if (trim(preg_replace('/<[^>]+>/', '', $inner)) !== '') {
                    continue;
                }
                // accessible name present? (aria-label / title / v-tooltip, bound or not)
                if (preg_match('/(?:^|\s)(title|aria-label|:title|:aria-label|v-tooltip)=/', $attrs)) {
                    continue;
                }

                $offenders[] = str_replace($dir.'/', '', $file->getPathname());
            }
        }

        $this->assertEmpty(
            $offenders,
            'These admin pages have icon-only buttons with no accessible name — add :aria-label/:title '
            ."(e.g. :title=\"isRtl ? 'تعديل' : 'Edit'\"): ".implode(', ', array_unique($offenders))
        );
    }
}
