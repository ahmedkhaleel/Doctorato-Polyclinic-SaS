<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the four sales-demo accounts (admin / doctor / secretary / patient).
 * They are flagged is_demo, so DemoModeGuard blocks deletes + core-settings.
 * The demo admin role grants every view/create/update permission EXCEPT delete
 * and core-settings, so the UI itself reflects what they can do.
 *
 * Idempotent. Password comes from env DEMO_PASSWORD (default below).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD', 'DemoClinic@2026');

        // ── Demo admin role (named 'admin' to pass AdminAuth whitelist) ──
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Demo Admin',
                'display_name_ar' => 'مدير تجريبي',
                'permissions' => $this->demoAdminPermissions(),
                'is_system' => false,
            ]
        );

        $admin = $this->makeUser('demo.admin@doctorato.net', 'Demo Admin / مدير تجريبي', $adminRole->id, $password);

        // ── Demo doctor → link to a populated demo Doctor ──
        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['ai.view', 'ai.doctor'], 'is_system' => true]
        );
        $doctorUser = $this->makeUser('demo.doctor@doctorato.net', 'Demo Doctor / طبيب تجريبي', $doctorRole->id, $password);
        $this->attachDoctor($doctorUser);

        // ── Demo secretary ──
        $secretaryRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]
        );
        $this->makeUser('demo.secretary@doctorato.net', 'Demo Secretary / سكرتارية تجريبية', $secretaryRole->id, $password);

        // ── Demo patient → link to a populated demo Patient (website portal) ──
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );
        $patientUser = $this->makeUser('demo.patient@doctorato.net', 'Demo Patient / مريض تجريبي', $patientRole->id, $password);
        $this->attachPatient($patientUser);

        $this->command->info('Demo users ready (password from DEMO_PASSWORD, default DemoClinic@2026):');
        foreach (['demo.admin', 'demo.doctor', 'demo.secretary', 'demo.patient'] as $u) {
            $this->command->info("  {$u}@doctorato.net");
        }
    }

    private function makeUser(string $email, string $name, int $roleId, string $password): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'role_id' => $roleId,
            'is_active' => true,
            'is_demo' => true,
            'password' => Hash::make($password),
        ]);
        if (method_exists($user, 'restore') && $user->trashed()) {
            $user->restore();
        }
        $user->save();

        return $user;
    }

    private function attachDoctor(User $user): void
    {
        // Prefer a demo doctor that already has visits, so the panel is populated.
        $doctorId = DB::table('visits')->select('doctor_id')->whereNotNull('doctor_id')
            ->groupBy('doctor_id')->orderByRaw('COUNT(*) DESC')->value('doctor_id');

        $doctor = $doctorId ? Doctor::find($doctorId) : Doctor::first();

        if (! $doctor) {
            $doctor = Doctor::create([
                'name_ar' => 'طبيب تجريبي', 'name_en' => 'Demo Doctor', 'status' => 'active',
            ]);
        }
        $doctor->user_id = $user->id;
        $doctor->save();
    }

    private function attachPatient(User $user): void
    {
        // Prefer a patient that already has bookings, so the portal shows history.
        $patientId = DB::table('bookings')->select('patient_id')->whereNotNull('patient_id')
            ->groupBy('patient_id')->orderByRaw('COUNT(*) DESC')->value('patient_id');

        $patient = $patientId ? Patient::find($patientId) : Patient::first();

        if (! $patient) {
            $patient = new Patient(['full_name' => 'مريض تجريبي', 'phone' => '01000000000']);
            $patient->file_number = Patient::generateFileNumber();
            $patient->is_active = true;
            $patient->save();
        }
        $patient->user_id = $user->id;
        $patient->save();
    }

    /** All view/create/update permissions, minus delete and core-settings. */
    private function demoAdminPermissions(): array
    {
        $coreSettingsModules = ['settings', 'users', 'roles', 'permissions', 'modules_management', 'branches', 'ai'];
        $blockedActions = ['delete', 'destroy', 'force_delete', 'empty_trash', 'manage', 'prompts'];

        $perms = [];
        foreach ((array) config('permissions.modules', []) as $key => $module) {
            foreach ((array) ($module['actions'] ?? []) as $action) {
                if (in_array($action, $blockedActions, true)) {
                    continue;
                }
                if (in_array($key, $coreSettingsModules, true) && $action !== 'view') {
                    continue; // core settings: view only
                }
                $perms[] = "{$key}.{$action}";
            }
        }

        return array_values(array_unique($perms));
    }
}
