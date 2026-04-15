<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ModuleManager;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $enabledModules = array_keys(ModuleManager::getActiveModules());

        $categories = ServiceCategory::whereIn('module', $enabledModules)
            ->whereHas('services', fn ($q) => $q->active()->website())
            ->with(['services' => function ($query) {
                $query->active()->website()->orderBy('display_order');
            }])
            ->orderBy('display_order')
            ->get();

        // Group categories by module for frontend tabs
        $activeModules = [];
        foreach (ModuleManager::MEDICAL_MODULES as $mod) {
            if (ModuleManager::isEnabled($mod)) {
                $info = ModuleManager::getModuleInfo($mod);
                $activeModules[] = [
                    'slug' => $mod,
                    'name_ar' => $info['name_ar'],
                    'name_en' => $info['name_en'],
                    'color' => $info['color'],
                ];
            }
        }

        return Inertia::render('Frontend/Services/Index', [
            'categories' => $categories,
            'activeModules' => $activeModules,
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
                'title_ar' => $service->name_ar . ' - عيادة دكتوراتو',
                'title_en' => $service->name_en . ' - Doctorato Polyclinic',
                'description_ar' => $service->short_desc_ar ?? $service->seo_desc_ar ?? '',
                'description_en' => $service->short_desc_en ?? $service->seo_desc_en ?? '',
                'image' => $service->featured_image ?? '',
                'keywords' => $service->name_ar . ', ' . $service->name_en . ', عيادة دكتوراتو, Doctorato',
            ],
        ]);
    }
}
