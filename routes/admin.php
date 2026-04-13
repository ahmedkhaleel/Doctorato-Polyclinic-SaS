<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\MedicationController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Admin\SupplyCategoryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\AdvanceController;
use App\Http\Controllers\Admin\PenaltyController;
use App\Http\Controllers\Admin\HrDashboardController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\DentalReportController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SeoPageController;
use App\Http\Controllers\Admin\TrackingController;
use App\Http\Controllers\Admin\PackageBundleController;
use App\Http\Controllers\Admin\PackageBundleBookingController;
use App\Http\Controllers\Admin\AdminDoctorPayoutController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\CrmDashboardController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\CrmCampaignController;
use App\Http\Controllers\Admin\LeadSourceController;
use App\Http\Controllers\Admin\CommunicationTemplateController;
use App\Http\Controllers\Admin\MarketerCommissionController;
use App\Http\Controllers\Admin\CrmReportController;
use App\Http\Controllers\Admin\CrmSettingsController;
use App\Http\Controllers\Admin\LeadScoringRuleController;
use App\Http\Controllers\Admin\FollowUpSequenceController;
use App\Http\Controllers\Admin\LeadAssignmentRuleController;
use App\Http\Controllers\Admin\ModuleSettingController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\InsuranceCompanyController;
use App\Http\Controllers\Admin\InsuranceClaimController;
use App\Http\Controllers\Admin\PatientVitalController;
use App\Http\Controllers\Admin\PatientDocumentController;
use App\Http\Controllers\Admin\PatientSatisfactionController;
use App\Http\Controllers\Admin\DentalDashboardController;
use App\Http\Controllers\Admin\DentalChartController;
use App\Http\Controllers\Admin\DentalTreatmentController;
use App\Http\Controllers\Admin\DentalTreatmentPlanController;
use App\Http\Controllers\Admin\DentalXrayController;
use App\Http\Controllers\Admin\DentalLabOrderController;
use App\Http\Controllers\Admin\DentalPrescriptionTemplateController;
use App\Http\Controllers\Admin\PeriodontalChartController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\InsuranceReportController;
use App\Http\Controllers\Admin\AppointmentReminderController;
use App\Http\Controllers\Admin\StaffPerformanceController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\MedicalCertificateController;
use App\Http\Controllers\Admin\DoctorKpiController;
use App\Http\Controllers\Admin\QueueAnalyticsController;
use App\Http\Controllers\Admin\RevenueAnalyticsController;
use App\Http\Controllers\Admin\PatientWalletController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\NotificationCenterController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\TrashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes handle all administration panel functionality for the
| AURA Derma Clinic website. All routes are prefixed with 'admin'.
| Each route is protected by granular per-action permissions.
|
*/

// Admin authentication routes (no auth middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/login', [AuthController::class, 'login'])->name('admin.authenticate')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
Route::post('/switch-locale-public', [AuthController::class, 'switchLocale'])->name('admin.switchLocalePublic');

