<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * NP0.2 — backfill the psychiatry.* and neurology.* permission families onto
 * existing roles, mirroring the obgyn grant. The module routes are gated by
 * permission:psychiatry.view (+create/update/delete) and neurology.* ; without
 * this only super_admin ('*') could open the modules.
 *
 * Heightened RBAC: the *.view_sensitive permission (psychotherapy notes + risk
 * assessments) is granted ONLY to roles that already hold sensitive-medical
 * access (patients.view_sensitive_medical) — not to every clinical role.
 *
 * Idempotent; super_admin skipped (already has '*').
 */
return new class extends Migration
{
    private array $families = ['psychiatry', 'neurology'];

    public function up(): void
    {
        foreach (Role::all() as $role) {
            $perms = $role->permissions ?? [];
            if (in_array('*', $perms, true)) {
                continue;
            }

            $grant = [];
            foreach ($this->families as $fam) {
                if (array_intersect(['visits.update', 'patients.update'], $perms)) {
                    $grant = array_merge($grant, ["$fam.view", "$fam.create", "$fam.update"]);
                } elseif (array_intersect(['visits.view', 'patients.view'], $perms)) {
                    $grant[] = "$fam.view";
                }
                // Sensitive view only for roles already trusted with sensitive medical data.
                if (in_array('patients.view_sensitive_medical', $perms, true)) {
                    $grant[] = "$fam.view_sensitive";
                }
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
        $all = [];
        foreach ($this->families as $fam) {
            $all = array_merge($all, ["$fam.view", "$fam.create", "$fam.update", "$fam.delete", "$fam.view_sensitive"]);
        }

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
