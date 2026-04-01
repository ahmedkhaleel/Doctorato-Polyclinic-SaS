<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $categories = ServiceCategory::with(['services' => function ($query) {
            $query->active()->website()->orderBy('display_order');
        }])
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Frontend/Services/Index', [
            'categories' => $categories,
            'seo' => SeoService::get('services'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        $service = Service::where('slug', $slug)
            ->active()
            ->website()
            ->with(['category', 'faqs', 'gallery'])
            ->firstOrFail();

        $relatedServices = Service::active()->website()
            ->where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->orderBy('display_order')
            ->limit(4)
            ->get();

        return Inertia::render('Frontend/Services/Show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
            'seo' => [
                'title_ar' => $service->name_ar . ' - عيادة أورا ديرما',
                'title_en' => $service->name_en . ' - AURA Derma Clinic',
                'description_ar' => $service->short_desc_ar ?? $service->seo_desc_ar ?? '',
                'description_en' => $service->short_desc_en ?? $service->seo_desc_en ?? '',
                'image' => $service->featured_image ?? '',
                'keywords' => $service->name_ar . ', ' . $service->name_en . ', عيادة أورا ديرما, AURA Derma',
            ],
        ]);
    }
}