// Protected admin routes (requires authentication + active account)
Route::middleware('admin.auth')->group(function () {

    // Dashboard (all authenticated users)
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // ─── Locale Switching ─────────────────────────────────────
    Route::post('/switch-locale', [SettingController::class, 'switchLocale'])->name('admin.switchLocale');

    // ─── Notifications ───────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('admin.notifications.markAllRead');
    Route::post('/notifications/{type}/{id}/read', [NotificationController::class, 'markRead'])->name('admin.notifications.markRead');

    // ─── Notification Center (Full Page) ──────────────────────
    Route::get('/notification-center', [NotificationCenterController::class, 'index'])->name('admin.notification-center.index');
    Route::post('/notification-center/mark-all-read', [NotificationCenterController::class, 'markAllRead'])->name('admin.notification-center.markAllRead');
    Route::delete('/notification-center/clear', [NotificationCenterController::class, 'destroyAll'])->name('admin.notification-center.clear');
    Route::post('/notification-center/{id}/read', [NotificationCenterController::class, 'markRead'])->name('admin.notification-center.markRead');
    Route::delete('/notification-center/{id}', [NotificationCenterController::class, 'destroy'])->name('admin.notification-center.destroy');

    // ─── Chat / Messaging ─────────────────────────────────────
    Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('admin.chat.unreadCount')->middleware('throttle:30,1');
    Route::get('/chat/{user}', [ChatController::class, 'show'])->name('admin.chat.show');
    Route::post('/chat/{user}', [ChatController::class, 'store'])->name('admin.chat.store')->middleware('throttle:30,1');
    Route::get('/chat/{user}/poll', [ChatController::class, 'poll'])->name('admin.chat.poll')->middleware('throttle:30,1');
    Route::post('/chat/{user}/mark-read', [ChatController::class, 'markRead'])->name('admin.chat.markRead')->middleware('throttle:30,1');
    Route::get('/chat/{user}/older', [ChatController::class, 'loadOlder'])->name('admin.chat.loadOlder')->middleware('throttle:30,1');
    Route::delete('/chat/{user}', [ChatController::class, 'destroy'])->name('admin.chat.destroy');

    // ─── Posts ──────────────────────────────────────────────
    Route::get('/posts', [PostController::class, 'index'])->name('admin.posts.index')->middleware('permission:posts.view');
    Route::get('/posts/create', [PostController::class, 'create'])->name('admin.posts.create')->middleware('permission:posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('admin.posts.store')->middleware('permission:posts.create');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('admin.posts.edit')->middleware('permission:posts.update');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('admin.posts.update')->middleware('permission:posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('admin.posts.destroy')->middleware('permission:posts.delete');

    // ─── Post Categories ───────────────────────────────────
    Route::get('/post-categories', [PostCategoryController::class, 'index'])->name('admin.post-categories.index')->middleware('permission:post_categories.view');
    Route::get('/post-categories/create', [PostCategoryController::class, 'create'])->name('admin.post-categories.create')->middleware('permission:post_categories.create');
    Route::post('/post-categories', [PostCategoryController::class, 'store'])->name('admin.post-categories.store')->middleware('permission:post_categories.create');
    Route::get('/post-categories/{post_category}/edit', [PostCategoryController::class, 'edit'])->name('admin.post-categories.edit')->middleware('permission:post_categories.update');
    Route::put('/post-categories/{post_category}', [PostCategoryController::class, 'update'])->name('admin.post-categories.update')->middleware('permission:post_categories.update');
    Route::delete('/post-categories/{post_category}', [PostCategoryController::class, 'destroy'])->name('admin.post-categories.destroy')->middleware('permission:post_categories.delete');

    // ─── Tags ──────────────────────────────────────────────
    Route::get('/tags', [TagController::class, 'index'])->name('admin.tags.index')->middleware('permission:tags.view');
    Route::get('/tags/create', [TagController::class, 'create'])->name('admin.tags.create')->middleware('permission:tags.create');
    Route::post('/tags', [TagController::class, 'store'])->name('admin.tags.store')->middleware('permission:tags.create');
    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('admin.tags.edit')->middleware('permission:tags.update');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('admin.tags.update')->middleware('permission:tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('admin.tags.destroy')->middleware('permission:tags.delete');

    // ─── Services ──────────────────────────────────────────
    Route::get('/services', [ServiceController::class, 'index'])->name('admin.services.index')->middleware('permission:services.view');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('admin.services.create')->middleware('permission:services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('admin.services.store')->middleware('permission:services.create');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('admin.services.show')->middleware('permission:services.view');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('admin.services.edit')->middleware('permission:services.update');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('admin.services.update')->middleware('permission:services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('admin.services.destroy')->middleware('permission:services.delete');

    // ─── Service Categories ────────────────────────────────
    Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('admin.service-categories.index')->middleware('permission:service_categories.view');
    Route::get('/service-categories/create', [ServiceCategoryController::class, 'create'])->name('admin.service-categories.create')->middleware('permission:service_categories.create');
    Route::post('/service-categories', [ServiceCategoryController::class, 'store'])->name('admin.service-categories.store')->middleware('permission:service_categories.create');
    Route::get('/service-categories/{service_category}', [ServiceCategoryController::class, 'show'])->name('admin.service-categories.show')->middleware('permission:service_categories.view');
    Route::get('/service-categories/{service_category}/edit', [ServiceCategoryController::class, 'edit'])->name('admin.service-categories.edit')->middleware('permission:service_categories.update');
    Route::put('/service-categories/{service_category}', [ServiceCategoryController::class, 'update'])->name('admin.service-categories.update')->middleware('permission:service_categories.update');
    Route::delete('/service-categories/{service_category}', [ServiceCategoryController::class, 'destroy'])->name('admin.service-categories.destroy')->middleware('permission:service_categories.delete');

    // ─── Doctors ───────────────────────────────────────────
    Route::get('/doctors', [DoctorController::class, 'index'])->name('admin.doctors.index')->middleware('permission:doctors.view');
    Route::get('/doctors/create', [DoctorController::class, 'create'])->name('admin.doctors.create')->middleware('permission:doctors.create');
    Route::post('/doctors', [DoctorController::class, 'store'])->name('admin.doctors.store')->middleware('permission:doctors.create');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('admin.doctors.show')->middleware('permission:doctors.view');
    Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('admin.doctors.edit')->middleware('permission:doctors.update');
    Route::post('/doctors/{doctor}', [DoctorController::class, 'update'])->name('admin.doctors.update')->middleware('permission:doctors.update');
    Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('admin.doctors.destroy')->middleware('permission:doctors.delete');
    Route::post('/doctors/{doctor}/create-user', [DoctorController::class, 'createUserAccount'])->name('admin.doctors.createUser')->middleware('permission:doctors.update');

    // ─── Gallery ───────────────────────────────────────────
    Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index')->middleware('permission:gallery.view');
    Route::get('/gallery/create', [GalleryController::class, 'create'])->name('admin.gallery.create')->middleware('permission:gallery.create');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store')->middleware('permission:gallery.create');
    Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('admin.gallery.edit')->middleware('permission:gallery.update');
    Route::put('/gallery/{gallery}', [GalleryController::class, 'update'])->name('admin.gallery.update')->middleware('permission:gallery.update');
    Route::delete('/gallery/{gallery}', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy')->middleware('permission:gallery.delete');

    // ─── Testimonials ──────────────────────────────────────
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('admin.testimonials.index')->middleware('permission:testimonials.view');
    Route::get('/testimonials/create', [TestimonialController::class, 'create'])->name('admin.testimonials.create')->middleware('permission:testimonials.create');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->name('admin.testimonials.store')->middleware('permission:testimonials.create');
    Route::get('/testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])->name('admin.testimonials.edit')->middleware('permission:testimonials.update');
    Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('admin.testimonials.update')->middleware('permission:testimonials.update');
    Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('admin.testimonials.destroy')->middleware('permission:testimonials.delete');

    // ─── FAQs ──────────────────────────────────────────────
    Route::get('/faqs', [FaqController::class, 'index'])->name('admin.faqs.index')->middleware('permission:faqs.view');
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('admin.faqs.create')->middleware('permission:faqs.create');
    Route::post('/faqs', [FaqController::class, 'store'])->name('admin.faqs.store')->middleware('permission:faqs.create');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('admin.faqs.edit')->middleware('permission:faqs.update');
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('admin.faqs.update')->middleware('permission:faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('admin.faqs.destroy')->middleware('permission:faqs.delete');

    // ─── Pages (view + update only) ────────────────────────
    Route::get('/pages', [PageController::class, 'index'])->name('admin.pages.index')->middleware('permission:pages.view');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.pages.edit')->middleware('permission:pages.update');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('admin.pages.update')->middleware('permission:pages.update');

    // ─── Bookings (full CRUD + workflow) ─────────────────────
    Route::get('/bookings', [BookingController::class, 'index'])->name('admin.bookings.index')->middleware('permission:bookings.view');
    Route::get('/bookings/export', [BookingController::class, 'export'])->name('admin.bookings.export')->middleware(['permission:bookings.export', 'throttle:10,1']);
    Route::get('/bookings/check-followup', [BookingController::class, 'checkFollowUp'])->name('admin.bookings.checkFollowUp')->middleware('permission:bookings.view');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('admin.bookings.create')->middleware('permission:bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('admin.bookings.store')->middleware('permission:bookings.create');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('admin.bookings.show')->middleware('permission:bookings.view');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('admin.bookings.update')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('admin.bookings.confirm')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/payment', [BookingController::class, 'processPayment'])->name('admin.bookings.payment')->middleware('permission:bookings.update');
    Route::get('/bookings/{booking}/receipt', [BookingController::class, 'printReceipt'])->name('admin.bookings.receipt')->middleware('permission:bookings.view');
    Route::get('/bookings/{booking}/payments/{payment}/receipt', [BookingController::class, 'printPaymentReceipt'])->name('admin.bookings.paymentReceipt')->middleware('permission:bookings.view');
    Route::post('/bookings/{booking}/retouch', [BookingController::class, 'addRetouchSession'])->name('admin.bookings.retouch')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/consents', [BookingController::class, 'uploadConsent'])->name('admin.bookings.uploadConsent')->middleware('permission:bookings.update');
    Route::delete('/bookings/{booking}/consents/{consent}', [BookingController::class, 'deleteConsent'])->name('admin.bookings.deleteConsent')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/appointments/{appointment}/check-in', [BookingController::class, 'checkInAppointment'])->name('admin.bookings.checkInAppointment')->middleware('permission:bookings.update');
    Route::put('/bookings/{booking}/appointments/{appointment}/reschedule', [BookingController::class, 'rescheduleAppointment'])->name('admin.bookings.rescheduleAppointment')->middleware('permission:bookings.update');
    Route::put('/bookings/{booking}/services', [BookingController::class, 'updateServices'])->name('admin.bookings.updateServices')->middleware('permission:bookings.edit_services');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('admin.bookings.destroy')->middleware('permission:bookings.delete');

    // ─── Contact Messages (view + delete) ──────────────────
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('admin.contact-messages.index')->middleware('permission:contact_messages.view');
    Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('admin.contact-messages.show')->middleware('permission:contact_messages.view');
    Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('admin.contact-messages.destroy')->middleware('permission:contact_messages.delete');

    // ─── Users ─────────────────────────────────────────────
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index')->middleware('permission:users.view');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create')->middleware('permission:users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store')->middleware('permission:users.create');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit')->middleware('permission:users.update');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update')->middleware('permission:users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy')->middleware('permission:users.delete');

    // ─── Roles & Permissions ───────────────────────────────
    Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index')->middleware('permission:roles.view');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('admin.roles.create')->middleware('permission:roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store')->middleware('permission:roles.create');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.roles.edit')->middleware('permission:roles.update');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update')->middleware('permission:roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy')->middleware('permission:roles.delete');

    // ─── Settings (view + update) ──────────────────────────
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index')->middleware('permission:settings.view');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update')->middleware('permission:settings.update');
    Route::post('/settings/test-sms', [SettingController::class, 'testSms'])->name('admin.settings.testSms')->middleware('permission:settings.update');

    // ═══════════════════════════════════════════════════════
    // Backups (Super Admin only)
    // ═══════════════════════════════════════════════════════
    Route::middleware('permission:settings.update')->group(function () {
        Route::get('/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/backups/run-full', [\App\Http\Controllers\Admin\BackupController::class, 'runFull'])->name('admin.backups.runFull')->middleware('throttle:3,10');
        Route::post('/backups/run-database', [\App\Http\Controllers\Admin\BackupController::class, 'runDatabase'])->name('admin.backups.runDatabase')->middleware('throttle:5,10');
        Route::post('/backups/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backups.download')->middleware('throttle:10,1');
        Route::post('/backups/cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanup'])->name('admin.backups.cleanup')->middleware('throttle:3,10');
        Route::delete('/backups', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy')->middleware('throttle:10,1');
    });

    // ─── Trash / Recycle Bin (سلة المحذوفات) ───────────────────
    Route::middleware('permission:settings.update')->group(function () {
        Route::get('/trash', [TrashController::class, 'index'])->name('admin.trash.index');
        Route::post('/trash/{type}/restore-all', [TrashController::class, 'restoreAll'])->name('admin.trash.restoreAll');
        Route::delete('/trash/{type}/empty', [TrashController::class, 'emptyTrash'])->name('admin.trash.empty');
        Route::post('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])->name('admin.trash.restore');
        Route::delete('/trash/{type}/{id}', [TrashController::class, 'forceDelete'])->name('admin.trash.forceDelete');
    });

    // ═══════════════════════════════════════════════════════════
    // ═══ CLINIC MANAGEMENT SYSTEM ROUTES ══════════════════════
    // ═══════════════════════════════════════════════════════════

    // ─── Patients ────────────────────────────────────────────
    Route::get('/patients', [PatientController::class, 'index'])->name('admin.patients.index')->middleware('permission:patients.view');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('admin.patients.create')->middleware('permission:patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('admin.patients.store')->middleware('permission:patients.create');
    Route::post('/patients/quick-create', [PatientController::class, 'quickStore'])->name('admin.patients.quickStore')->middleware('permission:patients.create');
    Route::get('/patients/search', [PatientController::class, 'search'])->name('admin.patients.search');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('admin.patients.show')->middleware('permission:patients.view');
    Route::get('/patients/{patient}/timeline', [PatientController::class, 'timeline'])->name('admin.patients.timeline')->middleware('permission:patients.view');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('admin.patients.edit')->middleware('permission:patients.update');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('admin.patients.update')->middleware('permission:patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('admin.patients.destroy')->middleware('permission:patients.delete');
    Route::put('/patients/{patient}/dental-medical', [PatientController::class, 'updateDentalMedical'])->name('admin.patients.updateDentalMedical')->middleware(['permission:patients.update', 'module:dental']);

    // ─── Visits ──────────────────────────────────────────────
    Route::get('/visits', [VisitController::class, 'index'])->name('admin.visits.index')->middleware('permission:visits.view');
    Route::get('/visits/today-queue', [VisitController::class, 'todayQueue'])->name('admin.visits.todayQueue')->middleware('permission:visits.view');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('admin.visits.show')->middleware('permission:visits.view');
    Route::post('/visits/{visit}/start', [VisitController::class, 'start'])->name('admin.visits.start')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/complete', [VisitController::class, 'complete'])->name('admin.visits.complete')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/cancel', [VisitController::class, 'cancel'])->name('admin.visits.cancel')->middleware('permission:visits.update');
    Route::put('/visits/{visit}/diagnosis', [VisitController::class, 'updateDiagnosis'])->name('admin.visits.updateDiagnosis')->middleware('permission:visits.update');
    Route::put('/visits/{visit}/details', [VisitController::class, 'updateDetails'])->name('admin.visits.updateDetails')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/photos', [VisitController::class, 'uploadPhoto'])->name('admin.visits.uploadPhoto')->middleware('permission:visits.update');

    // ─── Prescriptions ───────────────────────────────────────
    Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('admin.prescriptions.index')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('admin.prescriptions.show')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('admin.prescriptions.print')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'downloadPdf'])->name('admin.prescriptions.pdf')->middleware('permission:prescriptions.view');
    Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('admin.prescriptions.store')->middleware('permission:prescriptions.create');
    Route::put('/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->name('admin.prescriptions.update')->middleware('permission:prescriptions.update');
    Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->name('admin.prescriptions.destroy')->middleware('permission:prescriptions.delete');

    // ─── Medications ────────────────────────────────────────
    Route::get('/medications', [MedicationController::class, 'index'])->name('admin.medications.index')->middleware('permission:medications.view');
    Route::post('/medications', [MedicationController::class, 'store'])->name('admin.medications.store')->middleware('permission:medications.create');
    Route::post('/medications/{medication}/update', [MedicationController::class, 'update'])->name('admin.medications.update')->middleware('permission:medications.update');
    Route::post('/medications/{medication}/delete', [MedicationController::class, 'destroy'])->name('admin.medications.destroy')->middleware('permission:medications.delete');
    Route::get('/api/medications', [MedicationController::class, 'search'])->name('admin.medications.search')->middleware(['permission:medications.view', 'throttle:30,1']);

    // ─── Global Search ─────────────────────────────────────
    Route::get('/api/global-search', GlobalSearchController::class)->name('admin.global-search')->middleware('throttle:60,1');

    // ─── Invoices ────────────────────────────────────────────
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('admin.invoices.index')->middleware('permission:invoices.view');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('admin.invoices.create')->middleware('permission:invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store')->middleware('permission:invoices.create');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('admin.invoices.show')->middleware('permission:invoices.view');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('admin.invoices.update')->middleware('permission:invoices.update');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('admin.invoices.print')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('admin.invoices.pdf')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/payments/{payment}/receipt', [InvoiceController::class, 'printPaymentReceipt'])->name('admin.invoices.paymentReceipt')->middleware('permission:invoices.view');

    // ─── Payments ────────────────────────────────────────────
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index')->middleware('permission:payments.view');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store')->middleware('permission:payments.create');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('admin.payments.destroy')->middleware('permission:payments.delete');

    // ─── Payment Methods ─────────────────────────────────────
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('admin.payment-methods.index')->middleware('permission:payments.view');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('admin.payment-methods.store')->middleware('permission:payments.create');
    Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('admin.payment-methods.update')->middleware('permission:payments.create');

    // ─── Discount Codes ──────────────────────────────────────
    Route::get('/discount-codes', [DiscountCodeController::class, 'index'])->name('admin.discount-codes.index')->middleware('permission:discount_codes.view');
    Route::get('/discount-codes/create', [DiscountCodeController::class, 'create'])->name('admin.discount-codes.create')->middleware('permission:discount_codes.create');
    Route::post('/discount-codes', [DiscountCodeController::class, 'store'])->name('admin.discount-codes.store')->middleware('permission:discount_codes.create');
    Route::get('/discount-codes/{discountCode}/edit', [DiscountCodeController::class, 'edit'])->name('admin.discount-codes.edit')->middleware('permission:discount_codes.update');
    Route::post('/discount-codes/{discountCode}', [DiscountCodeController::class, 'update'])->name('admin.discount-codes.update')->middleware('permission:discount_codes.update');
    Route::delete('/discount-codes/{discountCode}', [DiscountCodeController::class, 'destroy'])->name('admin.discount-codes.destroy')->middleware('permission:discount_codes.delete');
    Route::post('/api/discount-codes/validate', [DiscountCodeController::class, 'validateCode'])->name('admin.discount-codes.validate')->middleware(['permission:discount_codes.view', 'throttle:30,1']);

    // ─── Expense Categories ──────────────────────────────────
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories.index')->middleware('permission:expenses.view');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store')->middleware('permission:expenses.create');
    Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('admin.expense-categories.update')->middleware('permission:expenses.update');
    Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('admin.expense-categories.destroy')->middleware('permission:expenses.delete');

    // ─── Expenses ────────────────────────────────────────────
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index')->middleware('permission:expenses.view');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('admin.expenses.create')->middleware('permission:expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store')->middleware('permission:expenses.create');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('admin.expenses.edit')->middleware('permission:expenses.update');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('admin.expenses.update')->middleware('permission:expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy')->middleware('permission:expenses.delete');

    // ─── Doctor Payouts ──────────────────────────────────────
    Route::get('/doctor-payouts', [AdminDoctorPayoutController::class, 'index'])->name('admin.doctor-payouts.index')->middleware(['module:hr', 'permission:doctor_payouts.view']);
    Route::get('/doctor-payouts/create', [AdminDoctorPayoutController::class, 'create'])->name('admin.doctor-payouts.create')->middleware(['module:hr', 'permission:doctor_payouts.create']);
    Route::post('/doctor-payouts', [AdminDoctorPayoutController::class, 'store'])->name('admin.doctor-payouts.store')->middleware(['module:hr', 'permission:doctor_payouts.create']);
    Route::get('/doctor-payouts/{payout}', [AdminDoctorPayoutController::class, 'show'])->name('admin.doctor-payouts.show')->middleware(['module:hr', 'permission:doctor_payouts.view']);
    Route::put('/doctor-payouts/{payout}/confirm', [AdminDoctorPayoutController::class, 'confirm'])->name('admin.doctor-payouts.confirm')->middleware(['module:hr', 'permission:doctor_payouts.update']);
    Route::put('/doctor-payouts/{payout}/mark-paid', [AdminDoctorPayoutController::class, 'markPaid'])->name('admin.doctor-payouts.mark-paid')->middleware(['module:hr', 'permission:doctor_payouts.update']);
    Route::put('/doctor-payouts/{payout}/cancel', [AdminDoctorPayoutController::class, 'cancel'])->name('admin.doctor-payouts.cancel')->middleware(['module:hr', 'permission:doctor_payouts.update']);
    Route::get('/doctor-payouts/{payout}/print', [AdminDoctorPayoutController::class, 'printReceipt'])->name('admin.doctor-payouts.print')->middleware(['module:hr', 'permission:doctor_payouts.view']);
    Route::get('/api/doctor-unpaid-visits', [AdminDoctorPayoutController::class, 'getUnpaidVisits'])->name('admin.doctor-payouts.unpaid-visits')->middleware(['module:hr', 'permission:doctor_payouts.create']);

    // ─── Inventory Module ────────────────────────────────────────
    Route::middleware('module:inventory')->group(function () {
        Route::get('/inventory', [SupplyController::class, 'dashboard'])->name('admin.inventory.dashboard')->middleware('permission:supplies.view');

        // Supply Categories
        Route::get('/supply-categories', [SupplyCategoryController::class, 'index'])->name('admin.supply-categories.index')->middleware('permission:supplies.view');
        Route::post('/supply-categories', [SupplyCategoryController::class, 'store'])->name('admin.supply-categories.store')->middleware('permission:supplies.create');
        Route::post('/supply-categories/{category}/update', [SupplyCategoryController::class, 'update'])->name('admin.supply-categories.update')->middleware('permission:supplies.update');
        Route::post('/supply-categories/{category}/delete', [SupplyCategoryController::class, 'destroy'])->name('admin.supply-categories.destroy')->middleware('permission:supplies.delete');

        // Supplies
        Route::get('/supplies', [SupplyController::class, 'index'])->name('admin.supplies.index')->middleware('permission:supplies.view');
        Route::get('/supplies/create', [SupplyController::class, 'create'])->name('admin.supplies.create')->middleware('permission:supplies.create');
        Route::post('/supplies', [SupplyController::class, 'store'])->name('admin.supplies.store')->middleware('permission:supplies.create');
        Route::get('/supplies/{supply}', [SupplyController::class, 'show'])->name('admin.supplies.show')->middleware('permission:supplies.view');
        Route::get('/supplies/{supply}/edit', [SupplyController::class, 'edit'])->name('admin.supplies.edit')->middleware('permission:supplies.update');
        Route::post('/supplies/{supply}/update', [SupplyController::class, 'update'])->name('admin.supplies.update')->middleware('permission:supplies.update');
        Route::post('/supplies/{supply}/delete', [SupplyController::class, 'destroy'])->name('admin.supplies.destroy')->middleware('permission:supplies.delete');
        Route::get('/supplies/{supply}/transactions', [SupplyController::class, 'transactions'])->name('admin.supplies.transactions')->middleware('permission:supplies.view');
        Route::post('/supplies/{supply}/transactions', [SupplyController::class, 'addTransaction'])->name('admin.supplies.addTransaction')->middleware('permission:supplies.update');
    });

    // ─── HR Module ──────────────────────────────────────────────
    Route::middleware('module:hr')->group(function () {
        // HR Dashboard
        Route::get('/hr-dashboard', [HrDashboardController::class, 'index'])->name('admin.hr-dashboard')->middleware('permission:employees.view');

        // Departments
        Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments.index')->middleware('permission:departments.view');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store')->middleware('permission:departments.create');
        Route::post('/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update')->middleware('permission:departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy')->middleware('permission:departments.delete');

        // Employees
        Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees.index')->middleware('permission:employees.view');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('admin.employees.create')->middleware('permission:employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store')->middleware('permission:employees.create');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('admin.employees.show')->middleware('permission:employees.view');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit')->middleware('permission:employees.update');
        Route::post('/employees/{employee}', [EmployeeController::class, 'update'])->name('admin.employees.update')->middleware('permission:employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy')->middleware('permission:employees.delete');

        // Payroll
        Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index')->middleware('permission:salary_slips.view');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('admin.payroll.create')->middleware('permission:salary_slips.create');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('admin.payroll.generate')->middleware('permission:salary_slips.create');
        Route::get('/payroll/{salarySlip}', [PayrollController::class, 'show'])->name('admin.payroll.show')->middleware('permission:salary_slips.view');
        Route::post('/payroll/{salarySlip}/approve', [PayrollController::class, 'approve'])->name('admin.payroll.approve')->middleware('permission:salary_slips.update');
        Route::post('/payroll/{salarySlip}/mark-paid', [PayrollController::class, 'markPaid'])->name('admin.payroll.mark-paid')->middleware('permission:salary_slips.update');
        Route::post('/payroll/bulk-approve', [PayrollController::class, 'bulkApprove'])->name('admin.payroll.bulk-approve')->middleware(['permission:salary_slips.update', 'throttle:10,1']);
        Route::post('/payroll/bulk-mark-paid', [PayrollController::class, 'bulkMarkPaid'])->name('admin.payroll.bulk-mark-paid')->middleware(['permission:salary_slips.update', 'throttle:10,1']);
        Route::get('/payroll/{salarySlip}/print', [PayrollController::class, 'print'])->name('admin.payroll.print')->middleware('permission:salary_slips.view');

        // Advances
        Route::get('/advances', [AdvanceController::class, 'index'])->name('admin.advances.index')->middleware('permission:advances.view');
        Route::post('/advances', [AdvanceController::class, 'store'])->name('admin.advances.store')->middleware('permission:advances.create');
        Route::post('/advances/{advance}/approve', [AdvanceController::class, 'approve'])->name('admin.advances.approve')->middleware('permission:advances.update');
        Route::post('/advances/{advance}/reject', [AdvanceController::class, 'reject'])->name('admin.advances.reject')->middleware('permission:advances.update');

        // Penalties & Rewards
        Route::get('/penalties', [PenaltyController::class, 'index'])->name('admin.penalties.index')->middleware('permission:penalties.view');
        Route::post('/penalties', [PenaltyController::class, 'store'])->name('admin.penalties.store')->middleware('permission:penalties.create');
        Route::delete('/penalties/{penalty}', [PenaltyController::class, 'destroy'])->name('admin.penalties.destroy')->middleware('permission:penalties.delete');

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'index'])->name('admin.shifts.index')->middleware('permission:shifts.view');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('admin.shifts.store')->middleware('permission:shifts.create');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('admin.shifts.update')->middleware('permission:shifts.update');
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('admin.shifts.destroy')->middleware('permission:shifts.delete');

        // Attendance
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('admin.attendances.index')->middleware('permission:attendances.view');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('admin.attendances.store')->middleware('permission:attendances.create');
        Route::put('/attendances/{attendance}', [AttendanceController::class, 'update'])->name('admin.attendances.update')->middleware('permission:attendances.update');
        Route::post('/attendances/quick-check-in', [AttendanceController::class, 'quickCheckIn'])->name('admin.attendances.quick-check-in')->middleware('permission:attendances.create');
        Route::post('/attendances/quick-check-out', [AttendanceController::class, 'quickCheckOut'])->name('admin.attendances.quick-check-out')->middleware('permission:attendances.create');
        Route::delete('/attendances/{attendance}', [AttendanceController::class, 'destroy'])->name('admin.attendances.destroy')->middleware('permission:attendances.delete');

        // Leaves
        Route::get('/leaves', [LeaveController::class, 'index'])->name('admin.leaves.index')->middleware('permission:leaves.view');
        Route::get('/leaves/create', [LeaveController::class, 'create'])->name('admin.leaves.create')->middleware('permission:leaves.create');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('admin.leaves.store')->middleware('permission:leaves.create');
        Route::put('/leaves/{leave}', [LeaveController::class, 'update'])->name('admin.leaves.update')->middleware('permission:leaves.update');
    });

    // ─── Package Bundles ────────────────────────────────────────
    Route::get('/package-bundles', [PackageBundleController::class, 'index'])->name('admin.package-bundles.index')->middleware('permission:package_bundles.view');
    Route::get('/package-bundles/create', [PackageBundleController::class, 'create'])->name('admin.package-bundles.create')->middleware('permission:package_bundles.create');
    Route::post('/package-bundles', [PackageBundleController::class, 'store'])->name('admin.package-bundles.store')->middleware('permission:package_bundles.create');
    Route::get('/package-bundles/{packageBundle}', [PackageBundleController::class, 'show'])->name('admin.package-bundles.show')->middleware('permission:package_bundles.view');
    Route::get('/package-bundles/{packageBundle}/edit', [PackageBundleController::class, 'edit'])->name('admin.package-bundles.edit')->middleware('permission:package_bundles.update');
    Route::post('/package-bundles/{packageBundle}/update', [PackageBundleController::class, 'update'])->name('admin.package-bundles.update')->middleware('permission:package_bundles.update');
    Route::delete('/package-bundles/{packageBundle}', [PackageBundleController::class, 'destroy'])->name('admin.package-bundles.destroy')->middleware('permission:package_bundles.delete');
    Route::post('/package-bundles/{packageBundle}/toggle-active', [PackageBundleController::class, 'toggleActive'])->name('admin.package-bundles.toggleActive')->middleware('permission:package_bundles.update');

    // ─── Package Bundle Bookings ─────────────────────────────────
    Route::get('/package-bundle-bookings', [PackageBundleBookingController::class, 'index'])->name('admin.package-bundle-bookings.index')->middleware('permission:package_bundle_bookings.view');
    Route::get('/package-bundle-bookings/create', [PackageBundleBookingController::class, 'create'])->name('admin.package-bundle-bookings.create')->middleware('permission:package_bundle_bookings.create');
    Route::post('/package-bundle-bookings', [PackageBundleBookingController::class, 'store'])->name('admin.package-bundle-bookings.store')->middleware('permission:package_bundle_bookings.create');
    Route::get('/package-bundle-bookings/{bundleBooking}', [PackageBundleBookingController::class, 'show'])->name('admin.package-bundle-bookings.show')->middleware('permission:package_bundle_bookings.view');
    Route::post('/package-bundle-bookings/{bundleBooking}/process-payment', [PackageBundleBookingController::class, 'processPayment'])->name('admin.package-bundle-bookings.processPayment')->middleware('permission:package_bundle_bookings.process_payment');
    Route::post('/package-bundle-bookings/{bundleBooking}/cancel', [PackageBundleBookingController::class, 'cancel'])->name('admin.package-bundle-bookings.cancel')->middleware('permission:package_bundle_bookings.cancel');
    Route::get('/package-bundle-bookings/{bundleBooking}/print-receipt', [PackageBundleBookingController::class, 'printReceipt'])->name('admin.package-bundle-bookings.printReceipt')->middleware('permission:package_bundle_bookings.view');
    Route::post('/package-bundle-bookings/{bundleBooking}/appointments/{appointment}/check-in', [PackageBundleBookingController::class, 'checkInAppointment'])->name('admin.package-bundle-bookings.checkInAppointment')->middleware('permission:package_bundle_bookings.update');
    Route::post('/package-bundle-bookings/{bundleBooking}/appointments/{appointment}/reschedule', [PackageBundleBookingController::class, 'rescheduleAppointment'])->name('admin.package-bundle-bookings.rescheduleAppointment')->middleware('permission:package_bundle_bookings.update');
    Route::post('/package-bundle-bookings/{bundleBooking}/retouch', [PackageBundleBookingController::class, 'addRetouchSession'])->name('admin.package-bundle-bookings.retouch')->middleware('permission:package_bundle_bookings.update');

    // ─── Reports ─────────────────────────────────────────────
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index')->middleware('permission:reports.view');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('admin.reports.financial')->middleware('permission:reports.view');
    Route::get('/reports/revenue-analytics', [RevenueAnalyticsController::class, 'index'])->name('admin.reports.revenue-analytics')->middleware('permission:reports.view');
    Route::get('/reports/queue-analytics', [QueueAnalyticsController::class, 'index'])->name('admin.reports.queue-analytics')->middleware('permission:reports.view');
    Route::get('/reports/doctor-kpi', [DoctorKpiController::class, 'index'])->name('admin.reports.doctor-kpi')->middleware(['module:hr', 'permission:reports.view']);
    Route::get('/reports/staff-performance', [StaffPerformanceController::class, 'index'])->name('admin.reports.staff-performance')->middleware(['module:hr', 'permission:reports.view']);
    Route::get('/reports/doctors', [ReportController::class, 'doctors'])->name('admin.reports.doctors')->middleware('permission:reports.view');
    Route::get('/reports/patients', [ReportController::class, 'patients'])->name('admin.reports.patients')->middleware('permission:reports.view');
    Route::get('/reports/services', [ReportController::class, 'services'])->name('admin.reports.services')->middleware('permission:reports.view');
    Route::get('/reports/dental', [DentalReportController::class, 'index'])->name('admin.reports.dental')->middleware('permission:reports.view', 'module:dental');
    Route::get('/reports/dental/chart-pdf/{patient}', [DentalReportController::class, 'chartPdf'])->name('admin.reports.dental.chartPdf')->middleware('permission:reports.view', 'module:dental');

    // ─── Excel Exports ────────────────────────────────────────
    Route::get('/exports/patients', [ExportController::class, 'patients'])->name('admin.exports.patients')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/invoices', [ExportController::class, 'invoices'])->name('admin.exports.invoices')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/payments', [ExportController::class, 'payments'])->name('admin.exports.payments')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/visits', [ExportController::class, 'visits'])->name('admin.exports.visits')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/commissions', [ExportController::class, 'commissions'])->name('admin.exports.commissions')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/activity-logs', [ExportController::class, 'activityLogs'])->name('admin.exports.activityLogs')->middleware(['permission:reports.view', 'throttle:10,1']);
    Route::get('/exports/employees', [ExportController::class, 'employees'])->name('admin.exports.employees')->middleware(['module:hr', 'permission:employees.view', 'throttle:10,1']);
    Route::get('/exports/salary-slips', [ExportController::class, 'salarySlips'])->name('admin.exports.salarySlips')->middleware(['module:hr', 'permission:salary_slips.view', 'throttle:10,1']);
    Route::get('/exports/dental-treatments', [ExportController::class, 'dentalTreatments'])->name('admin.exports.dentalTreatments')->middleware(['module:dental', 'permission:dental.view', 'throttle:10,1']);
    Route::get('/exports/dental-lab-orders', [ExportController::class, 'dentalLabOrders'])->name('admin.exports.dentalLabOrders')->middleware(['module:dental', 'permission:dental.view', 'throttle:10,1']);
    Route::get('/exports/dental-treatment-plans', [ExportController::class, 'dentalTreatmentPlans'])->name('admin.exports.dentalTreatmentPlans')->middleware(['module:dental', 'permission:dental.view', 'throttle:10,1']);
    Route::get('/exports/dental-followups', [ExportController::class, 'dentalFollowups'])->name('admin.exports.dentalFollowups')->middleware(['module:dental', 'permission:dental.view', 'throttle:10,1']);
    Route::get('/exports/supplies', [ExportController::class, 'supplies'])->name('admin.exports.supplies')->middleware(['permission:supplies.view', 'throttle:10,1']);

    // ─── Activity Logs ────────────────────────────────────────
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity-logs.index')->middleware('permission:reports.view');

    // ─── Medical Data Access Logs (HIPAA Compliance) ─────────
    Route::get('/medical-access-logs', [\App\Http\Controllers\Admin\MedicalAccessLogController::class, 'index'])
        ->name('admin.medical-access-logs.index')
        ->middleware('permission:patients.view_sensitive_medical');

    // ─── Calendar ─────────────────────────────────────────────
    Route::get('/calendar', [CalendarController::class, 'index'])->name('admin.calendar.index')->middleware('permission:visits.view');

    // ─── Hero Slider ──────────────────────────────────────────
    Route::get('/slider', [SliderController::class, 'index'])->name('admin.slider.index')->middleware('permission:settings.view');
    Route::get('/slider/create', [SliderController::class, 'create'])->name('admin.slider.create')->middleware('permission:settings.update');
    Route::post('/slider', [SliderController::class, 'store'])->name('admin.slider.store')->middleware('permission:settings.update');
    Route::get('/slider/{heroSlide}/edit', [SliderController::class, 'edit'])->name('admin.slider.edit')->middleware('permission:settings.update');
    Route::put('/slider/{heroSlide}', [SliderController::class, 'update'])->name('admin.slider.update')->middleware('permission:settings.update');
    Route::delete('/slider/{heroSlide}', [SliderController::class, 'destroy'])->name('admin.slider.destroy')->middleware('permission:settings.update');
    Route::post('/slider/update-order', [SliderController::class, 'updateOrder'])->name('admin.slider.updateOrder')->middleware('permission:settings.update');

    // ─── SEO Pages ───────────────────────────────────────────
    Route::get('/seo-pages', [SeoPageController::class, 'index'])->name('admin.seoPages.index')->middleware('permission:settings.view');
    Route::get('/seo-pages/{seoPage}/edit', [SeoPageController::class, 'edit'])->name('admin.seoPages.edit')->middleware('permission:settings.view');
    Route::put('/seo-pages/{seoPage}', [SeoPageController::class, 'update'])->name('admin.seoPages.update')->middleware('permission:settings.update');

    // ─── Tracking & Pixels ───────────────────────────────────
    Route::get('/tracking', [TrackingController::class, 'index'])->name('admin.tracking.index')->middleware('permission:settings.view');
    Route::post('/tracking', [TrackingController::class, 'update'])->name('admin.tracking.update')->middleware('permission:settings.update');

    // ─── CRM ────────────────────────────────────────────────
    Route::get('/crm', [CrmDashboardController::class, 'index'])->name('admin.crm.dashboard')->middleware('permission:leads.view');
    Route::get('/crm/calendar', [CrmDashboardController::class, 'calendar'])->name('admin.crm.calendar')->middleware('permission:leads.view');

    // Leads
    Route::get('/leads', [LeadController::class, 'index'])->name('admin.leads.index')->middleware('permission:leads.view');
    Route::get('/leads/pipeline', [LeadController::class, 'pipeline'])->name('admin.leads.pipeline')->middleware('permission:leads.view');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('admin.leads.create')->middleware('permission:leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('admin.leads.store')->middleware('permission:leads.create');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('admin.leads.show')->middleware('permission:leads.view');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('admin.leads.edit')->middleware('permission:leads.update');
    Route::post('/leads/{lead}', [LeadController::class, 'update'])->name('admin.leads.update')->middleware('permission:leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('admin.leads.destroy')->middleware('permission:leads.delete');
    Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('admin.leads.updateStatus')->middleware('permission:leads.update');
    Route::post('/leads/{lead}/priority', [LeadController::class, 'updatePriority'])->name('admin.leads.updatePriority')->middleware('permission:leads.update');
    Route::post('/leads/{lead}/reactivate', [LeadController::class, 'reactivate'])->name('admin.leads.reactivate')->middleware('permission:leads.update');
    Route::get('/leads/{lead}/quick-view', [LeadController::class, 'quickView'])->name('admin.leads.quickView')->middleware('permission:leads.view');
    Route::post('/leads/{lead}/activity', [LeadController::class, 'logActivity'])->name('admin.leads.logActivity')->middleware('permission:leads.update');
    Route::post('/leads/{lead}/follow-up', [LeadController::class, 'scheduleFollowUp'])->name('admin.leads.scheduleFollowUp')->middleware('permission:leads.update');
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('admin.leads.convert')->middleware('permission:leads.convert');
    Route::post('/leads/bulk-action', [LeadController::class, 'bulkAction'])->name('admin.leads.bulkAction')->middleware(['permission:leads.update', 'throttle:10,1']);
    Route::get('/leads-export', [LeadController::class, 'export'])->name('admin.leads.export')->middleware(['permission:leads.view', 'throttle:10,1']);
    Route::post('/leads/{lead}/quick-send', [LeadController::class, 'quickSend'])->name('admin.leads.quickSend')->middleware('permission:leads.update');
    Route::get('/leads-check-duplicate', [LeadController::class, 'checkDuplicate'])->name('admin.leads.checkDuplicate')->middleware('permission:leads.view');
    Route::get('/leads-import', [LeadController::class, 'importPage'])->name('admin.leads.importPage')->middleware('permission:leads.create');
    Route::post('/leads-import', [LeadController::class, 'importCsv'])->name('admin.leads.importCsv')->middleware('permission:leads.create');

    // Lead merge
    Route::get('/leads-merge', [LeadController::class, 'mergePage'])->name('admin.leads.mergePage')->middleware('permission:leads.update');
    Route::post('/leads-merge', [LeadController::class, 'merge'])->name('admin.leads.merge')->middleware('permission:leads.update');

    // Follow-up actions
    Route::post('/follow-ups/{followUp}/complete', [LeadController::class, 'completeFollowUp'])->name('admin.follow-ups.complete')->middleware('permission:leads.update');
    Route::post('/follow-ups/{followUp}/miss', [LeadController::class, 'missFollowUp'])->name('admin.follow-ups.miss')->middleware('permission:leads.update');
    Route::post('/follow-ups/{followUp}/reschedule', [LeadController::class, 'rescheduleFollowUp'])->name('admin.follow-ups.reschedule')->middleware('permission:leads.update');

    // Campaigns
    Route::get('/campaigns', [CrmCampaignController::class, 'index'])->name('admin.campaigns.index')->middleware('permission:crm_campaigns.view');
    Route::get('/campaigns/create', [CrmCampaignController::class, 'create'])->name('admin.campaigns.create')->middleware('permission:crm_campaigns.create');
    Route::post('/campaigns', [CrmCampaignController::class, 'store'])->name('admin.campaigns.store')->middleware('permission:crm_campaigns.create');
    Route::get('/campaigns/{campaign}', [CrmCampaignController::class, 'show'])->name('admin.campaigns.show')->middleware('permission:crm_campaigns.view');
    Route::get('/campaigns/{campaign}/edit', [CrmCampaignController::class, 'edit'])->name('admin.campaigns.edit')->middleware('permission:crm_campaigns.update');
    Route::post('/campaigns/{campaign}', [CrmCampaignController::class, 'update'])->name('admin.campaigns.update')->middleware('permission:crm_campaigns.update');
    Route::delete('/campaigns/{campaign}', [CrmCampaignController::class, 'destroy'])->name('admin.campaigns.destroy')->middleware('permission:crm_campaigns.delete');

    // Lead Sources
    Route::get('/lead-sources', [LeadSourceController::class, 'index'])->name('admin.lead-sources.index')->middleware('permission:lead_sources.view');
    Route::post('/lead-sources', [LeadSourceController::class, 'store'])->name('admin.lead-sources.store')->middleware('permission:lead_sources.create');
    Route::post('/lead-sources/{leadSource}', [LeadSourceController::class, 'update'])->name('admin.lead-sources.update')->middleware('permission:lead_sources.update');
    Route::delete('/lead-sources/{leadSource}', [LeadSourceController::class, 'destroy'])->name('admin.lead-sources.destroy')->middleware('permission:lead_sources.delete');

    // Communication Templates
    Route::get('/templates', [CommunicationTemplateController::class, 'index'])->name('admin.templates.index')->middleware('permission:communication_templates.view');
    Route::get('/templates/create', [CommunicationTemplateController::class, 'create'])->name('admin.templates.create')->middleware('permission:communication_templates.create');
    Route::post('/templates', [CommunicationTemplateController::class, 'store'])->name('admin.templates.store')->middleware('permission:communication_templates.create');
    Route::get('/templates/{template}/edit', [CommunicationTemplateController::class, 'edit'])->name('admin.templates.edit')->middleware('permission:communication_templates.update');
    Route::post('/templates/{template}', [CommunicationTemplateController::class, 'update'])->name('admin.templates.update')->middleware('permission:communication_templates.update');
    Route::delete('/templates/{template}', [CommunicationTemplateController::class, 'destroy'])->name('admin.templates.destroy')->middleware('permission:communication_templates.delete');

    // Marketer Commissions
    Route::get('/commissions', [MarketerCommissionController::class, 'index'])->name('admin.commissions.index')->middleware('permission:marketer_commissions.view');
    Route::get('/commissions/create', [MarketerCommissionController::class, 'create'])->name('admin.commissions.create')->middleware('permission:marketer_commissions.create');
    Route::post('/commissions', [MarketerCommissionController::class, 'store'])->name('admin.commissions.store')->middleware('permission:marketer_commissions.create');
    Route::post('/commissions/{commission}/approve', [MarketerCommissionController::class, 'approve'])->name('admin.commissions.approve')->middleware('permission:marketer_commissions.update');
    Route::post('/commissions/{commission}/mark-paid', [MarketerCommissionController::class, 'markPaid'])->name('admin.commissions.markPaid')->middleware('permission:marketer_commissions.update');
    Route::delete('/commissions/{commission}', [MarketerCommissionController::class, 'destroy'])->name('admin.commissions.destroy')->middleware('permission:marketer_commissions.delete');

    // Scoring Rules
    Route::get('/scoring-rules', [LeadScoringRuleController::class, 'index'])->name('admin.scoring-rules.index')->middleware('permission:leads.view');
    Route::post('/scoring-rules', [LeadScoringRuleController::class, 'store'])->name('admin.scoring-rules.store')->middleware('permission:leads.update');
    Route::post('/scoring-rules/{rule}', [LeadScoringRuleController::class, 'update'])->name('admin.scoring-rules.update')->middleware('permission:leads.update');
    Route::delete('/scoring-rules/{rule}', [LeadScoringRuleController::class, 'destroy'])->name('admin.scoring-rules.destroy')->middleware('permission:leads.update');

    // Assignment Rules
    Route::get('/assignment-rules', [LeadAssignmentRuleController::class, 'index'])->name('admin.assignment-rules.index')->middleware('permission:leads.view');
    Route::post('/assignment-rules', [LeadAssignmentRuleController::class, 'store'])->name('admin.assignment-rules.store')->middleware('permission:leads.update');
    Route::post('/assignment-rules/{rule}', [LeadAssignmentRuleController::class, 'update'])->name('admin.assignment-rules.update')->middleware('permission:leads.update');
    Route::delete('/assignment-rules/{rule}', [LeadAssignmentRuleController::class, 'destroy'])->name('admin.assignment-rules.destroy')->middleware('permission:leads.update');

    // CRM Reports
    Route::get('/crm-reports', [CrmReportController::class, 'index'])->name('admin.crm-reports.index')->middleware('permission:leads.view');

    // CRM Settings
    Route::get('/crm-settings', [CrmSettingsController::class, 'index'])->name('admin.crm-settings')->middleware('permission:settings.view');
    Route::post('/crm-settings', [CrmSettingsController::class, 'update'])->name('admin.crm-settings.update')->middleware('permission:settings.update');

    // Follow-up Automation Sequences
    Route::get('/sequences', [FollowUpSequenceController::class, 'index'])->name('admin.sequences.index')->middleware('permission:leads.view');
    Route::get('/sequences/create', [FollowUpSequenceController::class, 'create'])->name('admin.sequences.create')->middleware('permission:leads.create');
    Route::post('/sequences', [FollowUpSequenceController::class, 'store'])->name('admin.sequences.store')->middleware('permission:leads.create');
    Route::get('/sequences/{sequence}/edit', [FollowUpSequenceController::class, 'edit'])->name('admin.sequences.edit')->middleware('permission:leads.update');
    Route::post('/sequences/{sequence}', [FollowUpSequenceController::class, 'update'])->name('admin.sequences.update')->middleware('permission:leads.update');
    Route::delete('/sequences/{sequence}', [FollowUpSequenceController::class, 'destroy'])->name('admin.sequences.destroy')->middleware('permission:leads.delete');
    Route::post('/sequences/{sequence}/toggle', [FollowUpSequenceController::class, 'toggle'])->name('admin.sequences.toggle')->middleware('permission:leads.update');
    Route::get('/sequences/{sequence}/enrollments', [FollowUpSequenceController::class, 'enrollments'])->name('admin.sequences.enrollments')->middleware('permission:leads.view');
    Route::post('/sequences/enroll', [FollowUpSequenceController::class, 'enrollLead'])->name('admin.sequences.enroll')->middleware('permission:leads.update');
    Route::post('/enrollments/{enrollment}/cancel', [FollowUpSequenceController::class, 'cancelEnrollment'])->name('admin.enrollments.cancel')->middleware('permission:leads.update');

    // ═══════════════════════════════════════════════════════════
    // ═══ MODULE MANAGEMENT ROUTES ════════════════════════════
    // ═══════════════════════════════════════════════════════════

    // Module Settings
    Route::get('/settings/modules', [ModuleSettingController::class, 'index'])->name('admin.modules.index')->middleware('permission:settings.view');
    Route::post('/settings/modules/{module}', [ModuleSettingController::class, 'update'])->name('admin.modules.update')->middleware('permission:settings.update');
    Route::post('/settings/modules/{module}/toggle', [ModuleSettingController::class, 'toggle'])->name('admin.modules.toggle')->middleware('permission:settings.update');
    Route::get('/settings/modules/{module}', [ModuleSettingController::class, 'moduleSettings'])->name('admin.modules.show')->middleware('permission:settings.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ DENTAL MODULE ROUTES ════════════════════════════════
    // ═══════════════════════════════════════════════════════════

    // Dental Dashboard
    Route::get('/dental', [DentalDashboardController::class, 'index'])->name('admin.dental.dashboard')->middleware(['module:dental', 'permission:dental.view']);

    // Dental Chart
    Route::get('/dental/chart-search', [DentalChartController::class, 'search'])->name('admin.dental.chart.search')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/chart/{patient}', [DentalChartController::class, 'show'])->name('admin.dental.chart.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/chart/{patient}/tooth/{toothNumber}', [DentalChartController::class, 'updateTooth'])->name('admin.dental.chart.updateTooth')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/chart/{patient}/tooth/{toothNumber}/entry', [DentalChartController::class, 'addEntry'])->name('admin.dental.chart.addEntry')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/chart/{patient}/entry/{entry}', [DentalChartController::class, 'deleteEntry'])->name('admin.dental.chart.deleteEntry')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/chart/{patient}/initialize', [DentalChartController::class, 'initializeChart'])->name('admin.dental.chart.initialize')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Treatment Plans
    Route::get('/dental/treatment-plans', [DentalTreatmentPlanController::class, 'index'])->name('admin.dental.treatment-plans.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/treatment-plans/create', [DentalTreatmentPlanController::class, 'create'])->name('admin.dental.treatment-plans.create')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/treatment-plans', [DentalTreatmentPlanController::class, 'store'])->name('admin.dental.treatment-plans.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::get('/dental/treatment-plans/{treatmentPlan}', [DentalTreatmentPlanController::class, 'show'])->name('admin.dental.treatment-plans.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatment-plans/{treatmentPlan}', [DentalTreatmentPlanController::class, 'update'])->name('admin.dental.treatment-plans.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::get('/dental/treatment-plans/{treatmentPlan}/pdf', [DentalTreatmentPlanController::class, 'downloadPdf'])->name('admin.dental.treatment-plans.pdf')->middleware(['module:dental', 'permission:dental.view']);
    Route::delete('/dental/treatment-plans/{treatmentPlan}', [DentalTreatmentPlanController::class, 'destroy'])->name('admin.dental.treatment-plans.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // Dental Treatment Plan Consent
    Route::post('/dental/treatment-plans/{treatmentPlan}/consent/send', [\App\Http\Controllers\Admin\TreatmentPlanConsentController::class, 'send'])->name('admin.dental.consent.send')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/consent/{consent}/resend', [\App\Http\Controllers\Admin\TreatmentPlanConsentController::class, 'resend'])->name('admin.dental.consent.resend')->middleware(['module:dental', 'permission:dental.update']);
    Route::get('/dental/consent/{consent}/pdf', [\App\Http\Controllers\Admin\TreatmentPlanConsentController::class, 'downloadPdf'])->name('admin.dental.consent.pdf')->middleware(['module:dental', 'permission:dental.view']);

    // Dental Treatment Plan Templates
    Route::get('/dental/treatment-plan-templates', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'index'])->name('admin.dental.plan-templates.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatment-plan-templates', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'store'])->name('admin.dental.plan-templates.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/treatment-plan-templates/{template}', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'update'])->name('admin.dental.plan-templates.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/treatment-plan-templates/{template}/toggle', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'toggleActive'])->name('admin.dental.plan-templates.toggle')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/treatment-plan-templates/{template}/duplicate', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'duplicate'])->name('admin.dental.plan-templates.duplicate')->middleware(['module:dental', 'permission:dental.create']);
    Route::delete('/dental/treatment-plan-templates/{template}', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'destroy'])->name('admin.dental.plan-templates.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/treatment-plan-templates-seed', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'seedDefaults'])->name('admin.dental.plan-templates.seed')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Treatments
    Route::get('/dental/treatments', [DentalTreatmentController::class, 'index'])->name('admin.dental.treatments.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatments', [DentalTreatmentController::class, 'store'])->name('admin.dental.treatments.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/treatments/{treatment}', [DentalTreatmentController::class, 'update'])->name('admin.dental.treatments.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/treatments/{treatment}', [DentalTreatmentController::class, 'destroy'])->name('admin.dental.treatments.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // Dental X-rays
    Route::get('/dental/xrays', [DentalXrayController::class, 'index'])->name('admin.dental.xrays.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/xrays', [DentalXrayController::class, 'store'])->name('admin.dental.xrays.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/xrays/{xray}', [DentalXrayController::class, 'update'])->name('admin.dental.xrays.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/xrays/{xray}', [DentalXrayController::class, 'destroy'])->name('admin.dental.xrays.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::get('/dental/xrays/patient/{patient}', [DentalXrayController::class, 'patientXrays'])->name('admin.dental.xrays.patient')->middleware(['module:dental', 'permission:dental.view']);

    // Dental Lab Orders
    Route::get('/dental/lab-orders/dashboard', [DentalLabOrderController::class, 'dashboard'])->name('admin.dental.lab-orders.dashboard')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders/profitability', [DentalLabOrderController::class, 'profitability'])->name('admin.dental.lab-orders.profitability')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders', [DentalLabOrderController::class, 'index'])->name('admin.dental.lab-orders.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders/create', [DentalLabOrderController::class, 'create'])->name('admin.dental.lab-orders.create')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/lab-orders', [DentalLabOrderController::class, 'store'])->name('admin.dental.lab-orders.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/lab-orders/{labOrder}', [DentalLabOrderController::class, 'update'])->name('admin.dental.lab-orders.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/lab-orders/{labOrder}/status', [DentalLabOrderController::class, 'update'])->name('admin.dental.lab-orders.status')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/lab-orders/{labOrder}', [DentalLabOrderController::class, 'destroy'])->name('admin.dental.lab-orders.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/lab-orders-bulk/update-status', [DentalLabOrderController::class, 'bulkUpdateStatus'])->name('admin.dental.lab-orders.bulk-status')->middleware(['module:dental', 'permission:dental.update', 'throttle:10,1']);
    Route::post('/dental/lab-orders-bulk/sms-notify', [DentalLabOrderController::class, 'bulkSmsNotify'])->name('admin.dental.lab-orders.bulk-sms')->middleware(['module:dental', 'permission:dental.update', 'throttle:5,1']);
    Route::get('/dental/lab-orders-bulk/print', [DentalLabOrderController::class, 'printOrders'])->name('admin.dental.lab-orders.print')->middleware(['module:dental', 'permission:dental.view']);

    // Dental Smart Patient Notifications
    Route::get('/dental/smart-notifications', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'index'])->name('admin.dental.smart-notifications.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/smart-notifications/send', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'sendManual'])->name('admin.dental.smart-notifications.send')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/smart-notifications/scan', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'triggerScan'])->name('admin.dental.smart-notifications.scan')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/smart-notifications/{notification}/resend', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'resend'])->name('admin.dental.smart-notifications.resend')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/smart-notifications/{notification}/cancel', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'cancel'])->name('admin.dental.smart-notifications.cancel')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/smart-notifications/{notification}/responded', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'markResponded'])->name('admin.dental.smart-notifications.responded')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/smart-notifications/{notification}', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'destroy'])->name('admin.dental.smart-notifications.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // Dental Follow-up Rules
    Route::get('/dental/followup-rules', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'index'])->name('admin.dental.followup-rules.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/followup-rules/{rule}', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'update'])->name('admin.dental.followup-rules.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/followup-rules/{rule}/toggle', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'toggleActive'])->name('admin.dental.followup-rules.toggle')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/followup-rules-global/toggle', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'toggleGlobal'])->name('admin.dental.followup-rules.toggle-global')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/followup-rules-seed', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'seedDefaults'])->name('admin.dental.followup-rules.seed')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/scheduled-followups/{followup}/cancel', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'cancelFollowup'])->name('admin.dental.followup.cancel')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/scheduled-followups/{followup}/sms', [\App\Http\Controllers\Admin\DentalFollowupRuleController::class, 'sendSms'])->name('admin.dental.followup.sms')->middleware(['module:dental', 'permission:dental.update']);

    // Dental Before/After Comparisons
    Route::get('/dental/comparisons', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'index'])->name('admin.dental.comparisons.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/comparisons/create', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'create'])->name('admin.dental.comparisons.create')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/comparisons', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'store'])->name('admin.dental.comparisons.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::get('/dental/comparisons/{comparison}', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'show'])->name('admin.dental.comparisons.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/comparisons/{comparison}', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'update'])->name('admin.dental.comparisons.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/comparisons/{comparison}', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'destroy'])->name('admin.dental.comparisons.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/comparisons/{comparison}/toggle-visibility', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'toggleVisibility'])->name('admin.dental.comparisons.toggle-visibility')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/comparisons/{comparison}/toggle-featured', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'toggleFeatured'])->name('admin.dental.comparisons.toggle-featured')->middleware(['module:dental', 'permission:dental.update']);

    // Periodontal Chart
    Route::get('/dental/periodontal/{patient}', [PeriodontalChartController::class, 'show'])->name('admin.dental.periodontal.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/periodontal/{patient}', [PeriodontalChartController::class, 'store'])->name('admin.dental.periodontal.store')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Prescription Templates
    Route::get('/dental/prescription-templates', [DentalPrescriptionTemplateController::class, 'index'])->name('admin.dental.prescription-templates.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/prescription-templates', [DentalPrescriptionTemplateController::class, 'store'])->name('admin.dental.prescription-templates.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/prescription-templates/{template}', [DentalPrescriptionTemplateController::class, 'update'])->name('admin.dental.prescription-templates.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::delete('/dental/prescription-templates/{template}', [DentalPrescriptionTemplateController::class, 'destroy'])->name('admin.dental.prescription-templates.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // ═══════════════════════════════════════════════════════════
    // ═══ SUPPLIERS & PURCHASE ORDERS ══════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::middleware('module:inventory')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('admin.suppliers.index')->middleware('permission:supplies.view');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('admin.suppliers.store')->middleware('permission:supplies.create');
        Route::post('/suppliers/{supplier}/update', [SupplierController::class, 'update'])->name('admin.suppliers.update')->middleware('permission:supplies.update');
        Route::post('/suppliers/{supplier}/delete', [SupplierController::class, 'destroy'])->name('admin.suppliers.destroy')->middleware('permission:supplies.delete');

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('admin.purchase-orders.index')->middleware('permission:supplies.view');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('admin.purchase-orders.create')->middleware('permission:supplies.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('admin.purchase-orders.store')->middleware('permission:supplies.create');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('admin.purchase-orders.show')->middleware('permission:supplies.view');
        Route::post('/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])->name('admin.purchase-orders.status')->middleware('permission:supplies.update');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receiveItems'])->name('admin.purchase-orders.receive')->middleware('permission:supplies.update');
    });

    // ═══════════════════════════════════════════════════════════
    // ═══ PATIENT VITALS ═══════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/patients/{patient}/vitals', [PatientVitalController::class, 'index'])->name('admin.patient.vitals.index')->middleware('permission:patients.view');
    Route::get('/patients/{patient}/vitals/latest', [PatientVitalController::class, 'latest'])->name('admin.patient.vitals.latest')->middleware('permission:patients.view');
    Route::post('/patients/{patient}/vitals', [PatientVitalController::class, 'store'])->name('admin.patient.vitals.store')->middleware('permission:patients.update');
    Route::get('/patients/{patient}/vitals/trends', [PatientVitalController::class, 'trends'])->name('admin.patient.vitals.trends')->middleware('permission:patients.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ PATIENT DOCUMENTS ════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/patients/{patient}/documents', [PatientDocumentController::class, 'index'])->name('admin.patient.documents.index')->middleware('permission:patients.view');
    Route::post('/patients/{patient}/documents', [PatientDocumentController::class, 'store'])->name('admin.patient.documents.store')->middleware('permission:patients.update');
    Route::delete('/patients/{patient}/documents/{document}', [PatientDocumentController::class, 'destroy'])->name('admin.patient.documents.destroy')->middleware('permission:patients.update');
    Route::get('/documents/expiring', [PatientDocumentController::class, 'expiring'])->name('admin.documents.expiring')->middleware('permission:patients.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ INSURANCE MANAGEMENT ═════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::middleware('module:insurance')->group(function () {
        Route::get('/insurance/companies', [InsuranceCompanyController::class, 'index'])->name('admin.insurance.companies.index')->middleware('permission:settings.view');
        Route::get('/insurance/companies/create', [InsuranceCompanyController::class, 'create'])->name('admin.insurance.companies.create')->middleware('permission:settings.update');
        Route::post('/insurance/companies', [InsuranceCompanyController::class, 'store'])->name('admin.insurance.companies.store')->middleware('permission:settings.update');
        Route::get('/insurance/companies/{company}/edit', [InsuranceCompanyController::class, 'edit'])->name('admin.insurance.companies.edit')->middleware('permission:settings.update');
        Route::put('/insurance/companies/{company}', [InsuranceCompanyController::class, 'update'])->name('admin.insurance.companies.update')->middleware('permission:settings.update');
        Route::delete('/insurance/companies/{company}', [InsuranceCompanyController::class, 'destroy'])->name('admin.insurance.companies.destroy')->middleware('permission:settings.update');

        Route::get('/insurance/claims', [InsuranceClaimController::class, 'index'])->name('admin.insurance.claims.index')->middleware('permission:invoices.view');
        Route::post('/insurance/claims', [InsuranceClaimController::class, 'store'])->name('admin.insurance.claims.store')->middleware('permission:invoices.create');
        Route::post('/insurance/claims/{claim}/status', [InsuranceClaimController::class, 'updateStatus'])->name('admin.insurance.claims.status')->middleware('permission:invoices.update');

        Route::get('/insurance/reports', [InsuranceReportController::class, 'index'])->name('admin.insurance.reports')->middleware('permission:invoices.view');
    });

    // ═══════════════════════════════════════════════════════════
    // ═══ PATIENT SATISFACTION ═════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/satisfaction', [PatientSatisfactionController::class, 'index'])->name('admin.satisfaction.index')->middleware('permission:reports.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ REFERRALS ═════════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/referrals', [ReferralController::class, 'index'])->name('admin.referrals.index')->middleware('permission:visits.view');
    Route::post('/referrals', [ReferralController::class, 'store'])->name('admin.referrals.store')->middleware('permission:visits.create');
    Route::put('/referrals/{referral}/status', [ReferralController::class, 'updateStatus'])->name('admin.referrals.status')->middleware('permission:visits.update');

    // ═══ CREDIT NOTES ═══════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('admin.credit-notes.index')->middleware('permission:invoices.view');
    Route::post('/credit-notes', [CreditNoteController::class, 'store'])->name('admin.credit-notes.store')->middleware('permission:invoices.create');
    Route::put('/credit-notes/{creditNote}/status', [CreditNoteController::class, 'updateStatus'])->name('admin.credit-notes.status')->middleware('permission:invoices.update');

    // ═══ DOCTOR SCHEDULES ═══════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/schedules', [DoctorScheduleController::class, 'index'])->name('admin.schedules.index')->middleware('permission:doctors.view');
    Route::put('/schedules/{doctor}', [DoctorScheduleController::class, 'update'])->name('admin.schedules.update')->middleware('permission:doctors.update');

    // ═══ MEDICAL CERTIFICATES ═══════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/medical-certificates', [MedicalCertificateController::class, 'index'])->name('admin.medical-certificates.index')->middleware('permission:visits.view');
    Route::post('/medical-certificates', [MedicalCertificateController::class, 'store'])->name('admin.medical-certificates.store')->middleware('permission:visits.create');
    Route::put('/medical-certificates/{medicalCertificate}/issue', [MedicalCertificateController::class, 'issue'])->name('admin.medical-certificates.issue')->middleware('permission:visits.update');
    Route::put('/medical-certificates/{medicalCertificate}/cancel', [MedicalCertificateController::class, 'cancel'])->name('admin.medical-certificates.cancel')->middleware('permission:visits.update');

    // ═══ APPOINTMENT REMINDERS ══════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/appointment-reminders', [AppointmentReminderController::class, 'index'])->name('admin.appointment-reminders.index')->middleware('permission:visits.view');

    // ═══ PATIENT WALLETS ════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/wallets', [PatientWalletController::class, 'index'])->name('admin.wallets.index')->middleware('permission:payments.view');
    Route::get('/wallets/{patient}', [PatientWalletController::class, 'show'])->name('admin.wallets.show')->middleware('permission:payments.view');
    Route::post('/wallets/{patient}/deposit', [PatientWalletController::class, 'deposit'])->name('admin.wallets.deposit')->middleware('permission:payments.create');
    Route::post('/wallets/{patient}/withdraw', [PatientWalletController::class, 'withdraw'])->name('admin.wallets.withdraw')->middleware('permission:payments.create');
    Route::post('/wallets/{patient}/adjust', [PatientWalletController::class, 'adjust'])->name('admin.wallets.adjust')->middleware('permission:payments.create');
});
