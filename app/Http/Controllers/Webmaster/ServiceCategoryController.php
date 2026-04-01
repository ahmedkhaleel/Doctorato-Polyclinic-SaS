<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Admin\ServiceCategoryController as AdminServiceCategoryController;
use App\Models\ServiceCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends AdminServiceCategoryController
{
    public function index(Request $request): Response
    {
        $categories = ServiceCategory::withCount('services')
            ->when($request->search, function ($query, $search) {
                $query->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Webmaster/ServiceCategories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Webmaster/ServiceCategories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $serviceCategory = ServiceCategory::create($data);

        AuditLogger::log('created', $serviceCategory);

        return redirect()->route('webmaster.service-categories.index')->with('success', 'Service category created successfully.');
    }

    public function edit(ServiceCategory $serviceCategory): Response
    {
        return Inertia::render('Webmaster/ServiceCategories/Edit', [
            'category' => $serviceCategory,
        ]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $serviceCategory->update($data);

        AuditLogger::log('updated', $serviceCategory);

        return redirect()->route('webmaster.service-categories.index')->with('success', 'Service category updated successfully.');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        AuditLogger::log('deleted', $serviceCategory);
        $serviceCategory->delete();

        return redirect()->route('webmaster.service-categories.index')->with('success', 'Service category deleted successfully.');
    }
}
