<?php

namespace App\Services;

use App\Models\DentalScheduledFollowup;
use App\Models\DentalTreatmentPlan;
use App\Models\DermaTreatmentPlan;
use App\Models\NeuropsychEncounter;
use App\Models\ObgynLabTest;
use App\Models\ScaleResult;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * DoctorWorklistService — the actionable "needs MY attention" worklist for a
 * doctor, aggregated from cross-cutting clinical state that the dashboard
 * summaries don't surface. Single source of truth for both the dedicated
 * /doctor/worklist page (items) and the dashboard counter card (counts only).
 *
 * Four buckets (all scoped to the doctor; specialty buckets return empty when
 * the doctor's data doesn't apply):
 *   1. incomplete_docs   — visits in progress/completed with no diagnosis (30d)
 *   2. results_review    — recently flagged scales / abnormal OB labs (14d)
 *   3. due_followups     — pending dental scheduled follow-ups (with overdue)
 *   4. open_plans        — active derma/dental plans with no upcoming session
 */
class DoctorWorklistService
{
    private const DOC_WINDOW_DAYS = 30;

    private const RESULT_WINDOW_DAYS = 14;

    private const ITEM_LIMIT = 10;

    /**
     * @return array{incomplete_docs:array, results_review:array, due_followups:array, open_plans:array, total:int}
     */
    public function buckets(int $doctorId, bool $withItems = true): array
    {
        $incomplete = $this->incompleteDocs($doctorId, $withItems);
        $results = $this->resultsToReview($doctorId, $withItems);
        $followups = $this->dueFollowups($doctorId, $withItems);
        $plans = $this->openPlans($doctorId, $withItems);

        return [
            'incomplete_docs' => $incomplete,
            'results_review' => $results,
            'due_followups' => $followups,
            'open_plans' => $plans,
            'total' => $incomplete['count'] + $results['count'] + $followups['count'] + $plans['count'],
        ];
    }

    /** Lightweight counts for the dashboard card. */
    public function counts(int $doctorId): array
    {
        $b = $this->buckets($doctorId, false);

        return [
            'incomplete_docs' => $b['incomplete_docs']['count'],
            'results_review' => $b['results_review']['count'],
            'due_followups' => $b['due_followups']['count'],
            'open_plans' => $b['open_plans']['count'],
            'total' => $b['total'],
        ];
    }

