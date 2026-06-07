<?php

namespace Tests\Feature\Admin;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Guards the admin sidebar against losing or breaking a page during any
 * reorganization (ADMIN_SIDEBAR_REORG_PLAN). It does NOT use the database.
 *
 *  1. No link is lost — the set of hrefs in the sidebar source must be a
 *     superset of the committed baseline (tests/fixtures/admin_sidebar_hrefs.txt).
 *  2. No link is broken — every sidebar href must resolve to a registered GET
 *     route.
 *
 * Re-baseline intentionally (only when adding NEW pages) by regenerating the
 * fixture with the documented grep command.
 */
class AdminSidebarIntegrityTest extends TestCase
{
    /** Source files the nav definition may live in (during/after extraction). */
    private function navSources(): array
    {
        return array_filter([
            base_path('resources/js/Layouts/AdminLayout.vue'),
            base_path('resources/js/Layouts/adminNav.js'),
        ], 'is_file');
    }

    private function currentHrefs(): array
    {
        $hrefs = [];
        foreach ($this->navSources() as $file) {
            $src = file_get_contents($file);
            if (preg_match_all("/href:\s*'([^']+)'/", $src, $m)) {
                $hrefs = array_merge($hrefs, $m[1]);
            }
        }

        // Only the admin sidebar links (ignore any stray non-admin matches).
        $hrefs = array_values(array_unique(array_filter($hrefs, fn ($h) => str_starts_with($h, '/admin'))));
        sort($hrefs);

        return $hrefs;
    }

    public function test_no_sidebar_page_is_lost_vs_baseline(): void
    {
        $baseline = array_filter(array_map('trim', file(base_path('tests/fixtures/admin_sidebar_hrefs.txt'))));
        $current = $this->currentHrefs();

        $missing = array_values(array_diff($baseline, $current));

        $this->assertSame([], $missing, 'Sidebar links removed vs baseline: '.implode(', ', $missing));
    }

    public function test_every_sidebar_href_resolves_to_a_route(): void
    {
        $router = app('router');
        $broken = [];

        foreach ($this->currentHrefs() as $href) {
            try {
                $router->getRoutes()->match(Request::create($href, 'GET'));
            } catch (\Throwable $e) {
                $broken[] = $href.' ('.class_basename($e).')';
            }
        }

        $this->assertSame([], $broken, 'Sidebar links that do not resolve to a GET route: '.implode(', ', $broken));
    }
}
