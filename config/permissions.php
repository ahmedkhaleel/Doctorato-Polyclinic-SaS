<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Modules Registry
    |--------------------------------------------------------------------------
    |
    | Single source of truth for ALL permissions in the system.
    | Each module defines its label (bilingual), group, and available actions.
    | Permissions are generated as: module_key.action (e.g., posts.view)
    |
    */

    'groups' => [
        'clinic' => ['label_en' => 'Clinic Management',     'label_ar' => 'إدارة العيادة',         'icon' => 'hospital',    'color' => '#3B82F6'],
        'finance' => ['label_en' => 'Finance & Billing',     'label_ar' => 'المالية والفواتير',      'icon' => 'banknotes',   'color' => '#10B981'],
        'hr' => ['label_en' => 'Human Resources',       'label_ar' => 'الموارد البشرية',        'icon' => 'users',       'color' => '#8B5CF6'],
        'crm' => ['label_en' => 'CRM & Marketing',       'label_ar' => 'إدارة العملاء والتسويق', 'icon' => 'funnel',      'color' => '#F59E0B'],
        'dental' => ['label_en' => 'Dental Module',         'label_ar' => 'وحدة الأسنان',           'icon' => 'tooth',       'color' => '#06B6D4'],
        'derma' => ['label_en' => 'Dermatology & Cosmetic', 'label_ar' => 'الجلدية والتجميل',      'icon' => 'sparkles',    'color' => '#1B365D'],
        'pediatric' => ['label_en' => 'Pediatrics',            'label_ar' => 'طب الأطفال',             'icon' => 'child',       'color' => '#4CAF50'],
        'obgyn' => ['label_en' => 'Obstetrics & Gynecology', 'label_ar' => 'النساء والتوليد',     'icon' => 'heart',       'color' => '#DB2777'],
        'insurance' => ['label_en' => 'Insurance',             'label_ar' => 'التأمين',                'icon' => 'shield',      'color' => '#EC4899'],
        'inventory' => ['label_en' => 'Inventory & Supplies',  'label_ar' => 'المخزون والمستلزمات',    'icon' => 'box',         'color' => '#F97316'],
        'content' => ['label_en' => 'Content & Website',     'label_ar' => 'المحتوى والموقع',        'icon' => 'globe',       'color' => '#6366F1'],
        'system' => ['label_en' => 'System & Settings',     'label_ar' => 'النظام والإعدادات',      'icon' => 'cog',         'color' => '#64748B'],
    ],

    'modules' => [

        // ─── Clinic Management ─────────────────────────────────
        'patients' => [
            'label_en' => 'Patients',
            'label_ar' => 'المرضى',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete', 'view_sensitive_medical', 'update_sensitive_medical'],
        ],
        'visits' => [
            'label_en' => 'Visits',
            'label_ar' => 'الزيارات',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'bookings' => [
            'label_en' => 'Bookings',
            'label_ar' => 'الحجوزات',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete', 'export', 'edit_services'],
        ],
        'prescriptions' => [
            'label_en' => 'Prescriptions',
            'label_ar' => 'الوصفات الطبية',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'medications' => [
            'label_en' => 'Medications',
            'label_ar' => 'الأدوية',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'doctors' => [
            'label_en' => 'Doctors',
            'label_ar' => 'الأطباء',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'queue' => [
            'label_en' => 'Queue Management',
            'label_ar' => 'إدارة قائمة الانتظار',
            'group' => 'clinic',
            'actions' => ['view', 'update'],
        ],
        'calendar' => [
            'label_en' => 'Calendar',
            'label_ar' => 'التقويم',
            'group' => 'clinic',
            'actions' => ['view'],
        ],
        'appointment_reminders' => [
            'label_en' => 'Appointment Reminders',
            'label_ar' => 'تذكيرات المواعيد',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'medical_certificates' => [
            'label_en' => 'Medical Certificates',
            'label_ar' => 'الشهادات الطبية',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'consent_forms' => [
            'label_en' => 'Consent Forms',
            'label_ar' => 'نماذج الموافقة',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'referrals' => [
            'label_en' => 'Referrals',
            'label_ar' => 'الإحالات',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'satisfaction' => [
            'label_en' => 'Patient Satisfaction',
            'label_ar' => 'رضا المرضى',
            'group' => 'clinic',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'recall' => [
            'label_en' => 'Patient Recall',
            'label_ar' => 'استدعاء المرضى',
            'group' => 'clinic',
            'actions' => ['view', 'send'],
        ],
        'telemedicine' => [
            'label_en' => 'Telemedicine / Online Consultations',
            'label_ar' => 'التطبيب عن بُعد',
            'group' => 'clinic',
            'actions' => ['view', 'manage'],
        ],

        // ─── Finance & Billing ─────────────────────────────────
        'invoices' => [
            'label_en' => 'Invoices',
            'label_ar' => 'الفواتير',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'payments' => [
            'label_en' => 'Payments',
            'label_ar' => 'المدفوعات',
            'group' => 'finance',
            'actions' => ['view', 'create', 'delete'],
        ],
        'expenses' => [
            'label_en' => 'Expenses',
            'label_ar' => 'المصروفات',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'expense_categories' => [
            'label_en' => 'Expense Categories',
            'label_ar' => 'أقسام المصروفات',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'discount_codes' => [
            'label_en' => 'Discount Codes',
            'label_ar' => 'أكواد الخصم',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'doctor_payouts' => [
            'label_en' => 'Doctor Payouts',
            'label_ar' => 'مدفوعات الأطباء',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update'],
        ],
        'package_bundles' => [
            'label_en' => 'Package Bundles',
            'label_ar' => 'حزم الباقات',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'package_bundle_bookings' => [
            'label_en' => 'Package Bookings',
            'label_ar' => 'حجوزات الباقات',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'cancel', 'process_payment'],
        ],
        'credit_notes' => [
            'label_en' => 'Credit Notes',
            'label_ar' => 'إشعارات دائنة',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'wallets' => [
            'label_en' => 'Patient Wallets',
            'label_ar' => 'محافظ المرضى',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update'],
        ],
        'payment_methods' => [
            'label_en' => 'Payment Methods',
            'label_ar' => 'طرق الدفع',
            'group' => 'finance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'reports' => [
            'label_en' => 'Reports & Analytics',
            'label_ar' => 'التقارير والتحليلات',
            'group' => 'finance',
            'actions' => ['view', 'export'],
        ],

        // ─── Human Resources ───────────────────────────────────
        'departments' => [
            'label_en' => 'Departments',
            'label_ar' => 'الأقسام',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'hr' => [
            'label_en' => 'HR Dashboard',
            'label_ar' => 'لوحة الموارد البشرية',
            'group' => 'hr',
            'actions' => ['view'],
        ],
        'employees' => [
            'label_en' => 'Employees',
            'label_ar' => 'الموظفين',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'shifts' => [
            'label_en' => 'Shifts',
            'label_ar' => 'الورديات',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'attendances' => [
            'label_en' => 'Attendance',
            'label_ar' => 'الحضور والانصراف',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'leaves' => [
            'label_en' => 'Leaves',
            'label_ar' => 'الإجازات',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'approve', 'delete'],
        ],
        'salary_slips' => [
            'label_en' => 'Payroll / Salary Slips',
            'label_ar' => 'كشوف الرواتب',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update'],
        ],
        'advances' => [
            'label_en' => 'Advances & Loans',
            'label_ar' => 'السلف والقروض',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update'],
        ],
        'penalties' => [
            'label_en' => 'Penalties & Rewards',
            'label_ar' => 'الجزاءات والمكافآت',
            'group' => 'hr',
            'actions' => ['view', 'create', 'delete'],
        ],
        'schedules' => [
            'label_en' => 'Doctor Schedules',
            'label_ar' => 'جداول الأطباء',
            'group' => 'hr',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        // ─── CRM & Marketing ──────────────────────────────────
        'leads' => [
            'label_en' => 'Leads',
            'label_ar' => 'العملاء المحتملين',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete', 'assign', 'convert', 'export'],
        ],
        'loyalty' => [
            'label_en' => 'Loyalty & Referrals',
            'label_ar' => 'الولاء والإحالات',
            'group' => 'crm',
            'actions' => ['view', 'manage'],
        ],
        'lead_activities' => [
            'label_en' => 'Lead Activities',
            'label_ar' => 'أنشطة العملاء',
            'group' => 'crm',
            'actions' => ['view', 'create'],
        ],
        'lead_follow_ups' => [
            'label_en' => 'Follow-ups',
            'label_ar' => 'المتابعات',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'lead_sources' => [
            'label_en' => 'Lead Sources',
            'label_ar' => 'مصادر العملاء',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'crm_campaigns' => [
            'label_en' => 'CRM Campaigns',
            'label_ar' => 'حملات CRM',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'communication_templates' => [
            'label_en' => 'Communication Templates',
            'label_ar' => 'قوالب التواصل',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'scoring_rules' => [
            'label_en' => 'Scoring Rules',
            'label_ar' => 'قواعد التقييم',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'assignment_rules' => [
            'label_en' => 'Assignment Rules',
            'label_ar' => 'قواعد التعيين',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'marketer_commissions' => [
            'label_en' => 'Marketer Commissions',
            'label_ar' => 'عمولات المسوقين',
            'group' => 'crm',
            'actions' => ['view', 'create', 'update', 'approve', 'delete'],
        ],

        // ─── Dental Module ─────────────────────────────────────
        // Module-wide permission gating ALL dental admin screens (chart,
        // treatments, lab orders, dashboard…). The routes check dental.*,
        // so this MUST exist as a real permission family.
        'dental' => [
            'label_en' => 'Dental Module',
            'label_ar' => 'وحدة الأسنان',
            'group' => 'dental',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'dental_treatment_plans' => [
            'label_en' => 'Treatment Plans',
            'label_ar' => 'خطط العلاج',
            'group' => 'dental',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'dental_lab_orders' => [
            'label_en' => 'Lab Orders',
            'label_ar' => 'طلبات المختبر',
            'group' => 'dental',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'dental_followups' => [
            'label_en' => 'Dental Follow-ups',
            'label_ar' => 'متابعات الأسنان',
            'group' => 'dental',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'dental_xrays' => [
            'label_en' => 'Dental X-Rays',
            'label_ar' => 'أشعة الأسنان',
            'group' => 'dental',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'dental_charts' => [
            'label_en' => 'Dental Charts',
            'label_ar' => 'مخططات الأسنان',
            'group' => 'dental',
            'actions' => ['view', 'update'],
        ],

        // ─── Dermatology & Cosmetic Module ─────────────────────
        // One permission family gates BOTH the derma and cosmetic
        // sub-features (cosmetic lives under the derma module).
        'derma' => [
            'label_en' => 'Dermatology & Cosmetic',
            'label_ar' => 'الجلدية والتجميل',
            'group' => 'derma',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        // ─── Pediatrics Module ─────────────────────────────────
        'pediatric' => [
            'label_en' => 'Pediatrics',
            'label_ar' => 'طب الأطفال',
            'group' => 'pediatric',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        // ─── Obstetrics & Gynecology Module ────────────────────
        'obgyn' => [
            'label_en' => 'Obstetrics & Gynecology',
            'label_ar' => 'النساء والتوليد',
            'group' => 'obgyn',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        // ─── Insurance ─────────────────────────────────────────
        'insurance' => [
            'label_en' => 'Insurance Access',
            'label_ar' => 'الوصول للتأمين',
            'group' => 'insurance',
            'actions' => ['view'],
        ],
        'insurance_companies' => [
            'label_en' => 'Insurance Companies',
            'label_ar' => 'شركات التأمين',
            'group' => 'insurance',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'insurance_claims' => [
            'label_en' => 'Insurance Claims',
            'label_ar' => 'مطالبات التأمين',
            'group' => 'insurance',
            'actions' => ['view', 'create', 'update', 'delete', 'approve'],
        ],

        // ─── Inventory & Supplies ──────────────────────────────
        'inventory' => [
            'label_en' => 'Inventory Access',
            'label_ar' => 'الوصول للمخزون',
            'group' => 'inventory',
            'actions' => ['view'],
        ],
        'supplies' => [
            'label_en' => 'Supplies & Inventory',
            'label_ar' => 'المستلزمات والمخزون',
            'group' => 'inventory',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'supply_categories' => [
            'label_en' => 'Supply Categories',
            'label_ar' => 'أقسام المستلزمات',
            'group' => 'inventory',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'purchase_orders' => [
            'label_en' => 'Purchase Orders',
            'label_ar' => 'أوامر الشراء',
            'group' => 'inventory',
            'actions' => ['view', 'create', 'update', 'delete', 'approve'],
        ],
        'suppliers' => [
            'label_en' => 'Suppliers',
            'label_ar' => 'الموردين',
            'group' => 'inventory',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        // ─── Content & Website ─────────────────────────────────
        'posts' => [
            'label_en' => 'Blog Posts',
            'label_ar' => 'المقالات',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'post_categories' => [
            'label_en' => 'Post Categories',
            'label_ar' => 'أقسام المقالات',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'tags' => [
            'label_en' => 'Tags',
            'label_ar' => 'الوسوم',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'services' => [
            'label_en' => 'Services',
            'label_ar' => 'الخدمات',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'service_categories' => [
            'label_en' => 'Service Categories',
            'label_ar' => 'أقسام الخدمات',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'gallery' => [
            'label_en' => 'Gallery',
            'label_ar' => 'المعرض',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'testimonials' => [
            'label_en' => 'Testimonials',
            'label_ar' => 'شهادات العملاء',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'faqs' => [
            'label_en' => 'FAQ',
            'label_ar' => 'الأسئلة الشائعة',
            'group' => 'content',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'pages' => [
            'label_en' => 'Pages',
            'label_ar' => 'الصفحات',
            'group' => 'content',
            'actions' => ['view', 'update'],
        ],
        'seo_pages' => [
            'label_en' => 'SEO Pages',
            'label_ar' => 'صفحات SEO',
            'group' => 'content',
            'actions' => ['view', 'update'],
        ],

        // ─── System & Settings ─────────────────────────────────
        'branches' => [
            'label_en' => 'Branches',
            'label_ar' => 'الفروع',
            'group' => 'system',
            'actions' => ['view', 'manage'],
        ],
        'users' => [
            'label_en' => 'Users',
            'label_ar' => 'المستخدمين',
            'group' => 'system',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'roles' => [
            'label_en' => 'Roles & Permissions',
            'label_ar' => 'الأدوار والصلاحيات',
            'group' => 'system',
            'actions' => ['view', 'create', 'update', 'delete'],
        ],
        'settings' => [
            'label_en' => 'Settings',
            'label_ar' => 'الإعدادات',
            'group' => 'system',
            'actions' => ['view', 'update'],
        ],
        'contact_messages' => [
            'label_en' => 'Contact Messages',
            'label_ar' => 'رسائل التواصل',
            'group' => 'system',
            'actions' => ['view', 'delete'],
        ],
        'notifications' => [
            'label_en' => 'Notifications',
            'label_ar' => 'الإشعارات',
            'group' => 'system',
            'actions' => ['view', 'create', 'update', 'send'],
        ],
        'audit_logs' => [
            'label_en' => 'Audit Logs',
            'label_ar' => 'سجل النشاطات',
            'group' => 'system',
            'actions' => ['view'],
        ],
        'backups' => [
            'label_en' => 'Backups',
            'label_ar' => 'النسخ الاحتياطية',
            'group' => 'system',
            'actions' => ['view', 'create', 'delete'],
        ],
        'modules_management' => [
            'label_en' => 'Modules Management',
            'label_ar' => 'إدارة الوحدات',
            'group' => 'system',
            'actions' => ['view', 'update'],
        ],
    ],

];
