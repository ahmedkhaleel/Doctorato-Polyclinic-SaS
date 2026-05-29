<?php

use App\Http\Controllers\Patient\PatientAuthController;
use App\Http\Controllers\Patient\PatientDashboardController;
use App\Http\Controllers\Patient\PatientDermaController;
use App\Http\Controllers\Patient\PatientCosmeticConsentController;
use App\Http\Controllers\Patient\PatientBookingController;
use App\Http\Controllers\Patient\PatientVisitController;
use App\Http\Controllers\Patient\PatientPhotoController;
use App\Http\Controllers\Patient\PatientInvoiceController;
use App\Http\Controllers\Patient\PatientPrescriptionController;
use App\Http\Controllers\Patient\PatientTreatmentPlanController;
use App\Http\Controllers\Patient\PatientProfileController;
use App\Http\Controllers\Patient\PatientComparisonController;
use App\Http\Controllers\Patient\PatientConsentController;
use App\Http\Controllers\Patient\PatientDentalController;
use App\Http\Controllers\Patient\PatientPediatricController;
use App\Http\Controllers\Patient\PatientDoctorsController;
use App\Http\Controllers\Patient\PatientDocumentController;
use App\Http\Controllers\Patient\PatientExportController;
use App\Http\Controllers\Patient\PatientFeedbackController;
use App\Http\Controllers\Patient\PatientLoyaltyController;
use App\Http\Controllers\Patient\PatientReferralsController;
use App\Http\Controllers\Patient\PatientServicesController;
use App\Http\Controllers\Patient\PatientVitalsController;
use App\Http\Controllers\Patient\PatientActivityController;
use App\Http\Controllers\Patient\OnlineConsultationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient Portal Routes
|--------------------------------------------------------------------------
|
| These routes handle the patient portal functionality.
| All routes are prefixed with '{locale}/patient' where locale is ar|en.
|
*/

// Patient authentication routes (no auth middleware)
Route::get('/login', [PatientAuthController::class, 'showLogin'])->name('patient.login');
Route::post('/login', [PatientAuthController::class, 'login'])->name('patient.authenticate')->middleware('throttle:5,1');
Route::get('/register', [PatientAuthController::class, 'showRegister'])->name('patient.register');
Route::post('/register', [PatientAuthController::class, 'storeRegister'])->name('patient.register.store')->middleware('throttle:5,1');
Route::post('/logout', [PatientAuthController::class, 'logout'])->name('patient.logout');

// Password reset (forgot-password flow)
Route::get('/forgot-password', [PatientAuthController::class, 'showForgotPassword'])->name('patient.password.request');
Route::post('/forgot-password', [PatientAuthController::class, 'sendResetLink'])->name('patient.password.email')->middleware('throttle:5,15');
Route::get('/reset-password/{token}', [PatientAuthController::class, 'showResetFormWithLocale'])->name('patient.password.reset');
Route::post('/reset-password', [PatientAuthController::class, 'resetPassword'])->name('patient.password.update')->middleware('throttle:5,15');

