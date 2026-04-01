<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin - full access (system role, cannot be deleted)
        Role::updateOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name_en' => 'Super Admin',
                'display_name_ar' => 'المدير العام',
                'permissions' => ['*'],
                'is_system' => true,
            ]
        );

        // Editor - content management
        Role::updateOrCreate(
            ['name' => 'editor'],
            [
                'display_name_en' => 'Editor',
                'display_name_ar' => 'محرر',
                'permissions' => [
                    'posts.view', 'posts.create', 'posts.update', 'posts.delete',
                    'post_categories.view', 'post_categories.create', 'post_categories.update', 'post_categories.delete',
                    'tags.view', 'tags.create', 'tags.update', 'tags.delete',
                    'services.view', 'services.create', 'services.update', 'services.delete',
                    'service_categories.view', 'service_categories.create', 'service_categories.update', 'service_categories.delete',
                    'doctors.view', 'doctors.create', 'doctors.update', 'doctors.delete',
                    'gallery.view', 'gallery.create', 'gallery.update', 'gallery.delete',
                    'testimonials.view', 'testimonials.create', 'testimonials.update', 'testimonials.delete',
                    'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
                    'pages.view', 'pages.update',
                ],
            ]
        );

        // Moderator - bookings & messages
        Role::updateOrCreate(
            ['name' => 'moderator'],
            [
                'display_name_en' => 'Moderator',
                'display_name_ar' => 'مشرف',
                'permissions' => [
                    'bookings.view', 'bookings.update', 'bookings.export',
                    'contact_messages.view', 'contact_messages.delete',
                    // CRM
                    'leads.view', 'leads.create', 'leads.update',
                    'lead_activities.view', 'lead_activities.create',
                    'lead_follow_ups.view', 'lead_follow_ups.create', 'lead_follow_ups.update',
                ],
            ]
        );

        // Receptionist - patient management & visit workflow
        Role::updateOrCreate(
            ['name' => 'receptionist'],
            [
                'display_name_en' => 'Receptionist',
                'display_name_ar' => 'موظفة الاستقبال',
                'permissions' => [
                    'patients.view', 'patients.create', 'patients.update',
                    'visits.view', 'visits.create', 'visits.update',
                    'invoices.view', 'invoices.create',
                    'payments.view', 'payments.create',
                    'discount_codes.view',
                    'bookings.view', 'bookings.update',
                    // CRM
                    'leads.view', 'leads.create', 'leads.update',
                    'lead_activities.view', 'lead_activities.create',
                    'lead_follow_ups.view', 'lead_follow_ups.create', 'lead_follow_ups.update',
                ],
            ]
        );

        // Secretary - separate panel with operational permissions
        Role::updateOrCreate(
            ['name' => 'secretary'],
            [
                'display_name_en' => 'Secretary',
                'display_name_ar' => 'سكرتارية',
                'permissions' => [
                    'patients.view', 'patients.create', 'patients.update',
                    'visits.view', 'visits.create', 'visits.update',
                    'bookings.view', 'bookings.update',
                    'invoices.view', 'invoices.create',
                    'payments.view', 'payments.create',
                    'discount_codes.view',
                    'prescriptions.view',
                    'contact_messages.view',
                    // CRM
                    'leads.view', 'leads.create', 'leads.update',
                    'lead_activities.view', 'lead_activities.create',
                    'lead_follow_ups.view', 'lead_follow_ups.create', 'lead_follow_ups.update',
                ],
            ]
        );

        // Doctor - clinical permissions
        Role::updateOrCreate(
            ['name' => 'doctor'],
            [
                'display_name_en' => 'Doctor',
                'display_name_ar' => 'طبيب',
                'permissions' => [
                    'patients.view',
                    'visits.view', 'visits.update',
                    'prescriptions.view', 'prescriptions.create', 'prescriptions.update',
                    'invoices.view',
                    'reports.view',
                ],
            ]
        );

        // Accountant - financial permissions
        Role::updateOrCreate(
            ['name' => 'accountant'],
            [
                'display_name_en' => 'Accountant',
                'display_name_ar' => 'محاسب',
                'permissions' => [
                    'invoices.view', 'invoices.create', 'invoices.update',
                    'payments.view', 'payments.create', 'payments.delete',
                    'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
                    'discount_codes.view', 'discount_codes.create', 'discount_codes.update',
                    'doctor_payouts.view', 'doctor_payouts.create', 'doctor_payouts.update',
                    'employees.view', 'departments.view',
                    'salary_slips.view', 'salary_slips.create', 'salary_slips.update',
                    'advances.view', 'advances.create', 'advances.update',
                    'penalties.view', 'penalties.create',
                    'reports.view',
                    'patients.view',
                ],
            ]
        );

        // Webmaster - website content management (separate panel)
        Role::updateOrCreate(
            ['name' => 'webmaster'],
            [
                'display_name_en' => 'Webmaster',
                'display_name_ar' => 'مدير الموقع',
                'permissions' => [
                    'services.view', 'services.create', 'services.update', 'services.delete',
                    'service_categories.view', 'service_categories.create', 'service_categories.update', 'service_categories.delete',
                    'doctors.view', 'doctors.create', 'doctors.update', 'doctors.delete',
                    'gallery.view', 'gallery.create', 'gallery.update', 'gallery.delete',
                    'testimonials.view', 'testimonials.create', 'testimonials.update', 'testimonials.delete',
                    'faqs.view', 'faqs.create', 'faqs.update', 'faqs.delete',
                    'pages.view', 'pages.update',
                    'posts.view', 'posts.create', 'posts.update', 'posts.delete',
                    'post_categories.view', 'post_categories.create', 'post_categories.update', 'post_categories.delete',
                    'tags.view', 'tags.create', 'tags.update', 'tags.delete',
                    'settings.view', 'settings.update',
                ],
                'is_system' => true,
            ]
        );
    }
}
