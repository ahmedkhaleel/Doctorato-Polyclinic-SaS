<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PackageBundle;
use App\Models\PackageBundleBooking;
use App\Models\PackageBundleBookingAppointment;
use App\Models\PackageBundleBookingService;
use App\Models\Payment;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class PackageBundleWorkflowService
{
    public function __construct(
        protected TimeSlotService $timeSlotService,
        protected PromoCodeService $promoCodeService,
    ) {}

    /**
     * Create a bundle booking from admin panel.
     */
    public function createFromAdmin(array $data, int $userId): array
    {
        return $this->createBundleBooking($data, 'admin', $userId);
    }

    /**
     * Create a bundle booking from secretary panel.
     */
    public function createFromSecretary(array $data, int $userId): array
    {
        return $this->createBundleBooking($data, 'secretary', $userId);
    }

    /**
     * Create a bundle booking from the website (status: pending, no appointments created).
     */
    public function createFromWebsite(array $data): PackageBundleBooking
    {
        return DB::transaction(function () use ($data) {
            $bundle = PackageBundle::findOrFail($data['package_bundle_id']);

            $bundleBooking = PackageBundleBooking::create([
                'booking_number' => PackageBundleBooking::generateBookingNumber(),
                'package_bundle_id' => $bundle->id,
                'patient_id' => $data['patient_id'],
                'status' => 'pending',
                'total_price' => $bundle->total_price,
                'total_paid' => 0,
                'balance_due' => $bundle->total_price,
                'source' => 'website',
                'notes' => $data['notes'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
            ]);

            return $bundleBooking;
        });
    }

    /**
     * Core method: create bundle booking with services and appointments.
     */
    protected function createBundleBooking(array $data, string $source, int $userId): array
    {
        return DB::transaction(function () use ($data, $source, $userId) {
            $bundle = PackageBundle::with('services.service')->findOrFail($data['package_bundle_id']);

            // 1. Create PackageBundleBooking
            $bundleBooking = PackageBundleBooking::create([
                'booking_number' => PackageBundleBooking::generateBookingNumber(),
                'package_bundle_id' => $bundle->id,
                'patient_id' => $data['patient_id'],
                'receptionist_id' => $userId,
                'status' => 'confirmed',
                'total_price' => $bundle->total_price,
                'total_paid' => 0,
                'balance_due' => $bundle->total_price,
                'source' => $source,
                'notes' => $data['notes'] ?? null,
            ]);

            // 2. Create booking services with doctor assignments
            $servicesData = $data['services'] ?? [];
            foreach ($bundle->services as $bundleService) {
                $serviceConfig = collect($servicesData)->firstWhere('package_bundle_service_id', $bundleService->id);
                $doctorId = $serviceConfig['doctor_id'] ?? ($data['default_doctor_id'] ?? null);

                if (!$doctorId) {
                    throw new \RuntimeException("Doctor must be assigned for service: {$bundleService->service->name_en}");
                }

                $bookingService = PackageBundleBookingService::create([
                    'package_bundle_booking_id' => $bundleBooking->id,
                    'package_bundle_service_id' => $bundleService->id,
                    'service_id' => $bundleService->service_id,
                    'doctor_id' => $doctorId,
                    'sessions_count' => $bundleService->sessions_count,
                    'completed_sessions' => 0,
                    'bundle_price' => $bundleService->bundle_price,
                    'discount_percentage' => $bundleService->discount_percentage,
                    'status' => 'pending',
                ]);

                // 3. Create appointments for each session of this service
                $appointments = $serviceConfig['appointments'] ?? [];
                foreach ($appointments as $index => $appt) {
                    PackageBundleBookingAppointment::create([
                        'package_bundle_booking_id' => $bundleBooking->id,
                        'package_bundle_booking_service_id' => $bookingService->id,
                        'doctor_id' => $appt['doctor_id'] ?? $doctorId,
                        'appointment_date' => $appt['date'],
                        'start_time' => $appt['start_time'],
                        'end_time' => $appt['end_time'],
                        'session_number' => $index + 1,
                        'status' => 'scheduled',
                    ]);
                }
            }

            // 4. Generate invoice
            $invoice = $this->generateInvoice($bundleBooking, $userId);

            // 5. Apply promo code if present
            if (!empty($data['promo_code'])) {
                $discountCode = $this->promoCodeService->resolveCode($data['promo_code']);
                if ($discountCode) {
                    $validation = $this->promoCodeService->validate(
                        code: $data['promo_code'],
                        bookingType: 'package',
                        packageBundleId: $bundle->id,
                        amount: (float) $invoice->total,
                        patientId: $data['patient_id'],
                    );

                    if ($validation['valid']) {
                        $this->promoCodeService->applyToInvoice(
                            discountCode: $discountCode,
                            invoice: $invoice,
                            patientId: $data['patient_id'],
                            packageBundleBookingId: $bundleBooking->id,
                        );
                    }
                }
            }

            return [
                'bundleBooking' => $bundleBooking->fresh([
                    'bundleServices.service',
                    'bundleServices.doctor',
                    'bundleServices.appointments',
                    'patient',
                    'packageBundle',
                ]),
                'invoice' => $invoice,
            ];
        });
    }

    /**
     * Create visits for today's scheduled appointments (auto on payment).
     */
    public function createVisitsForTodayAppointments(PackageBundleBooking $bundleBooking, int $userId): array
    {
        $appointments = PackageBundleBookingAppointment::where('package_bundle_booking_id', $bundleBooking->id)
            ->whereDate('appointment_date', today())
            ->whereNull('visit_id')
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->with('bundleBookingService')
            ->get();

        $visits = [];

        foreach ($appointments as $appointment) {
            $visit = Visit::create([
                'patient_id' => $bundleBooking->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'receptionist_id' => $userId,
                'package_bundle_booking_id' => $bundleBooking->id,
                'package_bundle_booking_service_id' => $appointment->package_bundle_booking_service_id,
                'package_bundle_booking_appointment_id' => $appointment->id,
                'visit_type' => 'session',
                'service_id' => $appointment->bundleBookingService->service_id ?? null,
                'session_number' => $appointment->session_number,
                'status' => 'waiting',
                'visit_date' => today(),
                'scheduled_time' => $appointment->start_time,
            ]);

            $appointment->update([
                'visit_id' => $visit->id,
                'status' => 'checked_in',
            ]);

            $visits[] = $visit;
        }

        return $visits;
    }

    /**
     * Check in a specific appointment (manual check-in by staff).
     */
    public function checkInAppointment(PackageBundleBookingAppointment $appointment, int $userId): Visit
    {
        if ($appointment->visit_id) {
            throw new \RuntimeException('This appointment already has a visit linked.');
        }

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'])) {
            throw new \RuntimeException('Cannot check in an appointment with status: ' . $appointment->status);
        }

        $bundleBooking = $appointment->bundleBooking;

        if (!in_array($bundleBooking->status, ['confirmed', 'in_progress'])) {
            throw new \RuntimeException('Bundle booking is not in an active state.');
        }

        $visit = Visit::create([
            'patient_id' => $bundleBooking->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'receptionist_id' => $userId,
            'package_bundle_booking_id' => $bundleBooking->id,
            'package_bundle_booking_service_id' => $appointment->package_bundle_booking_service_id,
            'package_bundle_booking_appointment_id' => $appointment->id,
            'visit_type' => 'session',
            'service_id' => $appointment->bundleBookingService->service_id ?? null,
            'session_number' => $appointment->session_number,
            'status' => 'waiting',
            'visit_date' => today(),
            'scheduled_time' => $appointment->start_time,
        ]);

        $appointment->update([
            'visit_id' => $visit->id,
            'status' => 'checked_in',
        ]);

        return $visit;
    }

    /**
     * Reschedule an appointment (change date/time/doctor).
     */
    public function rescheduleAppointment(PackageBundleBookingAppointment $appointment, array $data): PackageBundleBookingAppointment
    {
        if ($appointment->visit_id) {
            throw new \RuntimeException('Cannot reschedule an appointment that already has a visit.');
        }

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'])) {
            throw new \RuntimeException('Cannot reschedule an appointment with status: ' . $appointment->status);
        }

        $doctorId = $data['doctor_id'] ?? $appointment->doctor_id;

        // Check slot availability
        if (!$this->timeSlotService->isSlotAvailable($doctorId, $data['date'], $data['start_time'], $data['end_time'], null, $appointment->id)) {
            throw new \RuntimeException('The selected time slot is not available.');
        }

        $appointment->update([
            'doctor_id' => $doctorId,
            'appointment_date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 'scheduled',
        ]);

        return $appointment->fresh();
    }

    /**
     * Add a retouch session for a bundle service.
     */
    public function addRetouchSession(PackageBundleBooking $bundleBooking, array $data, int $userId): PackageBundleBookingAppointment
    {
        $bookingService = PackageBundleBookingService::where('package_bundle_booking_id', $bundleBooking->id)
            ->where('id', $data['package_bundle_booking_service_id'])
            ->firstOrFail();

        // Increment sessions count
        $bookingService->increment('sessions_count');

        // Reopen service and booking if they were completed
        if ($bookingService->status === 'completed') {
            $bookingService->update(['status' => 'in_progress']);
        }

        if ($bundleBooking->status === 'completed') {
            $bundleBooking->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);
        }

        // Create the retouch appointment
        $appointment = PackageBundleBookingAppointment::create([
            'package_bundle_booking_id' => $bundleBooking->id,
            'package_bundle_booking_service_id' => $bookingService->id,
            'doctor_id' => $data['doctor_id'],
            'appointment_date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'session_number' => $bookingService->sessions_count,
            'status' => 'scheduled',
            'is_retouch' => true,
        ]);

        return $appointment;
    }

    /**
     * Generate invoice for the bundle booking.
     */
    public function generateInvoice(PackageBundleBooking $bundleBooking, int $userId): Invoice
    {
        $bundleBooking->load(['bundleServices.service', 'packageBundle']);
        $bundle = $bundleBooking->packageBundle;

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $bundleBooking->patient_id,
            'package_bundle_booking_id' => $bundleBooking->id,
            'subtotal' => $bundle->original_price,
            'discount_amount' => max(0, $bundle->original_price - $bundle->total_price),
            'tax_amount' => 0,
            'total' => $bundle->total_price,
            'notes' => "Package Bundle: {$bundle->name_en}",
            'created_by' => $userId,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        // Create invoice items for each service in the bundle
        foreach ($bundleBooking->bundleServices as $bs) {
            $service = $bs->service;
            $descEn = ($service->name_en ?? 'Service') . " ({$bs->sessions_count} sessions)";
            $descAr = ($service->name_ar ?? 'خدمة') . " ({$bs->sessions_count} جلسات)";

            $originalPerSession = $service ? $service->price : 0;
            $bundlePerSession = $bs->sessions_count > 0 ? $bs->bundle_price / $bs->sessions_count : 0;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => $descEn,
                'description_ar' => $descAr,
                'quantity' => $bs->sessions_count,
                'unit_price' => $bundlePerSession,
                'discount' => ($originalPerSession - $bundlePerSession) * $bs->sessions_count,
                'total' => $bs->bundle_price,
            ]);
        }

        return $invoice;
    }

    /**
     * Process payment for a bundle booking.
     */
    public function processPayment(PackageBundleBooking $bundleBooking, array $paymentData, int $userId): array
    {
        return DB::transaction(function () use ($bundleBooking, $paymentData, $userId) {
            $invoice = $bundleBooking->invoice;

            if (!$invoice) {
                throw new \RuntimeException('Bundle booking has no associated invoice.');
            }

            // Record payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'patient_id' => $bundleBooking->patient_id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'amount' => $paymentData['amount'],
                'payment_date' => now()->toDateString(),
                'reference_number' => $paymentData['reference_number'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'received_by' => $userId,
            ]);

            // Update invoice status
            $invoice->recalculateStatus();

            // Update bundle booking payment tracking
            $bundleBooking->recalculateBalance();

            $visits = [];

            // Move to in_progress if first payment
            if ($bundleBooking->status === 'confirmed') {
                $bundleBooking->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);

                // Activate pending services
                $bundleBooking->bundleServices()
                    ->where('status', 'pending')
                    ->update(['status' => 'in_progress']);

                // Auto-create visits for today's scheduled appointments
                $visits = $this->createVisitsForTodayAppointments($bundleBooking, $userId);
            }

            return [
                'payment' => $payment->load('paymentMethod'),
                'invoice' => $invoice->fresh(),
                'visits' => $visits,
            ];
        });
    }

    /**
     * Handle visit completion for a bundle service.
     * Called by VisitWorkflowService when a bundle-linked visit is completed.
     */
    public function handleVisitCompleted(Visit $visit): void
    {
        if (!$visit->package_bundle_booking_service_id) {
            return;
        }

        $bookingService = PackageBundleBookingService::find($visit->package_bundle_booking_service_id);
        if (!$bookingService) {
            return;
        }

        // Update linked appointment status to completed
        if ($visit->package_bundle_booking_appointment_id) {
            PackageBundleBookingAppointment::where('id', $visit->package_bundle_booking_appointment_id)
                ->update(['status' => 'completed']);
        }

        // Increment completed sessions
        $bookingService->increment('completed_sessions');

        // Check if all sessions for this service are completed
        if ($bookingService->completed_sessions >= $bookingService->sessions_count) {
            $bookingService->update(['status' => 'completed']);
        }

        // Check if all services in the bundle are completed
        $bundleBooking = $bookingService->bundleBooking;
        if ($bundleBooking) {
            $allCompleted = $bundleBooking->bundleServices()
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->doesntExist();

            if ($allCompleted) {
                $bundleBooking->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle visit cancellation for a bundle service.
     */
    public function handleVisitCancelled(Visit $visit): void
    {
        if (!$visit->package_bundle_booking_service_id) {
            return;
        }

        $bookingService = PackageBundleBookingService::find($visit->package_bundle_booking_service_id);
        if (!$bookingService) {
            return;
        }

        // Revert linked appointment status and clear visit link
        if ($visit->package_bundle_booking_appointment_id) {
            PackageBundleBookingAppointment::where('id', $visit->package_bundle_booking_appointment_id)
                ->update([
                    'status' => 'scheduled',
                    'visit_id' => null,
                ]);
        }

        // Decrement completed sessions if the visit was completed
        if ($bookingService->completed_sessions > 0) {
            $bookingService->decrement('completed_sessions');
        }

        // Revert service status if it was marked completed
        if ($bookingService->status === 'completed') {
            $bookingService->update(['status' => 'in_progress']);
        }

        // Revert bundle booking status if it was marked completed
        $bundleBooking = $bookingService->bundleBooking;
        if ($bundleBooking && $bundleBooking->status === 'completed') {
            $bundleBooking->update([
                'status' => 'in_progress',
                'completed_at' => null,
            ]);
        }
    }

    /**
     * Cancel an entire bundle booking.
     */
    public function cancelBooking(PackageBundleBooking $bundleBooking): void
    {
        DB::transaction(function () use ($bundleBooking) {
            // Cancel all pending/waiting visits
            Visit::where('package_bundle_booking_id', $bundleBooking->id)
                ->whereIn('status', ['waiting', 'in_progress'])
                ->update(['status' => 'cancelled']);

            // Cancel all non-completed appointments
            PackageBundleBookingAppointment::where('package_bundle_booking_id', $bundleBooking->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update(['status' => 'cancelled']);

            // Cancel all pending services
            $bundleBooking->bundleServices()
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['status' => 'cancelled']);

            // Cancel the bundle booking
            $bundleBooking->update(['status' => 'cancelled']);

            // Cancel the invoice if unpaid
            $invoice = $bundleBooking->invoice;
            if ($invoice && $invoice->status === 'unpaid') {
                $invoice->update(['status' => 'cancelled']);
            }
        });
    }
}
