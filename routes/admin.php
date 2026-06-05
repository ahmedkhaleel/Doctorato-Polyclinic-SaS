<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDoctorPayoutController;
use App\Http\Controllers\Admin\AdvanceController;
use App\Http\Controllers\Admin\AppointmentReminderController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\CommunicationTemplateController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CreditNoteController;
use App\Http\Controllers\Admin\CrmCampaignController;
use App\Http\Controllers\Admin\CrmDashboardController;
use App\Http\Controllers\Admin\CrmReportController;
use App\Http\Controllers\Admin\CrmSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DentalChartController;
use App\Http\Controllers\Admin\DentalDashboardController;
use App\Http\Controllers\Admin\DentalLabOrderController;
use App\Http\Controllers\Admin\DentalPrescriptionTemplateController;
use App\Http\Controllers\Admin\DentalReportController;
use App\Http\Controllers\Admin\DentalTreatmentController;
use App\Http\Controllers\Admin\DentalTreatmentPlanController;
use App\Http\Controllers\Admin\DentalXrayController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorKpiController;
use App\Http\Controllers\Admin\DoctorScheduleController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FollowUpSequenceController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\HrDashboardController;
use App\Http\Controllers\Admin\InsuranceClaimController;
use App\Http\Controllers\Admin\InsuranceCompanyController;
use App\Http\Controllers\Admin\InsurancePlanController;
use App\Http\Controllers\Admin\InsuranceReportController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadAssignmentRuleController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LeadScoringRuleController;
use App\Http\Controllers\Admin\LeadSourceController;
use App\Http\Controllers\Admin\LeaveController;
use App\Http\Controllers\Admin\MarketerCommissionController;
use App\Http\Controllers\Admin\MedicalCertificateController;
use App\Http\Controllers\Admin\MedicationController;
use App\Http\Controllers\Admin\ModuleSettingController;
use App\Http\Controllers\Admin\NotificationCenterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PackageBundleBookingController;
use App\Http\Controllers\Admin\PackageBundleController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PatientDocumentController;
use App\Http\Controllers\Admin\PatientInsuranceController;
use App\Http\Controllers\Admin\PatientSatisfactionController;
use App\Http\Controllers\Admin\PatientVitalController;
use App\Http\Controllers\Admin\PatientWalletController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PenaltyController;
use App\Http\Controllers\Admin\PeriodontalChartController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\QueueAnalyticsController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RevenueAnalyticsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoPageController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\StaffPerformanceController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupplyCategoryController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\TrackingController;
use App\Http\Controllers\Admin\TrashController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitController;
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

