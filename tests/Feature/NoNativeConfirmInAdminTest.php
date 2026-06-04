<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UX rule (docs/ADMIN_UI_AUDIT_PLAN.md): every panel must use the branded,
 * bilingual GlobalConfirmDialog (via useConfirm()), never the native, English,
 * unbranded window.confirm(). This guard scans ALL Vue pages (Admin, Doctor,
 * Secretary, Patient, Frontend, Webmaster) and fails if a native confirm()
 * call sneaks back in. GlobalConfirmDialog is mounted in every panel layout.
 */
class NoNativeConfirmInAdminTest extends TestCase
{
    public function test_no_native_confirm_in_admin_pages(): void
    {
        $dir = resource_path('js/Pages');

        // Matches: window.confirm(   OR   a bare confirm( used as a call
        // (if (confirm(...)), !confirm(...), && confirm(...)). The composable is
        // always invoked as `confirm(` too, so we specifically flag the native
        // forms: window.confirm and `if (… confirm(` / `!confirm(`.
        $native = '/(window\.confirm\s*\(|(?:if\s*\(\s*!?|&&\s*!?|\|\|\s*!?|return\s+!?)\s*confirm\s*\()/';

        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'vue') {
                continue;
            }
            foreach (file($file->getPathname()) as $n => $line) {
                // Ignore comments.
                $trimmed = ltrim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*')) {
                    continue;
                }
                if (preg_match($native, $line)) {
                    $offenders[] = str_replace(resource_path('js/Pages').'/', '', $file->getPathname()).':'.($n + 1);
                }
            }
        }

        $this->assertSame([], $offenders,
            'These admin pages use native confirm() — use useConfirm()/GlobalConfirmDialog instead: '.implode(', ', $offenders));
    }
}
