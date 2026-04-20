<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = Post::published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(9);

        $categories = PostCategory::withCount(['posts' => function ($query) {
            $query->published();
        }])->get();

        $featuredPost = Post::published()
            ->featured()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->first();

        return Inertia::render('Frontend/Blog/Index', [
            'posts' => $posts,
            'categories' => $categories,
            'featuredPost' => $featuredPost,
            'seo' => SeoService::get('blog'),
        ]);
    }

    public function show(string $locale, string $slug): Response
    {
        $post = Post::where('slug', $slug)
            ->published()
            ->with(['category', 'author', 'tags'])
            ->firstOrFail();

        $relatedPosts = Post::published()
            ->where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('Frontend/Blog/Show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'seo' => [
                'title_ar' => ($post->title_ar ?? '') . ' - مدونة دكتوراتو',
                'title_en' => ($post->title_en ?? '') . ' - AURA Derma Blog',
                'description_ar' => $post->excerpt_ar ?? '',
                'description_en' => $post->excerpt_en ?? '',
                'image' => $post->featured_image ?? $post->image ?? '',
                'keywords' => ($post->title_ar ?? '') . ', ' . ($post->title_en ?? ''),
            ],
        ]);
    }
}
