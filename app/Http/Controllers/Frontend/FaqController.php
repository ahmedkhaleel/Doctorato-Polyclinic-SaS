<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        $faqs = Faq::orderBy('display_order')->get();

        return Inertia::render('Frontend/Faq', [
            'faqs' => $faqs,
            'seo' => SeoService::get('faq'),
        ]);
    }
}
