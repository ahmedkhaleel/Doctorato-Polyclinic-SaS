<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Booking;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatmentPlan;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Visit;
use App\Services\ModuleManager;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class SecretaryDashboardController extends BaseSecretaryController
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $enabledModules = array_keys(ModuleManager::getActiveModules());

        // Today stats
        $stats = [
            'patients_today' => Patient::whereDate('created_at', $today)->count(),
            'visits_today' => Visit::whereDate('visit_date', $today)->whereIn('module', $enabledModules)->count(),
            'pending_bookings' => Booking::where('status', 'unconfirmed')->whereIn('module', $enabledModules)->count(),
            'payments_today' => round((float) Payment::whereDate('payment_date', $today)->sum('amount'), 2),
        ];

        // Today's queue (grouped by doctor)
        $todayQueue = Visit::with(['patient', 'doctor', 'service'])
            ->whereDate('visit_date', $today)
            ->whereIn('module', $enabledModules)
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        // Medical risk alerts for today's dental patients
        $medicalAlerts = [];
        foreach ($todayQueue as $visit) {
            if ($visit->module === 'dental' && $visit->patient) {
                $riskFlags = $visit->patient->getDentalRiskFlags();
                if (!empty($riskFlags)) {
                    $medicalAlerts[] = [
                        'visit_id' => $visit->id,
                        'patient_name' => $visit->patient->full_name,
                        'patient_id' => $visit->patient->id,
                        'doctor_name' => $visit->doctor?->name_en ?? $visit->doctor?->name_ar,
                        'flags' => $riskFlags,
                        'high_risk_count' => count(array_filter($riskFlags, fn ($f) => $f['severity'] === 'high')),
                    ];
                }
            }
        }

        // Recent pending bookings
        $pendingBookings = Booking::where('status', 'unconfirmed')
            ->whereIn('module', $enabledModules)
            ->latest()
            ->limit(5)
            ->get();

        // Recent payments
        $recentPayments = Payment::with(['invoice.patient', 'paymentMethod'])
            ->latest('payment_date')
            ->limit(5)
            ->get();

        // Dental summary for secretary (only if dental module enabled)
        $dental = null;
        $pendingFollowups = [];
        $overdueFollowups = 0;

        if (ModuleManager::isEnabled('dental')) {
            $dental = [
                'lab_ready' => DentalLabOrder::where('status', 'ready')->count(),
                'lab_pending' => DentalLabOrder::pending()->count(),
                'lab_overdue' => DentalLabOrder::overdue()->count(),
                'active_plans' => DentalTreatmentPlan::active()->count(),
                'recent_lab_orders' => DentalLabOrder::with(['patient:id,full_name,file_number', 'doctor:id,name_ar,name_en'])
                    ->whereIn('status', ['ready', 'ordered', 'in_production'])
                    ->latest('order_date')
                    ->limit(5)
                    ->get(),
            ];

            // Pending dental follow-ups that need booking
            $pendingFollowups = DentalScheduledFollowup::with([
                    'patient:id,full_name,file_number,phone',
                    'doctor:id,name_ar,name_en',
                    'treatment:id,treatment_type,tooth_number',
                ])
                ->needsBooking()
                ->orderBy('scheduled_date')
                ->limit(10)
                ->get();

            // Overdue followups (scheduled date has passed but still pending)
            $overdueFollowups = DentalScheduledFollowup::where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', $today)
                ->count();
        }

        return Inertia::render('Secretary/Dashboard', [
            'stats' => $stats,
            'todayQueue' => $todayQueue,
            'pendingBookings' => $pendingBookings,
            'recentPayments' => $recentPayments,
            'dental' => $dental,
            'medicalAlerts' => $medicalAlerts,
            'pendingFollowups' => $pendingFollowups,
            'overdueFollowups' => $overdueFollowups,
        ]);
    }
}
