<?php

use App\Http\Controllers\Secretary\AuthController;
use App\Http\Controllers\Secretary\SecretaryDashboardController;
use App\Http\Controllers\Secretary\SecretaryPatientController;
use App\Http\Controllers\Secretary\SecretaryQueueController;
use App\Http\Controllers\Secretary\SecretaryVisitController;
use App\Http\Controllers\Secretary\SecretaryBookingController;
use App\Http\Controllers\Secretary\SecretaryInvoiceController;
use App\Http\Controllers\Secretary\SecretaryPaymentController;
use App\Http\Controllers\Secretary\SecretaryPrescriptionController;
use App\Http\Controllers\Secretary\SecretaryProfileController;
use App\Http\Controllers\Secretary\SecretaryNotificationController;
use App\Http\Controllers\Secretary\SecretaryCalendarController;
use App\Http\Controllers\Secretary\SecretaryChatController;
use App\Http\Controllers\Secretary\SecretaryAttendanceController;
use App\Http\Controllers\Secretary\SecretaryLeaveController;
use App\Http\Controllers\Secretary\SecretaryPackageBundleBookingController;
use App\Http\Controllers\Secretary\SecretarySalarySlipController;
use App\Http\Controllers\Secretary\SecretaryCrmController;
use App\Http\Controllers\Secretary\SecretaryDentalController;
use App\Http\Controllers\Secretary\SecretaryInventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Secretary Routes
|--------------------------------------------------------------------------
|
| These routes handle the secretary panel functionality.
| All routes are prefixed with 'secretary'.
|
*/

// Secretary authentication routes (no auth middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('secretary.login');
Route::post('/login', [AuthController::class, 'login'])->name('secretary.authenticate')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('secretary.logout');

// Password reset
Route::get('/forgot-password',          [AuthController::class, 'showForgotPassword'])->name('secretary.password.request');
Route::post('/forgot-password',         [AuthController::class, 'sendResetLink'])->name('secretary.password.email')->middleware('throttle:5,15');
Route::get('/reset-password/{token}',   [AuthController::class, 'showResetForm'])->name('secretary.password.reset');
Route::post('/reset-password',          [AuthController::class, 'resetPassword'])->name('secretary.password.update')->middleware('throttle:5,15');

