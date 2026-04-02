<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\PackageBundle;
use App\Models\PackageBundleBooking;
use App\Models\PackageBundleBookingAppointment;
use App\Models\Patient;
use App\Models\PaymentMethod;
use App\Services\PackageBundleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryPackageBundleBookingController extends BaseSecretaryController
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

        return Inertia::render('Secretary/PackageBundleBookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Secretary/PackageBundleBookings/Create', [
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

        $result = $this->workflowService->createFromSecretary($data, auth()->id());

        return redirect()->route('secretary.bundle-bookings.show', $result['bundleBooking']->id)
            ->with('success', $this->msg(
                'Package bundle booking created. Booking #' . $result['bundleBooking']->booking_number,
                'تم إنشاء حجز الباقة. رقم الحجز #' . $result['bundleBooking']->booking_number,
            ));
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

        return Inertia::render('Secretary/PackageBundleBookings/Show', [
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

        return redirect()->back()->with('success', $this->msg('Payment processed successfully.', 'تم معالجة الدفعة بنجاح.'));
    }

    public function checkInAppointment(Request $request, PackageBundleBooking $bundleBooking, PackageBundleBookingAppointment $appointment): RedirectResponse
    {
        if ((int) $appointment->package_bundle_booking_id !== (int) $bundleBooking->id) {
            abort(403);
        }

        try {
            $this->workflowService->checkInAppointment($appointment, $request->user()->id);

            return redirect()->back()->with('success', $this->msg('Patient checked in successfully. Visit created.', 'تم تسجيل دخول المريض بنجاح. تم إنشاء الزيارة.'));
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

            return redirect()->back()->with('success', $this->msg('Appointment rescheduled successfully.', 'تم إعادة جدولة الموعد بنجاح.'));
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
            $this->workflowService->addRetouchSession($bundleBooking, $data, auth()->id());

            return redirect()->back()->with('success', $this->msg('Retouch session added successfully.', 'تم إضافة جلسة المتابعة بنجاح.'));
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