// Protected patient routes (requires authentication + linked patient)
Route::middleware('patient.auth')->group(function () {

    // ─── Dashboard ──────────────────────────────────────────
    Route::get('/', [PatientDashboardController::class, 'index'])->name('patient.dashboard');

    // ─── My Bookings ────────────────────────────────────────
    Route::get('/bookings', [PatientBookingController::class, 'index'])->name('patient.bookings.index');
    Route::get('/bookings/create', [PatientBookingController::class, 'create'])->name('patient.bookings.create');
    Route::post('/bookings', [PatientBookingController::class, 'store'])->name('patient.bookings.store')->middleware('throttle:10,1');
    Route::post('/bookings/{booking}/cancel', [PatientBookingController::class, 'cancel'])->name('patient.bookings.cancel');
    Route::post('/bookings/{booking}/reschedule', [PatientBookingController::class, 'reschedule'])
        ->name('patient.bookings.reschedule')->middleware('throttle:5,1');

    // ─── My Visits ──────────────────────────────────────────
    Route::get('/visits', [PatientVisitController::class, 'index'])->name('patient.visits.index');
    Route::get('/visits/{visit}', [PatientVisitController::class, 'show'])->name('patient.visits.show');

    // ─── My Photos ──────────────────────────────────────────
    Route::get('/photos', [PatientPhotoController::class, 'index'])->name('patient.photos.index');

    // ─── My Invoices ────────────────────────────────────────
    Route::get('/invoices', [PatientInvoiceController::class, 'index'])->name('patient.invoices.index');
    Route::get('/invoices/{invoice}', [PatientInvoiceController::class, 'show'])->name('patient.invoices.show');
    Route::get('/invoices/{invoice}/pdf', [PatientInvoiceController::class, 'downloadPdf'])
        ->name('patient.invoices.pdf')->middleware('throttle:10,1');

    // ─── My Prescriptions ───────────────────────────────────
    Route::get('/prescriptions', [PatientPrescriptionController::class, 'index'])->name('patient.prescriptions.index');
    Route::get('/prescriptions/{prescription}', [PatientPrescriptionController::class, 'show'])->name('patient.prescriptions.show');

    // ─── My Treatment Plans ─────────────────────────────────
    Route::get('/treatment-plans', [PatientTreatmentPlanController::class, 'index'])->name('patient.treatment-plans.index');
    Route::get('/treatment-plans/{treatmentPlan}', [PatientTreatmentPlanController::class, 'show'])->name('patient.treatment-plans.show');

    // ─── Dental ───────────────────────────────────────────────
    Route::middleware('module:dental')->group(function () {
        Route::get('/dental/overview', [PatientDentalController::class, 'overview'])->name('patient.dental.overview');
        Route::get('/dental/chart', [PatientDentalController::class, 'chart'])->name('patient.dental.chart');
        Route::get('/dental/treatments', [PatientDentalController::class, 'treatments'])->name('patient.dental.treatments');
        Route::get('/dental/xrays', [PatientDentalController::class, 'xrays'])->name('patient.dental.xrays');
        Route::get('/dental/treatment-plans', [PatientDentalController::class, 'treatmentPlans'])->name('patient.dental.treatment-plans');
        Route::get('/dental/treatment-plans/{dentalTreatmentPlan}', [PatientDentalController::class, 'treatmentPlanShow'])->name('patient.dental.treatment-plans.show');
        Route::get('/dental/lab-orders', [PatientDentalController::class, 'labOrders'])->name('patient.dental.lab-orders');
        Route::get('/dental/followups', [PatientDentalController::class, 'followups'])->name('patient.dental.followups');

        // Dental Before/After Comparisons
        Route::get('/dental/comparisons', [PatientComparisonController::class, 'index'])->name('patient.dental.comparisons.index');
        Route::get('/dental/comparisons/{comparison}', [PatientComparisonController::class, 'show'])->name('patient.dental.comparisons.show');

        // Dental Consent
        Route::get('/dental/consent/{consent}', [PatientConsentController::class, 'show'])->name('patient.dental.consent.show');
        Route::post('/dental/consent/{consent}/sign', [PatientConsentController::class, 'sign'])->name('patient.dental.consent.sign');
        Route::get('/dental/consent/{consent}/signed', [PatientConsentController::class, 'signed'])->name('patient.dental.consent.signed');
    });

    // ─── Dermatology & Cosmetic ───────────────────────────────
    Route::middleware('module:derma')->group(function () {
        Route::get('/derma', [PatientDermaController::class, 'overview'])->name('patient.derma.overview');
        Route::get('/derma/consents', [PatientCosmeticConsentController::class, 'index'])->name('patient.derma.consents');
        Route::post('/derma/consents/{consent}/sign', [PatientCosmeticConsentController::class, 'sign'])->name('patient.derma.consents.sign')->middleware('throttle:10,1');
    });

    // ─── Pediatric ──────────────────────────────────────────
    Route::middleware('module:pediatric')->group(function () {
        Route::get('/pediatric/overview', [PatientPediatricController::class, 'overview'])->name('patient.pediatric.overview');
        Route::get('/pediatric/vaccinations', [PatientPediatricController::class, 'vaccinations'])->name('patient.pediatric.vaccinations');
        Route::get('/pediatric/growth', [PatientPediatricController::class, 'growth'])->name('patient.pediatric.growth');
        Route::get('/pediatric/visits', [PatientPediatricController::class, 'visits'])->name('patient.pediatric.visits');
        Route::get('/pediatric/vaccination-card', [PatientPediatricController::class, 'vaccinationCard'])->name('patient.pediatric.vaccination-card');
        Route::get('/pediatric/growth-report', [PatientPediatricController::class, 'growthReport'])->name('patient.pediatric.growth-report');
    });

    // ─── Online Consultations ───────────────────────────────
    Route::get('/online-consultations', [OnlineConsultationController::class, 'index'])
        ->name('patient.online-consultations.index');
    Route::get('/online-consultations/doctors', [OnlineConsultationController::class, 'doctors'])
        ->name('patient.online-consultations.doctors');
    Route::get('/online-consultations/book/{docId}', [OnlineConsultationController::class, 'showDoctor'])
        ->where('docId', '[0-9]+')
        ->name('patient.online-consultations.book');
    Route::post('/online-consultations', [OnlineConsultationController::class, 'store'])
        ->name('patient.online-consultations.store')
        ->middleware('throttle:10,1');
    Route::get('/online-consultation/success', [OnlineConsultationController::class, 'success'])
        ->name('patient.online-consultations.success');
    Route::get('/online-consultation/cancelled', [OnlineConsultationController::class, 'cancelled'])
        ->name('patient.online-consultations.cancelled');
    Route::post('/online-consultations/{consultation}/cancel', [OnlineConsultationController::class, 'cancel'])
        ->name('patient.online-consultations.cancel');
    Route::get('/online-consultations/{consultation}/room', [OnlineConsultationController::class, 'room'])
        ->name('patient.online-consultations.room');

    // ─── My File Export (self-service medical record download) ─
    Route::get('/file/download', [PatientExportController::class, 'downloadFullFile'])
        ->name('patient.file.download')->middleware('throttle:5,10');

    // ─── Browse Doctors ─────────────────────────────────────
    Route::get('/doctors', [PatientDoctorsController::class, 'index'])->name('patient.doctors.index');

    // ─── Browse Services ────────────────────────────────────
    Route::get('/services', [PatientServicesController::class, 'index'])->name('patient.services.index');

    // ─── My Vitals (read-only — recorded by staff during visits) ─
    Route::get('/vitals', [PatientVitalsController::class, 'index'])->name('patient.vitals.index');

    // ─── Activity feed (chronological account events) ─────────
    Route::get('/activity', [PatientActivityController::class, 'index'])->name('patient.activity.index');

    // ─── Feedback / Reviews (patient rates their completed visits) ─
    Route::get('/feedback', [PatientFeedbackController::class, 'index'])->name('patient.feedback.index');
    Route::get('/feedback/{visit}', [PatientFeedbackController::class, 'create'])->name('patient.feedback.create');
    Route::post('/feedback/{visit}', [PatientFeedbackController::class, 'store'])
        ->name('patient.feedback.store')->middleware('throttle:10,1');

    // ─── My Documents (self-uploaded medical records + admin-shared) ─
    Route::get('/documents', [PatientDocumentController::class, 'index'])->name('patient.documents.index');
    Route::post('/documents', [PatientDocumentController::class, 'store'])
        ->name('patient.documents.store')->middleware('throttle:20,10');
    Route::get('/documents/{document}/download', [PatientDocumentController::class, 'download'])
        ->name('patient.documents.download');
    Route::delete('/documents/{document}', [PatientDocumentController::class, 'destroy'])
        ->name('patient.documents.destroy');

    // ─── Loyalty Points ─────────────────────────────────────
    Route::get('/loyalty', [PatientLoyaltyController::class, 'index'])->name('patient.loyalty.index');
    Route::post('/loyalty/redeem', [PatientLoyaltyController::class, 'redeem'])
        ->name('patient.loyalty.redeem')->middleware('throttle:5,1');

    // ─── Referrals ──────────────────────────────────────────
    Route::get('/referrals', [PatientReferralsController::class, 'index'])->name('patient.referrals.index');

    // ─── My Profile ─────────────────────────────────────────
    Route::get('/profile', [PatientProfileController::class, 'index'])->name('patient.profile.index');
    Route::post('/profile', [PatientProfileController::class, 'update'])->name('patient.profile.update');
    Route::post('/profile/password', [PatientProfileController::class, 'updatePassword'])->name('patient.profile.password');
    Route::post('/profile/preferences', [PatientProfileController::class, 'updatePreferences'])->name('patient.profile.preferences');
    Route::post('/profile/photo', [PatientProfileController::class, 'uploadPhoto'])
        ->name('patient.profile.photo')->middleware('throttle:6,1');
    Route::post('/profile/photo/delete', [PatientProfileController::class, 'deletePhoto'])
        ->name('patient.profile.photo.delete');
});
