<?php

use App\Http\Controllers\Patient\PatientAuthController;
use App\Http\Controllers\Patient\PatientDashboardController;
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

// Protected patient routes (requires authentication + linked patient)
Route::middleware('patient.auth')->group(function () {

    // ─── Dashboard ──────────────────────────────────────────
    Route::get('/', [PatientDashboardController::class, 'index'])->name('patient.dashboard');

    // ─── My Bookings ────────────────────────────────────────
    Route::get('/bookings', [PatientBookingController::class, 'index'])->name('patient.bookings.index');
    Route::get('/bookings/create', [PatientBookingController::class, 'create'])->name('patient.bookings.create');
    Route::post('/bookings', [PatientBookingController::class, 'store'])->name('patient.bookings.store')->middleware('throttle:10,1');
    Route::post('/bookings/{booking}/cancel', [PatientBookingController::class, 'cancel'])->name('patient.bookings.cancel');

    // ─── My Visits ──────────────────────────────────────────
    Route::get('/visits', [PatientVisitController::class, 'index'])->name('patient.visits.index');
    Route::get('/visits/{visit}', [PatientVisitController::class, 'show'])->name('patient.visits.show');

    // ─── My Photos ──────────────────────────────────────────
    Route::get('/photos', [PatientPhotoController::class, 'index'])->name('patient.photos.index');

    // ─── My Invoices ────────────────────────────────────────
    Route::get('/invoices', [PatientInvoiceController::class, 'index'])->name('patient.invoices.index');
    Route::get('/invoices/{invoice}', [PatientInvoiceController::class, 'show'])->name('patient.invoices.show');

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

    // ─── My Profile ─────────────────────────────────────────
    Route::get('/profile', [PatientProfileController::class, 'index'])->name('patient.profile.index');
    Route::post('/profile', [PatientProfileController::class, 'update'])->name('patient.profile.update');
    Route::post('/profile/password', [PatientProfileController::class, 'updatePassword'])->name('patient.profile.password');
});
