<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticProcedure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticProcedureController extends Controller
{
    public function index(Request $request)
    {
        $query = CosmeticProcedure::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('name_en', 'like', "%$s%");
            });
        }
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('active') && $request->active !== '') $query->where('is_active', $request->active === '1');

        return Inertia::render('Admin/Cosmetic/Procedures/Index', [
            'procedures' => $query->orderBy('display_order')->orderBy('name_ar')->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'category', 'active']),
            'categories' => CosmeticProcedure::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        CosmeticProcedure::create($this->validated($request));
        return back()->with('success', 'تم الإنشاء');
    }

    public function update(Request $request, CosmeticProcedure $procedure)
    {
        $procedure->update($this->validated($request));
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(CosmeticProcedure $procedure)
    {
        $procedure->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'category' => 'required|in:' . implode(',', CosmeticProcedure::CATEGORIES),
            'description' => 'nullable|string',
            'default_price' => 'nullable|numeric|min:0',
            'default_duration_minutes' => 'nullable|integer|min:0',
            'recovery_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);
    }
}
