<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Page;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function show(string $locale, string $slug): Response
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return Inertia::render('Frontend/Page', [
            'page' => $page,
            'seo' => [
                'title_ar' => $page->seo_title_ar ?? $page->title_ar ?? '',
                'title_en' => $page->seo_title_en ?? $page->title_en ?? '',
                'description_ar' => $page->seo_desc_ar ?? $page->meta_description_ar ?? '',
                'description_en' => $page->seo_desc_en ?? $page->meta_description_en ?? '',
            ],
        ]);
    }

    public function about(): Response
    {
        $page = Page::where('slug', 'about')->first();

        $doctors = Doctor::active()
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Frontend/About', [
            'page' => $page,
            'doctors' => $doctors,
            'seo' => SeoService::get('about'),
        ]);
    }
}
