<?php

namespace App\Http\Controllers\Doctor;

use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use App\Models\DentalTreatment;
use App\Models\DentalTreatmentPlan;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dedicated chair-side cockpit for the dental doctor: today's queue (with the
 * planned tooth/procedure per patient), production + workflow KPIs vs last month,
 * an action strip (pending consents / lab-ready / stalled plans), clinical
 * insights (condition mix, procedure mix + production, 14-day production trend),
 * and a resume-plans list. All patient-scoped to this doctor, N+1-safe.
 */
class DoctorDentalDashboardController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $now = Carbon::now();

        // Patients this doctor actually treats in dental (for caseload insights).
        $patientIds = collect()
            ->merge(DentalTreatment::where('doctor_id', $doctorId)->pluck('patient_id'))
            ->merge(DentalTreatmentPlan::where('doctor_id', $doctorId)->pluck('patient_id'))
            ->unique()->values();

        return Inertia::render('Doctor/Dental/Dashboard', [
            'stats' => $this->stats($doctorId, $now),
            'todayQueue' => $this->todayQueue($doctorId),
            'alerts' => $this->alerts($doctorId, $now),
            'conditionMix' => $this->conditionMix($patientIds),
            'procedureMix' => $this->procedureMix($doctorId),
            'productionTrend' => $this->productionTrend($doctorId, $now),
            'resumePlans' => $this->resumePlans($doctorId),
        ]);
    }

    private function stats(int $doctorId, Carbon $now): array
    {
        $completed = fn () => DentalTreatment::where('doctor_id', $doctorId)->where('status', 'completed');
        $monthProd = fn (Carbon $m) => round((float) $completed()
            ->whereMonth('completed_at', $m->month)->whereYear('completed_at', $m->year)
            ->sum(DB::raw('cost + COALESCE(lab_cost, 0)')), 2);

        $prev = $now->copy()->subMonthNoOverflow();
        $monthVisits = Visit::where('doctor_id', $doctorId)->where('module', 'dental')
            ->whereMonth('visit_date', $now->month)->whereYear('visit_date', $now->year);

        return [
            'production_today' => round((float) $completed()->whereDate('completed_at', today())->sum(DB::raw('cost + COALESCE(lab_cost, 0)')), 2),
            'production_month' => $monthProd($now),
            'production_prev_month' => $monthProd($prev),
            'commission_month' => round((float) (clone $monthVisits)->where('status', 'completed')->sum('commission_amount'), 2),
            'treatments_today' => DentalTreatment::where('doctor_id', $doctorId)->whereDate('created_at', today())->count(),
            'completed_today' => DentalTreatment::where('doctor_id', $doctorId)->where('status', 'completed')->whereDate('completed_at', today())->count(),
            'active_plans' => DentalTreatmentPlan::where('doctor_id', $doctorId)->active()->count(),
            'pending_treatments' => DentalTreatment::where('doctor_id', $doctorId)->whereIn('status', ['planned', 'in_progress'])->count(),
        ];
    }

    private function todayQueue(int $doctorId): array
    {
        $visits = Visit::where('doctor_id', $doctorId)->where('module', 'dental')
            ->whereDate('visit_date', today())
            ->with('patient:id,full_name,file_number,phone,photo')
            ->orderBy('scheduled_time')
            ->get(['id', 'patient_id', 'status', 'scheduled_time', 'visit_type']);

        // Next planned treatment (tooth/procedure) per patient — one extra query, mapped.
        $pids = $visits->pluck('patient_id')->filter()->unique()->values();
        $nextByPatient = DentalTreatment::whereIn('patient_id', $pids)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['planned', 'in_progress'])
            ->orderBy('created_at')
            ->get(['id', 'patient_id', 'tooth_number', 'treatment_type', 'status'])
            ->groupBy('patient_id');

        return $visits->map(function ($v) use ($nextByPatient) {
            $next = optional($nextByPatient->get($v->patient_id))->first();

            return [
                'id' => $v->id,
                'patient' => $v->patient ? $v->patient->only(['id', 'full_name', 'file_number', 'phone', 'photo']) : null,
                'status' => $v->status,
                'time' => $v->scheduled_time,
                'next_tooth' => $next?->tooth_number,
                'next_procedure' => $next?->treatment_type,
            ];
        })->values()->all();
    }

    private function alerts(int $doctorId, Carbon $now): array
    {
        // Active plans awaiting the patient's consent signature.
        $consentPending = DentalTreatmentPlan::where('doctor_id', $doctorId)->active()
            ->with('patient:id,full_name')
            ->get()
            ->filter(fn ($p) => $p->hasPendingConsent())
            ->map(fn ($p) => ['id' => $p->id, 'patient' => $p->patient?->full_name, 'title' => $p->title_en ?? $p->title_ar])
            ->values();

        // Lab work that has come back and is ready to fit.
        $labReady = DentalLabOrder::where('doctor_id', $doctorId)->where('status', 'ready')
            ->with('patient:id,full_name')
            ->latest('updated_at')->limit(10)->get()
            ->map(fn ($l) => ['id' => $l->id, 'patient' => $l->patient?->full_name])
            ->values();

        // Active plans that have started but stalled (no update in 14+ days).
        $stalled = DentalTreatmentPlan::where('doctor_id', $doctorId)->active()
            ->where('completed_sessions', '>', 0)
            ->whereColumn('completed_sessions', '<', 'estimated_sessions')
            ->where('updated_at', '<', $now->copy()->subDays(14))
            ->count();

        return [
            'consents_pending' => $consentPending,
            'lab_ready' => $labReady,
            'stalled_plans' => $stalled,
        ];
    }

    private function conditionMix($patientIds): array
    {
        if ($patientIds->isEmpty()) {
            return [];
        }

        return DentalChart::whereIn('patient_id', $patientIds)
            ->select('condition', DB::raw('COUNT(*) as c'))
            ->groupBy('condition')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => ['condition' => $r->condition ?: 'healthy', 'count' => (int) $r->c])
            ->all();
    }

    private function procedureMix(int $doctorId): array
    {
        return DentalTreatment::where('doctor_id', $doctorId)
            ->select('treatment_type', DB::raw('COUNT(*) as c'), DB::raw('SUM(CASE WHEN status = "completed" THEN cost + COALESCE(lab_cost,0) ELSE 0 END) as revenue'))
            ->groupBy('treatment_type')
            ->orderByDesc('c')
            ->get()
            ->map(fn ($r) => ['type' => $r->treatment_type ?: 'other', 'count' => (int) $r->c, 'revenue' => round((float) $r->revenue, 2)])
            ->all();
    }

    private function productionTrend(int $doctorId, Carbon $now): array
    {
        $start = $now->copy()->subDays(13)->startOfDay();
        $rows = DentalTreatment::where('doctor_id', $doctorId)->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->select(DB::raw('DATE(completed_at) as d'), DB::raw('SUM(cost + COALESCE(lab_cost,0)) as v'))
            ->groupBy('d')->pluck('v', 'd');

        $out = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $start->copy()->addDays($i);
            $out[] = ['x' => $i + 1, 'y' => round((float) ($rows[$day->toDateString()] ?? 0), 2), 'label' => $day->format('M d')];
        }

        return $out;
    }

    private function resumePlans(int $doctorId): array
    {
        return DentalTreatmentPlan::where('doctor_id', $doctorId)->active()
            ->with('patient:id,full_name,photo')
            ->orderBy('updated_at')
            ->limit(8)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'patient' => $p->patient ? $p->patient->only(['id', 'full_name', 'photo']) : null,
                'title' => $p->title_en ?? $p->title_ar,
                'completed_sessions' => (int) $p->completed_sessions,
                'estimated_sessions' => (int) $p->estimated_sessions,
                'progress' => $p->progress,
            ])->all();
    }
}
