<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Notifications\NewBookingNotification;
use App\Services\BookingWorkflowService;
use App\Services\ModuleManager;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(protected BookingWorkflowService $bookingWorkflowService) {}

    public function create(): Response
    {
        // Only medical specialty modules should appear on booking page
        $allActive = array_keys(ModuleManager::getActiveModules());
        $enabledModules = array_intersect($allActive, ModuleManager::MEDICAL_MODULES);

        $serviceCategories = ServiceCategory::whereHas('services', function ($q) use ($enabledModules) {
            $q->where('status', 'active')->where('bookable', true)->whereIn('module', $enabledModules);
        })->with(['services' => function ($q) use ($enabledModules) {
            $q->where('status', 'active')->where('bookable', true)->whereIn('module', $enabledModules)->orderBy('display_order');
        }])
            ->orderBy('display_order')
            ->get();

        $services = Service::active()->bookable()
            ->whereIn('module', $enabledModules)
            ->orderBy('display_order')
            ->get();

        $doctors = Doctor::active()
            ->whereIn('module', $enabledModules)
            ->orderBy('display_order')
            ->get();

        $doctorSchedules = DoctorSchedule::active()
            ->get(['doctor_id', 'day_of_week', 'start_time', 'end_time']);

        // Only pass medical modules to the booking page
        $activeModules = collect(ModuleManager::getActiveModules())
            ->only(ModuleManager::MEDICAL_MODULES)
            ->all();

        return Inertia::render('Frontend/Booking', [
            'serviceCategories' => $serviceCategories,
            'services' => $services,
            'doctors' => $doctors,
            'doctorSchedules' => $doctorSchedules,
            'seo' => SeoService::get('booking'),
            'modules' => $activeModules,
        ]);
    }

    public function store(BookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingWorkflowService->createFromWebsite($request->validated());

        // Notify the assigned doctor (if any)
        if ($booking->doctor_id) {
            $doctor = $booking->doctor;
            if ($doctor?->user) {
                \App\Jobs\SendNotificationJob::dispatch($doctor->user, new NewBookingNotification($booking->load('service')), 'new_booking_doctor');
            }
        }

        $message = app()->getLocale() === 'ar'
            ? 'تم إرسال حجزك بنجاح. سنتواصل معك قريبا للتأكيد.'
            : 'Your booking has been submitted successfully. We will contact you shortly to confirm.';

        return redirect()->back()->with('success', $message);
    }
}