// Protected secretary routes (requires authentication + secretary role)
Route::middleware(['secretary.auth', 'branch.context'])->group(function () {

    // ─── Branch Switching (multi-branch) ─────────────────────
    Route::post('/switch-branch', [\App\Http\Controllers\Admin\BranchSwitchController::class, 'switch'])->name('secretary.switch-branch');

    // ─── Locale Switch ─────────────────────────────────────
    Route::post('/switch-locale', function (\Illuminate\Http\Request $request) {
        $locale = $request->input('locale', 'ar');
        session()->put('admin_locale', in_array($locale, ['ar', 'en']) ? $locale : 'ar');
        $previous = url()->previous();
        // Ensure redirect stays within secretary portal
        if (! str_contains($previous, '/secretary')) {
            return redirect('/secretary');
        }
        return redirect($previous);
    })->name('secretary.switchLocale');

    // ─── Dashboard ──────────────────────────────────────────
    Route::get('/', [SecretaryDashboardController::class, 'index'])->name('secretary.dashboard');

    // ─── Unified Hub in-app feed (bell) ─────────────────────
    Route::get('/my-notifications', [\App\Http\Controllers\StaffNotificationController::class, 'index'])->defaults('panel', 'secretary')->name('secretary.my-notifications.index');
    Route::get('/my-notifications/bell', [\App\Http\Controllers\StaffNotificationController::class, 'bell'])->name('secretary.my-notifications.bell');
    Route::post('/my-notifications/{id}/read', [\App\Http\Controllers\StaffNotificationController::class, 'markRead'])->whereNumber('id')->name('secretary.my-notifications.read');
    Route::post('/my-notifications/read-all', [\App\Http\Controllers\StaffNotificationController::class, 'markAllRead'])->name('secretary.my-notifications.readAll');

    // ─── Notifications Inbox (two-way conversations) ────────
    Route::get('/inbox', [\App\Http\Controllers\NotificationInboxController::class, 'show'])->defaults('panel', 'secretary')->name('secretary.notifications-inbox.index')->middleware('permission:notifications.view');
    Route::post('/inbox/reply', [\App\Http\Controllers\NotificationInboxController::class, 'reply'])->name('secretary.notifications-inbox.reply')->middleware('permission:notifications.send');

    // ─── Notifications ──────────────────────────────────────
    Route::get('/notifications', [SecretaryNotificationController::class, 'index'])->name('secretary.notifications.index');
    Route::post('/notifications/mark-all-read', [SecretaryNotificationController::class, 'markAllRead'])->name('secretary.notifications.markAllRead');

    // ─── Patients ───────────────────────────────────────────
    Route::get('/patients', [SecretaryPatientController::class, 'index'])->name('secretary.patients.index');
    Route::get('/patients/create', [SecretaryPatientController::class, 'create'])->name('secretary.patients.create');
    Route::post('/patients', [SecretaryPatientController::class, 'store'])->name('secretary.patients.store');
    Route::post('/patients/quick-create', [SecretaryPatientController::class, 'quickStore'])->name('secretary.patients.quickStore');
    Route::get('/patients/{patient}', [SecretaryPatientController::class, 'show'])->name('secretary.patients.show');
    Route::get('/patients/{patient}/edit', [SecretaryPatientController::class, 'edit'])->name('secretary.patients.edit');
    Route::post('/patients/{patient}/update', [SecretaryPatientController::class, 'update'])->name('secretary.patients.update');
    Route::get('/api/patients', [SecretaryPatientController::class, 'search'])->name('secretary.patients.search');

    // ─── Today Queue ────────────────────────────────────────
    Route::get('/queue', [SecretaryQueueController::class, 'index'])->name('secretary.queue.index');

    // ─── Visits ─────────────────────────────────────────────
    Route::get('/visits', [SecretaryVisitController::class, 'index'])->name('secretary.visits.index');
    Route::get('/visits/{visit}', [SecretaryVisitController::class, 'show'])->name('secretary.visits.show');
    Route::post('/visits/{visit}/start', [SecretaryVisitController::class, 'start'])->name('secretary.visits.start');
    Route::post('/visits/{visit}/complete', [SecretaryVisitController::class, 'complete'])->name('secretary.visits.complete');
    Route::post('/visits/{visit}/cancel', [SecretaryVisitController::class, 'cancel'])->name('secretary.visits.cancel');
    Route::post('/visits/{visit}/details', [SecretaryVisitController::class, 'updateDetails'])
        ->name('secretary.visits.updateDetails');
    Route::post('/visits/{visit}/restore', [SecretaryVisitController::class, 'restore'])
        ->name('secretary.visits.restore');

    // ─── Bookings ───────────────────────────────────────────
    Route::get('/bookings', [SecretaryBookingController::class, 'index'])->name('secretary.bookings.index');
    Route::get('/bookings/create', [SecretaryBookingController::class, 'create'])->name('secretary.bookings.create');
    Route::post('/bookings', [SecretaryBookingController::class, 'store'])->name('secretary.bookings.store');
    Route::get('/bookings/check-followup', [SecretaryBookingController::class, 'checkFollowUp'])->name('secretary.bookings.checkFollowUp');
    Route::get('/bookings/{booking}', [SecretaryBookingController::class, 'show'])->name('secretary.bookings.show');
    Route::post('/bookings/{booking}/status', [SecretaryBookingController::class, 'updateStatus'])->name('secretary.bookings.updateStatus');
    Route::post('/bookings/{booking}/confirm', [SecretaryBookingController::class, 'confirm'])->name('secretary.bookings.confirm');
    Route::post('/bookings/{booking}/payment', [SecretaryBookingController::class, 'processPayment'])->name('secretary.bookings.payment');
    Route::get('/bookings/{booking}/receipt', [SecretaryBookingController::class, 'printReceipt'])->name('secretary.bookings.receipt');
    Route::get('/bookings/{booking}/payments/{payment}/receipt', [SecretaryBookingController::class, 'printPaymentReceipt'])->name('secretary.bookings.paymentReceipt');
    Route::post('/bookings/{booking}/retouch', [SecretaryBookingController::class, 'addRetouchSession'])->name('secretary.bookings.retouch');
    Route::post('/bookings/{booking}/consents', [SecretaryBookingController::class, 'uploadConsent'])->name('secretary.bookings.uploadConsent');
    Route::post('/bookings/{booking}/consents/{consent}/delete', [SecretaryBookingController::class, 'deleteConsent'])->name('secretary.bookings.deleteConsent');
    Route::post('/bookings/{booking}/appointments/{appointment}/check-in', [SecretaryBookingController::class, 'checkInAppointment'])->name('secretary.bookings.checkInAppointment');
    Route::post('/bookings/{booking}/appointments/{appointment}/reschedule', [SecretaryBookingController::class, 'rescheduleAppointment'])->name('secretary.bookings.rescheduleAppointment');

    // ─── Package Bundle Bookings ──────────────────────────────
    Route::get('/bundle-bookings', [SecretaryPackageBundleBookingController::class, 'index'])->name('secretary.bundle-bookings.index');
    Route::get('/bundle-bookings/create', [SecretaryPackageBundleBookingController::class, 'create'])->name('secretary.bundle-bookings.create');
    Route::post('/bundle-bookings', [SecretaryPackageBundleBookingController::class, 'store'])->name('secretary.bundle-bookings.store');
    Route::get('/bundle-bookings/{bundleBooking}', [SecretaryPackageBundleBookingController::class, 'show'])->name('secretary.bundle-bookings.show');
    Route::post('/bundle-bookings/{bundleBooking}/payment', [SecretaryPackageBundleBookingController::class, 'processPayment'])->name('secretary.bundle-bookings.payment');
    Route::post('/bundle-bookings/{bundleBooking}/appointments/{appointment}/check-in', [SecretaryPackageBundleBookingController::class, 'checkInAppointment'])->name('secretary.bundle-bookings.checkInAppointment');
    Route::post('/bundle-bookings/{bundleBooking}/appointments/{appointment}/reschedule', [SecretaryPackageBundleBookingController::class, 'rescheduleAppointment'])->name('secretary.bundle-bookings.rescheduleAppointment');
    Route::post('/bundle-bookings/{bundleBooking}/retouch', [SecretaryPackageBundleBookingController::class, 'addRetouchSession'])->name('secretary.bundle-bookings.retouch');

    // ─── Invoices ───────────────────────────────────────────
    Route::get('/invoices', [SecretaryInvoiceController::class, 'index'])->name('secretary.invoices.index');
    Route::get('/invoices/create', [SecretaryInvoiceController::class, 'create'])->name('secretary.invoices.create');
    Route::post('/invoices', [SecretaryInvoiceController::class, 'store'])->name('secretary.invoices.store');
    Route::get('/invoices/{invoice}', [SecretaryInvoiceController::class, 'show'])->name('secretary.invoices.show');
    Route::get('/invoices/{invoice}/payments/{payment}/receipt', [SecretaryInvoiceController::class, 'printPaymentReceipt'])->name('secretary.invoices.paymentReceipt');

    // ─── Payments ───────────────────────────────────────────
    Route::get('/payments', [SecretaryPaymentController::class, 'index'])->name('secretary.payments.index');
    Route::post('/payments', [SecretaryPaymentController::class, 'store'])->name('secretary.payments.store');

    // ─── Prescriptions (Read-only) ──────────────────────────
    Route::get('/prescriptions', [SecretaryPrescriptionController::class, 'index'])->name('secretary.prescriptions.index');
    Route::get('/prescriptions/{prescription}', [SecretaryPrescriptionController::class, 'show'])->name('secretary.prescriptions.show');
    Route::get('/prescriptions/{prescription}/print', [SecretaryPrescriptionController::class, 'print'])->name('secretary.prescriptions.print');
    Route::get('/prescriptions/{prescription}/pdf', [SecretaryPrescriptionController::class, 'downloadPdf'])->name('secretary.prescriptions.pdf');

    // ─── Calendar ──────────────────────────────────────────────
    Route::get('/calendar', [SecretaryCalendarController::class, 'index'])->name('secretary.calendar.index');

    // ─── Chat / Messaging ─────────────────────────────────────
    Route::get('/chat', [SecretaryChatController::class, 'index'])->name('secretary.chat.index');
    Route::get('/chat/unread-count', [SecretaryChatController::class, 'unreadCount'])->name('secretary.chat.unreadCount')->middleware('throttle:30,1');
    Route::get('/chat/{user}', [SecretaryChatController::class, 'show'])->name('secretary.chat.show');
    Route::post('/chat/{user}', [SecretaryChatController::class, 'store'])->name('secretary.chat.store')->middleware('throttle:30,1');
    Route::get('/chat/{user}/poll', [SecretaryChatController::class, 'poll'])->name('secretary.chat.poll')->middleware('throttle:30,1');
    Route::post('/chat/{user}/mark-read', [SecretaryChatController::class, 'markRead'])->name('secretary.chat.markRead')->middleware('throttle:30,1');
    Route::get('/chat/{user}/older', [SecretaryChatController::class, 'loadOlder'])->name('secretary.chat.loadOlder')->middleware('throttle:30,1');

    // ─── HR Module (Attendance, Leaves, Salary) ─────────────────
    Route::middleware('module:hr')->group(function () {
        Route::get('/my-attendance', [SecretaryAttendanceController::class, 'index'])->name('secretary.my-attendance.index');
        Route::post('/my-attendance/check-in', [SecretaryAttendanceController::class, 'checkIn'])->name('secretary.my-attendance.check-in');
        Route::post('/my-attendance/check-out', [SecretaryAttendanceController::class, 'checkOut'])->name('secretary.my-attendance.check-out');
        Route::get('/my-leaves', [SecretaryLeaveController::class, 'index'])->name('secretary.my-leaves.index');
        Route::post('/my-leaves', [SecretaryLeaveController::class, 'store'])->name('secretary.my-leaves.store');
        Route::get('/my-salary-slips', [SecretarySalarySlipController::class, 'index'])->name('secretary.my-salary-slips.index');
        Route::get('/my-salary-slips/{salarySlip}', [SecretarySalarySlipController::class, 'show'])->name('secretary.my-salary-slips.show');
    });

    // ─── CRM / Leads ──────────────────────────────────────────
    // ─── Inventory ─────────────────────────────────────────
    Route::get('/inventory', [SecretaryInventoryController::class, 'index'])->name('secretary.inventory.index');
    Route::post('/inventory/{supply}/quick-adjust', [SecretaryInventoryController::class, 'quickAdjust'])->name('secretary.inventory.quickAdjust');

    Route::get('/crm', [SecretaryCrmController::class, 'dashboard'])->name('secretary.crm.dashboard');
    Route::get('/crm/leads', [SecretaryCrmController::class, 'leads'])->name('secretary.crm.leads');
    Route::post('/crm/leads/bulk-status', [SecretaryCrmController::class, 'bulkUpdateStatus'])->name('secretary.crm.bulkStatus');
    Route::post('/crm/leads/bulk-priority', [SecretaryCrmController::class, 'bulkUpdatePriority'])->name('secretary.crm.bulkPriority');
    Route::get('/crm/leads/create', [SecretaryCrmController::class, 'create'])->name('secretary.crm.create');
    Route::post('/crm/leads', [SecretaryCrmController::class, 'store'])->name('secretary.crm.store');
    Route::get('/crm/leads/{lead}/edit', [SecretaryCrmController::class, 'edit'])->name('secretary.crm.edit');
    Route::post('/crm/leads/{lead}', [SecretaryCrmController::class, 'update'])->name('secretary.crm.update');
    Route::get('/crm/leads/{lead}', [SecretaryCrmController::class, 'show'])->name('secretary.crm.show');
    Route::get('/crm/leads/{lead}/quick-view', [SecretaryCrmController::class, 'quickView'])->name('secretary.crm.quickView');
    Route::post('/crm/leads/{lead}/activity', [SecretaryCrmController::class, 'logActivity'])->name('secretary.crm.logActivity');
    Route::post('/crm/leads/{lead}/follow-up', [SecretaryCrmController::class, 'scheduleFollowUp'])->name('secretary.crm.scheduleFollowUp');
    Route::post('/crm/leads/{lead}/status', [SecretaryCrmController::class, 'updateStatus'])->name('secretary.crm.updateStatus');
    Route::post('/crm/leads/{lead}/priority', [SecretaryCrmController::class, 'updatePriority'])->name('secretary.crm.updatePriority');
    Route::post('/crm/leads/{lead}/quick-send', [SecretaryCrmController::class, 'quickSend'])->name('secretary.crm.quickSend');
    Route::post('/crm/follow-ups/{followUp}/complete', [SecretaryCrmController::class, 'completeFollowUp'])->name('secretary.crm.completeFollowUp');
    Route::post('/crm/follow-ups/{followUp}/miss', [SecretaryCrmController::class, 'missFollowUp'])->name('secretary.crm.missFollowUp');
    Route::post('/crm/leads/{lead}/convert', [SecretaryCrmController::class, 'convertToPatient'])->name('secretary.crm.convertToPatient');
    Route::post('/crm/leads/{lead}/lost', [SecretaryCrmController::class, 'markAsLost'])->name('secretary.crm.markAsLost');
    Route::post('/crm/leads/{lead}/reactivate', [SecretaryCrmController::class, 'reactivate'])->name('secretary.crm.reactivate');
    Route::post('/crm/follow-ups/{followUp}/reschedule', [SecretaryCrmController::class, 'rescheduleFollowUp'])->name('secretary.crm.rescheduleFollowUp');
    Route::get('/crm/check-duplicate', [SecretaryCrmController::class, 'checkDuplicate'])->name('secretary.crm.checkDuplicate');
    Route::get('/crm/performance', [SecretaryCrmController::class, 'performance'])->name('secretary.crm.performance');
    Route::get('/crm/calendar', [SecretaryCrmController::class, 'calendar'])->name('secretary.crm.calendar');
    Route::get('/crm/pipeline', [SecretaryCrmController::class, 'pipeline'])->name('secretary.crm.pipeline');
    Route::get('/crm/export', [SecretaryCrmController::class, 'export'])->name('secretary.crm.export');
    Route::get('/crm/import', [SecretaryCrmController::class, 'importPage'])->name('secretary.crm.import');
    Route::post('/crm/import/preview', [SecretaryCrmController::class, 'importPreview'])->name('secretary.crm.importPreview');
    Route::post('/crm/import/process', [SecretaryCrmController::class, 'importProcess'])->name('secretary.crm.importProcess');
    Route::get('/crm/import/template', [SecretaryCrmController::class, 'importTemplate'])->name('secretary.crm.importTemplate');
    Route::get('/crm/templates', [SecretaryCrmController::class, 'templates'])->name('secretary.crm.templates');
    Route::post('/crm/templates/duplicate', [SecretaryCrmController::class, 'duplicateTemplate'])->name('secretary.crm.duplicateTemplate');
    Route::post('/crm/templates/preview', [SecretaryCrmController::class, 'templatePreview'])->name('secretary.crm.templatePreview');
    Route::get('/crm/reports', [SecretaryCrmController::class, 'reports'])->name('secretary.crm.reports');

    // ─── Dental Module ────────────────────────────────────────
    Route::prefix('dental')->middleware('module:dental')->group(function () {
        Route::get('/treatment-plans', [SecretaryDentalController::class, 'treatmentPlans'])->name('secretary.dental.treatment-plans.index');
        Route::get('/treatment-plans/{treatmentPlan}', [SecretaryDentalController::class, 'treatmentPlanShow'])->name('secretary.dental.treatment-plans.show');
        Route::get('/lab-orders', [SecretaryDentalController::class, 'labOrders'])->name('secretary.dental.lab-orders.index');
        Route::post('/lab-orders/{labOrder}/received', [SecretaryDentalController::class, 'markLabOrderReceived'])->name('secretary.dental.lab-orders.received');
        Route::get('/followups', [SecretaryDentalController::class, 'followups'])->name('secretary.dental.followups.index');
        Route::get('/chart/{patient}', [SecretaryDentalController::class, 'patientChart'])->name('secretary.dental.chart');
        Route::get('/patient-risk/{patient}', [SecretaryDentalController::class, 'patientRiskAlert'])->name('secretary.dental.patient-risk');
    });

    // ─── Pediatric Module ──────────────────────────────────────
    Route::prefix('pediatric')->middleware('module:pediatric')->group(function () {
        Route::get('/patients', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'index'])->name('secretary.pediatric.patients.index');
        Route::get('/patients/create', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'create'])->name('secretary.pediatric.patients.create');
        Route::post('/patients', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'store'])->name('secretary.pediatric.patients.store');
        Route::get('/patients/{patient}', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'show'])->name('secretary.pediatric.patients.show');
        Route::get('/patients/{patient}/edit', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'edit'])->name('secretary.pediatric.patients.edit');
        Route::post('/patients/{patient}/update', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'update'])->name('secretary.pediatric.patients.update');
        Route::post('/patients/{patient}/family-history', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'storeFamilyHistory'])->name('secretary.pediatric.patients.familyHistory.store');
        Route::post('/family-history/{familyHistory}/delete', [\App\Http\Controllers\Secretary\SecretaryPediatricPatientController::class, 'destroyFamilyHistory'])->name('secretary.pediatric.patients.familyHistory.destroy');

        Route::get('/visits', [\App\Http\Controllers\Secretary\SecretaryPediatricVisitController::class, 'index'])->name('secretary.pediatric.visits.index');
        Route::get('/visits/{visit}', [\App\Http\Controllers\Secretary\SecretaryPediatricVisitController::class, 'show'])->name('secretary.pediatric.visits.show');

        Route::get('/vaccinations', [\App\Http\Controllers\Secretary\SecretaryPediatricVaccinationController::class, 'index'])->name('secretary.pediatric.vaccinations.index');
        Route::post('/vaccinations/{vaccination}/status', [\App\Http\Controllers\Secretary\SecretaryPediatricVaccinationController::class, 'updateStatus'])->name('secretary.pediatric.vaccinations.updateStatus');
        Route::post('/vaccinations/batch-status', [\App\Http\Controllers\Secretary\SecretaryPediatricVaccinationController::class, 'batchUpdateStatus'])->name('secretary.pediatric.vaccinations.batchUpdateStatus');
    });

    // ─── OB/GYN Module ──────────────────────────────────────
    Route::prefix('obgyn')->middleware('module:obgyn')->group(function () {
        Route::get('/pregnancies', [\App\Http\Controllers\Secretary\SecretaryObgynController::class, 'pregnancies'])->name('secretary.obgyn.pregnancies.index');
        Route::post('/pregnancies', [\App\Http\Controllers\Secretary\SecretaryObgynController::class, 'storePregnancy'])->name('secretary.obgyn.pregnancies.store');
    });

    // ─── Dermatology & Cosmetic (front-desk: admin overview only) ───────
    Route::prefix('derma')->middleware('module:derma')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Secretary\SecretaryDermaController::class, 'index'])
            ->name('secretary.derma.overview');
    });

    // ─── Telemedicine (front-desk: scheduling + payment chase only) ─────
    Route::prefix('telemedicine')->middleware('module:telemedicine')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Secretary\SecretaryTelemedicineController::class, 'index'])
            ->name('secretary.telemedicine.overview');
    });

    // ─── Physiotherapy (front-desk: admin overview only, NO clinical data) ──
    Route::prefix('physiotherapy')->middleware('module:physiotherapy')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Secretary\SecretaryPhysioController::class, 'index'])
            ->name('secretary.physiotherapy.overview');
    });

    // ─── Psychiatry & Neurology (front-desk: admin overview only) ───────
    // STRICTLY administrative — appointments/roster/billing; NO clinical content
    // and NO view_sensitive surface (that stays in the doctor portal).
    foreach (['psychiatry', 'neurology'] as $npm) {
        Route::prefix($npm)->middleware("module:{$npm}")->group(function () use ($npm) {
            Route::get('/overview', [\App\Http\Controllers\Secretary\SecretaryNeuropsychController::class, 'index'])
                ->defaults('npModule', $npm)
                ->name("secretary.{$npm}.overview");
        });
    }

    // ─── Profile ────────────────────────────────────────────
    Route::get('/profile', [SecretaryProfileController::class, 'index'])->name('secretary.profile.index');
    Route::post('/profile/update', [SecretaryProfileController::class, 'update'])->name('secretary.profile.update');
    Route::post('/profile/password', [SecretaryProfileController::class, 'updatePassword'])->name('secretary.profile.password');
});
