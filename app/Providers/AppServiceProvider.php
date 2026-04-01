<?php

namespace App\Providers;

use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\InvoiceOverdue;
use App\Events\LeadConverted;
use App\Events\PatientRegistered;
use App\Events\PaymentReceived;
use App\Events\VisitCancelled;
use App\Events\VisitCompleted;
use App\Listeners\CreatePatientFromLead;
use App\Listeners\LinkPendingBookings;
use App\Listeners\LogPaymentReceived;
use App\Listeners\LogVisitCancelled;
use App\Listeners\LogVisitCompleted;
use App\Listeners\SendBookingConfirmedSms;
use App\Listeners\SendBookingNotification;
use App\Listeners\SendOverdueReminder;
use App\Listeners\SendPaymentSms;
use App\Listeners\SendVisitCompletedSms;
use App\Listeners\SendWelcomeSms;
use App\Listeners\UpdateBookingStatusOnCompletion;
use App\Listeners\UpdateCrmOnPayment;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Setting;
use App\Models\Visit;
use App\Policies\BookingPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PatientPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\VisitPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ─── Events & Listeners ───────────────────────────
        // Booking lifecycle
        Event::listen(BookingCreated::class, SendBookingNotification::class);
        Event::listen(BookingConfirmed::class, SendBookingConfirmedSms::class);

        // Visit lifecycle
        Event::listen(VisitCompleted::class, LogVisitCompleted::class);
        Event::listen(VisitCompleted::class, SendVisitCompletedSms::class);
        Event::listen(VisitCompleted::class, UpdateBookingStatusOnCompletion::class);
        Event::listen(VisitCancelled::class, LogVisitCancelled::class);

        // Payment lifecycle
        Event::listen(PaymentReceived::class, LogPaymentReceived::class);
        Event::listen(PaymentReceived::class, SendPaymentSms::class);
        Event::listen(PaymentReceived::class, UpdateCrmOnPayment::class);

        // Patient lifecycle
        Event::listen(PatientRegistered::class, SendWelcomeSms::class);
        Event::listen(PatientRegistered::class, LinkPendingBookings::class);

        // Invoice lifecycle
        Event::listen(InvoiceOverdue::class, SendOverdueReminder::class);

        // CRM lifecycle
        Event::listen(LeadConverted::class, CreatePatientFromLead::class);

        // ─── Authorization Policies ────────────────────────
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Visit::class, VisitPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);

        // Preload all settings into memory to avoid N+1 queries
        // (128+ Setting::get() calls across the app hit DB individually without this)
        try {
            Setting::preload();
        } catch (\Throwable) {
            // Silently ignore if DB not available (e.g., during migrations)
        }
    }
}
