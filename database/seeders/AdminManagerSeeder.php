<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminManagerSeeder extends Seeder
{
    public function run(): void
    {
        // Create the Administrative Manager role with VIEW-ONLY permissions
        $role = Role::updateOrCreate(
            ['name' => 'admin_manager'],
            [
                'display_name_en' => 'Administrative Manager',
                'display_name_ar' => 'المدير الإداري',
                'permissions' => [
                    // ─── Patients & Clinic ───
                    'patients.view',
                    'visits.view',
                    'bookings.view',
                    'prescriptions.view',
                    'medications.view',

                    // ─── Finance ───
                    'invoices.view',
                    'payments.view',
                    'expenses.view',
                    'discount_codes.view',
                    'doctor_payouts.view',
                    'package_bundles.view',
                    'package_bundle_bookings.view',

                    // ─── HR ───
                    'departments.view',
                    'employees.view',
                    'shifts.view',
                    'attendances.view',
                    'leaves.view',
                    'salary_slips.view',
                    'advances.view',
                    'penalties.view',

                    // ─── Content ───
                    'services.view',
                    'service_categories.view',
                    'doctors.view',
                    'gallery.view',
                    'testimonials.view',
                    'faqs.view',
                    'pages.view',
                    'posts.view',
                    'post_categories.view',
                    'tags.view',

                    // ─── CRM ───
                    'leads.view',
                    'lead_sources.view',
                    'crm_campaigns.view',
                    'communication_templates.view',
                    'marketer_commissions.view',

                    // ─── Communication ───
                    'contact_messages.view',

                    // ─── Reports ───
                    'reports.view',

                    // ─── Administration (view only) ───
                    'users.view',
                    'roles.view',
                    'settings.view',
                ],
                'is_system' => false,
            ]
        );

        // Create the admin manager user
        User::updateOrCreate(
            ['email' => 'manager@aura-clinic.net'],
            [
                'name' => 'Administrative Manager',
                'username' => 'manager',
                'email' => 'manager@aura-clinic.net',
                'password' => Hash::make('Manager@2026'),
                'role_id' => $role->id,
                'is_active' => true,
            ]
        );
    }
}
