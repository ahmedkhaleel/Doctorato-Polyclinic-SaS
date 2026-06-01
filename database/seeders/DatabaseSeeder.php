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
        $secretaryRole = Role::where('name', 'secretary')->first();

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

        // Administrative Manager role + user (idempotent)
        $this->call(AdminManagerSeeder::class);

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
            ObgynServiceSeeder::class,
        ]);

        // ── 3. Clinic reference data (always required for clinic system) ──
        // All idempotent (firstOrCreate). Order respects dependencies:
        // SupplyCategory → Supply; ServiceSeeder (section 2) → Dental/Pediatric
        // service catalogs → packages; DentalSeeder needs Role (section 1) + Service.
        $this->call([
            PaymentMethodSeeder::class,
            MedicationSeeder::class,
            ShiftSeeder::class,
            ExpenseCategorySeeder::class,
            DepartmentSeeder::class,
            SupplyCategorySeeder::class,
            SupplySeeder::class,
            DentalServiceSeeder::class,
            DentalSeeder::class,
            DentalPackageSeeder::class,
            DentalPrescriptionTemplateSeeder::class,
            PediatricServiceSeeder::class,
            PediatricPackageSeeder::class,
            SmsTemplateSeeder::class,
            AiSettingsSeeder::class,
        ]);

        // ── 3b. CRM reference data ──────────────────────────────
        $this->call([
            LeadSourceSeeder::class,
            LeadScoringRuleSeeder::class,
            LeadAssignmentRuleSeeder::class,
            CommunicationTemplateSeeder::class,
            FollowUpSequenceSeeder::class,
            CrmCampaignSeeder::class,
        ]);

        // ── 4. Demo data (dev always; production only with explicit opt-in) ──
        // Every demo seeder is guarded (skips rows that already exist by phone /
        // count), so this block is SAFE to re-run. On the live server it runs ONLY
        // when SEED_DEMO_DATA=true is set in .env — set it once to fill the site,
        // run the seed, then remove the flag so a later deploy never re-injects
        // demo data onto a real clinic.
        $seedDemo = ! $isProduction || filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN);

        if ($seedDemo) {
            $this->command->info('Seeding demo data (patients, visits, invoices...)');

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
                ObgynDemoSeeder::class,
                OnlineConsultationDemoSeeder::class,
                // Fills every remaining operational table so all screens show data.
                ShowcaseDemoSeeder::class,
            ]);
        } else {
            $this->command->info('Production mode: Skipping demo data (set SEED_DEMO_DATA=true to fill).');
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
