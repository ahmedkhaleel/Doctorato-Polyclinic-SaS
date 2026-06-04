<?php

use App\Http\Controllers\Doctor\AuthController;
use App\Http\Controllers\Doctor\DoctorAttendanceController;
use App\Http\Controllers\Doctor\DoctorBookingController;
use App\Http\Controllers\Doctor\DoctorChatController;
use App\Http\Controllers\Doctor\DoctorCommissionController;
use App\Http\Controllers\Doctor\DoctorDashboardController;
use App\Http\Controllers\Doctor\DoctorDentalChartController;
use App\Http\Controllers\Doctor\DoctorDentalFollowupController;
use App\Http\Controllers\Doctor\DoctorDentalTreatmentController;
use App\Http\Controllers\Doctor\DoctorDentalTreatmentPlanController;
use App\Http\Controllers\Doctor\DoctorDentalXrayController;
use App\Http\Controllers\Doctor\DoctorExportController;
use App\Http\Controllers\Doctor\DoctorFavoritePatientController;
use App\Http\Controllers\Doctor\DoctorInventoryController;
use App\Http\Controllers\Doctor\DoctorLeaveController;
use App\Http\Controllers\Doctor\DoctorNotificationController;
use App\Http\Controllers\Doctor\DoctorPatientController;
use App\Http\Controllers\Doctor\DoctorPatientNoteController;
use App\Http\Controllers\Doctor\DoctorPediatricDashboardController;
use App\Http\Controllers\Doctor\DoctorPediatricExtraController;
use App\Http\Controllers\Doctor\DoctorPediatricPatientController;
use App\Http\Controllers\Doctor\DoctorPediatricVisitController;
use App\Http\Controllers\Doctor\DoctorPrescriptionController;
use App\Http\Controllers\Doctor\DoctorProfileController;
use App\Http\Controllers\Doctor\DoctorQueueController;
use App\Http\Controllers\Doctor\DoctorReviewsController;
use App\Http\Controllers\Doctor\DoctorSalarySlipController;
use App\Http\Controllers\Doctor\DoctorVisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Doctor Routes
|--------------------------------------------------------------------------
|
| These routes handle the doctor panel functionality.
| All routes are prefixed with 'doctor'.
|
*/

// Doctor authentication routes (no auth middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('doctor.login');
Route::post('/login', [AuthController::class, 'login'])->name('doctor.authenticate')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('doctor.logout');

