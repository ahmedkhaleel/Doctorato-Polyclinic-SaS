<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Brand rule: the UI uses SVG icons, never emoji pictographs. This guard scans
 * every Inertia page (.vue) for emoji in the pictograph unicode blocks and
 * fails if any are found. Typographic symbols (✓ ✗ ★ ♂ ♀ ↗ ⚠) live outside
 * these blocks and are intentionally allowed.
 */
class NoEmojiInPagesTest extends TestCase
{
    public function test_no_emoji_pictographs_in_vue_pages(): void
    {
        $dir = resource_path('js/Pages');
        // Misc Symbols & Pictographs, Emoticons, Transport, Supplemental & Extended,
        // Dingbats sparkle, Supplemental Symbols (1F900-1F9FF), and the VS-16 selector.
        $emoji = '/[\x{1F300}-\x{1FAFF}\x{1F000}-\x{1F0FF}\x{1F900}-\x{1F9FF}\x{2728}\x{FE0F}]/u';

        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (preg_match($emoji, $content)) {
                $offenders[] = str_replace(resource_path('js/Pages').'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders,
            'These pages contain emoji pictographs (use SVG icons instead): '.implode(', ', $offenders));
    }

    /**
     * Stricter rule for the admin panel (audited in docs/ADMIN_UI_AUDIT_PLAN.md):
     * NO symbol glyphs used as icons either — the Miscellaneous Symbols + Dingbats
     * block (2600-27BF: ★ ☆ ✓ ✗ ✕ ✦ ✎ ⚠ ♂ ♀ …) plus ⏰ (23F0) and ⭐ (2B50).
     * Typographic arrows (2190-21FF: → ↑ ↓ ←) remain allowed as text separators.
     *
     * Scoped to resources/js/Pages/Admin only; other panels are cleaned in later
     * audit phases. This guard prevents the Phase-0 cleanup from regressing.
     */
    public function test_no_symbol_glyph_icons_in_admin_pages(): void
    {
        $dir = resource_path('js/Pages/Admin');
        $glyphs = '/[\x{2600}-\x{27BF}\x{23F0}\x{2B50}]/u';

        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if (preg_match($glyphs, $content)) {
                $offenders[] = str_replace(resource_path('js/Pages').'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders,
            'These admin pages use symbol glyphs as icons (use SVG instead): '.implode(', ', $offenders));
    }
}
