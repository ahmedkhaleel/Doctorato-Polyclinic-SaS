<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends AdminPostController
{
    public function index(Request $request): Response
    {
        $posts = Post::with(['category', 'author'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title_ar', 'like', "%{$search}%")
                      ->orWhere('title_en', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Webmaster/Posts/Index', [
            'posts' => $posts,
            'filters' => $request->only(['search', 'status', 'category_id']),
        ]);
    }

    public function create(): Response
    {
        $categories = PostCategory::all();
        $tags = Tag::all();

        return Inertia::render('Webmaster/Posts/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->sanitizeFields($data, ['content_ar', 'content_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('uploads/posts', 'public');
        }

        $data['slug'] = $data['slug'] ?? Str::slug($data['title_en']);
        $data['author_id'] = $data['author_id'] ?? auth()->id();

        $post = Post::create($data);

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        AuditLogger::log('created', $post);

        return redirect()->route('webmaster.posts.index')->with('success', 'Post created successfully.');
    }

    public function edit(Post $post): Response
    {
        $post->load(['category', 'tags']);
        $categories = PostCategory::all();
        $tags = Tag::all();

        return Inertia::render('Webmaster/Posts/Edit', [
            'post' => $post,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();
        $this->sanitizeFields($data, ['content_ar', 'content_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('uploads/posts', 'public');
        }

        if (isset($data['slug'])) {
            $data['slug'] = $data['slug'] ?: Str::slug($data['title_en']);
        }

        $post->update($data);

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        AuditLogger::log('updated', $post);

        return redirect()->route('webmaster.posts.index')->with('success', 'Post updated successfully.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        // Delete featured image from storage
        if ($post->featured_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($post->featured_image);
        }

        AuditLogger::log('deleted', $post);
        $post->tags()->detach();
        $post->delete();

        return redirect()->route('webmaster.posts.index')->with('success', 'Post deleted successfully.');
    }
}
