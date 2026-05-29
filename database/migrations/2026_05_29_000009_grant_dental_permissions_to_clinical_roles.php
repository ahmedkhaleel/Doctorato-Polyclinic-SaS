<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the dental.* permission family onto existing roles.
 *
 * Every dental admin route is gated by permission:dental.view (+create/
 * update/delete), but `dental.*` was never a real permission — only the
 * granular dental_treatment_plans.* / dental_charts.* etc. existed, and the
 * routes don't check those. Result: only super_admin ('*') could open any
 * dental screen; doctors/reception/secretary got 403 across the module.
 *
 * This adds dental.* (now a real family in config/permissions.php) to the
 * roles that are clearly dental-enabled, so they regain access on deploy.
 * super_admin already holds '*' and is skipped.
 *
 * Rules (idempotent — only appends missing permissions):
 *   - Operational (can edit visits, or already hold any granular dental_*
 *     edit perm) → view + create + update
 *   - Viewers (can view visits or hold a granular dental_* view perm) → view
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue;
            }

            $operationalMarkers = ['visits.update', 'patients.update', 'dental_charts.update', 'dental_treatment_plans.create'];
            $viewerMarkers = ['visits.view', 'patients.view', 'dental_charts.view', 'dental_treatment_plans.view'];

            $grant = [];
            if (array_intersect($operationalMarkers, $perms)) {
                $grant = ['dental.view', 'dental.create', 'dental.update'];
            } elseif (array_intersect($viewerMarkers, $perms)) {
                $grant = ['dental.view'];
            }

            if (! $grant) {
                continue;
            }

            $merged = array_values(array_unique(array_merge($perms, $grant)));
            if (count($merged) !== count($perms)) {
                $role->update(['permissions' => $merged]);
            }
        }
    }

    public function down(): void
    {
        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue;
            }
            $filtered = array_values(array_diff($perms, ['dental.view', 'dental.create', 'dental.update', 'dental.delete']));
            if (count($filtered) !== count($perms)) {
                $role->update(['permissions' => $filtered]);
            }
        }
    }
};
