<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::withCount('supplies')
            ->when($request->search, fn ($q, $s) => $q->where('name_ar', 'like', "%{$s}%")->orWhere('name_en', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->when($request->status === 'active', fn ($q) => $q->active())
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name_en')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:suppliers,code',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);
        AuditLogger::log('created', $supplier);

        return back()->with('success', 'Supplier created successfully');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:suppliers,code,' . $supplier->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);
        AuditLogger::log('updated', $supplier);

        return back()->with('success', 'Supplier updated');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->supplies()->exists()) {
            return back()->with('error', 'Cannot delete — supplier has linked supplies');
        }

        $supplier->delete();
        AuditLogger::log('deleted', $supplier);

        return back()->with('success', 'Supplier deleted');
    }
}
