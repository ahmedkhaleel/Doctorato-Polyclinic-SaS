<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplyCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplyCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = SupplyCategory::ordered()
            ->withCount('supplies')
            ->get();

        return Inertia::render('Admin/Supplies/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category = SupplyCategory::create($data);

        AuditLogger::log('created', $category);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, SupplyCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update($data);

        AuditLogger::log('updated', $category);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy(SupplyCategory $category): RedirectResponse
    {
        if ($category->supplies()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category with existing supplies.');
        }

        AuditLogger::log('deleted', $category);

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
