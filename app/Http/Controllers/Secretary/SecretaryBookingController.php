<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingConsent;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\BookingWorkflowService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryBookingController extends BaseSecretaryController
{
    public function __construct(protected BookingWorkflowService $bookingWorkflowService) {}

    public function index(Request $request): Response
    {
        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:unconfirmed,confirmed,in_progress,completed,cancelled',
            'source' => 'nullable|string|in:website,secretary,walk_in,phone',
            'module' => 'nullable|string|in:derma,dental,pediatric,obgyn,psychiatry,neurology',
        ]);

        $query = Booking::with(['patient', 'bookingServices.service', 'invoice'])
            ->withCount('bookingServices');

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%"));
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($source = $filters['source'] ?? null) {
            $query->where('source', $source);
        }

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status', 'source', 'module']),
        ]);
    }

    public function create(): Response
    {
        $serviceCategories = ServiceCategory::whereHas('services', function ($q) {
            $q->where('status', 'active')->where('bookable', true);
        })->with(['services' => function ($q) {
            $q->where('status', 'active')->where('bookable', true)->orderBy('display_order');
        }])->orderBy('display_order')->get();
        $services = Service::active()->bookable()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'price', 'price_after_discount', 'default_sessions', 'session_duration_minutes', 'category_id', 'module']);
        $doctors = Doctor::active()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'doctor_type', 'dermatology_fee', 'cosmetic_fee', 'module', 'dental_consultation_fee', 'dental_service_fee', 'obgyn_consultation_fee', 'psychiatry_consultation_fee', 'neurology_consultation_fee']);
        $doctorSchedules = DoctorSchedule::active()
            ->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']);

        return Inertia::render('Secretary/Bookings/Create', [
            'serviceCategories' => $serviceCategories,
            'services' => $services,
            'doctors' => $doctors,
            'doctorSchedules' => $doctorSchedules,
            'defaultDermatologyFee' => (float) Setting::get('default_dermatology_fee', 0),
            'defaultCosmeticFee' => (float) Setting::get('default_cosmetic_fee', 0),
            'dermatologyConsultantFee' => (float) Setting::get('dermatology_consultant_fee', 0),
            'dermatologySpecialistFee' => (float) Setting::get('dermatology_specialist_fee', 0),
            'cosmeticConsultationFee' => (float) Setting::get('cosmetic_consultation_fee', 0),
            'followupFee' => (float) Setting::get('followup_fee', 0),
            'followupWindowDays' => (int) Setting::get('followup_window_days', 15),
            'dentalConsultantFee' => (float) Setting::get('dental_consultant_fee', 0),
            'dentalSpecialistFee' => (float) Setting::get('dental_specialist_fee', 0),
            'pediatricConsultantFee' => (float) Setting::get('pediatric_consultant_fee', 0),
            'pediatricSpecialistFee' => (float) Setting::get('pediatric_specialist_fee', 0),
            // Module-level consultation fees (fallback when a doctor has no
            // specialty fee set) — seeded in module_settings.
            'obgynConsultationFee' => (float) \App\Services\ModuleManager::getSetting('obgyn', 'consultation_fee', 0),
            'psychiatryConsultationFee' => (float) \App\Services\ModuleManager::getSetting('psychiatry', 'consultation_fee', 0),
            'neurologyConsultationFee' => (float) \App\Services\ModuleManager::getSetting('neurology', 'consultation_fee', 0),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Get patient info for booking record
        $patient = Patient::find($data['patient_id']);
        $data['full_name'] = $patient->full_name;
        $data['phone'] = $patient->phone;

        $result = $this->bookingWorkflowService->createFromSecretary($data, $request->user()->id);

        AuditLogger::log('created', $result['booking'], ['source' => 'secretary']);

        // Link booking to existing lead if phone/email matches
        LeadService::linkBookingToLead($result['booking']);

        return redirect()->route('secretary.bookings.show', $result['booking']->id)
            ->with('success', $this->msg('Booking created successfully.', 'تم إنشاء الحجز بنجاح.'));
    }

    public function show(Booking $booking): Response
    {
        $booking->load([
            'patient',
            'service',
            'doctor',
            'bookingServices.service',
            'bookingServices.doctor',
            'bookingServices.appointments.doctor',
            'bookingServices.appointments.visit',
            'appointments.doctor',
            'appointments.visit',
            'invoice.items',
            'invoice.payments.paymentMethod',
            'invoice.payments.receiver',
            'creator',
            'consents.uploader',
        ]);

        $paymentMethods = PaymentMethod::active()->get();

        // For confirming website bookings and retouch form
        $patients = null;
        $services = null;
        $serviceCategories = null;

        // Doctors and schedules are needed for both unconfirmed (confirm form) and retouch form
        $doctors = Doctor::active()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'doctor_type', 'module', 'dermatology_fee', 'cosmetic_fee', 'dental_consultation_fee', 'dental_service_fee', 'obgyn_consultation_fee', 'psychiatry_consultation_fee', 'neurology_consultation_fee']);
        $doctorSchedules = DoctorSchedule::active()
            ->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']);

        if ($booking->status === 'unconfirmed') {
            $patients = Patient::active()->orderBy('full_name')->get(['id', 'file_number', 'full_name', 'phone']);
            $serviceCategories = ServiceCategory::whereHas('services', function ($q) {
                $q->where('status', 'active')->where('bookable', true);
            })->with(['services' => function ($q) {
                $q->where('status', 'active')->where('bookable', true)->orderBy('display_order');
            }])->orderBy('display_order')->get();
            $services = Service::active()->bookable()->orderBy('display_order')
                ->get(['id', 'name_ar', 'name_en', 'price', 'price_after_discount', 'default_sessions', 'session_duration_minutes', 'category_id']);
        }

        // Check follow-up eligibility for dermatology bookings
        $followUpInfo = null;
        if ($booking->patient_id && $booking->booking_type === 'dermatology_consultation') {
            $followUpInfo = $this->bookingWorkflowService->checkFollowUpEligibility($booking->patient_id);
        }

        // Medical risk flags for dental bookings (shown at check-in)
        $medicalRiskFlags = [];
        if ($booking->patient_id && $booking->module === 'dental') {
            $medicalRiskFlags = $booking->patient->getDentalRiskFlags();
        }

        return Inertia::render('Secretary/Bookings/Show', [
            'booking' => $booking,
            'paymentMethods' => $paymentMethods,
            'patients' => $patients,
            'serviceCategories' => $serviceCategories,
            'services' => $services,
            'doctors' => $doctors,
            'doctorSchedules' => $doctorSchedules,
            'defaultDermatologyFee' => (float) Setting::get('default_dermatology_fee', 0),
            'defaultCosmeticFee' => (float) Setting::get('default_cosmetic_fee', 0),
            'dermatologyConsultantFee' => (float) Setting::get('dermatology_consultant_fee', 0),
            'dermatologySpecialistFee' => (float) Setting::get('dermatology_specialist_fee', 0),
            'cosmeticConsultationFee' => (float) Setting::get('cosmetic_consultation_fee', 0),
            'followUpInfo' => $followUpInfo,
            'followupFee' => (float) Setting::get('followup_fee', 0),
            'medicalRiskFlags' => $medicalRiskFlags,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:unconfirmed,confirmed,in_progress,completed,cancelled',
            'admin_notes' => 'nullable|string',
        ]);

        // Enforce valid state transitions to prevent data corruption
        $allowedTransitions = [
            'unconfirmed' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],  // Terminal state
            'cancelled' => ['unconfirmed'],  // Allow re-opening cancelled bookings
        ];

        $currentStatus = $booking->status;
        $newStatus = $data['status'];

        if ($newStatus !== $currentStatus && ! in_array($newStatus, $allowedTransitions[$currentStatus] ?? [])) {
            return redirect()->back()->with('error', $this->msg("Cannot change status from '{$currentStatus}' to '{$newStatus}'.", "لا يمكن تغيير الحالة من '{$currentStatus}' إلى '{$newStatus}'."));
        }

        $data['is_read'] = true;
        $booking->update($data);

        AuditLogger::log('updated_status', $booking);

        // Email the patient on cancellation. Confirmed status has its own
        // SecretaryBookingController::confirm() flow that already dispatches
        // BookingConfirmed; this branch only fires for direct cancel here.
        if ($newStatus === 'cancelled' && $currentStatus !== 'cancelled') {
            \App\Events\BookingCancelled::dispatch($booking, $data['admin_notes'] ?? null);
        }

        return redirect()->back()->with('success', $this->msg('Booking status updated.', 'تم تحديث حالة الحجز.'));
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'unconfirmed') {
            return redirect()->back()->with('error', $this->msg('This booking is already confirmed.', 'هذا الحجز مؤكد بالفعل.'));
        }

        $bookingType = $booking->booking_type ?? $request->input('booking_type', 'service');
        $isService = $bookingType === 'service';

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'services' => 'required|array|min:1',
            'services.*.service_id' => $isService ? 'required|exists:services,id' : 'nullable',
            'services.*.doctor_id' => 'nullable|exists:doctors,id',
            'services.*.sessions_count' => 'required|integer|min:1|max:50',
            'services.*.unit_price' => 'required|numeric|min:0',
            'services.*.discount_per_session' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:500',
            'services.*.appointments' => 'required|array|min:1',
            'services.*.appointments.*.doctor_id' => 'nullable|exists:doctors,id',
            'services.*.appointments.*.date' => 'required|date|after_or_equal:today',
            'services.*.appointments.*.start_time' => 'required|date_format:H:i',
            'services.*.appointments.*.end_time' => 'required|date_format:H:i|after:services.*.appointments.*.start_time',
        ]);

        $this->bookingWorkflowService->confirmWebsiteBooking($booking, $data, $request->user()->id);

        AuditLogger::log('confirmed', $booking);

        return redirect()->back()->with('success', $this->msg('Booking confirmed successfully.', 'تم تأكيد الحجز بنجاح.'));
    }

    public function processPayment(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->bookingWorkflowService->processPayment($booking, $data, $request->user()->id);

        AuditLogger::log('payment_recorded', $booking, [
            'amount' => $data['amount'],
            'visits_created' => count($result['visits_created']),
        ]);

        $visitCount = count($result['visits_created']);
        $successMsg = $this->msg(
            'Payment recorded successfully.'.($visitCount > 0 ? " {$visitCount} visit(s) created for today's appointments." : ''),
            'تم تسجيل الدفعة بنجاح.'.($visitCount > 0 ? " تم إنشاء {$visitCount} زيارة(زيارات) لمواعيد اليوم." : ''),
        );

        return redirect()->back()->with('success', $successMsg);
    }

    public function addRetouchSession(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'booking_service_id' => 'required|exists:booking_services,id',
            'doctor_id' => 'required|exists:doctors,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
        ]);

        try {
            $appointment = $this->bookingWorkflowService->addRetouchSession($booking, $data, $request->user()->id);

            AuditLogger::log('retouch_added', $booking, [
                'appointment_id' => $appointment->id,
                'appointment_date' => $data['appointment_date'],
            ]);

            return redirect()->back()->with('success', $this->msg('Retouch session added successfully.', 'تم إضافة جلسة المتابعة بنجاح.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', $this->msg(
                'Failed to add retouch session. Please try again.',
                'فشل إضافة جلسة المتابعة. يرجى المحاولة مرة أخرى.'
            ));
        }
    }

    public function uploadConsent(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate([
            'consents' => 'required|array|min:1|max:10',
            'consents.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($request->file('consents') as $file) {
            $path = $file->store("consents/{$booking->id}", 'public');

            BookingConsent::create([
                'booking_id' => $booking->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        AuditLogger::log('consent_uploaded', $booking, [
            'count' => count($request->file('consents')),
        ]);

        return redirect()->back()->with('success', $this->msg('Consent document(s) uploaded successfully.', 'تم رفع مستند(ات) الموافقة بنجاح.'));
    }

    public function deleteConsent(Request $request, Booking $booking, BookingConsent $consent): RedirectResponse
    {
        if ((int) $consent->booking_id !== (int) $booking->id) {
            abort(403);
        }

        Storage::disk('public')->delete($consent->file_path);
        $consent->delete();

        AuditLogger::log('consent_deleted', $booking, [
            'file' => $consent->original_name,
        ]);

        return redirect()->back()->with('success', $this->msg('Consent document deleted.', 'تم حذف مستند الموافقة.'));
    }

    public function checkInAppointment(Request $request, Booking $booking, BookingAppointment $appointment): RedirectResponse
    {
        if ((int) $appointment->booking_id !== (int) $booking->id) {
            abort(403);
        }

        try {
            $visit = $this->bookingWorkflowService->checkInAppointment($appointment, $request->user()->id);

            AuditLogger::log('appointment_checked_in', $booking, [
                'appointment_id' => $appointment->id,
                'visit_id' => $visit->id,
            ]);

            return redirect()->back()->with('success', $this->msg('Patient checked in successfully. Visit created.', 'تم تسجيل دخول المريض بنجاح. تم إنشاء الزيارة.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rescheduleAppointment(Request $request, Booking $booking, BookingAppointment $appointment): RedirectResponse
    {
        if ((int) $appointment->booking_id !== (int) $booking->id) {
            abort(403);
        }

        $data = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        try {
            $this->bookingWorkflowService->rescheduleAppointment($appointment, $data);

            AuditLogger::log('appointment_rescheduled', $booking, [
                'appointment_id' => $appointment->id,
                'new_date' => $data['appointment_date'],
                'new_time' => $data['start_time'],
                'new_doctor' => $data['doctor_id'],
            ]);

            return redirect()->back()->with('success', $this->msg('Appointment rescheduled successfully.', 'تم إعادة جدولة الموعد بنجاح.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFollowUp(Request $request): JsonResponse
    {
        $request->validate(['patient_id' => 'required|exists:patients,id']);
        $info = $this->bookingWorkflowService->checkFollowUpEligibility((int) $request->input('patient_id'));

        return response()->json(['follow_up' => $info]);
    }

    public function printReceipt(Booking $booking): Response
    {
        $booking->load([
            'patient',
            'bookingServices.service',
            'invoice.payments.paymentMethod',
            'invoice.payments.receiver',
            'invoice.items',
        ]);

        return Inertia::render('Secretary/Bookings/PrintReceipt', [
            'booking' => $booking,
        ]);
    }

    public function printPaymentReceipt(Booking $booking, Payment $payment): Response
    {
        $booking->load([
            'patient',
            'bookingServices.service',
            'invoice.items',
        ]);

        $payment->load(['paymentMethod', 'receiver']);

        // Get all payments for this invoice to show payment history
        $allPayments = $booking->invoice
            ? $booking->invoice->payments()->with(['paymentMethod', 'receiver'])->orderBy('payment_date')->get()
            : collect();

        return Inertia::render('Secretary/Bookings/PrintPaymentReceipt', [
            'booking' => $booking,
            'payment' => $payment,
            'allPayments' => $allPayments,
        ]);
    }
}
