<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticConsent;
use App\Models\CosmeticConsentTemplate;
use App\Models\CosmeticPackage;
use App\Models\CosmeticProcedure;
use App\Models\CosmeticSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\CosmeticDermaInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'procedures' => CosmeticProcedure::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'category', 'default_price', 'supply_id', 'default_consumption_qty']),
            'packages' => CosmeticPackage::where('is_active', true)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'procedure_id']),
            'patients' => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone']),
            'doctors' => Doctor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'supplies' => \App\Models\Supply::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'unit', 'quantity']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->enforceConsent($data);
        $session = CosmeticSession::create($data);
        $this->invoicing->generateForCosmeticSession($session);
        $this->invoicing->consumeInventoryForCosmeticSession($session->fresh());
        return back()->with('success', 'تم إضافة الجلسة');
    }

    /**
     * Block a completed cosmetic session when its procedure has an active
     * consent template flagged requires_signature and the patient has no
     * signed consent on file (for that procedure, or a general one).
     * No template / no requires_signature → no enforcement (backward safe).
     */
    private function enforceConsent(array $data): void
    {
        if (empty($data['completed_at']) || empty($data['procedure_id'])) {
            return;
        }

        $needsConsent = CosmeticConsentTemplate::active()
            ->where('requires_signature', true)
            ->where(fn ($q) => $q->where('procedure_id', $data['procedure_id'])->orWhereNull('procedure_id'))
            ->exists();

        if (! $needsConsent) {
            return;
        }

        $hasSigned = CosmeticConsent::where('patient_id', $data['patient_id'])
            ->whereNotNull('signed_at')
            ->where(fn ($q) => $q->where('procedure_id', $data['procedure_id'])->orWhereNull('procedure_id'))
            ->exists();

        if (! $hasSigned) {
            throw ValidationException::withMessages([
                'consent' => 'يتطلب هذا الإجراء موافقة موقّعة من المريض قبل تنفيذ الجلسة. / This procedure requires a signed patient consent before the session.',
            ]);
        }
    }

    public function update(Request $request, CosmeticSession $session)
    {
        $data = $this->validated($request);
        $this->enforceConsent($data + ['completed_at' => $data['completed_at'] ?? $session->completed_at]);
        $session->update($data);
        $fresh = $session->fresh();

        if ($fresh->completed_at === null) {
            // Un-completed → reverse any prior billing / inventory draw.
            $this->invoicing->reverseBilling($fresh);
            $this->invoicing->reverseInventoryForCosmeticSession($fresh->fresh());
        } else {
            // Bill + draw inventory on completion (both idempotent — never twice).
            $this->invoicing->generateForCosmeticSession($fresh);
            $this->invoicing->consumeInventoryForCosmeticSession($fresh->fresh());
        }
        // Keep the prepaid-package balance coherent either way.
        $this->invoicing->syncPackageBalance($fresh->packagePurchase);

        return back()->with('success', 'تم التحديث');
    }

    public function destroy(CosmeticSession $session)
    {
        $purchase = $session->packagePurchase;
        // Undo financial + inventory side effects before removing the row.
        $this->invoicing->reverseBilling($session);
        $this->invoicing->reverseInventoryForCosmeticSession($session->fresh());
        $session->delete();
        $this->invoicing->syncPackageBalance($purchase);
        return back()->with('success', 'تم الحذف');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'package_id' => 'nullable|exists:cosmetic_packages,id',
            'package_purchase_id' => 'nullable|exists:cosmetic_package_purchases,id',
            'procedure_id' => 'nullable|exists:cosmetic_procedures,id',
            'supply_id' => 'nullable|exists:supplies,id',
            'consumption_qty' => 'nullable|numeric|min:0',
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
