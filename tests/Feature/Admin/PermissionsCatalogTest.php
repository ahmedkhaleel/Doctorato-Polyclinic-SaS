<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the permissions catalog (config/permissions.php) against drift: every
 * permission the application actually checks must be GRANTABLE in the Roles UI,
 * otherwise RoleController strips it on save and the feature becomes reachable
 * only by super_admin (wildcard). Scans routes (permission:x.y middleware) and
 * the codebase (can('x.y') / hasPermission('x.y')).
 */
class PermissionsCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_checked_permission_exists_in_the_catalog(): void
    {
        $catalog = Role::getAllPermissions();
        $checked = $this->scanCheckedPermissions();

        $missing = array_values(array_diff($checked, $catalog));

        $this->assertSame([], $missing,
            'These permissions are checked in code but missing from config/permissions.php '
            .'(they would be stripped on role save): '.implode(', ', $missing));
    }

    /** @return string[] */
    private function scanCheckedPermissions(): array
    {
        $base = base_path();
        $found = [];

        $patterns = [
            // permission:a.b  or  permission:a.b,c.d  (route middleware, OR-list)
            ['#permission:((?:[a-z_]+\.[a-z_]+)(?:,[a-z_]+\.[a-z_]+)*)#', [base_path('routes')]],
            // can('module.action') / hasPermission('module.action')
            ['#(?:can|hasPermission)\(\s*[\'\"]([a-z_]+\.[a-z_]+)[\'\"]#', [base_path('app'), base_path('resources/js')]],
            // sidebar nav: permission: 'module.action'
            ['#permission:\s*[\'\"]([a-z_]+\.[a-z_]+)[\'\"]#', [base_path('resources/js')]],
        ];

        foreach ($patterns as [$regex, $dirs]) {
            foreach ($dirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    $ext = $file->getExtension();
                    if (! in_array($ext, ['php', 'vue', 'js'], true)) {
                        continue;
                    }
                    if (preg_match_all($regex, file_get_contents($file->getPathname()), $m)) {
                        foreach ($m[1] as $perm) {
                            // OR-lists (a.b,c.d) → split into individual permissions
                            foreach (explode(',', $perm) as $single) {
                                $found[trim($single)] = true;
                            }
                        }
                    }
                }
            }
        }

        // Exclude the wildcard and any "*" style tokens.
        return array_values(array_filter(array_keys($found), fn ($p) => $p !== '*'));
    }
}
