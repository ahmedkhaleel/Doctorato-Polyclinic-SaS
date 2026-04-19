<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production mode: Seeds only essential data (roles, users, website content, reference data).
     * Development mode: Also seeds demo/fake data (patients, visits, invoices, etc.).
     *
     * Usage on server:
     *   php artisan migrate --seed
     *   OR
     *   php artisan db:seed
     */
    public function run(): void
    {
        $isProduction = app()->environment('production');

        // ── 1. Roles & Users (always required) ──────────────────────────
        $this->call(RoleSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $secretaryRole  = Role::where('name', 'secretary')->first();

        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@aura.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
            ]
        );

        // Secretary 1
        User::firstOrCreate(
            ['email' => 'secretary@auraderma.com'],
            [
                'name' => 'Secretary',
                'password' => Hash::make('password'),
                'role_id' => $secretaryRole->id,
                'is_active' => true,
            ]
        );

        // Secretary 2
        User::firstOrCreate(
            ['email' => 'secretary@aura.com'],
            [
                'name' => 'Secretary',
                'password' => Hash::make('password'),
                'role_id' => $secretaryRole->id,
                'is_active' => true,
            ]
        );

        // ── 2. Website content (always required for the website to display) ──
        $this->call([
            ServiceSeeder::class,
            DoctorSeeder::class,
            SettingSeeder::class,
            FaqSeeder::class,
            PageSeeder::class,
            PostCategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            TestimonialSeeder::class,
            PackageBundleSeeder::class,
            DiscountCodeSeeder::class,
            GallerySeeder::class,
            HeroSlideSeeder::class,
            SeoPageSeeder::class,
        ]);

        // ── 3. Clinic reference data (always required for clinic system) ──
        $this->call([
            PaymentMethodSeeder::class,
            MedicationSeeder::class,
            ShiftSeeder::class,
            ExpenseCategorySeeder::class,
        ]);

        // ── 3b. CRM reference data ──────────────────────────────
        $this->call([
            LeadSourceSeeder::class,
            LeadScoringRuleSeeder::class,
            LeadAssignmentRuleSeeder::class,
            CommunicationTemplateSeeder::class,
            FollowUpSequenceSeeder::class,
        ]);

        // ── 4. Demo data (development only — skip in production) ──
        if (! $isProduction) {
            $this->command->info('Development mode: Seeding demo data (patients, visits, invoices...)');

            $this->call([
                BookingSeeder::class,
                DemoDataSeeder::class,
                ContactMessageSeeder::class,
                PatientSeeder::class,
                ClinicVisitSeeder::class,
                PrescriptionSeeder::class,
                InvoiceSeeder::class,
                ExpenseSeeder::class,
                AttendanceSeeder::class,
                ComprehensiveDemoSeeder::class,
            ]);
        } else {
            $this->command->info('Production mode: Skipping demo data. Only essential data seeded.');
            $this->command->info('');
            $this->command->info('Roles & admin users created');
            $this->command->info('Website content seeded (services, doctors, FAQs, pages, posts, etc.)');
            $this->command->info('Clinic reference data seeded (payment methods, supplies, etc.)');
            $this->command->info('Doctor accounts & schedules created');
            $this->command->info('');
            $this->command->info('Remember to change default passwords after seeding!');
        }
    }
}
