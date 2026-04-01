<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatientSatisfaction;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PatientSatisfactionController extends Controller
{
    public function index(Request $request)
    {
        $feedbacks = PatientSatisfaction::with([
                'patient:id,full_name,file_number,photo',
                'doctor:id,name_ar,name_en,photo',
                'visit:id,visit_date,module',
            ])
            ->when($request->doctor_id, fn ($q, $d) => $q->where('doctor_id', $d))
            ->when($request->rating, fn ($q, $r) => $q->where('overall_rating', $r))
            ->when($request->date_from, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->date_to, fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // ── Stats ───────────────────────────────────────
        $stats = Cache::remember('satisfaction_stats', 600, function () {
            $total = PatientSatisfaction::count();
            if ($total === 0) return [
                'total' => 0, 'avg_overall' => 0, 'avg_doctor' => 0, 'avg_staff' => 0,
                'avg_cleanliness' => 0, 'avg_waiting' => 0, 'recommend_rate' => 0,
                'nps' => 0, 'distribution' => [0, 0, 0, 0, 0],
            ];

            $promoters = PatientSatisfaction::where('nps_score', '>=', 9)->count();
            $detractors = PatientSatisfaction::where('nps_score', '<=', 6)->count();
            $npsTotal = PatientSatisfaction::whereNotNull('nps_score')->count();

            return [
                'total' => $total,
                'avg_overall' => round(PatientSatisfaction::avg('overall_rating'), 1),
                'avg_doctor' => round(PatientSatisfaction::avg('doctor_rating'), 1),
                'avg_staff' => round(PatientSatisfaction::avg('staff_rating'), 1),
                'avg_cleanliness' => round(PatientSatisfaction::avg('cleanliness_rating'), 1),
                'avg_waiting' => round(PatientSatisfaction::avg('waiting_time_rating'), 1),
                'recommend_rate' => $total > 0
                    ? round(PatientSatisfaction::where('would_recommend', true)->count() / $total * 100, 1)
                    : 0,
                'nps' => $npsTotal > 0
                    ? round((($promoters - $detractors) / $npsTotal) * 100, 1)
                    : 0,
                'distribution' => [
                    PatientSatisfaction::where('overall_rating', 1)->count(),
                    PatientSatisfaction::where('overall_rating', 2)->count(),
                    PatientSatisfaction::where('overall_rating', 3)->count(),
                    PatientSatisfaction::where('overall_rating', 4)->count(),
                    PatientSatisfaction::where('overall_rating', 5)->count(),
                ],
            ];
        });

        // ── Top improvement areas ───────────────────────
        $improvementAreas = Cache::remember('satisfaction_improvement_areas', 600, function () {
            $areas = [];
            PatientSatisfaction::whereNotNull('improvement_areas')
                ->pluck('improvement_areas')
                ->each(function ($arr) use (&$areas) {
                    foreach ($arr as $area) {
                        $areas[$area] = ($areas[$area] ?? 0) + 1;
                    }
                });
            arsort($areas);
            return array_slice($areas, 0, 5, true);
        });

        // ── Doctor rankings ─────────────────────────────
        $doctorRankings = Cache::remember('satisfaction_doctor_rankings', 600, function () {
            return PatientSatisfaction::select(
                    'doctor_id',
                    DB::raw('ROUND(AVG(doctor_rating), 1) as avg_rating'),
                    DB::raw('COUNT(*) as review_count')
                )
                ->whereNotNull('doctor_id')
                ->groupBy('doctor_id')
                ->having('review_count', '>=', 3)
                ->orderByDesc('avg_rating')
                ->limit(10)
                ->get()
                ->load('doctor:id,name_ar,name_en,photo');
        });

        $doctors = Doctor::active()->select('id', 'name_ar', 'name_en')->get();

        return Inertia::render('Admin/Satisfaction/Index', [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'improvementAreas' => $improvementAreas,
            'doctorRankings' => $doctorRankings,
            'doctors' => $doctors,
            'areaLabels' => PatientSatisfaction::IMPROVEMENT_AREAS,
            'filters' => $request->only(['doctor_id', 'rating', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Public survey page (patient fills via SMS link).
     */
    public function survey(string $token)
    {
        $satisfaction = PatientSatisfaction::where('token', $token)
            ->with('patient:id,full_name', 'doctor:id,name_ar,name_en')
            ->firstOrFail();

        if ($satisfaction->overall_rating) {
            return Inertia::render('Satisfaction/ThankYou', [
                'satisfaction' => $satisfaction,
            ]);
        }

        return Inertia::render('Satisfaction/Survey', [
            'satisfaction' => $satisfaction,
            'improvementAreas' => PatientSatisfaction::IMPROVEMENT_AREAS,
        ]);
    }

    /**
     * Submit survey response.
     */
    public function submitSurvey(Request $request, string $token)
    {
        $satisfaction = PatientSatisfaction::where('token', $token)->firstOrFail();

        $validated = $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'doctor_rating' => 'nullable|integer|min:1|max:5',
            'staff_rating' => 'nullable|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'waiting_time_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'comments' => 'nullable|string|max:2000',
            'would_recommend' => 'nullable|boolean',
            'improvement_areas' => 'nullable|array',
            'nps_score' => 'nullable|integer|min:0|max:10',
        ]);

        $satisfaction->update($validated);

        Cache::forget('satisfaction_stats');
        Cache::forget('satisfaction_improvement_areas');
        Cache::forget('satisfaction_doctor_rankings');

        return redirect()->route('satisfaction.thank-you', $token);
    }
}
