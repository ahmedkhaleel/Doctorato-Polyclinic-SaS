<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends AdminServiceController
{
    public function index(Request $request): Response
    {
        $services = Service::with('category')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                      ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        $categories = ServiceCategory::all();

        return Inertia::render('Webmaster/Services/Index', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function create(): Response
    {
        $categories = ServiceCategory::orderBy('display_order')->get();

        return Inertia::render('Webmaster/Services/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'short_desc_ar' => 'nullable|string',
            'short_desc_en' => 'nullable|string',
            'full_desc_ar' => 'nullable|string',
            'full_desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'benefits_ar' => 'nullable|string',
            'benefits_en' => 'nullable|string',
            'sessions_count' => 'nullable|integer',
            'results_ar' => 'nullable|string',
            'results_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'show_on_home' => 'boolean',
            'show_on_website' => 'boolean',
            'bookable' => 'boolean',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_desc_ar' => 'nullable|string',
            'seo_desc_en' => 'nullable|string',
        ]);

        $this->sanitizeFields($data, ['full_desc_ar', 'full_desc_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->processAndStoreImage($request->file('featured_image'), 'uploads/services');
        }

        $slug = $data['slug'] ?? Str::slug($data['name_en']);
        $originalSlug = $slug;
        $counter = 1;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        $data['slug'] = $slug;

        $service = Service::create($data);

        AuditLogger::log('created', $service);

        return redirect()->route('webmaster.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): Response
    {
        $service->load(['category', 'faqs', 'gallery']);
        $categories = ServiceCategory::orderBy('display_order')->get();

        return Inertia::render('Webmaster/Services/Edit', [
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug,' . $service->id,
            'short_desc_ar' => 'nullable|string',
            'short_desc_en' => 'nullable|string',
            'full_desc_ar' => 'nullable|string',
            'full_desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'benefits_ar' => 'nullable|string',
            'benefits_en' => 'nullable|string',
            'sessions_count' => 'nullable|integer',
            'results_ar' => 'nullable|string',
            'results_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'show_on_home' => 'boolean',
            'show_on_website' => 'boolean',
            'bookable' => 'boolean',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_desc_ar' => 'nullable|string',
            'seo_desc_en' => 'nullable|string',
        ]);

        $this->sanitizeFields($data, ['full_desc_ar', 'full_desc_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->processAndStoreImage($request->file('featured_image'), 'uploads/services');
        }

        if (isset($data['slug'])) {
            $data['slug'] = $data['slug'] ?: Str::slug($data['name_en']);
        }

        $service->update($data);

        AuditLogger::log('updated', $service);

        return redirect()->route('webmaster.services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $storage = \Illuminate\Support\Facades\Storage::disk('public');

        // Delete featured image
        if ($service->featured_image) {
            $storage->delete($service->featured_image);
        }

        // Delete gallery image files
        foreach ($service->gallery as $galleryItem) {
            foreach (['image_path', 'before_image', 'after_image'] as $field) {
                if ($galleryItem->$field) {
                    $storage->delete($galleryItem->$field);
                }
            }
        }

        AuditLogger::log('deleted', $service);
        $service->faqs()->delete();
        $service->gallery()->delete();
        $service->delete();

        return redirect()->route('webmaster.services.index')->with('success', 'Service deleted successfully.');
    }
}
