<?php

namespace App\Http\Controllers\Patient;

use App\Models\Doctor;
use App\Models\PatientSatisfaction;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient-portal "Browse Doctors" page. Lets logged-in patients see
 * every active doctor at a glance — photo, specialization, average
 * rating, total reviews — and book directly. Closes the gap where
 * patients would have to know a specific doctor's name to book.
 */
class PatientDoctorsController extends BasePatientController
{
    public function index(Request $request): Response
    {
        $module = $request->input('module');

        $query = Doctor::active();

        if ($module && in_array($module, ModuleManager::MEDICAL_MODULES, true)) {
            $query->forModule($module);
        }

        $doctors = $query
            ->select('id', 'name_en', 'name_ar', 'specialization_en', 'specialization_ar',
                     'photo', 'module', 'bio_en', 'bio_ar', 'years_experience')
            ->orderBy('name_en')
            ->get();

        // Compute rating aggregates for the listed doctors in a single
        // query — avoid N+1 even if the clinic has 30+ doctors.
        $doctorIds = $doctors->pluck('id')->all();
        $ratings = PatientSatisfaction::whereIn('doctor_id', $doctorIds)
            ->whereNotNull('overall_rating')
            ->select('doctor_id', DB::raw('COUNT(*) as cnt'), DB::raw('AVG(overall_rating) as avg'))
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $payload = $doctors->map(function ($d) use ($ratings) {
            $r = $ratings->get($d->id);
            return [
                'id'                  => $d->id,
                'name_en'             => $d->name_en,
                'name_ar'             => $d->name_ar,
                'specialization_en'   => $d->specialization_en,
                'specialization_ar'   => $d->specialization_ar,
                'photo'               => $d->photo,
                'photo_url'           => $d->photo
                                            ? (str_starts_with($d->photo, 'http') ? $d->photo : '/storage/' . $d->photo)
                                            : null,
                'module'              => $d->module,
                'bio_en'              => $d->bio_en,
                'bio_ar'              => $d->bio_ar,
                'years_experience'    => $d->years_experience,
                'rating_avg'          => $r ? round((float) $r->avg, 1) : null,
                'rating_count'        => $r ? (int) $r->cnt : 0,
            ];
        })->values();

        // Available module filters — only those enabled at the clinic.
        $availableModules = collect(ModuleManager::MEDICAL_MODULES)
            ->filter(fn ($m) => ModuleManager::isEnabled($m))
            ->values()
            ->all();

        return Inertia::render('Patient/Doctors/Index', [
            'doctors'          => $payload,
            'availableModules' => $availableModules,
            'filters'          => ['module' => $module],
        ]);
    }
}