// Password reset
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('doctor.password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('doctor.password.email')->middleware('throttle:5,15');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('doctor.password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('doctor.password.update')->middleware('throttle:5,15');

// Protected doctor routes (requires authentication + active doctor)
Route::middleware(['doctor.auth', 'branch.context'])->group(function () {

    // ─── Branch Switching (multi-branch) ─────────────────────
    Route::post('/switch-branch', [\App\Http\Controllers\Admin\BranchSwitchController::class, 'switch'])->name('doctor.switch-branch');

    // ─── Locale Switching ────────────────────────────────────
    Route::post('/switch-locale', function (\Illuminate\Http\Request $request) {
        $locale = $request->input('locale', 'ar');
        session()->put('admin_locale', in_array($locale, ['ar', 'en']) ? $locale : 'ar');

        return redirect()->back();
    })->name('doctor.switchLocale');

    // ─── Dashboard ──────────────────────────────────────────
    Route::get('/', [DoctorDashboardController::class, 'index'])->name('doctor.dashboard');

    // ─── Unified Hub in-app feed (bell) ─────────────────────
    Route::get('/my-notifications', [\App\Http\Controllers\StaffNotificationController::class, 'index'])->defaults('panel', 'doctor')->name('doctor.my-notifications.index');
    Route::get('/my-notifications/bell', [\App\Http\Controllers\StaffNotificationController::class, 'bell'])->name('doctor.my-notifications.bell');
    Route::post('/my-notifications/{id}/read', [\App\Http\Controllers\StaffNotificationController::class, 'markRead'])->whereNumber('id')->name('doctor.my-notifications.read');
    Route::post('/my-notifications/read-all', [\App\Http\Controllers\StaffNotificationController::class, 'markAllRead'])->name('doctor.my-notifications.readAll');

    // ─── Notifications ──────────────────────────────────────
    Route::get('/notifications', [DoctorNotificationController::class, 'index'])->name('doctor.notifications.index');
    Route::get('/notifications/history', [DoctorNotificationController::class, 'history'])->name('doctor.notifications.history');
    Route::post('/notifications/mark-all-read', [DoctorNotificationController::class, 'markAllRead'])->name('doctor.notifications.markAllRead');
    Route::post('/notifications/{id}/read', [DoctorNotificationController::class, 'markRead'])->name('doctor.notifications.markRead');

    // ─── My Queue (Today) ───────────────────────────────────
    Route::get('/queue', [DoctorQueueController::class, 'index'])->name('doctor.queue.index');

    // ─── AI Clinical Assistant (Wave 3) — decision support, gated by ai.doctor ──
    Route::middleware('permission:ai.doctor')->prefix('ai')->group(function () {
        $c = \App\Http\Controllers\Doctor\DoctorAiClinicalController::class;
        Route::get('/', [$c, 'index'])->name('doctor.ai.index');
        Route::post('/summary', [$c, 'summary'])->name('doctor.ai.summary');
        Route::post('/soap', [$c, 'soap'])->name('doctor.ai.soap');
        Route::post('/differential', [$c, 'differential'])->name('doctor.ai.differential');
        Route::post('/icd10', [$c, 'icd10'])->name('doctor.ai.icd10');
        Route::post('/prescription', [$c, 'prescription'])->name('doctor.ai.prescription');
        Route::post('/drug-check', [$c, 'drugCheck'])->name('doctor.ai.drugCheck');
        Route::post('/report', [$c, 'report'])->name('doctor.ai.report');
        Route::post('/vision', [$c, 'vision'])->name('doctor.ai.vision');
        Route::post('/transcribe', [$c, 'transcribe'])->name('doctor.ai.transcribe');
    });

    // ─── My Patients ────────────────────────────────────────
    Route::get('/patients', [DoctorPatientController::class, 'index'])->name('doctor.patients.index');
    Route::get('/patients/favorites', [DoctorFavoritePatientController::class, 'index'])->name('doctor.patients.favorites');
    Route::get('/patients/{patient}', [DoctorPatientController::class, 'show'])->name('doctor.patients.show');
    Route::post('/patients/{patient}/notes', [DoctorPatientController::class, 'updateNotes'])->name('doctor.patients.updateNotes');

    // ─── Patient Quick Notes ────────────────────────────────
    Route::post('/patients/{patient}/notes/store', [DoctorPatientNoteController::class, 'store'])->name('doctor.patients.notes.store');
    Route::post('/patients/{patient}/notes/{note}/delete', [DoctorPatientNoteController::class, 'destroy'])->name('doctor.patients.notes.destroy');
    Route::post('/patients/{patient}/notes/{note}/toggle-pin', [DoctorPatientNoteController::class, 'togglePin'])->name('doctor.patients.notes.togglePin');

    // ─── Patient Favorites ──────────────────────────────────
    Route::post('/patients/{patient}/favorite/toggle', [DoctorFavoritePatientController::class, 'toggle'])->name('doctor.patients.favorite.toggle');

    // ─── My Visits ──────────────────────────────────────────
    Route::get('/visits', [DoctorVisitController::class, 'index'])->name('doctor.visits.index');
    Route::get('/visits/{visit}', [DoctorVisitController::class, 'show'])->name('doctor.visits.show');
    Route::post('/visits/{visit}/start', [DoctorVisitController::class, 'start'])->name('doctor.visits.start');
    Route::post('/visits/{visit}/complete', [DoctorVisitController::class, 'complete'])->name('doctor.visits.complete');
    Route::post('/visits/{visit}/cancel', [DoctorVisitController::class, 'cancel'])->name('doctor.visits.cancel');
    Route::post('/visits/{visit}/diagnosis', [DoctorVisitController::class, 'updateDiagnosis'])->name('doctor.visits.updateDiagnosis');
    Route::post('/visits/{visit}/photos', [DoctorVisitController::class, 'uploadPhoto'])->name('doctor.visits.uploadPhoto');
    Route::post('/patients/{patient}/vitals', [DoctorVisitController::class, 'storeVitals'])->name('doctor.patients.vitals.store');
    Route::post('/visits/{visit}/pediatric-growth', [DoctorPediatricVisitController::class, 'storeGrowthFromVisit'])->name('doctor.visits.growth.store');

    // ─── Prescriptions ──────────────────────────────────────
    Route::get('/prescriptions', [DoctorPrescriptionController::class, 'index'])->name('doctor.prescriptions.index');
    Route::post('/prescriptions', [DoctorPrescriptionController::class, 'store'])->name('doctor.prescriptions.store');
    Route::post('/prescriptions/{prescription}/update', [DoctorPrescriptionController::class, 'update'])->name('doctor.prescriptions.update');
    Route::post('/prescriptions/{prescription}/delete', [DoctorPrescriptionController::class, 'destroy'])->name('doctor.prescriptions.destroy');
    Route::post('/prescriptions/{prescription}/duplicate', [DoctorPrescriptionController::class, 'duplicate'])->name('doctor.prescriptions.duplicate');
    Route::get('/prescriptions/{prescription}/pdf', [DoctorPrescriptionController::class, 'downloadPdf'])->name('doctor.prescriptions.pdf');
    Route::get('/prescriptions/{prescription}/print', [DoctorPrescriptionController::class, 'printPdf'])->name('doctor.prescriptions.print');
    Route::get('/api/medications', [DoctorPrescriptionController::class, 'searchMedications'])->name('doctor.medications.search');
    Route::get('/api/patients', [DoctorPrescriptionController::class, 'searchPatients'])->name('doctor.patients.search');
    // ─── Bookings (read-only) ───────────────────────────────
    Route::get('/bookings', [DoctorBookingController::class, 'index'])->name('doctor.bookings.index');

    // ─── Commission ─────────────────────────────────────────
    // ─── My Reviews (read-only — patient feedback about this doctor)
    Route::get('/reviews', [DoctorReviewsController::class, 'index'])->name('doctor.reviews.index');

    Route::get('/commission', [DoctorCommissionController::class, 'index'])->name('doctor.commission.index')->middleware('module:hr');
    Route::get('/commission/payouts/{payout}', [DoctorCommissionController::class, 'payoutShow'])->name('doctor.commission.payout-show')->middleware('module:hr');
    Route::get('/commission/payouts/{payout}/print', [DoctorCommissionController::class, 'payoutPrint'])->name('doctor.commission.payout-print')->middleware('module:hr');

    // ─── Inventory (read-only) ───────────────────────────────
    Route::get('/inventory', [DoctorInventoryController::class, 'index'])->name('doctor.inventory.index');

    // ─── Chat / Messaging ─────────────────────────────────────
    Route::get('/chat', [DoctorChatController::class, 'index'])->name('doctor.chat.index');
    Route::get('/chat/unread-count', [DoctorChatController::class, 'unreadCount'])->name('doctor.chat.unreadCount')->middleware('throttle:30,1');
    Route::get('/chat/{user}', [DoctorChatController::class, 'show'])->name('doctor.chat.show');
    Route::post('/chat/{user}', [DoctorChatController::class, 'store'])->name('doctor.chat.store')->middleware('throttle:30,1');
    Route::get('/chat/{user}/poll', [DoctorChatController::class, 'poll'])->name('doctor.chat.poll')->middleware('throttle:30,1');
    Route::post('/chat/{user}/mark-read', [DoctorChatController::class, 'markRead'])->name('doctor.chat.markRead')->middleware('throttle:30,1');
    Route::get('/chat/{user}/older', [DoctorChatController::class, 'loadOlder'])->name('doctor.chat.loadOlder')->middleware('throttle:30,1');

    // ─── HR Module (Attendance, Leaves, Salary) ─────────────────
    Route::middleware('module:hr')->group(function () {
        Route::get('/my-attendance', [DoctorAttendanceController::class, 'index'])->name('doctor.my-attendance.index');
        Route::post('/my-attendance/check-in', [DoctorAttendanceController::class, 'checkIn'])->name('doctor.my-attendance.check-in');
        Route::post('/my-attendance/check-out', [DoctorAttendanceController::class, 'checkOut'])->name('doctor.my-attendance.check-out');
        Route::get('/my-leaves', [DoctorLeaveController::class, 'index'])->name('doctor.my-leaves.index');
        Route::post('/my-leaves', [DoctorLeaveController::class, 'store'])->name('doctor.my-leaves.store');
        Route::get('/my-salary-slips', [DoctorSalarySlipController::class, 'index'])->name('doctor.my-salary-slips.index');
        Route::get('/my-salary-slips/{salarySlip}', [DoctorSalarySlipController::class, 'show'])->name('doctor.my-salary-slips.show');
    });

    // ─── Dental Module ────────────────────────────────────────
    Route::prefix('dental')->middleware('module:dental')->group(function () {
        // Dental Chart
        Route::get('/chart-search', [DoctorDentalChartController::class, 'search'])->name('doctor.dental.chart.search');
        Route::get('/chart/{patient}', [DoctorDentalChartController::class, 'show'])->name('doctor.dental.chart.show');
        Route::post('/chart/{patient}/tooth/{toothNumber}', [DoctorDentalChartController::class, 'updateTooth'])->name('doctor.dental.chart.updateTooth');
        Route::post('/chart/{patient}/initialize', [DoctorDentalChartController::class, 'initializeChart'])->name('doctor.dental.chart.initialize');

        // Treatment Plans
        Route::get('/treatment-plans', [DoctorDentalTreatmentPlanController::class, 'index'])->name('doctor.dental.treatment-plans.index');
        Route::post('/treatment-plans', [DoctorDentalTreatmentPlanController::class, 'store'])->name('doctor.dental.treatment-plans.store');
        Route::get('/treatment-plans/{treatmentPlan}', [DoctorDentalTreatmentPlanController::class, 'show'])->name('doctor.dental.treatment-plans.show');
        Route::post('/treatment-plans/{treatmentPlan}/update', [DoctorDentalTreatmentPlanController::class, 'update'])->name('doctor.dental.treatment-plans.update');

        // Treatment Plan Consent
        Route::post('/treatment-plans/{treatmentPlan}/consent/send', [\App\Http\Controllers\Doctor\DoctorTreatmentPlanConsentController::class, 'send'])->name('doctor.dental.consent.send');
        Route::get('/consent/{consent}/pdf', [\App\Http\Controllers\Doctor\DoctorTreatmentPlanConsentController::class, 'downloadPdf'])->name('doctor.dental.consent.pdf');

        // Treatments
        Route::get('/treatments', [DoctorDentalTreatmentController::class, 'index'])->name('doctor.dental.treatments.index');
        Route::post('/treatments', [DoctorDentalTreatmentController::class, 'store'])->name('doctor.dental.treatments.store');
        Route::post('/treatments/{treatment}/update', [DoctorDentalTreatmentController::class, 'update'])->name('doctor.dental.treatments.update');

        // X-rays
        Route::get('/xrays', [DoctorDentalXrayController::class, 'index'])->name('doctor.dental.xrays.index');
        Route::post('/xrays', [DoctorDentalXrayController::class, 'store'])->name('doctor.dental.xrays.store');
        Route::get('/xrays/patient/{patient}', [DoctorDentalXrayController::class, 'patientXrays'])->name('doctor.dental.xrays.patient');

        // Before/After Comparisons
        Route::get('/comparisons', [\App\Http\Controllers\Doctor\DoctorDentalComparisonController::class, 'index'])->name('doctor.dental.comparisons.index');
        Route::post('/comparisons', [\App\Http\Controllers\Doctor\DoctorDentalComparisonController::class, 'store'])->name('doctor.dental.comparisons.store');
        Route::get('/comparisons/{comparison}', [\App\Http\Controllers\Doctor\DoctorDentalComparisonController::class, 'show'])->name('doctor.dental.comparisons.show');

        // Follow-ups
        Route::get('/followups', [DoctorDentalFollowupController::class, 'index'])->name('doctor.dental.followups.index');

        // Dental Prescription Templates
        Route::get('/prescription-templates', [DoctorPrescriptionController::class, 'dentalTemplates'])->name('doctor.dental-prescription-templates.index');
        Route::post('/prescriptions/apply-template/{template}', [DoctorPrescriptionController::class, 'applyDentalTemplate'])->name('doctor.prescriptions.applyDentalTemplate');
    });

    // ─── Pediatric Module ──────────────────────────────────────
    Route::prefix('pediatric')->middleware('module:pediatric')->group(function () {
        Route::get('/', [DoctorPediatricDashboardController::class, 'index'])->name('doctor.pediatric.dashboard');

        // Patients
        Route::get('/patients', [DoctorPediatricPatientController::class, 'index'])->name('doctor.pediatric.patients.index');
        Route::get('/patients/{patient}', [DoctorPediatricPatientController::class, 'show'])->name('doctor.pediatric.patients.show');

        // Growth Records
        Route::post('/patients/{patient}/growth', [DoctorPediatricPatientController::class, 'storeGrowth'])->name('doctor.pediatric.patients.growth.store');
        Route::post('/patients/{patient}/growth/{record}/update', [DoctorPediatricPatientController::class, 'updateGrowth'])->name('doctor.pediatric.patients.growth.update');
        Route::post('/patients/{patient}/growth/{record}/delete', [DoctorPediatricPatientController::class, 'destroyGrowth'])->name('doctor.pediatric.patients.growth.destroy');

        // Vaccinations
        Route::post('/patients/{patient}/vaccination', [DoctorPediatricPatientController::class, 'storeVaccination'])->name('doctor.pediatric.patients.vaccination.store');
        Route::post('/patients/{patient}/vaccinations/initialize', [DoctorPediatricPatientController::class, 'initializeVaccinations'])->name('doctor.pediatric.patients.vaccinations.initialize');

        // Milestones
        Route::post('/patients/{patient}/milestone', [DoctorPediatricPatientController::class, 'updateMilestone'])->name('doctor.pediatric.patients.milestone.update');

        // Allergies
        Route::post('/patients/{patient}/allergy', [DoctorPediatricPatientController::class, 'storeAllergy'])->name('doctor.pediatric.patients.allergy.store');
        Route::post('/patients/{patient}/allergy/{allergy}/toggle', [DoctorPediatricPatientController::class, 'toggleAllergy'])->name('doctor.pediatric.patients.allergy.toggle');
        Route::post('/patients/{patient}/allergy/{allergy}/delete', [DoctorPediatricPatientController::class, 'destroyAllergy'])->name('doctor.pediatric.patients.allergy.destroy');

        // Chronic Conditions
        Route::post('/patients/{patient}/chronic-condition', [DoctorPediatricPatientController::class, 'storeChronicCondition'])->name('doctor.pediatric.patients.chronic.store');
        Route::post('/patients/{patient}/chronic-condition/{condition}/toggle', [DoctorPediatricPatientController::class, 'toggleChronicCondition'])->name('doctor.pediatric.patients.chronic.toggle');
        Route::post('/patients/{patient}/chronic-condition/{condition}/delete', [DoctorPediatricPatientController::class, 'destroyChronicCondition'])->name('doctor.pediatric.patients.chronic.destroy');

        // Nutrition
        Route::post('/patients/{patient}/nutrition', [DoctorPediatricPatientController::class, 'storeNutrition'])->name('doctor.pediatric.patients.nutrition.store');

        // Screening Tests
        Route::post('/patients/{patient}/screening', [DoctorPediatricPatientController::class, 'storeScreening'])->name('doctor.pediatric.patients.screening.store');

        // Visits
        Route::get('/visits', [DoctorPediatricVisitController::class, 'index'])->name('doctor.pediatric.visits.index');
        Route::get('/visits/{visit}', [DoctorPediatricVisitController::class, 'show'])->name('doctor.pediatric.visits.show');

        // Prescriptions
        Route::get('/prescriptions', [DoctorPediatricExtraController::class, 'prescriptions'])->name('doctor.pediatric.prescriptions.index');

        // Well-Child Schedule
        Route::get('/well-child', [DoctorPediatricExtraController::class, 'wellChild'])->name('doctor.pediatric.wellchild.index');
        Route::get('/well-child/{patient}', [DoctorPediatricExtraController::class, 'wellChildPatient'])->name('doctor.pediatric.wellchild.show');
        Route::post('/well-child/{patient}/initialize', [DoctorPediatricExtraController::class, 'initializeWellChild'])->name('doctor.pediatric.wellchild.initialize');
        Route::post('/well-child/{patient}/{wellChild}/update', [DoctorPediatricExtraController::class, 'updateWellChild'])->name('doctor.pediatric.wellchild.update');

        // Reports
        Route::get('/reports', [DoctorPediatricExtraController::class, 'reports'])->name('doctor.pediatric.reports.index');

        // PDF Exports
        Route::get('/patients/{patient}/vaccination-card', [DoctorPediatricExtraController::class, 'vaccinationCardPdf'])->name('doctor.pediatric.patients.vaccinationCard');
        Route::get('/patients/{patient}/growth-report', [DoctorPediatricExtraController::class, 'growthReportPdf'])->name('doctor.pediatric.patients.growthReport');
        Route::get('/patients/{patient}/general-report', [DoctorPediatricExtraController::class, 'generalReportPdf'])->name('doctor.pediatric.patients.generalReport');
        Route::get('/patients/{patient}/school-certificate', [DoctorPediatricExtraController::class, 'schoolCertificatePdf'])->name('doctor.pediatric.patients.schoolCertificate');
        Route::get('/patients/{patient}/referral-letter', [DoctorPediatricExtraController::class, 'referralLetterPdf'])->name('doctor.pediatric.patients.referralLetter');
        Route::get('/patients/{patient}/medical-leave', [DoctorPediatricExtraController::class, 'medicalLeavePdf'])->name('doctor.pediatric.patients.medicalLeave');
    });

    // ─── Dermatology & Cosmetic Module ──────────────────────
    Route::prefix('derma')->middleware('module:derma')->group(function () {
        Route::get('/', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'dashboard'])->name('doctor.derma.dashboard');
        Route::get('/patients', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'patients'])->name('doctor.derma.patients.index');
        Route::get('/patients/{patient}', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'patientShow'])->name('doctor.derma.patients.show');
        Route::post('/patients/{patient}/sessions', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'storeDermaSession'])->name('doctor.derma.sessions.store');
        Route::post('/patients/{patient}/cosmetic-sessions', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'storeCosmeticSession'])->name('doctor.derma.cosmetic-sessions.store');
        Route::post('/patients/{patient}/photos', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'uploadPhoto'])->name('doctor.derma.photos.store')->middleware('throttle:20,1');
        Route::get('/treatment-plans', [\App\Http\Controllers\Doctor\DoctorDermaController::class, 'treatmentPlans'])->name('doctor.derma.treatment-plans.index');
    });

    // ─── OB/GYN Module ──────────────────────────────────────
    Route::prefix('obgyn')->middleware('module:obgyn')->group(function () {
        Route::get('/', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'dashboard'])->name('doctor.obgyn.dashboard');
        Route::get('/pregnancies', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'pregnancies'])->name('doctor.obgyn.pregnancies.index');
        Route::get('/pregnancies/{pregnancy}', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'pregnancyShow'])->name('doctor.obgyn.pregnancies.show');
        Route::post('/pregnancies', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storePregnancy'])->name('doctor.obgyn.pregnancies.store');
        Route::post('/pregnancies/{pregnancy}/antenatal', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storeAntenatalVisit'])->name('doctor.obgyn.antenatal.store');
        Route::post('/pregnancies/{pregnancy}/ultrasound', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storeUltrasound'])->name('doctor.obgyn.ultrasound.store');
        Route::post('/pregnancies/{pregnancy}/lab', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storeLab'])->name('doctor.obgyn.lab.store');
        Route::post('/pregnancies/{pregnancy}/delivery', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storeDelivery'])->name('doctor.obgyn.delivery.store');
        Route::get('/pregnancies/{pregnancy}/antenatal-card', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'antenatalCard'])->name('doctor.obgyn.antenatal-card');
        Route::get('/gynecology', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'gynecology'])->name('doctor.obgyn.gynecology');
        Route::post('/gynecology/pap-smear', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storePapSmear'])->name('doctor.obgyn.pap-smear.store');
        Route::post('/gynecology/contraception', [\App\Http\Controllers\Doctor\DoctorObgynController::class, 'storeContraception'])->name('doctor.obgyn.contraception.store');
    });

    // ─── Psychiatry & Neurology (shared NeuropsychEncounterController) ──
    foreach (['psychiatry', 'neurology'] as $npModule) {
        Route::prefix($npModule)->middleware("module:{$npModule}")->group(function () use ($npModule) {
            Route::get('/encounters', [\App\Http\Controllers\Doctor\NeuropsychEncounterController::class, 'index'])
                ->defaults('npModule', $npModule)->name("doctor.{$npModule}.encounters.index");
            Route::post('/encounters', [\App\Http\Controllers\Doctor\NeuropsychEncounterController::class, 'store'])
                ->defaults('npModule', $npModule)->middleware("permission:{$npModule}.create")->name("doctor.{$npModule}.encounters.store");
            Route::post('/encounters/{encounter}', [\App\Http\Controllers\Doctor\NeuropsychEncounterController::class, 'update'])
                ->defaults('npModule', $npModule)->middleware("permission:{$npModule}.update")->name("doctor.{$npModule}.encounters.update");
            Route::delete('/encounters/{encounter}', [\App\Http\Controllers\Doctor\NeuropsychEncounterController::class, 'destroy'])
                ->defaults('npModule', $npModule)->middleware("permission:{$npModule}.delete")->name("doctor.{$npModule}.encounters.destroy");
        });
    }

    // ─── Online Consultations ──────────────────────────────
    Route::get('/online-consultations', [\App\Http\Controllers\Doctor\OnlineConsultationController::class, 'index'])
        ->name('doctor.online-consultations.index');
    Route::get('/online-consultations/{consultation}/room', [\App\Http\Controllers\Doctor\OnlineConsultationController::class, 'room'])
        ->name('doctor.online-consultations.room');

    // ─── Exports ────────────────────────────────────────────
    Route::get('/export/visits', [DoctorExportController::class, 'exportVisits'])->name('doctor.export.visits');
    Route::get('/export/commissions', [DoctorExportController::class, 'exportCommissions'])->name('doctor.export.commissions')->middleware('module:hr');
    Route::get('/export/salary-slips', [DoctorExportController::class, 'exportSalarySlips'])->name('doctor.export.salary-slips')->middleware('module:hr');

    // ─── My Profile ─────────────────────────────────────────
    Route::get('/profile', [DoctorProfileController::class, 'index'])->name('doctor.profile.index');
    Route::post('/profile/update', [DoctorProfileController::class, 'update'])->name('doctor.profile.update');
    Route::post('/profile/password', [DoctorProfileController::class, 'updatePassword'])->name('doctor.profile.password');
});
