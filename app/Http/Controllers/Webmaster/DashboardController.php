<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Doctor;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\HeroSlide;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Webmaster/Dashboard', [
            'stats' => [
                'services'     => Service::count(),
                'doctors'      => Doctor::count(),
                'gallery'      => Gallery::count(),
                // The original "offers" model never existed — surface website-popup
                // discount codes instead, which is the actual marketing artifact.
                'offers'       => DiscountCode::where('show_on_website', true)
                                              ->where('is_active', true)->count(),
                'posts'        => Post::count(),
                'testimonials' => Testimonial::count(),
                'faqs'         => Faq::count(),
                'slides'       => HeroSlide::where('is_active', true)->count(),
            ],
            'recentPosts' => Post::latest()->take(5)
                ->get(['id', 'title_en', 'title_ar', 'status', 'created_at']),
        ]);
    }
}
