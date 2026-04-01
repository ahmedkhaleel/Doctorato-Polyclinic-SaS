<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\HeroSlide;
use App\Models\PackageBundle;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $featuredServices = Service::active()
            ->website()
            ->homepage()
            ->with('category')
            ->orderBy('display_order')
            ->get();

        $packageBundles = PackageBundle::active()
            ->with('services.service')
            ->orderBy('display_order')
            ->limit(6)
            ->get();

        $testimonials = Testimonial::published()
            ->with('service')
            ->orderBy('display_order')
            ->limit(10)
            ->get();

        $doctors = Doctor::active()
            ->orderBy('display_order')
            ->limit(4)
            ->get();

        $heroSlides = HeroSlide::active()->ordered()->get();

        return Inertia::render('Frontend/Home', [
            'featuredServices' => $featuredServices,
            'packageBundles' => $packageBundles,
            'testimonials' => $testimonials,
            'doctors' => $doctors,
            'heroSlides' => $heroSlides,
            'seo' => SeoService::get('home'),
        ]);
    }
}
