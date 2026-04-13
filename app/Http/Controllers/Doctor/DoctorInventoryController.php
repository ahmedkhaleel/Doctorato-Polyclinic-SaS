<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Supply;
use App\Models\SupplyCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorInventoryController extends BaseDoctorController
{
    public function index(Request $request): Response
    {
        $query = Supply::active()->with('supplyCategory');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($module = $request->input('module')) {
            $query->forModule($module);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('supply_category_id', $categoryId);
        }

        if ($request->input('stock') === 'low') {
            $query->lowStock();
        }

        $supplies = $query->orderBy('name_en')->paginate(20)->withQueryString();

        $categories = SupplyCategory::active()->ordered()->get();

        // Summary stats
        $stats = [
            'total' => Supply::active()->count(),
            'lowStock' => Supply::active()->lowStock()->count(),
        ];

        return Inertia::render('Doctor/Inventory/Index', [
            'supplies' => $supplies,
            'categories' => $categories,
            'filters' => $request->only(['search', 'module', 'category_id', 'stock']),
            'stats' => $stats,
        ]);
    }
}
