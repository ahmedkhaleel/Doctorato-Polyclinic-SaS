<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The financial reports (P&L, revenue analytics) moved from the broad
 * `reports.view` to a dedicated `reports.financial` permission so a role can
 * view operational reports without seeing revenue. To avoid removing access on
 * deploy, grant `reports.financial` to every role that currently holds
 * `reports.view` (and to super_admin's wildcard, which already covers it).
 * Idempotent and additive — never removes a permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        foreach (\App\Models\Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue; // wildcard already covers it
            }
            if (in_array('reports.view', $perms, true) && ! in_array('reports.financial', $perms, true)) {
                $perms[] = 'reports.financial';
                $role->update(['permissions' => array_values($perms)]);
            }
        }
    }

    public function down(): void
    {
        // Not reverted — leaving the (additive) grant in place is harmless.
    }
};
