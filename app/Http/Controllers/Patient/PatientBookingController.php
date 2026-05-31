<?php

namespace App\Http\Controllers\Patient;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Doctor;
use App\Models\LoyaltyPoint;
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
            'module' => 'nullable|string|in:derma,dental,pediatric',
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

        // Only show MEDICAL modules (derma, dental, pediatric) on the patient
        // booking page — HR / Inventory / Insurance are administrative modules
        // and should never appear as department filters here.
        $activeModules = collect(ModuleManager::getActiveModules())
            ->only(ModuleManager::MEDICAL_MODULES)
            ->all();

        // Patient's own active discount codes (LOYAL-* from loyalty
        // redemptions, FRIEND-* from referrals, etc.) — shown as one-tap
        // chips above the promo code field so they don't have to dig
        // through email or the loyalty page to copy/paste.
        $codeIds = LoyaltyPoint::where('patient_id', $patient->id)
            ->where('type', LoyaltyPoint::TYPE_REDEEM)
            ->where('reference_type', (new DiscountCode)->getMorphClass())
            ->pluck('reference_id')
            ->all();

        $patientCodes = DiscountCode::whereIn('id', $codeIds)
            ->where('is_active', true)
            ->whereColumn('used_count', '<', 'max_uses')
            ->where(function ($q) { $q->whereNull('end_date')->orWhere('end_date', '>=', now()); })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['code', 'discount_value', 'discount_type', 'end_date'])
            ->map(fn ($d) => [
                'code'       => $d->code,
                'amount'     => (float) $d->discount_value,
                'type'       => $d->discount_type, // 'fixed' or 'percentage'
                'expires_at' => $d->end_date?->toDateString(),
                'source'     => 'loyalty',
            ]);

        // Multi-branch: offer a branch picker only when enabled with >1 branch.
        $branches = [];
        if (config('branches.enabled')) {
            $branches = \App\Models\Branch::where('is_active', true)
                ->orderBy('id')->get(['id', 'name_ar', 'name_en']);
            if ($branches->count() < 2) {
                $branches = [];
            }
        }

        return Inertia::render('Patient/Bookings/Create', [
            'patient' => $patient->only(['full_name', 'phone', 'email', 'file_number']),
            'categories' => $categories,
            'doctors' => $doctors,
            'modules' => $activeModules,
            'patientCodes' => $patientCodes,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);

        $data = $request->validate([
            'booking_type' => 'required|in:dermatology_consultation,cosmetic_consultation,service,dental_consultation,dental_service,pediatric_consultation,pediatric_service',
            'module' => 'nullable|string|in:derma,dental,pediatric',
            'service_id' => 'nullable|exists:services,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'promo_code' => 'nullable|string|max:50',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        // Auto-detect module from booking type
        $module = $data['module'] ?? ModuleManager::getDefaultModule();
        if (in_array($data['booking_type'] ?? '', ['dental_consultation', 'dental_service'])) {
            $module = 'dental';
        }
        if (in_array($data['booking_type'] ?? '', ['pediatric_consultation', 'pediatric_service'])) {
            $module = 'pediatric';
        }

        $create = fn () => Booking::create([
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

        // Pin to the chosen branch so the booking + its number inherit it.
        $booking = ! empty($data['branch_id'])
            ? app(\App\Services\Branch\BranchContext::class)->runForBranch((int) $data['branch_id'], $create)
            : $create();

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
        \App\Events\BookingCancelled::dispatch($booking, 'Cancelled by patient');

        return back()->with('success', 'Booking cancelled successfully.');
    }

    /**
     * Patient self-reschedule. Keeps the same doctor / service / module —
     * just shifts the date/time. Drops the booking back to `unconfirmed`
     * so the front desk re-confirms (avoids a patient bypassing the
     * doctor's actual availability via a self-confirmed reschedule).
     */
    public function reschedule(Request $request, string $locale, Booking $booking): RedirectResponse
    {
        $patient = $this->patient($request);

        if ($booking->patient_id !== $patient->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['unconfirmed', 'confirmed'])) {
            return back()->with('error', 'This booking cannot be rescheduled.');
        }

        $data = $request->validate([
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'reason'         => ['nullable', 'string', 'max:255'],
        ]);

        $booking->update([
            'preferred_date' => $data['preferred_date'],
            'preferred_time' => $data['preferred_time'] ?? $booking->preferred_time,
            // Re-enter the queue so a human re-confirms availability.
            'status'         => 'unconfirmed',
            'notes'          => trim(
                ($booking->notes ? $booking->notes . "\n" : '')
                . '[' . now()->toDateString() . '] '
                . 'Patient rescheduled.'
                . ($data['reason'] ?? null ? ' Reason: ' . $data['reason'] : '')
            ),
        ]);

        \App\Services\AuditLogger::log('booking_rescheduled_by_patient', $booking, [
            'new_date'   => $data['preferred_date'],
            'new_time'   => $data['preferred_time'] ?? null,
            'reason'     => $data['reason'] ?? null,
            'patient_id' => $patient->id,
        ]);

        return back()->with('success', 'Booking rescheduled. We will reconfirm shortly.');
    }
}
