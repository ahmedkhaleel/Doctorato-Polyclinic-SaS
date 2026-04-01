<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Booking;
use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\DoctorPayout;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDashboardController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctor = $this->doctor($request);
        $doctorId = $doctor->id;
        $now = Carbon::now();

        // ─── Today Stats ──────────────────────────────────────
        $todayVisits = Visit::where('doctor_id', $doctorId)->whereDate('visit_date', today());
        $today = [
            'total' => $todayVisits->count(),
            'waiting' => (clone $todayVisits)->where('status', 'waiting')->count(),
            'in_progress' => (clone $todayVisits)->where('status', 'in_progress')->count(),
            'completed' => (clone $todayVisits)->where('status', 'completed')->count(),
        ];

        // ─── Monthly Stats ────────────────────────────────────
        $monthVisits = Visit::where('doctor_id', $doctorId)
            ->whereMonth('visit_date', $now->month)
            ->whereYear('visit_date', $now->year);

        $monthly = [
            'total_visits' => $monthVisits->count(),
            'completed_visits' => (clone $monthVisits)->where('status', 'completed')->count(),
            'total_commission' => round((float) (clone $monthVisits)->where('status', 'completed')->sum('commission_amount'), 2),
            'unique_patients' => (clone $monthVisits)->distinct('patient_id')->count('patient_id'),
        ];

        // ─── Previous Month Comparison ──────────────────────
        $prevMonth = $now->copy()->subMonth();
        $prevMonthVisits = Visit::where('doctor_id', $doctorId)
            ->whereMonth('visit_date', $prevMonth->month)
            ->whereYear('visit_date', $prevMonth->year);

        $prevMonthly = [
            'total_visits' => $prevMonthVisits->count(),
            'completed_visits' => (clone $prevMonthVisits)->where('status', 'completed')->count(),
            'total_commission' => round((float) (clone $prevMonthVisits)->where('status', 'completed')->sum('commission_amount'), 2),
        ];

        // ─── Completion Rate ─────────────────────────────────
        $completionRate = $monthly['total_visits'] > 0
            ? round(($monthly['completed_visits'] / $monthly['total_visits']) * 100)
            : 0;

        // ─── Visit Trend (last 7 days) ────────────────────────
        $visitTrend = Visit::where('doctor_id', $doctorId)
            ->select(DB::raw('DATE(visit_date) as date'), DB::raw('COUNT(*) as total'))
            ->where('visit_date', '>=', $now->copy()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $visitTrendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $visitTrendData[] = [
                'label' => Carbon::parse($date)->format('D'),
                'value' => (int) ($visitTrend[$date]->total ?? 0),
                'date' => Carbon::parse($date)->format('M d'),
            ];
        }

        // ─── Today Queue ──────────────────────────────────────
        $todayQueue = Visit::with(['patient:id,full_name,file_number,phone', 'service:id,name_en'])
            ->where('doctor_id', $doctorId)
            ->whereDate('visit_date', today())
            ->whereIn('status', ['waiting', 'in_progress'])
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        // ─── Recent Completed Visits (with commission info) ────
        $recentVisits = Visit::with([
                'patient:id,full_name',
                'service:id,name_en,price,price_after_discount',
                'invoice:id,visit_id,total,status',
                'booking.invoice:id,booking_id,total,status',
                'booking.bookingServices:id,booking_id,service_id,unit_price,discount_per_session,sessions_count',
            ])
            ->where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(function ($visit) {
                $visitAmount = 0;
                if ($visit->invoice) {
                    $visitAmount = (float) $visit->invoice->total;
                } elseif ($visit->booking && $visit->booking->invoice) {
                    $bookingService = $visit->booking->bookingServices
                        ->firstWhere('service_id', $visit->service_id);
                    if ($bookingService) {
                        $visitAmount = (float) ($bookingService->unit_price - ($bookingService->discount_per_session ?? 0));
                    } else {
                        $visitAmount = (float) $visit->booking->invoice->total;
                    }
                }

                if ($visitAmount <= 0 && $visit->service) {
                    $visitAmount = (float) ($visit->service->price_after_discount ?? $visit->service->price ?? 0);
                }

                $visit->setAttribute('visit_amount', $visitAmount);
                unset($visit->booking);

                return $visit;
            });

        // ─── Upcoming Bookings ────────────────────────────────
        $upcomingBookings = Booking::with('service:id,name_en')
            ->where('doctor_id', $doctorId)
            ->where('status', 'confirmed')
            ->where('preferred_date', '>=', today())
            ->orderBy('preferred_date')
            ->limit(5)
            ->get();

        // ─── Payout Summary ─────────────────────────────────
        $payoutSummary = [
            'total_paid' => round((float) DoctorPayout::where('doctor_id', $doctorId)->paid()->sum('net_amount'), 2),
            'pending' => round((float) DoctorPayout::where('doctor_id', $doctorId)->confirmed()->sum('net_amount'), 2),
            'pending_count' => DoctorPayout::where('doctor_id', $doctorId)->confirmed()->count(),
        ];

        // ─── Doctor Commission Info ──────────────────────────
        $doctorInfo = [
            'default_commission_percentage' => (float) ($doctor->default_commission_percentage ?? 0),
            'consultation_fee' => (float) ($doctor->consultation_fee ?? 0),
        ];

        // ─── Dental Summary (for dental doctors, only if module enabled) ─────────────
        $dental = null;
        $pendingFollowups = null;
        $todayMedicalAlerts = [];

        if ($doctor->module === 'dental' && \App\Services\ModuleManager::isEnabled('dental')) {
            $dentalTreatmentsToday = DentalTreatment::where('doctor_id', $doctorId)
                ->whereDate('created_at', today());

            $dental = [
                'treatments_today' => $dentalTreatmentsToday->count(),
                'completed_today' => (clone $dentalTreatmentsToday)->where('status', 'completed')->count(),
                'active_plans' => DentalTreatmentPlan::where('doctor_id', $doctorId)->active()->count(),
                'pending_treatments' => DentalTreatment::where('doctor_id', $doctorId)
                    ->whereIn('status', ['planned', 'in_progress'])->count(),
                'lab_ready' => DentalLabOrder::where('doctor_id', $doctorId)
                    ->where('status', 'ready')->count(),
                'lab_pending' => DentalLabOrder::where('doctor_id', $doctorId)
                    ->pending()->count(),
                'month_treatments' => DentalTreatment::where('doctor_id', $doctorId)
                    ->where('status', 'completed')
                    ->whereMonth('completed_at', $now->month)
                    ->whereYear('completed_at', $now->year)
                    ->count(),
                'month_revenue' => round((float) DentalTreatment::where('doctor_id', $doctorId)
                    ->where('status', 'completed')
                    ->whereMonth('completed_at', $now->month)
                    ->whereYear('completed_at', $now->year)
                    ->sum(DB::raw('cost + lab_cost')), 2),
            ];

            // Pending follow-ups for this doctor
            $pendingFollowups = DentalScheduledFollowup::with([
                    'patient:id,full_name,file_number,phone',
                    'treatment:id,treatment_type,tooth_number',
                ])
                ->where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->orderBy('scheduled_date')
                ->limit(5)
                ->get();

            $dental['overdue_followups'] = DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->where('scheduled_date', '<', today())
                ->count();

            $dental['pending_followups_count'] = DentalScheduledFollowup::where('doctor_id', $doctorId)
                ->where('status', DentalScheduledFollowup::STATUS_PENDING)
                ->whereNull('booking_id')
                ->count();

            // Medical risk alerts for today's dental patients
            foreach ($todayQueue as $visit) {
                if ($visit->patient) {
                    $riskFlags = $visit->patient->getDentalRiskFlags();
                    if (!empty($riskFlags)) {
                        $todayMedicalAlerts[] = [
                            'visit_id' => $visit->id,
                            'patient_name' => $visit->patient->full_name,
                            'patient_id' => $visit->patient->id,
                            'flags' => $riskFlags,
                            'high_risk_count' => count(array_filter($riskFlags, fn ($f) => $f['severity'] === 'high')),
                        ];
                    }
                }
            }
        }

        return Inertia::render('Doctor/Dashboard', [
            'today' => $today,
            'monthly' => $monthly,
            'prevMonthly' => $prevMonthly,
            'completionRate' => $completionRate,
            'visitTrend' => $visitTrendData,
            'todayQueue' => $todayQueue,
            'recentVisits' => $recentVisits,
            'upcomingBookings' => $upcomingBookings,
            'payoutSummary' => $payoutSummary,
            'doctorInfo' => $doctorInfo,
            'dental' => $dental,
            'pendingFollowups' => $pendingFollowups,
            'todayMedicalAlerts' => $todayMedicalAlerts,
        ]);
    }
}