// Password reset
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('admin.password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('admin.password.email')->middleware('throttle:5,15');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('admin.password.update')->middleware('throttle:5,15');

// Protected admin routes (requires authentication + active account)
Route::middleware(['admin.auth', 'branch.context'])->group(function () {

    // ─── Branch switch + registry (multi-branch) ─────────────
    Route::post('/switch-branch', [\App\Http\Controllers\Admin\BranchSwitchController::class, 'switch'])->name('admin.switch-branch');
    Route::get('/branches', [\App\Http\Controllers\Admin\AdminBranchController::class, 'index'])->name('admin.branches.index')->middleware('permission:branches.view,settings.view');
    Route::post('/branches', [\App\Http\Controllers\Admin\AdminBranchController::class, 'store'])->name('admin.branches.store')->middleware('permission:branches.manage,settings.update');
    Route::post('/branches/{branch}/update', [\App\Http\Controllers\Admin\AdminBranchController::class, 'update'])->name('admin.branches.update')->middleware('permission:branches.manage,settings.update');
    Route::post('/branches/{branch}/delete', [\App\Http\Controllers\Admin\AdminBranchController::class, 'destroy'])->name('admin.branches.destroy')->middleware('permission:branches.manage,settings.update');
    Route::post('/branches/{branch}/members', [\App\Http\Controllers\Admin\AdminBranchController::class, 'syncMembers'])->name('admin.branches.members')->middleware('permission:branches.manage,settings.update');

    // Dashboard (all authenticated users)
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // ─── Locale Switching ─────────────────────────────────────
    Route::post('/switch-locale', [SettingController::class, 'switchLocale'])->name('admin.switchLocale');

    // ─── Unified Hub in-app feed (bell) ──────────────────────
    Route::get('/my-notifications', [\App\Http\Controllers\StaffNotificationController::class, 'index'])->defaults('panel', 'admin')->name('admin.my-notifications.index');
    Route::get('/my-notifications/bell', [\App\Http\Controllers\StaffNotificationController::class, 'bell'])->name('admin.my-notifications.bell');
    Route::post('/my-notifications/{id}/read', [\App\Http\Controllers\StaffNotificationController::class, 'markRead'])->whereNumber('id')->name('admin.my-notifications.read');
    Route::post('/my-notifications/read-all', [\App\Http\Controllers\StaffNotificationController::class, 'markAllRead'])->name('admin.my-notifications.readAll');

    // ─── Notifications ───────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('admin.notifications.markAllRead');
    Route::post('/notifications/{type}/{id}/read', [NotificationController::class, 'markRead'])->name('admin.notifications.markRead');

    // ─── Notification Center (Full Page) ──────────────────────
    Route::get('/notification-center', [NotificationCenterController::class, 'index'])->name('admin.notification-center.index');
    Route::post('/notification-center/mark-all-read', [NotificationCenterController::class, 'markAllRead'])->name('admin.notification-center.markAllRead');
    Route::post('/notification-center/clear', [NotificationCenterController::class, 'destroyAll'])->name('admin.notification-center.clear');
    Route::post('/notification-center/{id}/read', [NotificationCenterController::class, 'markRead'])->name('admin.notification-center.markRead');
    Route::post('/notification-center/{id}/delete', [NotificationCenterController::class, 'destroy'])->name('admin.notification-center.destroy');

    // ─── Chat / Messaging ─────────────────────────────────────
    Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('admin.chat.unreadCount')->middleware('throttle:30,1');
    Route::get('/chat/{user}', [ChatController::class, 'show'])->name('admin.chat.show');
    Route::post('/chat/{user}', [ChatController::class, 'store'])->name('admin.chat.store')->middleware('throttle:30,1');
    Route::get('/chat/{user}/poll', [ChatController::class, 'poll'])->name('admin.chat.poll')->middleware('throttle:30,1');
    Route::post('/chat/{user}/mark-read', [ChatController::class, 'markRead'])->name('admin.chat.markRead')->middleware('throttle:30,1');
    Route::get('/chat/{user}/older', [ChatController::class, 'loadOlder'])->name('admin.chat.loadOlder')->middleware('throttle:30,1');
    Route::post('/chat/{user}/delete', [ChatController::class, 'destroy'])->name('admin.chat.destroy');

    // ─── Posts ──────────────────────────────────────────────
    Route::get('/posts', [PostController::class, 'index'])->name('admin.posts.index')->middleware('permission:posts.view');
    Route::get('/posts/create', [PostController::class, 'create'])->name('admin.posts.create')->middleware('permission:posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('admin.posts.store')->middleware('permission:posts.create');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('admin.posts.edit')->middleware('permission:posts.update');
    Route::post('/posts/{post}/update', [PostController::class, 'update'])->name('admin.posts.update')->middleware('permission:posts.update');
    Route::post('/posts/{post}/delete', [PostController::class, 'destroy'])->name('admin.posts.destroy')->middleware('permission:posts.delete');

    // ─── Post Categories ───────────────────────────────────
    Route::get('/post-categories', [PostCategoryController::class, 'index'])->name('admin.post-categories.index')->middleware('permission:post_categories.view');
    Route::get('/post-categories/create', [PostCategoryController::class, 'create'])->name('admin.post-categories.create')->middleware('permission:post_categories.create');
    Route::post('/post-categories', [PostCategoryController::class, 'store'])->name('admin.post-categories.store')->middleware('permission:post_categories.create');
    Route::get('/post-categories/{post_category}/edit', [PostCategoryController::class, 'edit'])->name('admin.post-categories.edit')->middleware('permission:post_categories.update');
    Route::post('/post-categories/{post_category}/update', [PostCategoryController::class, 'update'])->name('admin.post-categories.update')->middleware('permission:post_categories.update');
    Route::post('/post-categories/{post_category}/delete', [PostCategoryController::class, 'destroy'])->name('admin.post-categories.destroy')->middleware('permission:post_categories.delete');

    // ─── Tags ──────────────────────────────────────────────
    Route::get('/tags', [TagController::class, 'index'])->name('admin.tags.index')->middleware('permission:tags.view');
    Route::get('/tags/create', [TagController::class, 'create'])->name('admin.tags.create')->middleware('permission:tags.create');
    Route::post('/tags', [TagController::class, 'store'])->name('admin.tags.store')->middleware('permission:tags.create');
    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('admin.tags.edit')->middleware('permission:tags.update');
    Route::post('/tags/{tag}/update', [TagController::class, 'update'])->name('admin.tags.update')->middleware('permission:tags.update');
    Route::post('/tags/{tag}/delete', [TagController::class, 'destroy'])->name('admin.tags.destroy')->middleware('permission:tags.delete');

    // ─── Services ──────────────────────────────────────────
    Route::get('/services', [ServiceController::class, 'index'])->name('admin.services.index')->middleware('permission:services.view');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('admin.services.create')->middleware('permission:services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('admin.services.store')->middleware('permission:services.create');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('admin.services.show')->middleware('permission:services.view');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('admin.services.edit')->middleware('permission:services.update');
    Route::post('/services/{service}/update', [ServiceController::class, 'update'])->name('admin.services.update')->middleware('permission:services.update');
    Route::post('/services/{service}/delete', [ServiceController::class, 'destroy'])->name('admin.services.destroy')->middleware('permission:services.delete');

    // ─── Service Categories ────────────────────────────────
    Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('admin.service-categories.index')->middleware('permission:service_categories.view');
    Route::get('/service-categories/create', [ServiceCategoryController::class, 'create'])->name('admin.service-categories.create')->middleware('permission:service_categories.create');
    Route::post('/service-categories', [ServiceCategoryController::class, 'store'])->name('admin.service-categories.store')->middleware('permission:service_categories.create');
    Route::get('/service-categories/{service_category}', [ServiceCategoryController::class, 'show'])->name('admin.service-categories.show')->middleware('permission:service_categories.view');
    Route::get('/service-categories/{service_category}/edit', [ServiceCategoryController::class, 'edit'])->name('admin.service-categories.edit')->middleware('permission:service_categories.update');
    Route::post('/service-categories/{service_category}/update', [ServiceCategoryController::class, 'update'])->name('admin.service-categories.update')->middleware('permission:service_categories.update');
    Route::post('/service-categories/{service_category}/delete', [ServiceCategoryController::class, 'destroy'])->name('admin.service-categories.destroy')->middleware('permission:service_categories.delete');

    // ─── Doctors ───────────────────────────────────────────
    Route::get('/doctors', [DoctorController::class, 'index'])->name('admin.doctors.index')->middleware('permission:doctors.view');
    Route::get('/doctors/create', [DoctorController::class, 'create'])->name('admin.doctors.create')->middleware('permission:doctors.create');
    Route::post('/doctors', [DoctorController::class, 'store'])->name('admin.doctors.store')->middleware('permission:doctors.create');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('admin.doctors.show')->middleware('permission:doctors.view');
    Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('admin.doctors.edit')->middleware('permission:doctors.update');
    Route::post('/doctors/{doctor}', [DoctorController::class, 'update'])->name('admin.doctors.update')->middleware('permission:doctors.update');
    Route::post('/doctors/{doctor}/delete', [DoctorController::class, 'destroy'])->name('admin.doctors.destroy')->middleware('permission:doctors.delete');
    Route::post('/doctors/{doctor}/create-user', [DoctorController::class, 'createUserAccount'])->name('admin.doctors.createUser')->middleware('permission:doctors.update');

    // ─── Gallery ───────────────────────────────────────────
    Route::get('/gallery', [GalleryController::class, 'index'])->name('admin.gallery.index')->middleware('permission:gallery.view');
    Route::get('/gallery/create', [GalleryController::class, 'create'])->name('admin.gallery.create')->middleware('permission:gallery.create');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('admin.gallery.store')->middleware('permission:gallery.create');
    Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('admin.gallery.edit')->middleware('permission:gallery.update');
    Route::post('/gallery/{gallery}/update', [GalleryController::class, 'update'])->name('admin.gallery.update')->middleware('permission:gallery.update');
    Route::post('/gallery/{gallery}/delete', [GalleryController::class, 'destroy'])->name('admin.gallery.destroy')->middleware('permission:gallery.delete');

    // ─── Testimonials ──────────────────────────────────────
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('admin.testimonials.index')->middleware('permission:testimonials.view');
    Route::get('/testimonials/create', [TestimonialController::class, 'create'])->name('admin.testimonials.create')->middleware('permission:testimonials.create');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->name('admin.testimonials.store')->middleware('permission:testimonials.create');
    Route::get('/testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])->name('admin.testimonials.edit')->middleware('permission:testimonials.update');
    Route::post('/testimonials/{testimonial}/update', [TestimonialController::class, 'update'])->name('admin.testimonials.update')->middleware('permission:testimonials.update');
    Route::post('/testimonials/{testimonial}/delete', [TestimonialController::class, 'destroy'])->name('admin.testimonials.destroy')->middleware('permission:testimonials.delete');

    // ─── FAQs ──────────────────────────────────────────────
    Route::get('/faqs', [FaqController::class, 'index'])->name('admin.faqs.index')->middleware('permission:faqs.view');
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('admin.faqs.create')->middleware('permission:faqs.create');
    Route::post('/faqs', [FaqController::class, 'store'])->name('admin.faqs.store')->middleware('permission:faqs.create');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('admin.faqs.edit')->middleware('permission:faqs.update');
    Route::post('/faqs/{faq}/update', [FaqController::class, 'update'])->name('admin.faqs.update')->middleware('permission:faqs.update');
    Route::post('/faqs/{faq}/delete', [FaqController::class, 'destroy'])->name('admin.faqs.destroy')->middleware('permission:faqs.delete');

    // ─── Pages (view + update only) ────────────────────────
    Route::get('/pages', [PageController::class, 'index'])->name('admin.pages.index')->middleware('permission:pages.view');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.pages.edit')->middleware('permission:pages.update');
    Route::post('/pages/{page}/update', [PageController::class, 'update'])->name('admin.pages.update')->middleware('permission:pages.update');

    // ─── Bookings (full CRUD + workflow) ─────────────────────
    Route::get('/bookings', [BookingController::class, 'index'])->name('admin.bookings.index')->middleware('permission:bookings.view');
    Route::get('/bookings/export', [BookingController::class, 'export'])->name('admin.bookings.export')->middleware(['permission:bookings.export', 'throttle:10,1']);
    Route::get('/bookings/check-followup', [BookingController::class, 'checkFollowUp'])->name('admin.bookings.checkFollowUp')->middleware('permission:bookings.view');
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('admin.bookings.create')->middleware('permission:bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('admin.bookings.store')->middleware('permission:bookings.create');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('admin.bookings.show')->middleware('permission:bookings.view');
    Route::post('/bookings/{booking}/update', [BookingController::class, 'update'])->name('admin.bookings.update')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('admin.bookings.confirm')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/payment', [BookingController::class, 'processPayment'])->name('admin.bookings.payment')->middleware('permission:bookings.update');
    Route::get('/bookings/{booking}/receipt', [BookingController::class, 'printReceipt'])->name('admin.bookings.receipt')->middleware('permission:bookings.view');
    Route::get('/bookings/{booking}/payments/{payment}/receipt', [BookingController::class, 'printPaymentReceipt'])->name('admin.bookings.paymentReceipt')->middleware('permission:bookings.view');
    Route::post('/bookings/{booking}/retouch', [BookingController::class, 'addRetouchSession'])->name('admin.bookings.retouch')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/consents', [BookingController::class, 'uploadConsent'])->name('admin.bookings.uploadConsent')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/consents/{consent}/delete', [BookingController::class, 'deleteConsent'])->name('admin.bookings.deleteConsent')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/appointments/{appointment}/check-in', [BookingController::class, 'checkInAppointment'])->name('admin.bookings.checkInAppointment')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/appointments/{appointment}/reschedule', [BookingController::class, 'rescheduleAppointment'])->name('admin.bookings.rescheduleAppointment')->middleware('permission:bookings.update');
    Route::post('/bookings/{booking}/services/update', [BookingController::class, 'updateServices'])->name('admin.bookings.updateServices')->middleware('permission:bookings.edit_services');
    Route::post('/bookings/{booking}/delete', [BookingController::class, 'destroy'])->name('admin.bookings.destroy')->middleware('permission:bookings.delete');

    // ─── Contact Messages (view + delete) ──────────────────
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('admin.contact-messages.index')->middleware('permission:contact_messages.view');
    Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('admin.contact-messages.show')->middleware('permission:contact_messages.view');
    Route::post('/contact-messages/{contactMessage}/delete', [ContactMessageController::class, 'destroy'])->name('admin.contact-messages.destroy')->middleware('permission:contact_messages.delete');

    // ─── Users ─────────────────────────────────────────────
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index')->middleware('permission:users.view');
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create')->middleware('permission:users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store')->middleware('permission:users.create');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit')->middleware('permission:users.update');
    Route::post('/users/{user}/update', [UserController::class, 'update'])->name('admin.users.update')->middleware('permission:users.update');
    Route::post('/users/{user}/delete', [UserController::class, 'destroy'])->name('admin.users.destroy')->middleware('permission:users.delete');

    // ─── Roles & Permissions ───────────────────────────────
    Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index')->middleware('permission:roles.view');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('admin.roles.create')->middleware('permission:roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store')->middleware('permission:roles.create');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.roles.edit')->middleware('permission:roles.update');
    Route::post('/roles/{role}/update', [RoleController::class, 'update'])->name('admin.roles.update')->middleware('permission:roles.update');
    Route::post('/roles/{role}/delete', [RoleController::class, 'destroy'])->name('admin.roles.destroy')->middleware('permission:roles.delete');

    // ─── Demo & Trial (super_admin only; controller hard-gates each action) ─
    Route::get('/demo-trial', [\App\Http\Controllers\Admin\DemoTrialController::class, 'index'])
        ->name('admin.demo-trial.index')->middleware('permission:settings.view');
    Route::post('/demo-trial/settings', [\App\Http\Controllers\Admin\DemoTrialController::class, 'updateSettings'])
        ->name('admin.demo-trial.settings')->middleware('permission:settings.update');
    Route::post('/demo-trial/extend', [\App\Http\Controllers\Admin\DemoTrialController::class, 'extendTrials'])
        ->name('admin.demo-trial.extend')->middleware('permission:settings.update');
    Route::post('/demo-trial/reset-password', [\App\Http\Controllers\Admin\DemoTrialController::class, 'resetPassword'])
        ->name('admin.demo-trial.reset-password')->middleware('permission:settings.update');

    // ─── Settings (view + update) ──────────────────────────
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index')->middleware('permission:settings.view');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update')->middleware('permission:settings.update');
    Route::post('/settings/test-sms', [SettingController::class, 'testSms'])->name('admin.settings.testSms')->middleware('permission:settings.update');

    // ─── Artificial Intelligence ───────────────────────────
    Route::prefix('ai')->group(function () {
        $ai = \App\Http\Controllers\Admin\AiController::class;
        Route::get('/settings', [$ai, 'settings'])->name('admin.ai.settings')->middleware('permission:ai.view');
        Route::post('/settings', [$ai, 'updateSettings'])->name('admin.ai.settings.update')->middleware('permission:ai.manage');
        Route::post('/settings/test', [$ai, 'testConnection'])->name('admin.ai.test')->middleware('permission:ai.manage');
        Route::get('/features', [$ai, 'features'])->name('admin.ai.features')->middleware('permission:ai.view');
        Route::post('/features', [$ai, 'updateFeatures'])->name('admin.ai.features.update')->middleware('permission:ai.manage');
        Route::get('/prompts', [$ai, 'prompts'])->name('admin.ai.prompts')->middleware('permission:ai.prompts,ai.manage');
        Route::post('/prompts/{prompt}', [$ai, 'updatePrompt'])->name('admin.ai.prompts.update')->middleware('permission:ai.prompts,ai.manage');
        Route::get('/usage', [$ai, 'usage'])->name('admin.ai.usage')->middleware('permission:ai.logs,ai.view');
        Route::get('/logs', [$ai, 'logs'])->name('admin.ai.logs')->middleware('permission:ai.logs,ai.view');
        Route::get('/insights', [$ai, 'insights'])->name('admin.ai.insights')->middleware('permission:ai.view');
        Route::post('/insights/ask', [$ai, 'analyticsAsk'])->name('admin.ai.insights.ask')->middleware('permission:ai.view');
        Route::get('/predictions', [$ai, 'predictions'])->name('admin.ai.predictions')->middleware('permission:ai.view');
        Route::post('/predictions/no-show', [$ai, 'predictNoShow'])->name('admin.ai.predictions.noShow')->middleware('permission:ai.view');
        Route::post('/predictions/reorder', [$ai, 'suggestReorder'])->name('admin.ai.predictions.reorder')->middleware('permission:ai.view');

        // AI Assistant workspace (Wave 1 text tools) + generic generation endpoint.
        $assist = \App\Http\Controllers\Admin\AiAssistController::class;
        Route::get('/assistant', [$assist, 'workspace'])->name('admin.ai.assistant')->middleware('permission:ai.view');
        Route::post('/assist', [$assist, 'generate'])->name('admin.ai.assist')->middleware('permission:ai.view');

        // Patient Assistant (RAG) — index management + playground.
        $pa = \App\Http\Controllers\Admin\AiPatientAssistantController::class;
        Route::get('/patient-assistant', [$pa, 'index'])->name('admin.ai.patient-assistant')->middleware('permission:ai.view');
        Route::post('/patient-assistant/rebuild', [$pa, 'rebuild'])->name('admin.ai.patient-assistant.rebuild')->middleware('permission:ai.manage');
        Route::post('/patient-assistant/test', [$pa, 'test'])->name('admin.ai.patient-assistant.test')->middleware('permission:ai.view');
    });

    // ─── Notifications Hub (unified control center) ────────
    Route::prefix('notifications-hub')->name('admin.notifications-hub.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'index'])
            ->name('index')->middleware('permission:notifications.view');
        Route::get('/logs', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'logs'])
            ->name('logs')->middleware('permission:notifications.view');
        Route::get('/analytics', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'analytics'])
            ->name('analytics')->middleware('permission:notifications.view');
        Route::get('/scheduled', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'scheduled'])
            ->name('scheduled')->middleware('permission:notifications.view');
        Route::post('/scheduled/{scheduledNotification}/cancel', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'cancelScheduled'])
            ->name('scheduled.cancel')->middleware('permission:notifications.update');
        Route::post('/channels/{channel}', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateChannel'])
            ->name('channels.update')->middleware('permission:notifications.update');
        Route::post('/routes', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateRoute'])
            ->name('routes.update')->middleware('permission:notifications.update');
        Route::post('/events/{key}', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateEvent'])
            ->name('events.update')->middleware('permission:notifications.update');
        Route::post('/templates', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'storeTemplate'])
            ->name('templates.store')->middleware('permission:notifications.create');
        Route::post('/templates/{template}/update', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateTemplate'])
            ->name('templates.update')->middleware('permission:notifications.update');
        Route::post('/templates/{template}/delete', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'destroyTemplate'])
            ->name('templates.destroy')->middleware('permission:notifications.update');
        Route::post('/settings', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateSettings'])
            ->name('settings.update')->middleware('permission:notifications.update');
        Route::post('/test', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'test'])
            ->name('test')->middleware('permission:notifications.send');

        // WhatsApp template registry (Meta-approved templates)
        Route::get('/whatsapp-templates', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'whatsappTemplates'])
            ->name('whatsapp-templates')->middleware('permission:notifications.view');
        Route::post('/whatsapp-templates', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'storeWhatsappTemplate'])
            ->name('whatsapp-templates.store')->middleware('permission:notifications.create');
        Route::post('/whatsapp-templates/{whatsappTemplate}/update', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'updateWhatsappTemplate'])
            ->name('whatsapp-templates.update')->middleware('permission:notifications.update');
        Route::post('/whatsapp-templates/{whatsappTemplate}/delete', [\App\Http\Controllers\Admin\AdminNotificationHubController::class, 'destroyWhatsappTemplate'])
            ->name('whatsapp-templates.destroy')->middleware('permission:notifications.update');
    });

    // ─── Notifications Inbox (two-way conversations) ───────
    Route::get('/inbox', [\App\Http\Controllers\NotificationInboxController::class, 'show'])->defaults('panel', 'admin')->name('admin.notifications-inbox.index')->middleware('permission:notifications.view');
    Route::post('/inbox/reply', [\App\Http\Controllers\NotificationInboxController::class, 'reply'])->name('admin.notifications-inbox.reply')->middleware('permission:notifications.send');

    // ─── Notification Drip Sequences ───────────────────────
    Route::get('/notification-sequences', [\App\Http\Controllers\Admin\AdminSequenceController::class, 'index'])->name('admin.notification-sequences.index')->middleware('permission:notifications.view');
    Route::post('/notification-sequences', [\App\Http\Controllers\Admin\AdminSequenceController::class, 'store'])->name('admin.notification-sequences.store')->middleware('permission:notifications.create');
    Route::post('/notification-sequences/{sequence}/update', [\App\Http\Controllers\Admin\AdminSequenceController::class, 'update'])->name('admin.notification-sequences.update')->middleware('permission:notifications.update');
    Route::post('/notification-sequences/{sequence}/delete', [\App\Http\Controllers\Admin\AdminSequenceController::class, 'destroy'])->name('admin.notification-sequences.destroy')->middleware('permission:notifications.update');
    Route::post('/notification-sequences/{sequence}/enroll', [\App\Http\Controllers\Admin\AdminSequenceController::class, 'enroll'])->name('admin.notification-sequences.enroll')->middleware('permission:notifications.send');

    // ─── Notification Campaigns (distinct path from CRM /campaigns) ──
    Route::get('/notification-campaigns', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'index'])->name('admin.notification-campaigns.index')->middleware('permission:notifications.view');
    Route::post('/notification-campaigns/preview', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'preview'])->name('admin.notification-campaigns.preview')->middleware('permission:notifications.view');
    Route::post('/notification-campaigns', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'store'])->name('admin.notification-campaigns.store')->middleware('permission:notifications.send');
    Route::post('/notification-campaigns/{campaign}/send', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'send'])->name('admin.notification-campaigns.send')->middleware('permission:notifications.send');
    Route::post('/notification-campaigns/{campaign}/delete', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'destroy'])->name('admin.notification-campaigns.destroy')->middleware('permission:notifications.update');

    // ─── SMS Templates (admin-editable copy) ───────────────
    Route::get('/sms-templates', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'index'])
        ->name('admin.sms-templates.index')->middleware('permission:settings.view');
    Route::post('/sms-templates/{smsTemplate}', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'update'])
        ->name('admin.sms-templates.update')->middleware('permission:settings.update');
    Route::post('/sms-templates/{smsTemplate}/preview', [\App\Http\Controllers\Admin\SmsTemplateController::class, 'preview'])
        ->name('admin.sms-templates.preview')->middleware('permission:settings.view');

    // ─── Loyalty Points (admin oversight + manual adjustments) ─
    Route::get('/loyalty', [\App\Http\Controllers\Admin\LoyaltyController::class, 'index'])
        ->name('admin.loyalty.index')->middleware('permission:loyalty.view,patients.view');
    Route::post('/loyalty/settings', [\App\Http\Controllers\Admin\LoyaltyController::class, 'updateSettings'])
        ->name('admin.loyalty.settings')->middleware('permission:loyalty.manage,settings.update');
    Route::get('/loyalty/{patient}', [\App\Http\Controllers\Admin\LoyaltyController::class, 'show'])
        ->name('admin.loyalty.show')->middleware('permission:loyalty.view,patients.view');
    Route::post('/loyalty/{patient}/adjust', [\App\Http\Controllers\Admin\LoyaltyController::class, 'adjust'])
        ->name('admin.loyalty.adjust')->middleware('permission:loyalty.manage,patients.update');

    // ─── Patient Referrals (admin oversight, read-only) ────────
    Route::get('/patient-referrals', [\App\Http\Controllers\Admin\PatientReferralController::class, 'index'])
        ->name('admin.patient-referrals.index')->middleware('permission:referrals.view,patients.view');

    // ─── Patient Recall (lapsed patients to bring back) ────────
    Route::get('/recall', [\App\Http\Controllers\Admin\RecallController::class, 'index'])
        ->name('admin.recall.index')->middleware('permission:recall.view,patients.view');
    Route::post('/recall/send-sms', [\App\Http\Controllers\Admin\RecallController::class, 'sendBulkSms'])
        ->name('admin.recall.send-sms')
        ->middleware(['permission:recall.send,patients.update', 'throttle:3,15']);

    // ─── Diagnostics (self-service system health) ──────────
    Route::get('/diagnostics', [\App\Http\Controllers\Admin\DiagnosticsController::class, 'show'])
        ->name('admin.diagnostics')
        ->middleware('permission:settings.view');
    Route::get('/diagnostics/export', [\App\Http\Controllers\Admin\DiagnosticsController::class, 'export'])
        ->name('admin.diagnostics.export')
        ->middleware('permission:settings.view');

    // ═══════════════════════════════════════════════════════
    // Backups (Super Admin only)
    // ═══════════════════════════════════════════════════════
    Route::middleware('permission:settings.update')->group(function () {
        Route::get('/backups', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/backups/run-full', [\App\Http\Controllers\Admin\BackupController::class, 'runFull'])->name('admin.backups.runFull')->middleware('throttle:3,10');
        Route::post('/backups/run-database', [\App\Http\Controllers\Admin\BackupController::class, 'runDatabase'])->name('admin.backups.runDatabase')->middleware('throttle:5,10');
        Route::post('/backups/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backups.download')->middleware('throttle:10,1');
        Route::post('/backups/cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanup'])->name('admin.backups.cleanup')->middleware('throttle:3,10');
        Route::post('/backups/delete', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy')->middleware('throttle:10,1');
    });

    // ─── Trash / Recycle Bin (سلة المحذوفات) ───────────────────
    Route::middleware('permission:settings.update')->group(function () {
        Route::get('/trash', [TrashController::class, 'index'])->name('admin.trash.index');
        Route::post('/trash/{type}/restore-all', [TrashController::class, 'restoreAll'])->name('admin.trash.restoreAll');
        Route::post('/trash/{type}/empty', [TrashController::class, 'emptyTrash'])->name('admin.trash.empty');
        Route::post('/trash/{type}/{id}/restore', [TrashController::class, 'restore'])->name('admin.trash.restore');
        Route::post('/trash/{type}/{id}/delete', [TrashController::class, 'forceDelete'])->name('admin.trash.forceDelete');
        Route::post('/trash/bulk-restore', [TrashController::class, 'bulkRestore'])->name('admin.trash.bulkRestore');
        Route::post('/trash/bulk-delete', [TrashController::class, 'bulkForceDelete'])->name('admin.trash.bulkForceDelete');
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
    Route::get('/patients/{patient}/communications', [\App\Http\Controllers\Admin\PatientCommunicationController::class, 'index'])->name('admin.patients.communications')->middleware('permission:patients.view');
    Route::post('/patients/{patient}/communications/send', [\App\Http\Controllers\Admin\PatientCommunicationController::class, 'send'])->name('admin.patients.communications.send')->middleware('permission:notifications.send');
    Route::post('/patients/{patient}/communications/preferences', [\App\Http\Controllers\Admin\PatientCommunicationController::class, 'updatePreferences'])->name('admin.patients.communications.preferences')->middleware('permission:patients.update');
    Route::get('/patients/{patient}/export-file', [\App\Http\Controllers\Admin\PatientExportController::class, 'exportFullFile'])->name('admin.patients.export')->middleware('permission:patients.view');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'edit'])->name('admin.patients.edit')->middleware('permission:patients.update');
    Route::post('/patients/{patient}/update', [PatientController::class, 'update'])->name('admin.patients.update')->middleware('permission:patients.update');
    Route::post('/patients/{patient}/delete', [PatientController::class, 'destroy'])->name('admin.patients.destroy')->middleware('permission:patients.delete');
    Route::post('/patients/{patient}/dental-medical', [PatientController::class, 'updateDentalMedical'])->name('admin.patients.updateDentalMedical')->middleware(['permission:patients.update', 'module:dental']);

    // ─── Visits ──────────────────────────────────────────────
    Route::get('/visits', [VisitController::class, 'index'])->name('admin.visits.index')->middleware('permission:visits.view');
    Route::get('/visits/today-queue', [VisitController::class, 'todayQueue'])->name('admin.visits.todayQueue')->middleware('permission:visits.view');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('admin.visits.show')->middleware('permission:visits.view');
    Route::post('/visits/{visit}/start', [VisitController::class, 'start'])->name('admin.visits.start')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/complete', [VisitController::class, 'complete'])->name('admin.visits.complete')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/cancel', [VisitController::class, 'cancel'])->name('admin.visits.cancel')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/restore', [VisitController::class, 'restore'])->name('admin.visits.restore')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/diagnosis', [VisitController::class, 'updateDiagnosis'])->name('admin.visits.updateDiagnosis')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/details', [VisitController::class, 'updateDetails'])->name('admin.visits.updateDetails')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/photos', [VisitController::class, 'uploadPhoto'])->name('admin.visits.uploadPhoto')->middleware('permission:visits.update');
    Route::post('/visits/{visit}/delete', [VisitController::class, 'destroy'])->name('admin.visits.destroy')->middleware('permission:visits.delete');

    // ─── Prescriptions ───────────────────────────────────────
    Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('admin.prescriptions.index')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('admin.prescriptions.show')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}/print', [PrescriptionController::class, 'print'])->name('admin.prescriptions.print')->middleware('permission:prescriptions.view');
    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'downloadPdf'])->name('admin.prescriptions.pdf')->middleware('permission:prescriptions.view');
    Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('admin.prescriptions.store')->middleware('permission:prescriptions.create');
    Route::post('/prescriptions/{prescription}/update', [PrescriptionController::class, 'update'])->name('admin.prescriptions.update')->middleware('permission:prescriptions.update');
    Route::post('/prescriptions/{prescription}/delete', [PrescriptionController::class, 'destroy'])->name('admin.prescriptions.destroy')->middleware('permission:prescriptions.delete');

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
    Route::post('/invoices/{invoice}/update', [InvoiceController::class, 'update'])->name('admin.invoices.update')->middleware('permission:invoices.update');
    Route::post('/invoices/{invoice}/delete', [InvoiceController::class, 'destroy'])->name('admin.invoices.destroy')->middleware('permission:invoices.delete');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('admin.invoices.print')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('admin.invoices.pdf')->middleware('permission:invoices.view');
    Route::get('/invoices/{invoice}/payments/{payment}/receipt', [InvoiceController::class, 'printPaymentReceipt'])->name('admin.invoices.paymentReceipt')->middleware('permission:invoices.view');

    // ─── Payments ────────────────────────────────────────────
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index')->middleware('permission:payments.view');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store')->middleware('permission:payments.create');
    Route::post('/payments/{payment}/delete', [PaymentController::class, 'destroy'])->name('admin.payments.destroy')->middleware('permission:payments.delete');

    // ─── Payment Methods ─────────────────────────────────────
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('admin.payment-methods.index')->middleware('permission:payments.view');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('admin.payment-methods.store')->middleware('permission:payments.create');
    Route::post('/payment-methods/{paymentMethod}/update', [PaymentMethodController::class, 'update'])->name('admin.payment-methods.update')->middleware('permission:payments.create');

    // ─── Discount Codes ──────────────────────────────────────
    Route::get('/discount-codes', [DiscountCodeController::class, 'index'])->name('admin.discount-codes.index')->middleware('permission:discount_codes.view');
    Route::get('/discount-codes/create', [DiscountCodeController::class, 'create'])->name('admin.discount-codes.create')->middleware('permission:discount_codes.create');
    Route::post('/discount-codes', [DiscountCodeController::class, 'store'])->name('admin.discount-codes.store')->middleware('permission:discount_codes.create');
    Route::get('/discount-codes/{discountCode}/edit', [DiscountCodeController::class, 'edit'])->name('admin.discount-codes.edit')->middleware('permission:discount_codes.update');
    Route::post('/discount-codes/{discountCode}', [DiscountCodeController::class, 'update'])->name('admin.discount-codes.update')->middleware('permission:discount_codes.update');
    Route::post('/discount-codes/{discountCode}/delete', [DiscountCodeController::class, 'destroy'])->name('admin.discount-codes.destroy')->middleware('permission:discount_codes.delete');
    Route::post('/api/discount-codes/validate', [DiscountCodeController::class, 'validateCode'])->name('admin.discount-codes.validate')->middleware(['permission:discount_codes.view', 'throttle:30,1']);

    // ─── Expense Categories ──────────────────────────────────
    Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->name('admin.expense-categories.index')->middleware('permission:expenses.view');
    Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->name('admin.expense-categories.store')->middleware('permission:expenses.create');
    Route::post('/expense-categories/{expenseCategory}/update', [ExpenseCategoryController::class, 'update'])->name('admin.expense-categories.update')->middleware('permission:expenses.update');
    Route::post('/expense-categories/{expenseCategory}/delete', [ExpenseCategoryController::class, 'destroy'])->name('admin.expense-categories.destroy')->middleware('permission:expenses.delete');

    // ─── Expenses ────────────────────────────────────────────
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index')->middleware('permission:expenses.view');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('admin.expenses.create')->middleware('permission:expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store')->middleware('permission:expenses.create');
    Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('admin.expenses.edit')->middleware('permission:expenses.update');
    Route::post('/expenses/{expense}/update', [ExpenseController::class, 'update'])->name('admin.expenses.update')->middleware('permission:expenses.update');
    Route::post('/expenses/{expense}/delete', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy')->middleware('permission:expenses.delete');

    // ─── Doctor Payouts ──────────────────────────────────────
    Route::get('/doctor-payouts', [AdminDoctorPayoutController::class, 'index'])->name('admin.doctor-payouts.index')->middleware(['module:hr', 'permission:doctor_payouts.view']);
    Route::get('/doctor-payouts/create', [AdminDoctorPayoutController::class, 'create'])->name('admin.doctor-payouts.create')->middleware(['module:hr', 'permission:doctor_payouts.create']);
    Route::post('/doctor-payouts', [AdminDoctorPayoutController::class, 'store'])->name('admin.doctor-payouts.store')->middleware(['module:hr', 'permission:doctor_payouts.create']);
    Route::get('/doctor-payouts/{payout}', [AdminDoctorPayoutController::class, 'show'])->name('admin.doctor-payouts.show')->middleware(['module:hr', 'permission:doctor_payouts.view']);
    Route::post('/doctor-payouts/{payout}/confirm', [AdminDoctorPayoutController::class, 'confirm'])->name('admin.doctor-payouts.confirm')->middleware(['module:hr', 'permission:doctor_payouts.update']);
    Route::post('/doctor-payouts/{payout}/mark-paid', [AdminDoctorPayoutController::class, 'markPaid'])->name('admin.doctor-payouts.mark-paid')->middleware(['module:hr', 'permission:doctor_payouts.update']);
    Route::post('/doctor-payouts/{payout}/cancel', [AdminDoctorPayoutController::class, 'cancel'])->name('admin.doctor-payouts.cancel')->middleware(['module:hr', 'permission:doctor_payouts.update']);
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
        Route::post('/departments/{department}/delete', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy')->middleware('permission:departments.delete');

        // Employees
        Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees.index')->middleware('permission:employees.view');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('admin.employees.create')->middleware('permission:employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store')->middleware('permission:employees.create');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('admin.employees.show')->middleware('permission:employees.view');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('admin.employees.edit')->middleware('permission:employees.update');
        Route::post('/employees/{employee}', [EmployeeController::class, 'update'])->name('admin.employees.update')->middleware('permission:employees.update');
        Route::post('/employees/{employee}/delete', [EmployeeController::class, 'destroy'])->name('admin.employees.destroy')->middleware('permission:employees.delete');

        // Payroll
        // Self-service: any logged-in admin-panel user can see their own payslips.
        // No permission gate — scoped to the user's own employee record in controller.
        Route::get('/my-payslips', [\App\Http\Controllers\Admin\AdminMyPayslipController::class, 'index'])->name('admin.my-payslips.index')->middleware('module:hr');
        Route::get('/my-payslips/{salarySlip}', [\App\Http\Controllers\Admin\AdminMyPayslipController::class, 'show'])->name('admin.my-payslips.show')->middleware('module:hr');

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
        Route::post('/penalties/{penalty}/delete', [PenaltyController::class, 'destroy'])->name('admin.penalties.destroy')->middleware('permission:penalties.delete');

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'index'])->name('admin.shifts.index')->middleware('permission:shifts.view');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('admin.shifts.store')->middleware('permission:shifts.create');
        Route::post('/shifts/{shift}/update', [ShiftController::class, 'update'])->name('admin.shifts.update')->middleware('permission:shifts.update');
        Route::post('/shifts/{shift}/delete', [ShiftController::class, 'destroy'])->name('admin.shifts.destroy')->middleware('permission:shifts.delete');

        // Attendance
        Route::get('/attendances', [AttendanceController::class, 'index'])->name('admin.attendances.index')->middleware('permission:attendances.view');
        Route::post('/attendances', [AttendanceController::class, 'store'])->name('admin.attendances.store')->middleware('permission:attendances.create');
        Route::post('/attendances/{attendance}/update', [AttendanceController::class, 'update'])->name('admin.attendances.update')->middleware('permission:attendances.update');
        Route::post('/attendances/quick-check-in', [AttendanceController::class, 'quickCheckIn'])->name('admin.attendances.quick-check-in')->middleware('permission:attendances.create');
        Route::post('/attendances/quick-check-out', [AttendanceController::class, 'quickCheckOut'])->name('admin.attendances.quick-check-out')->middleware('permission:attendances.create');
        Route::post('/attendances/{attendance}/delete', [AttendanceController::class, 'destroy'])->name('admin.attendances.destroy')->middleware('permission:attendances.delete');

        // Leaves
        Route::get('/leaves', [LeaveController::class, 'index'])->name('admin.leaves.index')->middleware('permission:leaves.view');
        Route::get('/leaves/create', [LeaveController::class, 'create'])->name('admin.leaves.create')->middleware('permission:leaves.create');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('admin.leaves.store')->middleware('permission:leaves.create');
        Route::post('/leaves/{leave}/update', [LeaveController::class, 'update'])->name('admin.leaves.update')->middleware('permission:leaves.update');
        Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])->name('admin.leaves.approve')->middleware('permission:leaves.approve');
        Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])->name('admin.leaves.reject')->middleware('permission:leaves.approve');
        Route::post('/leaves/{leave}/delete', [LeaveController::class, 'destroy'])->name('admin.leaves.destroy')->middleware('permission:leaves.delete');
    });

    // ─── Package Bundles ────────────────────────────────────────
    Route::get('/package-bundles', [PackageBundleController::class, 'index'])->name('admin.package-bundles.index')->middleware('permission:package_bundles.view');
    Route::get('/package-bundles/create', [PackageBundleController::class, 'create'])->name('admin.package-bundles.create')->middleware('permission:package_bundles.create');
    Route::post('/package-bundles', [PackageBundleController::class, 'store'])->name('admin.package-bundles.store')->middleware('permission:package_bundles.create');
    Route::get('/package-bundles/{packageBundle}', [PackageBundleController::class, 'show'])->name('admin.package-bundles.show')->middleware('permission:package_bundles.view');
    Route::get('/package-bundles/{packageBundle}/edit', [PackageBundleController::class, 'edit'])->name('admin.package-bundles.edit')->middleware('permission:package_bundles.update');
    Route::post('/package-bundles/{packageBundle}/update', [PackageBundleController::class, 'update'])->name('admin.package-bundles.update')->middleware('permission:package_bundles.update');
    Route::post('/package-bundles/{packageBundle}/delete', [PackageBundleController::class, 'destroy'])->name('admin.package-bundles.destroy')->middleware('permission:package_bundles.delete');
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
    Route::get('/reports/branch-comparison', [\App\Http\Controllers\Admin\BranchComparisonController::class, 'index'])->name('admin.reports.branch-comparison')->middleware('permission:reports.view');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('admin.reports.financial')->middleware('permission:reports.financial');
    Route::get('/reports/revenue-analytics', [RevenueAnalyticsController::class, 'index'])->name('admin.reports.revenue-analytics')->middleware('permission:reports.financial');
    Route::get('/reports/queue-analytics', [QueueAnalyticsController::class, 'index'])->name('admin.reports.queue-analytics')->middleware('permission:reports.view');
    Route::get('/reports/doctor-kpi', [DoctorKpiController::class, 'index'])->name('admin.reports.doctor-kpi')->middleware(['module:hr', 'permission:reports.view']);
    Route::get('/reports/staff-performance', [StaffPerformanceController::class, 'index'])->name('admin.reports.staff-performance')->middleware(['module:hr', 'permission:reports.view']);
    Route::get('/reports/doctors', [ReportController::class, 'doctors'])->name('admin.reports.doctors')->middleware('permission:reports.view');
    Route::get('/reports/patients', [ReportController::class, 'patients'])->name('admin.reports.patients')->middleware('permission:reports.view');
    Route::get('/reports/services', [ReportController::class, 'services'])->name('admin.reports.services')->middleware('permission:reports.view');
    Route::get('/reports/dental', [DentalReportController::class, 'index'])->name('admin.reports.dental')->middleware('permission:reports.view', 'module:dental');
    Route::get('/reports/derma', [\App\Http\Controllers\Admin\DermaReportController::class, 'index'])->name('admin.reports.derma')->middleware('permission:reports.view', 'module:derma');
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
    Route::post('/slider/{heroSlide}/update', [SliderController::class, 'update'])->name('admin.slider.update')->middleware('permission:settings.update');
    Route::post('/slider/{heroSlide}/delete', [SliderController::class, 'destroy'])->name('admin.slider.destroy')->middleware('permission:settings.update');
    Route::post('/slider/update-order', [SliderController::class, 'updateOrder'])->name('admin.slider.updateOrder')->middleware('permission:settings.update');

    // ─── SEO Pages ───────────────────────────────────────────
    Route::get('/seo-pages', [SeoPageController::class, 'index'])->name('admin.seoPages.index')->middleware('permission:settings.view');
    Route::get('/seo-pages/{seoPage}/edit', [SeoPageController::class, 'edit'])->name('admin.seoPages.edit')->middleware('permission:settings.view');
    Route::post('/seo-pages/{seoPage}/update', [SeoPageController::class, 'update'])->name('admin.seoPages.update')->middleware('permission:settings.update');

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
    Route::post('/leads/{lead}/delete', [LeadController::class, 'destroy'])->name('admin.leads.destroy')->middleware('permission:leads.delete');
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
    Route::post('/campaigns/{campaign}/delete', [CrmCampaignController::class, 'destroy'])->name('admin.campaigns.destroy')->middleware('permission:crm_campaigns.delete');

    // Lead Sources
    Route::get('/lead-sources', [LeadSourceController::class, 'index'])->name('admin.lead-sources.index')->middleware('permission:lead_sources.view');
    Route::post('/lead-sources', [LeadSourceController::class, 'store'])->name('admin.lead-sources.store')->middleware('permission:lead_sources.create');
    Route::post('/lead-sources/{leadSource}', [LeadSourceController::class, 'update'])->name('admin.lead-sources.update')->middleware('permission:lead_sources.update');
    Route::post('/lead-sources/{leadSource}/delete', [LeadSourceController::class, 'destroy'])->name('admin.lead-sources.destroy')->middleware('permission:lead_sources.delete');

    // Communication Templates
    Route::get('/templates', [CommunicationTemplateController::class, 'index'])->name('admin.templates.index')->middleware('permission:communication_templates.view');
    Route::get('/templates/create', [CommunicationTemplateController::class, 'create'])->name('admin.templates.create')->middleware('permission:communication_templates.create');
    Route::post('/templates', [CommunicationTemplateController::class, 'store'])->name('admin.templates.store')->middleware('permission:communication_templates.create');
    Route::get('/templates/{template}/edit', [CommunicationTemplateController::class, 'edit'])->name('admin.templates.edit')->middleware('permission:communication_templates.update');
    Route::post('/templates/{template}', [CommunicationTemplateController::class, 'update'])->name('admin.templates.update')->middleware('permission:communication_templates.update');
    Route::post('/templates/{template}/delete', [CommunicationTemplateController::class, 'destroy'])->name('admin.templates.destroy')->middleware('permission:communication_templates.delete');

    // Marketer Commissions
    Route::get('/commissions', [MarketerCommissionController::class, 'index'])->name('admin.commissions.index')->middleware('permission:marketer_commissions.view');
    Route::get('/commissions/create', [MarketerCommissionController::class, 'create'])->name('admin.commissions.create')->middleware('permission:marketer_commissions.create');
    Route::post('/commissions', [MarketerCommissionController::class, 'store'])->name('admin.commissions.store')->middleware('permission:marketer_commissions.create');
    Route::post('/commissions/{commission}/approve', [MarketerCommissionController::class, 'approve'])->name('admin.commissions.approve')->middleware('permission:marketer_commissions.update');
    Route::post('/commissions/{commission}/mark-paid', [MarketerCommissionController::class, 'markPaid'])->name('admin.commissions.markPaid')->middleware('permission:marketer_commissions.update');
    Route::post('/commissions/{commission}/delete', [MarketerCommissionController::class, 'destroy'])->name('admin.commissions.destroy')->middleware('permission:marketer_commissions.delete');

    // Scoring Rules
    Route::get('/scoring-rules', [LeadScoringRuleController::class, 'index'])->name('admin.scoring-rules.index')->middleware('permission:leads.view');
    Route::post('/scoring-rules', [LeadScoringRuleController::class, 'store'])->name('admin.scoring-rules.store')->middleware('permission:leads.update');
    Route::post('/scoring-rules/{rule}', [LeadScoringRuleController::class, 'update'])->name('admin.scoring-rules.update')->middleware('permission:leads.update');
    Route::post('/scoring-rules/{rule}/delete', [LeadScoringRuleController::class, 'destroy'])->name('admin.scoring-rules.destroy')->middleware('permission:leads.update');

    // Assignment Rules
    Route::get('/assignment-rules', [LeadAssignmentRuleController::class, 'index'])->name('admin.assignment-rules.index')->middleware('permission:leads.view');
    Route::post('/assignment-rules', [LeadAssignmentRuleController::class, 'store'])->name('admin.assignment-rules.store')->middleware('permission:leads.update');
    Route::post('/assignment-rules/{rule}', [LeadAssignmentRuleController::class, 'update'])->name('admin.assignment-rules.update')->middleware('permission:leads.update');
    Route::post('/assignment-rules/{rule}/delete', [LeadAssignmentRuleController::class, 'destroy'])->name('admin.assignment-rules.destroy')->middleware('permission:leads.update');

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
    Route::post('/sequences/{sequence}/delete', [FollowUpSequenceController::class, 'destroy'])->name('admin.sequences.destroy')->middleware('permission:leads.delete');
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

    // Telemedicine Settings
    Route::get('/settings/telemedicine', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'show'])
        ->name('admin.settings.telemedicine')->middleware('permission:settings.view');
    Route::post('/settings/telemedicine', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'update'])
        ->name('admin.settings.telemedicine.update')->middleware('permission:settings.update');
    Route::post('/settings/telemedicine/toggle', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'toggleModule'])
        ->name('admin.settings.telemedicine.toggle')->middleware('permission:settings.update');
    Route::post('/settings/telemedicine/test-agora', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'testAgora'])->middleware('permission:settings.update');
    Route::post('/settings/telemedicine/test-paymob', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'testPaymob'])->middleware('permission:settings.update');
    Route::post('/settings/telemedicine/test-stripe', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'testStripe'])->middleware('permission:settings.update');
    Route::post('/settings/telemedicine/test-reverb', [\App\Http\Controllers\Admin\TelemedicineSettingsController::class, 'testReverb'])->middleware('permission:settings.update');

    // Admin Online Consultations Dashboard
    Route::get('/online-consultations', [\App\Http\Controllers\Admin\OnlineConsultationController::class, 'index'])
        ->name('admin.online-consultations.index')
        ->middleware('permission:telemedicine.view,visits.view');
    Route::get('/online-consultations/doctors', [\App\Http\Controllers\Admin\OnlineConsultationController::class, 'doctors'])
        ->name('admin.online-consultations.doctors')
        ->middleware('permission:telemedicine.view,visits.view');
    Route::get('/online-consultations/{consultation}', [\App\Http\Controllers\Admin\OnlineConsultationController::class, 'show'])
        ->name('admin.online-consultations.show')
        ->middleware('permission:visits.view');

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
    Route::post('/dental/chart/{patient}/entry/{entry}/delete', [DentalChartController::class, 'deleteEntry'])->name('admin.dental.chart.deleteEntry')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/chart/{patient}/initialize', [DentalChartController::class, 'initializeChart'])->name('admin.dental.chart.initialize')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Treatment Plans
    Route::get('/dental/treatment-plans', [DentalTreatmentPlanController::class, 'index'])->name('admin.dental.treatment-plans.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/treatment-plans/create', [DentalTreatmentPlanController::class, 'create'])->name('admin.dental.treatment-plans.create')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/treatment-plans', [DentalTreatmentPlanController::class, 'store'])->name('admin.dental.treatment-plans.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::get('/dental/treatment-plans/{treatmentPlan}', [DentalTreatmentPlanController::class, 'show'])->name('admin.dental.treatment-plans.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatment-plans/{treatmentPlan}', [DentalTreatmentPlanController::class, 'update'])->name('admin.dental.treatment-plans.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/treatment-plans/{treatmentPlan}/status', [DentalTreatmentPlanController::class, 'updateStatus'])->name('admin.dental.treatment-plans.status')->middleware(['module:dental', 'permission:dental.update']);
    Route::get('/dental/treatment-plans/{treatmentPlan}/pdf', [DentalTreatmentPlanController::class, 'downloadPdf'])->name('admin.dental.treatment-plans.pdf')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatment-plans/{treatmentPlan}/delete', [DentalTreatmentPlanController::class, 'destroy'])->name('admin.dental.treatment-plans.destroy')->middleware(['module:dental', 'permission:dental.delete']);

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
    Route::post('/dental/treatment-plan-templates/{template}/delete', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'destroy'])->name('admin.dental.plan-templates.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/treatment-plan-templates-seed', [\App\Http\Controllers\Admin\DentalTreatmentPlanTemplateController::class, 'seedDefaults'])->name('admin.dental.plan-templates.seed')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Treatments
    Route::get('/dental/treatments', [DentalTreatmentController::class, 'index'])->name('admin.dental.treatments.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/treatments', [DentalTreatmentController::class, 'store'])->name('admin.dental.treatments.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/treatments/{treatment}', [DentalTreatmentController::class, 'update'])->name('admin.dental.treatments.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/treatments/{treatment}/delete', [DentalTreatmentController::class, 'destroy'])->name('admin.dental.treatments.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // Dental X-rays
    Route::get('/dental/xrays', [DentalXrayController::class, 'index'])->name('admin.dental.xrays.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/xrays', [DentalXrayController::class, 'store'])->name('admin.dental.xrays.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/xrays/{xray}', [DentalXrayController::class, 'update'])->name('admin.dental.xrays.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/xrays/{xray}/delete', [DentalXrayController::class, 'destroy'])->name('admin.dental.xrays.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::get('/dental/xrays/patient/{patient}', [DentalXrayController::class, 'patientXrays'])->name('admin.dental.xrays.patient')->middleware(['module:dental', 'permission:dental.view']);

    // Dental Lab Orders
    Route::get('/dental/lab-orders/dashboard', [DentalLabOrderController::class, 'dashboard'])->name('admin.dental.lab-orders.dashboard')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders/profitability', [DentalLabOrderController::class, 'profitability'])->name('admin.dental.lab-orders.profitability')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders', [DentalLabOrderController::class, 'index'])->name('admin.dental.lab-orders.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::get('/dental/lab-orders/create', [DentalLabOrderController::class, 'create'])->name('admin.dental.lab-orders.create')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/lab-orders', [DentalLabOrderController::class, 'store'])->name('admin.dental.lab-orders.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/lab-orders/{labOrder}', [DentalLabOrderController::class, 'update'])->name('admin.dental.lab-orders.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/lab-orders/{labOrder}/status', [DentalLabOrderController::class, 'update'])->name('admin.dental.lab-orders.status')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/lab-orders/{labOrder}/delete', [DentalLabOrderController::class, 'destroy'])->name('admin.dental.lab-orders.destroy')->middleware(['module:dental', 'permission:dental.delete']);
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
    Route::post('/dental/smart-notifications/{notification}/delete', [\App\Http\Controllers\Admin\DentalSmartNotificationController::class, 'destroy'])->name('admin.dental.smart-notifications.destroy')->middleware(['module:dental', 'permission:dental.delete']);

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
    Route::post('/dental/comparisons/{comparison}/delete', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'destroy'])->name('admin.dental.comparisons.destroy')->middleware(['module:dental', 'permission:dental.delete']);
    Route::post('/dental/comparisons/{comparison}/toggle-visibility', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'toggleVisibility'])->name('admin.dental.comparisons.toggle-visibility')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/comparisons/{comparison}/toggle-featured', [\App\Http\Controllers\Admin\DentalComparisonController::class, 'toggleFeatured'])->name('admin.dental.comparisons.toggle-featured')->middleware(['module:dental', 'permission:dental.update']);

    // Periodontal Chart
    Route::get('/dental/periodontal/{patient}', [PeriodontalChartController::class, 'show'])->name('admin.dental.periodontal.show')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/periodontal/{patient}', [PeriodontalChartController::class, 'store'])->name('admin.dental.periodontal.store')->middleware(['module:dental', 'permission:dental.create']);

    // Dental Prescription Templates
    Route::get('/dental/prescription-templates', [DentalPrescriptionTemplateController::class, 'index'])->name('admin.dental.prescription-templates.index')->middleware(['module:dental', 'permission:dental.view']);
    Route::post('/dental/prescription-templates', [DentalPrescriptionTemplateController::class, 'store'])->name('admin.dental.prescription-templates.store')->middleware(['module:dental', 'permission:dental.create']);
    Route::post('/dental/prescription-templates/{template}', [DentalPrescriptionTemplateController::class, 'update'])->name('admin.dental.prescription-templates.update')->middleware(['module:dental', 'permission:dental.update']);
    Route::post('/dental/prescription-templates/{template}/delete', [DentalPrescriptionTemplateController::class, 'destroy'])->name('admin.dental.prescription-templates.destroy')->middleware(['module:dental', 'permission:dental.delete']);

    // ─── Pediatric Module ──────────────────────────────────────
    Route::get('/pediatric', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'dashboard'])->name('admin.pediatric.dashboard')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::get('/pediatric/patients', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'patients'])->name('admin.pediatric.patients')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::get('/pediatric/vaccinations', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'vaccinations'])->name('admin.pediatric.vaccinations')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::post('/pediatric/vaccinations', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'storeVaccination'])->name('admin.pediatric.vaccinations.store')->middleware(['module:pediatric', 'permission:pediatric.create']);
    Route::post('/pediatric/patients/{patient}/vaccinations/initialize', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'initializeVaccinations'])->name('admin.pediatric.vaccinations.initialize')->middleware(['module:pediatric', 'permission:pediatric.create']);
    Route::post('/pediatric/vaccinations/{vaccination}/status', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'updateVaccinationStatus'])->name('admin.pediatric.vaccinations.status')->middleware(['module:pediatric', 'permission:pediatric.update']);
    Route::post('/pediatric/vaccinations/{vaccination}/delete', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'destroyVaccination'])->name('admin.pediatric.vaccinations.destroy')->middleware(['module:pediatric', 'permission:pediatric.delete']);
    Route::get('/pediatric/visits', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'visits'])->name('admin.pediatric.visits')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::get('/pediatric/growth', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'growth'])->name('admin.pediatric.growth')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::post('/pediatric/patients/{patient}/growth', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'storeGrowth'])->name('admin.pediatric.growth.store')->middleware(['module:pediatric', 'permission:pediatric.create']);
    Route::post('/pediatric/growth/{record}/update', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'updateGrowth'])->name('admin.pediatric.growth.update')->middleware(['module:pediatric', 'permission:pediatric.update']);
    Route::post('/pediatric/growth/{record}/delete', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'destroyGrowth'])->name('admin.pediatric.growth.destroy')->middleware(['module:pediatric', 'permission:pediatric.delete']);
    Route::get('/pediatric/settings', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'settings'])->name('admin.pediatric.settings')->middleware(['module:pediatric', 'permission:pediatric.view']);
    Route::post('/pediatric/settings', [\App\Http\Controllers\Admin\AdminPediatricController::class, 'updateSettings'])->name('admin.pediatric.settings.update')->middleware(['module:pediatric', 'permission:pediatric.update']);

    // ═══ OB/GYN MODULE ════════════════════════════════════════
    Route::get('/obgyn', [\App\Http\Controllers\Admin\AdminObgynController::class, 'dashboard'])->name('admin.obgyn.dashboard')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/cases', [\App\Http\Controllers\Admin\AdminObgynController::class, 'cases'])->name('admin.obgyn.cases')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/anc', [\App\Http\Controllers\Admin\AdminObgynController::class, 'anc'])->name('admin.obgyn.anc')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/labs', [\App\Http\Controllers\Admin\AdminObgynController::class, 'labs'])->name('admin.obgyn.labs')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/pregnancies', [\App\Http\Controllers\Admin\AdminObgynController::class, 'pregnancies'])->name('admin.obgyn.pregnancies')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/reports', [\App\Http\Controllers\Admin\AdminObgynController::class, 'reports'])->name('admin.obgyn.reports')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::get('/obgyn/settings', [\App\Http\Controllers\Admin\AdminObgynController::class, 'settings'])->name('admin.obgyn.settings')->middleware(['module:obgyn', 'permission:obgyn.view']);
    Route::post('/obgyn/settings', [\App\Http\Controllers\Admin\AdminObgynController::class, 'updateSettings'])->name('admin.obgyn.settings.update')->middleware(['module:obgyn', 'permission:obgyn.update']);

    // ─── Psychiatry & Neurology admin (NP7) — shared controller ──
    foreach (['psychiatry', 'neurology'] as $npm) {
        Route::get("/{$npm}", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'dashboard'])->defaults('npModule', $npm)->name("admin.{$npm}.dashboard")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::get("/{$npm}/cases", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'cases'])->defaults('npModule', $npm)->name("admin.{$npm}.cases")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::get("/{$npm}/encounters", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'encounters'])->defaults('npModule', $npm)->name("admin.{$npm}.encounters")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::get("/{$npm}/outcomes", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'outcomes'])->defaults('npModule', $npm)->name("admin.{$npm}.outcomes")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::get("/{$npm}/medications", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'medications'])->defaults('npModule', $npm)->name("admin.{$npm}.medications")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        // Sensitive — safety + compliance: heightened RBAC.
        Route::get("/{$npm}/risk", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'riskRegister'])->defaults('npModule', $npm)->name("admin.{$npm}.risk")->middleware(["module:{$npm}", "permission:{$npm}.view_sensitive"]);
        Route::get("/{$npm}/controlled", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'controlled'])->defaults('npModule', $npm)->name("admin.{$npm}.controlled")->middleware(["module:{$npm}", "permission:{$npm}.view_sensitive"]);
        Route::get("/{$npm}/reports", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'reports'])->defaults('npModule', $npm)->name("admin.{$npm}.reports")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::get("/{$npm}/settings", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'settings'])->defaults('npModule', $npm)->name("admin.{$npm}.settings")->middleware(["module:{$npm}", "permission:{$npm}.view"]);
        Route::post("/{$npm}/settings", [\App\Http\Controllers\Admin\AdminNeuropsychController::class, 'updateSettings'])->defaults('npModule', $npm)->name("admin.{$npm}.settings.update")->middleware(["module:{$npm}", "permission:{$npm}.update"]);
    }

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
    Route::post('/patients/{patient}/documents/{document}/delete', [PatientDocumentController::class, 'destroy'])->name('admin.patient.documents.destroy')->middleware('permission:patients.update');
    Route::get('/documents/expiring', [PatientDocumentController::class, 'expiring'])->name('admin.documents.expiring')->middleware('permission:patients.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ INSURANCE MANAGEMENT ═════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::middleware('module:insurance')->group(function () {
        Route::get('/insurance/companies', [InsuranceCompanyController::class, 'index'])->name('admin.insurance.companies.index')->middleware('permission:settings.view');
        Route::get('/insurance/companies/create', [InsuranceCompanyController::class, 'create'])->name('admin.insurance.companies.create')->middleware('permission:settings.update');
        Route::post('/insurance/companies', [InsuranceCompanyController::class, 'store'])->name('admin.insurance.companies.store')->middleware('permission:settings.update');
        Route::get('/insurance/companies/{company}/edit', [InsuranceCompanyController::class, 'edit'])->name('admin.insurance.companies.edit')->middleware('permission:settings.update');
        Route::post('/insurance/companies/{company}/update', [InsuranceCompanyController::class, 'update'])->name('admin.insurance.companies.update')->middleware('permission:settings.update');
        Route::post('/insurance/companies/{company}/delete', [InsuranceCompanyController::class, 'destroy'])->name('admin.insurance.companies.destroy')->middleware('permission:settings.update');

        Route::get('/insurance/claims', [InsuranceClaimController::class, 'index'])->name('admin.insurance.claims.index')->middleware('permission:invoices.view');
        Route::get('/insurance/claims/{claim}', [InsuranceClaimController::class, 'show'])->name('admin.insurance.claims.show')->middleware('permission:invoices.view');
        Route::post('/insurance/claims', [InsuranceClaimController::class, 'store'])->name('admin.insurance.claims.store')->middleware('permission:invoices.create');
        Route::post('/insurance/claims/{claim}/status', [InsuranceClaimController::class, 'updateStatus'])->name('admin.insurance.claims.status')->middleware('permission:invoices.update');

        // ─── Pre-authorizations ───────────────────────────────
        Route::get('/insurance/pre-authorizations', [\App\Http\Controllers\Admin\InsurancePreAuthorizationController::class, 'index'])->name('admin.insurance.pre-authorizations.index')->middleware('permission:invoices.view');
        Route::post('/insurance/pre-authorizations', [\App\Http\Controllers\Admin\InsurancePreAuthorizationController::class, 'store'])->name('admin.insurance.pre-authorizations.store')->middleware('permission:invoices.create');
        Route::post('/insurance/pre-authorizations/{preAuthorization}/status', [\App\Http\Controllers\Admin\InsurancePreAuthorizationController::class, 'updateStatus'])->name('admin.insurance.pre-authorizations.status')->middleware('permission:invoices.update');
        Route::post('/insurance/pre-authorizations/{preAuthorization}/delete', [\App\Http\Controllers\Admin\InsurancePreAuthorizationController::class, 'destroy'])->name('admin.insurance.pre-authorizations.destroy')->middleware('permission:invoices.update');

        Route::get('/insurance/reports', [InsuranceReportController::class, 'index'])->name('admin.insurance.reports')->middleware('permission:invoices.view');

        // Insurance Plans
        Route::get('/insurance/plans', [InsurancePlanController::class, 'index'])->name('admin.insurance.plans.index')->middleware('permission:settings.view');
        Route::get('/insurance/plans/create', [InsurancePlanController::class, 'create'])->name('admin.insurance.plans.create')->middleware('permission:settings.update');
        Route::post('/insurance/plans', [InsurancePlanController::class, 'store'])->name('admin.insurance.plans.store')->middleware('permission:settings.update');
        Route::get('/insurance/plans/{plan}/edit', [InsurancePlanController::class, 'edit'])->name('admin.insurance.plans.edit')->middleware('permission:settings.update');
        Route::post('/insurance/plans/{plan}/update', [InsurancePlanController::class, 'update'])->name('admin.insurance.plans.update')->middleware('permission:settings.update');
        Route::post('/insurance/plans/{plan}/delete', [InsurancePlanController::class, 'destroy'])->name('admin.insurance.plans.destroy')->middleware('permission:settings.update');

        // Patient Insurances
        Route::get('/insurance/patient-insurances', [PatientInsuranceController::class, 'index'])->name('admin.insurance.patient-insurances.index')->middleware('permission:patients.view');
        Route::post('/insurance/patient-insurances/ocr', [PatientInsuranceController::class, 'ocr'])->name('admin.patient-insurances.ocr')->middleware('permission:patients.update');
        Route::post('/patients/{patient}/insurances', [PatientInsuranceController::class, 'storeForPatient'])->name('admin.patient-insurances.store')->middleware('permission:patients.update');
        Route::post('/patient-insurances/{insurance}/update', [PatientInsuranceController::class, 'update'])->name('admin.patient-insurances.update')->middleware('permission:patients.update');
        Route::post('/patient-insurances/{insurance}/verify', [PatientInsuranceController::class, 'verify'])->name('admin.patient-insurances.verify')->middleware('permission:patients.update');
        Route::post('/patient-insurances/{insurance}/delete', [PatientInsuranceController::class, 'destroy'])->name('admin.patient-insurances.destroy')->middleware('permission:patients.update');
    });

    // ═══════════════════════════════════════════════════════════
    // ═══ PATIENT SATISFACTION ═════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/satisfaction', [PatientSatisfactionController::class, 'index'])->name('admin.satisfaction.index')->middleware('permission:satisfaction.view,reports.view');

    // ═══════════════════════════════════════════════════════════
    // ═══ REFERRALS ═════════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/referrals', [ReferralController::class, 'index'])->name('admin.referrals.index')->middleware('permission:visits.view');
    Route::post('/referrals', [ReferralController::class, 'store'])->name('admin.referrals.store')->middleware('permission:visits.create');
    Route::post('/referrals/{referral}/status', [ReferralController::class, 'updateStatus'])->name('admin.referrals.status')->middleware('permission:visits.update');

    // ═══ CREDIT NOTES ═══════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('admin.credit-notes.index')->middleware('permission:invoices.view');
    Route::post('/credit-notes', [CreditNoteController::class, 'store'])->name('admin.credit-notes.store')->middleware('permission:invoices.create');
    Route::post('/credit-notes/{creditNote}/status', [CreditNoteController::class, 'updateStatus'])->name('admin.credit-notes.status')->middleware('permission:invoices.update');

    // ═══ DOCTOR SCHEDULES ═══════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/schedules', [DoctorScheduleController::class, 'index'])->name('admin.schedules.index')->middleware('permission:doctors.view');
    Route::post('/schedules/{doctor}/update', [DoctorScheduleController::class, 'update'])->name('admin.schedules.update')->middleware('permission:doctors.update');

    // ═══ MEDICAL CERTIFICATES ═══════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/medical-certificates', [MedicalCertificateController::class, 'index'])->name('admin.medical-certificates.index')->middleware('permission:visits.view');
    Route::post('/medical-certificates', [MedicalCertificateController::class, 'store'])->name('admin.medical-certificates.store')->middleware('permission:visits.create');
    Route::post('/medical-certificates/{medicalCertificate}/issue', [MedicalCertificateController::class, 'issue'])->name('admin.medical-certificates.issue')->middleware('permission:visits.update');
    Route::post('/medical-certificates/{medicalCertificate}/cancel', [MedicalCertificateController::class, 'cancel'])->name('admin.medical-certificates.cancel')->middleware('permission:visits.update');

    // ═══ APPOINTMENT REMINDERS ══════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/appointment-reminders', [AppointmentReminderController::class, 'index'])->name('admin.appointment-reminders.index')->middleware('permission:visits.view');

    // ═══ DERMA MODULE ═══════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    // Group requires the derma module enabled AND at least derma.view.
    // Mutating routes additionally require derma.create/update/delete.
    Route::prefix('derma')->name('admin.derma.')->middleware(['module:derma', 'permission:derma.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DermaController::class, 'index'])->name('dashboard');
        Route::get('/patients', [\App\Http\Controllers\Admin\DermaPatientController::class, 'index'])->name('patients');
        Route::get('/patients/{patient}', [\App\Http\Controllers\Admin\DermaPatientController::class, 'show'])->name('patients.show');
        Route::get('/visits', [\App\Http\Controllers\Admin\DermaVisitController::class, 'index'])->name('visits');

        Route::get('/conditions', [\App\Http\Controllers\Admin\SkinConditionController::class, 'index'])->name('conditions.index');
        Route::post('/conditions', [\App\Http\Controllers\Admin\SkinConditionController::class, 'store'])->name('conditions.store')->middleware('permission:derma.create');
        Route::post('/conditions/{condition}', [\App\Http\Controllers\Admin\SkinConditionController::class, 'update'])->name('conditions.update')->middleware('permission:derma.update');
        Route::delete('/conditions/{condition}', [\App\Http\Controllers\Admin\SkinConditionController::class, 'destroy'])->name('conditions.destroy')->middleware('permission:derma.delete');

        Route::get('/treatment-plans', [\App\Http\Controllers\Admin\DermaTreatmentPlanController::class, 'index'])->name('treatmentPlans.index');
        Route::post('/treatment-plans', [\App\Http\Controllers\Admin\DermaTreatmentPlanController::class, 'store'])->name('treatmentPlans.store')->middleware('permission:derma.create');
        Route::post('/treatment-plans/{plan}', [\App\Http\Controllers\Admin\DermaTreatmentPlanController::class, 'update'])->name('treatmentPlans.update')->middleware('permission:derma.update');
        Route::delete('/treatment-plans/{plan}', [\App\Http\Controllers\Admin\DermaTreatmentPlanController::class, 'destroy'])->name('treatmentPlans.destroy')->middleware('permission:derma.delete');

        Route::get('/sessions', [\App\Http\Controllers\Admin\DermaSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [\App\Http\Controllers\Admin\DermaSessionController::class, 'store'])->name('sessions.store')->middleware('permission:derma.create');
        Route::post('/sessions/{session}', [\App\Http\Controllers\Admin\DermaSessionController::class, 'update'])->name('sessions.update')->middleware('permission:derma.update');
        Route::delete('/sessions/{session}', [\App\Http\Controllers\Admin\DermaSessionController::class, 'destroy'])->name('sessions.destroy')->middleware('permission:derma.delete');

        Route::get('/comparisons', [\App\Http\Controllers\Admin\DermaComparisonController::class, 'index'])->name('comparisons.index');

        Route::get('/gallery', [\App\Http\Controllers\Admin\DermaPhotoController::class, 'index'])->name('gallery');
        Route::post('/gallery', [\App\Http\Controllers\Admin\DermaPhotoController::class, 'store'])->name('gallery.store')->middleware('permission:derma.create');
        Route::delete('/gallery/{photo}', [\App\Http\Controllers\Admin\DermaPhotoController::class, 'destroy'])->name('gallery.destroy')->middleware('permission:derma.delete');

        Route::get('/prescription-templates', [\App\Http\Controllers\Admin\DermaPrescriptionTemplateController::class, 'index'])->name('prescriptionTemplates.index');
        Route::post('/prescription-templates', [\App\Http\Controllers\Admin\DermaPrescriptionTemplateController::class, 'store'])->name('prescriptionTemplates.store')->middleware('permission:derma.create');
        Route::post('/prescription-templates/{template}', [\App\Http\Controllers\Admin\DermaPrescriptionTemplateController::class, 'update'])->name('prescriptionTemplates.update')->middleware('permission:derma.update');
        Route::delete('/prescription-templates/{template}', [\App\Http\Controllers\Admin\DermaPrescriptionTemplateController::class, 'destroy'])->name('prescriptionTemplates.destroy')->middleware('permission:derma.delete');

        Route::get('/settings', [\App\Http\Controllers\Admin\DermaController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\DermaController::class, 'updateSettings'])->name('settings.update')->middleware('permission:derma.update');
    });

    // ═══ COSMETIC FEATURES (part of Dermatology & Cosmetic module) ═══
    // ═══════════════════════════════════════════════════════════════
    // Cosmetic shares the derma.* permission family (same module).
    Route::prefix('cosmetic')->name('admin.cosmetic.')->middleware(['module:derma', 'permission:derma.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CosmeticController::class, 'index'])->name('dashboard');
        Route::get('/patients', [\App\Http\Controllers\Admin\CosmeticPatientController::class, 'index'])->name('patients');
        Route::get('/patients/{patient}', [\App\Http\Controllers\Admin\CosmeticPatientController::class, 'show'])->name('patients.show');

        Route::get('/procedures', [\App\Http\Controllers\Admin\CosmeticProcedureController::class, 'index'])->name('procedures.index');
        Route::post('/procedures', [\App\Http\Controllers\Admin\CosmeticProcedureController::class, 'store'])->name('procedures.store')->middleware('permission:derma.create');
        Route::post('/procedures/{procedure}', [\App\Http\Controllers\Admin\CosmeticProcedureController::class, 'update'])->name('procedures.update')->middleware('permission:derma.update');
        Route::delete('/procedures/{procedure}', [\App\Http\Controllers\Admin\CosmeticProcedureController::class, 'destroy'])->name('procedures.destroy')->middleware('permission:derma.delete');

        Route::get('/packages', [\App\Http\Controllers\Admin\CosmeticPackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [\App\Http\Controllers\Admin\CosmeticPackageController::class, 'store'])->name('packages.store')->middleware('permission:derma.create');
        Route::post('/packages/{package}', [\App\Http\Controllers\Admin\CosmeticPackageController::class, 'update'])->name('packages.update')->middleware('permission:derma.update');
        Route::delete('/packages/{package}', [\App\Http\Controllers\Admin\CosmeticPackageController::class, 'destroy'])->name('packages.destroy')->middleware('permission:derma.delete');

        // Patient package purchases (prepaid enrollments + session balance)
        Route::get('/package-purchases', [\App\Http\Controllers\Admin\CosmeticPackagePurchaseController::class, 'index'])->name('packagePurchases.index');
        Route::post('/package-purchases', [\App\Http\Controllers\Admin\CosmeticPackagePurchaseController::class, 'store'])->name('packagePurchases.store')->middleware('permission:derma.create');
        Route::post('/package-purchases/{purchase}/cancel', [\App\Http\Controllers\Admin\CosmeticPackagePurchaseController::class, 'cancel'])->name('packagePurchases.cancel')->middleware('permission:derma.update');

        Route::get('/sessions', [\App\Http\Controllers\Admin\CosmeticSessionController::class, 'index'])->name('sessions.index');
        Route::post('/sessions', [\App\Http\Controllers\Admin\CosmeticSessionController::class, 'store'])->name('sessions.store')->middleware('permission:derma.create');
        Route::post('/sessions/{session}', [\App\Http\Controllers\Admin\CosmeticSessionController::class, 'update'])->name('sessions.update')->middleware('permission:derma.update');
        Route::delete('/sessions/{session}', [\App\Http\Controllers\Admin\CosmeticSessionController::class, 'destroy'])->name('sessions.destroy')->middleware('permission:derma.delete');

        Route::get('/gallery', [\App\Http\Controllers\Admin\CosmeticPhotoController::class, 'index'])->name('gallery');
        Route::post('/gallery', [\App\Http\Controllers\Admin\CosmeticPhotoController::class, 'store'])->name('gallery.store')->middleware('permission:derma.create');
        Route::delete('/gallery/{photo}', [\App\Http\Controllers\Admin\CosmeticPhotoController::class, 'destroy'])->name('gallery.destroy')->middleware('permission:derma.delete');

        Route::get('/consents', [\App\Http\Controllers\Admin\CosmeticConsentController::class, 'index'])->name('consents.index');
        Route::post('/consents', [\App\Http\Controllers\Admin\CosmeticConsentController::class, 'store'])->name('consents.store')->middleware('permission:derma.create');
        Route::post('/consents/{consent}', [\App\Http\Controllers\Admin\CosmeticConsentController::class, 'update'])->name('consents.update')->middleware('permission:derma.update');
        Route::delete('/consents/{consent}', [\App\Http\Controllers\Admin\CosmeticConsentController::class, 'destroy'])->name('consents.destroy')->middleware('permission:derma.delete');

        // Consent templates (drive mandatory-signature enforcement)
        Route::get('/consent-templates', [\App\Http\Controllers\Admin\CosmeticConsentTemplateController::class, 'index'])->name('consentTemplates.index');
        Route::post('/consent-templates', [\App\Http\Controllers\Admin\CosmeticConsentTemplateController::class, 'store'])->name('consentTemplates.store')->middleware('permission:derma.create');
        Route::post('/consent-templates/{template}', [\App\Http\Controllers\Admin\CosmeticConsentTemplateController::class, 'update'])->name('consentTemplates.update')->middleware('permission:derma.update');
        Route::delete('/consent-templates/{template}', [\App\Http\Controllers\Admin\CosmeticConsentTemplateController::class, 'destroy'])->name('consentTemplates.destroy')->middleware('permission:derma.delete');

        Route::get('/settings', [\App\Http\Controllers\Admin\CosmeticController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\CosmeticController::class, 'updateSettings'])->name('settings.update')->middleware('permission:derma.update');
    });

    // ═══ PATIENT WALLETS ════════════════════════════════════════
    // ═══════════════════════════════════════════════════════════
    Route::get('/wallets', [PatientWalletController::class, 'index'])->name('admin.wallets.index')->middleware('permission:payments.view');
    Route::get('/wallets/{patient}', [PatientWalletController::class, 'show'])->name('admin.wallets.show')->middleware('permission:payments.view');
    Route::post('/wallets/{patient}/deposit', [PatientWalletController::class, 'deposit'])->name('admin.wallets.deposit')->middleware('permission:payments.create');
    Route::post('/wallets/{patient}/withdraw', [PatientWalletController::class, 'withdraw'])->name('admin.wallets.withdraw')->middleware('permission:payments.create');
    Route::post('/wallets/{patient}/adjust', [PatientWalletController::class, 'adjust'])->name('admin.wallets.adjust')->middleware('permission:payments.create');
});
