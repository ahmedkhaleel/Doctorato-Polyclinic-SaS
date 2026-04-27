<?php

namespace App\Http\Controllers\Doctor;

use App\Models\PatientSatisfaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Doctor's view of their own patient ratings & reviews. Read-only —
 * doctors can see every review submitted about them but cannot edit
 * or delete them.
 */
class DoctorReviewsController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $doctor = $this->doctor($request);

        $base = PatientSatisfaction::where('doctor_id', $doctor->id)
            ->whereNotNull('overall_rating');

        // Aggregate stats — single SQL trip with conditional aggregates.
        $aggregate = (clone $base)->selectRaw('
            COUNT(*)                              AS total,
            AVG(overall_rating)                   AS avg_overall,
            AVG(doctor_rating)                    AS avg_doctor,
            AVG(communication_rating)             AS avg_communication,
            AVG(waiting_time_rating)              AS avg_waiting,
            AVG(staff_rating)                     AS avg_staff,
            AVG(cleanliness_rating)               AS avg_cleanliness,
            AVG(nps_score)                        AS avg_nps,
            SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) AS recommend_yes,
            SUM(CASE WHEN would_recommend = 0 THEN 1 ELSE 0 END) AS recommend_no
        ')->first();

        // Star distribution (5 → 1)
        $distribution = (clone $base)
            ->select('overall_rating', DB::raw('COUNT(*) as cnt'))
            ->groupBy('overall_rating')
            ->orderByDesc('overall_rating')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->overall_rating => (int) $r->cnt])
            ->all();

        // Last 30 days (to compare with overall)
        $last30 = (clone $base)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('COUNT(*) AS total, AVG(overall_rating) AS avg_overall')
            ->first();

        $stats = [
            'total'             => (int) $aggregate->total,
            'avg_overall'       => $aggregate->total ? round((float) $aggregate->avg_overall, 2) : null,
            'avg_doctor'        => $aggregate->avg_doctor       ? round((float) $aggregate->avg_doctor, 2) : null,
            'avg_communication' => $aggregate->avg_communication ? round((float) $aggregate->avg_communication, 2) : null,
            'avg_waiting'       => $aggregate->avg_waiting      ? round((float) $aggregate->avg_waiting, 2) : null,
            'avg_staff'         => $aggregate->avg_staff        ? round((float) $aggregate->avg_staff, 2) : null,
            'avg_cleanliness'   => $aggregate->avg_cleanliness  ? round((float) $aggregate->avg_cleanliness, 2) : null,
            'avg_nps'           => $aggregate->avg_nps          ? round((float) $aggregate->avg_nps, 2) : null,
            'recommend_yes'     => (int) $aggregate->recommend_yes,
            'recommend_no'      => (int) $aggregate->recommend_no,
            'distribution'      => $distribution,
            'last_30d_count'    => (int) $last30->total,
            'last_30d_avg'      => $last30->total ? round((float) $last30->avg_overall, 2) : null,
        ];

        $reviews = (clone $base)
            ->with(['patient:id,full_name', 'visit:id,visit_date'])
            ->latest()
            ->paginate(15)
            ->through(fn ($r) => [
                'id'              => $r->id,
                'overall_rating'  => $r->overall_rating,
                'doctor_rating'   => $r->doctor_rating,
                'comments'        => $r->comments,
                'would_recommend' => $r->would_recommend,
                'nps_score'       => $r->nps_score,
                'is_anonymous'    => $r->is_anonymous,
                'patient_name'    => $r->is_anonymous ? null : $r->patient?->full_name,
                'visit_date'      => $r->visit?->visit_date,
                'created_at'      => $r->created_at?->toDateString(),
                'source'          => $r->source,
            ]);

        return Inertia::render('Doctor/Reviews/Index', [
            'stats'   => $stats,
            'reviews' => $reviews,
        ]);
    }
}
