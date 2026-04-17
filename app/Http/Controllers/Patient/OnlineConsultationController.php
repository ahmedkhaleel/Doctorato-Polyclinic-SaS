<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\OnlineConsultation;
use App\Models\Setting;
use App\Services\OnlineConsultationService;
use App\Services\OnlineSlotGeneratorService;
use App\Services\Payment\PaymentGatewayManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnlineConsultationController extends Controller
{
    public function index(Request $request)
    {
        $patient = $request->user()->patient;
        if (!$patient) abort(403);

        $consultations = OnlineConsultation::forPatient($patient->id)
            ->with(['doctor:id,name_ar,name_en,specialization_ar,specialization_en,photo'])
            ->orderByDesc('scheduled_date')
            ->orderByDesc('start_time')
            ->paginate(10);

        return Inertia::render('Patient/OnlineConsultation/Index', [
            'consultations' => $consultations,
            'joinWindowMinutes' => (int) Setting::get('telemedicine_join_window_minutes', 15),
            'cancellationHours' => (int) Setting::get('telemedicine_cancellation_hours', 24),
        ]);
    }

    public function doctors(Request $request)
    {
        $doctors = Doctor::onlineEnabled()
            ->select('id', 'name_ar', 'name_en', 'specialization_ar', 'specialization_en',
                     'photo', 'doctor_type', 'online_consultation_fee',
                     'online_session_duration_minutes',
                     'online_consultation_bio_ar', 'online_consultation_bio_en',
                     'module')
            ->when($request->input('module'), fn ($q, $module) => $q->where('module', $module))
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Patient/OnlineConsultation/Doctors', [
            'doctors' => $doctors,
            'filters' => [
                'module' => $request->input('module'),
            ],
        ]);
    }

    public function showDoctor(Request $request, Doctor $doctor, OnlineSlotGeneratorService $slots)
    {
        if (!$doctor->online_consultation_enabled) abort(404);

        $startDate = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        // Generate 14 days of availability starting from today
        $availability = [];
        for ($i = 0; $i < 14; $i++) {
            $date = $startDate->copy()->addDays($i);
            $daySlots = $slots->getAvailableSlots($doctor, $date);
            $availability[] = [
                'date' => $date->format('Y-m-d'),
                'day_name_ar' => $this->getArabicDayName($date),
                'day_name_en' => $date->format('l'),
                'date_label_ar' => $date->translatedFormat('j F'),
                'date_label_en' => $date->format('M j'),
                'is_today' => $date->isSameDay(now()),
                'slots' => $daySlots,
                'has_available' => collect($daySlots)->contains('available', true),
            ];
        }

        return Inertia::render('Patient/OnlineConsultation/Book', [
            'doctor' => $doctor->only([
                'id', 'name_ar', 'name_en', 'specialization_ar', 'specialization_en',
                'photo', 'photo_url', 'doctor_type', 'online_consultation_fee',
                'online_session_duration_minutes', 'online_consultation_bio_ar',
                'online_consultation_bio_en', 'module',
            ]),
            'availability' => $availability,
        ]);
    }

    public function store(Request $request, OnlineConsultationService $service, PaymentGatewayManager $gateways)
    {
        $patient = $request->user()->patient;
        if (!$patient) abort(403);

        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'chief_complaint' => 'nullable|string|max:2000',
        ]);

        $doctor = Doctor::findOrFail($data['doctor_id']);
        if (!$doctor->online_consultation_enabled) {
            return back()->with('error', __('Doctor does not accept online consultations'));
        }

        // Verify slot still available
        $datetime = Carbon::parse($data['scheduled_date'] . ' ' . $data['start_time']);
        $slotService = app(OnlineSlotGeneratorService::class);
        if (!$slotService->isSlotAvailable($doctor, $datetime)) {
            return back()->with('error', __('This time slot is no longer available. Please pick another.'));
        }

        $consultation = $service->createConsultation([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'scheduled_date' => $data['scheduled_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'module' => $doctor->module ?: 'derma',
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'fee' => (float) $doctor->online_consultation_fee,
        ]);

        // Initiate payment
        $gateway = $gateways->getActive();
        if (!$gateway) {
            return back()->with('error', __('Payment gateway is not configured. Please contact support.'));
        }

        $result = $gateway->initiatePayment($consultation);
        if (!$result->success) {
            return back()->with('error', $result->errorMessage);
        }

        return redirect($result->checkoutUrl);
    }

    public function success(Request $request)
    {
        $consultationId = $request->input('id');
        $consultation = OnlineConsultation::with('doctor:id,name_ar,name_en,specialization_ar,specialization_en,photo')
            ->findOrFail($consultationId);

        if ($consultation->patient_id !== $request->user()->patient?->id) abort(403);

        return Inertia::render('Patient/OnlineConsultation/Success', [
            'consultation' => $consultation,
        ]);
    }

    public function cancelled(Request $request)
    {
        return Inertia::render('Patient/OnlineConsultation/Cancelled', [
            'consultationId' => $request->input('id'),
        ]);
    }

    public function room(Request $request, OnlineConsultation $consultation)
    {
        if ($consultation->patient_id !== $request->user()->patient?->id) abort(403);

        return Inertia::render('Patient/OnlineConsultation/Room', [
            'consultationId' => $consultation->id,
            'role' => 'patient',
        ]);
    }

    public function cancel(Request $request, OnlineConsultation $consultation, OnlineConsultationService $service)
    {
        if ($consultation->patient_id !== $request->user()->patient?->id) abort(403);

        if (!$consultation->isCancellable((int) Setting::get('telemedicine_cancellation_hours', 24))) {
            return back()->with('error', __('Cannot cancel this consultation (past cancellation window)'));
        }

        $service->cancel($consultation, $request->user(), $request->input('reason', 'Patient cancelled'));

        return redirect()->route('patient.online-consultations.index')
            ->with('success', __('Consultation cancelled. Refund will be processed if applicable.'));
    }

    private function getArabicDayName(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SATURDAY => 'السبت',
            Carbon::SUNDAY => 'الأحد',
            Carbon::MONDAY => 'الإثنين',
            Carbon::TUESDAY => 'الثلاثاء',
            Carbon::WEDNESDAY => 'الأربعاء',
            Carbon::THURSDAY => 'الخميس',
            Carbon::FRIDAY => 'الجمعة',
            default => '',
        };
    }
}
