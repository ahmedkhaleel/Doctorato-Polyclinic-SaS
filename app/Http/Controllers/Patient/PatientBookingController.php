<?php

namespace App\Http\Controllers\Patient;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\ServiceCategory;
use App\Services\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientBookingController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        $filters = $request->validate([
            'module' => 'nullable|string|in:derma,dental',
        ]);

        $query = Booking::where('patient_id', $patient->id)
            ->with(['service:id,name_en,name_ar', 'doctor:id,name_en,name_ar']);

        if ($module = $filters['module'] ?? null) {
            $query->where('module', $module);
        }

        $bookings = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Patient/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'module' => $request->input('module'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $patient = $this->patient($request);

        $categories = ServiceCategory::with(['services' => function ($q) {
            $q->where('bookable', true)->where('status', 'active')->select('id', 'name_en', 'name_ar', 'category_id', 'price', 'session_duration_minutes', 'module');
        }])->orderBy('display_order')->get();

        $doctors = Doctor::where('status', 'active')
            ->select('id', 'name_en', 'name_ar', 'specialization_en', 'specialization_ar', 'photo', 'module')
            ->with('schedules')
            ->get();

        $activeModules = ModuleManager::getActiveModules();

        return Inertia::render('Patient/Bookings/Create', [
            'patient' => $patient->only(['full_name', 'phone', 'email', 'file_number']),
            'categories' => $categories,
            'doctors' => $doctors,
            'modules' => $activeModules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);

        $data = $request->validate([
            'booking_type' => 'required|in:dermatology_consultation,cosmetic_consultation,service,dental_consultation,dental_service',
            'module' => 'nullable|string|in:derma,dental',
            'service_id' => 'nullable|exists:services,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'promo_code' => 'nullable|string|max:50',
        ]);

        // Auto-detect module from booking type
        $module = $data['module'] ?? 'derma';
        if (in_array($data['booking_type'] ?? '', ['dental_consultation', 'dental_service'])) {
            $module = 'dental';
        }

        $booking = Booking::create([
            'booking_number' => Booking::generateBookingNumber(),
            'source' => 'patient_portal',
            'module' => $module,
            'booking_type' => $data['booking_type'],
            'full_name' => $patient->full_name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'patient_id' => $patient->id,
            'service_id' => $data['service_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'preferred_date' => $data['preferred_date'],
            'preferred_time' => $data['preferred_time'],
            'notes' => $data['notes'] ?? null,
            'promo_code' => $data['promo_code'] ?? null,
            'status' => 'unconfirmed',
        ]);

        return redirect()->route('patient.bookings.index', ['locale' => app()->getLocale()])
            ->with('success', 'Booking created successfully! We will confirm your appointment soon.');
    }

    public function cancel(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $patient = $this->patient($request);

        // Ensure the booking belongs to this patient
        if ($booking->patient_id !== $patient->id) {
            abort(403);
        }

        // Only allow cancelling unconfirmed or confirmed bookings
        if (!in_array($booking->status, ['unconfirmed', 'confirmed'])) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled successfully.');
    }
}
