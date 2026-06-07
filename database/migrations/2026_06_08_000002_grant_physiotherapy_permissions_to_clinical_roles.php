<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * PT-0.2 — backfill the physiotherapy.* permission family onto existing roles,
 * mirroring the obgyn/neuropsych grant. Module routes are gated by
 * permission:physiotherapy.view (+create/update/delete); without this only
 * super_admin ('*') could open the module. No sensitive sub-permission
 * (physiotherapy has no heightened-RBAC data). Idempotent; super_admin skipped.
 */
return new class extends Migration
{
    private string $family = 'physiotherapy';

    public function up(): void
    {
        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue;
            }

            $grant = [];
            if (array_intersect(['visits.update', 'patients.update'], $perms)) {
                $grant = ["{$this->family}.view", "{$this->family}.create", "{$this->family}.update"];
            } elseif (array_intersect(['visits.view', 'patients.view'], $perms)) {
                $grant = ["{$this->family}.view"];
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
        $all = ["{$this->family}.view", "{$this->family}.create", "{$this->family}.update", "{$this->family}.delete"];

        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue;
            }
            $filtered = array_values(array_diff($perms, $all));
            if (count($filtered) !== count($perms)) {
                $role->update(['permissions' => $filtered]);
            }
        }
    }
};
