<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\PatientSatisfaction;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public-facing doctor profiles. Prospective patients land here
 * before they're logged in (or even registered) — a classic SEO +
 * conversion surface. Pulls average rating from the existing
 * PatientSatisfaction data so reviews compound over time.
 */
class DoctorController extends Controller
{
    public function index(Request $request): Response
    {
        $module = $request->input('module');
        $enabledModules = collect(ModuleManager::MEDICAL_MODULES)
            ->filter(fn ($m) => ModuleManager::isEnabled($m))
            ->values()
            ->all();

        $q = Doctor::active()
            ->whereIn('module', $enabledModules)
            ->select('id', 'name_ar', 'name_en', 'specialization_ar', 'specialization_en',
                     'photo', 'module', 'doctor_type', 'display_order',
                     'consultation_fee', 'online_consultation_enabled', 'online_consultation_fee');

        if ($module && in_array($module, $enabledModules, true)) {
            $q->where('module', $module);
        }

        $doctors = $q->orderBy('display_order')->orderBy('name_en')->get();

        // Hydrate ratings for the list (one aggregated query, not N+1).
        $ratingMap = PatientSatisfaction::whereIn('doctor_id', $doctors->pluck('id'))
            ->whereNotNull('overall_rating')
            ->selectRaw('doctor_id, COUNT(*) AS cnt, AVG(overall_rating) AS avg')
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $doctors = $doctors->map(function ($d) use ($ratingMap) {
            $r = $ratingMap->get($d->id);
            return [
                'id'                => $d->id,
                'name_ar'           => $d->name_ar,
                'name_en'           => $d->name_en,
                'specialization_ar' => $d->specialization_ar,
                'specialization_en' => $d->specialization_en,
                'photo'             => $d->photo ? '/storage/' . $d->photo : null,
                'module'            => $d->module,
                'doctor_type'       => $d->doctor_type,
                'consultation_fee'  => $d->consultation_fee !== null ? (float) $d->consultation_fee : null,
                'online'            => (bool) $d->online_consultation_enabled,
                'rating_avg'        => $r ? round((float) $r->avg, 1) : null,
                'rating_count'      => $r ? (int) $r->cnt : 0,
            ];
        });

        return Inertia::render('Frontend/Doctors/Index', [
            'doctors'          => $doctors,
            'availableModules' => $enabledModules,
            'filters'          => ['module' => $module],
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $doctor = Doctor::active()->where('id', $id)->firstOrFail();

        // Aggregate rating + sub-ratings (single SQL trip).
        $aggregate = PatientSatisfaction::where('doctor_id', $doctor->id)
            ->whereNotNull('overall_rating')
            ->selectRaw('
                COUNT(*) AS total,
                AVG(overall_rating) AS avg_overall,
                AVG(doctor_rating) AS avg_doctor,
                AVG(communication_rating) AS avg_communication,
                SUM(CASE WHEN would_recommend = 1 THEN 1 ELSE 0 END) AS recommend_yes,
                SUM(CASE WHEN would_recommend = 0 THEN 1 ELSE 0 END) AS recommend_no
            ')
            ->first();

        // Recent reviews — anonymized (we only show first-name initial).
        $reviews = PatientSatisfaction::where('doctor_id', $doctor->id)
            ->whereNotNull('overall_rating')
            ->whereNotNull('comments')
            ->where('comments', '!=', '')
            ->with('patient:id,full_name')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($r) {
                $name = $r->is_anonymous || ! $r->patient ? null : $r->patient->full_name;
                $initial = $name ? mb_substr($name, 0, 1) . '.' : null;
                return [
                    'id'             => $r->id,
                    'overall_rating' => $r->overall_rating,
                    'comments'       => $r->comments,
                    'created_at'     => $r->created_at?->toDateString(),
                    'reviewer'       => $initial,
                ];
            });

        return Inertia::render('Frontend/Doctors/Show', [
            'doctor' => [
                'id'                          => $doctor->id,
                'name_ar'                     => $doctor->name_ar,
                'name_en'                     => $doctor->name_en,
                'specialization_ar'           => $doctor->specialization_ar,
                'specialization_en'           => $doctor->specialization_en,
                'bio_ar'                      => $doctor->bio_ar,
                'bio_en'                      => $doctor->bio_en,
                'qualifications_ar'           => $doctor->qualifications_ar,
                'qualifications_en'           => $doctor->qualifications_en,
                'photo'                       => $doctor->photo ? '/storage/' . $doctor->photo : null,
                'module'                      => $doctor->module,
                'consultation_fee'            => $doctor->consultation_fee !== null ? (float) $doctor->consultation_fee : null,
                'online_consultation_enabled' => (bool) $doctor->online_consultation_enabled,
                'online_consultation_fee'     => $doctor->online_consultation_fee !== null ? (float) $doctor->online_consultation_fee : null,
                'online_consultation_bio_ar'  => $doctor->online_consultation_bio_ar,
                'online_consultation_bio_en'  => $doctor->online_consultation_bio_en,
            ],
            'rating' => [
                'total'             => (int) $aggregate->total,
                'avg_overall'       => $aggregate->total ? round((float) $aggregate->avg_overall, 1) : null,
                'avg_doctor'        => $aggregate->avg_doctor ? round((float) $aggregate->avg_doctor, 1) : null,
                'avg_communication' => $aggregate->avg_communication ? round((float) $aggregate->avg_communication, 1) : null,
                'recommend_yes'     => (int) $aggregate->recommend_yes,
                'recommend_no'      => (int) $aggregate->recommend_no,
            ],
            'reviews' => $reviews,
        ]);
    }
}
