<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAppointment;
use App\Models\BookingConsent;
use App\Models\BookingService;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\Visit;
use App\Observers\CrmEventObserver;
use App\Http\Requests\StoreBookingRequest;
use App\Services\AuditLogger;
use App\Services\BookingWorkflowService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function __construct(protected BookingWorkflowService $bookingWorkflowService) {}

    public function index(Request $request): Response
    {
        // Validate filter inputs
        $filters = $request->validate([
            'module' => 'nullable|string|in:derma,dental,pediatric',
            'status' => 'nullable|string|in:unconfirmed,confirmed,in_progress,completed,cancelled,new,contacted',
            'source' => 'nullable|string|in:website,secretary,walk_in,phone',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:100',
        ]);

        $bookings = Booking::with(['patient', 'service', 'doctor', 'bookingServices.service', 'invoice'])
            ->withCount('bookingServices')
            ->when($filters['module'] ?? null, function ($query, $module) {
                $query->where('module', $module);
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['source'] ?? null, function ($query, $source) {
                $query->where('source', $source);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('booking_number', 'like', "%{$search}%")
                      ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('file_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['status', 'source', 'date_from', 'date_to', 'search', 'module']),
        ]);
    }

    public function create(): Response
    {
        $patients = Patient::active()->orderBy('full_name')->get(['id', 'file_number', 'full_name', 'phone']);
        $serviceCategories = ServiceCategory::whereHas('services', function ($q) {
            $q->where('status', 'active')->where('bookable', true);
        })->with(['services' => function ($q) {
            $q->where('status', 'active')->where('bookable', true)->orderBy('display_order');
        }])->orderBy('display_order')->get();
        $services = Service::active()->bookable()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'price', 'price_after_discount', 'default_sessions', 'session_duration_minutes', 'category_id', 'module']);
        $doctors = Doctor::active()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'doctor_type', 'dermatology_fee', 'cosmetic_fee', 'module', 'dental_consultation_fee', 'dental_service_fee']);
        $doctorSchedules = DoctorSchedule::active()
            ->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']);

        return Inertia::render('Admin/Bookings/Create', [
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
            'dentalConsultantFee' => (float) Setting::get('dental_consultant_fee', 0),
            'dentalSpecialistFee' => (float) Setting::get('dental_specialist_fee', 0),
            'followupFee' => (float) Setting::get('followup_fee', 0),
            'followupWindowDays' => (int) Setting::get('followup_window_days', 15),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $patient = Patient::find($data['patient_id']);
        $data['full_name'] = $patient->full_name;
        $data['phone'] = $patient->phone;
        $data['source'] = 'admin';

        $result = $this->bookingWorkflowService->createFromSecretary($data, $request->user()->id);

        AuditLogger::log('created', $result['booking'], ['source' => 'admin']);

        // Link booking to existing lead if phone/email matches
        LeadService::linkBookingToLead($result['booking']);

        return redirect()->route('admin.bookings.show', $result['booking']->id)
            ->with('success', 'Booking created successfully.');
    }

    public function show(Request $request, Booking $booking): Response
    {
        if (!$booking->is_read) {
            $booking->update(['is_read' => true]);
        }

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

        $patients = null;
        $services = null;
        $serviceCategories = null;

        // Doctors and schedules are needed for unconfirmed (confirm form) and retouch form
        $doctors = Doctor::active()->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'doctor_type', 'module', 'dermatology_fee', 'cosmetic_fee', 'dental_consultation_fee', 'dental_service_fee']);
        $doctorSchedules = DoctorSchedule::active()
            ->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']);

        $canEditServices = $request->user()->can('bookings.edit_services');

        if ($booking->status === 'unconfirmed' || $canEditServices) {
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

        return Inertia::render('Admin/Bookings/Show', [
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
            'defaultDentalFee' => (float) Setting::get('dental_consultant_fee', 0),
            'dentalConsultantFee' => (float) Setting::get('dental_consultant_fee', 0),
            'dentalSpecialistFee' => (float) Setting::get('dental_specialist_fee', 0),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:unconfirmed,confirmed,in_progress,completed,cancelled,new,contacted',
            'admin_notes' => 'nullable|string',
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:255',
            'preferred_date' => 'nullable|date',
            'preferred_time' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'service_id' => 'nullable|exists:services,id',
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $oldStatus = $booking->status;

        // Validate status transition if status is changing
        if ($data['status'] !== $oldStatus) {
            $allowedTransitions = [
                'unconfirmed' => ['confirmed', 'cancelled'],
                'confirmed' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => ['unconfirmed'],
                'new' => ['contacted', 'confirmed', 'cancelled'],
                'contacted' => ['confirmed', 'cancelled'],
            ];

            $allowed = $allowedTransitions[$oldStatus] ?? [];
            if (!in_array($data['status'], $allowed)) {
                return redirect()->back()->with('error', "Cannot transition booking from '{$oldStatus}' to '{$data['status']}'.");
            }
        }

        $booking->update($data);

        AuditLogger::log('updated', $booking);

        // CRM: Update linked lead when booking status changes
        if ($oldStatus !== $booking->status) {
            try {
                CrmEventObserver::onBookingStatusChanged($booking, $oldStatus, $booking->status);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('CRM booking event failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Booking updated successfully.');
    }

    public function confirm(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== 'unconfirmed') {
            return redirect()->back()->with('error', 'This booking is already confirmed.');
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

        // CRM: Notify lead of booking confirmation
        try {
            CrmEventObserver::onBookingStatusChanged($booking, 'unconfirmed', 'confirmed');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CRM confirm event failed', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Booking confirmed successfully.');
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

        return redirect()->back()->with('success', 'Payment recorded successfully.' .
            (count($result['visits_created']) > 0
                ? ' ' . count($result['visits_created']) . ' visit(s) created for today\'s appointments.'
                : ''));
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

        return Inertia::render('Admin/Bookings/PrintReceipt', [
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

        return Inertia::render('Admin/Bookings/PrintPaymentReceipt', [
            'booking' => $booking,
            'payment' => $payment,
            'allPayments' => $allPayments,
        ]);
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

            return redirect()->back()->with('success', 'Retouch session added successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('error', 'Failed to add retouch session. Please try again.');
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

        return redirect()->back()->with('success', 'Consent document(s) uploaded successfully.');
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

        return redirect()->back()->with('success', 'Consent document deleted.');
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

            return redirect()->back()->with('success', 'Patient checked in successfully. Visit created.');
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

            return redirect()->back()->with('success', 'Appointment rescheduled successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFollowUp(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
        ]);

        $info = $this->bookingWorkflowService->checkFollowUpEligibility((int) $request->input('patient_id'));

        return response()->json([
            'follow_up' => $info,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // Validate filter inputs for export
        $filters = $request->validate([
            'status' => 'nullable|string|in:unconfirmed,confirmed,in_progress,completed,cancelled,new,contacted',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $bookings = Booking::with(['service', 'doctor', 'patient', 'bookingServices.service'])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bookings-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Booking #', 'Source', 'Patient/Name', 'Phone', 'Email',
                'Service', 'Doctor', 'Status', 'Notes', 'Admin Notes', 'Created At',
            ]);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $booking->booking_number ?? '-',
                    $booking->source ?? 'website',
                    $booking->patient?->full_name ?? $booking->full_name,
                    $booking->patient?->phone ?? $booking->phone,
                    $booking->email,
                    $booking->service?->name_en ?? ($booking->bookingServices->pluck('service.name_en')->filter()->implode(', ') ?: '-'),
                    $booking->doctor?->name_en ?? '-',
                    $booking->status,
                    $booking->notes,
                    $booking->admin_notes,
                    $booking->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /* ── Delete Booking ────────────────────────────────────── */

    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        if (!$request->user()->can('bookings.delete')) {
            abort(403, 'You do not have permission to delete bookings.');
        }

        DB::transaction(function () use ($booking) {
            // 1. Delete consent files from storage
            foreach ($booking->consents as $consent) {
                Storage::disk('public')->delete($consent->file_path);
            }
            $booking->consents()->delete();

            // 2. Nullify booking references on visits (preserve medical records)
            Visit::where('booking_id', $booking->id)->update([
                'booking_id' => null,
                'booking_appointment_id' => null,
            ]);

            // 3. Delete appointments
            BookingAppointment::where('booking_id', $booking->id)->delete();

            // 4. Delete booking services
            $booking->bookingServices()->delete();

            // 5. Delete invoice and related records
            if ($booking->invoice_id) {
                $invoice = Invoice::find($booking->invoice_id);
                if ($invoice) {
                    $invoice->payments()->delete();
                    $invoice->items()->delete();
                    $booking->update(['invoice_id' => null]);
                    $invoice->delete();
                }
            }

            // 6. Log and delete
            AuditLogger::log('deleted', $booking);
            $booking->delete();
        });

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /* ── Update Booking Services ──────────────────────────── */

    public function updateServices(Request $request, Booking $booking): RedirectResponse
    {
        if (!$request->user()->can('bookings.edit_services')) {
            abort(403, 'You do not have permission to edit booking services.');
        }

        if (!in_array($booking->status, ['confirmed', 'in_progress', 'completed'])) {
            return redirect()->back()->with('error', 'Cannot edit services for this booking status.');
        }

        $data = $request->validate([
            'services' => 'required|array|min:1',
            'services.*.id' => 'nullable|integer',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.doctor_id' => 'nullable|exists:doctors,id',
            'services.*.sessions_count' => 'required|integer|min:1|max:50',
            'services.*.unit_price' => 'required|numeric|min:0',
            'services.*.discount_per_session' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:500',
            'services.*._delete' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($booking, $data) {
            foreach ($data['services'] as $serviceData) {
                $unitPrice = (float) $serviceData['unit_price'];
                $discount = (float) ($serviceData['discount_per_session'] ?? 0);
                $sessions = (int) $serviceData['sessions_count'];
                $totalPrice = ($unitPrice - $discount) * $sessions;

                // Delete flagged service
                if (!empty($serviceData['_delete']) && !empty($serviceData['id'])) {
                    $bs = BookingService::find($serviceData['id']);
                    if ($bs && $bs->booking_id === $booking->id) {
                        $bs->appointments()->delete();
                        $bs->delete();
                    }
                    continue;
                }

                if (!empty($serviceData['id'])) {
                    // Update existing
                    $bs = BookingService::find($serviceData['id']);
                    if ($bs && $bs->booking_id === $booking->id) {
                        $bs->update([
                            'service_id' => $serviceData['service_id'] ?? $bs->service_id,
                            'doctor_id' => $serviceData['doctor_id'] ?? $bs->doctor_id,
                            'sessions_count' => $sessions,
                            'unit_price' => $unitPrice,
                            'discount_per_session' => $discount,
                            'total_price' => $totalPrice,
                            'notes' => $serviceData['notes'] ?? $bs->notes,
                        ]);
                    }
                } else {
                    // Create new
                    BookingService::create([
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
                }
            }

            // Recalculate invoice
            $this->recalculateBookingInvoice($booking);
        });

        AuditLogger::log('services_updated', $booking);

        return redirect()->back()->with('success', 'Booking services updated successfully.');
    }

    private function recalculateBookingInvoice(Booking $booking): void
    {
        $booking->load('bookingServices.service');

        $invoice = $booking->invoice_id ? Invoice::find($booking->invoice_id) : null;
        if (!$invoice) return;

        $subtotal = $booking->bookingServices->sum('total_price');

        $invoice->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - ($invoice->discount_amount ?? 0),
        ]);

        // Rebuild invoice items
        $invoice->items()->delete();

        foreach ($booking->bookingServices as $bs) {
            $service = $bs->service;
            $descEn = $service->name_en ?? ($booking->booking_type === 'dermatology_consultation' ? 'Dermatology Consultation' : 'Cosmetic Consultation');
            $descAr = $service->name_ar ?? $descEn;

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

        $invoice->recalculateStatus();
    }
}
