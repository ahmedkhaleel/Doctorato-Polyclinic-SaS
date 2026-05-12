<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\PackageBundle;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Services\ModuleManager;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $enabledMedical = array_intersect(
            array_keys(ModuleManager::getActiveModules()),
            ModuleManager::MEDICAL_MODULES
        );

        $featuredServices = Service::active()
            ->website()
            ->homepage()
            ->whereIn('module', $enabledMedical)
            ->with('category')
            ->orderBy('display_order')
            ->get();

        // Medical specialties: each active medical module with its categories & services
        $medicalSpecialties = [];
        foreach ($enabledMedical as $mod) {
            $info = ModuleManager::getModuleInfo($mod);
            $categories = ServiceCategory::forModule($mod)
                ->whereHas('services', fn ($q) => $q->where('status', 'active')->where('show_on_website', true))
                ->with(['services' => fn ($q) => $q->active()->website()->orderBy('display_order')->limit(6)])
                ->orderBy('display_order')
                ->get();

            $medicalSpecialties[] = [
                'slug' => $mod,
                'name_ar' => $info['name_ar'],
                'name_en' => $info['name_en'],
                'color' => $info['color'],
                'icon' => $info['icon'],
                'categories' => $categories,
            ];
        }

        $packageBundles = PackageBundle::active()
            ->whereIn('module', $enabledMedical)
            ->with('services.service')
            ->orderBy('module')
            ->orderBy('display_order')
            ->get();

        $testimonials = Testimonial::published()
            ->with('service')
            ->orderBy('display_order')
            ->limit(10)
            ->get();

        $doctors = Doctor::active()
            ->whereIn('module', $enabledMedical)
            ->orderBy('display_order')
            ->limit(4)
            ->get();

        $heroSlides = HeroSlide::active()->ordered()->get();

        // Real, trust-building clinic stats — cached for 1 hour to avoid
        // running these aggregates on every homepage hit.
        $clinicStats = \Illuminate\Support\Facades\Cache::remember('frontend:clinic-stats', 3600, function () {
            $reviewsAgg = \App\Models\PatientSatisfaction::whereNotNull('overall_rating')
                ->selectRaw('COUNT(*) AS total, AVG(overall_rating) AS avg')
                ->first();
            return [
                'patients_served' => (int) \App\Models\Patient::where('is_active', true)->count(),
                'doctors'         => (int) \App\Models\Doctor::active()->count(),
                'reviews_count'   => (int) ($reviewsAgg->total ?? 0),
                'reviews_avg'     => $reviewsAgg && $reviewsAgg->total ? round((float) $reviewsAgg->avg, 1) : null,
                // Visits completed in the last 12 months — proxy for activity
                'visits_12m'      => (int) \App\Models\Visit::where('status', 'completed')
                                            ->where('visit_date', '>=', now()->subYear())
                                            ->count(),
            ];
        });

        // Top 6 patient reviews (4★+ with a non-empty comment) — actual
        // patient voices, not staff-managed testimonials. Anonymized
        // when is_anonymous=true.
        $patientReviews = \App\Models\PatientSatisfaction::whereNotNull('overall_rating')
            ->where('overall_rating', '>=', 4)
            ->whereNotNull('comments')
            ->where('comments', '!=', '')
            ->with(['patient:id,full_name', 'doctor:id,name_ar,name_en'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($r) {
                $patientName = $r->is_anonymous || ! $r->patient ? null : $r->patient->full_name;
                return [
                    'id'             => $r->id,
                    'overall_rating' => $r->overall_rating,
                    'comments'       => $r->comments,
                    'created_at'     => $r->created_at?->toDateString(),
                    'reviewer'       => $patientName ? mb_substr($patientName, 0, 1) . '.' : null,
                    'doctor_name_ar' => $r->doctor?->name_ar,
                    'doctor_name_en' => $r->doctor?->name_en,
                ];
            });

        return Inertia::render('Frontend/Home', [
            'featuredServices' => $featuredServices,
            'medicalSpecialties' => $medicalSpecialties,
            'packageBundles' => $packageBundles,
            'testimonials' => $testimonials,
            'patientReviews' => $patientReviews,
            'doctors' => $doctors,
            'heroSlides' => $heroSlides,
            'clinicStats' => $clinicStats,
            'seo' => SeoService::get('home'),
        ]);
    }
}
