<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPackage;
use App\Models\CosmeticProcedure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticPackageController extends Controller
{
    public function index(Request $request)
    {
        $query = CosmeticPackage::with('procedure:id,name_ar,name_en');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('name_en', 'like', "%$s%");
            });
        }
        if ($request->filled('procedure_id')) $query->where('procedure_id', $request->procedure_id);

        return Inertia::render('Admin/Cosmetic/Packages/Index', [
            'packages' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'procedure_id']),
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request)
    {
        CosmeticPackage::create($this->validated($request));
        return back()->with('success', 'تم الإنشاء');
    }

    public function update(Request $request, CosmeticPackage $package)
    {
        $package->update($this->validated($request));
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(CosmeticPackage $package)
    {
        $package->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'total_sessions' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'validity_days' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
