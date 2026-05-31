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
                    'seo_pages.view', 'seo_pages.update',
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
                    'queue.view', 'queue.update',
                    'calendar.view',
                    'derma.view', 'derma.create', 'derma.update',
                    'dental.view', 'dental.create', 'dental.update',
                    'pediatric.view', 'pediatric.create', 'pediatric.update',
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
                    'queue.view', 'queue.update',
                    'calendar.view',
                    'derma.view', 'derma.create', 'derma.update',
                    'dental.view', 'dental.create', 'dental.update',
                    'pediatric.view', 'pediatric.create', 'pediatric.update',
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
                    'patients.view', 'patients.view_sensitive_medical',
                    'visits.view', 'visits.update',
                    'prescriptions.view', 'prescriptions.create', 'prescriptions.update',
                    'medications.view',
                    'invoices.view',
                    'reports.view',
                    'calendar.view',
                    'dental.view', 'dental.create', 'dental.update',
                    'pediatric.view', 'pediatric.create', 'pediatric.update',
                    'dental_treatment_plans.view', 'dental_treatment_plans.create', 'dental_treatment_plans.update',
                    'dental_lab_orders.view', 'dental_lab_orders.create',
                    'dental_followups.view', 'dental_followups.create', 'dental_followups.update',
                    'dental_xrays.view', 'dental_xrays.create', 'dental_xrays.update',
                    'dental_charts.view', 'dental_charts.update',
                    'derma.view', 'derma.create', 'derma.update',
                ],
            ]
        );

        // Administrative Manager - view-only access to all data
        Role::updateOrCreate(
            ['name' => 'admin_manager'],
            [
                'display_name_en' => 'Administrative Manager',
                'display_name_ar' => 'المدير الإداري',
                'permissions' => [
                    'patients.view', 'visits.view', 'bookings.view', 'prescriptions.view',
                    'medications.view', 'doctors.view', 'queue.view', 'calendar.view',
                    'appointment_reminders.view', 'medical_certificates.view', 'consent_forms.view',
                    'referrals.view', 'satisfaction.view',
                    'invoices.view', 'payments.view', 'expenses.view', 'expense_categories.view',
                    'discount_codes.view', 'doctor_payouts.view', 'package_bundles.view',
                    'package_bundle_bookings.view', 'credit_notes.view', 'wallets.view',
                    'payment_methods.view', 'reports.view',
                    'hr.view', 'departments.view', 'employees.view', 'shifts.view', 'attendances.view',
                    'leaves.view', 'leaves.approve', 'leaves.delete', 'salary_slips.view', 'advances.view', 'penalties.view', 'schedules.view',
                    'loyalty.view', 'loyalty.manage', 'recall.view', 'recall.send',
                    'telemedicine.view', 'branches.view', 'branches.manage',
                    'leads.view', 'lead_activities.view', 'lead_follow_ups.view', 'lead_sources.view',
                    'crm_campaigns.view', 'communication_templates.view', 'scoring_rules.view',
                    'assignment_rules.view', 'marketer_commissions.view',
                    'dental.view',
                    'dental_treatment_plans.view', 'dental_lab_orders.view', 'dental_followups.view',
                    'dental_xrays.view', 'dental_charts.view',
                    'derma.view',
                    'pediatric.view',
                    'insurance.view', 'insurance_companies.view', 'insurance_claims.view',
                    'inventory.view', 'supplies.view', 'supply_categories.view', 'purchase_orders.view', 'suppliers.view',
                    'posts.view', 'post_categories.view', 'tags.view', 'services.view',
                    'service_categories.view', 'gallery.view', 'testimonials.view', 'faqs.view',
                    'pages.view', 'seo_pages.view',
                    'users.view', 'roles.view', 'settings.view', 'contact_messages.view',
                    'notifications.view', 'audit_logs.view', 'modules_management.view',
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
                    'expense_categories.view', 'expense_categories.create', 'expense_categories.update',
                    'discount_codes.view', 'discount_codes.create', 'discount_codes.update',
                    'doctor_payouts.view', 'doctor_payouts.create', 'doctor_payouts.update',
                    'credit_notes.view', 'credit_notes.create', 'credit_notes.update',
                    'wallets.view', 'wallets.create', 'wallets.update',
                    'payment_methods.view',
                    'employees.view', 'departments.view',
                    'salary_slips.view', 'salary_slips.create', 'salary_slips.update',
                    'advances.view', 'advances.create', 'advances.update',
                    'penalties.view', 'penalties.create',
                    'reports.view', 'reports.export',
                    'patients.view',
                    'insurance_claims.view', 'insurance_claims.create', 'insurance_claims.update',
                    'insurance_companies.view',
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
                    'seo_pages.view', 'seo_pages.update',
                    'settings.view', 'settings.update',
                ],
                'is_system' => true,
            ]
        );
    }
}
