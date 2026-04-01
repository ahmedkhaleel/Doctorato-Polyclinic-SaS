<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = PostCategory::withCount('posts')
            ->when($request->search, function ($query, $search) {
                $query->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/PostCategories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PostCategories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $postCategory = PostCategory::create($data);

        AuditLogger::log('created', $postCategory);

        return redirect()->route('admin.post-categories.index')->with('success', 'Post category created successfully.');
    }

    public function edit(PostCategory $postCategory): Response
    {
        return Inertia::render('Admin/PostCategories/Edit', [
            'category' => $postCategory,
        ]);
    }

    public function update(Request $request, PostCategory $postCategory): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $postCategory->update($data);

        AuditLogger::log('updated', $postCategory);

        return redirect()->route('admin.post-categories.index')->with('success', 'Post category updated successfully.');
    }

    public function destroy(PostCategory $postCategory): RedirectResponse
    {
        AuditLogger::log('deleted', $postCategory);
        $postCategory->delete();

        return redirect()->route('admin.post-categories.index')->with('success', 'Post category deleted successfully.');
    }
}
