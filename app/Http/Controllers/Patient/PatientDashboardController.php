<?php

namespace App\Http\Controllers\Patient;

use App\Models\DentalLabOrder;
use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\TreatmentPlanConsent;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientDashboardController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        $regularBookings = \App\Models\Booking::where('patient_id', $patient->id)
            ->whereIn('status', ['unconfirmed', 'confirmed'])
            ->where('preferred_date', '>=', now()->toDateString())
            ->with(['service:id,name_en,name_ar', 'doctor:id,name_en,name_ar'])
            ->orderBy('preferred_date')
            ->limit(5)
            ->get();

        $recentVisits = $patient->visits()
            ->with(['doctor:id,name_en,name_ar', 'service:id,name_en,name_ar'])
            ->latest('visit_date')
            ->limit(5)
            ->get();

        $unpaidInvoices = $patient->invoices()
            ->whereIn('status', ['unpaid', 'partial'])
            ->latest('invoice_date')
            ->get();

        // Top 3 most-recent unpaid invoices for the dashboard banner —
        // patient sees "you owe X — top invoices: ..." with deep links.
        $topUnpaid = $unpaidInvoices->take(3)->map(fn ($inv) => [
            'id'             => $inv->id,
            'invoice_number' => $inv->invoice_number,
            'invoice_date'   => $inv->invoice_date,
            'total'          => (float) $inv->total,
            'paid_amount'    => (float) $inv->paid_amount,
            'outstanding'    => (float) ($inv->total - $inv->paid_amount),
            'status'         => $inv->status,
        ])->values();

        // Dental stats (only if module enabled)
        $hasDental = \App\Services\ModuleManager::isEnabled('dental') && $patient->visits()->where('module', 'dental')->exists();
        $dentalStats = null;
        $nextDentalAppointment = null;
        $pendingFollowups = [];
        $dentalLabOrders = [];

        if ($hasDental) {
            // Batch dental stats using single aggregation queries to avoid N+1
            $treatmentAgg = DentalTreatment::where('patient_id', $patient->id)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
                ->first();

            $dentalStats = [
                'total_treatments' => (int) $treatmentAgg->total,
                'completed_treatments' => (int) $treatmentAgg->completed,
                'active_plans' => DentalTreatmentPlan::where('patient_id', $patient->id)->whereIn('status', ['approved', 'in_progress'])->count(),
                'pending_lab_orders' => DentalLabOrder::where('patient_id', $patient->id)->whereIn('status', ['ordered', 'in_production'])->count(),
            ];

            // Next dental appointment
            $nextDentalAppointment = Visit::where('module', 'dental')
                ->where('patient_id', $patient->id)
                ->where('visit_date', '>=', now()->toDateString())
                ->whereIn('status', ['scheduled', 'booked', 'confirmed'])
                ->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])
                ->orderBy('visit_date')
                ->orderBy('scheduled_time')
                ->first();

            // Pending follow-ups (upcoming scheduled follow-ups)
            $pendingFollowups = DentalScheduledFollowup::where('patient_id', $patient->id)
                ->whereIn('status', ['pending', 'sms_sent'])
                ->with(['doctor:id,name_ar,name_en'])
                ->orderBy('scheduled_date')
                ->limit(3)
                ->get();

            // Lab orders ready for pickup
            $dentalLabOrders = DentalLabOrder::where('patient_id', $patient->id)
                ->whereIn('status', ['delivered', 'ready'])
                ->with('doctor:id,name_ar,name_en')
                ->latest('order_date')
                ->limit(3)
                ->get();

            // Pending consents needing signature
            $pendingConsents = DentalTreatmentPlan::where('patient_id', $patient->id)
                ->whereHas('consent', fn ($q) => $q->where('status', 'pending'))
                ->with(['consent:id,dental_treatment_plan_id,status', 'doctor:id,name_ar,name_en'])
                ->get();
        }

        $referralStats = \App\Services\PatientReferralService::statsFor($patient);
        $locale = $request->route('locale', 'ar');
        $loyaltyBalance = \App\Services\LoyaltyService::balance($patient);

        return Inertia::render('Patient/Dashboard', [
            'patient' => $patient->only(['id', 'full_name', 'file_number', 'photo_url', 'referral_code']),
            'upcomingBookings' => $regularBookings,
            'recentVisits' => $recentVisits,
            'stats' => [
                'upcoming_count' => $regularBookings->count(),
                'total_visits' => $patient->visits()->count(),
                'unpaid_count' => $unpaidInvoices->count(),
                'unpaid_amount' => (float) $unpaidInvoices->sum(fn ($inv) => $inv->total - $inv->paid_amount),
                // Aliases preserved so any old templates still work
                'unpaid_invoices_count'  => $unpaidInvoices->count(),
                'unpaid_invoices_amount' => (float) $unpaidInvoices->sum(fn ($inv) => $inv->total - $inv->paid_amount),
                'active_plans' => \App\Models\TreatmentPlan::where('patient_id', $patient->id)->where('status', 'active')->count(),
            ],
            'topUnpaid' => $topUnpaid,
            'dentalStats' => $dentalStats,
            'nextDentalAppointment' => $nextDentalAppointment,
            'pendingFollowups' => $pendingFollowups,
            'dentalLabOrders' => $dentalLabOrders,
            'pendingConsents' => $pendingConsents ?? [],
            'referral' => [
                'code'           => $patient->referral_code,
                'share_url'      => \App\Services\PatientReferralService::shareUrl($patient, $locale),
                'count'          => $referralStats['count'],
                'total_discount' => $referralStats['total_discount'],
                'currency'       => $referralStats['currency'],
            ],
            'loyalty' => [
                'balance'      => $loyaltyBalance,
                'egp_value'    => \App\Services\LoyaltyService::pointsToEgp($loyaltyBalance),
                'currency'     => \App\Models\Setting::get('currency_code', 'EGP'),
                'min_redeem'   => (int) \App\Models\Setting::get('loyalty_min_redeem_points', 100),
                'recent'       => \App\Models\LoyaltyPoint::where('patient_id', $patient->id)
                                    ->latest()->limit(5)
                                    ->get(['id', 'points', 'type', 'description', 'created_at']),
            ],
        ]);
    }
}