    private function incompleteDocs(int $doctorId, bool $withItems): array
    {
        $base = Visit::where('doctor_id', $doctorId)
            ->whereIn('status', ['in_progress', 'completed'])
            ->where(fn ($q) => $q->whereNull('diagnosis')->orWhere('diagnosis', ''))
            ->whereDate('visit_date', '>=', now()->subDays(self::DOC_WINDOW_DAYS));

        $count = (clone $base)->count();
        $items = [];
        if ($withItems) {
            $items = $base->with('patient:id,full_name,file_number')
                ->latest('visit_date')
                ->limit(self::ITEM_LIMIT)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'patient' => $v->patient?->full_name,
                    'file_number' => $v->patient?->file_number,
                    'label' => $v->status === 'in_progress' ? 'visit_in_progress' : 'visit_completed',
                    'date' => optional($v->visit_date)->format('Y-m-d'),
                    'href' => "/doctor/visits/{$v->id}",
                    'severity' => $v->status === 'completed' ? 'high' : 'medium',
                ])->all();
        }

        return ['count' => $count, 'items' => $items];
    }

    private function resultsToReview(int $doctorId, bool $withItems): array
    {
        $since = now()->subDays(self::RESULT_WINDOW_DAYS);

        // Flagged measurement scales tied to this doctor's encounters.
        $encounterIds = NeuropsychEncounter::where('doctor_id', $doctorId)->pluck('id');
        $scaleBase = ScaleResult::where('flag', true)
            ->whereIn('neuropsych_encounter_id', $encounterIds)
            ->where('taken_at', '>=', $since);

        // Abnormal antenatal labs ordered by this doctor.
        $labBase = ObgynLabTest::where('doctor_id', $doctorId)
            ->where('is_abnormal', true)
            ->whereDate('result_date', '>=', $since->toDateString());

        $count = (clone $scaleBase)->count() + (clone $labBase)->count();
        $items = [];
        if ($withItems) {
            $scales = $scaleBase->with('patient:id,full_name,file_number')
                ->latest('taken_at')->limit(self::ITEM_LIMIT)->get()
                ->map(fn ($s) => [
                    'id' => 'scale-'.$s->id,
                    'patient' => $s->patient?->full_name,
                    'file_number' => $s->patient?->file_number,
                    'label' => 'scale_flagged',
                    'detail' => strtoupper((string) $s->scale_key).' · '.$s->score,
                    'date' => optional($s->taken_at)->format('Y-m-d'),
                    'href' => $s->patient_id ? "/doctor/patients/{$s->patient_id}" : '#',
                    'severity' => 'high',
                ]);
            $labs = $labBase->with('patient:id,full_name,file_number')
                ->latest('result_date')->limit(self::ITEM_LIMIT)->get()
                ->map(fn ($l) => [
                    'id' => 'lab-'.$l->id,
                    'patient' => $l->patient?->full_name,
                    'file_number' => $l->patient?->file_number,
                    'label' => 'lab_abnormal',
                    'detail' => $l->test_type.' · '.$l->value.' '.$l->unit,
                    'date' => optional($l->result_date)->format('Y-m-d'),
                    'href' => $l->patient_id ? "/doctor/patients/{$l->patient_id}" : '#',
                    'severity' => 'high',
                ]);
            $items = $scales->concat($labs)->take(self::ITEM_LIMIT)->values()->all();
        }

        return ['count' => $count, 'items' => $items];
    }

    private function dueFollowups(int $doctorId, bool $withItems): array
    {
        $base = DentalScheduledFollowup::where('doctor_id', $doctorId)
            ->where('status', DentalScheduledFollowup::STATUS_PENDING)
            ->whereNull('booking_id');

        $count = (clone $base)->count();
        $overdue = (clone $base)->whereDate('scheduled_date', '<', today())->count();
        $items = [];
        if ($withItems) {
            $items = $base->with(['patient:id,full_name,file_number', 'treatment:id,treatment_type,tooth_number'])
                ->orderBy('scheduled_date')
                ->limit(self::ITEM_LIMIT)
                ->get()
                ->map(fn ($f) => [
                    'id' => $f->id,
                    'patient' => $f->patient?->full_name,
                    'file_number' => $f->patient?->file_number,
                    'label' => 'followup_due',
                    'detail' => $f->treatment?->treatment_type,
                    'date' => optional($f->scheduled_date)->format('Y-m-d'),
                    'href' => $f->patient_id ? "/doctor/patients/{$f->patient_id}" : '#',
                    'severity' => ($f->scheduled_date && $f->scheduled_date->isPast()) ? 'high' : 'medium',
                ])->all();
        }

        return ['count' => $count, 'overdue' => $overdue, 'items' => $items];
    }

    private function openPlans(int $doctorId, bool $withItems): array
    {
        $noUpcomingBooking = function ($q, string $planTable) use ($doctorId) {
            $q->select(DB::raw(1))
                ->from('bookings')
                ->whereColumn('bookings.patient_id', "{$planTable}.patient_id")
                ->where('bookings.doctor_id', $doctorId)
                ->whereIn('bookings.status', ['confirmed', 'pending'])
                ->whereDate('bookings.preferred_date', '>=', today())
                ->whereNull('bookings.deleted_at');
        };

        $dermaBase = DermaTreatmentPlan::where('doctor_id', $doctorId)
            ->active()
            ->where('estimated_sessions', '>', 0)
            ->whereColumn('completed_sessions', '<', 'estimated_sessions')
            ->whereDoesntHave('sessions', fn ($q) => $q->whereDate('next_session_date', '>=', today()))
            ->whereNotExists(fn ($q) => $noUpcomingBooking($q, 'derma_treatment_plans'));

        $dentalBase = DentalTreatmentPlan::where('doctor_id', $doctorId)
            ->where('status', DentalTreatmentPlan::STATUS_IN_PROGRESS)
            ->where('estimated_sessions', '>', 0)
            ->whereColumn('completed_sessions', '<', 'estimated_sessions')
            ->whereNotExists(fn ($q) => $noUpcomingBooking($q, 'dental_treatment_plans'));

        $count = (clone $dermaBase)->count() + (clone $dentalBase)->count();
        $items = [];
        if ($withItems) {
            $derma = $dermaBase->with('patient:id,full_name,file_number')->latest('start_date')->limit(self::ITEM_LIMIT)->get()
                ->map(fn ($p) => [
                    'id' => 'derma-'.$p->id,
                    'patient' => $p->patient?->full_name,
                    'file_number' => $p->patient?->file_number,
                    'label' => 'plan_open',
                    'detail' => ($p->title_ar ?: $p->title_en).' · '.$p->completed_sessions.'/'.$p->estimated_sessions,
                    'date' => optional($p->start_date)->format('Y-m-d'),
                    'href' => $p->patient_id ? "/doctor/derma/patients/{$p->patient_id}" : '#',
                    'severity' => 'medium',
                ]);
            $dental = $dentalBase->with('patient:id,full_name,file_number')->latest('id')->limit(self::ITEM_LIMIT)->get()
                ->map(fn ($p) => [
                    'id' => 'dental-'.$p->id,
                    'patient' => $p->patient?->full_name,
                    'file_number' => $p->patient?->file_number,
                    'label' => 'plan_open',
                    'detail' => ($p->title_ar ?: $p->title_en).' · '.$p->completed_sessions.'/'.$p->estimated_sessions,
                    'date' => null,
                    'href' => "/doctor/dental/treatment-plans?patient_id={$p->patient_id}",
                    'severity' => 'medium',
                ]);
            $items = $derma->concat($dental)->take(self::ITEM_LIMIT)->values()->all();
        }

        return ['count' => $count, 'items' => $items];
    }
}
