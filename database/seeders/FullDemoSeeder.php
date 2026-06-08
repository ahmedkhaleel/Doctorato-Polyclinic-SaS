<?php

namespace Database\Seeders;

use App\Services\ModuleManager;
use Illuminate\Database\Seeder;

/**
 * One-stop demo: a navigable, fully-populated showcase with a login for EVERY
 * role AND every medical specialty.
 *
 *   1. Enables all medical modules (+ telemedicine) so every panel/page is
 *      reachable in the demo (the modules normally ship disabled).
 *   2. SpecialtyDoctorDemoSeeder → one doctor login per specialty
 *      (demo.<module>@doctorato.net) each owning a complete self-contained data
 *      set: patients, bookings (past + upcoming), completed visits + commissions,
 *      invoices + payments, and rich specialty clinical records.
 *   3. DemoUserSeeder → the role logins (demo.admin / demo.secretary /
 *      demo.patient / demo.doctor) over that shared clinic data — the secretary
 *      front desks, admin dashboards and patient portal all show real content.
 *
 * Idempotent (both child seeders skip already-populated rows). Demo/staging
 * ONLY — never in the auto-deploy path. Password = env('DEMO_PASSWORD').
 *
 *   php artisan db:seed --class=Database\\Seeders\\FullDemoSeeder
 */
class FullDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Make the whole demo navigable.
        $modules = array_merge(ModuleManager::MEDICAL_MODULES, ['telemedicine']);
        foreach ($modules as $slug) {
            ModuleManager::enable($slug);
        }
        ModuleManager::flushStaticCache();
        $this->command?->info('Enabled modules: '.implode(', ', $modules));

        // 2. Per-specialty doctor logins + their data (creates patients first).
        $this->call(SpecialtyDoctorDemoSeeder::class);

        // 3. Role logins (admin / secretary / patient / generic doctor) over the data.
        $this->call(DemoUserSeeder::class);

        $this->command?->info('Full demo ready. Logins (password = DEMO_PASSWORD):');
        foreach (['demo.admin', 'demo.secretary', 'demo.patient', 'demo.doctor'] as $u) {
            $this->command?->info("  {$u}@doctorato.net");
        }
        foreach (ModuleManager::MEDICAL_MODULES as $m) {
            $this->command?->info("  demo.{$m}@doctorato.net");
        }
    }
}
