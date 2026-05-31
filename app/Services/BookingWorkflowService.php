<?php

namespace App\Services;

use App\Events\BookingConfirmed;
use App\Events\BookingCreated;
use App\Events\PaymentReceived;
use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Visit;
use App\Notifications\NewBookingNotification;
use App\Notifications\NewVisitNotification;
use App\Services\LeadService;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingWorkflowService
{
    public function __construct(
        protected TimeSlotService $timeSlotService,
        protected PromoCodeService $promoCodeService,
    ) {}

    /**
     * Create a booking from the website (status: unconfirmed).
     */
    public function createFromWebsite(array $data): Booking
    {
        // Branch selection: run the whole creation pinned to the chosen branch so
        // the booking, its number prefix and appointments all inherit it. Recurse
        // once with branch_id cleared to avoid a loop. (Validated as an active
        // branch upstream in BookingRequest.)
        if (! empty($data['branch_id'])) {
            $branchId = (int) $data['branch_id'];
            $data['branch_id'] = null;

            return app(\App\Services\Branch\BranchContext::class)
                ->runForBranch($branchId, fn () => $this->createFromWebsite($data));
        }

        // Determine module from booking type if not explicitly set
        $module = $data['module'] ?? 'derma';
        if (in_array($data['booking_type'] ?? '', ['dental_consultation', 'dental_service'])) {
            $module = 'dental';
        } elseif (in_array($data['booking_type'] ?? '', ['pediatric_consultation', 'pediatric_service'])) {
            $module = 'pediatric';
        }

        $booking = Booking::create([
            'booking_number' => Booking::generateBookingNumber(),
            'source' => 'website',
            'module' => $module,
            'booking_type' => $data['booking_type'] ?? null,
            'status' => 'unconfirmed',
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null,
            'notes' => $data['notes'] ?? null,
            'promo_code' => $data['promo_code'] ?? null,
        ]);

        // Auto-create or link to a CRM lead
        LeadService::createFromWebsiteBooking($booking);

        // Fire event for listeners (email notification, CRM, etc.)
        BookingCreated::dispatch($booking);

        return $booking;
    }

    /**
     * Create a booking from the secretary (status: confirmed) with services and appointments.
     */
    public function createFromSecretary(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            // Auto-detect module from doctor or service
            $module = $this->detectModule($data);

            // 1. Create the booking
            $booking = new Booking([
                'booking_number' => Booking::generateBookingNumber(),
                'source' => $data['source'] ?? 'secretary',
                'module' => $module,
                'booking_type' => $data['booking_type'] ?? 'service',
                'status' => 'confirmed',
                'patient_id' => $data['patient_id'],
                'full_name' => $data['full_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
            ]);
            $booking->created_by = $userId;
            $booking->save();

            // 2. Create booking services
            $totalAmount = 0;
            foreach ($data['services'] as $serviceData) {
                $unitPrice = (float) $serviceData['unit_price'];
                $discount = (float) ($serviceData['discount_per_session'] ?? 0);
                $sessions = (int) ($serviceData['sessions_count'] ?? 1);
                $totalPrice = ($unitPrice - $discount) * $sessions;
                $totalAmount += $totalPrice;

                $bookingService = BookingService::create([
                    'booking_id' => $booking->id,
                    'service_id' => $serviceData['service_id'] ?? null,
                    'doctor_id' => $serviceData['doctor_id'] ?? null,
                    'sessions_count' => $sessions,
                    'unit_price' => $unitPrice,
                    'discount_per_session' => $discount,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'notes' => $serviceData['notes'] ?? null,
                ]);

                // 3. Create appointments for each session
                if (!empty($serviceData['appointments'])) {
                    foreach ($serviceData['appointments'] as $index => $appt) {
                        BookingAppointment::create([
                            'booking_id' => $booking->id,
                            'booking_service_id' => $bookingService->id,
                            'doctor_id' => $appt['doctor_id'] ?? $serviceData['doctor_id'],
                            'appointment_date' => $appt['date'],
                            'start_time' => $appt['start_time'],
                            'end_time' => $appt['end_time'],
                            'session_number' => $index + 1,
                            'status' => 'scheduled',
                        ]);
                    }
                }
            }

            // 4. Generate invoice
            $invoice = $this->generateBookingInvoice($booking, $userId);
            $booking->update(['invoice_id' => $invoice->id]);

            // 5. Apply promo code if present
            if ($booking->promo_code) {
                $discountCode = $this->promoCodeService->resolveCode($booking->promo_code);
                if ($discountCode) {
                    $validation = $this->promoCodeService->validate(
                        code: $booking->promo_code,
                        bookingType: 'service',
                        serviceId: $booking->service_id,
                        amount: (float) $invoice->subtotal,
                        patientId: $booking->patient_id,
                    );

                    if ($validation['valid']) {
                        $this->promoCodeService->applyToInvoice(
                            discountCode: $discountCode,
                            invoice: $invoice,
                            patientId: $booking->patient_id,
                            bookingId: $booking->id,
                        );
                    }
                }
            }

            // Notify assigned doctors about the new booking
            $freshBooking = $booking->fresh(['bookingServices.service', 'bookingServices.doctor.user', 'appointments.doctor', 'invoice', 'patient']);

            $notifiedDoctorIds = [];
            foreach ($freshBooking->bookingServices as $bs) {
                if ($bs->doctor_id && $bs->doctor?->user && !in_array($bs->doctor_id, $notifiedDoctorIds)) {
                    try {
                        $bs->doctor->user->notify(new NewBookingNotification($freshBooking));
                        $notifiedDoctorIds[] = $bs->doctor_id;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Failed to notify doctor #{$bs->doctor_id} about booking: " . $e->getMessage());
                    }
                }
            }

            // Fire event — listeners handle SMS, logging, CRM, etc.
            BookingConfirmed::dispatch($freshBooking);

            return [
                'booking' => $freshBooking,
                'invoice' => $invoice,
            ];
        });
    }

    /**
     * Confirm a website booking: link to patient, add services, create appointments, generate invoice.
     */
    public function confirmWebsiteBooking(Booking $booking, array $data, int $userId): array
    {
        return DB::transaction(function () use ($booking, $data, $userId) {
            // 1. Link to patient
            $booking->update([
                'patient_id' => $data['patient_id'],
                'status' => 'confirmed',
            ]);
            $booking->created_by = $userId;
            $booking->save();

            // 2. Create booking services
            $totalAmount = 0;
            foreach ($data['services'] as $serviceData) {
                $unitPrice = (float) $serviceData['unit_price'];
                $discount = (float) ($serviceData['discount_per_session'] ?? 0);
                $sessions = (int) ($serviceData['sessions_count'] ?? 1);
                $totalPrice = ($unitPrice - $discount) * $sessions;
                $totalAmount += $totalPrice;

                $bookingService = BookingService::create([
                    'booking_id' => $booking->id,
                    'service_id' => $serviceData['service_id'] ?? null,
                    'doctor_id' => $serviceData['doctor_id'] ?? null,
                    'sessions_count' => $sessions,
                    'unit_price' => $unitPrice,
                    'discount_per_session' => $discount,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'notes' => $serviceData['notes'] ?? null,
                ]);

                // 3. Create appointments
                if (!empty($serviceData['appointments'])) {
                    foreach ($serviceData['appointments'] as $index => $appt) {
                        BookingAppointment::create([
                            'booking_id' => $booking->id,
                            'booking_service_id' => $bookingService->id,
                            'doctor_id' => $appt['doctor_id'] ?? $serviceData['doctor_id'],
                            'appointment_date' => $appt['date'],
                            'start_time' => $appt['start_time'],
                            'end_time' => $appt['end_time'],
                            'session_number' => $index + 1,
                            'status' => 'scheduled',
                        ]);
                    }
                }
            }

            // 4. Generate invoice
            $invoice = $this->generateBookingInvoice($booking, $userId);
            $booking->update(['invoice_id' => $invoice->id]);

            // 5. Apply promo code if present
            if ($booking->promo_code) {
                $discountCode = $this->promoCodeService->resolveCode($booking->promo_code);
                if ($discountCode) {
                    $validation = $this->promoCodeService->validate(
                        code: $booking->promo_code,
                        bookingType: 'service',
                        serviceId: $booking->service_id,
                        amount: (float) $invoice->subtotal,
                        patientId: $booking->patient_id,
                    );

                    if ($validation['valid']) {
                        $this->promoCodeService->applyToInvoice(
                            discountCode: $discountCode,
                            invoice: $invoice,
                            patientId: $booking->patient_id,
                            bookingId: $booking->id,
                        );
                    }
                }
            }

            $freshBooking = $booking->fresh(['bookingServices.service', 'appointments.doctor', 'invoice', 'patient']);

            // SMS: Send booking confirmation to patient
            // Fire event — listeners handle SMS, logging, etc.
            BookingConfirmed::dispatch($freshBooking);

            return [
                'booking' => $freshBooking,
                'invoice' => $invoice->fresh(),
            ];
        });
    }

    /**
     * Process payment for a booking: record payment, update invoice, auto-create visits for today's appointments.
     */
    public function processPayment(Booking $booking, array $paymentData, int $userId): array
    {
        return DB::transaction(function () use ($booking, $paymentData, $userId) {
            $invoice = $booking->invoice ?? Invoice::find($booking->invoice_id);

            if (!$invoice) {
                throw new \RuntimeException('Booking has no associated invoice.');
            }

            // 1. Record payment
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'patient_id' => $booking->patient_id,
                'payment_method_id' => $paymentData['payment_method_id'],
                'amount' => $paymentData['amount'],
                'payment_date' => now()->toDateString(),
                'reference_number' => $paymentData['reference_number'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'received_by' => $userId,
            ]);

            // 2. Update invoice status
            $invoice->recalculateStatus();

            // 3. Update booking status to in_progress
            if ($booking->status === 'confirmed') {
                $booking->update(['status' => 'in_progress']);
            }

            // 4. Update booking services to in_progress
            $booking->bookingServices()
                ->where('status', 'pending')
                ->update(['status' => 'in_progress']);

            // 5. Auto-create visits for today's appointments that don't have a visit yet
            $visits = $this->createVisitsForTodayAppointments($booking, $userId);

            // Fire event — listeners handle logging, CRM, etc.
            PaymentReceived::dispatch($payment, $invoice->fresh());

            return [
                'payment' => $payment->load('paymentMethod'),
                'invoice' => $invoice->fresh(),
                'visits_created' => $visits,
            ];
        });
    }

    /**
     * Create visits for today's appointments that don't have a visit yet.
     */
    public function createVisitsForTodayAppointments(Booking $booking, int $userId): array
    {
        $todayAppointments = $booking->appointments()
            ->whereDate('appointment_date', today())
            ->whereNull('visit_id')
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->with('bookingService.service')
            ->get();

        $visits = [];

        // Determine visit_type and consultation_type based on booking_type
        $bookingType = $booking->booking_type;
        $visitType = 'session';
        $consultationType = null;

        if ($bookingType === 'dermatology_consultation') {
            $visitType = 'consultation';
            $consultationType = 'dermatology';
        } elseif ($bookingType === 'cosmetic_consultation') {
            $visitType = 'consultation';
            $consultationType = 'cosmetic';
        } elseif ($bookingType === 'pediatric_consultation') {
            $visitType = 'consultation';
            $consultationType = 'pediatric';
        } elseif ($bookingType === 'dental_consultation') {
            $visitType = 'consultation';
            $consultationType = 'dental';
        }

        foreach ($todayAppointments as $appointment) {
            $bookingService = $appointment->bookingService;

            // For retouch appointments, set visit_type to 'follow_up'
            $apptVisitType = $appointment->is_retouch ? 'follow_up' : $visitType;
            $apptConsultationType = $appointment->is_retouch ? null : $consultationType;

            $visit = Visit::create([
                'patient_id' => $booking->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'receptionist_id' => $userId,
                'booking_id' => $booking->id,
                'booking_appointment_id' => $appointment->id,
                'module' => $booking->module ?? 'derma',
                'visit_type' => $apptVisitType,
                'consultation_type' => $apptConsultationType,
                'service_id' => $bookingService->service_id ?? null,
                'session_number' => $appointment->session_number,
                'status' => 'waiting',
                'visit_date' => today(),
                'scheduled_time' => $appointment->start_time,
            ]);

            // Link visit to appointment
            $appointment->update([
                'visit_id' => $visit->id,
                'status' => 'checked_in',
            ]);

            // Notify the doctor about the new visit
            $visit->load(['patient', 'service']);
            $doctor = $visit->doctor;
            if ($doctor?->user) {
                try {
                    $doctor->user->notify(new NewVisitNotification($visit));
                } catch (\Throwable $e) {
                    // Don't fail the booking flow if notification fails
                    \Illuminate\Support\Facades\Log::warning("Failed to notify doctor #{$doctor->id}: " . $e->getMessage());
                }
            }

            $visits[] = $visit;
        }

        return $visits;
    }

    /**
     * Generate a single invoice for all booking services.
     */
    public function generateBookingInvoice(Booking $booking, int $userId): Invoice
    {
        $booking->load('bookingServices.service');

        $subtotal = $booking->bookingServices->sum('total_price');

        $invoice = new Invoice([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => now()->toDateString(),
            'patient_id' => $booking->patient_id,
            'booking_id' => $booking->id,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'created_by' => $userId,
        ]);
        $invoice->paid_amount = 0;
        $invoice->status = 'unpaid';
        $invoice->save();

        // Create invoice items for each booking service
        foreach ($booking->bookingServices as $bs) {
            $service = $bs->service;

            // Determine description based on whether this is a consultation or service
            if ($service) {
                $descAr = $service->name_ar ?? 'Service';
                $descEn = $service->name_en ?? 'Service';
            } else {
                // Consultation without a linked service
                $descEn = match ($booking->booking_type) {
                    'dermatology_consultation' => 'Dermatology Consultation',
                    'pediatric_consultation' => 'Pediatric Consultation',
                    'dental_consultation' => 'Dental Consultation',
                    default => 'Cosmetic Consultation',
                };
                $descAr = match ($booking->booking_type) {
                    'dermatology_consultation' => 'كشف جلدية',
                    'pediatric_consultation' => 'كشف أطفال',
                    'dental_consultation' => 'كشف أسنان',
                    default => 'كشف تجميل',
                };
            }

            if ($bs->sessions_count > 1) {
                $descAr .= " ({$bs->sessions_count} sessions)";
                $descEn .= " ({$bs->sessions_count} sessions)";
            }

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_ar' => $descAr,
                'description_en' => $descEn,
                'quantity' => $bs->sessions_count,
                'unit_price' => $bs->unit_price - $bs->discount_per_session,
                'discount' => $bs->discount_per_session * $bs->sessions_count,
                'total' => $bs->total_price,
            ]);
        }

        // Auto-apply insurance coverage if patient has active verified insurance
        app(\App\Services\InsuranceCoverageService::class)->applyToInvoice($invoice);

        return $invoice;
    }

    /**
     * Add a free retouch session to an existing booking service.
     * Reopens the booking/service if already completed.
     */
    public function addRetouchSession(Booking $booking, array $data, int $userId): BookingAppointment
    {
        return DB::transaction(function () use ($booking, $data, $userId) {
            $bookingService = BookingService::findOrFail($data['booking_service_id']);

            // Ensure this booking service belongs to this booking
            if ($bookingService->booking_id !== $booking->id) {
                throw new \RuntimeException('Booking service does not belong to this booking.');
            }

            // Calculate end_time
            $startTime = $data['start_time'];
            $service = $bookingService->service;
            $duration = $service?->session_duration_minutes ?? 30;
            $endTime = $data['end_time'] ?? null;

            if (!$endTime && $startTime) {
                $parts = explode(':', $startTime);
                $totalMin = (int) $parts[0] * 60 + (int) $parts[1] + $duration;
                $endTime = sprintf('%02d:%02d', intdiv($totalMin, 60), $totalMin % 60);
            }

            // Create retouch appointment
            $sessionNumber = $bookingService->sessions_count + 1;

            $appointment = BookingAppointment::create([
                'booking_id' => $booking->id,
                'booking_service_id' => $bookingService->id,
                'doctor_id' => $data['doctor_id'],
                'appointment_date' => $data['appointment_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'session_number' => $sessionNumber,
                'status' => 'scheduled',
                'is_retouch' => true,
                'notes' => $data['notes'] ?? 'Retouch session',
            ]);

            // Increment sessions_count on the booking service
            $bookingService->increment('sessions_count');

            // Reopen booking service if completed
            if ($bookingService->status === 'completed') {
                $bookingService->update(['status' => 'in_progress']);
            }

            // Reopen booking if completed
            if ($booking->status === 'completed') {
                $booking->update(['status' => 'in_progress']);
            }

            return $appointment->load('doctor');
        });
    }

    /**
     * Handle booking service/appointment tracking after a visit is completed.
     * Called by VisitWorkflowService::complete()
     */
    public function handleVisitCompleted(Visit $visit): void
    {
        if (!$visit->booking_appointment_id) {
            return;
        }

        $appointment = BookingAppointment::find($visit->booking_appointment_id);
        if (!$appointment) {
            return;
        }

        // Update appointment status
        $appointment->update(['status' => 'completed']);

        // Increment completed sessions on BookingService
        $bookingService = $appointment->bookingService;
        if ($bookingService) {
            $bookingService->increment('completed_sessions');

            // Check if all sessions completed
            if ($bookingService->completed_sessions >= $bookingService->sessions_count) {
                $bookingService->update(['status' => 'completed']);
            }
        }

        // Check if all booking services are completed
        $booking = $appointment->booking;
        if ($booking) {
            $allCompleted = $booking->bookingServices()
                ->where('status', '!=', 'completed')
                ->where('status', '!=', 'cancelled')
                ->doesntExist();

            if ($allCompleted) {
                $booking->update(['status' => 'completed']);
            }
        }
    }

    /**
     * Handle booking service/appointment tracking after a visit is cancelled.
     * Called by VisitWorkflowService::cancel()
     */
    public function handleVisitCancelled(Visit $visit): void
    {
        if (!$visit->booking_appointment_id) {
            return;
        }

        $appointment = BookingAppointment::find($visit->booking_appointment_id);
        if (!$appointment) {
            return;
        }

        // Revert appointment status
        $appointment->update([
            'status' => 'scheduled',
            'visit_id' => null,
        ]);

        // Decrement completed sessions if it was completed
        $bookingService = $appointment->bookingService;
        if ($bookingService && $visit->status === 'cancelled') {
            if ($bookingService->completed_sessions > 0) {
                $bookingService->decrement('completed_sessions');
            }

            if ($bookingService->status === 'completed') {
                $bookingService->update(['status' => 'in_progress']);
            }
        }

        // Revert booking status
        $booking = $appointment->booking;
        if ($booking && $booking->status === 'completed') {
            $booking->update(['status' => 'in_progress']);
        }
    }

    /**
     * Reschedule an appointment: change date, time, and/or doctor.
     * Only allowed for appointments that don't have a visit yet.
     */
    public function rescheduleAppointment(BookingAppointment $appointment, array $data): BookingAppointment
    {
        if ($appointment->visit_id) {
            throw new \RuntimeException('Cannot reschedule an appointment that already has a visit.');
        }

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'])) {
            throw new \RuntimeException('Cannot reschedule a completed, cancelled, or no-show appointment.');
        }

        $updateData = [];

        if (isset($data['appointment_date'])) {
            $updateData['appointment_date'] = $data['appointment_date'];
        }

        if (isset($data['start_time'])) {
            $updateData['start_time'] = $data['start_time'];
        }

        if (isset($data['end_time'])) {
            $updateData['end_time'] = $data['end_time'];
        }

        if (isset($data['doctor_id'])) {
            $updateData['doctor_id'] = $data['doctor_id'];
        }

        $appointment->update($updateData);

        return $appointment->fresh('doctor');
    }

    /**
     * Check in a specific appointment: create a visit for it regardless of date.
     * Used by admin/secretary to manually create a visit when the patient arrives.
     */
    public function checkInAppointment(BookingAppointment $appointment, int $userId): Visit
    {
        $booking = $appointment->booking;

        if ($appointment->visit_id) {
            throw new \RuntimeException('This appointment already has a visit.');
        }

        if (in_array($appointment->status, ['cancelled', 'no_show', 'completed'])) {
            throw new \RuntimeException('Cannot check in a cancelled, no-show, or completed appointment.');
        }

        if ($booking->status !== 'in_progress') {
            throw new \RuntimeException('Booking must be in progress (paid) before checking in.');
        }

        $appointment->load('bookingService.service');
        $bookingService = $appointment->bookingService;

        // Determine visit_type and consultation_type
        $bookingType = $booking->booking_type;
        $visitType = 'session';
        $consultationType = null;

        if ($bookingType === 'dermatology_consultation') {
            $visitType = 'consultation';
            $consultationType = 'dermatology';
        } elseif ($bookingType === 'cosmetic_consultation') {
            $visitType = 'consultation';
            $consultationType = 'cosmetic';
        } elseif ($bookingType === 'pediatric_consultation') {
            $visitType = 'consultation';
            $consultationType = 'pediatric';
        } elseif ($bookingType === 'dental_consultation') {
            $visitType = 'consultation';
            $consultationType = 'dental';
        }

        if ($appointment->is_retouch) {
            $visitType = 'follow_up';
            $consultationType = null;
        }

        $visit = Visit::create([
            'patient_id' => $booking->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'receptionist_id' => $userId,
            'booking_id' => $booking->id,
            'booking_appointment_id' => $appointment->id,
            'module' => $booking->module ?? 'derma',
            'visit_type' => $visitType,
            'consultation_type' => $consultationType,
            'service_id' => $bookingService->service_id ?? null,
            'session_number' => $appointment->session_number,
            'status' => 'waiting',
            'visit_date' => today(),
            'scheduled_time' => $appointment->start_time,
        ]);

        $appointment->update([
            'visit_id' => $visit->id,
            'status' => 'checked_in',
        ]);

        // Notify the doctor about the new visit
        $visit->load(['patient', 'service']);
        $doctor = $visit->doctor;
        if ($doctor?->user) {
            try {
                $doctor->user->notify(new NewVisitNotification($visit));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to notify doctor #{$doctor->id}: " . $e->getMessage());
            }
        }

        return $visit;
    }

    /**
     * Check if a patient qualifies for a dermatology follow-up at reduced price.
     */
    public function checkFollowUpEligibility(int $patientId): ?array
    {
        $windowDays = (int) Setting::get('followup_window_days', 15);
        $followUpFee = (float) Setting::get('followup_fee', 0);

        if ($windowDays <= 0 || $followUpFee <= 0) {
            return null;
        }

        $cutoffDate = now()->subDays($windowDays);

        $recentDermaVisit = Visit::where('patient_id', $patientId)
            ->where('visit_type', 'consultation')
            ->where('consultation_type', 'dermatology')
            ->where('status', 'completed')
            ->where('visit_date', '>=', $cutoffDate)
            ->latest('visit_date')
            ->first();

        if ($recentDermaVisit) {
            return [
                'eligible' => true,
                'follow_up_fee' => $followUpFee,
                'window_days' => $windowDays,
                'original_visit_date' => $recentDermaVisit->visit_date->format('Y-m-d'),
                'original_visit_id' => $recentDermaVisit->id,
            ];
        }

        return null;
    }

    /**
     * Detect module from booking data (doctor or service).
     */
    private function detectModule(array $data): string
    {
        // Check booking type first
        if (in_array($data['booking_type'] ?? '', ['dental_consultation', 'dental_service'])) {
            return 'dental';
        }
        if (in_array($data['booking_type'] ?? '', ['pediatric_consultation', 'pediatric_service'])) {
            return 'pediatric';
        }

        // Check first service's doctor module
        $firstService = $data['services'][0] ?? null;
        if ($firstService) {
            if (! empty($firstService['doctor_id'])) {
                $doctor = \App\Models\Doctor::find($firstService['doctor_id']);
                if ($doctor && in_array($doctor->module, ['dental', 'pediatric'])) {
                    return $doctor->module;
                }
            }
            if (! empty($firstService['service_id'])) {
                $service = \App\Models\Service::find($firstService['service_id']);
                if ($service && in_array($service->module, ['dental', 'pediatric'])) {
                    return $service->module;
                }
            }
        }

        return 'derma';
    }
}
