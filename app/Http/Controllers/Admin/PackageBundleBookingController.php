<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\PackageBundle;
use App\Models\PackageBundleBooking;
use App\Models\PackageBundleBookingAppointment;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Services\AuditLogger;
use App\Services\PackageBundleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageBundleBookingController extends Controller
{
    public function __construct(
        protected PackageBundleWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $query = PackageBundleBooking::with(['patient', 'packageBundle', 'bundleServices']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/PackageBundleBookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PackageBundleBookings/Create', [
            'bundles' => PackageBundle::active()->with('services.service')->orderBy('display_order')->get(),
            'patients' => Patient::active()->select('id', 'full_name', 'phone', 'file_number')->get(),
            'doctors' => Doctor::active()->select('id', 'name_ar', 'name_en')->get(),
            'doctorSchedules' => DoctorSchedule::active()->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package_bundle_id' => 'required|exists:package_bundles,id',
            'patient_id' => 'required|exists:patients,id',
            'notes' => 'nullable|string',
            'services' => 'required|array|min:1',
            'services.*.package_bundle_service_id' => 'required|exists:package_bundle_services,id',
            'services.*.doctor_id' => 'required|exists:doctors,id',
            'services.*.appointments' => 'required|array|min:1',
            'services.*.appointments.*.date' => 'required|date|after_or_equal:today',
            'services.*.appointments.*.start_time' => 'required|date_format:H:i',
            'services.*.appointments.*.end_time' => 'required|date_format:H:i|after:services.*.appointments.*.start_time',
        ]);

        $result = $this->workflowService->createFromAdmin($data, auth()->id());

        AuditLogger::log('created', $result['bundleBooking']);

        return redirect()->route('admin.package-bundle-bookings.show', $result['bundleBooking']->id)
            ->with('success', 'Package bundle booking created. Booking #' . $result['bundleBooking']->booking_number);
    }

    public function show(PackageBundleBooking $bundleBooking): Response
    {
        $bundleBooking->load([
            'patient',
            'packageBundle',
            'bundleServices.service',
            'bundleServices.doctor',
            'bundleServices.appointments' => fn ($q) => $q->with('doctor')->orderBy('session_number'),
            'visits' => fn ($q) => $q->with(['doctor', 'service'])->orderBy('visit_date'),
            'appointments' => fn ($q) => $q->with(['doctor', 'bundleBookingService.service'])->orderBy('appointment_date')->orderBy('start_time'),
            'invoice.payments.paymentMethod',
            'invoice.items',
            'receptionist',
        ]);

        return Inertia::render('Admin/PackageBundleBookings/Show', [
            'bundleBooking' => $bundleBooking,
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'doctors' => Doctor::active()->select('id', 'name_ar', 'name_en')->get(),
            'doctorSchedules' => DoctorSchedule::active()->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']),
        ]);
    }

    public function processPayment(Request $request, PackageBundleBooking $bundleBooking): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->workflowService->processPayment($bundleBooking, $data, auth()->id());

        AuditLogger::log('payment_processed', $bundleBooking);

        return redirect()->back()->with('success', 'Payment processed successfully.');
    }

    public function cancel(PackageBundleBooking $bundleBooking): RedirectResponse
    {
        $this->workflowService->cancelBooking($bundleBooking);

        AuditLogger::log('cancelled', $bundleBooking);

        return redirect()->back()->with('success', 'Package bundle booking cancelled.');
    }

    public function checkInAppointment(Request $request, PackageBundleBooking $bundleBooking, PackageBundleBookingAppointment $appointment): RedirectResponse
    {
        if ((int) $appointment->package_bundle_booking_id !== (int) $bundleBooking->id) {
            abort(403);
        }

        try {
            $visit = $this->workflowService->checkInAppointment($appointment, $request->user()->id);

            AuditLogger::log('appointment_checked_in', $bundleBooking, [
                'appointment_id' => $appointment->id,
                'visit_id' => $visit->id,
            ]);

            return redirect()->back()->with('success', 'Patient checked in successfully. Visit created.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rescheduleAppointment(Request $request, PackageBundleBooking $bundleBooking, PackageBundleBookingAppointment $appointment): RedirectResponse
    {
        if ((int) $appointment->package_bundle_booking_id !== (int) $bundleBooking->id) {
            abort(403);
        }

        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        try {
            $this->workflowService->rescheduleAppointment($appointment, $data);

            AuditLogger::log('appointment_rescheduled', $bundleBooking, [
                'appointment_id' => $appointment->id,
                'new_date' => $data['date'],
                'new_time' => $data['start_time'],
                'new_doctor' => $data['doctor_id'],
            ]);

            return redirect()->back()->with('success', 'Appointment rescheduled successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function addRetouchSession(Request $request, PackageBundleBooking $bundleBooking): RedirectResponse
    {
        $data = $request->validate([
            'package_bundle_booking_service_id' => 'required|exists:package_bundle_booking_services,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        try {
            $appointment = $this->workflowService->addRetouchSession($bundleBooking, $data, auth()->id());

            AuditLogger::log('retouch_added', $bundleBooking, [
                'appointment_id' => $appointment->id,
                'appointment_date' => $data['date'],
            ]);

            return redirect()->back()->with('success', 'Retouch session added successfully.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function printReceipt(PackageBundleBooking $bundleBooking): Response
    {
        $bundleBooking->load([
            'patient',
            'packageBundle',
            'bundleServices.service',
            'bundleServices.doctor',
            'invoice.payments.paymentMethod',
            'invoice.items',
        ]);

        return Inertia::render('Admin/PackageBundleBookings/PrintReceipt', [
            'bundleBooking' => $bundleBooking,
        ]);
    }
}
