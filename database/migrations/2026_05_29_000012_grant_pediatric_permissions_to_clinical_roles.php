<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the pediatric.* permission family onto existing roles.
 *
 * Pediatric admin routes are gated by permission:pediatric.view (+create/
 * update/delete), but `pediatric.*` was never a real permission family —
 * so only super_admin could open the pediatric module. This grants the now-
 * real pediatric.* permissions to the clinical roles so they regain access.
 *
 * Idempotent; super_admin ('*') skipped.
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

            $grant = [];
            if (array_intersect(['visits.update', 'patients.update'], $perms)) {
                $grant = ['pediatric.view', 'pediatric.create', 'pediatric.update'];
            } elseif (array_intersect(['visits.view', 'patients.view'], $perms)) {
                $grant = ['pediatric.view'];
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
            $filtered = array_values(array_diff($perms, ['pediatric.view', 'pediatric.create', 'pediatric.update', 'pediatric.delete']));
            if (count($filtered) !== count($perms)) {
                $role->update(['permissions' => $filtered]);
            }
        }
    }
};
