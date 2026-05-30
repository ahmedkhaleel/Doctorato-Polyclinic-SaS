<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the obgyn.* permission family onto existing roles, mirroring the
 * pediatric grant. obgyn admin/doctor/secretary routes are gated by
 * permission:obgyn.view (+create/update/delete); without this only
 * super_admin ('*') could open the module.
 *
 * Idempotent; super_admin skipped (already has '*').
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
                $grant = ['obgyn.view', 'obgyn.create', 'obgyn.update'];
            } elseif (array_intersect(['visits.view', 'patients.view'], $perms)) {
                $grant = ['obgyn.view'];
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
            $filtered = array_values(array_diff($perms, ['obgyn.view', 'obgyn.create', 'obgyn.update', 'obgyn.delete']));
            if (count($filtered) !== count($perms)) {
                $role->update(['permissions' => $filtered]);
            }
        }
    }
};
