<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Accessibility guard: every <img> across all Vue pages + shared components must
 * carry an `alt` attribute (static `alt=` or bound `:alt=`), even if empty
 * (`alt=""` for decorative/redundant images). A missing alt makes screen
 * readers announce the image URL — and for this system that URL is often a
 * signed PHI media link. Sibling to NoUnlabeledIconButtonInAdminTest.
 */
class NoUnlabeledImageInPagesTest extends TestCase
{
    public function test_every_img_has_an_alt_attribute(): void
    {
        $offenders = [];

        foreach ([resource_path('js/Pages'), resource_path('js/Components')] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($it as $file) {
                if (substr($file, -4) !== '.vue') {
                    continue;
                }

                $src = file_get_contents($file->getPathname());
                if (! preg_match_all('/<img\b[^>]*>/s', $src, $matches)) {
                    continue;
                }

                foreach ($matches[0] as $tag) {
                    // Accept static alt= or bound :alt= (and v-bind:alt=).
                    if (! preg_match('/(?:\s|:|")alt\s*=/', $tag) && ! preg_match('/v-bind:alt=/', $tag)) {
                        $offenders[] = str_replace(base_path().'/', '', $file->getPathname())
                            .' → '.trim(preg_replace('/\s+/', ' ', substr($tag, 0, 80)));
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These <img> tags are missing an alt attribute (use alt=\"\" if decorative):\n".implode("\n", $offenders)
        );
    }
}
