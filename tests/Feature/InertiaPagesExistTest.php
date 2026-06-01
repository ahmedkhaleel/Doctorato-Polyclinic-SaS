<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards against "broken screens": every Inertia::render('Path') in the
 * controllers must have a matching resources/js/Pages/Path.vue file. Inertia
 * feature tests assert the component NAME only — they do NOT load the .vue file,
 * so a missing page passes tests yet 404s/errors in the browser. This scan
 * closes that gap.
 */
class InertiaPagesExistTest extends TestCase
{
    public function test_every_rendered_inertia_page_has_a_vue_file(): void
    {
        $pagesDir = resource_path('js/Pages');
        $missing = [];

        $controllers = base_path('app');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controllers, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if (! preg_match_all('#Inertia::render\(\s*[\'"]([^\'"]+)[\'"]#', $code, $m)) {
                continue;
            }
            foreach ($m[1] as $page) {
                if (str_contains($page, '$')) {
                    continue; // dynamic component name — can't statically verify
                }
                if (! is_file("{$pagesDir}/{$page}.vue")) {
                    $missing[$page] = true;
                }
            }
        }

        $this->assertSame([], array_keys($missing),
            'Controllers render these Inertia pages but the .vue files are missing '
            .'(broken screens): '.implode(', ', array_keys($missing)));
    }
}
