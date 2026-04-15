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

        return Inertia::render('Frontend/Home', [
            'featuredServices' => $featuredServices,
            'medicalSpecialties' => $medicalSpecialties,
            'packageBundles' => $packageBundles,
            'testimonials' => $testimonials,
            'doctors' => $doctors,
            'heroSlides' => $heroSlides,
            'seo' => SeoService::get('home'),
        ]);
    }
}
