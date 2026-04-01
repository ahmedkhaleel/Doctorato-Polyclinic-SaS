<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Modules Registry
    |--------------------------------------------------------------------------
    |
    | This is the single source of truth for ALL permissions in the system.
    | Each module defines its label (bilingual) and available actions.
    | Permissions are generated as: module_key.action (e.g., posts.view)
    |
    */

    'modules' => [
        'posts' => [
            'label_en' => 'Blog Posts',
            'label_ar' => 'المقالات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'post_categories' => [
            'label_en' => 'Post Categories',
            'label_ar' => 'أقسام المقالات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'tags' => [
            'label_en' => 'Tags',
            'label_ar' => 'الوسوم',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'services' => [
            'label_en' => 'Services',
            'label_ar' => 'الخدمات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'service_categories' => [
            'label_en' => 'Service Categories',
            'label_ar' => 'أقسام الخدمات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'doctors' => [
            'label_en' => 'Doctors',
            'label_ar' => 'الأطباء',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'gallery' => [
            'label_en' => 'Gallery',
            'label_ar' => 'المعرض',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'testimonials' => [
            'label_en' => 'Testimonials',
            'label_ar' => 'شهادات العملاء',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'faqs' => [
            'label_en' => 'FAQ',
            'label_ar' => 'الأسئلة الشائعة',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'pages' => [
            'label_en' => 'Pages',
            'label_ar' => 'الصفحات',
            'actions' => ['view', 'update'],
        ],
        'bookings' => [
            'label_en' => 'Bookings',
            'label_ar' => 'الحجوزات',
            'actions' => ['view', 'create', 'update', 'delete', 'export', 'edit_services'],
        ],
        'contact_messages' => [
            'label_en' => 'Contact Messages',
            'label_ar' => 'رسائل التواصل',
            'actions' => ['view', 'delete'],
        ],
        'users' => [
            'label_en' => 'Users',
            'label_ar' => 'المستخدمين',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'roles' => [
            'label_en' => 'Roles & Permissions',
            'label_ar' => 'الأدوار والصلاحيات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'settings' => [
            'label_en' => 'Settings',
            'label_ar' => 'الإعدادات',
            'actions' => ['view', 'update'],
        ],

        // ─── Clinic Management Modules ──────────────────────

        'patients' => [
            'label_en' => 'Patients',
            'label_ar' => 'المرضى',
            'actions' => ['view', 'create', 'update', 'delete', 'view_sensitive_medical', 'update_sensitive_medical'],
        ],
        'visits' => [
            'label_en' => 'Visits',
            'label_ar' => 'الزيارات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'package_bundles' => [
            'label_en' => 'Package Bundles',
            'label_ar' => 'حزم الباقات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'package_bundle_bookings' => [
            'label_en' => 'Package Bundle Bookings',
            'label_ar' => 'حجوزات حزم الباقات',
            'actions' => ['view', 'create', 'update', 'cancel', 'process_payment'],
        ],
        'prescriptions' => [
            'label_en' => 'Prescriptions',
            'label_ar' => 'الوصفات الطبية',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'medications' => [
            'label_en' => 'Medications',
            'label_ar' => 'الأدوية',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'invoices' => [
            'label_en' => 'Invoices',
            'label_ar' => 'الفواتير',
            'actions' => ['view', 'create', 'update'],
        ],
        'payments' => [
            'label_en' => 'Payments',
            'label_ar' => 'المدفوعات',
            'actions' => ['view', 'create', 'delete'],
        ],
        'discount_codes' => [
            'label_en' => 'Discount Codes',
            'label_ar' => 'أكواد الخصم',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'expenses' => [
            'label_en' => 'Expenses',
            'label_ar' => 'المصروفات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'doctor_payouts' => [
            'label_en' => 'Doctor Payouts',
            'label_ar' => 'مدفوعات الأطباء',
            'actions' => ['view', 'create', 'update'],
        ],
        'supplies' => [
            'label_en' => 'Supplies & Inventory',
            'label_ar' => 'المستلزمات والمخزون',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'departments' => [
            'label_en' => 'Departments',
            'label_ar' => 'الأقسام',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'employees' => [
            'label_en' => 'Employees',
            'label_ar' => 'الموظفين',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'salary_slips' => [
            'label_en' => 'Payroll / Salary Slips',
            'label_ar' => 'كشوف الرواتب',
            'actions' => ['view', 'create', 'update'],
        ],
        'advances' => [
            'label_en' => 'Advances & Loans',
            'label_ar' => 'السلف والقروض',
            'actions' => ['view', 'create', 'update'],
        ],
        'penalties' => [
            'label_en' => 'Penalties & Rewards',
            'label_ar' => 'الجزاءات والمكافآت',
            'actions' => ['view', 'create', 'delete'],
        ],
        'shifts' => [
            'label_en' => 'Shifts',
            'label_ar' => 'الورديات',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'attendances' => [
            'label_en' => 'Attendance',
            'label_ar' => 'الحضور والانصراف',
            'actions' => ['view', 'create', 'update'],
        ],
        'leaves' => [
            'label_en' => 'Leaves',
            'label_ar' => 'الإجازات',
            'actions' => ['view', 'create', 'update'],
        ],
        'reports' => [
            'label_en' => 'Reports',
            'label_ar' => 'التقارير',
            'actions' => ['view'],
        ],

        // ─── CRM Modules ─────────────────────────────────────

        'leads' => [
            'label_en' => 'Leads',
            'label_ar' => 'العملاء المحتملين',
            'actions' => ['view', 'create', 'update', 'delete', 'assign', 'convert', 'export'],
        ],
        'crm_campaigns' => [
            'label_en' => 'CRM Campaigns',
            'label_ar' => 'حملات CRM',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'lead_sources' => [
            'label_en' => 'Lead Sources',
            'label_ar' => 'مصادر العملاء',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'communication_templates' => [
            'label_en' => 'Communication Templates',
            'label_ar' => 'قوالب التواصل',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'marketer_commissions' => [
            'label_en' => 'Marketer Commissions',
            'label_ar' => 'عمولات المسوقين',
            'actions' => ['view', 'create', 'update', 'approve'],
        ],
    ],

];
