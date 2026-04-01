<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $galleryItems = Gallery::visible()
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Frontend/Gallery', [
            'galleryItems' => $galleryItems,
            'seo' => SeoService::get('gallery'),
        ]);
    }
}
