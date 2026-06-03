<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Accessibility guard: a modal-/panel-close "✕" icon button must carry an
 * accessible name (aria-label or title — bound or static), so screen-reader
 * users and tooltip users know what it does. The close-X icon is unambiguous
 * (it always means "Close"), so this is a hard, low-false-positive rule.
 *
 * Sibling to NoEmojiInPagesTest / NoNativeConfirmInAdminTest /
 * NoEnglishTitleAttrInAdminTest. Detection: a <button> whose inner content is
 * an <svg> with the X path and no visible text/mustache, lacking any of
 * title / aria-label / :title / :aria-label / v-tooltip.
 */
class NoUnlabeledCloseButtonInAdminTest extends TestCase
{
    public function test_close_buttons_have_an_accessible_name(): void
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

                if (strpos($inner, '<svg') === false) {
                    continue;
                }
                // icon-only: nothing visible once tags are stripped
                if (trim(preg_replace('/<[^>]+>/', '', $inner)) !== '') {
                    continue;
                }
                // only the close-X icon
                $isClose = strpos($inner, 'M6 18L18 6') !== false || strpos($inner, '6 18L18 6') !== false;
                if (! $isClose) {
                    continue;
                }
                // accessible name present?
                if (preg_match('/(?:^|\s)(title|aria-label|:title|:aria-label|v-tooltip)=/', $attrs)) {
                    continue;
                }

                $offenders[] = str_replace($dir.'/', '', $file->getPathname());
            }
        }

        $this->assertEmpty(
            $offenders,
            'These admin pages have close "✕" buttons with no accessible name — add :aria-label/:title '
            ."(e.g. :title=\"isRtl ? 'إغلاق' : 'Close'\"): ".implode(', ', array_unique($offenders))
        );
    }
}
