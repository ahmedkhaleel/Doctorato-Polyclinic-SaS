<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPackage;
use App\Models\CosmeticProcedure;
use App\Models\CosmeticSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\CosmeticDermaInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticSessionController extends Controller
{
    public function __construct(private CosmeticDermaInvoiceService $invoicing) {}

    public function index(Request $request)
    {
        $query = CosmeticSession::with([
            'patient:id,full_name,phone',
            'doctor:id,name_ar,name_en',
            'procedure:id,name_ar,name_en,category',
            'package:id,name_ar,name_en',
        ]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('patient', fn($q) => $q->where('full_name', 'like', "%$s%"));
        }
        if ($request->filled('procedure_id')) $query->where('procedure_id', $request->procedure_id);
        if ($request->filled('patient_id')) $query->where('patient_id', $request->patient_id);

        return Inertia::render('Admin/Cosmetic/Sessions/Index', [
            'sessions' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'procedure_id', 'patient_id']),
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'category', 'default_price']),
            'packages' => CosmeticPackage::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'procedure_id']),
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
            'doctors' => Doctor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
        ]);
    }

    public function store(Request $request)
    {
        $session = CosmeticSession::create($this->validated($request));
        $this->invoicing->generateForCosmeticSession($session);
        return back()->with('success', 'تم إضافة الجلسة');
    }

    public function update(Request $request, CosmeticSession $session)
    {
        $session->update($this->validated($request));
        // Bill on completion (idempotent — never invoices the same session twice).
        $this->invoicing->generateForCosmeticSession($session->fresh());
        return back()->with('success', 'تم التحديث');
    }

    public function destroy(CosmeticSession $session)
    {
        $session->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'package_id' => 'nullable|exists:cosmetic_packages,id',
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'visit_id' => 'nullable|exists:visits,id',
            'session_number' => 'nullable|integer|min:1',
            'area_treated' => 'nullable|string|max:255',
            'product_used' => 'nullable|string|max:255',
            'dose_units' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
    }
}
