<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the new derma.* permissions onto existing roles.
 *
 * Commit 4ef.. introduced a real `derma` permission family and gated the
 * derma/cosmetic routes with permission:derma.view (+create/update/delete).
 * Without this backfill, roles that previously had implicit access (the
 * routes had no permission gate before) would lose it on deploy.
 *
 * Rules (super_admin already holds '*', so it is skipped automatically):
 *   - Operational clinical roles (can edit visits) → view + create + update
 *   - View-only clinical/admin roles (can view visits) → view
 *
 * Idempotent: only appends missing permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];

            // Wildcard roles already cover everything.
            if (in_array('*', $perms, true)) {
                continue;
            }

            $grant = [];
            $isOperational = array_intersect(
                ['visits.update', 'patients.update', 'dental_treatment_plans.create'],
                $perms
            );
            $isViewer = in_array('visits.view', $perms, true) || in_array('patients.view', $perms, true);

            if ($isOperational) {
                $grant = ['derma.view', 'derma.create', 'derma.update'];
            } elseif ($isViewer) {
                $grant = ['derma.view'];
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
            $filtered = array_values(array_diff($perms, ['derma.view', 'derma.create', 'derma.update', 'derma.delete']));
            if (count($filtered) !== count($perms)) {
                $role->update(['permissions' => $filtered]);
            }
        }
    }
};
